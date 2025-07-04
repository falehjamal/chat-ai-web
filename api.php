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
$chatHistory = $data['history'] ?? [];

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Create context from chat history
$contextMessage = "";
if (!empty($chatHistory)) {
    foreach ($chatHistory as $msg) {
        if (!is_array($msg) || !isset($msg['sender']) || !isset($msg['text'])) {
            continue;
        }
        
        $historyText = $msg['text'];
        if (empty($historyText)) {
            continue;
        }
        
        $role = ($msg['sender'] == 'user') ? 'User' : 'Assistant';
        $contextMessage .= "$role: " . $historyText . "\n";
    }
}
$contextMessage .= "User: " . $userMessage;

// Get API key from environment
$apiKey = getEnvironmentVar('GEMINI_API_KEY');
if (!$apiKey || $apiKey === 'your_gemini_api_key_here') {
    echo json_encode(['error' => 'API Key tidak dikonfigurasi dengan benar']);
    exit;
}

// Define the API URL with key from environment
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

// Prepare the payload with context
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Tolong jawab dengan singkat,padat,dan jelas.\n". $contextMessage]
            ]
        ]
    ]
];

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: ChatAI-Web/1.0'
]);

// Basic timeout settings
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($response === false || !empty($curlError)) {
    echo json_encode(['error' => 'Gagal menghubungi layanan AI. Silakan coba lagi.']);
    exit;
}

// Check HTTP status code
if ($httpCode !== 200) {
    echo json_encode(['error' => 'Layanan AI sedang tidak tersedia. Silakan coba lagi.']);
    exit;
}

// Parse the response
$responseData = json_decode($response, true);

// Check for JSON parsing errors
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Gagal memproses respons dari AI']);
    exit;
}

// Check if response has expected structure
if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    
    // Clean up the response
    $botReply = cleanResponse($botReply);
    
    if (empty($botReply)) {
        echo json_encode(['error' => 'Respons AI kosong atau tidak valid']);
        exit;
    }
    
    // Simpan percakapan ke database
    try {
        $ipAddress = Database::getRealIpAddress();
        $database->saveChatHistory($userMessage, $botReply, $ipAddress);
    } catch (Exception $e) {
        // Log error tetapi tetap lanjutkan response
        error_log("Gagal menyimpan chat history: " . $e->getMessage());
    }
    
    echo json_encode(['reply' => $botReply]);
} else {
    echo json_encode(['error' => 'Gagal mendapatkan respons dari AI']);
}

/**
 * Clean up AI response by removing common prefixes
 * @param string $response The raw AI response
 * @return string The cleaned response
 */
function cleanResponse($response) {
    // Trim whitespace
    $response = trim($response);
    
    // Remove "Assistant:" prefix if present (case-insensitive)
    if (preg_match('/^Assistant:\s*/i', $response)) {
        $response = preg_replace('/^Assistant:\s*/i', '', $response);
    }
    
    // Remove other common prefixes that might appear
    $prefixes = [
        'AI:',
        'Bot:',
        'Chatbot:',
        'System:'
    ];
    
    foreach ($prefixes as $prefix) {
        if (stripos($response, $prefix) === 0) {
            $response = substr($response, strlen($prefix));
            break;
        }
    }
    
    // Trim again after prefix removal
    return trim($response);
}
?>
