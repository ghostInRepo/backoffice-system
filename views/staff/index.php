<h1><?=htmlspecialchars($title)?></h1>
<a href="<?=$this->config['app']['base_url']?>/staff/create">Add Staff</a>
<table>
    <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Contact</th><th>Email</th></tr></thead>
    <tbody>
        <?php foreach ($staff as $s): ?>
            <tr>
                <td><?=htmlspecialchars($s['id'])?></td>
                <td><?=htmlspecialchars($s['name'])?></td>
                <td><?=htmlspecialchars($s['role'])?></td>
                <td><?=htmlspecialchars($s['contact'])?></td>
                <td><?=htmlspecialchars($s['email'])?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
