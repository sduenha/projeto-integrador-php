<?php

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'adm';
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: access-denied.php');
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

?>