<?php

class AdminController extends BaseController {
    public function index() {
        // require admin role
        require_once __DIR__ . '/../helpers/auth.php';
        require_login();
        require_role('admin');

        $this->view('admin/index.php', ['pageTitle' => 'Admin Panel']);
    }
}
