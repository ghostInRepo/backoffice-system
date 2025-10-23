<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> - Travel Agency Backoffice</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $this->config['app']['base_url'] ?>/assets/img/favicon.png">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= $this->config['app']['base_url'] ?>/assets/css/normalize.css">
    <link rel="stylesheet" href="<?= $this->config['app']['base_url'] ?>/assets/css/dashboard.css">
    
    <!-- Theme toggle script - run ASAP to prevent flash -->
    <script>
        (function() {
            // Check local storage or system preference
            const theme = localStorage.getItem('theme') || 
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    <!-- Top navigation -->
    <nav class="top-nav">
        <div class="nav-start">
            <button id="sidebar-toggle" aria-label="Toggle Sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </button>
            <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="nav-end">
            <button id="theme-toggle" aria-label="Toggle Theme">
                <svg class="theme-light" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 00-1.41 0 .996.996 0 000 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 00-1.41 0 .996.996 0 000 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 000-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 000-1.41.996.996 0 00-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 000-1.41.996.996 0 00-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
                </svg>
                <svg class="theme-dark" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 01-4.4 2.26 5.403 5.403 0 01-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/>
                </svg>
            </button>
            <div class="user-menu">
                <button class="user-button" aria-label="User menu" aria-haspopup="true">
                    <img src="<?= $this->config['app']['base_url'] ?>/assets/img/avatar-placeholder.png" alt="" class="user-avatar">
                    <span class="user-name"><?= htmlspecialchars($user['name'] ?? 'Guest') ?></span>
                </button>
                <div class="dropdown-menu" aria-label="User menu">
                    <a href="<?= $this->config['app']['base_url'] ?>/profile">Profile</a>
                    <a href="<?= $this->config['app']['base_url'] ?>/settings">Settings</a>
                    <hr>
                    <a href="<?= $this->config['app']['base_url'] ?>/logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?= $this->config['app']['base_url'] ?>/assets/img/logo.png" alt="Logo" class="logo">
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="<?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/dashboard">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li class="<?= $currentRoute === 'staff' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/staff">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                        Staff
                    </a>
                </li>
                <li class="<?= $currentRoute === 'suppliers' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/suppliers">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                        Suppliers
                    </a>
                </li>
                <li class="<?= $currentRoute === 'tours' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/tours">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/>
                        </svg>
                        Tours
                    </a>
                </li>
                <li class="<?= $currentRoute === 'marketing' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/marketing">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/>
                        </svg>
                        Marketing
                    </a>
                </li>
                <li class="<?= $currentRoute === 'finance' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/finance">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                        Finance
                    </a>
                </li>
                <li class="<?= $currentRoute === 'visa' ? 'active' : '' ?>">
                    <a href="<?= $this->config['app']['base_url'] ?>/visa">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                        Visa Services
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <?php if (!empty($flashMessage)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType ?? 'info') ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <!-- Core JS -->
    <script src="<?= $this->config['app']['base_url'] ?>/assets/js/dashboard.js"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>