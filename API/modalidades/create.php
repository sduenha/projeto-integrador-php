<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/auth.php';
require_once '../../config/connect.php';

requireAuth();
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

    if (empty($nome)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nome é obrigatório']);
        exit();
    }

    $sql = "INSERT INTO modalidades (nome, descricao, ativo) VALUES (?, ?, 1)";
    $stmt = $con->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na query: ' . $con->error]);
        exit();
    }

    $stmt->bind_param('ss', $nome, $descricao);
    
    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Modalidade criada com sucesso', 'id' => $con->insert_id]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Adicionar Modalidade</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style type="text/css">
        body {
            padding: 20px;
            background: #f5f5f5;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h3 {
            color: #1e3a8a;
            margin-bottom: 25px;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-default {
            color: #333;
            background-color: #fff;
            border-color: #ccc;
        }
        .btn-default:hover {
            color: #333;
            background-color: #e6e6e6;
            border-color: #adadad;
        }
        .alert {
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <ol class="breadcrumb">
            <li><a href="../../dashboard.php">Home</a></li>
            <li><a href="get-with-pagination.php">Modalidades</a></li>
            <li class="active">Adicionar</li>
        </ol>

        <a href="get-with-pagination.php" class="btn btn-default">
            <span class="glyphicon glyphicon-arrow-left"></span> Voltar para a lista
        </a>
        <hr>

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="box">
                    <h3><i class="glyphicon glyphicon-plus"></i> Adicionar Nova Modalidade</h3>
                    
                    <?php if (!empty($message)) { ?>
                        <div class="alert alert-<?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php } ?>

                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="nome">Nome da Modalidade:</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="descricao">Descrição:</label>
                            <textarea rows="4" name="descricao" class="form-control" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Adicionar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>