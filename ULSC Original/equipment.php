<?php
/**
 * equipment & vehicles module
 * Admin: full CRUD. Staff: view only
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Equipment';
$is_admin = isAdmin();

/* Handle delete (admin only) */
if ($is_admin && isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare("DELETE FROM equipment WHERE id = ?")->execute([$id]);
    header('Location: equipment.php?msg=deleted');
    exit;
}

/* Handle add/edit (admin only) */
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    
    /* Auto-generate ID for new equipment */
    if (!$id) {
        /* Get the next available ID to avoid duplicates */
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(item_id, 3) AS UNSIGNED)) as max_id FROM equipment WHERE item_id LIKE 'EQ%'");
        $stmt->execute();
        $last_id = $stmt->fetch()['max_id'] ?? 0;
        $item_id = 'EQ' . str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);
        
        /* Double-check for uniqueness */
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM equipment WHERE item_id = ?");
        $check_stmt->execute([$item_id]);
        if ($check_stmt->fetch()['count'] > 0) {
            /* If still duplicate, find next available */
            for ($i = $last_id + 2; $i <= $last_id + 100; $i++) {
                $test_id = 'EQ' . str_pad($i, 4, '0', STR_PAD_LEFT);
                $check_stmt->execute([$test_id]);
                if ($check_stmt->fetch()['count'] == 0) {
                    $item_id = $test_id;
                    break;
                }
            }
        }
    } else {
        $item_id = trim($_POST['item_id'] ?? '');
    }
    
    $name = trim($_POST['name'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $facilitated_by = trim($_POST['facilitated_by'] ?? '');
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $status = $_POST['status'] ?? 'Operational';
    $notes = trim($_POST['notes'] ?? '');
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (!in_array($status, ['Operational', 'Under Repair', 'Out of Service'])) $errors[] = 'Invalid status';

    if (empty($errors)) {
        if ($id) {
            /* Check for duplicate item_id during update (excluding current record) */
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM equipment WHERE item_id = ? AND id != ?");
            $check_stmt->execute([$item_id, $id]);
            if ($check_stmt->fetch()['count'] > 0) {
                $errors[] = 'Item ID already exists. Please use a different ID.';
            } else {
                try {
                    /* Get current item_id for this record */
                    $current_stmt = $pdo->prepare("SELECT item_id FROM equipment WHERE id = ?");
                    $current_stmt->execute([$id]);
                    $current_item_id = $current_stmt->fetch()['item_id'] ?? '';
                    
                    $stmt = $pdo->prepare("UPDATE equipment SET name=?, model=?, facilitated_by=?, purchase_date=?, status=?, notes=?, quantity=? WHERE id=?");
                    $stmt->execute([$name, $model, $facilitated_by, $purchase_date ?: null, $status, $notes, $quantity, $id]);
                    
                    if ($stmt->rowCount() === 0) {
                        $errors[] = 'No changes were saved. Please try again.';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Update failed: ' . $e->getMessage();
                }
            }
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO equipment (item_id, name, model, category, facilitated_by, purchase_date, status, notes, quantity) VALUES (?,?,?,'Equipment',?,?,?,?,?)");
                $stmt->execute([$item_id, $name, $model, $facilitated_by, $purchase_date ?: null, $status, $notes, $quantity]);
                header('Location: equipment.php?msg=saved');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    /* Duplicate entry error */
                    $errors[] = 'Item ID already exists. Please use a different ID or let the system generate one.';
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
        
        if (empty($errors)) {
            header('Location: equipment.php?msg=saved');
            exit;
        }
    } else {
        $error_msg = implode(', ', $errors);
    }
}

$search = trim($_GET['search'] ?? '');
$filter_cat = $_GET['category'] ?? '';
$filter_status = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'item_id';
$sort_order = $_GET['order'] ?? 'asc';

// validate sort parameters
$allowed_sorts = ['id', 'name', 'model', 'operator_name', 'purchase_date', 'status'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'name';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'asc';

$sql = "SELECT e.*, u.full_name as operator_name FROM equipment e LEFT JOIN users u ON u.id = e.assigned_operator_id WHERE (e.category = 'Equipment' OR e.category IS NULL)";
$params = [];
if ($search) {
    $sql .= " AND (e.item_id LIKE ? OR e.name LIKE ? OR e.model LIKE ?)";
    $q = "%$search%";
    $params = array_merge($params, [$q, $q, $q]);
}
if ($filter_status) {
    $sql .= " AND e.status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY e.$sort_by $sort_order";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// staff list for dropdown
$staff = $pdo->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= $_GET['msg'] === 'saved' ? 'Record saved.' : 'Record deleted.' ?></div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<div class="search-container" style="margin-bottom: 24px;">
    <form method="GET" class="search-filters-row">
        <div class="search-filter-item" style="flex: 1; min-width: 280px;">
            <label class="search-filter-label">Search Equipment</label>
            <div class="search-input-wrapper">
                <div class="search-icon-wrapper">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" class="modern-search-input" placeholder="Search by name, model, or ID..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        
        <div class="search-filter-item">
            <label class="search-filter-label">Status</label>
            <select name="status" class="modern-select" style="min-width: 150px;">
                <option value="">All Statuses</option>
                <option value="Operational" <?= $filter_status === 'Operational' ? 'selected' : '' ?>>Operational</option>
                <option value="Under Repair" <?= $filter_status === 'Under Repair' ? 'selected' : '' ?>>Under Repair</option>
                <option value="Out of Service" <?= $filter_status === 'Out of Service' ? 'selected' : '' ?>>Out of Service</option>
            </select>
        </div>
        
        <div class="search-filter-item">
            <button type="submit" class="modern-btn-primary">
                <i class="bi bi-search me-2"></i>Search Equipment
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end mb-3">
    <?php if ($is_admin): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#equipModal" onclick="openEquipModal()">
        <i class="bi bi-plus-lg"></i> Add Item
    </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?sort=id&order=<?= $sort_by === 'id' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">ID <?= $sort_by === 'id' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=name&order=<?= $sort_by === 'name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Name / Model <?= $sort_by === 'name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Operator</th>
                <th><a href="?sort=purchase_date&order=<?= $sort_by === 'purchase_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Purchase Date <?= $sort_by === 'purchase_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=status&order=<?= $sort_by === 'status' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Status <?= $sort_by === 'status' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Qty</th>
                <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
            <tr><td colspan="<?= $is_admin ? 7 : 6 ?>" class="text-muted">No equipments yet</td></tr>
            <?php else: ?>
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['item_id']) ?></td>
                    <td><?= htmlspecialchars($i['name']) ?><?= $i['model'] ? ' <small class="text-muted">(' . htmlspecialchars($i['model']) . ')</small>' : '' ?></td>
                    <td><?= htmlspecialchars($i['facilitated_by'] ?? $i['operator_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($i['purchase_date'] ?? '-') ?></td>
                    <td>
                        <?php
                        $s = $i['status'];
                        $b = match($s) { 'Operational' => 'badge-operational', 'Under Repair' => 'badge-repair', default => 'badge-out' };
                        ?>
                        <span class="badge <?= $b ?>"><?= htmlspecialchars($s) ?></span>
                    </td>
                    <td><?= (int)($i['quantity'] ?? 1) ?></td>
                    <?php if ($is_admin): ?>
                    <td>
                        <!-- edit button - primary 3d effect with text -->
                        <?php if ($i['status'] === 'Out of Service'): ?>
                        <button class="btn btn-sm btn-secondary" 
                                disabled
                                title="Edit not allowed - Equipment is out of service"
                                style="box-shadow: 0 4px 0 #6c757d; transform: translateY(0); transition: all 0.1s; border: none; opacity: 0.6;">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-primary" 
                                onclick="openEquipModal(JSON.parse(atob('<?= base64_encode(json_encode($i)) ?>')))"
                                title="Edit <?= htmlspecialchars($i['name'] ?? 'Equipment') ?>"
                                style="box-shadow: 0 4px 0 #0b5ed7; transform: translateY(0); transition: all 0.1s; border: none;"
                                onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #0b5ed7'"
                                onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'"
                                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <?php endif; ?>

                        <!-- delete button - danger 3d effect with text -->
                        <button class="btn btn-sm btn-danger" 
                                onclick="showDeleteConfirm('<?= htmlspecialchars($i['name'] ?? 'Equipment') ?>', '<?= htmlspecialchars($i['model'] ?? '') ?>', 'equipment.php?delete=<?= $i['id'] ?>')"
                                title="Delete <?= htmlspecialchars($i['name'] ?? 'Equipment') ?>"
                                style="box-shadow: 0 4px 0 #a13d3d; transform: translateY(0); transition: all 0.1s; border: none;"
                                onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #a13d3d'"
                                onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'"
                                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($is_admin): ?>
<!-- modal -->
<div class="modal fade" id="equipModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="eid">
                <div class="modal-header">
                    <h5 class="modal-title">Equipment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                                                <div class="col-md-6">
                            <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="ename" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Model</label>
                            <input type="text" class="form-control" name="model" id="emodel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Facilitated By</label>
                            <input type="text" class="form-control" name="facilitated_by" id="efacilitated" placeholder="Enter name...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" id="epurchase">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="estatus">
                                <option value="Operational">Operational</option>
                                <option value="Under Repair">Under Repair</option>
                                <option value="Out of Service">Out of Service</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="equantity" min="1" value="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <textarea class="form-control" name="notes" id="enotes" rows="3" placeholder="Enter any additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Save Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEquipModal(row) {
    if (row) {
        document.getElementById('eid').value = row.id;
        document.getElementById('ename').value = row.name;
        document.getElementById('emodel').value = row.model || '';
        document.getElementById('efacilitated').value = row.facilitated_by || row.operator_name || '';
        document.getElementById('epurchase').value = row.purchase_date || '';
        document.getElementById('estatus').value = row.status;
        document.getElementById('equantity').value = row.quantity || 1;
        document.getElementById('enotes').value = row.notes || '';
    } else {
        document.getElementById('eid').value = '';
        document.getElementById('ename').value = '';
        document.getElementById('emodel').value = '';
        document.getElementById('efacilitated').value = '';
        document.getElementById('epurchase').value = '';
        document.getElementById('estatus').value = 'Operational';
        document.getElementById('equantity').value = 1;
        document.getElementById('enotes').value = '';
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('equipModal')).show();
}
</script>
<?php endif; ?>

<script>
function applyFilters() {
    const q = new URLSearchParams();
    const s = document.getElementById('searchInput').value;
    const st = document.getElementById('filterStatus').value;
    if (s) q.set('search', s);
    if (st) q.set('status', st);
    location.href = 'equipment.php?' + q.toString();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = '';
    applyFilters();
}
document.getElementById('searchInput')?.addEventListener('keypress', function(e) { if (e.key === 'Enter') applyFilters(); });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
