<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db_connect.php';

// CSRF protection
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Error handling function
function handleQueryError($conn, $query) {
    error_log("Database query error: " . $conn->error . " in query: " . $query);
    return false;
}

// Set default filter values
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$course_filter = isset($_GET['course']) ? $conn->real_escape_string($_GET['course']) : '';
$from_date = isset($_GET['from_date']) ? $conn->real_escape_string($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? $conn->real_escape_string($_GET['to_date']) : '';
$sort_by = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'created_at';
$sort_dir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Validate sort_by to prevent SQL injection
$allowed_columns = ['id', 'student_name', 'father_name', 'course_applied', 'pu_marks', 'status', 'created_at'];
if (!in_array($sort_by, $allowed_columns)) {
    $sort_by = 'created_at'; // Default if invalid column is provided
}

// Build the query with filters using prepared statements
$where_conditions = [];
$params = [];
$types = "";

$base_query = "SELECT a.id, a.student_name, a.father_name, a.course_applied, a.pu_marks, a.status, a.created_at 
               FROM applications a WHERE 1=1";

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($course_filter)) {
    $where_conditions[] = "a.course_applied = ?";
    $params[] = $course_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(a.student_name LIKE ? OR a.father_name LIKE ? OR a.email LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Construct the full WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = " AND " . implode(" AND ", $where_conditions);
}

// Pagination
$results_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start_from = ($page - 1) * $results_per_page;

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM applications a WHERE 1=1" . $where_clause;
$stmt_count = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}

$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);
$stmt_count->close();

// Final query with pagination
$query = $base_query . $where_clause . " ORDER BY a." . $sort_by . " " . $sort_dir . " LIMIT ?, ?";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $all_params = array_merge($params, [$start_from, $results_per_page]);
    $types .= "ii";
    $stmt->bind_param($types, ...$all_params);
} else {
    $stmt->bind_param("ii", $start_from, $results_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

// Action handling
if (isset($_POST['action']) && isset($_POST['application_id'])) {
    // Generate new CSRF token
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;

    $application_id = (int)$_POST['application_id'];
    $action = $conn->real_escape_string($_POST['action']);
    $review_notes = isset($_POST['review_notes']) ? $conn->real_escape_string($_POST['review_notes']) : '';
    
    // Using prepared statement for update
    $update_query = "UPDATE applications SET status = ?, review_notes = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $action, $review_notes, $application_id);
    
    if ($update_stmt->execute()) {
        // Redirect back to the original page
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'applications.php';
        $redirect_params = '';
        
        // Add filter parameters if we're staying on applications.php
        if ($redirect_to === 'applications.php') {
            $redirect_params = "?status=" . urlencode($status_filter) . "&course=" . urlencode($course_filter) . "&search=" . urlencode($search) . "&sort=" . urlencode($sort_by) . "&dir=" . urlencode($sort_dir);
        }
        
        header("Location: " . $redirect_to . $redirect_params);
        exit;
    } else {
        $error_message = "Error updating application: " . $conn->error;
    }
    
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications | Admin Panel</title>
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

    /* Filter Form */
    .filter-form {
        background-color: var(--white);
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border: 1px solid var(--border-color);
        margin-bottom: 1.5rem;
    }

    .filter-form .form-group {
        margin-bottom: 1rem;
    }

    .filter-form label {
        font-weight: 500;
        color: var(--text-primary);
    }

    .filter-form .form-control,
    .filter-form .form-select {
        border-radius: 4px;
        border: 1px solid var(--border-color);
        padding: 0.5rem;
    }

    .filter-form .form-control:focus,
    .filter-form .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(64, 0, 87, 0.25);
    }

    /* Table */
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

    .table th a {
        color: var(--text-primary);
        text-decoration: none;
    }

    .table th a:hover {
        text-decoration: underline;
    }

    .table th a i {
        font-size: 0.75rem;
        margin-left: 0.3rem;
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

    /* Pagination */
    .pagination .page-link {
        color: var(--primary);
        border-radius: 4px;
        margin: 0 0.2rem;
    }

    .pagination .page-item.active .page-link {
        background-color: var(--primary);
        border-color: var(--primary);
        color: var(--white);
    }

    .pagination .page-item.disabled .page-link {
        color: var(--text-muted);
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

    .filter-form .btn {
        padding: 0.5rem 1.25rem;
        border-radius: 4px;
    }

    .pagination .page-link:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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

        /* Ensure uniform button corners in mobile view */
        .table .btn-group .btn {
            padding: 0.75rem 1.25rem;
            font-size: 1rem;
            min-width: 48px;
            height: 48px;
            border-radius: 8px !important; /* Ensure uniform corners */
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
            border-radius: 8px !important; /* Ensure uniform corners */
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

        .filter-form {
            padding: 1rem;
        }

        .filter-form .form-group {
            margin-bottom: 0.75rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Student Applications</h1>
                    <div>
                        <a href="export_applications.php?token=<?php echo bin2hex(random_bytes(32)); ?>&status=<?php echo htmlspecialchars($status_filter); ?>&course=<?php echo htmlspecialchars($course_filter); ?>&from_date=<?php echo htmlspecialchars($from_date); ?>&to_date=<?php echo htmlspecialchars($to_date); ?>&search=<?php echo htmlspecialchars($search); ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Export to Excel
                        </a>
                    </div>
                </div>
                
                <?php if(isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filter Form -->
                <div class="filter-form">
                    <form action="applications.php" method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Draft" <?php echo $status_filter === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="Submitted" <?php echo $status_filter === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="Under Review" <?php echo $status_filter === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="Selected" <?php echo $status_filter === 'Selected' ? 'selected' : ''; ?>>Selected</option>
                                <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="course" class="form-label">Course</label>
                            <select name="course" id="course" class="form-select">
                                <option value="">All Courses</option>
                                <option value="BCom General" <?php echo $course_filter === 'BCom General' ? 'selected' : ''; ?>>BCom General</option>
                                <option value="BCA" <?php echo $course_filter === 'BCA' ? 'selected' : ''; ?>>BCA</option>
                                <option value="BBA" <?php echo $course_filter === 'BBA' ? 'selected' : ''; ?>>BBA</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>
                
                <!-- Applications Table -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>
                                        <a href="applications.php?status=<?php echo htmlspecialchars($status_filter); ?>&course=<?php echo htmlspecialchars($course_filter); ?>&search=<?php echo htmlspecialchars($search); ?>&sort=student_name&dir=<?php echo $sort_by === 'student_name' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                            Student Name
                                            <?php if($sort_by === 'student_name'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Father's Name</th>
                                    <th>Course</th>
                                    <th>
                                        <a href="applications.php?status=<?php echo htmlspecialchars($status_filter); ?>&course=<?php echo htmlspecialchars($course_filter); ?>&search=<?php echo htmlspecialchars($search); ?>&sort=pu_marks&dir=<?php echo $sort_by === 'pu_marks' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                            PU Marks
                                            <?php if($sort_by === 'pu_marks'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="applications.php?status=<?php echo htmlspecialchars($status_filter); ?>&course=<?php echo htmlspecialchars($course_filter); ?>&search=<?php echo htmlspecialchars($search); ?>&sort=status&dir=<?php echo $sort_by === 'status' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                            Status
                                            <?php if($sort_by === 'status'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="applications.php?status=<?php echo htmlspecialchars($status_filter); ?>&course=<?php echo htmlspecialchars($course_filter); ?>&search=<?php echo htmlspecialchars($search); ?>&sort=created_at&dir=<?php echo $sort_by === 'created_at' && $sort_dir === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                            Date
                                            <?php if($sort_by === 'created_at'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_dir === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
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
                                        echo '<td data-label="Father\'s Name">' . htmlspecialchars($row['father_name']) . '</td>';
                                        echo '<td data-label="Course">' . htmlspecialchars($row['course_applied']) . '</td>';
                                        echo '<td data-label="PU Marks" class="text-center">' . htmlspecialchars($row['pu_marks']) . '%</td>';
                                        echo '<td data-label="Status" class="text-center"><span class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></td>';
                                        echo '<td data-label="Date" class="text-center">' . date('d M Y', strtotime($row['created_at'])) . '</td>';
                                        echo '<td data-label="Actions" class="text-center">
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
                                                            <form action="applications.php" method="post">
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
                                                                        <textarea name="review_notes" id="review_notes" class="form-control" rows="3" placeholder="Enter any notes about the status change..."></textarea>
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
                                    echo '<tr><td colspan="8" class="text-center py-4">No applications found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : 'applications.php?page=' . ($page - 1) . '&status=' . htmlspecialchars($status_filter) . '&course=' . htmlspecialchars($course_filter) . '&search=' . htmlspecialchars($search) . '&sort=' . htmlspecialchars($sort_by) . '&dir=' . htmlspecialchars($sort_dir); ?>">Previous</a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="applications.php?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($status_filter); ?>&course=<?php echo htmlspecialchars($course_filter); ?>&search=<?php echo htmlspecialchars($search); ?>&sort=<?php echo htmlspecialchars($sort_by); ?>&dir=<?php echo htmlspecialchars($sort_dir); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : 'applications.php?page=' . ($page + 1) . '&status=' . htmlspecialchars($status_filter) . '&course=' . htmlspecialchars($course_filter) . '&search=' . htmlspecialchars($search) . '&sort=' . htmlspecialchars($sort_by) . '&dir=' . htmlspecialchars($sort_dir); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
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
            document.querySelectorAll('.modal form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(form);
                    var hiddenForm = document.createElement('form');
                    hiddenForm.method = 'post';
                    hiddenForm.style.display = 'none';
                    formData.forEach(function(value, key) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        hiddenForm.appendChild(input);
                    });
                    document.body.appendChild(hiddenForm);
                    hiddenForm.submit();
                });
            });
        });
    </script>
</body>
</html>