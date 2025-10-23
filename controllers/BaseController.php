<?php

class BaseController {
    protected $db;
    protected $config;

    public function __construct($db) {
        $this->db = $db;
        $this->config = require __DIR__ . '/../config/config.php';
    }

    protected function view($template, $data = []) {
        // Expose base URL and common vars to templates
        $baseUrl = $this->config['app']['base_url'] ?? '';
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
        $flashMessage = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
        $flashType = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : null;
        // Derive current route simple name (used for sidebar active state)
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = $this->config['app']['base_url'] ?? '';
        $path = '/' . trim(substr($uri, strlen($base)), '/');
        if ($path === '/' || $path === '') $path = '/dashboard';
        $currentRoute = trim($path, '/');

        // Page title compatibility
        $pageTitle = $data['pageTitle'] ?? $data['title'] ?? 'Backoffice';

        // Render the requested template into $content (templates can use $baseUrl, $user, $flashMessage)
        extract($data);
        $templatePath = __DIR__ . '/../views/' . $template;
        ob_start();
        if (file_exists($templatePath)) {
            require $templatePath;
        } else {
            echo "Template not found: " . htmlspecialchars($template);
        }
        $content = ob_get_clean();

        // Clear flash so it shows only once (after rendering template)
        if (isset($_SESSION['flash_message'])) unset($_SESSION['flash_message']);
        if (isset($_SESSION['flash_type'])) unset($_SESSION['flash_type']);

        // Select layout: use dashboard layout for dashboard views, keep legacy for others
        $useDashboardLayout = strpos($template, 'dashboard/') === 0;
        if ($useDashboardLayout && file_exists(__DIR__ . '/../views/layouts/dashboard.php')) {
            require __DIR__ . '/../views/layouts/dashboard.php';
        } else {
            require __DIR__ . '/../views/layout.php';
        }
    }

    protected function redirect($path) {
        header('Location: ' . $this->config['app']['base_url'] . $path);
        exit;
    }
}
