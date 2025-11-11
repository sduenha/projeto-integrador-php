<?php

require_once '../../connect.php';

if ($_POST) {
    echo "<div class='alert alert-danger'>Por favor, preencha todos os campos</div>";
} else {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];

    $sql = "INSERT INTO modalidades(nome, descricao) VALUES('{$nome}', '{$descricao}')";

    if ($con->query($sql) === TRUE) {
        echo "<div class='alert alert-success'>Modalidade adicionada com sucesso</div>";
        header("Refresh:2; url=get-with-pagination.php");
    } else {
        echo "<div class='alert alert-danger'>Erro ao adicionar modalidade</div>";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Adicionar Modalidade</title>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style type="text/css">
        .box {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
        }
        body {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../dashboard.php" class="btn btn-default">
            <span class="glyphicon glyphicon-arrow-left"></span> Voltar
        </a>
        <hr>

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="box">
                    <h3><i class="glyphicon glyphicon-plus"></i> Adicionar Nova Modalidade</h3>
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