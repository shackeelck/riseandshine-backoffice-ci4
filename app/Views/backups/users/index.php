<div class="container mt-4">
   
    <div class="row g-1" >
        <div class="col-md-6"><h3 class="h6 bold">All Users</h3></div>
        <div class="col-md-6"><a href="/users/create" class="btn btn-success mb-3 float-end"><i class="fas fa-plus-circle"></i>  Add User</a></div>
    </div>
    
    <table class="table table-bordered">
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
        <?php foreach($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= esc($user['username']) ?></td>
                <td><?= esc($user['email']) ?></td>
                <td><?= esc($user['role']) ?></td>
                <td><?= esc($user['status']) ?></td>
                <td>
                    <a href="/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-pencil-square"></i>  Edit</a>
                    <a href="/users/delete/<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')"><i class="fas fa-trash"></i> Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
