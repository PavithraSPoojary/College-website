<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

try {
    // Get the latest status from database
    $stmt = $pdo->prepare("SELECT status, updated_at FROM applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $application = $stmt->fetch();

    if ($application) {
        // Compare with the status in session
        $current_status = isset($_SESSION['last_status']) ? $_SESSION['last_status'] : null;
        $new_status = $application['status'];
        
        if ($current_status !== $new_status) {
            // Update session with new status
            $_SESSION['last_status'] = $new_status;
            echo json_encode(['status_updated' => true]);
        } else {
            echo json_encode(['status_updated' => false]);
        }
    } else {
        echo json_encode(['status_updated' => false]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
