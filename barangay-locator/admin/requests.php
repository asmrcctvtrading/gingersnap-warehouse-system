<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('requests.review');
$user = currentUser();
$scope = userBarangayScope();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $rid = (int)($_POST['request_id'] ?? 0);
    $decision = (string)($_POST['decision'] ?? '');
    $note = clean_str($_POST['review_note'] ?? null, 255);
    $st = db()->prepare('SELECT * FROM establishment_change_requests WHERE request_id = ? AND status = "pending"');
    $st->execute([$rid]);
    $req = $st->fetch();
    if (!$req || !canAccessBarangay((int)$req['barangay_id'])) {
        flash('error', 'Request not found or already handled.');
    } elseif ($decision === 'reject') {
        db()->prepare('UPDATE establishment_change_requests SET status="rejected", reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE request_id=?')
            ->execute([$user['user_id'], $note, $rid]);
        logAudit('request_reject', 'change_request', $rid, $note);
        flash('ok', 'Rejected.');
    } elseif ($decision === 'approve') {
        $p = json_decode((string)$req['payload'], true) ?: [];
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($req['request_type'] === 'new') {
                $catId = !empty($p['category_id']) ? (int)$p['category_id'] : 0;
                $c = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ? AND is_active = 1');
                $c->execute([$catId]);
                if (!$c->fetchColumn()) {
                    $o = $pdo->query("SELECT category_id FROM categories WHERE slug = 'other'")->fetchColumn();
                    $catId = (int)$o;
                }
                [$lat, $lng, $ce] = validate_coords($p['lat'] ?? null, $p['lng'] ?? null);
                // Submitted coordinates are kept but NEVER marked as checked.
                $ins = $pdo->prepare('INSERT INTO establishments (barangay_id, name, description, primary_category_id, street_address, landmark, zone,
                    latitude, longitude, coords_verified, coords_source, classification, status, data_source, created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,0,?,"private","pending_verification",?,?)');
                $ins->execute([(int)$req['barangay_id'], $p['name'] ?? 'Unnamed', $p['message'] ?? null, $catId, $p['address'] ?? null, $p['landmark'] ?? null,
                    $p['zone'] ?? null, $ce ? null : $lat, $ce ? null : $lng, ($lat !== null && !$ce) ? 'Public submission (unchecked)' : null,
                    'Public submission #' . $rid, $user['user_id']]);
                $newId = (int)$pdo->lastInsertId();
                if (!empty($p['phone'])) {
                    $pdo->prepare('INSERT INTO establishment_contacts (establishment_id, contact_type, contact_value) VALUES (?, "phone", ?)')->execute([$newId, $p['phone']]);
                }
                $pdo->prepare('INSERT INTO establishment_categories (establishment_id, category_id) VALUES (?,?)')->execute([$newId, $catId]);
                $pdo->prepare('INSERT INTO verification_records (establishment_id, action, to_status, note, user_id) VALUES (?, "created", "pending_verification", ?, ?)')
                    ->execute([$newId, 'from public request #' . $rid, $user['user_id']]);
                $note = trim(($note ?? '') . ' [created establishment #' . $newId . ']');
            } else {
                // Corrections are not applied automatically: flag the record so a staff member checks and edits it.
                $pdo->prepare('UPDATE establishments SET status = IF(status = "inactive", status, "needs_review") WHERE establishment_id = ?')->execute([$req['establishment_id']]);
                $pdo->prepare('INSERT INTO verification_records (establishment_id, action, note, user_id) VALUES (?, "flag_review", ?, ?)')
                    ->execute([$req['establishment_id'], 'public request #' . $rid, $user['user_id']]);
            }
            $pdo->prepare('UPDATE establishment_change_requests SET status="approved", reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE request_id=?')
                ->execute([$user['user_id'], $note, $rid]);
            $pdo->commit();
            logAudit('request_approve', 'change_request', $rid, $note);
            flash('ok', $req['request_type'] === 'new' ? 'Approved: a record was created and is awaiting verification.' : 'Approved: the record was flagged for review. Edit it with the corrected details.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log($e->getMessage());
            flash('error', 'Could not process the request.');
        }
    }
    redirect('admin/requests.php');
}

$w = $scope !== null ? ' AND r.barangay_id = ' . (int)$scope : '';
$rows = db()->query('SELECT r.*, e.name AS est_name FROM establishment_change_requests r LEFT JOIN establishments e ON e.establishment_id = r.establishment_id
                     WHERE r.status = "pending"' . $w . ' ORDER BY r.created_at ASC LIMIT 100')->fetchAll();

admin_header('Public requests', 'requests');
?>
<h1>Public requests</h1>
<p class="muted">Nothing here is public. Approving a new-place request creates an “awaiting verification” record; corrections flag the record for you to edit.</p>
<?php if (!$rows): ?><div class="notice info">No pending requests.</div><?php endif; ?>
<?php foreach ($rows as $r): $p = json_decode((string)$r['payload'], true) ?: []; ?>
<article class="card" style="margin:.8rem 0">
  <strong><?= h(['new' => 'New place', 'correction' => 'Correction', 'report_incorrect' => 'Report incorrect'][$r['request_type']]) ?></strong>
  <?= $r['est_name'] ? ' — <a href="' . h(url('admin/establishment_edit.php?id=' . $r['establishment_id'])) . '">' . h($r['est_name']) . '</a>' : '' ?>
  <span class="muted"> · <?= h($r['created_at']) ?></span>
  <dl class="facts">
    <?php foreach ($p as $k => $val): if ($val === null || $val === '') { continue; } ?><dt><?= h($k) ?></dt><dd><?= h((string)$val) ?></dd><?php endforeach; ?>
    <?php if ($r['submitter_name'] || $r['submitter_contact']): ?><dt>From</dt><dd><?= h(trim(($r['submitter_name'] ?? '') . ' ' . ($r['submitter_contact'] ?? ''))) ?></dd><?php endif; ?>
  </dl>
  <form method="post" class="row-actions">
    <?= csrfField() ?><input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
    <input type="text" name="review_note" placeholder="Note (optional)" maxlength="255" style="min-width:200px">
    <button name="decision" value="approve">Approve</button>
    <button name="decision" value="reject" class="secondary">Reject</button>
  </form>
</article>
<?php endforeach; ?>
<?php admin_footer(); ?>
