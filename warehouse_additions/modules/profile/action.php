<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    redirect('/login.php');
}

switch ($action) {

    // ── Change Password ──────────────────────────────────────────────────────

    case 'change_password':
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = trim($_POST['new_password']     ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Load current hash
        $stmtUser = $db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmtUser->execute([$userId]);
        $currentHash = $stmtUser->fetchColumn();

        if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
            setFlash('error', 'Current password is incorrect.');
            redirect('/modules/profile/index.php');
        }
        if (strlen($newPassword) < 8) {
            setFlash('error', 'New password must be at least 8 characters.');
            redirect('/modules/profile/index.php');
        }
        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'New passwords do not match.');
            redirect('/modules/profile/index.php');
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password = ? WHERE user_id = ?")
           ->execute([$newHash, $userId]);

        logAudit($db, 'CHANGE_PASSWORD', 'profile', $userId, 'Password changed by user');
        setFlash('success', 'Password changed successfully.');
        redirect('/modules/profile/index.php');
        break;

    // ── Save Profile ─────────────────────────────────────────────────────────

    case 'save_profile':
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email']     ?? '') ?: null;

        if ($fullName === '') {
            setFlash('error', 'Full name is required.');
            redirect('/modules/profile/index.php');
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Please enter a valid email address.');
            redirect('/modules/profile/index.php');
        }

        $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?")
           ->execute([$fullName, $email, $userId]);

        // Update session so topbar shows new name immediately
        $_SESSION['full_name'] = $fullName;

        logAudit($db, 'UPDATE', 'profile', $userId, 'Profile updated: ' . $fullName);
        setFlash('success', 'Profile updated.');
        redirect('/modules/profile/index.php');
        break;

    default:
        redirect('/modules/profile/index.php');
}
