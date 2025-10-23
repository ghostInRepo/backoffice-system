<?php if (!empty($flashMessage)): ?>
    <div class="alert alert-<?=htmlspecialchars($flashType ?? 'info')?>"><?=htmlspecialchars($flashMessage)?></div>
<?php endif; ?>

<h1>Admin Panel</h1>
<p>Only administrators should see this.</p>
