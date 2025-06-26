<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db_connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: applications.php");
    exit;
}

$application_id = $_GET['id'];

// Get application details
$query = "SELECT a.*, u.full_name as user_full_name, u.email as user_email 
          FROM applications a 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: applications.php");
    exit;
}

$application = $result->fetch_assoc();

// Handle status update
if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $review_notes = isset($_POST['review_notes']) ? $_POST['review_notes'] : '';
    
    $update_query = "UPDATE applications SET status = ?, review_notes = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $action, $review_notes, $application_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Application status updated successfully!";
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
    } else {
        $error_message = "Error updating application: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #400057;        /* Main brand color - Purple */
            --secondary: #FFD700;      /* Accent color - Gold */
            --dark: #333;              /* Dark text color */
            --light: #f8f9fa;          /* Light background color */
            --white: #ffffff;          /* Pure white */
            --success: #28a745;        /* Success color */
            --danger: #dc3545;         /* Danger color */
            --warning: #ffc107;        /* Warning color */
            --info: #17a2b8;           /* Info color */
            --border-color: #dee2e6;   /* Border color */
            --text-primary: #495057;   /* Primary text color */
            --sidebar-width: 250px;    /* Sidebar width for desktop */
            --sidebar-width-mobile: 60px; /* Sidebar width for tablet */
            --mobile-sidebar-width: 250px; /* Mobile sidebar width when open */
            --top-nav-height: 50px;    /* Height of the top navigation bar on mobile */
        }

        * {
            font-family: 'Montserrat', sans-serif;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--light);
            overflow-x: hidden;
        }

        /* CRT Effect */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                0deg,
                rgba(0, 0, 0, 0.05),
                rgba(0, 0, 0, 0.05) 1px,
                transparent 1px,
                transparent 2px
            );
            pointer-events: none;
            z-index: 1000;
            opacity: 0.3;
        }

        .container-fluid {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .row {
            flex: 1;
            min-height: 0;
        }

        .top-nav {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--top-nav-height);
            background-color: var(--primary);
            color: var(--white);
            z-index: 1001;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            align-items: center;
            padding: 0 1rem;
        }

        .top-nav .sidebar-toggle {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .top-nav .sidebar-toggle:hover {
            transform: scale(1.1);
        }

        .top-nav .top-nav-title {
            flex: 1;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .sidebar {
            background-color: var(--primary);
            color: var(--white);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            transition: transform 0.3s ease, width 0.3s ease;
            padding: 1rem 0;
            width: var(--sidebar-width);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .sidebar-header img {
            height: 40px;
            width: auto;
        }

        .sidebar-header h4 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            margin: 0;
            font-size: 1.2rem;
            color: var(--white);
        }

        .sidebar-nav {
            margin-top: 2rem;
        }

        .sidebar-nav a {
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 0.9rem;
            margin: 0 0.5rem;
        }

        .sidebar-nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar-nav .active {
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--white);
        }

        .sidebar-nav a i {
            font-size: 1.2rem;
            min-width: 1.2rem;
            text-align: center;
        }

        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
            transition: opacity 0.3s ease;
        }

        .sidebar-backdrop.active {
            display: block;
            opacity: 1;
        }

        .content-area {
            padding: 2rem;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            flex: 1;
            overflow-y: auto;
            min-height: 100vh;
            background-color: var(--light);
        }

        .application-details {
            background-color: var(--white);
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .application-details::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(64, 0, 87, 0.1), transparent);
            pointer-events: none;
        }

        .application-details .application-id {
            font-size: 0.9rem;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 1px solid var(--primary);
        }

        .id-proof-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 1px solid var(--primary);
            transition: width 0.3s ease, height 0.3s ease, border 0.3s ease;
            cursor: pointer;
        }

        .id-proof-img.enlarged {
            width: 300px;
            height: 300px;
            border: 2px solid var(--primary);
        }

        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .info-section h4 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-section h4 i {
            color: var(--secondary);
        }

        .document-container {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .document-item {
            background-color: var(--light);
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            flex: 1;
            min-width: 200px;
        }

        .document-item p {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .document-preview {
            max-width: 100%;
            border-radius: 0.25rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .document-placeholder {
            text-align: center;
            color: var(--dark);
            padding: 1rem;
            background-color: #EDF2F7;
            border-radius: 0.25rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            color: var(--white);
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary:focus-visible {
            background-color: var(--primary) !important;
            color: var(--white) !important;
            box-shadow: none !important;
            outline: none !important;
            transform: none !important;
        }

        .btn-primary i {
            color: var(--white);
            margin-right: 0.5rem;
        }

        .btn-secondary {
            background-color: var(--secondary);
            border: none;
            color: var(--dark);
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: var(--secondary);
            color: var(--dark);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .btn-secondary:focus,
        .btn-secondary:active,
        .btn-secondary:focus-visible {
            background-color: var(--secondary) !important;
            color: var(--dark) !important;
            box-shadow: none !important;
            outline: none !important;
            transform: none !important;
        }

        .btn-secondary i {
            color: var(--dark);
            margin-right: 0.5rem;
        }

        .btn-group .btn {
            margin: 0 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        /* Export as PDF Button Responsiveness */
        .btn-sm.btn-secondary {
            position: relative;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-sm.btn-secondary:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--dark);
            color: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 10;
            margin-top: 0.25rem;
            display: none;
        }

        @media (max-width: 767.98px) {
            .btn-sm.btn-secondary {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 576px) {
            .btn-sm.btn-secondary {
                padding: 0.3rem 0.5rem;
                font-size: 0;
            }

            .btn-sm.btn-secondary i {
                font-size: 1rem;
                margin-right: 0;
            }

            .btn-sm.btn-secondary span {
                display: none;
            }

            .btn-sm.btn-secondary:hover::after {
                display: block;
            }
        }

        .modal-content {
            border-radius: 0.5rem;
            background-color: var(--white);
            border: 1px solid var(--primary);
        }

        .modal-header {
            background-color: var(--primary);
            color: var(--white);
            border-bottom: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-footer {
            border-top: none;
            padding: 1rem;
        }

        .table-borderless th {
            color: var(--primary);
            font-weight: 600;
        }

        .table-borderless td {
            color: var(--dark);
        }

        .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: #5b0080;
        }

        .alert-success {
            background-color: #C6F6D5;
            color: #22543D;
        }

        .alert-danger {
            background-color: #FED7D7;
            color: #742A2A;
        }

        /* Status Badges */
        .badge.bg-success { background-color: var(--success); color: var(--white); }
        .badge.bg-danger { background-color: var(--danger); color: var(--white); }
        .badge.bg-warning { background-color: var(--warning); color: var(--dark); }
        .badge.bg-info { background-color: var(--info); color: var(--white); }
        .badge.bg-secondary { background-color: var(--text-muted); color: var(--white); }
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Responsive Adjustments */
        @media (min-width: 992px) {
            .table-borderless th,
            .table-borderless td {
                white-space: normal;
                word-wrap: break-word;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        @media (max-width: 991.98px) {
            .sidebar {
                width: var(--sidebar-width-mobile);
                padding: 0.5rem 0;
            }

            .sidebar-header img {
                height: 35px;
            }

            .sidebar-header h4 {
                font-size: 1rem;
                display: none;
            }

            .sidebar-nav a {
                font-size: 0.8rem;
                padding: 0.75rem;
                justify-content: center;
                margin: 0;
            }

            .sidebar-nav a span {
                display: none;
            }

            .sidebar-nav a i {
                font-size: 1.4rem;
                margin: 0;
            }

            .content-area {
                margin-left: var(--sidebar-width-mobile);
                padding: 1.5rem;
            }

            .document-container {
                flex-direction: column;
            }
        }

        @media (max-width: 767.98px) {
            .top-nav {
                display: flex;
            }

            .sidebar {
                width: 0;
                transform: translateX(-100%);
                overflow: hidden;
                top: var(--top-nav-height);
                min-height: calc(100vh - var(--top-nav-height));
                padding: 1rem 0;
            }

            .sidebar.active {
                width: var(--mobile-sidebar-width);
                transform: translateX(0);
            }

            .sidebar-header h4 {
                display: block;
            }

            .sidebar-nav a {
                padding: 0.75rem 1rem;
                justify-content: flex-start;
                margin: 0 0.5rem;
            }

            .sidebar-nav a span {
                display: inline;
            }

            .sidebar-nav a i {
                font-size: 1.2rem;
            }

            .content-area {
                margin-left: 0;
                padding: 1rem;
                padding-top: calc(var(--top-nav-height) + 1rem);
            }

            .content-area.sidebar-open {
                margin-left: 0;
            }

            .sidebar-header {
                flex-direction: row;
                text-align: left;
            }

            .sidebar-header img {
                height: 30px;
            }

            .sidebar-nav a {
                font-size: 0.9rem;
            }

            .btn-group .btn {
                margin-bottom: 0.5rem;
            }

            .document-preview {
                max-width: 100%;
            }

            .id-proof-img.enlarged {
                width: 250px;
                height: 250px;
            }
        }

        @media (max-width: 576px) {
            .sidebar.active {
                width: var(--mobile-sidebar-width);
            }

            .content-area {
                padding: 0.75rem;
                padding-top: calc(var(--top-nav-height) + 0.75rem);
            }

            .application-details {
                padding: 1rem;
            }

            .info-section h4 {
                font-size: 1.2rem;
            }

            .btn-group .btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }

            .modal-dialog {
                margin: 0.5rem;
                max-width: 95%;
            }

            .modal-content {
                border-radius: 0.5rem;
            }

            .modal-body,
            .modal-footer,
            .modal-header {
                padding: 1rem;
            }

            .modal-title {
                font-size: 1rem;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .form-select,
            .form-control {
                font-size: 0.9rem;
            }

            .id-proof-img.enlarged {
                width: 200px;
                height: 200px;
            }

            .top-nav .top-nav-title {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Top Navigation Bar for Mobile -->
        <div class="top-nav">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="top-nav-title">MGMEC Admin Panel</h5>
        </div>

        <!-- Sidebar Backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                 <a href="../../pages/index.html">
                        <img src="../../assets/images/header logo.svg" alt="MGMEC Logo">
                    </a> 
                <h4>MGMEC <br> Admin Panel</h4>
            </div>
            <div class="sidebar-nav">
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="applications.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'applications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Applications</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fw-bold" style="color: var(--primary);">Application Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="applications.php">Applications</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View Application</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="application_pdf.php?id=<?php echo $application_id; ?>" class="btn btn-sm btn-secondary" target="_blank" data-tooltip="Export as PDF">
                        <i class="fas fa-file-pdf"></i> <span>Export as PDF</span>
                    </a>
                </div>
            </div>
            
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Application Details -->
            <div class="application-details">
                <div class="row mb-4">
                    <!-- Status and Quick Actions Section -->
                    <div class="col-md-8">
                        <h2 class="fw-bold" style="color: var(--primary);"><?php echo $application['student_name']; ?></h2>
                        <p class="text-muted application-id">Application ID: #<?php echo $application['id']; ?> | Submitted on: <?php echo date('F j, Y', strtotime($application['created_at'])); ?></p>
                        
                        <?php
                        $status_class = "";
                        switch($application['status']) {
                            case 'Selected':
                                $status_class = "badge bg-success";
                                break;
                            case 'Rejected':
                                $status_class = "badge bg-danger";
                                break;
                            case 'Under Review':
                                $status_class = "badge bg-warning text-dark";
                                break;
                            case 'Submitted':
                                $status_class = "badge bg-info";
                                break;
                            default:
                                $status_class = "badge bg-secondary";
                        }
                        ?>
                        
                        <div class="mb-3">
                            <span class="<?php echo $status_class; ?> status-badge"><?php echo $application['status']; ?></span>
                        </div>
                        
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusUpdateModal">
                            <i class="fas fa-sync-alt"></i> Update Status
                        </button>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Personal Information -->
                <div class="info-section">
                    <h4><i class="fas fa-user"></i> Personal Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Full Name:</th>
                                    <td><?php echo $application['student_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Father's Name:</th>
                                    <td><?php echo $application['father_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Mother's Name:</th>
                                    <td><?php echo $application['mother_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>Gender:</th>
                                    <td><?php echo $application['gender']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Date of Birth:</th>
                                    <td><?php echo date('F j, Y', strtotime($application['date_of_birth'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Category:</th>
                                    <td><?php echo $application['category']; ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo $application['email']; ?></td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td><?php echo $application['contact_number']; ?> (Primary) / <?php echo $application['whatsapp_number']; ?> (WhatsApp)</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Academic Information -->
                <div class="info-section">
                    <h4><i class="fas fa-graduation-cap"></i> Academic Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Course Applied:</th>
                                    <td><?php echo $application['course_applied']; ?></td>
                                </tr>
                                <tr>
                                    <th>10th Board:</th>
                                    <td><?php echo $application['tenth_board']; ?></td>
                                </tr>
                                <tr>
                                    <th>10th Marks:</th>
                                    <td><?php echo $application['tenth_marks']; ?>%</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Languages:</th>
                                    <td><?php echo $application['language_1']; ?>, <?php echo $application['language_2']; ?></td>
                                </tr>
                                <tr>
                                    <th>PU College:</th>
                                    <td><?php echo $application['pu_college']; ?></td>
                                </tr>
                                <tr>
                                    <th>PU Stream:</th>
                                    <td><?php echo $application['pu_stream']; ?></td>
                                </tr>
                                <tr>
                                    <th>PU Marks:</th>
                                    <td><?php echo $application['pu_marks']; ?>%</td>
                                </tr>
                                <tr>
                                    <th>PU Board:</th>
                                    <td><?php echo $application['pu_board']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Address Information -->
                <div class="info-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Address Information</h4>
                    <div class="row">
                        <div class="col-md-12">
                            <p><?php echo nl2br($application['address']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Other Information -->
                <div class="info-section">
                    <h4><i class="fas fa-info-circle"></i> Other Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Hostel Required:</th>
                                    <td><?php echo $application['hostel_required']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Section -->
                <div class="info-section">
                    <h4><i class="fas fa-file-alt"></i> Documents</h4>
                    <div class="document-container">
                        <!-- Profile Photo -->
                        <div class="document-item">
                            <p><strong>Profile Photo</strong></p>
                            <?php
                            $base_url = 'http://localhost/mgmec1/studentadmission';
                            $document_root = 'C:/wamp64/www/mgmec1/studentadmission/';
                            $photo_path = !empty($application['photo_path']) ? trim($application['photo_path']) : '';
                            $absolute_photo_path = $document_root . $photo_path;
                            
                            if ($photo_path && file_exists($absolute_photo_path)) {
                                $photo_url = $base_url . '/serve_file.php?file=' . urlencode($photo_path);
                                error_log("Photo found at: $absolute_photo_path");
                                error_log("Photo URL: $photo_url");
                                
                                echo '<img src="' . htmlspecialchars($photo_url) . '" alt="Profile Photo" class="document-preview profile-img">';
                            } else {
                                echo '<div class="document-placeholder">No photo available</div>';
                                error_log("Photo missing or inaccessible: $absolute_photo_path");
                            }
                            ?>
                        </div>
                        
                        <!-- ID Proof -->
                        <div class="document-item">
                            <p><strong>ID Proof</strong></p>
                            <?php
                            $id_proof_path = !empty($application['id_proof_path']) ? trim($application['id_proof_path']) : '';
                            $absolute_id_proof_path = $document_root . $id_proof_path;
                            
                            if ($id_proof_path && file_exists($absolute_id_proof_path)) {
                                $id_proof_url = $base_url . '/serve_file.php?file=' . urlencode($id_proof_path);
                                $ext = strtolower(pathinfo($id_proof_path, PATHINFO_EXTENSION));
                                
                                error_log("ID Proof found at: $absolute_id_proof_path");
                                error_log("ID Proof URL: $id_proof_url");
                                
                                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                    echo '<img src="' . htmlspecialchars($id_proof_url) . '" alt="ID Proof" class="document-preview id-proof-img">';
                                } elseif ($ext === 'pdf') {
                                    echo '<div class="document-placeholder">';
                                    echo '<i class="fas fa-file-pdf text-danger" style="font-size: 2rem;"></i><br>';
                                    echo '<a href="' . htmlspecialchars($id_proof_url) . '" target="_blank" class="btn btn-outline-primary btn-sm mt-2">';
                                    echo '<i class="fas fa-eye"></i> View PDF Document';
                                    echo '</a></div>';
                                } else {
                                    echo '<div class="document-placeholder">';
                                    echo '<a href="' . htmlspecialchars($id_proof_url) . '" target="_blank" class="document-link">';
                                    echo 'View ID Proof (' . strtoupper($ext) . ')';
                                    echo '</a></div>';
                                }
                            } else {
                                echo '<div class="document-placeholder">No ID proof available</div>';
                                error_log("ID Proof missing or inaccessible: $absolute_id_proof_path");
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Review Notes Section -->
                <div class="info-section">
                    <h4><i class="fas fa-clipboard-check"></i> Review Notes</h4>
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($application['review_notes'])): ?>
                                <p><?php echo nl2br($application['review_notes']); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No review notes available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <div class="btn-group">
                        <a href="applications.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusUpdateModal"><i class="fas fa-check-circle"></i> Update Status</button>
                        <a href="application_pdf.php?id=<?php echo $application_id; ?>" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-labelledby="statusUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusUpdateModalLabel">Update Application Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="view_application.php?id=<?php echo $application_id; ?>" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="action" class="form-label">Status</label>
                            <select name="action" id="action" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="Draft" <?php echo $application['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="Submitted" <?php echo $application['status'] == 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="Under Review" <?php echo $application['status'] == 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="Selected" <?php echo $application['status'] == 'Selected' ? 'selected' : ''; ?>>Selected</option>
                                <option value="Rejected" <?php echo $application['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="review_notes" class="form-label">Review Notes</label>
                            <textarea name="review_notes" id="review_notes" class="form-control" rows="4"><?php echo $application['review_notes']; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap modal
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            new bootstrap.Modal(modal);
        });

        // Handle sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleIcon = sidebarToggle.querySelector('i');
            const sidebar = document.getElementById('sidebar');
            const contentArea = document.querySelector('.content-area');
            const backdrop = document.getElementById('sidebarBackdrop');
            const idProofImg = document.querySelector('.id-proof-img');
            let resizeTimeout;

            // Toggle sidebar and icon on click
            sidebarToggle.addEventListener('click', function() {
                if (sidebar.classList.contains('active')) {
                    // Close sidebar
                    sidebar.classList.remove('active');
                    contentArea.classList.remove('sidebar-open');
                    backdrop.classList.remove('active');
                    sidebarToggleIcon.classList.remove('fa-times');
                    sidebarToggleIcon.classList.add('fa-bars');
                } else {
                    // Open sidebar
                    sidebar.classList.add('active');
                    contentArea.classList.add('sidebar-open');
                    backdrop.classList.add('active');
                    sidebarToggleIcon.classList.remove('fa-bars');
                    sidebarToggleIcon.classList.add('fa-times');
                }
            });

            // Close sidebar when clicking on backdrop or outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 767.98 && sidebar.classList.contains('active') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                    contentArea.classList.remove('sidebar-open');
                    backdrop.classList.remove('active');
                    sidebarToggleIcon.classList.remove('fa-times');
                    sidebarToggleIcon.classList.add('fa-bars');
                }
            });

            // Adjust layout based on screen size without auto-opening sidebar
            function updateSidebarVisibility() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    if (window.innerWidth <= 767.98) {
                        // On mobile, sidebar is hidden unless manually opened
                        contentArea.classList.remove('sidebar-open');
                    } else if (window.innerWidth <= 991.98) {
                        // On tablet, sidebar is narrow, but not auto-opened
                        contentArea.classList.remove('sidebar-open');
                    } else {
                        // On desktop, sidebar is visible but not auto-opened
                        contentArea.classList.remove('sidebar-open');
                    }
                }, 100);
            }

            // Initial call
            updateSidebarVisibility();

            // Handle window resize
            window.addEventListener('resize', updateSidebarVisibility);

            // Toggle ID proof image size on click
            if (idProofImg) {
                idProofImg.addEventListener('click', function() {
                    this.classList.toggle('enlarged');
                });
            }
        });
    </script>
</body>
</html>