<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/auth.php';
require_once '../../config/connect.php';

requireAuth();
requireAdmin();

$limite_por_pagina = 5;
$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;

$modalidades = [];
$total_modalidades = 0;
$total_paginas = 1;
$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$sql_count = "SELECT COUNT(*) AS total FROM modalidades";
$result_count = $con->query($sql_count);
if ($result_count) {
    $total_modalidades = $result_count->fetch_assoc()['total'];
    $total_paginas = ceil($total_modalidades / $limite_por_pagina);
} else {
    $message = "Erro ao contar modalidades: " . $con->error;
    $message_type = "danger";
}

$sql = "SELECT * FROM modalidades ORDER BY modalidade_id DESC LIMIT {$limite_por_pagina} OFFSET {$offset}";
$result = $con->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $modalidades[] = $row;
    }
} else {
    $message = "Erro ao buscar modalidades: " . $con->error;
    $message_type = "danger";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Modalidades</title>
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
        .table > tbody > tr > td {
            vertical-align: middle;
        }
        .pagination {
            margin: 0;
        }
        .pagination > li > a, .pagination > li > span {
            color: #1e3a8a;
        }
        .pagination > .active > a, .pagination > .active > span {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
            color: white;
        }
        .btn-primary { background-color: #1e3a8a; border-color: #1e3a8a; }
        .btn-primary:hover { background-color: #152d6a; border-color: #152d6a; }
        .btn-info { background-color: #3b82f6; border-color: #3b82f6; }
        .btn-info:hover { background-color: #2e6eda; border-color: #2e6eda; }
        .btn-warning { background-color: #f0ad4e; border-color: #f0ad4e; }
        .btn-warning:hover { background-color: #ec971f; border-color: #ec971f; }
        .btn-danger { background-color: #d9534f; border-color: #d9534f; }
        .btn-danger:hover { background-color: #c9302c; border-color: #c9302c; }
    </style>
</head>
<body>
    <div class="container">
        <ol class="breadcrumb">
            <li><a href="../../dashboard.php">Home</a></li>
            <li class="active">Modalidades</li>
        </ol>

        <h2>Gerenciar Modalidades</h2>

        <?php if (!empty($message)) { ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <div class="well">
            <a href="create-form.php" class="btn btn-primary">
                <span class="glyphicon glyphicon-plus"></span> Adicionar Nova Modalidade
            </a>
        </div>

        <?php if (!empty($modalidades)) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modalidades as $modalidade) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($modalidade['modalidade_id']); ?></td>
                                <td><?php echo htmlspecialchars($modalidade['nome']); ?></td>
                                <td><?php echo htmlspecialchars($modalidade['descricao'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="update.php?id=<?php echo htmlspecialchars($modalidade['modalidade_id']); ?>" class="btn btn-warning btn-sm">
                                        <span class="glyphicon glyphicon-edit"></span> Editar
                                    </a>
                                    <a href="delete.php?id=<?php echo htmlspecialchars($modalidade['modalidade_id']); ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Tem certeza que deseja deletar esta modalidade?');">
                                        <span class="glyphicon glyphicon-trash"></span> Deletar
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                <ul class="pagination">
                    <?php if ($pagina_atual > 1) { ?>
                        <li><a href="?page=<?php echo $pagina_atual - 1; ?>">Anterior</a></li>
                    <?php } ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++) { ?>
                        <li class="<?php echo ($i === $pagina_atual) ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php } ?>

                    <?php if ($pagina_atual < $total_paginas) { ?>
                        <li><a href="?page=<?php echo $pagina_atual + 1; ?>">Próxima</a></li>
                    <?php } ?>
                </ul>
                <p>Mostrando <?php echo count($modalidades); ?> de <?php echo $total_modalidades; ?> registros.</p>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">
                Nenhuma modalidade encontrada. <a href="create-form.php">Crie uma nova</a>!
            </div>
        <?php } ?>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>