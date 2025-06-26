<?php
// Prevent caching


session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) { // 30 minutes
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();
require_once 'config/database.php';

// CSRF token setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Initialize variables
$errors = [];
$status_message = '';
$current_step = isset($_GET['step']) ? max(1, min(5, (int)$_GET['step'])) : 1;

// Fetch user and application data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=user_not_found");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $stmt = $pdo->prepare("INSERT INTO applications (user_id, status) VALUES (?, 'draft')");
        $stmt->execute([$_SESSION['user_id']]);
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
    error_log($e->getMessage());
}

// Determine editability
$can_edit = ($application && strtolower($application['status']) === 'draft');
if (!$can_edit && $application) {
    $status_message = match (strtolower($application['status'])) {
        'submitted' => 'Your application has been submitted and can no longer be edited.',
        'under_review' => 'Your application is under review and cannot be modified.',
        'approved' => 'Your application has been approved. No further changes allowed.',
        'rejected' => 'Your application has been processed. No changes permitted.',
        default => 'Your application cannot be edited at this time.'
    };
    $current_step = 5; // Force step 5 for non-draft applications
}

// Block POST if cannot edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$can_edit) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Application cannot be modified. Current status: ' . ucfirst($application['status'])
    ]);
    exit();
}

// CSRF validation for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid CSRF token";
        $current_step = isset($_POST['step']) ? max(1, min(5, (int)$_POST['step'])) : $current_step;
    }
}

// Handle file uploads separately for auto-save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auto_save']) && $_POST['auto_save'] == 1) {
    $response = ['success' => false, 'message' => ''];
    
    // Additional check for auto-save requests
    if (!$can_edit) {
        $response = [
            'success' => false, 
            'message' => 'Cannot upload files. Application status: ' . ucfirst($application['status'])
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    try {
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            // Validate file size (max 5MB)
            if ($_FILES['photo']['size'] > 5000000) {
                throw new Exception("File size too large. Maximum 5MB allowed.");
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['photo']['tmp_name']);
            finfo_close($file_info);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, and PNG are allowed.");
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload photo.");
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$upload_path, $_SESSION['user_id']]);
            
            $stmt = $pdo->prepare("UPDATE applications SET photo_path = ? WHERE user_id = ?");
            $stmt->execute([$upload_path, $_SESSION['user_id']]);
            
            $response = ['success' => true, 'message' => 'Photo uploaded successfully', 'path' => $upload_path];
        }
        
        // Handle ID proof upload
        if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
            // Validate file size (max 5MB)
            if ($_FILES['id_proof']['size'] > 5000000) {
                throw new Exception("File size too large. Maximum 5MB allowed.");
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['id_proof']['tmp_name']);
            finfo_close($file_info);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.");
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/id_proofs/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $ext = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['id_proof']['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload ID proof.");
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE applications SET id_proof_path = ? WHERE user_id = ?");
            $stmt->execute([$upload_path, $_SESSION['user_id']]);
            
            $response = ['success' => true, 'message' => 'ID proof uploaded successfully', 'path' => $upload_path];
        }
  } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['auto_save'])) {
    $step = isset($_POST['step']) ? max(1, min(5, (int)$_POST['step'])) : $current_step;
    $current_step = $step; // Set current step to the submitted step

    switch ($step) {
        case 1:
            // Personal Information
            $student_name = trim($_POST['student_name'] ?? '');
            $father_name = trim($_POST['father_name'] ?? '');
            $mother_name = trim($_POST['mother_name'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $date_of_birth = $_POST['date_of_birth'] ?? '';
            $category = $_POST['category'] ?? '';

            // Validation
            if (empty($student_name)) $errors[] = "Student name is required";
            if (empty($father_name)) $errors[] = "Father's name is required";
            if (empty($mother_name)) $errors[] = "Mother's name is required";
            if (empty($gender)) $errors[] = "Gender is required";
            if (empty($date_of_birth)) $errors[] = "Date of birth is required";
            if (empty($category)) $errors[] = "Category is required";

            if ($student_name && !preg_match('/^[a-zA-Z\s]{2,50}$/', $student_name)) {
                $errors[] = "Student name must be 2-50 characters, letters and spaces only";
            }
            if ($father_name && !preg_match('/^[a-zA-Z\s]{2,50}$/', $father_name)) {
                $errors[] = "Father's name must be 2-50 characters, letters and spaces only";
            }
            if ($mother_name && !preg_match('/^[a-zA-Z\s]{2,50}$/', $mother_name)) {
                $errors[] = "Mother's name must be 2-50 characters, letters and spaces only";
            }

            if ($date_of_birth) {
                $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
                $today = new DateTime();
                if (!$dob || $dob > $today) {
                    $errors[] = "Invalid date of birth";
                } elseif ($today->diff($dob)->y < 17) {
                    $errors[] = "You must be at least 17 years old";
                }
            }

              // Handle photo upload only if it wasn't auto-saved earlier
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                // Validate file size (max 5MB)
                if ($_FILES['photo']['size'] > 5000000) {
                    $errors[] = "File size too large. Maximum 5MB allowed.";
                }
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png'];
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $_FILES['photo']['tmp_name']);
                finfo_close($file_info);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $errors[] = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
                }
                
                if (empty($errors)) {
                    // Create upload directory if it doesn't exist
                    $upload_dir = 'uploads/photos/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                        try {
                            // Update both profile photo and application photo
                            $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                            $stmt->execute([$upload_path, $_SESSION['user_id']]);
                            
                            $stmt = $pdo->prepare("UPDATE applications SET photo_path = ? WHERE user_id = ?");
                            $stmt->execute([$upload_path, $_SESSION['user_id']]);
                        } catch(PDOException $e) {
                            $errors[] = "Failed to update photo";
                        }
                    } else {
                        $errors[] = "Failed to upload photo";
                    }
                }
            } elseif (!$application['photo_path']) {
                // If no photo was previously uploaded and no photo is being uploaded now
                $errors[] = "Profile photo is required";
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();
                    
                    if (isset($upload_path)) {
                        $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                        $stmt->execute([$upload_path, $_SESSION['user_id']]);
                        $stmt = $pdo->prepare("UPDATE applications SET photo_path = ? WHERE user_id = ?");
                        $stmt->execute([$upload_path, $_SESSION['user_id']]);
                    }

                    $stmt = $pdo->prepare("UPDATE applications SET 
                        student_name = ?, father_name = ?, mother_name = ?, 
                        gender = ?, date_of_birth = ?, category = ? 
                        WHERE user_id = ?");
                    $stmt->execute([
                        $student_name, $father_name, $mother_name,
                        $gender, $date_of_birth, $category,
                        $_SESSION['user_id']
                    ]);

                    $pdo->commit();
                    header("Location: dashboard.php?step=2");
                    exit();
                } catch(PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = "Failed to save personal information: " . $e->getMessage();
                }
            }
            $_SESSION['form_data'] = [
    'student_name' => $student_name,
    'father_name' => $father_name,
    'mother_name' => $mother_name,
    'gender' => $gender,
    'date_of_birth' => $date_of_birth,
    'category' => $category
];
            break;

        case 2:
            // Course and Languages
            $course_applied = $_POST['course_applied'] ?? '';
            $language_1 = $_POST['language_1'] ?? '';
            $language_2 = $_POST['language_2'] ?? '';

            // Validation
            if (empty($course_applied)) $errors[] = "Course selection is required";
            if (empty($language_1)) $errors[] = "First language is required";
            if (empty($language_2)) $errors[] = "Second language is required";
            if ($language_1 == $language_2 && !empty($language_1) && !empty($language_2)) {
                $errors[] = "Please select two different languages";
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE applications SET 
                        course_applied = ?, language_1 = ?, language_2 = ? 
                        WHERE user_id = ?");
                    $stmt->execute([
                        $course_applied, $language_1, $language_2,
                        $_SESSION['user_id']
                    ]);

                    header("Location: dashboard.php?step=3");
                    exit();
                } catch(PDOException $e) {
                    $errors[] = "Failed to save course and language information: " . $e->getMessage();
                }
            }
            break;

        case 3:
            // Academic Information
            $tenth_marks = trim($_POST['tenth_marks'] ?? '');
            $tenth_board = trim($_POST['tenth_board'] ?? '');
            $pu_college = trim($_POST['pu_college'] ?? '');
            $pu_stream = trim($_POST['pu_stream'] ?? '');
            $pu_marks = trim($_POST['pu_marks'] ?? '');
            $pu_board = trim($_POST['pu_board'] ?? '');

            // Validation
            if ($tenth_marks === '') $errors[] = "10th marks are required";
            if ($tenth_board === '') $errors[] = "10th board is required";
            if ($pu_college === '') $errors[] = "PU college is required";
            if ($pu_stream === '') $errors[] = "PU stream is required";
            if ($pu_marks === '') $errors[] = "PU marks are required";
            if ($pu_board === '') $errors[] = "PU board is required";

            if ($tenth_marks !== '' && (!is_numeric($tenth_marks) || $tenth_marks < 0 || $tenth_marks > 100)) {
                $errors[] = "10th marks must be a number between 0 and 100";
            }
            if ($pu_marks !== '' && (!is_numeric($pu_marks) || $pu_marks < 0 || $pu_marks > 100)) {
                $errors[] = "PU marks must be a number between 0 and 100";
            }
            if ($tenth_board && !preg_match("/^[a-zA-Z\s]{2,100}$/", $tenth_board)) {
                $errors[] = "10th board must be  letters and spaces only";
            }
            if ($pu_board && !preg_match("/^[a-zA-Z\s]{2,100}$/", $pu_board)) {
                $errors[] = "PU board must be  letters and spaces only";
            }
            if ($pu_stream && !preg_match("/^[a-zA-Z\s]{2,100}$/", $pu_stream)) {
                $errors[] = "PU stream must be  letters and spaces only";
            }
            if ($pu_college && !preg_match("/^[a-zA-Z\s]{2,100}$/", $pu_college)) {
                $errors[] = "PU college name invalid ( letters, spaces, dots, hyphens)";
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE applications SET 
                        tenth_marks = ?, tenth_board = ?, pu_college = ?, 
                        pu_stream = ?, pu_marks = ?, pu_board = ? 
                        WHERE user_id = ?");
                    $stmt->execute([
                        $tenth_marks, $tenth_board, $pu_college,
                        $pu_stream, $pu_marks, $pu_board,
                        $_SESSION['user_id']
                    ]);

                    header("Location: dashboard.php?step=4");
                    exit();
                } catch(PDOException $e) {
                    $errors[] = "Failed to save academic information: " . $e->getMessage();
                }
            }
            break;

        case 4:
            // Contact Information
            $address = trim($_POST['address'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contact_number = trim($_POST['contact_number'] ?? '');
            $whatsapp_number = trim($_POST['whatsapp_number'] ?? '');
            $hostel_required = $_POST['hostel_required'] ?? '';

            // Validation
            if (empty($address)) $errors[] = "Address is required";
            if (empty($email)) $errors[] = "Email is required";
            if (empty($contact_number)) $errors[] = "Contact number is required";
            if (empty($whatsapp_number)) $errors[] = "WhatsApp number is required";
            if (empty($hostel_required)) $errors[] = "Hostel requirement is required";

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            if ($contact_number && !preg_match('/^\d{10}$/', $contact_number)) {
                $errors[] = "Contact number must be 10 digits";
            }
            if ($whatsapp_number && !preg_match('/^\d{10}$/', $whatsapp_number)) {
                $errors[] = "WhatsApp number must be 10 digits";
            }
            if ($hostel_required && !in_array($hostel_required, ['Yes', 'No'])) {
                $errors[] = "Invalid hostel requirement selection";
            }
            if ($address && strlen($address) > 255) {
                $errors[] = "Address cannot exceed 255 characters";
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE applications SET 
                        address = ?, email = ?, contact_number = ?, 
                        whatsapp_number = ?, hostel_required = ? 
                        WHERE user_id = ?");
                    $stmt->execute([
                        $address, $email, $contact_number,
                        $whatsapp_number, $hostel_required,
                        $_SESSION['user_id']
                    ]);

                    header("Location: dashboard.php?step=5");
                    exit();
                } catch(PDOException $e) {
                    $errors[] = "Failed to save contact information: " . $e->getMessage();
                }
            }
            break;

        case 5:
               // Document Upload
            $id_proof_path = null;
            
            // Check if a file was already uploaded before
            $stmt = $pdo->prepare("SELECT id_proof_path FROM applications WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_app = $stmt->fetch();
            $id_proof_path = $current_app['id_proof_path'] ?? null;
            
            // Only process new upload if a file is selected
            if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
                // Validate file size (max 5MB)
                if ($_FILES['id_proof']['size'] > 5000000) {
                    $errors[] = "File size too large. Maximum 5MB allowed.";
                }
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $_FILES['id_proof']['tmp_name']);
                finfo_close($file_info);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.";
                }
                
                if (empty($errors)) {
                    // Create upload directory if it doesn't exist
                    $upload_dir = 'uploads/id_proofs/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $ext = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['id_proof']['tmp_name'], $upload_path)) {
                        $id_proof_path = $upload_path;
                    } else {
                        $errors[] = "Failed to upload ID proof";
                    }
                }
            }
            
            // If no ID proof exists and none was uploaded
            if (empty($id_proof_path)) {
                $errors[] = "ID proof document is required";
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE applications SET 
                        id_proof_path = ?, 
                        status = 'Submitted' 
                        WHERE user_id = ?");
                    
                    $stmt->execute([$id_proof_path, $_SESSION['user_id']]);
                    
                    // Add notification
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['user_id'], "Your application has been submitted successfully."]);
                    
                    header("Location: dashboard.php?submitted=1");
                    exit();
                } catch(PDOException $e) {
                    $errors[] = "Failed to submit application";
                }
            }
            break;
    }
}

$application_status = $application['status'] ?? 'draft';
$is_readonly = !$can_edit;
$_SESSION['form_errors'] = $errors; // Store errors for display
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };

window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

    window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType('navigation')[0].type === 'back_forward')) {
        window.location.reload();
    }
});
</script>
    <style>
        :root {
            --primary: #400057;        /* Main brand color - Purple */
            --secondary: #FFD700;      /* Accent color - Gold */
            --success: #28a745;        /* Success state */
            --warning: #ffc107;        /* Warning state */
            --danger: #dc3545;         /* Error state */
            --dark: #333;              /* Dark text color */
            --light: #f8f9fa;          /* Light background color */
            --white: #ffffff;          /* White */
            --gray: #6c757d;           /* Gray - Secondary text */
            --accent: #6a1b9a;         /* Darker Purple - Accent */
            --completed: #FFD700;      /* Gold for completed steps */
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            font-size: 16px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .dashboard-container {
            background: var(--white);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin: 20px 10px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
            font-size: clamp(12px, 2.5vw, 14px);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 10px;
            font-size: clamp(12px, 2.5vw, 14px);
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(64, 0, 87, 0.1);
        }



.btn-primary {
    background-color: var(--primary) !important; /* Override Bootstrap's default blue */
    border-color: var(--primary) !important;
    color: var(--white) !important;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: clamp(12px, 2.5vw, 14px);
}

.btn-primary:hover {
    background-color: var(--accent) !important; /* Use accent color on hover */
    border-color: var(--accent) !important;
    color: var(--white) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 27, 154, 0.2);
}

.btn-outline-primary {
    color: var(--primary) !important; /* Override Bootstrap's default blue */
    border-color: var(--primary) !important;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: clamp(12px, 2.5vw, 14px);
}

.btn-outline-primary:hover {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: var(--white) !important;
    transform: translateY(-2px);
}











        /* .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: clamp(12px, 2.5vw, 14px);
        } */
/* 
        .btn-primary:hover {
            background-color: var(--accent);
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 27, 154, 0.2);
        } */
/* 
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: clamp(12px, 2.5vw, 14px);
        } */

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            color: var(--danger);
            border-color: var(--danger);
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: clamp(12px, 2.5vw, 14px);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger);
            border-color: var(--danger);
            color: var(--white);
            transform: translateY(-2px);
        }



.progress-container {
    margin: 20px 0;
    padding: 0 15px;
    position: relative;
    z-index: 1;
    width: 100%;
    box-sizing: border-box;
}
.progress-steps {
    display: flex;
    flex-direction: row !important;
    justify-content: space-between;
    position: relative;
    margin-bottom: 40px;
    gap: 10px;
    flex-wrap: nowrap;
    width: 100%;
    box-sizing: border-box;
}
.progress-step {
    position: relative;
    flex: 1 0 auto;
    text-align: center;
    z-index: 2;
    min-width: 60px;
    max-width: 80px;
}
.step-number {
    width: clamp(28px, 6vw, 32px);
    height: clamp(28px, 6vw, 32px);
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: clamp(12px, 2.5vw, 13px);
    border: 2px solid #f0f0f0;
    transition: all 0.3s ease;
    margin: 0 auto;
}
.progress-step-label {
    position: absolute;
    top: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    font-size: clamp(9px, 2vw, 10px);
    color: white;
    font-weight: 500;
    white-space: nowrap; /* Prevent wrapping */
    max-width: 80px;
    text-overflow: ellipsis;
    overflow: hidden; /* Use ellipsis instead of wrapping */
    margin-top: 4px;
    z-index: 5;
    line-height: 1.2;
}

.progress {
    height: 4px;
    background-color: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
    position: relative;
    z-index: 1;
    width: 100%;
    margin-top: 30px;
}
       .progress-step.active .step-number {
    background-color: var(--primary);
    border-color: var(--primary);
    color: var(--white);
}
       .progress-step.completed .step-number {
    background-color: var(--completed);
    border-color: var(--completed);
    color: var,--dark;
}
 
      .progress-step.active .progress-step-label {
    color: var(--primary);
    font-weight: 600;
}

      .progress-step.completed .progress-step-label {
    color: var(--completed);
}
 
/* Responsive adjustments */
@media (min-width: 768px) {
    .progress-steps {
        margin-bottom: 60px;
        flex-direction: row !important;
        justify-content: space-between !important;
        gap: 15px;
    }

    .progress-step {
        min-width: 70px;
        max-width: 100px;
        flex: 1 0 auto;
    }

    .progress-step-label {
        font-size: clamp(10px, 2vw, 12px);
        max-width: 100px;
        top: calc(100% + 8px);
    }

    .progress {
        margin-top: 40px;
    }
}
@media (max-width: 767px) {
    .progress-steps {
        flex-direction: row !important;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        padding: 0 10px 40px 10px; /* Increased bottom padding for labels */
        scrollbar-width: none;
        gap: 8px;
        flex-wrap: nowrap;
        width: 100%;
        margin-bottom: 80px; /* Increased to ensure labels are fully visible */
        position: relative;
        box-sizing: border-box;
    }

    .progress-steps::-webkit-scrollbar {
        display: none;
    }

    .progress-step {
        flex: 0 0 auto;
        min-width: 50px;
        max-width: 60px;
        scroll-snap-align: center;
    }

    .progress-step-label {
        font-size: clamp(8px, 1.8vw, 9px);
        max-width: 60px;
        margin-top: 3px;
        top: calc(100% + 6px); /* Adjusted to ensure labels are fully visible */
        z-index: 5;
        white-space: nowrap; /* Prevent wrapping on mobile */
        overflow: hidden; /* Use ellipsis instead of wrapping */
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .step-number {
        width: 26px;
        height: 26px;
        font-size: 11px;
    }

    .progress {
        margin-top: 80px; /* Increased to give more space for labels */
        margin-bottom: 10px;
    }
}

    .progress-steps::-webkit-scrollbar {
        display: none;
    }

    .progress-step {
        flex: 0 0 auto;
        min-width: 50px;
        scroll-snap-align: center;
    }

    .progress-step-label {
        font-size: clamp(8px, 1.8vw, 9px);
        max-width: 60px;
        margin-top: 3px;
        top: calc(100% + 2px); /* Slightly adjusted for mobile */
        z-index: 3; /* Ensure labels are above the progress bar */
    }

    .step-number {
        width: 26px;
        height: 26px;
        font-size: 11px;
    }

    .progress {
        margin-top: 40px; /* Increased to give more space for labels */
        margin-bottom: 10px;
    }

       .progress-bar {
    background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
    transition: width 0.3s ease-in-out;
    height: 100%;
}

        .alert {
            border-radius: 8px;
            padding: 10px;
            font-size: clamp(12px, 2.5vw, 14px);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: var(--success);
            color: var(--success);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }

        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-color: var(--warning);
            color: #856404;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }

        .card-header {
            background-color: var(--light);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            color: var(--primary);
            padding: 10px 15px;
        }

        .modal-content {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .modal-header {
            background-color: var(--primary);
            color: var(--white);
            border-radius: 12px 12px 0 0;
            padding: 15px;
        }

        .modal-title {
            font-size: clamp(16px, 3vw, 18px);
        }

        .modal-body {
            padding: 15px;
        }

        .help-text {
            font-size: clamp(10px, 2vw, 12px);
            color: var(--gray);
            margin-top: 5px;
        }

        .status-badge {
            font-size: clamp(10px, 2vw, 12px);
            padding: 5px 10px;
            border-radius: 50px;
        }

        .status-badge.draft {
            background-color: var(--gray);
            color: var(--white);
        }

        .status-badge.submitted {
            background-color: var(--primary);
            color: var(--white);
        }

        .status-badge.under-review {
            background-color: var(--warning);
            color: var(--dark);
        }

        .status-badge.selected {
            background-color: var(--success);
            color: var(--white);
        }

        .status-badge.rejected {
            background-color: var(--danger);
            color: var(--white);
        }

        .img-thumbnail {
            border-radius: 8px;
            max-width: 100%;
            height: auto;
            padding: 4px;
        }

        .form-section {
            background: var(--white);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            position: relative;
    z-index: 2;
        }

        .section-title {
            font-size: clamp(18px, 4vw, 20px);
            color: var(--primary);
            margin-bottom: 15px;
        }

        .form-navigation {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding: 10px 15px;
            flex-wrap: wrap;
        }

        .form-navigation .btn {
            min-width: 100px;
            padding: 8px 16px;
            font-size: clamp(12px, 2.5vw, 14px);
        }

        .submission-message {
            text-align: center;
            padding: 20px;
            margin: 20px auto;
        }

        .submission-message h3 {
            font-size: clamp(20px, 5vw, 24px);
        }

        .submission-message p {
            font-size: clamp(12px, 3vw, 14px);
        }

        .header-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            padding: 10px 15px;
            flex-wrap: wrap;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            min-width: 90px;
            padding: 8px 16px;
            font-size: clamp(12px, 2.5vw, 14px);
        }

        .invalid-feedback {
            font-size: clamp(10px, 2vw, 12px);
            color: var(--danger);
        }

        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: var(--white);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .message-icon {
            font-size: clamp(18px, 4vw, 20px);
        }

        .upload-form {
            max-width: 100%;
            margin: 0 auto;
        }

     

        @media (max-width: 767px) {
            .dashboard-container {
                margin: 10px;
                padding: 15px;
            }

            .form-navigation {
                justify-content: center;
            }

            .form-navigation .btn {
                width: 100%;
                max-width: 150px;
            }

            .header-container {
                justify-content: center;
            }

            .action-buttons {
                justify-content: center;
            }

            .modal-dialog {
                margin: 10px;
                max-width: 95vw;
            }

            .img-thumbnail {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            body {
                font-size: 14px;
            }

            .dashboard-container {
                padding: 10px;
            }

            .form-control, .form-select {
                padding: 8px;
                font-size: 12px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .section-title {
                font-size: 16px;
            }

            .modal-body {
                padding: 10px;
            }

            .card-header {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10">
                <div class="dashboard-container">
                    <!-- Display error messages if any -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                     <?php 
                    // Check if form should be disabled (any status other than 'draft')
                    $isFormDisabled = $application && strtolower($application['status']) !== 'draft';
                    $disabledAttr = $isFormDisabled ? 'disabled' : '';
                    ?>
                    
                    <div class="header-container">
                        <div class="action-buttons">
                            <?php if ($application && $application['status'] != 'Draft'): ?>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewApplicationModal">
                                    View Application
                                </button>
                            <?php endif; ?>
                            <a href="profile.php" class="btn btn-outline-primary">Profile</a>
                            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                        </div>
                    </div>
                    <div class="progress-container">
                        <div class="progress-steps">
                            <div class="progress-step <?php echo $current_step >= 1 ? 'completed' : ''; ?> <?php echo $current_step == 1 ? 'active' : ''; ?>">
                                <div class="progress-step-label">Personal Info</div>
                                <div class="step-number">1</div>
                            </div>
                            <div class="progress-step <?php echo $current_step >= 2 ? 'completed' : ''; ?> <?php echo $current_step == 2 ? 'active' : ''; ?>">
                                <div class="progress-step-label">Course</div>
                                <div class="step-number">2</div>
                            </div>
                            <div class="progress-step <?php echo $current_step >= 3 ? 'completed' : ''; ?> <?php echo $current_step == 3 ? 'active' : ''; ?>">
                                <div class="progress-step-label">Academic</div>
                                <div class="step-number">3</div>
                            </div>
                            <div class="progress-step <?php echo $current_step >= 4 ? 'completed' : ''; ?> <?php echo $current_step == 4 ? 'active' : ''; ?>">
                                <div class="progress-step-label">Contact Info</div>
                                <div class="step-number">4</div>
                            </div>
                            <div class="progress-step <?php echo $current_step >= 5 ? 'completed' : ''; ?> <?php echo $current_step == 5 ? 'active' : ''; ?>">
                                <div class="progress-step-label">Documents</div>
                                <div class="step-number">5</div>
                            </div>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo ($current_step / 5) * 100; ?>%"
                                 aria-valuenow="<?php echo $current_step; ?>" aria-valuemin="1" aria-valuemax="5"></div>
                        </div>
                    </div>

                    <?php if ($application && strtolower($application['status']) !== 'draft'): ?>
                        <div class="form-section">
                            <div class="submission-message">
                                <?php if (isset($_GET['submitted']) && $_GET['submitted'] == 1): ?>
                                    <div class="message-icon">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <h3 class="text-success">Application Submitted Successfully!</h3>
                                    <p class="text-muted">Your application has been submitted successfully. You will be notified via email about the status updates.</p>
                                <?php else: ?>
                                    <div class="message-content">
                                        <p>Your application status is: <strong><?php echo ucfirst($application['status']); ?></strong>. You cannot edit your application as it has been <?php echo strtolower($application['status']); ?>.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Start the form for editable steps -->
                        <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <!-- Include CSRF token and current step as hidden inputs -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="step" value="<?php echo $current_step; ?>">

                            <?php if ($current_step == 1 && $can_edit): ?>
                                <!-- Step 1: Personal Information -->
                                <div class="form-section">
                                    <h3 class="section-title">Personal Information</h3>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="student_name" class="form-label">Student's Name</label>
                                            <input type="text" class="form-control" id="student_name" name="student_name"
                                                   value="<?php echo htmlspecialchars($application['student_name'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="father_name" class="form-label">Father's Name</label>
                                            <input type="text" class="form-control" id="father_name" name="father_name"
                                                   value="<?php echo htmlspecialchars($application['father_name'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="mother_name" class="form-label">Mother's Name</label>
                                            <input type="text" class="form-control" id="mother_name" name="mother_name"
                                                   value="<?php echo htmlspecialchars($application['mother_name'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select" id="gender" name="gender" required <?php echo $disabledAttr; ?>>
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo ($application['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($application['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo ($application['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                                   value="<?php echo $application['date_of_birth'] ?? ''; ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select" id="category" name="category" required <?php echo $disabledAttr; ?>>
                                                <option value="">Select Category</option>
                                                <option value="General" <?php echo ($application['category'] ?? '') == 'General' ? 'selected' : ''; ?>>General</option>
                                                <option value="SC" <?php echo ($application['category'] ?? '') == 'SC' ? 'selected' : ''; ?>>SC</option>
                                                <option value="ST" <?php echo ($application['category'] ?? '') == 'ST' ? 'selected' : ''; ?>>ST</option>
                                                <option value="OBC" <?php echo ($application['category'] ?? '') == 'OBC' ? 'selected' : ''; ?>>OBC</option>
                                                <!-- <option value="EWS" <?php echo ($application['category'] ?? '') == 'EWS' ? 'selected' : ''; ?>>EWS</option> -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
    <label for="photo" class="form-label">Profile Photo</label>
    <?php if (!empty($application['photo_path'])): ?>
        <div class="mb-2">
            <img src="serve_file.php?file=<?php echo urlencode(str_replace('uploads/', '', $application['photo_path'])); ?>" 
                 alt="Profile Photo" class="img-thumbnail" style="max-width: 120px;">
            <div class="small text-muted mt-1">Current photo uploaded</div>
        </div>
    <?php endif; ?>
    <input type="file" class="form-control" id="photo" name="photo" accept="image/*"
           <?php echo empty($application['photo_path']) ? 'required' : ''; ?> <?php echo $disabledAttr; ?>>
    <div class="help-text">Upload a recent passport-size photograph (JPG, JPEG, or PNG)</div>
    <?php if (!empty($errors) && in_array('Profile photo is required', $errors)): ?>
        <div class="invalid-feedback" style="display:block;">Profile photo is required.</div>
    <?php endif; ?>
</div>

                            <?php elseif ($current_step == 2 && $can_edit): ?>
                                <!-- Step 2: Course and Languages -->
                                <div class="form-section">
                                    <h3 class="section-title">Course and Language Selection</h3>
                                    <div class="mb-3">
                                        <label for="course_applied" class="form-label">Course Applying For</label>
                                        <select class="form-select" id="course_applied" name="course_applied" required <?php echo $disabledAttr; ?>>
                                            <option value="">Select Course</option>
                                            <option value="BCom General" <?php echo ($application['course_applied'] ?? '') == 'BCom General' ? 'selected' : ''; ?>>BCom General</option>
                                            <option value="BCA" <?php echo ($application['course_applied'] ?? '') == 'BCA' ? 'selected' : ''; ?>>BCA</option>
                                            <option value="BBA" <?php echo ($application['course_applied'] ?? '') == 'BBA' ? 'selected' : ''; ?>>BBA</option>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="language_1" class="form-label">First Language</label>
                                            <select class="form-select" id="language_1" name="language_1" required <?php echo $disabledAttr; ?>>
                                                <option value="">Select Language</option>
                                                <option value="English" <?php echo ($application['language_1'] ?? '') == 'English' ? 'selected' : ''; ?>>English</option>
                                                <option value="Hindi" <?php echo ($application['language_1'] ?? '') == 'Hindi' ? 'selected' : ''; ?>>Hindi</option>
                                                <option value="Kannada" <?php echo ($application['language_1'] ?? '') == 'Kannada' ? 'selected' : ''; ?>>Kannada</option>
                                                <option value="Sanskrit" <?php echo ($application['language_1'] ?? '') == 'Sanskrit' ? 'selected' : ''; ?>>Sanskrit</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="language_2" class="form-label">Second Language</label>
                                            <select class="form-select" id="language_2" name="language_2" required <?php echo $disabledAttr; ?>>
                                                <option value="">Select Language</option>
                                                <option value="English" <?php echo ($application['language_2'] ?? '') == 'English' ? 'selected' : ''; ?>>English</option>
                                                <option value="Hindi" <?php echo ($application['language_2'] ?? '') == 'Hindi' ? 'selected' : ''; ?>>Hindi</option>
                                                <option value="Kannada" <?php echo ($application['language_2'] ?? '') == 'Kannada' ? 'selected' : ''; ?>>Kannada</option>
                                                <option value="Sanskrit" <?php echo ($application['language_2'] ?? '') == 'Sanskrit' ? 'selected' : ''; ?>>Sanskrit</option>
                                            </select>
                                            <div class="invalid-feedback" id="language-error" style="display: none;">
                                                Please select two different languages.
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!$isFormDisabled): ?>
                                        <div class="alert alert-warning mt-3" id="language-warning" style="display: none; padding: 15px; border: 2px solid #ffc107; background: #fffbe6; color: #856404; font-weight: bold; font-size: 16px;">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>Please select two different languages</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($current_step == 3 && $can_edit): ?>
                                <!-- Step 3: Academic Information -->
                                <div class="form-section">
                                    <h3 class="section-title">Academic Information</h3>
                                    <h4>10th Standard</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tenth_marks" class="form-label">Percentage of Marks</label>
                                            <input type="number" step="0.01" class="form-control" id="tenth_marks" name="tenth_marks"
                                                   value="<?php echo $application['tenth_marks'] ?? ''; ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="tenth_board" class="form-label">Board Studied</label>
                                            <input type="text" class="form-control" id="tenth_board" name="tenth_board"
                                                   value="<?php echo htmlspecialchars($application['tenth_board'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                    </div>
                                    <h4>PU College</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="pu_college" class="form-label">PU College Studied</label>
                                            <input type="text" class="form-control" id="pu_college" name="pu_college"
                                                   value="<?php echo htmlspecialchars($application['pu_college'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="pu_stream" class="form-label">Stream</label>
                                            <select class="form-select" id="pu_stream" name="pu_stream" required <?php echo $disabledAttr; ?>>
                                                <option value="">Select Stream</option>
                                                <option value="Science" <?php echo ($application['pu_stream'] ?? '') == 'Science' ? 'selected' : ''; ?>>Science</option>
                                                <option value="Commerce" <?php echo ($application['pu_stream'] ?? '') == 'Commerce' ? 'selected' : ''; ?>>Commerce</option>
                                                <option value="Arts" <?php echo ($application['pu_stream'] ?? '') == 'Arts' ? 'selected' : ''; ?>>Arts</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="pu_marks" class="form-label">Percentage of Marks in Second PU Exam</label>
                                            <input type="number" step="0.01" class="form-control" id="pu_marks" name="pu_marks"
                                                   value="<?php echo $application['pu_marks'] ?? ''; ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="pu_board" class="form-label">PU Board Studied</label>
                                            <input type="text" class="form-control" id="pu_board" name="pu_board"
                                                   value="<?php echo htmlspecialchars($application['pu_board'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($current_step == 4 && $can_edit): ?>
                                <!-- Step 4: Contact Information -->
                                <div class="form-section">
                                    <h3 class="section-title">Contact Information</h3>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address for Communication</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required <?php echo $disabledAttr; ?>><?php echo htmlspecialchars($application['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">E-mail Address</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   value="<?php echo htmlspecialchars($application['email'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_number" class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control <?php if (!empty($errors) && in_array('Contact number must be 10 digits', $errors)) echo 'is-invalid'; ?>" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($application['contact_number'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                            <?php if (!empty($errors) && in_array('Contact number must be 10 digits', $errors)): ?>
                                                <div class="invalid-feedback" style="display:block;">Contact number must be 10 digits.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
                                            <input type="tel" class="form-control <?php if (!empty($errors) && in_array('WhatsApp number must be 10 digits', $errors)) echo 'is-invalid'; ?>" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($application['whatsapp_number'] ?? ''); ?>" required <?php echo $disabledAttr; ?>>
                                            <?php if (!empty($errors) && in_array('WhatsApp number must be 10 digits', $errors)): ?>
                                                <div class="invalid-feedback" style="display:block;">WhatsApp number must be 10 digits.</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="hostel_required" class="form-label">Do you need Hostel Facility? (Applicable for Girl students only)</label>
                                            <select class="form-select" id="hostel_required" name="hostel_required" required <?php echo $disabledAttr; ?>>
                                                <option value="">Select Option</option>
                                                <option value="Yes" <?php echo ($application['hostel_required'] ?? '') == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                                <option value="No" <?php echo ($application['hostel_required'] ?? '') == 'No' ? 'selected' : ''; ?>>No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($current_step == 5 && $can_edit): ?>
                               

<!-- Step 5: Document Upload -->
<div class="form-section">
    <h3 class="section-title">Document Upload</h3>
    <div class="upload-form">
        <div class="mb-3">
            <label for="id_proof" class="form-label">ID Proof (Aadhar Card/Passport)</label>
            
            <!-- Display existing ID proof if available -->
            <?php if (!empty($application['id_proof_path'])): ?>
                <div class="mb-2">
                    <?php 
                    $file_extension = strtolower(pathinfo($application['id_proof_path'], PATHINFO_EXTENSION));
                    $file_param = urlencode(str_replace('uploads/', '', $application['id_proof_path']));
                    ?>
                    
                    <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                        <!-- Display image preview -->
                        <img src="serve_file.php?file=<?php echo $file_param; ?>" alt="ID Proof" class="img-thumbnail" style="max-width: 200px;">
                    <?php elseif ($file_extension === 'pdf'): ?>
                        <!-- Display PDF link -->
                        <div class="alert alert-info">
                            <i class="fas fa-file-pdf"></i>
                            <a href="serve_file.php?file=<?php echo $file_param; ?>" target="_blank" class="text-decoration-none">
                                View uploaded PDF document
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="small text-muted mt-1">Current ID proof uploaded</div>
                </div>
            <?php endif; ?>
            
            <input type="file" class="form-control" id="id_proof" name="id_proof" accept=".jpg,.jpeg,.png,.pdf" 
                   <?php echo empty($application['id_proof_path']) ? 'required' : ''; ?> <?php echo $disabledAttr; ?>>
            <div class="help-text">Upload a scanned copy of your ID proof (JPG, JPEG, PNG, or PDF)</div>
            
            <?php if (!empty($errors) && in_array('ID proof is required', $errors)): ?>
                <div class="invalid-feedback" style="display:block;">ID proof is required.</div>
            <?php endif; ?>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle"></i>
            <p>Please review your application carefully. Once submitted, you cannot make changes.</p>
        </div>
    </div>
</div>
                            <?php endif; ?>

                            <!-- Form Navigation -->
                            <div class="form-navigation" style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 40px; padding: 20px 40px;">
                                <?php if ($current_step > 1 && !$isFormDisabled): ?>
                                    <button type="button" class="btn btn-outline-primary" style="min-width: 120px; padding: 10px 24px;" onclick="window.location.href='?step=<?php echo $current_step - 1; ?>'">Previous</button>
                                <?php endif; ?>
                                <?php if ($current_step < 5 && !$isFormDisabled): ?>
                                    <button type="submit" class="btn btn-primary" style="min-width: 120px; padding: 10px 24px;">Next</button>
                                <?php elseif ($current_step == 5 && !$isFormDisabled): ?>
                                    <button type="submit" class="btn btn-primary" style="min-width: 120px; padding: 10px 24px; background-color: var(--primary); border-color: var(--primary); color: white; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(64, 0, 87, 0.2);">Submit Application</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Application Modal -->
    <div class="modal fade" id="viewApplicationModal" tabindex="-1" aria-labelledby="viewApplicationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary);">
                    <h5 class="modal-title" id="viewApplicationModalLabel" style="color: white;">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                <strong>Name:</strong> <?php echo htmlspecialchars($application['student_name']); ?><br>
                <strong>Father's Name:</strong> <?php echo htmlspecialchars($application['father_name']); ?><br>
                <strong>Mother's Name:</strong> <?php echo htmlspecialchars($application['mother_name']); ?><br>
                <strong>Gender:</strong> <?php echo htmlspecialchars($application['gender']); ?><br>
                <strong>Date of Birth:</strong> <?php echo htmlspecialchars($application['date_of_birth']); ?><br>
                <strong>Category:</strong> <?php echo htmlspecialchars($application['category']); ?><br>
            </div>
            <div class="col-md-6 mb-3 text-center">
                <?php if (!empty($application['photo_path'])): ?>
                    <img src="serve_file.php?file=<?php echo urlencode(str_replace('uploads/', '', $application['photo_path'])); ?>" alt="Profile Photo" class="img-thumbnail mb-2" style="max-width: 120px;">
                    <div class="small text-muted">Profile Photo</div>
                <?php else: ?>
                    <div class="small text-muted">No photo uploaded</div>
                <?php endif; ?>
            </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Course and Languages</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Course Applied:</strong> <?php echo htmlspecialchars($application['course_applied']); ?></p>
                            <p><strong>First Language:</strong> <?php echo htmlspecialchars($application['language_1']); ?></p>
                            <p><strong>Second Language:</strong> <?php echo htmlspecialchars($application['language_2']); ?></p>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Academic Information</h5>
                        </div>
                        <div class="card-body">
                            <h6>10th Standard</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Marks:</strong> <?php echo htmlspecialchars($application['tenth_marks']); ?>%</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Board:</strong> <?php echo htmlspecialchars($application['tenth_board']); ?></p>
                                </div>
                            </div>

                            <h6 class="mt-3">PU College</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>College:</strong> <?php echo htmlspecialchars($application['pu_college']); ?></p>
                                    <p><strong>Stream:</strong> <?php echo htmlspecialchars($application['pu_stream']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Marks:</strong> <?php echo htmlspecialchars($application['pu_marks']); ?>%</p>
                                    <p><strong>Board:</strong> <?php echo htmlspecialchars($application['pu_board']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($application['address'])); ?></p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($application['contact_number']); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>WhatsApp Number:</strong> <?php echo htmlspecialchars($application['whatsapp_number']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Hostel Required:</strong> <?php echo htmlspecialchars($application['hostel_required']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Submitted Documents</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                <p><strong>Profile Photo:</strong></p>
                <?php if (!empty($application['photo_path'])): ?>
                    <img src="serve_file.php?file=<?php echo urlencode(str_replace('uploads/', '', $application['photo_path'])); ?>" alt="Profile Photo" class="img-thumbnail" style="max-width: 200px;">
                <?php else: ?>
                    <p class="text-muted">No photo uploaded</p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <p><strong>ID Proof:</strong></p>
                <?php if (!empty($application['id_proof_path'])): ?>
                    <?php 
                        $file_extension = strtolower(pathinfo($application['id_proof_path'], PATHINFO_EXTENSION));
                        $file_param = urlencode(str_replace('uploads/', '', $application['id_proof_path']));
                    ?>
                    <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                        <img src="serve_file.php?file=<?php echo $file_param; ?>" alt="ID Proof" class="img-thumbnail" style="max-width: 200px;">
                    <?php elseif ($file_extension === 'pdf'): ?>
                        <div class="alert alert-info p-2 d-inline-block">
                            <i class="fas fa-file-pdf"></i>
                            <a href="serve_file.php?file=<?php echo $file_param; ?>" target="_blank" class="text-decoration-none">
                                View uploaded PDF document
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="serve_file.php?file=<?php echo $file_param; ?>" target="_blank" class="btn btn-sm btn-primary">View ID Proof</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No ID proof uploaded</p>
                <?php endif; ?>
            </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('check_session.php', {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    window.location.href = 'login.php?timeout=1';
                }
            })
            .catch(error => {
                console.error('Error checking session:', error);
                window.location.href = 'login.php?error=session_check_failed';
            });
            // Language validation
            var language1El = document.getElementById('language_1');
            var language2El = document.getElementById('language_2');
            var warning = document.getElementById('language-warning');
            var error = document.getElementById('language-error');
            var form = language1El && language1El.form;

            function validateLanguages() {
                var language1 = language1El.value;
                var language2 = language2El.value;
                if (
                    language1 && language2 &&
                    language1 !== "" && language2 !== "" &&
                    language1 === language2
                ) {
                    warning.style.display = 'block';
                    error.style.display = 'block';
                    language2El.classList.add('is-invalid');
                    language2El.setCustomValidity('Please select two different languages');
                } else {
                    warning.style.display = 'none';
                    error.style.display = 'none';
                    language2El.classList.remove('is-invalid');
                    language2El.setCustomValidity('');
                }
            }

            if (language1El && language2El && warning && error) {
                language1El.addEventListener('change', validateLanguages);
                language2El.addEventListener('change', validateLanguages);
                language1El.addEventListener('input', validateLanguages);
                language2El.addEventListener('input', validateLanguages);
                language2El.addEventListener('invalid', function() {
                    validateLanguages();
                });
                
                if (form) {
                    form.addEventListener('submit', function(e){
                        validateLanguages();
                        if (!form.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });
                }
            }
            
           var dobInput = document.getElementById('date_of_birth');
if (dobInput) {
    dobInput.addEventListener('change', function() {
        var dob = new Date(this.value);
        var today = new Date();
        var age = today.getFullYear() - dob.getFullYear();
        var m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }

        if (age < 17) {
            this.setCustomValidity('You must be at least 17 years old');
            this.reportValidity();
        } else {
            this.setCustomValidity('');
        }
    });
}
            // Phone number validation
            var phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    if (!/^\d{0,10}$/.test(this.value)) {
                        this.value = this.value.slice(0, -1);
                    }
                });
            });
            
            // Form validation
            var forms = document.querySelectorAll('.needs-validation');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        });
        // Check status and disable fields if not draft
function toggleFieldsBasedOnStatus(status) {
    var allInputs = document.querySelectorAll('input, select, textarea');
    var isDraft = status === 'draft';
    
    allInputs.forEach(function(field) {
        field.disabled = !isDraft;
        if (!isDraft) {
            field.classList.add('disabled-field');
        }
    });
}
    </script>
</body>
</html>