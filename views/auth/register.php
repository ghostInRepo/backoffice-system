<h2>Register</h2>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<form method="post" action="<?= $this->config['app']['base_url'] ?>/register">
    <?= csrf_field() ?>
    <div>
        <label>Name</label>
        <input type="text" name="name" required />
    </div>
    <div>
        <label>Email</label>
        <input type="email" name="email" required />
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password" required />
    </div>
    <div>
        <label>Role</label>
        <select name="role">
            <option value="agent">Agent</option>
            <option value="staff">Staff</option>
        </select>
    </div>
    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="<?= $this->config['app']['base_url'] ?>/login">Login</a></p>
