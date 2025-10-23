<div class="dashboard-grid">
    <!-- Stats Overview -->
    <div class="stats-grid">
    <?php foreach ($cards as $c): ?>
        <div class="stat-card">
            <h3 class="stat-title"><?=htmlspecialchars($c['title'])?></h3>
            <p class="stat-value"><?=htmlspecialchars($c['value'])?></p>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- Recent Activity -->
    <div class="dashboard-section">
        <h2>Recent Activity</h2>
        <div class="activity-list">
            <div class="activity-placeholder">
                <p>Activity feed will be implemented in the next phase.</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <a href="<?= htmlspecialchars($baseUrl) ?>/staff/create" class="quick-action-btn">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                Add Staff
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/suppliers/create" class="quick-action-btn">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                New Supplier
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/tours/create" class="quick-action-btn">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Create Tour
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/visa/create" class="quick-action-btn">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                New Visa Application
            </a>
        </div>
    </div>

    <!-- System Health -->
    <div class="dashboard-section">
        <h2>System Health</h2>
        <div class="system-health">
            <div class="health-indicator">
                <span class="health-label">Database</span>
                <span class="health-status status-ok">OK</span>
            </div>
            <div class="health-indicator">
                <span class="health-label">Redis Cache</span>
                <span class="health-status status-ok">OK</span>
            </div>
            <div class="health-indicator">
                <span class="health-label">Storage</span>
                <span class="health-status status-ok">OK</span>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard specific styles */
.dashboard-grid {
    display: grid;
    gap: var(--spacing-lg);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--spacing-md);
}

.stat-card {
    background-color: var(--surface-color);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    box-shadow: 0 1px 3px var(--shadow-color);
}

.stat-title {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--spacing-xs);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text-primary);
}

.dashboard-section {
    background-color: var(--surface-color);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
    box-shadow: 0 1px 3px var(--shadow-color);
}

.dashboard-section h2 {
    margin-bottom: var(--spacing-lg);
    font-size: 1.25rem;
    color: var(--text-primary);
}

.activity-list {
    min-height: 200px;
}

.activity-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
    background-color: var(--bg-color);
    border-radius: var(--border-radius);
    color: var(--text-secondary);
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md);
    background-color: var(--bg-color);
    color: var(--text-primary);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: all 0.2s ease;
}

.quick-action-btn:hover {
    background-color: var(--primary-color);
    color: white;
}

.quick-action-btn svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

.system-health {
    display: grid;
    gap: var(--spacing-md);
}

.health-indicator {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-sm) var(--spacing-md);
    background-color: var(--bg-color);
    border-radius: var(--border-radius);
}

.health-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.875rem;
}

.status-ok {
    background-color: #dcfce7;
    color: #15803d;
}

.status-warning {
    background-color: #fef3c7;
    color: #92400e;
}

.status-error {
    background-color: #fee2e2;
    color: #b91c1c;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>
