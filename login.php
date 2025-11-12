<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start(); // Inicia a sessão
require_once 'config/connect.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Consulta preparada para evitar SQL Injection
        $stmt = $con->prepare("SELECT user_id, email, password, role FROM users WHERE email = ? AND ativo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['role'];

                if ($row['role'] === 'adm') {
                    header('Location: api/modalidades/get-with-pagination.php');
                } else {
                    header('Location: dashboard.php'); // Usuários normais vão para o dashboard
                }
                exit();
            } else {
                $erro = "Email ou senha inválidos!";
            }
        } else {
            $erro = "Email ou senha inválidos!";
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/login.css">
    <style>
        /* Estilo para a mensagem de erro, para ser exibida dentro do design */
        .alert-error {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-error svg {
            width: 20px;
            height: 20px;
            fill: #c33;
            flex-shrink: 0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="login-badge"> 
                <h3 class="title-h3">LOGIN</h3>
            </div>
        </div>
        
        <div class="right-section">
            <h2 class="title-h2">LOGIN</h2>

            <?php if (!empty($erro)) { ?>
                <div class="alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                    </svg>
                    <span><?php echo $erro; ?></span>
                </div>
            <?php } ?>
            
            <form method="POST">
                <div class="input-group">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="input-group">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <div class="forgot-password">
                    <a href="#">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-btn">LOGIN</button>
            </form>

            <div class="test-credentials" style="margin-top: 30px; text-align: center; color: #999; font-size: 12px;">
                <small>Teste com: <strong>admin@example.com</strong> / <strong>123456</strong> (Role: adm)</small><br>
                <small>Teste com: <strong>user@example.com</strong> / <strong>123456</strong> (Role: user)</small>
            </div>
        </div>
    </div>
</body>
</html>