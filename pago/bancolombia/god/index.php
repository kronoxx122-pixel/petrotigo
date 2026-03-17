<?php
require_once '../security.php';
?><?php
// god/index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config_admin.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Credenciales incorrectas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>God Mode - Login</title>
    <style>
        body {
            background-color: #0f172a;
            color: #fff;
            font-family: sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: #1e293b;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 320px;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: #38bdf8;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 4px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #fff;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #38bdf8;
            color: #0f172a;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #0ea5e9;
        }

        .error {
            color: #ef4444;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h1>GOD MODE</h1>
        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>

</html>
