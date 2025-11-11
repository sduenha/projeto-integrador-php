<?php

session_start();
require_once 'connect.php';

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '{$email}' AND ativo = 1";
    $result = $con->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_email'] = $row['email'];
            header('Location: dashboard.php');
            exit();
        } else {
            $erro = "Email ou senha inválidos!";
        }
    } else {
        $erro = "Email ou senha inválidos!";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style type="text/css">
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .btn {
            border-radius: 4px;
            font-weight: bold;
        }
        .erro {
            color: #d9534f;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Sistema de Gestão de Aulas</h2>
        
        <?php if (isset($erro)) { ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php } ?>

        <form method="POST">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>

        <hr>
        <p style="text-align: center; color: #666;">
            <small>Teste com: admin@example.com / 123456</small>
        </p>
    </div>
</body>
</html>