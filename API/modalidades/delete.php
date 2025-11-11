<?php

require_once '../../connect.php';

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $sql = "DELETE FROM modalidades WHERE modalidade_id = {$id}";

    if ($con->query($sql) === TRUE) {
        header('Location: get-with-pagination.php?msg=deletado');
    } else {
        header('Location: get-with-pagination.php?msg=erro');
    }
} else {
    header('Location: get-with-pagination.php');
}

?>