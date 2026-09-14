<?php
// includes/auth_check.php

require_once __DIR__ . '/functions.php';

function recordUnauthorizedAccess(string $requiredRole): void {
    try { require_once __DIR__.'/../config/database.php'; require_once __DIR__.'/security_monitor.php'; $db=(new Database())->getConnection(); if($db)recordSecurityEvent($db,'access.denied',25,isLoggedIn()?(int)$_SESSION['user_id']:null,['required_role'=>$requiredRole],'denied'); } catch(Throwable $ignored) {}
}

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
        recordUnauthorizedAccess('admin');
        header("Location: " . BASE_URL . "index.php?error=access_denied");
        exit;
    }
}
function requireSeller() {
    requireLogin();
    if (!isSeller()) { recordUnauthorizedAccess('seller'); header("Location: " . BASE_URL . "seller-register.php?error=separate_account"); exit; }
}
function requireBuyer() {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'customer') { recordUnauthorizedAccess('customer'); header("Location: " . BASE_URL . "index.php?error=buyer_account_required"); exit; }
}
