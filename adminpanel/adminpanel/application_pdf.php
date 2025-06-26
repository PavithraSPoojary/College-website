<?php
ob_start();
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: applications.php");
    exit;
}

$application_id = $_GET['id'];

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

require_once('C:/wamp64/www/mgmec1/adminpanel/adminpanel/tcpdf/TCPDF-6.4.2/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        $logo_path = 'C:/wamp64/www/mgmec1/assets/images/header_logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 8, 30);
        }
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(33, 37, 41);
        $this->Cell(0, 10, 'MGM EVENING COLLEGE, Udupi', 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 8, 'Student Application Form', 0, 1, 'C');
        $this->Line(15, 30, 193, 30);
    }

    public function Footer() {
        // Remove all footer content (no page number, no generated date)
    }
}

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Student Registration System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Application #' . $application_id);
$pdf->SetSubject('Application Details');
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();

// Profile Photo Section (top right, reduced top space)
$document_root = 'C:/wamp64/www/mgmec1/studentadmission/';
$photo_path = trim($application['photo_path'] ?? '');
$absolute_photo_path = $document_root . $photo_path;

$photo_x = 160;
$photo_y = 12; // Lowered from 8 to 12 to align better with header
$photo_w = 35;
$photo_h = 42;

if ($photo_path && file_exists($absolute_photo_path)) {
    list($img_w, $img_h) = getimagesize($absolute_photo_path);
    $scale = min(31 / $img_w, 38 / $img_h, 1);
    $draw_w = round($img_w * $scale, 2);
    $draw_h = round($img_h * $scale, 2);
    $img_x = $photo_x + ($photo_w - $draw_w) / 2;
    $img_y = $photo_y + ($photo_h - $draw_h) / 2;
    // No border for the photo
    $pdf->Image($absolute_photo_path, $img_x, $img_y, $draw_w, $draw_h, '', '', '', false, 300, '', false, false, 1, false, false, false);
} else {
    $pdf->SetXY($photo_x, $photo_y);
    // No border for the photo placeholder
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor(150);
    $pdf->MultiCell($photo_w, $photo_h, 'No Photo', 0, 'C', false, 1, 0.0, '', true, 0, false, true, $photo_h, 'M');
}

$pdf->Ln(10); // Reduce vertical space after photo

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(33, 37, 41);
$pdf->Cell(0, 8, 'Application ID: #' . $application['id'], 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'Submission Date: ' . date('F j, Y', strtotime($application['created_at'])), 0, 1);
$pdf->Ln(3);

// Status badge style (black)
$status = $application['status'] ?? 'N/A';
$pdf->SetFont('helvetica', 'B', 11);
// $pdf->SetFillColor(0, 0, 0); // black
// $pdf->SetTextColor(255,255,255); // white text
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0, 9, 'Application Status: ' . $status, 0, 1, 'L', false, '', 0, false, 'T', 'M');
$pdf->Ln(3);

function section($pdf, $title) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(0, 0, 0); // black
    $pdf->Cell(0, 9, $title, 0, 1);
    $pdf->SetDrawColor(0, 0, 0); // black
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(2);
}

function infoRows($pdf, $data) {
    foreach ($data as $label => $value) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0); // black
        $pdf->Cell(50, 7, $label . ':', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0); // black
        $pdf->MultiCell(130, 7, $value ?: 'N/A', 0, 'L');
        $pdf->Ln(1);
    }
    $pdf->Ln(2);
}

section($pdf, 'Personal Information');
infoRows($pdf, [
    'Full Name' => $application['student_name'],
    "Father's Name" => $application['father_name'],
    "Mother's Name" => $application['mother_name'],
    'Gender' => $application['gender'],
    'Date of Birth' => date('F j, Y', strtotime($application['date_of_birth'] ?? '')),
    'Category' => $application['category'],
    'Email' => $application['email'],
    'Contact Number' => $application['contact_number'],
    'WhatsApp Number' => $application['whatsapp_number']
]);

section($pdf, 'Academic Information');
infoRows($pdf, [
    'Course Applied' => $application['course_applied'],
    'Languages' => trim(($application['language_1'] ?? '') . ', ' . ($application['language_2'] ?? '')),
    '10th Board' => $application['tenth_board'],
    '10th Marks' => $application['tenth_marks'] ? $application['tenth_marks'] . '%' : '',
    'PU College' => $application['pu_college'],
    'PU Stream' => $application['pu_stream'],
    'PU Board' => $application['pu_board'],
    'PU Marks' => $application['pu_marks'] ? $application['pu_marks'] . '%' : ''
]);

// Address Section
section($pdf, 'Address Information');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(33, 37, 41);
$pdf->MultiCell(0, 7, $application['address'] ?: 'N/A', 0, 'L');
$pdf->Ln(4);

// Other Info Section
section($pdf, 'Other Information');
infoRows($pdf, [
    'Hostel Required' => $application['hostel_required']
]);

// Review Notes Section (if any)
if (!empty($application['review_notes'])) {
    section($pdf, 'Review Notes');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(180, 0, 0);
    $pdf->MultiCell(0, 7, $application['review_notes'], 0, 'L');
    $pdf->Ln(2);
}

// Separator line before footer
$pdf->SetDrawColor(180, 180, 180);
$pdf->SetLineWidth(0.1);
$pdf->Line(15, $pdf->GetY() + 3, 195, $pdf->GetY() + 3);
$pdf->Ln(8);

ob_end_clean();
$pdf->Output('application_' . $application_id . '.pdf', 'I');
?>
