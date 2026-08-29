<?php
// includes/auth_check.php

require_once __DIR__ . '/functions.php';

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "index.php?error=access_denied");
        exit;
    }
}
function requireSeller() {
    requireLogin();
    if (!isSeller()) { header("Location: " . BASE_URL . "seller.php"); exit; }
}
