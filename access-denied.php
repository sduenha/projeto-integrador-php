<?php
session_start();
require_once 'config/auth.php';
requireAuth();

if (isAdmin()) {
    header('Location: api/modalidades/get-with-pagination.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; padding: 20px; font-family: 'Poppins', sans-serif; }
        .container { text-align: center; margin-top: 50px; }
        .error-box { 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            max-width: 600px;
            margin: 0 auto;
        }
        h1 { color: #d9534f; margin-bottom: 20px; }
        p { color: #555; font-size: 16px; margin-bottom: 30px;}
        .btn-primary { background-color: #1e3a8a; border-color: #1e3a8a; }
        .btn-primary:hover { background-color: #3b82f6; border-color: #3b82f6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-box">
            <h1><span class="glyphicon glyphicon-remove-circle"></span> Acesso Negado</h1>
            <p>Você não possui as permissões necessárias para acessar esta página.</p>
            <a href="dashboard.php" class="btn btn-primary">Voltar para o Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
</body>
</html>