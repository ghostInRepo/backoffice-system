<?php if (!empty($flashMessage)): ?>
    <div class="alert alert-<?=htmlspecialchars($flashType ?? 'info')?>"><?=htmlspecialchars($flashMessage)?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if (!empty($message)): ?>
    <div class="alert alert-info"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<form method="post" action="<?= $this->config['app']['base_url'] ?>/login">
    <?= csrf_field() ?>
    <div>
        <label>Email</label>
        <input type="email" name="email" required />
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password" required />
    </div>
    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="<?= $this->config['app']['base_url'] ?>/register">Register</a></p>
