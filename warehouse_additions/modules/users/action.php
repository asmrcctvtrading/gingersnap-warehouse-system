<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$myId   = (int)($_SESSION['user_id'] ?? 0);

switch ($action) {

    // ── Save User (create or update) ──────────────────────────────────────────
    case 'save_user':

        $id     = (int)($_POST['id'] ?? 0);
        $isEdit = $id > 0;

        if ($isEdit) {
            requirePermission('users', 'edit');
        } else {
            requirePermission('users', 'create');
        }

        // Collect fields
        $fullName        = trim($_POST['full_name']        ?? '');
        $username        = trim($_POST['username']         ?? '');
        $email           = trim($_POST['email']            ?? '') ?: null;
        $roleId          = ($_POST['role_id'] ?? '') !== '' ? (int)$_POST['role_id'] : null;
        $password        = $_POST['password']              ?? '';
        $passwordConfirm = $_POST['password_confirm']      ?? '';
        $status          = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true)
                           ? ($_POST['status'] ?? 'active')
                           : 'active';

        // Validate required fields
        if ($fullName === '') {
            setFlash('error', 'Full Name is required.');
            redirect($isEdit ? '/modules/users/form.php?id=' . $id : '/modules/users/form.php');
        }
        if ($username === '') {
            setFlash('error', 'Username is required.');
            redirect($isEdit ? '/modules/users/form.php?id=' . $id : '/modules/users/form.php');
        }
        if (!$isEdit && $password === '') {
            setFlash('error', 'Password is required when creating a new user.');
            redirect('/modules/users/form.php');
        }
        if ($password !== '' && $password !== $passwordConfirm) {
            setFlash('error', 'Passwords do not match.');
            redirect($isEdit ? '/modules/users/form.php?id=' . $id : '/modules/users/form.php');
        }

        // Check username uniqueness — exclude self on edit
        $stCheck = $db->prepare(
            "SELECT user_id FROM users WHERE username = ? AND user_id != ?"
        );
        $stCheck->execute([$username, $isEdit ? $id : 0]);
        if ($stCheck->fetchColumn()) {
            setFlash('error', 'A user with that username already exists.');
            redirect($isEdit ? '/modules/users/form.php?id=' . $id : '/modules/users/form.php');
        }

        try {
            if ($isEdit) {
                if ($password !== '') {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare(
                        "UPDATE users
                         SET full_name = :fn, username = :un, email = :em,
                             role_id   = :ri, status   = :st, password = :pw
                         WHERE user_id = :id"
                    );
                    $stmt->execute([
                        'fn' => $fullName,
                        'un' => $username,
                        'em' => $email,
                        'ri' => $roleId,
                        'st' => $status,
                        'pw' => $hashed,
                        'id' => $id,
                    ]);
                } else {
                    $stmt = $db->prepare(
                        "UPDATE users
                         SET full_name = :fn, username = :un, email = :em,
                             role_id   = :ri, status   = :st
                         WHERE user_id = :id"
                    );
                    $stmt->execute([
                        'fn' => $fullName,
                        'un' => $username,
                        'em' => $email,
                        'ri' => $roleId,
                        'st' => $status,
                        'id' => $id,
                    ]);
                }
                logActivity('UPDATE', 'users', $id, $username);
                setFlash('success', 'User "' . $username . '" updated.');

            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare(
                    "INSERT INTO users (username, full_name, email, role_id, password_hash, status)
                     VALUES (:un, :fn, :em, :ri, :pw, 'active')"
                );
                $stmt->execute([
                    'un' => $username,
                    'fn' => $fullName,
                    'em' => $email,
                    'ri' => $roleId,
                    'pw' => $hashed,
                ]);
                $newId = (int)$db->lastInsertId();
                logActivity('CREATE', 'users', $newId, $username);
                setFlash('success', 'User "' . $username . '" created.');
            }

            redirect('/modules/users/list.php');

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'A user with that username already exists.');
            } else {
                setFlash('error', 'Database error: ' . $e->getMessage());
            }
            redirect($isEdit ? '/modules/users/form.php?id=' . $id : '/modules/users/form.php');
        }
        break;

    // ── Toggle Status ─────────────────────────────────────────────────────────
    case 'toggle_status':
        requirePermission('users', 'delete');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid user.');
            redirect('/modules/users/list.php');
        }
        if ($id === $myId) {
            setFlash('error', 'You cannot change the status of your own account.');
            redirect('/modules/users/list.php');
        }

        $stmt = $db->prepare("SELECT user_id, username, status FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            setFlash('error', 'User not found.');
            redirect('/modules/users/list.php');
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $db->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$newStatus, $id]);
        logActivity('UPDATE', 'users', $id, $user['username'] . ' status=' . $newStatus);
        setFlash('success', 'User "' . $user['username'] . '" is now ' . $newStatus . '.');
        redirect('/modules/users/list.php');
        break;

    // ── Reset Password ────────────────────────────────────────────────────────
    case 'reset_password':
        requirePermission('users', 'edit');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid user.');
            redirect('/modules/users/list.php');
        }

        $stmt = $db->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            setFlash('error', 'User not found.');
            redirect('/modules/users/list.php');
        }

        // Generate a random 10-character temporary password
        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$';
        $tempPass = '';
        for ($i = 0; $i < 10; $i++) {
            $tempPass .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $hashed = password_hash($tempPass, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hashed, $id]);
        logActivity('UPDATE', 'users', $id, $user['username'] . ' password_reset');
        setFlash('success', 'Password for "' . $user['username'] . '" has been reset. Temp password: ' . $tempPass);
        redirect('/modules/users/list.php');
        break;

    default:
        redirect('/modules/users/list.php');
}
