<?php
/**
 * Admin - Contact Inquiries & Messages Management
 * Food-Mania - Read, toggle read/unread status, and reply to user contact inquiries
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();

// Toggle Read status
if (isset($_GET['toggle_read'])) {
    $msgId = (int)$_GET['toggle_read'];
    if ($msgId > 0) {
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 - is_read WHERE id = :id");
        $stmt->execute([':id' => $msgId]);
        setFlash('success', 'Message status updated.');
    }
    redirect(SITE_URL . '/admin/messages.php');
}

// Mark All as Read
if (isset($_POST['mark_all_read'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $pdo->query("UPDATE contact_messages SET is_read = 1 WHERE is_read = 0");
        setFlash('success', 'All messages marked as read.');
    }
    redirect(SITE_URL . '/admin/messages.php');
}

// Delete Message
if (isset($_GET['delete'])) {
    $msgId = (int)$_GET['delete'];
    if ($msgId > 0) {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->execute([':id' => $msgId]);
        setFlash('success', 'Message deleted successfully.');
    }
    redirect(SITE_URL . '/admin/messages.php');
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$where = '';
if ($statusFilter === 'unread') {
    $where = 'WHERE is_read = 0';
} elseif ($statusFilter === 'read') {
    $where = 'WHERE is_read = 1';
}

$messages = $pdo->query("SELECT * FROM contact_messages $where ORDER BY created_at DESC")->fetchAll();

$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

$pageTitle = 'Contact Messages';
include __DIR__ . '/includes/admin_header.php';
include __DIR__ . '/includes/admin_sidebar.php';
?>

<?php echo displayFlash(); ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="fas fa-envelope text-orange me-2"></i> Customer Inquiries & Messages</h4>
        <p class="text-muted mb-0">Manage incoming inquiries submitted via the Contact page.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($unreadCount > 0): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" name="mark_all_read" value="1" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="fas fa-check-double me-1"></i> Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs & Stats -->
<div class="d-flex gap-2 mb-3">
    <a href="?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">
        All Messages (<?php echo $totalCount; ?>)
    </a>
    <a href="?status=unread" class="btn btn-sm <?php echo $statusFilter === 'unread' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">
        Unread <span class="badge bg-danger rounded-pill ms-1"><?php echo $unreadCount; ?></span>
    </a>
    <a href="?status=read" class="btn btn-sm <?php echo $statusFilter === 'read' ? 'btn-primary-custom' : 'btn-light border'; ?> rounded-pill">
        Read (<?php echo ($totalCount - $unreadCount); ?>)
    </a>
</div>

<!-- Messages Table -->
<div class="checkout-card">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Sender Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr class="<?php echo $m['is_read'] ? '' : 'table-warning-subtle fw-500'; ?>">
                        <td>
                            <?php if ($m['is_read']): ?>
                                <span class="badge bg-light text-muted border"><i class="fas fa-envelope-open me-1"></i> Read</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-envelope me-1"></i> Unread</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-600"><?php echo sanitize($m['name']); ?></td>
                        <td>
                            <a href="mailto:<?php echo urlencode($m['email']); ?>?subject=Re: <?php echo urlencode($m['subject']); ?>" class="text-decoration-none">
                                <?php echo sanitize($m['email']); ?> <i class="fas fa-reply fa-xs ms-1 text-muted"></i>
                            </a>
                        </td>
                        <td><span class="fw-600 text-dark"><?php echo sanitize($m['subject']); ?></span></td>
                        <td>
                            <div class="small text-secondary" style="max-width: 300px; max-height: 80px; overflow-y: auto;">
                                <?php echo nl2br(sanitize($m['message'])); ?>
                            </div>
                        </td>
                        <td><small class="text-muted"><?php echo formatDate($m['created_at'], 'd M Y, h:i A'); ?></small></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?toggle_read=<?php echo $m['id']; ?>" class="btn btn-action view" title="<?php echo $m['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>">
                                    <i class="fas <?php echo $m['is_read'] ? 'fa-envelope' : 'fa-check'; ?>"></i>
                                </a>
                                <a href="mailto:<?php echo urlencode($m['email']); ?>?subject=Re: <?php echo urlencode($m['subject']); ?>" class="btn btn-action edit" title="Reply via Email">
                                    <i class="fas fa-reply"></i>
                                </a>
                                <a href="?delete=<?php echo $m['id']; ?>" class="btn btn-action delete"
                                   onclick="return confirm('Delete this message?')" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>
                            No contact messages found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
