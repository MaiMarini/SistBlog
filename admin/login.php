<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Preencha todos os campos.';
    } elseif (login($username, $password)) {
        header('Location: ' . BASE_URL . 'admin/');
        exit;
    } else {
        $error = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f23;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: radial-gradient(ellipse at top, #1a1a3e 0%, #0f0f23 70%);
        }
        .login-box {
            background: #1a1a2e;
            padding: 40px;
            border-radius: 16px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            border: 1px solid #2a2a4a;
        }
        .login-box h1 {
            color: #fff;
            font-size: 24px;
            margin-bottom: 8px;
            text-align: center;
        }
        .login-box p.subtitle {
            color: #888;
            font-size: 14px;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #aaa;
            font-size: 13px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: #252545;
            border: 2px solid #3a3a6a;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            transition: border-color 0.3s;
            -webkit-appearance: none;
            appearance: none;
        }
        input::placeholder {
            color: #666;
        }
        input:focus {
            outline: none;
            border-color: #e94560;
            background: #2a2a50;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #e94560, #c23152);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(233, 69, 96, 0.4);
        }
        .error {
            background: rgba(244, 67, 54, 0.15);
            color: #ef9a9a;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        .logo-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-icon">📄</div>
        <h1>Presell Manager</h1>
        <p class="subtitle">Faça login para acessar o painel</p>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required autofocus
                       placeholder="Digite seu usuário"
                       value="<?= e($username ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required
                       placeholder="Digite sua senha">
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>
    </div>
</body>
</html>
