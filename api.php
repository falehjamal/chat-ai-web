<?php
header('Content-Type: application/json');

// Load environment helper
require_once 'env_helper.php';

try {
    // Load environment variables
    loadEnv();
} catch (Exception $e) {
    echo json_encode(['error' => 'Gagal memuat konfigurasi: ' . $e->getMessage()]);
    exit;
}

// Get the incoming message and history from the frontend
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';
$chatHistory = $data['history'] ?? [];

if (!$userMessage) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Create context from chat history
$contextMessage = "";
if (!empty($chatHistory)) {
    foreach ($chatHistory as $msg) {
        $role = ($msg['sender'] == 'user') ? 'User' : 'Assistant';
        $contextMessage .= "$role: " . $msg['text'] . "\n";
    }
}
$contextMessage .= "User: " . $userMessage;

// Get API key from environment
$apiKey = getEnvironmentVar('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['error' => 'API Key Gemini tidak ditemukan dalam konfigurasi']);
    exit;
}

// Define the API URL with key from environment
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

// Prepare the payload with context
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Jawab singkat padat dan jelas. ". $contextMessage]
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
    'Content-Type: application/json'
]);

// Execute the request
$response = curl_exec($ch);
curl_close($ch);

// Parse the response
$responseData = json_decode($response, true);
if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['reply' => $botReply]);
} else {
    echo json_encode(['error' => 'Failed to get a response from the AI model']);
}
?>
