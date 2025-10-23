<?php

class DashboardController extends BaseController {
    public function index() {
        // basic analytics placeholders
        $data = [
            'title' => 'Dashboard',
            'cards' => [
                ['title' => 'Total Bookings', 'value' => 123],
                ['title' => 'Revenue (M)', 'value' => '₱1.2M'],
                ['title' => 'Active Agents', 'value' => 12],
                ['title' => 'Pending Visas', 'value' => 4],
            ]
        ];
        $this->view('dashboard/index.php', $data);
    }
}
