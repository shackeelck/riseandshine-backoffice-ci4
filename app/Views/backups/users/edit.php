<div class="container mt-4">
    <h2>Edit User</h2>
    <form method="post" action="/users/update/<?= $user['id'] ?>">
        <div class="mb-3"><label>Username</label><input type="text" name="username" value="<?= esc($user['username']) ?>" class="form-control" required></div>
        <div class="mb-3"><label>Email</label><input type="email" name="email" value="<?= esc($user['email']) ?>" class="form-control" required></div>
        <div class="mb-3"><label>Role</label>
            <select name="role" class="form-control">
                <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="mb-3"><label>Status</label>
            <select name="status" class="form-control">
                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
    </form>
</div>
