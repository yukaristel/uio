<?php
/**
 * HALAMAN LOGIN
 * Pastel Sea Green Theme
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Rumah Makan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- ====================== PASTEL SEA GREEN STYLE ====================== -->
    <style>
        :root {
            --primary: #129A7D;      /* Light Sea Green */
            --secondary: #9BD3CB;    /* Light Steel Blue */
            --danger: #F39CA4;       /* Light Coral */
            --warning: #FCBCBC;      /* Light Pink */
            --info: #9BD3CB;         /* Antique White */
            --light: #FFF7F9;        /* Pastel White */
            --dark: #0E6F57;         /* Darkened Sea Green */
        }

        body {
            background: var(--light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 2px solid var(--secondary);
            overflow: hidden;
        }

        .login-header {
            background: var(--primary);
            color: white;
            padding: 35px 25px;
            text-align: center;
            border-bottom: 4px solid var(--secondary);
        }

        .login-header h2 {
            font-weight: bold;
            margin: 0;
        }

        .login-header p {
            margin: 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 35px;
        }

        .input-group-text {
            background: var(--info);
            border: 2px solid var(--secondary);
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .form-control {
            border-radius: 0 12px 12px 0;
            border: 2px solid var(--secondary);
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.15rem rgba(18,154,125,0.25);
        }

        .btn-login {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 12px;
            color: white;
            font-weight: bold;
            width: 100%;
            margin-top: 5px;
            transition: 0.25s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(18,154,125,0.35);
        }

        .btn-outline-secondary {
            border-radius: 0 12px 12px 0;
            border: 2px solid var(--secondary);
            background: var(--info);
            color: var(--dark);
        }

        .btn-outline-secondary:hover {
            background: var(--secondary);
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            border-left: 5px solid;
        }

        .alert-danger {
            background: #FFECEF;
            border-left-color: var(--danger);
            color: #8a2b2b;
        }

        .alert-success {
            background: #E7FFF3;
            border-left-color: var(--primary);
            color: var(--dark);
        }

        /* Footer kecil */
        .footer-text {
            color: #777 !important;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">

            <!-- HEADER -->
            <div class="login-header">
                <i class="bi bi-shop" style="font-size: 3rem;"></i>
                <h2>Rumah Makan</h2>
                <p>Sistem Manajemen</p>
            </div>

            <!-- BODY -->
            <div class="login-body">

                <!-- ALERT ERROR -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-hide alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- ALERT SUCCESS -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-hide alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- FORM LOGIN -->
                <form action="config/auth_proses.php?action=login" method="POST">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control" name="username"
                                   placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="password"
                                   placeholder="Masukkan password" required id="password">
                            
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword()" id="toggleBtn">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>

                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Demo Login:<br>
                        Admin: <code>admin / admin123</code><br>
                        Karyawan: <code>karyawan / karyawan123</code>
                    </small>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-text">
            <small>&copy; <?php echo date('Y'); ?> Aplikasi Rumah Makan - Tugas PKL</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle show/hide password
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        // Auto hide alerts after 5 sec
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>
