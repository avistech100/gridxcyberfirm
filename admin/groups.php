<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('groups');

$db     = get_db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// DELETE
if ($action === 'delete' && $id) {
    verify_csrf();
    $db->prepare('DELETE FROM auth_group WHERE id=?')->execute([$id]);
    set_flash('success','Group deleted successfully.');
    redirect('/admin/groups.php');
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action,['add','edit'])) {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if (!$name) $errors[] = 'Name is required.';
    if (!$errors) {
        if ($action === 'add') {
            $db->prepare('INSERT INTO auth_group (name) VALUES (?)')->execute([$name]);
            set_flash('success',"Group \"{$name}\" added successfully.");
        } else {
            $db->prepare('UPDATE auth_group SET name=? WHERE id=?')->execute([$name,$id]);
            set_flash('success',"Group \"{$name}\" updated successfully.");
        }
        redirect('/admin/groups.php');
    }
}

$row = [];
if (in_array($action,['edit','delete']) && $id) {
    $st = $db->prepare('SELECT * FROM auth_group WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { set_flash('error','Group not found.'); redirect('/admin/groups.php'); }
}

$groups = $db->query('SELECT g.*, COUNT(ug.user_id) as user_count FROM auth_group g LEFT JOIN user_gridxuser_groups ug ON ug.group_id=g.id GROUP BY g.id ORDER BY g.name')->fetchAll();

$page_title = ($action==='add') ? 'Add group' : (($action==='edit') ? 'Change group' : 'Select group to change');
admin_head($page_title);
admin_body_open();
admin_content_header($page_title, ['Authentication and Authorization' => '/admin/', 'Groups' => '/admin/groups.php', $page_title => '']);
?>
<?= show_flash() ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Groups</h3>
            <a href="/admin/groups.php?action=add" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Add Group
            </a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-sm table-hover table-bordered">
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>NAME</th>
                    <th>Users in Group</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($groups as $g): ?>
                <tr>
                    <td><input type="checkbox"></td>
                    <td><a href="/admin/groups.php?action=edit&id=<?= $g['id'] ?>"><?= e($g['name']) ?></a></td>
                    <td><?= $g['user_count'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$groups): ?>
                <tr><td colspan="3" class="text-center text-muted">No groups found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p class="text-muted small"><?= count($groups) ?> group<?= count($groups)!==1?'s':'' ?></p>
    </div>
</div>

<?php else: ?>
<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><?= e($page_title) ?></h3></div>
    <div class="card-body">
        <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" maxlength="150"
                       value="<?= e($row['name'] ?? ($_POST['name'] ?? '')) ?>" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="/admin/groups.php" class="btn btn-outline-secondary">Cancel</a>
                <?php if ($action==='edit'): ?>
                <a href="/admin/groups.php?action=delete&id=<?= $id ?>"
                   onclick="return confirm('Delete this group?')"
                   class="btn btn-danger ms-auto">Delete</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php admin_footer(); ?>
