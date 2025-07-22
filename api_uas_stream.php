<?php
// Load environment helper
require_once 'env_helper.php';

// Load database helper
require_once 'database.php';

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
    
    // Inisialisasi database dan buat tabel jika diperlukan
    $database = getChatDatabase();
    $database->initialize();
} catch (Exception $e) {
    sendSSE(['error' => 'Gagal memuat konfigurasi: ' . $e->getMessage()], 'error');
    exit;
}

// Get and validate the incoming data
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    // Jika tidak ada POST data, ambil dari GET parameters
    $userMessage = $_GET['message'] ?? '';
    $selectedModel = $_GET['model'] ?? 'gpt-4o';
} else {
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendSSE(['error' => 'Invalid JSON data'], 'error');
        exit;
    }
    $userMessage = $data['message'] ?? '';
    $selectedModel = $data['model'] ?? 'gpt-4o'; // Default to GPT-4o untuk UAS
}

if (empty($userMessage)) {
    sendSSE(['error' => 'No message provided'], 'error');
    exit;
}

// Create system prompt for UAS mode - no history context needed
$systemPrompt = "Anda adalah asisten AI yang membantu mahasiswa menjawab soal UAS. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat dan jelas.";


// Get API key from environment
$apiKey = getEnvironmentVar('OPENAI_API_KEY');
if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
    sendSSE(['error' => 'OpenAI API Key tidak dikonfigurasi dengan benar'], 'error');
    exit;
}

// Function to normalize model name
function normalizeModelName($model) {
    $modelMap = [
        'gpt-4.1' => 'gpt-4.1-2025-04-14',  // GPT-4.1 mapped to GPT-4
        'gpt-4o' => 'gpt-4o-2024-08-06',  // GPT-4o tetap
        'gpt-3.5-turbo' => 'gpt-3.5-turbo-0125'  // GPT-3.5 Turbo tetap
    ];
    
    return $modelMap[$model] ?? 'gpt-4o'; // Default fallback untuk UAS mode
}

$normalizedModel = normalizeModelName($selectedModel);

// Send typing indicator
sendSSE(['type' => 'typing_start'], 'status');

// Prepare the payload for ChatGPT with streaming
$data = [
    'model' => $normalizedModel,
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.3, // Suhu yang lebih rendah untuk jawaban yang lebih fokus
    'max_tokens' => 1000,
    'stream' => true // Enable streaming
];

// Initialize cURL for streaming
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'User-Agent: ChatAI-Web-UAS/1.0'
]);

// Set up streaming callback
$fullResponse = '';
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$fullResponse, $userMessage, $database) {
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
                
                // Save to database with UAS mode indicator
                try {
                    $ipAddress = Database::getRealIpAddress();
                    $database->saveChatHistory($userMessage, $fullResponse, $ipAddress, 'uas');
                } catch (Exception $e) {
                    error_log("Gagal menyimpan chat history UAS: " . $e->getMessage());
                }
                
                sendSSE([
                    'type' => 'complete',
                    'mode' => 'uas',
                    'status' => 'success'
                ], 'complete');
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
                    'full_text' => $fullResponse,
                    'mode' => 'uas'
                ], 'chunk');
            }
        }
    }
    
    return strlen($chunk);
});

// Execute the request
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

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
