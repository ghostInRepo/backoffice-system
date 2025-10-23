<?php
// CSRF helper
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type=\"hidden\" name=\"_csrf\" value=\"$t\">";
}

function verify_csrf() {
    $sent = $_POST['_csrf'] ?? '';
    if (!$sent || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        throw new RuntimeException('Invalid CSRF token');
    }
    return true;
}
