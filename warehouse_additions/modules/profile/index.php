<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
// No specific permission check — every logged-in user can view their own profile.

$db     = getDB();
$userId = (int)($_SESSION['user_id'] ?? 0);

// ── Load full user row ────────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT u.*, r.role_name
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     WHERE u.user_id = ?"
);
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    redirect('/login.php');
}

$pageTitle = 'My Profile';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="mb-3">
    <h5 class="mb-0"><i class="bi bi-person-circle me-1"></i>My Profile</h5>
</div>

<div class="row g-3">

    <!-- ── Profile Info ─────────────────────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Account Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Username</dt>
                    <dd class="col-sm-7 fw-semibold"><?= h($user['username']) ?></dd>

                    <dt class="col-sm-5 text-muted">Full Name</dt>
                    <dd class="col-sm-7"><?= h($user['full_name']) ?></dd>

                    <dt class="col-sm-5 text-muted">Email</dt>
                    <dd class="col-sm-7"><?= $user['email'] ? h($user['email']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-sm-5 text-muted">Role</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-secondary"><?= h($user['role_name'] ?? '') ?></span>
                    </dd>

                    <dt class="col-sm-5 text-muted">Member Since</dt>
                    <dd class="col-sm-7 text-muted" style="font-size:13px;">
                        <?= $user['created_at'] ? h(date('M d, Y', strtotime($user['created_at']))) : '—' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- ── Update Profile ───────────────────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Update Profile</div>
            <div class="card-body">
                <form method="post" action="action.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save_profile">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= h($user['full_name']) ?>"
                               maxlength="150" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= h($user['email'] ?? '') ?>"
                               maxlength="150">
                    </div>

                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Save Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Change Password ──────────────────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Change Password</div>
            <div class="card-body">
                <form method="post" action="action.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="current_password" class="form-control"
                               autocomplete="current-password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="new_password" id="newPw"
                               class="form-control" minlength="8"
                               autocomplete="new-password" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="confirm_password" id="confirmPw"
                               class="form-control"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-key me-1"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
// Client-side confirm-password match hint
document.getElementById('confirmPw').addEventListener('input', function () {
    var pw      = document.getElementById('newPw').value;
    var confirm = this.value;
    if (pw && confirm && pw !== confirm) {
        this.setCustomValidity('Passwords do not match.');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
