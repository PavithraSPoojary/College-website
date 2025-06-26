<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Pragma: no-cache");
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=user_not_found");
        exit();
    }

    // Fetch application data if exists
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $application = $stmt->fetch();

} catch(PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $filename = $_FILES['profile_photo']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_filename = uniqid() . '.' . $ext;
        $upload_path = 'uploads/profile_photos/' . $new_filename;
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        if (!file_exists('uploads/profile_photos')) {
            mkdir('uploads/profile_photos', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$upload_path, $_SESSION['user_id']]);
                $success = "Profile photo updated successfully!";
                $user['profile_photo'] = $upload_path;
            } catch(PDOException $e) {
                $error = "Failed to update profile photo: " . $e->getMessage();
            }
        } else {
            $error = "Failed to upload photo. Please try again.";
        }
    } else {
        $error = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
    }
}

// Set default profile photo if none exists
if (!isset($user['profile_photo']) || empty($user['profile_photo'])) {
    $user['profile_photo'] = 'assets/user-silhouette.svg';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            --gray: #6c757d;          /* Gray - Secondary text */
            --accent: #6a1b9a;        /* Darker Purple - Accent */
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .profile-container {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin: 30px auto;
            max-width: 800px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light);
        }

        .profile-title {
            color: var(--primary);
            font-weight: 600;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .profile-photo-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 12px rgba(106, 27, 154, 0.15);
            background-color: #f8f9fa;
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background-color: #f8f9fa;
        }

        .profile-photo[src="assets/default-profile.png"] {
            opacity: 0.7;
            filter: grayscale(100%);
        }

        .profile-photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
        }

        .profile-photo-placeholder img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            opacity: 0.5;
        }

        .photo-upload-form {
            text-align: center;
            margin-bottom: 20px;
        }

        .photo-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .photo-upload-btn:hover {
            background-color: var(--accent);
            transform: translateY(-1px);
        }

        .photo-upload-btn i {
            font-size: 16px;
        }

        .profile-section {
            margin-bottom: 30px;
            padding: 20px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
        }

        .info-item {
            margin-bottom: 15px;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
        }

        .info-label {
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--dark);
            font-size: 16px;
        }

        .status-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .status-card {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }

        .status-card.submitted {
            border-left-color: var(--primary);
        }

        .status-card.under-review {
            border-left-color: var(--warning);
        }

        .status-card.selected {
            border-left-color: var(--success);
        }

        .status-card.rejected {
            border-left-color: var(--danger);
        }

        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
        }

        .status-card.submitted .status-icon {
            background-color: rgba(64, 0, 87, 0.1);
            color: var(--primary);
        }

        .status-card.under-review .status-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .status-card.selected .status-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .status-card.rejected .status-icon {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .status-content {
            flex: 1;
        }

        .status-title {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .status-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .status-date {
            font-size: 12px;
            color: var(--gray);
        }

        .review-notes {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .notes-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: 600;
        }

        .notes-header i {
            font-size: 18px;
        }

        .notes-content {
            color: var(--dark);
            line-height: 1.6;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
        }

        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--accent) !important;
            border-color: var(--accent) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 27, 154, 0.2);
        }

        .btn-outline-primary {
            color: var(--primary) !important;
            border-color: var(--primary) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 27, 154, 0.2);
        }

        .btn-outline-danger {
            color: var(--danger) !important;
            border-color: var(--danger) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background-color: var(--danger) !important;
            border-color: var(--danger) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .action-buttons .btn {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-buttons .btn i {
            font-size: 14px;
        }

        .status-message {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .status-message.success {
            border-left-color: var(--success);
            background-color: rgba(40, 167, 69, 0.05);
        }

        .status-message.error {
            border-left-color: var(--danger);
            background-color: rgba(220, 53, 69, 0.05);
        }

        .message-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .status-message.success .message-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .status-message.error .message-icon {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .message-content {
            flex: 1;
        }

        .message-content h4 {
            margin: 0 0 8px 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 16px;
        }

        .message-content p {
            margin: 0;
            color: var(--gray);
            font-size: 14px;
            line-height: 1.5;
        }

        .status-card {
            display: flex;
            align-items: flex-start;
            padding: 25px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
            margin-bottom: 20px;
        }

        .status-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--light);
        }

        .status-info p {
            margin: 0;
            color: var(--gray);
            font-size: 14px;
            line-height: 1.5;
        }

        .review-notes {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .notes-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: 600;
            font-size: 16px;
        }

        .notes-content {
            color: var(--dark);
            line-height: 1.6;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 20px;
                margin: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-container">
                    <div class="profile-header">
                        <h1 class="profile-title">Student Profile</h1>
                        <div class="profile-photo-container">
                            <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                                 alt="Profile Photo" class="profile-photo">
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="photo-upload-form">
                            <input type="file" name="profile_photo" id="profile_photo" accept="image/*" style="display: none;">
                            <label for="profile_photo" class="photo-upload-btn">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                        </form>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="status-message success">
                            <div class="message-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="message-content">
                                <h4>Success</h4>
                                <p><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="status-message error">
                            <div class="message-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="message-content">
                                <h4>Error</h4>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="profile-section">
                        <h2 class="section-title">Personal Information</h2>
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                        </div>
                    </div>

                    <?php if ($application): ?>
                        <div class="profile-section">
                            <h2 class="section-title">Application Status</h2>
                            <div class="status-container">
                                <div class="status-card <?php echo strtolower(str_replace(' ', '-', $application['status'])); ?>">
                                    <div class="status-icon">
                                        <i class="fas fa-<?php 
                                            switch($application['status']) {
                                                case 'Submitted':
                                                    echo 'paper-plane';
                                                    break;
                                                case 'Under Review':
                                                    echo 'search';
                                                    break;
                                                case 'Selected':
                                                    echo 'check-circle';
                                                    break;
                                                case 'Rejected':
                                                    echo 'times-circle';
                                                    break;
                                                default:
                                                    echo 'clock';
                                            }
                                        ?>"></i>
                                    </div>
                                    <div class="status-content">
                                        <div class="status-title">Current Status</div>
                                        <div class="status-value"><?php echo htmlspecialchars($application['status']); ?></div>
                                        <div class="status-date">Last Updated: <?php echo date('M d, Y', strtotime($application['updated_at'])); ?></div>
                                        <?php if ($application['status'] == 'Submitted'): ?>
                                            <div class="status-info">
                                                <p>Your application is in the queue for review. Please check back for updates.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($application['review_notes']): ?>
                                    <div class="review-notes">
                                        <div class="notes-header">
                                            <i class="fas fa-comment-alt"></i>
                                            <span>Review Notes</span>
                                        </div>
                                        <div class="notes-content">
                                            <?php echo nl2br(htmlspecialchars($application['review_notes'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="header-container">
                        <div class="action-buttons" style="display: flex; justify-content: flex-end; gap: 15px;">
                            <a href="dashboard.php" class="btn btn-outline-primary" style="min-width: 100px; padding: 8px 20px; border-radius: 5px; color: var(--primary); border-color: var(--primary);">Dashboard</a>
                            <a href="logout.php" class="btn btn-outline-danger" style="min-width: 100px; padding: 8px 20px; border-radius: 5px;">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const language1El = document.getElementById('language_1');
            const language2El = document.getElementById('language_2');
            const warning = document.getElementById('language-warning');
            const error = document.getElementById('language-error');

            if (language1El && language2El && warning && error) {
                language1El.addEventListener('change', validateLanguages);
                language2El.addEventListener('change', validateLanguages);

                function validateLanguages() {
                    console.log('Validating languages:', language1El.value, language2El.value); // Debug log
                    const language1 = language1El.value;
                    const language2 = language2El.value;
                    if (language1 && language2 && language1 === language2) {
                        warning.style.display = 'block';
                        error.style.display = 'block';
                        language2El.classList.add('is-invalid');
                }
            });
        });
    </script>
</body>
</html>