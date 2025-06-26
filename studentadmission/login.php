<?php
session_set_cookie_params(0);

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_SESSION['admin_logged_in'])) {
    session_unset();
    session_destroy();
    session_start();
}


require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    $errors = [];
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $password === $user['password']) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time();
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid email or password";
            }
        } catch(PDOException $e) {
            $errors[] = "Login failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - MGMEC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
    if (window.history && window.history.pushState) {
        window.history.pushState('forward', null, '');
        window.onpopstate = function () {
            window.history.pushState('forward', null, '');
        };
    }
</script>
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

        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--dark);
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .password-toggle i {
            font-size: 1.1rem;
        }

        .btn-login {
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

        .btn-login:hover {
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

        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            border-color: rgba(25, 135, 84, 0.2);
            color: #198754;
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

            .btn-login {
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

            .btn-login {
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
                    <p>Your Journey to Excellence</p>
                </div>
            </div>
            <div class="form-section">
                <div class="form-container">
                    <h2>Welcome Back!</h2>
                    <p>Please enter your credentials to login.</p>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success">Registration successful! Please login.</div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-login">Login</button>
                        </div>
                    </form>
                    <div class="links">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
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

        setInterval(changeImage, 4000); // Change image every 4 seconds

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
    </script>
</body>
</html>