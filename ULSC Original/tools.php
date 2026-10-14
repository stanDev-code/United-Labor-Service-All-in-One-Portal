<?php
/**
 * Tools Inventory Module
 * Admin: full CRUD. Staff: view only
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Tools Inventory';
$is_admin = isAdmin();

if ($is_admin && isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $pdo->prepare("DELETE FROM tools WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: tools.php?msg=deleted');
    exit;
}

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $tool_name = trim($_POST['tool_name'] ?? '');
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $condition_status = $_POST['condition_status'] ?? 'Good';
    $assigned_to = trim($_POST['assigned_to'] ?? '');
    $last_inspection_date = !empty($_POST['last_inspection_date']) ? $_POST['last_inspection_date'] : null;
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($tool_name) && in_array($condition_status, ['Good', 'Fair', 'Poor', 'Needs Replacement'])) {
        if ($id) {
            $pdo->prepare("UPDATE tools SET tool_name=?, quantity=?, condition_status=?, assigned_to=?, last_inspection_date=?, notes=? WHERE id=?")
                ->execute([$tool_name, $quantity, $condition_status, $assigned_to, $last_inspection_date ?: null, $notes, $id]);
        } else {
            $pdo->prepare("INSERT INTO tools (tool_name, quantity, condition_status, assigned_to, last_inspection_date, notes) VALUES (?,?,?,?,?,?)")
                ->execute([$tool_name, $quantity, $condition_status, $assigned_to, $last_inspection_date ?: null, $notes]);
        }
        header('Location: tools.php?msg=saved');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$filter_cond = $_GET['condition'] ?? '';
$sort_by = $_GET['sort'] ?? 'tool_name';
$sort_order = $_GET['order'] ?? 'desc';

/* Validate sort parameters */
$allowed_sorts = ['tool_name', 'quantity', 'condition_status', 'assigned_to', 'last_inspection_date'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'tool_name';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'asc';

$sql = "SELECT t.* FROM tools t WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND t.tool_name LIKE ?"; $params[] = "%$search%"; }
if ($filter_cond) { $sql .= " AND t.condition_status = ?"; $params[] = $filter_cond; }

/* Handle sorting for assigned_to */
if ($sort_by === 'assigned_to') {
    $sql .= " ORDER BY COALESCE(t.assigned_to, '') $sort_order";
} else {
    $sql .= " ORDER BY t.$sort_by $sort_order";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tools_list = $stmt->fetchAll();
$staff = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><?= $_GET['msg'] === 'saved' ? 'Record saved.' : 'Record deleted.' ?></div><?php endif; ?>

<div class="search-container" style="margin-bottom: 24px;">
    <form method="GET" class="search-filters-row">
        <div class="search-filter-item" style="flex: 1; min-width: 280px;">
            <label class="search-filter-label">Search Tools</label>
            <div class="search-input-wrapper">
                <div class="search-icon-wrapper">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" class="modern-search-input" placeholder="Search by tool name..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        
        <div class="search-filter-item">
            <label class="search-filter-label">Condition</label>
            <select name="condition" class="modern-select" style="min-width: 180px;">
                <option value="">All Conditions</option>
                <option value="Good" <?= $filter_cond === 'Good' ? 'selected' : '' ?>>Good</option>
                <option value="Fair" <?= $filter_cond === 'Fair' ? 'selected' : '' ?>>Fair</option>
                <option value="Poor" <?= $filter_cond === 'Poor' ? 'selected' : '' ?>>Poor</option>
                <option value="Needs Replacement" <?= $filter_cond === 'Needs Replacement' ? 'selected' : '' ?>>Needs Replacement</option>
            </select>
        </div>
        
        <div class="search-filter-item">
            <button type="submit" class="modern-btn-primary">
                <i class="bi bi-search me-2"></i>Search Tools
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end mb-3">
    <?php if ($is_admin): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#toolModal" onclick="openToolModal()"><i class="bi bi-plus-lg"></i> Add Tool</button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?sort=tool_name&order=<?= $sort_by === 'tool_name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Tool Name <?= $sort_by === 'tool_name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=quantity&order=<?= $sort_by === 'quantity' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Quantity <?= $sort_by === 'quantity' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=condition_status&order=<?= $sort_by === 'condition_status' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Condition <?= $sort_by === 'condition_status' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=assigned_to&order=<?= $sort_by === 'assigned_to' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Assigned To <?= $sort_by === 'assigned_to' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=last_inspection_date&order=<?= $sort_by === 'last_inspection_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Last Inspection <?= $sort_by === 'last_inspection_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tools_list as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['tool_name']) ?></td>
                <td><?= (int) $t['quantity'] ?></td>
                <td><span class="badge <?= match($t['condition_status']) { 'Good' => 'badge-good', 'Fair' => 'badge-fair', 'Poor' => 'badge-poor', default => 'badge-replace' } ?>"><?= htmlspecialchars($t['condition_status']) ?></span></td>
                <td><?= htmlspecialchars($t['assigned_to'] ?? '-') ?></td>
                <td><?= htmlspecialchars($t['last_inspection_date'] ?? '-') ?></td>
                <?php if ($is_admin): ?>
                <td>
                   <!-- Edit Button - Primary 3D effect with text -->
<button class="btn btn-sm btn-primary" 
        onclick='openToolModal(<?= json_encode($t) ?>)'
        title="Edit Tool: <?= htmlspecialchars($t['tool_name']) ?>"
        style="box-shadow: 0 4px 0 #0b5ed7; transform: translateY(0); transition: all 0.1s; border: none;"
        onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #0b5ed7'"
        onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'"
        onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'">
    <i class="bi bi-pencil me-1"></i> Edit
</button>

<!-- Delete Button - Danger 3D effect with text -->
<button class="btn btn-sm btn-danger" 
        onclick="showDeleteConfirm(
            'Tool: <?= htmlspecialchars(addslashes($t['tool_name'])) ?>', 
            'Quantity: <?= (int) $t['quantity'] ?> - Condition: <?= htmlspecialchars(addslashes($t['condition_status'])) ?>', 
            'tools.php?delete=<?= $t['id'] ?>'
        )"
        title="Delete Tool: <?= htmlspecialchars($t['tool_name']) ?>"
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
            <?php if (empty($tools_list)): ?><tr><td colspan="<?= $is_admin ? 6 : 5 ?>" class="text-muted">No tools yet</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($is_admin): ?>
<div class="modal fade" id="toolModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="tid">
                <div class="modal-header"><h5 class="modal-title">Add / Edit Tool</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Tool Name *</label><input type="text" class="form-control" name="tool_name" id="tname" required></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Quantity</label><input type="number" class="form-control" name="quantity" id="tqty" min="1" value="1"></div>
                        <div class="col-md-6"><label class="form-label">Condition</label><select class="form-select" name="condition_status" id="tcond"><option value="Good">Good</option><option value="Fair">Fair</option><option value="Poor">Poor</option><option value="Needs Replacement">Needs Replacement</option></select></div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-6"><label class="form-label">Assigned To</label><input type="text" class="form-control" name="assigned_to" id="tassigned" placeholder="Enter name..."></div>
                        <div class="col-md-6"><label class="form-label">Last Inspection Date</label><input type="date" class="form-control" name="last_inspection_date" id="tinspect"></div>
                    </div>
                    <div class="mt-2"><label class="form-label">Notes</label><textarea class="form-control" name="notes" id="tnotes" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>
<script>
function openToolModal(row) {
    if (row) {
        document.getElementById('tid').value = row.id;
        document.getElementById('tname').value = row.tool_name || '';
        document.getElementById('tqty').value = row.quantity || 1;
        document.getElementById('tcond').value = row.condition_status || 'Good';
        document.getElementById('tassigned').value = row.assigned_to || '';
        document.getElementById('tinspect').value = row.last_inspection_date || '';
        document.getElementById('tnotes').value = row.notes || '';
    } else { document.getElementById('tid').value = ''; document.querySelector('#toolModal form').reset(); document.getElementById('tid').value = ''; document.getElementById('tqty').value = 1; }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('toolModal')).show();
}
</script>
<?php endif; ?>
<script>
function applyFilters() {
    const q = new URLSearchParams();
    const s = document.getElementById('searchInput').value;
    const c = document.getElementById('filterCond').value;
    if (s) q.set('search', s);
    if (c) q.set('condition', c);
    location.href = 'tools.php?' + q.toString();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterCond').value = '';
    applyFilters();
}
document.getElementById('searchInput')?.addEventListener('keypress', function(e) { if (e.key === 'Enter') applyFilters(); });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
