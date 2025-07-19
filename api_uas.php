<?php
// Load environment helper
require_once 'env_helper.php';

// Load database helper
require_once 'database.php';

header('Content-Type: application/json');

try {
    // Load environment variables
    loadEnv();
    
    // Inisialisasi database dan buat tabel jika diperlukan
    $database = getChatDatabase();
    $database->initialize();
} catch (Exception $e) {
    echo json_encode(['error' => 'Gagal memuat konfigurasi: ' . $e->getMessage()]);
    exit;
}

// Get and validate the incoming data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Get inputs directly
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Create system prompt for UAS mode - no history context needed
$systemPrompt = "Anda adalah asisten AI yang membantu mahasiswa dalam menjawab soal ujian akhir semester (UAS). Jawaban harus singkat, padat, jelas, dan relevan dengan pertanyaan.";

// Get API key from environment
$apiKey = getEnvironmentVar('OPENAI_API_KEY');
if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
    echo json_encode(['error' => 'OpenAI API Key tidak dikonfigurasi dengan benar']);
    exit;
}

// Define the OpenAI API URL
$url = 'https://api.openai.com/v1/chat/completions';

// Prepare the payload for ChatGPT
$data = [
    'model' => 'gpt-4o', // Versi GPT-4o, yang stabil untuk API
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.3, // Suhu yang lebih rendah untuk jawaban yang lebih fokus
    'max_tokens' => 1000,
];

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    'User-Agent: ChatAI-Web-UAS/1.0'
]);

// Extended timeout settings for detailed responses
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($curlError) {
    echo json_encode(['error' => 'Network error: ' . $curlError]);
    exit;
}

// Check HTTP status
if ($httpCode !== 200) {
    $errorMessage = "API request failed with status: $httpCode";
    if ($response) {
        $errorData = json_decode($response, true);
        if (isset($errorData['error']['message'])) {
            $errorMessage .= " - " . $errorData['error']['message'];
        }
    }
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Parse the response
$responseData = json_decode($response, true);

if (!$responseData) {
    echo json_encode(['error' => 'Invalid response from API']);
    exit;
}

// Extract the generated text
if (isset($responseData['choices'][0]['message']['content'])) {
    $botReply = $responseData['choices'][0]['message']['content'];
    
    // Clean up the response
    $botReply = trim($botReply);
    
    // Store in database with UAS mode indicator
    try {
        $ipAddress = Database::getRealIpAddress();
        $database->saveChatHistory($userMessage, $botReply, $ipAddress, 'uas');
    } catch (Exception $e) {
        // Log error but don't fail the response
        error_log('Database save error: ' . $e->getMessage());
    }
    
    echo json_encode([
        'reply' => $botReply,
        'mode' => 'uas',
        'status' => 'success'
    ]);
} else {
    echo json_encode(['error' => 'No response generated']);
}
?>
