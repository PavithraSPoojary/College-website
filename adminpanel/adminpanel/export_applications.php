<?php
session_start();
require_once 'db_connect.php';

// Use our custom autoloader
require_once 'C:\wamp64\www\admin panel\vendor\phpoffice\PhpSpreadsheet-4.2.0\PhpSpreadsheet-4.2.0\src\PhpSpreadsheet\autoload.php';

// Use statements
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cache\SimpleCache as PhpSimpleCache;

// Initialize filter variables

// Initialize filter variables
$status = isset($_GET['status']) ? $_GET['status'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$export_format = isset($_GET['export_format']) ? $_GET['export_format'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Verify token
if (!isset($_GET['token'])) {
    header("Location: dashboard.php");
    exit;
}

// Handle Excel export
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    // Get filter parameters from URL
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $course = isset($_GET['course']) ? $_GET['course'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    
    // Build SQL query with filters
    $sql = "SELECT a.id, a.student_name, a.father_name, a.mother_name, a.gender, a.course_applied, 
                     a.category, a.pu_marks, a.status, a.created_at 
              FROM applications a 
              WHERE 1=1";
    
    $params = [];
    
    // Add filter conditions
    if (!empty($status)) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    if (!empty($course)) {
        $sql .= " AND a.course_applied = ?";
        $params[] = $course;
    }
    
    if (!empty($search)) {
        $search_param = "%" . $search . "%";
        $sql .= " AND (a.student_name LIKE ? OR a.father_name LIKE ? OR a.email LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($from_date)) {
        $sql .= " AND a.created_at >= ?";
        $params[] = $from_date . ' 00:00:00';
    }
    
    if (!empty($to_date)) {
        $sql .= " AND a.created_at <= ?";
        $params[] = $to_date . ' 23:59:59';
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->get_result();
    $applications = $result->fetch_all(MYSQLI_ASSOC);

    // Set headers for CSV file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student_applications_' . date('Y-m-d') . '.csv"');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Add BOM (Byte Order Mark) for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Add headers
    $headers = array('ID', 'Student Name', 'Father Name', 'Mother Name', 'Gender', 'Course Applied', 'Category', 'PU Marks', 'Status', 'Application Date');
    fputcsv($output, $headers);

    // Add data rows
    foreach ($applications as $application) {
        fputcsv($output, array(
            $application['id'],
            $application['student_name'],
            $application['father_name'],
            $application['mother_name'],
            $application['gender'],
            $application['course_applied'],
            $application['category'],
            $application['pu_marks'],
            $application['status'],
            date('Y-m-d', strtotime($application['created_at']))
        ));
    }

    // Close the output stream
    fclose($output);
    exit;
} else {
    // If not exporting, show the applications page with filters
    header('Content-Type: text/html; charset=utf-8');
    $page = 1; // Reset to first page when not exporting
    $per_page = 15; // Number of records per page
}

// Get filter parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // We accept parameters from both GET and POST to handle form submissions
    $params_source = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;
    
    $from_date = isset($params_source['from_date']) ? htmlspecialchars(trim($params_source['from_date'])) : '';
    $to_date = isset($params_source['to_date']) ? htmlspecialchars(trim($params_source['to_date'])) : '';
    $export_format = isset($params_source['export_format']) ? htmlspecialchars(trim($params_source['export_format'])) : '';
    
    // Validate date formats
    if (!empty($from_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
        $_SESSION['error'] = "Invalid 'From Date' format. Please use YYYY-MM-DD.";
        $from_date = '';
    }
    
    if (!empty($to_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
        $_SESSION['error'] = "Invalid 'To Date' format. Please use YYYY-MM-DD.";
        $to_date = '';
    }
}

// Build SQL query with filters
$sql_count = "SELECT COUNT(*) FROM applications a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
$sql = "SELECT a.*, u.full_name as user_full_name, u.email as user_email 
        FROM applications a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE 1=1";

$params = [];
$where_clauses = [];

// Add filter conditions
if (!empty($status)) {
    $where_clauses[] = "a.status = ?";
    $params[] = $status;
}

if (!empty($course)) {
    $where_clauses[] = "a.course_applied = ?";
    $params[] = $course;
}

if (!empty($from_date)) {
    $where_clauses[] = "a.created_at >= ?";
    $params[] = $from_date . ' 00:00:00';
}

if (!empty($to_date)) {
    $where_clauses[] = "a.created_at <= ?";
    $params[] = $to_date . ' 23:59:59';
}

// Append where clauses to SQL if any
if (!empty($where_clauses)) {
    $sql_count .= " AND " . implode(" AND ", $where_clauses);
    $sql .= " AND " . implode(" AND ", $where_clauses);
}

// Order by most recent applications first
$sql .= " ORDER BY a.created_at DESC";

// Add pagination for display (not for exports)
$offset = ($page - 1) * $per_page;
$sql_paginated = $sql . " LIMIT " . $per_page . " OFFSET " . $offset;

// Process the export if requested
if (!empty($export_format) && ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['export_now']))) {
    try {
        // Check if we're dealing with potentially large dataset
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->execute($params);
        $total_records = $stmt_count->fetchColumn();
        
        if ($total_records == 0) {
            $_SESSION['error'] = "No data found to export.";
            header("Location: export_applications.php?status=$status&course=$course&from_date=$from_date&to_date=$to_date");
            exit;
        }
        
        // For large datasets, set appropriate PHP limits
        if ($total_records > 1000) {
            // Increase memory and execution time for large exports
            ini_set('memory_limit', '256M');
            set_time_limit(300); // 5 minutes
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $applications = $result->fetch_all(MYSQLI_ASSOC);
        
        switch ($export_format) {
            case 'csv':
                exportToCSV($applications);
                break;
            case 'excel':
                exportToExcel($applications);
                break;
            case 'pdf':
                exportToPDF($applications);
                break;
            default:
                $_SESSION['error'] = "Invalid export format selected.";
                header("Location: export_applications.php?status=$status&course=$course&from_date=$from_date&to_date=$to_date");
                exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: export_applications.php?status=$status&course=$course&from_date=$from_date&to_date=$to_date");
        exit;
    }
}

/**
 * Export data to CSV format
 */
function exportToCSV($applications) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="applications_export_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM to fix Excel UTF-8 display issues
    fputs($output, "\xEF\xBB\xBF");
    
    // Define headers with friendly names
    $headers = [
        'ID', 'Student Name', 'Father Name', 'Mother Name', 'Gender', 'Date of Birth', 
        'Category', 'Course Applied', 'Language 1', 'Language 2', 'X Marks', 'X Board',
        'PU College', 'PU Stream', 'PU Marks', 'PU Board', 'Address', 'Email', 
        'Contact Number', 'WhatsApp Number', 'Hostel Required', 'Status', 'Application Date'
    ];
    
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($applications as $row) {
        $data = [
            $row['id'],
            $row['student_name'],
            $row['father_name'],
            $row['mother_name'],
            $row['gender'],
            $row['date_of_birth'],
            $row['category'],
            $row['course_applied'],
            $row['language_1'],
            $row['language_2'],
            $row['tenth_marks'],
            $row['tenth_board'],
            $row['pu_college'],
            $row['pu_stream'],
            $row['pu_marks'],
            $row['pu_board'],
            $row['address'],
            $row['email'],
            $row['contact_number'],
            $row['whatsapp_number'],
            $row['hostel_required'],
            $row['status'],
            $row['created_at']
        ];
        
        fputcsv($output, $data);
    }
    
    fclose($output);
    exit;
}

/**
 * Export data to Excel format using PHPExcel
 */
function exportToExcel($applications) {
    require_once 'C:\wamp64\www\admin panel\admin.php\vendor\PHPExcel-1.8.2\Classes\PHPExcel.php';
    require_once 'C:\wamp64\www\admin panel\admin.php\vendor\PHPExcel-1.8.2\Classes\PHPExcel\IOFactory.php';
    require_once 'C:\wamp64\www\admin panel\admin.php\vendor\PHPExcel-1.8.2\Classes\PHPExcel\Writer\Excel2007.php';
    
    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();
    
    // Set document properties
    $objPHPExcel->getProperties()->setCreator("Student Registration System")
                                 ->setLastModifiedBy("Admin")
                                 ->setTitle("Student Applications Export")
                                 ->setSubject("Student Applications")
                                 ->setDescription("Exported applications data");
    
    // Add some data
    $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', 'ID')
                ->setCellValue('B1', 'Student Name')
                ->setCellValue('C1', 'Father Name')
                ->setCellValue('D1', 'Mother Name')
                ->setCellValue('E1', 'Gender')
                ->setCellValue('F1', 'Date of Birth')
                ->setCellValue('G1', 'Category')
                ->setCellValue('H1', 'Course Applied')
                ->setCellValue('I1', 'Language 1')
                ->setCellValue('J1', 'Language 2')
                ->setCellValue('K1', 'X Marks')
                ->setCellValue('L1', 'X Board')
                ->setCellValue('M1', 'PU College')
                ->setCellValue('N1', 'PU Stream')
                ->setCellValue('O1', 'PU Marks')
                ->setCellValue('P1', 'PU Board')
                ->setCellValue('Q1', 'Address')
                ->setCellValue('R1', 'Email')
                ->setCellValue('S1', 'Contact Number')
                ->setCellValue('T1', 'WhatsApp Number')
                ->setCellValue('U1', 'Hostel Required')
                ->setCellValue('V1', 'Status')
                ->setCellValue('W1', 'Application Date');
    
    // Add data rows
    $row = 2;
    foreach ($applications as $app) {
        $objPHPExcel->getActiveSheet()
            ->setCellValue('A'.$row, $app['id'])
            ->setCellValue('B'.$row, $app['student_name'])
            ->setCellValue('C'.$row, $app['father_name'])
            ->setCellValue('D'.$row, $app['mother_name'])
            ->setCellValue('E'.$row, $app['gender'])
            ->setCellValue('F'.$row, $app['date_of_birth'])
            ->setCellValue('G'.$row, $app['category'])
            ->setCellValue('H'.$row, $app['course_applied'])
            ->setCellValue('I'.$row, $app['language_1'])
            ->setCellValue('J'.$row, $app['language_2'])
            ->setCellValue('K'.$row, $app['tenth_marks'])
            ->setCellValue('L'.$row, $app['tenth_board'])
            ->setCellValue('M'.$row, $app['pu_college'])
            ->setCellValue('N'.$row, $app['pu_stream'])
            ->setCellValue('O'.$row, $app['pu_marks'])
            ->setCellValue('P'.$row, $app['pu_board'])
            ->setCellValue('Q'.$row, $app['address'])
            ->setCellValue('R'.$row, $app['email'])
            ->setCellValue('S'.$row, $app['contact_number'])
            ->setCellValue('T'.$row, $app['whatsapp_number'])
            ->setCellValue('U'.$row, $app['hostel_required'])
            ->setCellValue('V'.$row, $app['status'])
            ->setCellValue('W'.$row, $app['created_at']);
            
        $row++;
    }
    
    // Rename worksheet
    $objPHPExcel->getActiveSheet()->setTitle('Applications');
    
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
    
    // Redirect output to a client’s web browser (Excel2007)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="applications_export_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;;
    
    foreach ($applications as $row) {
        echo '
        <Row>
          <Cell><Data ss:Type="String">' . xmlEscape($row['id']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['student_name']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['father_name']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['mother_name']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['gender']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['date_of_birth']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['category']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['course_applied']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['language_1']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['language_2']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['tenth_marks']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['tenth_board']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['pu_college']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['pu_stream']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['pu_marks']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['pu_board']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['address']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['email']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['contact_number']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['whatsapp_number']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['hostel_required']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['status']) . '</Data></Cell>
          <Cell><Data ss:Type="String">' . xmlEscape($row['created_at']) . '</Data></Cell>
        </Row>';
    }
    
    echo '
      </Table>
     </Worksheet>
    </Workbook>';
    exit;
}

/**
 * Helper function to escape XML special characters
 */
function xmlEscape($string) {
    return str_replace(
        ['&', '<', '>', '"', "'"],
        ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
        $string
    );
}

/**
 * Export data to PDF format with improved HTML structure
 * This is still a browser-based solution, but with better formatting
 */
function exportToPDF($applications) {
    // Set content type to HTML for browser rendering
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Application Export</title>
        <style>
            @page { size: landscape; margin: 1cm; }
            body {
                font-family: Arial, Helvetica, sans-serif;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }
            .college-name {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .report-title {
                font-size: 16px;
                margin-bottom: 10px;
            }
            .report-meta {
                font-size: 12px;
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                font-size: 10px;
            }
            th {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #777;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            .print-button {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                font-size: 16px;
                margin: 10px 0;
                cursor: pointer;
                border: none;
                border-radius: 4px;
            }
            .filters {
                margin-bottom: 20px;
                padding: 10px;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .no-print {
                margin-bottom: 20px;
            }
            @media print {
                .no-print {
                    display: none;
                }
                body {
                    padding: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <button onclick="window.print();" class="print-button">Print / Save as PDF</button>
            <p>Click the button above to print this report or save as PDF. Use your browser\'s print dialog to select "Save as PDF" option.</p>
        </div>
        
        <div class="header">
            <div class="college-name">Student Registration System</div>
            <div class="report-title">Student Applications Report</div>
            <div class="report-meta">Generated on: ' . date('F j, Y, g:i a') . '</div>
        </div>';
        
        // Display filter information if any filters were applied
        $filters_applied = false;
        $filter_info = '<div class="filters">Applied filters: ';
        
        if (!empty($_POST['status']) || !empty($_GET['status'])) {
            $status_value = !empty($_POST['status']) ? $_POST['status'] : $_GET['status'];
            $filter_info .= 'Status: <strong>' . htmlspecialchars($status_value) . '</strong>, ';
            $filters_applied = true;
        }
        
        if (!empty($_POST['course']) || !empty($_GET['course'])) {
            $course_value = !empty($_POST['course']) ? $_POST['course'] : $_GET['course'];
            $filter_info .= 'Course: <strong>' . htmlspecialchars($course_value) . '</strong>, ';
            $filters_applied = true;
        }
        
        if (!empty($_POST['from_date']) || !empty($_GET['from_date'])) {
            $from_date_value = !empty($_POST['from_date']) ? $_POST['from_date'] : $_GET['from_date'];
            $filter_info .= 'From: <strong>' . htmlspecialchars($from_date_value) . '</strong>, ';
            $filters_applied = true;
        }
        
        if (!empty($_POST['to_date']) || !empty($_GET['to_date'])) {
            $to_date_value = !empty($_POST['to_date']) ? $_POST['to_date'] : $_GET['to_date'];
            $filter_info .= 'To: <strong>' . htmlspecialchars($to_date_value) . '</strong>, ';
            $filters_applied = true;
        }
        
        // Remove trailing comma and space
        if ($filters_applied) {
            $filter_info = rtrim($filter_info, ', ') . '</div>';
            echo $filter_info;
        }
        
        echo '<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Father Name</th>
                    <th>Mother Name</th>
                    <th>Gender</th>
                    <th>Course Applied</th>
                    <th>Category</th>
                    <th>PU Marks</th>
                    <th>Status</th>
                    <th>Application Date</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($applications as $row) {
        echo '<tr>
            <td>' . htmlspecialchars($row['id']) . '</td>
            <td>' . htmlspecialchars($row['student_name']) . '</td>
            <td>' . htmlspecialchars($row['father_name']) . '</td>
            <td>' . htmlspecialchars($row['mother_name']) . '</td>
            <td>' . htmlspecialchars($row['gender']) . '</td>
            <td>' . htmlspecialchars($row['course_applied']) . '</td>
            <td>' . htmlspecialchars($row['category']) . '</td>
            <td>' . htmlspecialchars($row['pu_marks']) . '</td>
            <td>' . htmlspecialchars($row['status']) . '</td>
            <td>' . date('Y-m-d', strtotime($row['created_at'])) . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="footer">
            <p>This is a system-generated report. For a complete PDF with all fields, please install a proper PDF library.</p>
            <p>Total Records: ' . count($applications) . '</p>
        </div>
        
        <script>
            // Auto-print in 1 second to allow page to fully load
            window.onload = function() {
                setTimeout(function() {
                    // Uncomment the line below to enable auto-print
                    // window.print();
                }, 1000);
            };
        </script>
    </body>
    </html>';
    exit;
}

// If we're here, we're displaying the page content, not exporting
try {
    // Get total count for pagination
    $sql_count = "SELECT COUNT(*) as total FROM applications";
    $result_count = $conn->query($sql_count);
    if (!$result_count) {
        throw new Exception($conn->error);
    }
    $row = $result_count->fetch_assoc();
    $total_records = $row['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Get paginated records
    $sql_paginated = "SELECT a.id, a.student_name, a.father_name, a.email, a.course_applied, a.pu_marks, a.status, a.created_at 
                     FROM applications a ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql_paginated);
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception($conn->error);
    }
    $applications = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    $result_count->free();
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $applications = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Applications - Student Registration System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .card {
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .export-options {
            margin-top: 20px;
        }
        .export-btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .card-header {
            background-color: #343a40;
            color: white;
        }
        .form-group label {
            font-weight: bold;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .alert {
            margin-top: 20px;
        }
        .badge-pill {
            padding: 5px 10px;
            font-size: 0.85em;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .field-selector {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 20px;
        }
        .loading-content {
            text-align: center;
            background-color: rgba(0,0,0,0.7);
            padding: 20px;
            border-radius: 10px;
        }
        .spinner-border {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Loading overlay for exports -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner-border text-light" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <div>Preparing export, please wait...</div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-export"></i> Export Applications</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php 
                                    echo $_SESSION['error']; 
                                    unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php 
                                    echo $_SESSION['success']; 
                                    unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Filter Form -->
                        <form method="GET" action="" id="filterForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="status">Application Status:</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="Draft" <?php echo ($status == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                                            <option value="Submitted" <?php echo ($status == 'Submitted') ? 'selected' : ''; ?>>Submitted</option>
                                            <option value="Under Review" <?php echo ($status == 'Under Review') ? 'selected' : ''; ?>>Under Review</option>
                                            <option value="Selected" <?php echo ($status == 'Selected') ? 'selected' : ''; ?>>Selected</option>
                                            <option value="Rejected" <?php echo ($status == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="course">Course Applied:</label>
                                        <select class="form-control" id="course" name="course">
                                            <option value="">All Courses</option>
                                            <option value="BCom General" <?php echo ($course == 'BCom General') ? 'selected' : ''; ?>>BCom General</option>
                                            <option value="BCA" <?php echo ($course == 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                            <option value="BBA" <?php echo ($course == 'BBA') ? 'selected' : ''; ?>>BBA</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="from_date">From Date:</label>
                                        <input type="text" class="form-control datepicker" id="from_date" name="from_date" value="<?php echo $from_date; ?>" placeholder="YYYY-MM-DD">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="to_date">To Date:</label>
                                        <input type="text" class="form-control datepicker" id="to_date" name="to_date" value="<?php echo $to_date; ?>" placeholder="YYYY-MM-DD">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter Applications
                                </button>
                                <a href="export_applications.php" class="btn btn-secondary">
                                    <i class="fas fa-sync"></i> Reset Filters
                                </a>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <h4>Export Results (<?php echo $total_records; ?> applications found)</h4>
                        
                        <?php if ($total_records > 0): ?>
                            <!-- Export Options -->
                            <div class="export-options">
                                <form method="POST" action="" id="exportForm">
                                    <!-- Hidden inputs to preserve filter values -->
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                    <input type="hidden" name="course" value="<?php echo htmlspecialchars($course); ?>">
                                    <input type="hidden" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                                    <input type="hidden" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                                    
                                    <!-- Export buttons -->
                                    <div class="btn-group" role="group">
                                        <button type="button" onclick="prepareExport('csv')" class="btn btn-success export-btn">
                                            <i class="fas fa-file-csv"></i> Export as CSV
                                        </button>
                                        
                                        <button type="button" onclick="prepareExport('excel')" class="btn btn-primary export-btn">
                                            <i class="fas fa-file-excel"></i> Export as Excel
                                        </button>
                                        
                                        <button type="button" onclick="prepareExport('pdf')" class="btn btn-danger export-btn">
                                            <i class="fas fa-file-pdf"></i> Export as PDF
                                        </button>
                                    </div>
                                    
                                    <!-- Hidden input for export_format, will be set by JavaScript -->
                                    <input type="hidden" name="export_format" id="export_format" value="">
                                </form>
                            </div>
                            
                            <!-- Toggle button for custom fields selection -->
                            <button class="btn btn-outline-secondary mb-3" type="button" data-toggle="collapse" data-target="#fieldSelector">
                                <i class="fas fa-cog"></i> Customize Export Fields
                            </button>
                            
                            <!-- Custom fields selection (collapsed by default) -->
                            <div class="collapse field-selector" id="fieldSelector">
                                <div class="form-row">
                                    <div class="col-12">
                                        <h5>Select fields to include in export</h5>
                                        <p class="text-muted">Note: This feature will be implemented in a future update.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Application Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['course_applied']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td>
                                                    <?php 
                                                        $statusClass = '';
                                                        switch ($row['status']) {
                                                            case 'Draft': $statusClass = 'badge-secondary'; break;
                                                            case 'Submitted': $statusClass = 'badge-info'; break;
                                                            case 'Under Review': $statusClass = 'badge-warning'; break;
                                                            case 'Selected': $statusClass = 'badge-success'; break;
                                                            case 'Rejected': $statusClass = 'badge-danger'; break;
                                                            default: $statusClass = 'badge-secondary';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?> badge-pill">
                                                        <?php echo htmlspecialchars($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_application.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="application_pdf.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-file-pdf"></i> PDF
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo urlencode($status); ?>&course=<?php echo urlencode($course); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Calculate range of page numbers to display
                                        $range = 2; // Display 2 pages before and after current page
                                        $start_page = max(1, $page - $range);
                                        $end_page = min($total_pages, $page + $range);
                                        
                                        // Always show first page
                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . urlencode($status) . '&course=' . urlencode($course) . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '">1</a></li>';
                                            if ($start_page > 2) {
                                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                                            }
                                        }
                                        
                                        // Display page numbers
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active = ($i == $page) ? 'active' : '';
                                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '&status=' . urlencode($status) . '&course=' . urlencode($course) . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '">' . $i . '</a></li>';
                                        }
                                        
                                        // Always show last page
                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&status=' . urlencode($status) . '&course=' . urlencode($course) . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo urlencode($status); ?>&course=<?php echo urlencode($course); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No applications found matching your criteria.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        
                        <!-- Show export stats if available -->
                        <?php if ($total_records > 0): ?>
                            <span class="float-right text-muted">
                                Showing <?php echo count($applications); ?> of <?php echo $total_records; ?> total applications
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize datepickers
            $(".datepicker").flatpickr({
                dateFormat: "Y-m-d",
                allowInput: true
            });
            
            // Add event handlers for export buttons
            $("#exportForm").submit(function() {
                $("#loadingOverlay").css("display", "flex");
            });
        });
        
        // Function to prepare export format and submit the form
        function prepareExport(format) {
            // Show loading overlay
            $("#loadingOverlay").css("display", "flex");
            
            // Set the export format
            $("#export_format").val(format);
            
            // Submit the form
            $("#exportForm").submit();
            
            // Set a timeout to hide the overlay in case of errors
            setTimeout(function() {
                $("#loadingOverlay").css("display", "none");
            }, 10000); // Hide after 10 seconds if not redirected
        }
    </script>
</body>
</html>