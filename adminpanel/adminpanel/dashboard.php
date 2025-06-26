<?php
session_set_cookie_params(0);
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();


session_regenerate_id(true);
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Include database connection
include 'db_connect.php';

// Initialize variables with default values in case queries fail
$total_users = $total_applications = $submitted_applications = $selected_students = 0;

// Error handling function
function handleQueryError($conn, $query) {
    error_log("Database query error: " . $conn->error . " in query: " . $query);
    return false;
}

// Count statistics with error handling
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_applications_query = "SELECT COUNT(*) as total FROM applications";
$submitted_applications_query = "SELECT COUNT(*) as total FROM applications WHERE status != 'Draft'";
$selected_students_query = "SELECT COUNT(*) as total FROM applications WHERE status = 'Selected'";

if ($total_users_result = $conn->query($total_users_query)) {
    $total_users = $total_users_result->fetch_assoc()['total'];
} else {
    handleQueryError($conn, $total_users_query);
}

if ($total_applications_result = $conn->query($total_applications_query)) {
    $total_applications = $total_applications_result->fetch_assoc()['total'];
} else {
    handleQueryError($conn, $total_applications_query);
}

if ($submitted_applications_result = $conn->query($submitted_applications_query)) {
    $submitted_applications = $submitted_applications_result->fetch_assoc()['total'];
} else {
    handleQueryError($conn, $submitted_applications_query);
}

if ($selected_students_result = $conn->query($selected_students_query)) {
    $selected_students = $selected_students_result->fetch_assoc()['total'];
} else {
    handleQueryError($conn, $selected_students_query);
}

// Recent Applications
$recent_applications_query = "SELECT a.id, a.student_name, a.course_applied, a.status, a.created_at 
                             FROM applications a 
                             ORDER BY a.created_at DESC 
                             LIMIT 5";
$recent_applications_result = $conn->query($recent_applications_query);
if (!$recent_applications_result) {
    handleQueryError($conn, $recent_applications_query);
}

// CSRF protection
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MGMEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #400057;        /* Main brand color - Purple */
            --secondary: #FFD700;      /* Accent color - Gold */
            --dark: #333;              /* Dark text color */
            --white: #ffffff;          /* Pure white */
            --light: #f8f9fa;          /* Light background color */
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

        .stats-card {
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            background-color: var(--white);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-card .icon {
            font-size: 2.5rem;
            opacity: 0.2;
            transition: opacity 0.3s ease;
        }

        .stats-card:hover .icon {
            opacity: 0.4;
        }

        .stats-card .count {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 0.5rem;
        }

        .stats-card .title {
            font-size: 1rem;
            color: var(--white);
            font-weight: 500;
        }

        .recent-applications {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background-color: var(--white);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .recent-applications .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }

        .recent-applications th {
            background-color: var(--light);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            white-space: nowrap;
        }

        .recent-applications td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .recent-applications .btn-group {
            display: flex;
            gap: 5px;
        }

        .color-primary { 
            background-color: var(--primary); 
            color: var(--white); 
        }

        .color-success { 
            background-color: var(--success); 
            color: var(--white); 
        }

        .color-warning { 
            background-color: var(--secondary); 
            color: var(--dark); 
        }

        .color-danger { 
            background-color: var(--danger); 
            color: var(--white); 
        }

        .color-info { 
            background-color: var(--info); 
            color: var(--white); 
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge.bg-success { background-color: var(--success); color: var(--white); }
        .badge.bg-danger { background-color: var(--danger); color: var(--white); }
        .badge.bg-warning { background-color: var(--warning); color: var(--dark); }
        .badge.bg-info { background-color: var(--info); color: var(--white); }
        .badge.bg-secondary { background-color: var(--text-muted); color: var(--white); }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(2px);
        }

        .loading .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary);
        }

        .modal-content {
            border-radius: 8px;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        .modal-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        /* Table Styling */
        .table-container {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            color: var(--text-primary);
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: var(--light);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            color: var(--text-primary);
            padding: 1rem;
            white-space: nowrap;
            min-width: 80px;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table .btn-group {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: nowrap;
        }

        /* Button Styles and Hover Effects */
        .btn {
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #400057;
            border-color: #400057;
        }

        .btn-primary:hover {
            color: black;
            background-color: #FFD700;
            border-color: #FFD700;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #138496;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .table .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .table .btn i {
            font-size: 1rem;
        }

        /* Desktop View Adjustments */
        @media (min-width: 992px) {
            .table-responsive {
                overflow-x: hidden;
            }

            .table th,
            .table td {
                white-space: normal;
                word-wrap: break-word;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* Tablet View Adjustments */
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
                padding: 1rem;
            }

            .stats-card {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }

            .stats-card .icon {
                margin-top: 1rem;
            }

            .stats-card .count {
                font-size: 1.5rem;
            }

            .stats-card .title {
                font-size: 0.9rem;
            }

            .table-container {
                padding: 1rem;
            }

            .table th,
            .table td {
                min-width: 50px;
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .table th:last-child,
            .table td:last-child {
                min-width: 100px;
            }

            .table .btn-group {
                gap: 0.3rem;
            }

            .table .btn {
                padding: 0.3rem 0.6rem;
                min-width: 28px;
                height: 28px;
            }

            .table .btn i {
                font-size: 0.875rem;
            }
        }

        /* Mobile View Adjustments */
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

            .stats-card {
                padding: 1rem;
            }

            .table-container {
                padding: 1rem;
            }

            /* Stacked Table Layout for Mobile Screens */
            .table {
                display: block;
                font-size: 1rem;
            }

            .table thead {
                display: none;
            }

            .table tbody, .table tr {
                display: block;
            }

            .table tr {
                margin-bottom: 2rem;
                border: 2px solid var(--border-color);
                border-radius: 12px;
                padding: 1.5rem;
                background-color: #fff;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transition: background-color 0.2s ease;
            }

            .table tr:active {
                background-color: rgba(0, 0, 0, 0.05);
            }

            .table td {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem 0;
                border: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                text-align: left;
                max-width: none;
                white-space: normal;
                font-size: 1rem;
            }

            .table td:last-child {
                border-bottom: none;
            }

            .table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--primary);
                margin-bottom: 0.75rem;
                font-size: 1rem;
                display: block;
                background-color: rgba(64, 0, 87, 0.05);
                padding: 0.5rem;
                border-radius: 6px;
                width: 100%;
            }

            .table td.text-center {
                align-items: flex-start;
                text-align: left;
            }

            .table .btn-group {
                flex-direction: row;
                gap: 1rem;
                justify-content: center;
                width: 100%;
                margin-top: 1.5rem;
            }

            .table .btn-group .btn {
                padding: 0.75rem 1.25rem;
                font-size: 1rem;
                min-width: 48px;
                height: 48px;
                border-radius: 8px !important;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
        }

        /* Very Small Screens Adjustments */
        @media (max-width: 576px) {
            .sidebar.active {
                width: var(--mobile-sidebar-width);
            }

            .content-area {
                padding: 0.75rem;
                padding-top: calc(var(--top-nav-height) + 0.75rem);
            }

            .stats-card {
                padding: 0.75rem;
            }

            .stats-card .count {
                font-size: 1.25rem;
            }

            .stats-card .title {
                font-size: 0.85rem;
            }

            .table-container {
                padding: 0.75rem;
            }

            .table {
                font-size: 0.875rem;
            }

            .table tr {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .table td {
                padding: 0.75rem 0;
                font-size: 0.875rem;
            }

            .table td:before {
                font-size: 0.875rem;
                margin-bottom: 0.5rem;
                padding: 0.4rem;
            }

            .table .btn-group {
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .table .btn-group .btn {
                padding: 0.6rem 1rem;
                font-size: 0.875rem;
                min-width: 40px;
                height: 40px;
                border-radius: 8px !important;
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

            .top-nav .top-nav-title {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading indicator -->
    <div class="loading" id="loading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

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

        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar" id="sidebar">
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
            <div class="col-md-9 col-lg-10 content-area">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">Dashboard</h1>
                    <div class="d-flex align-items-center">
                        <span class="me-2">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
                        <form action="logout.php" method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Logout</button>
                        </form>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="stats-card color-primary">
                            <div>
                                <div class="count"><?php echo $total_users; ?></div>
                                <div class="title">Total Users</div>
                            </div>
                            <div class="icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="stats-card color-success">
                            <div>
                                <div class="count"><?php echo $total_applications; ?></div>
                                <div class="title">Total Applications</div>
                            </div>
                            <div class="icon"><i class="fas fa-file-alt"></i></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="stats-card color-warning">
                            <div>
                                <div class="count"><?php echo $submitted_applications; ?></div>
                                <div class="title">Submitted Applications</div>
                            </div>
                            <div class="icon"><i class="fas fa-paper-plane"></i></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="stats-card color-danger">
                            <div>
                                <div class="count"><?php echo $selected_students; ?></div>
                                <div class="title">Selected Students</div>
                            </div>
                            <div class="icon"><i class="fas fa-user-check"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="recent-applications bg-white mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Recent Applications</h4>
                        <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($recent_applications_result && $recent_applications_result->num_rows > 0) {
                                        while($row = $recent_applications_result->fetch_assoc()) {
                                            $status_class = "";
                                            switch($row['status']) {
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
                                            
                                            echo '<tr class="align-middle">';
                                            echo '<td data-label="ID" class="text-center">' . htmlspecialchars($row['id']) . '</td>';
                                            echo '<td data-label="Student Name">' . htmlspecialchars($row['student_name']) . '</td>';
                                            echo '<td data-label="Course">' . htmlspecialchars($row['course_applied']) . '</td>';
                                            echo '<td data-label="Status" class="text-center"><span class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                                            echo '<td data-label="Date" class="text-center">' . date('d M Y', strtotime($row['created_at'])) . '</td>';
                                            echo '<td data-label="Action" class="text-center">
                                                    <div class="btn-group">
                                                        <a href="view_application.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#statusModal' . htmlspecialchars($row['id']) . '" title="Update Status"><i class="fas fa-check-circle"></i></button>
                                                        <a href="application_pdf.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-sm btn-secondary" target="_blank" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                                                    </div>
                                                    
                                                    <!-- Status Update Modal -->
                                                    <div class="modal fade" id="statusModal' . htmlspecialchars($row['id']) . '" tabindex="-1" aria-labelledby="statusModalLabel' . htmlspecialchars($row['id']) . '" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="statusModalLabel' . htmlspecialchars($row['id']) . '">Update Application Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="dashboard.php" method="post" class="status-update-form">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
                                                                        <input type="hidden" name="application_id" value="' . htmlspecialchars($row['id']) . '">
                                                                        <div class="mb-3">
                                                                            <label for="action" class="form-label">Status</label>
                                                                            <select name="action" id="action" class="form-select" required>
                                                                                <option value="">Select Status</option>
                                                                                <option value="Submitted">Submitted</option>
                                                                                <option value="Under Review">Under Review</option>
                                                                                <option value="Selected">Selected</option>
                                                                                <option value="Rejected">Rejected</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="review_notes" class="form-label">Review Notes</label>
                                                                            <textarea name="review_notes" id="review_notes" class="form-control" rows="3"></textarea>
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
                                                  </td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center py-4">No applications found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
        });

        // Hide loading indicator when page is fully loaded
        window.addEventListener('load', function() {
            document.getElementById('loading').style.display = 'none';
        });

        // Add CSRF token to all logout links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.logout-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var form = document.createElement('form');
                    form.method = 'post';
                    form.action = this.getAttribute('href');
                    var csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = '<?php echo $csrf_token; ?>';
                    form.appendChild(csrfInput);
                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });

        // Handle status update form submission
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-update-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(form);
                    var hiddenForm = document.createElement('form');
                    hiddenForm.method = 'post';
                    hiddenForm.action = 'applications.php';
                    hiddenForm.style.display = 'none';
                    formData.forEach(function(value, key) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        hiddenForm.appendChild(input);
                    });
                    var redirectInput = document.createElement('input');
                    redirectInput.type = 'hidden';
                    redirectInput.name = 'redirect_to';
                    redirectInput.value = 'dashboard.php';
                    hiddenForm.appendChild(redirectInput);
                    document.body.appendChild(hiddenForm);
                    hiddenForm.submit();
                });
            });
        });
    </script>
</body>
</html>