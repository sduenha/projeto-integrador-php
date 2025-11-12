<?php
session_start();
require_once 'config/auth.php';
require_once 'config/connect.php';

requireAuth();

$user_email = $_SESSION['user_email'] ?? 'Usuário';
$user_role = $_SESSION['user_role'] ?? 'user';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: #f5f5f5;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1e3a8a;
            margin-bottom: 25px;
        }
        .btn-primary { background-color: #1e3a8a; border-color: #1e3a8a; }
        .btn-primary:hover { background-color: #152d6a; border-color: #152d6a; }
    </style>
</head>
<body>
    <div class="container">
        <ol class="breadcrumb">
            <li class="active">Home</li>
        </ol>

        <h2>Bem-vindo ao Dashboard, <?php echo htmlspecialchars($user_email); ?>!</h2>
        <p>Seu perfil: <strong><?php echo htmlspecialchars($user_role); ?></strong></p>
        
        <hr>

        <?php if ($user_role === 'adm') { ?>
            <h3>Painel Administrativo</h3>
            <p>Você tem acesso total às funcionalidades de administração.</p>
            <a href="api/modalidades/get-with-pagination.php" class="btn btn-primary">
                <span class="glyphicon glyphicon-list"></span> Gerenciar Modalidades
            </a>
            <!-- Adicione outros links de administração aqui -->
        <?php } else { ?>
            <h3>Área do Usuário</h3>
            <p>Seu acesso é limitado às funcionalidades do usuário padrão.</p>
            <!-- Adicione links para funcionalidades de usuário aqui -->
        <?php } ?>

        <hr>
        <a href="logout.php" class="btn btn-default">
            <span class="glyphicon glyphicon-log-out"></span> Sair
        </a>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>