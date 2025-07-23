<?php
// Set timezone to Asia/Jakarta (GMT+7)
date_default_timezone_set('Asia/Jakarta');

// Load environment helper
require_once 'env_helper.php';

// Load model configuration
require_once 'model_config.php';

// Set headers for SSE (Server-Sent Events)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Untuk nginx, agar tidak di-buffer

// Disable output buffering untuk streaming realtime
if (ob_get_level()) {
    ob_end_clean();
}
ob_implicit_flush(true);

// Fungsi untuk mengirim data SSE
function sendSSE($data, $event = 'message') {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

try {
    // Load environment variables
    loadEnv();
} catch (Exception $e) {
    sendSSE(['error' => 'Gagal memuat konfigurasi: ' . $e->getMessage()], 'error');
    exit;
}

// Get and validate the incoming data
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    // Jika tidak ada POST data, ambil dari GET parameters
    $userMessage = $_GET['message'] ?? '';
    $chatHistory = json_decode($_GET['history'] ?? '[]', true);
    $selectedModel = $_GET['model'] ?? 'gpt-4';
} else {
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendSSE(['error' => 'Invalid JSON data'], 'error');
        exit;
    }
    $userMessage = $data['message'] ?? '';
    $chatHistory = $data['history'] ?? [];
    $selectedModel = $data['model'] ?? ModelConfig::getDefaultModelForMode('default'); // Use centralized default
}

if (empty($userMessage)) {
    sendSSE(['error' => 'No message provided'], 'error');
    exit;
}

// Create context from chat history
$systemPrompt = "Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.";
$messages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

// Add chat history to messages
if (!empty($chatHistory)) {
    foreach ($chatHistory as $msg) {
        if (!is_array($msg) || !isset($msg['sender']) || !isset($msg['text'])) {
            continue;
        }
        
        $historyText = $msg['text'];
        if (empty($historyText)) {
            continue;
        }
        
        $role = ($msg['sender'] == 'user') ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => $historyText];
    }
}

// Add current user message
$messages[] = ['role' => 'user', 'content' => $userMessage];

// Get API key from environment
$apiKey = getEnvironmentVar('OPENAI_API_KEY');
if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
    sendSSE(['error' => 'OpenAI API Key tidak dikonfigurasi dengan benar'], 'error');
    exit;
}

// Validate and get model configuration
if (!ModelConfig::isValidModel($selectedModel)) {
    sendSSE(['error' => "Model '$selectedModel' tidak valid atau tidak aktif"], 'error');
    exit;
}

$modelConfig = ModelConfig::getApiConfig($selectedModel);

// Send typing indicator
sendSSE(['type' => 'typing_start'], 'status');

// Prepare the payload for ChatGPT with streaming
$data = [
    'model' => $modelConfig['model'],
    'messages' => $messages,
    'temperature' => $modelConfig['temperature'],
    'max_tokens' => $modelConfig['max_tokens'],
    'stream' => true // Enable streaming
];

// Initialize cURL for streaming
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'User-Agent: ChatAI-Web/1.0'
]);

// Set up streaming callback
$fullResponse = '';
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$fullResponse, $userMessage, $selectedModel) {
    $lines = explode("\n", $chunk);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) {
            continue;
        }
        
        if (strpos($line, 'data: ') === 0) {
            $payload = trim(substr($line, 6));
            
            // Check for completion
            if ($payload === '[DONE]') {
                // Send completion status
                sendSSE(['type' => 'typing_end', 'full_text' => $fullResponse], 'status');
                
                // Save to database if needed
                try {
                    require_once 'database.php';
                    require_once 'model_config.php';
                    
                    $database = getChatDatabase();
                    $database->initialize();
                    $ipAddress = Database::getRealIpAddress();
                    
                    // Calculate tokens and save chat history
                    $tokenCount = ModelConfig::estimateConversationTokens($userMessage, $fullResponse);
                    
                    $database->saveChatHistory($userMessage, $fullResponse, $ipAddress, 'default', $tokenCount, $selectedModel);
                } catch (Exception $e) {
                    error_log("Gagal menyimpan chat history: " . $e->getMessage());
                }
                
                sendSSE(['type' => 'complete'], 'complete');
                return strlen($chunk);
            }
            
            // Parse JSON response
            $json = json_decode($payload, true);
            if ($json && isset($json['choices'][0]['delta']['content'])) {
                $content = $json['choices'][0]['delta']['content'];
                $fullResponse .= $content;
                
                // Send the chunk to frontend
                sendSSE([
                    'type' => 'chunk',
                    'content' => $content,
                    'full_text' => $fullResponse
                ], 'chunk');
            }
        }
    }
    
    return strlen($chunk);
});

// Execute the request
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for errors
if ($curlError) {
    sendSSE(['error' => 'Network error: ' . $curlError], 'error');
    exit;
}

if ($httpCode !== 200) {
    sendSSE(['error' => "API request failed with status: $httpCode"], 'error');
    exit;
}

// If we reach here without any chunks, something went wrong
if (empty($fullResponse)) {
    sendSSE(['error' => 'No response received from AI'], 'error');
}

?>
