<?php
// Layout template
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=isset($title)?htmlspecialchars($title):'Backoffice'?></title>
    <link rel="stylesheet" href="<?= $this->config['app']['base_url'] ?>/assets/css/app.css">
</head>
<body class="theme-light">
    <aside class="sidebar">
        <div class="brand">Travel Backoffice</div>
        <nav>
            <a href="<?=$this->config['app']['base_url']?>/dashboard">Dashboard</a>
            <a href="<?=$this->config['app']['base_url']?>/staff">Staff</a>
            <a href="#">Suppliers</a>
            <a href="#">Tours</a>
            <a href="#">Marketing</a>
            <a href="#">Finance</a>
            <a href="#">Visa</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="search">Search...</div>
            <div class="actions">
                <button id="theme-toggle">Toggle</button>
                <a href="<?=$this->config['app']['base_url']?>/logout">Logout</a>
            </div>
        </header>
        <section class="content">
            <?php require __DIR__ . '/' . $template; ?>
        </section>
    </main>

    <script src="<?= $this->config['app']['base_url'] ?>/assets/js/app.js"></script>
</body>
</html>
