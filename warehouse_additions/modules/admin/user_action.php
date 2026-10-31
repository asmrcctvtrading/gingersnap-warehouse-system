<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$myId   = (int)($_SESSION['user_id'] ?? 0);

switch ($action) {

    case 'create_user':
        requirePermission('users', 'view');
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $roleId   = $_POST['role_id'] !== '' ? (int)$_POST['role_id'] : null;
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $username === '') {
            setFlash('error', 'Full Name and Username are required.');
            redirect('/modules/admin/user_form.php');
        }
        if ($password === '') {
            setFlash('error', 'Password is required when creating a new user.');
            redirect('/modules/admin/user_form.php');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $db->prepare(
                "INSERT INTO users (username, full_name, role_id, password, status)
                 VALUES (:u, :fn, :ri, :pw, 'active')"
            );
            $stmt->execute([
                'u'  => $username,
                'fn' => $fullName,
                'ri' => $roleId,
                'pw' => $hashed,
            ]);
            $newId = (int)$db->lastInsertId();
            logActivity('CREATE', 'users', $newId, $username);
            setFlash('success', 'User "' . $username . '" created successfully.');
            redirect('/modules/admin/users.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'A user with that username already exists.');
            } else {
                setFlash('error', 'Database error: ' . $e->getMessage());
            }
            redirect('/modules/admin/user_form.php');
        }
        break;

    case 'update_user':
        requirePermission('users', 'view');
        $id       = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $roleId   = $_POST['role_id'] !== '' ? (int)$_POST['role_id'] : null;
        $status   = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $fullName === '' || $username === '') {
            setFlash('error', 'Invalid request: Full Name and Username are required.');
            redirect('/modules/admin/users.php');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        try {
            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare(
                    "UPDATE users
                     SET full_name = :fn, username = :u, role_id = :ri, status = :st, password = :pw
                     WHERE user_id = :id"
                );
                $stmt->execute([
                    'fn' => $fullName,
                    'u'  => $username,
                    'ri' => $roleId,
                    'st' => $status,
                    'pw' => $hashed,
                    'id' => $id,
                ]);
            } else {
                $stmt = $db->prepare(
                    "UPDATE users
                     SET full_name = :fn, username = :u, role_id = :ri, status = :st
                     WHERE user_id = :id"
                );
                $stmt->execute([
                    'fn' => $fullName,
                    'u'  => $username,
                    'ri' => $roleId,
                    'st' => $status,
                    'id' => $id,
                ]);
            }
            logActivity('UPDATE', 'users', $id, $username);
            setFlash('success', 'User "' . $username . '" updated successfully.');
            redirect('/modules/admin/users.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'A user with that username already exists.');
            } else {
                setFlash('error', 'Database error: ' . $e->getMessage());
            }
            redirect('/modules/admin/user_form.php?id=' . $id);
        }
        break;

    case 'toggle_status':
        requirePermission('users', 'view');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid user.');
            redirect('/modules/admin/users.php');
        }
        if ($id === $myId) {
            setFlash('error', 'You cannot change the status of your own account.');
            redirect('/modules/admin/users.php');
        }

        $stmt = $db->prepare("SELECT user_id, username, status FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            setFlash('error', 'User not found.');
            redirect('/modules/admin/users.php');
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $db->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$newStatus, $id]);
        logActivity('UPDATE', 'users', $id, $user['username'] . ' status=' . $newStatus);
        setFlash('success', 'User "' . $user['username'] . '" is now ' . $newStatus . '.');
        redirect('/modules/admin/users.php');
        break;

    case 'reset_password':
        requirePermission('users', 'view');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid user.');
            redirect('/modules/admin/users.php');
        }

        $stmt = $db->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            setFlash('error', 'User not found.');
            redirect('/modules/admin/users.php');
        }

        $tempPassword = 'Gingersnaps@2026';
        $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hashed, $id]);
        logActivity('UPDATE', 'users', $id, $user['username'] . ' password_reset');
        setFlash('success', 'Password for "' . $user['username'] . '" reset. Temp password: ' . $tempPassword);
        redirect('/modules/admin/users.php');
        break;

    default:
        redirect('/modules/admin/users.php');
}
