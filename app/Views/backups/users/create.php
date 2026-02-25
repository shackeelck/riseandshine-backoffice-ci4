<div class="container mt-5" style="max-width: 600px;">
    <h3 class="mb-4">Add New User</h3>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <form action="/users/store" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" value="<?= old('username') ?>" class="form-control <?= ($validation->hasError('username')) ? 'is-invalid' : '' ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('username') ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" value="<?= old('email') ?>" class="form-control <?= ($validation->hasError('email')) ? 'is-invalid' : '' ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('email') ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="role" class="form-label">User Role</label>
            <select name="role" class="form-select <?= ($validation->hasError('role')) ? 'is-invalid' : '' ?>">
                <option value="">Select role</option>
                <option value="admin" <?= old('role') == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="staff" <?= old('role') == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="delivery" <?= old('role') == 'delivery' ? 'selected' : '' ?>>Delivery Person</option>
            </select>
            <div class="invalid-feedback">
                <?= $validation->getError('role') ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control <?= ($validation->hasError('password')) ? 'is-invalid' : '' ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('password') ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control <?= ($validation->hasError('confirm_password')) ? 'is-invalid' : '' ?>">
            <div class="invalid-feedback">
                <?= $validation->getError('confirm_password') ?>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i> Save User</button>
    </form>
</div>


<?php /*?><div class="container mt-4">
    <h2>Add New User</h2>
    <form method="post" action="/users/create">
        <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" ></div>
        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="mb-3"><label>Role</label>
            <select name="role" class="form-control">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="mb-3"><label>Status</label>
            <select name="status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Create User</button>
    </form>
</div>
<?php */?>