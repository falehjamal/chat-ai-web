<?php
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
    sendSSE(['error' => 'No data received'], 'error');
    exit;
}

$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendSSE(['error' => 'Invalid JSON data'], 'error');
    exit;
}

$userMessage = $data['message'] ?? '';
$selectedModel = $data['model'] ?? ModelConfig::getDefaultModelForMode('uas-math');
$imageBase64 = $data['image'] ?? '';

// Mode UAS Matematika bisa dengan atau tanpa gambar
// Tidak ada validasi wajib gambar

// Validate API key
$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    sendSSE(['error' => 'API key tidak ditemukan'], 'error');
    exit;
}

// Siapkan pesan untuk OpenAI API dengan Vision
$messages = [];

// Sistem prompt untuk menyelesaikan soal matematika
$systemMessage = "Anda adalah asisten AI yang ahli dalam matematika dan pemecahan soal. Tugas Anda meliputi:
1. Jika terdapat gambar: Analisis gambar yang berisi soal matematika.
2. Jika hanya teks: Jawab pertanyaan secara langsung.
3. Identifikasi jenis soal (misalnya aljabar, kalkulus, geometri, dll.).
4. Berikan jawaban akhir yang akurat.
5. Gunakan format yang mudah dipahami, dengan penjelasan yang jelas dan terstruktur.

Fokuskan pada pemahaman konsep dan langkah-langkah logis dalam penyelesaian soal.";

// Tambahkan pesan sistem ke array
$messages[] = [
    'role' => 'system',
    'content' => $systemMessage,
];

// Cek apakah ada gambar yang dikirim (berformat base64)
if (!empty($imageBase64)) {
    // Mode dengan gambar
    $userContent = [
        [
            'type' => 'text',
            'text' => $userMessage ?: 'Tolong analisis dan selesaikan soal dalam gambar ini.'
        ],
        [
            'type' => 'image_url',
            'image_url' => [
                'url' => $imageBase64,
                'detail' => 'high'
            ]
        ]
    ];
} else {
    // Mode teks saja
    $userContent = $userMessage ?: 'Tolong bantu saya dengan soal ini.';
}

// Tambahkan ke messages
$messages[] = [
    'role' => 'user',
    'content' => $userContent
];

// Validate and get model configuration
if (!ModelConfig::isValidModel($selectedModel)) {
    sendSSE(['error' => "Model '$selectedModel' tidak valid atau tidak aktif"], 'error');
    exit;
}

$modelConfig = ModelConfig::getApiConfig($selectedModel);

// Prepare OpenAI API request
$requestData = [
    'model' => $modelConfig['model'],
    'messages' => $messages,
    'stream' => true,
    'max_tokens' => $modelConfig['max_tokens'],
    'temperature' => 0.3 // Lower temperature for more consistent math solutions
];

// Initialize cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_WRITEFUNCTION => function($ch, $data) {
        // Add debug logging
        error_log("UAS Math API: Received data chunk: " . strlen($data) . " bytes");
        
        // Process streaming data
        $lines = explode("\n", $data);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || $line === 'data: [DONE]') {
                continue;
            }
            
            if (strpos($line, 'data: ') === 0) {
                $jsonData = substr($line, 6);
                $decoded = json_decode($jsonData, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['choices'][0]['delta']['content'])) {
                    $content = $decoded['choices'][0]['delta']['content'];
                    error_log("UAS Math API: Sending content: " . substr($content, 0, 50) . "...");
                    sendSSE(['content' => $content], 'stream');
                } else {
                    error_log("UAS Math API: Could not parse JSON or no content: " . $jsonData);
                }
            }
        }
        
        return strlen($data);
    },
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

// Execute the request
sendSSE(['status' => 'Menganalisis gambar dan memproses soal matematika...'], 'status');

// Add debug logging
error_log("UAS Math API: Starting request to OpenAI");
error_log("UAS Math API: Model = " . $selectedModel);
error_log("UAS Math API: Image length = " . strlen($imageBase64));

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Add debug logging
error_log("UAS Math API: HTTP Code = " . $httpCode);
error_log("UAS Math API: cURL Error = " . $error);

curl_close($ch);

if ($result === false) {
    error_log("UAS Math API: cURL failed - " . $error);
    sendSSE(['error' => 'cURL error: ' . $error], 'error');
} elseif ($httpCode !== 200) {
    error_log("UAS Math API: HTTP Error - " . $httpCode);
    sendSSE(['error' => 'HTTP error: ' . $httpCode], 'error');
} else {
    error_log("UAS Math API: Request completed successfully");
    sendSSE(['status' => 'completed'], 'complete');
}
?>
