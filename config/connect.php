<?php

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "banco_aulas";

$con = new mysqli($localhost, $username, $password, $dbname);

if ($con->connect_error) {
    die("connection failed : " . $con->connect_error);
}

?>