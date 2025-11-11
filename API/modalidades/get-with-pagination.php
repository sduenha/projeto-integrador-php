<?php

require_once '../../connect.php';

$por_pagina = 5;
$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$inicio = ($pagina_atual - 1) * $por_pagina;

$sql_total = "SELECT COUNT(*) as total FROM modalidades";
$resultado_total = $con->query($sql_total);
$row_total = $resultado_total->fetch_assoc();
$total_registros = $row_total['total'];
$total_paginas = ceil($total_registros / $por_pagina);

$sql = "SELECT * FROM modalidades LIMIT {$inicio}, {$por_pagina}";
$result = $con->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Modalidades</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style type="text/css">
        body {
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: #667eea;
            border: none;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../dashboard.php" class="btn btn-default">
            <span class="glyphicon glyphicon-arrow-left"></span> Voltar
        </a>
        <a href="create.php" class="btn btn-success">
            <span class="glyphicon glyphicon-plus"></span> Adicionar Modalidade
        </a>
        <hr>

        <div class="box">
            <h2>Lista de Modalidades</h2>
            
            <?php if ($result->num_rows > 0) { ?>
                <table class="table table-bordered table-striped">
                    <tr>
                        <td><strong>Nome</strong></td>
                        <td><strong>Descrição</strong></td>
                        <td width="100px"><strong>Editar</strong></td>
                        <td width="100px"><strong>Deletar</strong></td>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['nome']; ?></td>
                            <td><?php echo $row['descricao']; ?></td>
                            <td>
                                <a href="update.php?id=<?php echo $row['modalidade_id']; ?>" class="btn btn-info btn-sm">
                                    Editar
                                </a>
                            </td>
                            <td>
                                <form action="delete.php" method="POST" style="display:inline;">
                                    <input type="hidden" value="<?php echo $row['modalidade_id']; ?>" name="id">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza?')">
                                        Deletar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </table>

                <!-- Paginação -->
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_paginas; $i++) { ?>
                            <li <?php if ($i == $pagina_atual) echo 'class="active"'; ?>>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>

            <?php } else { ?>
                <div class="alert alert-info">Nenhuma modalidade cadastrada</div>
            <?php } ?>
        </div>
    </div>
</body>
</html>