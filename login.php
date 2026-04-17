<?php
session_start();
include 'config.php';

$backgroundImage = file_exists(__DIR__ . '/church-background.jpg')
    ? 'church-background.jpg'
    : 'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1600&q=80';

$logoImage = file_exists(__DIR__ . '/logo.png') ? 'logo.png' : '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, 'SELECT * FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && $password === $user['password']) {
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treasurer Panel Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                linear-gradient(rgba(9, 20, 38, 0.64), rgba(9, 20, 38, 0.72)),
                url('<?php echo htmlspecialchars($backgroundImage); ?>') center/cover no-repeat;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        .page-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .church-header {
            width: 100%;
            text-align: center;
            padding: clamp(1.6rem, 4vw, 2.8rem) 1rem 1.25rem;
            background: linear-gradient(180deg, rgba(10, 22, 42, 0.68), rgba(10, 22, 42, 0.18));
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .church-header h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 700;
            line-height: 1.24;
            font-family: 'Cinzel', serif;
            letter-spacing: 1px;
            text-shadow: 0 4px 16px rgba(0, 0, 0, 0.42);
        }

        .church-header p {
            margin: 0.6rem 0 0;
            font-size: clamp(0.95rem, 1.4vw, 1.15rem);
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #eaf1ff;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        .login-wrapper {
            width: 100%;
            max-width: 470px;
            padding: 2.2rem 1rem 2rem;
        }

        .login-card {
            border: none;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.95);
            color: #1c2a4d;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
        }

        .login-logo {
            width: 86px;
            height: 86px;
            object-fit: contain;
            margin: 0 auto 0.75rem auto;
            display: block;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            padding: 0.35rem;
        }

        .login-title {
            font-weight: 700;
            color: #21386b;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.7rem 0.85rem;
        }

        .btn-login {
            border-radius: 10px;
            padding: 0.65rem;
            font-weight: 600;
            background-color: #2c58a0;
            border-color: #2c58a0;
        }

        .btn-login:hover {
            background-color: #234883;
            border-color: #234883;
        }

        .logout-note {
            font-size: 0.85rem;
            color: #4e5f85;
            text-align: center;
            margin-top: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <div class="church-header">
            <h1>Seventh-day Adventist Central Telugu Church<br>Chamarajpet, Bangalore</h1>
            <p>Treasurer Panel</p>
        </div>

        <div class="login-wrapper">
            <div class="card p-4 login-card">
                <?php if ($logoImage !== '') : ?>
                    <img src="<?php echo htmlspecialchars($logoImage); ?>" alt="Church Logo" class="login-logo">
                <?php endif; ?>
                <h3 class="text-center mb-3 login-title">Treasurer Login</h3>

                <?php if (isset($error)) : ?>
                    <p class="text-danger text-center"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <form method="POST">
                    <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <button name="login" class="btn btn-primary btn-login w-100">Login</button>
                </form>

                <p class="logout-note">You can log out anytime from your dashboard.</p>
            </div>
        </div>
    </div>
</body>
</html>


