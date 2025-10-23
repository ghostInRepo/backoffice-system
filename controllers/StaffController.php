<?php

class StaffController extends BaseController {
    public function index() {
        // list staff with pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare('SELECT * FROM staff ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $staff = $stmt->fetchAll();

        $this->view('staff/index.php', ['title' => 'Staff Management', 'staff' => $staff, 'page' => $page]);
    }
}
