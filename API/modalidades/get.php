<?php

session_start();
require_once '../../config/auth.php';
requireAuth();
requireAdmin();

require_once '../../config/connect.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $con->prepare("SELECT modalidade_id, nome, descricao, ativo FROM modalidades WHERE modalidade_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'message' => 'Modalidade não encontrada']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID de modalidade inválido']);
}

?>