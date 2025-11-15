<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Se já estiver logado, redirecionar
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

$conn = conectarBanco();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pegar valores SEM sanitizar primeiro (para debug)
    $email_raw = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha_raw = isset($_POST['senha']) ? $_POST['senha'] : '';
    
    if (empty($email_raw) || empty($senha_raw)) {
        $erro = 'Por favor, preencha todos os campos';
    } else {
        // Agora sanitizar apenas o email (senha nunca sanitiza)
        $email = sanitizarDados($conn, $email_raw);
        $senha = $senha_raw; // NUNCA sanitizar senha!
        
        if (fazerLogin($email, $senha)) {
            header('Location: index.php');
            exit;
        } else {
            $erro = 'Email ou senha incorretos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão de Aulas</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .login-header p {
            opacity: 0.95;
            font-size: 1rem;
        }

        .login-body {
            padding: 40px 30px;
        }

        .login-form .form-group {
            margin-bottom: 25px;
        }

        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-text);
        }

        .login-form input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .erro-login {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger-color);
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .login-footer {
            background: var(--light-bg);
            padding: 20px 30px;
            text-align: center;
            color: var(--gray-text);
            font-size: 0.9rem;
        }

        .credenciais-teste {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
        }

        .credenciais-teste h4 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .credenciais-teste code {
            display: block;
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            color: #1f2937;
        }

        .helper-links {
            margin-top: 20px;
            padding: 15px;
            background: #fef3c7;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
        }

        .helper-links h4 {
            color: #92400e;
            margin-bottom: 10px;
        }

        .helper-links a {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 8px 16px;
            background: #f59e0b;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .helper-links a:hover {
            background: #d97706;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Login</h1>
            <p>Sistema de Gestão de Aulas</p>
        </div>

        <div class="login-body">
            <?php if ($erro): ?>
                <div class="erro-login">
                    ❌ <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="email">📧 Email</label>
                    <input type="email" id="email" name="email" required autofocus 
                           placeholder="admin@sistema.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="senha">🔒 Senha</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="admin123">
                </div>

                <button type="submit" class="btn-login">
                    Entrar no Sistema
                </button>
            </form>

            <div class="helper-links">
                <h4>⚠️ Problemas para fazer login?</h4>
                <a href="criar-admin.php" target="_blank">🔧 Criar/Resetar Admin</a>
                <a href="test-password.php" target="_blank">🧪 Testar Senhas</a>
            </div>

            <div class="credenciais-teste">
                <h4>🔑 Credenciais Padrão:</h4>
                <strong>Email:</strong>
                <code>admin@sistema.com</code>
                <strong>Senha:</strong>
                <code>admin123</code>
            </div>
        </div>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Gestão de Aulas</p>
        </div>
    </div>
</body>
</html>