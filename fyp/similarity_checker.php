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
        $uploadedFile = $_FILES['file'];
        
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'File upload error']);
            exit;
        }
        
        // Check existing thesis files
        $thesisFolder = 'uploads/thesis/';
        if (!is_dir($thesisFolder)) {
            echo json_encode(['error' => 'Thesis folder not found']);
            exit;
        }
        
        // Get all existing thesis files
        $existingFiles = [];
        $files = glob($thesisFolder . '*');
        
        foreach ($files as $filePath) {
            if (is_file($filePath)) {
                $existingFiles[] = [
                    'filename' => basename($filePath),
                    'path' => $filePath
                ];
            }
        }
        
        if (empty($existingFiles)) {
            echo json_encode([
                'max_similarity' => 0,
                'message' => '✅ No existing thesis files found to compare against. You can submit your thesis.',
                'similar_file' => null,
                'can_submit' => true
            ]);
            exit;
        }
        
        // Prepare data for Python API
        $postData = [
            'existing_files' => json_encode($existingFiles)
        ];
        
        // Call Python API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:5000/api/check_similarity');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($uploadedFile['tmp_name'], $uploadedFile['type'], $uploadedFile['name']),
            'existing_files' => json_encode($existingFiles)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            echo json_encode(['error' => 'Failed to connect to similarity checking service']);
            exit;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo json_encode(['error' => 'Similarity checking service returned error']);
            exit;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Invalid response from similarity checking service']);
            exit;
        }
        
        echo $response; // Forward the Python API response
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>