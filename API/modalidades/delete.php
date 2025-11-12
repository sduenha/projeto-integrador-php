<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/auth.php';
require_once '../../config/connect.php';

requireAuth();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($id <= 0) {
        $_SESSION['message'] = 'ID de modalidade inválido.';
        $_SESSION['message_type'] = 'danger';
        header('Location: get-with-pagination.php');
        exit();
    }

    $sql = "DELETE FROM modalidades WHERE modalidade_id = ?";
    $stmt = $con->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Modalidade deletada com sucesso!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Erro ao deletar modalidade: ' . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Erro na preparação da query: ' . $con->error;
        $_SESSION['message_type'] = 'danger';
    }
} else {
    $_SESSION['message'] = 'Requisição inválida para deletar modalidade.';
    $_SESSION['message_type'] = 'danger';
}

header('Location: get-with-pagination.php');
exit();

?>