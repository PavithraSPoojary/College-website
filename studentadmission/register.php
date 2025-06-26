<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($phone) || !preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Valid 10-digit phone number is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } else {
        // Enhanced password validation
        $password_errors = [];
        
        if (strlen($password) < 8) {
            $password_errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $password_errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $password_errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $password_errors[] = "Password must contain at least one special character (!@#$%^&*(),.?\":{}|<>)";
        }
        
        if (!empty($password_errors)) {
            $errors[] = "Password is not strong enough. Please ensure it meets the following requirements:";
            foreach ($password_errors as $error) {
                $errors[] = "- " . $error;
            }
        }
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            }
        } catch(PDOException $e) {
            $errors[] = "Database error occurred";
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $hashed_password = $password;
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hashed_password]);
            
            // Create application record
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO applications (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            
            header("Location: login.php?registered=1");
            exit();
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - MGMEC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #400057;        /* Main brand color - Purple */
            --secondary: #FFD700;      /* Accent color - Gold */
            --dark: #333;              /* Dark text color */
            --light: #f8f9fa;          /* Light background color */
            --white: #ffffff;          /* Pure white */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--white);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container-fluid {
            flex-grow: 1;
            padding: 0;
        }

        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo img {
            height: 60px;
            width: auto;
            object-fit: contain;
        }

        .logo span {
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.3;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .main-content {
            display: flex;
            flex: 1;
            min-height: calc(100vh - 80px);
        }

        .promo-section {
            flex: 1;
            background-color: #000;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .promo-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .promo-image.active {
            opacity: 0.8;
        }

        .promo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4));
            z-index: 1;
        }

        .promo-text {
            position: relative;
            color: var(--white);
            text-align: center;
            z-index: 2;
            padding: 2rem;
        }

        .promo-text h1 {
            font-size: 4rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .promo-text p {
            font-size: 1.5rem;
            font-weight: 500;
            margin: 0.5rem 0 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .form-section {
            flex: 1;
            background-color: var(--light);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            max-width: 400px;
            width: 100%;
        }

        .form-section h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
            text-align: center;
        }

        .form-section p {
            font-size: 1rem;
            color: #666;
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            border: 1px solid #d5d5d5;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(64, 0, 87, 0.2);
            outline: none;
        }

        .form-control::placeholder {
            color: #999;
            opacity: 1;
        }

        .form-text {
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .password-input-group {
            position: relative;
        }

        .password-input-group input {
            width: 100%;
            padding-right: 35px;
            -webkit-text-security: none; /* Disable default password reveal */
        }

        .password-input-group input::-ms-reveal,
        .password-input-group input::-ms-clear {
            display: none; /* Hide IE/Edge password reveal button */
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: #666; /* Match form text color for consistency */
            opacity: 0.5;
            transition: opacity 0.3s;
            outline: none;
            padding: 0;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .password-toggle:focus,
        .password-toggle:focus-visible {
            outline: none;
        }

        .password-toggle i {
            font-size: 1rem;
            color: #666; /* Ensure icon color matches and has no outline */
        }

        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            background-color: var(--light);
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .password-requirements {
            font-size: 0.9em;
            margin-top: 5px;
            color: var(--dark);
            line-height: 1.4;
        }

        .requirement {
            color: #dc3545;
        }

        .requirement.valid {
            color: #198754;
        }

        .btn-register {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--white);
            border-radius: 8px;
            width: 100%;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-register:hover {
            color:white;
            background-color: #300047;
            transform: translateY(-2px);
        }

        .links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
            color: #300047;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .main-content {
                flex-direction: column;
            }

            .promo-section {
                height: 400px;
                min-height: 300px;
            }

            .promo-text h1 {
                font-size: 3rem;
            }

            .promo-text p {
                font-size: 1.2rem;
            }

            .form-section {
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .promo-section {
                height: 300px;
            }

            .promo-text h1 {
                font-size: 2.5rem;
            }

            .promo-text p {
                font-size: 1rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .form-section h2 {
                font-size: 1.75rem;
            }

            .form-control {
                font-size: 0.9rem;
                padding: 0.65rem;
            }

            .btn-register {
                font-size: 0.95rem;
                padding: 0.65rem;
            }
        }

        @media (max-width: 576px) {
            .promo-section {
                height: 250px;
            }

            .promo-text h1 {
                font-size: 2rem;
            }

            .promo-text p {
                font-size: 0.9rem;
            }

            .form-section {
                padding: 1rem;
            }

            .form-container {
                max-width: 100%;
            }

            .form-section h2 {
                font-size: 1.5rem;
            }

            .form-control {
                font-size: 0.85rem;
                padding: 0.6rem;
            }

            .btn-register {
                font-size: 0.9rem;
                padding: 0.6rem;
            }

            .logo span {
                font-size: 0.9rem;
            }

            .logo img {
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="../pages/index.html"> <!-- link to homepage -->
            <img src="../assets/images/header logo.svg" alt="MGMEC Logo" />
        </a>
                    <span>MAHATMA GANDHI <br>MEMORIAL<br> EVENING COLLEGE</span>
                </div>
            </div>
        </div>
    </header>
    <div class="container-fluid">
        <div class="main-content">
            <div class="promo-section">
                <img src="../assets/images/college-entrance.jpg" class="promo-image active" alt="Promo Image 1">
                <img src="../assets/images/IMG-20250530-WA0039.jpg" class="promo-image" alt="Promo Image 2">
                <img src="../assets/images/admissions-hero.jpg" class="promo-image" alt="Promo Image 3">
                <img src="../assets/images/WhatsApp Image 2025-05-30 at 18.10.36_995bb3f3.jpg" class="promo-image" alt="Promo Image 4">
                <img src="../assets/images/IMG-20250530-WA0041.jpg" class="promo-image" alt="Promo Image 5">
                <img src="../assets/images/IMG-20250530-WA0040.jpg" class="promo-image" alt="Promo Image 6">
                <img src="../assets/images/WhatsApp Image 2025-05-30 at 18.22.16_b0d8a880.jpg" class="promo-image" alt="Promo Image 7">
                <div class="promo-overlay"></div>
                <div class="promo-text">
                    <h1>Welcome to MGMEC</h1>
                    <p>Join Our Vibrant Community Today!</p>
                </div>
            </div>
            <div class="form-section">
                <div class="form-container">
                    <h2>Student Registration</h2>
                    <p>Register to start your journey with us.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   pattern="[0-9]{10}" required>
                            <div class="form-text">Enter 10-digit phone number</div>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <span class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength" id="password-strength"></div>
                            <div class="password-requirements">
                                <div class="requirement" id="length">✓ At least 8 characters</div>
                                <div class="requirement" id="uppercase">✓ Uppercase letter</div>
                                <div class="requirement" id="lowercase">✓ Lowercase letter</div>
                                <div class="requirement" id="number">✓ Number</div>
                                <div class="requirement" id="special">✓ Special character</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-register">Register</button>
                        </div>
                    </form>
                    <div class="links">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image slideshow
        const images = document.querySelectorAll('.promo-image');
        let currentIndex = 0;

        function changeImage() {
            images[currentIndex].classList.remove('active');
            currentIndex = (currentIndex + 1) % images.length;
            images[currentIndex].classList.add('active');
        }

        setInterval(changeImage, 4000);

        // Password strength validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('password-strength');
            const requirements = {
                length: document.getElementById('length'),
                uppercase: document.getElementById('uppercase'),
                lowercase: document.getElementById('lowercase'),
                number: document.getElementById('number'),
                special: document.getElementById('special')
            };

            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            // Update requirement indicators
            requirements.length.classList.toggle('valid', hasLength);
            requirements.uppercase.classList.toggle('valid', hasUppercase);
            requirements.lowercase.classList.toggle('valid', hasLowercase);
            requirements.number.classList.toggle('valid', hasNumber);
            requirements.special.classList.toggle('valid', hasSpecial);

            // Calculate strength
            let strengthValue = 0;
            if (hasLength) strengthValue += 20;
            if (hasUppercase) strengthValue += 20;
            if (hasLowercase) strengthValue += 20;
            if (hasNumber) strengthValue += 20;
            if (hasSpecial) strengthValue += 20;

            // Update strength bar
            strength.style.width = strengthValue + '%';
            if (strengthValue < 40) {
                strength.style.backgroundColor = '#dc3545';
            } else if (strengthValue < 80) {
                strength.style.backgroundColor = '#ffc107';
            } else {
                strength.style.backgroundColor = '#198754';
            }
        });

        // Password toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const confirmInput = this;
            
            if (confirmPassword !== password) {
                confirmInput.setCustomValidity("Passwords do not match");
            } else {
                confirmInput.setCustomValidity("");
            }
        });
    </script>
</body>
</html>