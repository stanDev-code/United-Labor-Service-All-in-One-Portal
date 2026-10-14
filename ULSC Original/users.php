<?php
/**
 * Manage Admin Accounts (Admin only)
 */
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Manage Admins';

if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id !== getCurrentUserId()) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        header('Location: users.php?msg=deleted');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = 'admin'; // Force admin role only
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($full_name)) {
        if ($id) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, is_active=?, password=? WHERE id=?")->execute([$username, $full_name, $email, $role, $is_active, $hash, $id]);
            } else {
                $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, is_active=? WHERE id=?")->execute([$username, $full_name, $email, $role, $is_active, $id]);
            }
        } else {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?,?,?,?,?,?)")->execute([$username, $hash, $full_name, $email, $role, $is_active]);
            }
        }
        header('Location: users.php?msg=saved');
        exit;
    }
}

$sort_by = $_GET['sort'] ?? 'username';
$sort_order = $_GET['order'] ?? 'desc';

/* Validate sort parameters */
$allowed_sorts = ['username', 'full_name', 'email', 'role', 'is_active'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'username';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'asc';

$users = $pdo->query("SELECT id, username, full_name, email, role, is_active FROM users ORDER BY $sort_by $sort_order")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><?= $_GET['msg'] === 'saved' ? 'User saved.' : 'User deleted.' ?></div><?php endif; ?>

<div class="search-container" style="margin-bottom: 24px;">
    <form method="GET" class="search-filters-row">
        <div class="search-filter-item" style="flex: 1; min-width: 280px;">
            <label class="search-filter-label">Search Users</label>
            <div class="search-input-wrapper">
                <div class="search-icon-wrapper">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" class="modern-search-input" placeholder="Search by username, name, or email..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
        </div>
        
        <div class="search-filter-item">
            <button type="submit" class="modern-btn-primary">
                <i class="bi bi-search me-2"></i>Search Users
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()"><i class="bi bi-plus-lg"></i> Add User</button>
</div>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?sort=username&order=<?= $sort_by === 'username' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Username <?= $sort_by === 'username' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=full_name&order=<?= $sort_by === 'full_name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Full Name <?= $sort_by === 'full_name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=email&order=<?= $sort_by === 'email' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Email <?= $sort_by === 'email' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=role&order=<?= $sort_by === 'role' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Role <?= $sort_by === 'role' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=is_active&order=<?= $sort_by === 'is_active' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Active <?= $sort_by === 'is_active' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                <td><span class="badge bg-danger">Admin</span></td>
                <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
                <td>
                    <!-- Edit Button - Primary 3D effect with text -->
<button class="btn btn-sm btn-primary" 
        onclick='openUserModal(<?= json_encode($u) ?>)'
        title="Edit User: <?= htmlspecialchars($u['username'] ?? $u['name'] ?? 'User') ?>"
        style="box-shadow: 0 4px 0 #0b5ed7; transform: translateY(0); transition: all 0.1s; border: none;"
        onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #0b5ed7'"
        onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'"
        onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'">
    <i class="bi bi-pencil me-1"></i> Edit
</button>

<?php if ($u['id'] != getCurrentUserId()): ?>
<!-- Delete Button - Danger 3D effect with text (only for other users) -->
<button class="btn btn-sm btn-danger" 
        onclick="showDeleteConfirm('User: <?= htmlspecialchars($u['username'] ?? $u['name'] ?? 'User') ?>', 'Role: <?= htmlspecialchars($u['role'] ?? 'User') ?>', 'users.php?delete=<?= $u['id'] ?>')"
        title="Delete User: <?= htmlspecialchars($u['username'] ?? $u['name'] ?? 'User') ?>"
        style="box-shadow: 0 4px 0 #a13d3d; transform: translateY(0); transition: all 0.1s; border: none;"
        onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #a13d3d'"
        onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'"
        onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'">
    <i class="bi bi-trash me-1"></i> Delete
</button>
<?php endif; ?>

                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="uid">
                <div class="modal-header"><h5 class="modal-title">Add / Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Username *</label><input type="text" class="form-control" name="username" id="uusername" required></div>
                    <div class="mb-2"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="full_name" id="ufullname" required></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="uemail"></div>
                    <div class="mb-2"><label class="form-label">Password <small class="text-muted">(leave blank to keep)</small></label><input type="password" class="form-control" name="password" id="upass"></div>
                    <div class="mb-2"><label class="form-label">Role</label><select class="form-select" name="role" id="urole" disabled><option value="admin">Admin</option></select></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="uactive" value="1" checked><label class="form-check-label" for="uactive">Active</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function openUserModal(row) {
    const modal = document.getElementById('userModal');
    if (row) {
        document.getElementById('uid').value = row.id;
        document.getElementById('uusername').value = row.username || '';
        document.getElementById('ufullname').value = row.full_name || '';
        document.getElementById('uemail').value = row.email || '';
        document.getElementById('upass').value = '';
        document.getElementById('upass').placeholder = 'Leave blank to keep';
        document.getElementById('upass').removeAttribute('required');
        document.getElementById('urole').value = 'admin';
        document.getElementById('uactive').checked = row.is_active == 1 || row.is_active === '1';
    } else {
        document.getElementById('uid').value = '';
        modal.querySelector('form').reset();
        document.getElementById('uid').value = '';
        document.getElementById('upass').placeholder = '';
        document.getElementById('upass').setAttribute('required', 'required');
    }
    bootstrap.Modal.getOrCreateInstance(modal).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
