<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/auth.php';
require_once '../../config/connect.php';

requireAuth();
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

    if (empty($nome)) {
        $message = 'Nome da modalidade é obrigatório.';
        $message_type = 'danger';
    } else {
        $sql = "INSERT INTO modalidades (nome, descricao, ativo) VALUES (?, ?, 1)";
        $stmt = $con->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('ss', $nome, $descricao);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Modalidade criada com sucesso!';
                $_SESSION['message_type'] = 'success';
                header('Location: get-with-pagination.php');
                exit();
            } else {
                $message = 'Erro ao criar modalidade: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Erro na preparação da query: ' . $con->error;
            $message_type = 'danger';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Modalidade</title>
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
        h3 {
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
            <li><a href="../../dashboard.php">Home</a></li>
            <li><a href="get-with-pagination.php">Modalidades</a></li>
            <li class="active">Criar</li>
        </ol>

        <a href="get-with-pagination.php" class="btn btn-default" style="margin-bottom: 20px;">
            <span class="glyphicon glyphicon-arrow-left"></span> Voltar para Modalidades
        </a>

        <h3><i class="glyphicon glyphicon-plus-sign"></i> Criar Nova Modalidade</h3>

        <?php if (!empty($message)) { ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-6">
                <form method="POST">
                    <div class="form-group">
                        <label for="nome">Nome da Modalidade:</label>
                        <input type="text" id="nome" name="nome" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição:</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="glyphicon glyphicon-save"></span> Salvar Modalidade
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>