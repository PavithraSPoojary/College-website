<?php  
function handleFileUpload($fileField, $uploadDir, $allowedTypes, $pdo, $userId) {     
    $response = ['success' => false, 'message' => ''];          
    
    try {         
        // Validate file size (max 5MB)         
        if ($_FILES[$fileField]['size'] > 5000000) {             
            throw new Exception("File size too large. Maximum 5MB allowed.");         
        }                  
        
        // Validate file type         
        if (!in_array($_FILES[$fileField]['type'], $allowedTypes)) {             
            throw new Exception("Only " . implode(', ', $allowedTypes) . " files are allowed.");         
        }                  
        
        // Generate unique filename         
        $filename = uniqid() . '_' . $_FILES[$fileField]['name'];         
        $targetPath = $uploadDir . $filename;                  
        
        // Create upload directory if it doesn't exist         
        if (!file_exists($uploadDir)) {             
            mkdir($uploadDir, 0777, true);         
        }                  
        
        // Move uploaded file         
        if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $targetPath)) {             
            // Update database with new file path             
            $stmt = $pdo->prepare("UPDATE applications SET " . $fileField . "_path = ? WHERE user_id = ?");             
            $stmt->execute([$targetPath, $userId]);                          
            
            $response['success'] = true;             
            $response['message'] = ucfirst($fileField) . ' uploaded successfully';         
        } else {             
            throw new Exception("Failed to move uploaded file");         
        }     
    } catch (Exception $e) {         
        $response['message'] = $e->getMessage();     
    }          
    
    return $response; 
}  

function handleAutoSaveUploads($pdo, $userId) {     
    $response = ['success' => false, 'message' => ''];          
    
    try {         
        // Handle photo upload         
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {             
            $response = handleFileUpload('photo', 'uploads/photos/', ['image/jpeg', 'image/png', 'image/jpg'], $pdo, $userId);         
        }                  
        
        // Handle ID proof upload         
        if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {             
            $response = handleFileUpload('id_proof', 'uploads/id_proofs/', ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'], $pdo, $userId);         
        }     
    } catch (Exception $e) {         
        $response['message'] = $e->getMessage();     
    }          
    
    return $response; 
} 
?>