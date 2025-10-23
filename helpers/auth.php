<?php
// Auth helper
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        // set a flash message and redirect to login
        $_SESSION['flash_message'] = 'Please login to continue.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: /backoffice_system/public/login');
        exit;
    }

    // session timeout (in seconds) - configurable via $_ENV or default
    $timeout = !empty($_ENV['SESSION_TIMEOUT']) ? (int)$_ENV['SESSION_TIMEOUT'] : 1800;
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // expire session and require fresh login
        session_unset();
        session_destroy();
        // start a new session to set the flash message
        session_start();
        $_SESSION['flash_message'] = 'Session expired. Please login again.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: /backoffice_system/public/login');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * require_role accepts a string role or an array of allowed roles.
 */
function require_role($role) {
    if (is_array($role)) {
        if (empty($_SESSION['role']) || !in_array($_SESSION['role'], $role, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    } else {
        if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}
