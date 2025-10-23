<h2>Two-Factor Authentication</h2>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if (!empty($message)): ?>
<div class="alert alert-success"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<p>An OTP code was sent to your email. Enter it below.</p>
<form method="post" action="<?= $this->config['app']['base_url'] ?>/verify-2fa">
    <?= csrf_field() ?>
    <div>
        <label>OTP Code</label>
        <input type="text" name="otp" required />
    </div>
    <button type="submit">Verify</button>
</form>

<form method="post" action="<?= $this->config['app']['base_url'] ?>/resend-otp" style="margin-top:1rem;">
    <?= csrf_field() ?>
    <button type="submit">Resend OTP</button>
    <?php
    $attempts = $_SESSION['otp_attempts'] ?? 0;
    $maxAttempts = 5;
    $remaining = max(0, $maxAttempts - $attempts);
    ?>
    <div class="muted">Attempts remaining: <?= $remaining ?></div>
</form>
