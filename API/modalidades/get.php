<?php

require_once '../../connect.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $sql = "SELECT * FROM modalidades WHERE modalidade_id = {$id}";
    $result = $con->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(['erro' => 'Modalidade não encontrada']);
    }
} else {
    echo json_encode(['erro' => 'ID inválido']);
}

?>