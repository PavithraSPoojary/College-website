CREATE DATABASE IF NOT EXISTS student_registration;
USE student_registration;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    date_of_birth DATE NOT NULL,
    category ENUM('General', 'SC', 'ST', 'OBC', 'EWS') NOT NULL,
    course_applied ENUM('BCom General', 'BCA', 'BBA') NOT NULL,
    language_1 ENUM('English', 'Hindi', 'Kannada', 'Sanskrit') NOT NULL,
    language_2 ENUM('English', 'Hindi', 'Kannada', 'Sanskrit') NOT NULL,
    tenth_marks DECIMAL(5,2) NOT NULL,
    tenth_board VARCHAR(50) NOT NULL,
    pu_college VARCHAR(100) NOT NULL,
    pu_stream ENUM('Science', 'Commerce', 'Arts') NOT NULL,
    pu_marks DECIMAL(5,2) NOT NULL,
    pu_board VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    whatsapp_number VARCHAR(15) NOT NULL,
    hostel_required ENUM('Yes', 'No') NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    id_proof_path VARCHAR(255) NOT NULL,
    status ENUM('Draft', 'Submitted', 'Under Review', 'Selected', 'Rejected') DEFAULT 'Draft',
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

