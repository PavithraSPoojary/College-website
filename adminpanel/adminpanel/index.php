<?php
// Start session at the very beginning
session_set_cookie_params(0);
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
session_regenerate_id(true);

// Clear any residual session data
if (isset($_SESSION['admin_logged_in'])) {
    unset($_SESSION['admin_logged_in']);
}


session_regenerate_id(true);
// Check if already logged in
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Define admin credentials (In production, these should be in the database)
$admin_username = "admin";
$admin_password = "admin123"; // In production, this should be hashed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $login_error = 'Invalid username or password!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MGMEC</title>
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
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        .logo span {
            font-weight: bold;
            font-size: 1rem;
            line-height: 1.2;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(64, 0, 87, 0.1);
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo i {
            font-size: 50px;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .login-logo h4 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .login-logo p {
            font-family: 'Montserrat', sans-serif;
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .form-label {
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            font-family: 'Montserrat', sans-serif;
            font-weight: 400;
            padding: 0.75rem;
            border-radius: 5px;
            border: 1px solid rgb(213, 213, 213);
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(64, 0, 87, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: rgb(149, 149, 149);
            opacity: 1;
        }

        .password-input-group {
            position: relative;
            width: 100%;
        }

        .password-input-group input {
            width: 100%;
            padding-right: 2rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--dark);
            opacity: 0.5;
            transition: opacity 0.3s;
            padding: 0;
            width: 1.25rem;
            height: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .password-toggle i {
            font-size: 1rem;
        }

        .btn-login {
            background-color: var(--primary);
            color: var(--white);
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 5px;
            border: none;
            transition: all 0.3s ease;
            margin-top: 1rem;
            width: 100%;
        }

        .btn-login:hover {
            color: var(--white);
            background-color: var(--primary);
            transform: translateY(-2px);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            padding: 0.75rem 1.25rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                margin: 1.5rem auto;
            }

            .login-logo i {
                font-size: 40px;
            }

            .login-logo h4 {
                font-size: 1.25rem;
            }

            .form-control {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            .btn-login {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .logo span {
                font-size: 1rem;
            }

            .logo img {
                height: 40px;
            }
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 1rem;
                margin: 1rem auto;
            }

            .login-logo i {
                font-size: 35px;
            }

            .login-logo h4 {
                font-size: 1.1rem;
            }

            .login-logo p {
                font-size: 0.8rem;
            }

            .form-control {
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .btn-login {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .logo span {
                font-size: 0.9rem;
            }

            .logo img {
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                     <a href="../../pages/index.html">
                        <img src="../../assets/images/header logo.svg" alt="MGMEC Logo">
                    </a> 
                    <span>MAHATMA GANDHI <br>MEMORIAL<br> EVENING COLLEGE</span>
                </div>
            </div>
        </div>
    </header>
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                
                <h4>Admin Login</h4>
                <p class="text-muted">Student Registration System</p>
            </div>
            
            <?php
            if (isset($login_error)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($login_error) . '</div>';
            }
            ?>
            
            <form action="" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username*" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password*" required>
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-login">Login</button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
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