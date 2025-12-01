<?php
// similarity_checker.php - PHP API caller for Python similarity checker
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        error_log('=== SIMILARITY CHECKER START ===');
        error_log('Request received');
        
        $uploadedFile = $_FILES['file'];
        error_log('Uploaded file: ' . $uploadedFile['name']);
        
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            error_log('File upload error: ' . $uploadedFile['error']);
            echo json_encode(['error' => 'File upload error: ' . $uploadedFile['error']]);
            exit;
        }
        
        // Check existing thesis files
        $thesisFolder = 'uploads/thesis/';
        if (!is_dir($thesisFolder)) {
            error_log('Thesis folder not found at: ' . realpath($thesisFolder));
            echo json_encode(['error' => 'Thesis folder not found']);
            exit;
        }
        
        // Get all existing thesis files
        $existingFiles = [];
        $files = glob($thesisFolder . '*');
        error_log('Found ' . count($files) . ' files in thesis folder');
        
        foreach ($files as $filePath) {
            if (is_file($filePath)) {
                $existingFiles[] = [
                    'filename' => basename($filePath),
                    'path' => $filePath
                ];
                error_log('Added to comparison: ' . basename($filePath));
            }
        }
        
        if (empty($existingFiles)) {
            error_log('No existing files found');
            echo json_encode([
                'max_similarity' => 0,
                'message' => '✅ No existing thesis files found to compare against. You can submit your thesis.',
                'similar_file' => null,
                'can_submit' => true
            ]);
            exit;
        }
        
        error_log('Calling Flask API at: http://localhost:5000/api/check_similarity');
        
        // Call Python API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:5000/api/check_similarity');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($uploadedFile['tmp_name'], $uploadedFile['type'], $uploadedFile['name']),
            'existing_files' => json_encode($existingFiles)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        error_log('Flask API Response Code: ' . $httpCode);
        error_log('Curl Error: ' . $curlError);
        error_log('Flask API Response: ' . substr($response, 0, 500));
        
        if (curl_errno($ch)) {
            error_log('Curl Error Number: ' . curl_errno($ch));
            curl_close($ch);
            echo json_encode(['error' => 'Failed to connect to similarity checking service: ' . $curlError]);
            exit;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Flask returned non-200 status');
            echo json_encode(['error' => 'Similarity checking service returned error: HTTP ' . $httpCode]);
            exit;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            echo json_encode(['error' => 'Invalid response from similarity checking service: ' . json_last_error_msg()]);
            exit;
        }
        
        error_log('=== SIMILARITY CHECKER END ===');
        error_log('Returning response to client');
        echo $response; // Forward the Python API response
        
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage());
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>