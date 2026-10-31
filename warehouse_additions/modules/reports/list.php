<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('reports', 'view');

$pageTitle = 'Reports';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Reports</h5>
</div>

<div class="row g-3">

    <!-- WIP Status Report -->
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3 fs-3 text-warning">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">WIP Status</h6>
                        <p class="mb-0 small text-muted">Work-in-progress flow across Receiving, QC, and Dispatching stages for a selected date range.</p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/reports/wip_status.php"
                   class="btn btn-dark btn-sm mt-auto align-self-start">
                    <i class="bi bi-diagram-3 me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>

    <!-- GRN Report -->
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3 fs-3 text-info">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">GRN Report</h6>
                        <p class="mb-0 small text-muted">Filterable GRN summary with received and rejected quantities, rejection rates, and QC status.</p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/reports/grn_report.php"
                   class="btn btn-dark btn-sm mt-auto align-self-start">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>

    <!-- Stock Report -->
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3 fs-3 text-success">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Stock Report</h6>
                        <p class="mb-0 small text-muted">Snapshot of current stock by location with subtotals per location and export to CSV.</p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/reports/stock_report.php"
                   class="btn btn-dark btn-sm mt-auto align-self-start">
                    <i class="bi bi-boxes me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>

    <!-- Stock Summary (existing) -->
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 fs-3 text-primary">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Stock Summary</h6>
                        <p class="mb-0 small text-muted">Current stock levels grouped by zone and location, with fill percentage per slot.</p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/reports/stock_summary.php"
                   class="btn btn-dark btn-sm mt-auto align-self-start">
                    <i class="bi bi-geo-alt me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>

    <!-- PO Aging (existing) -->
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-3 fs-3 text-danger">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">PO Aging</h6>
                        <p class="mb-0 small text-muted">Which purchase orders are overdue or at risk? Age buckets, urgency flags, and subcontractor breakdown.</p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/reports/po_aging.php"
                   class="btn btn-dark btn-sm mt-auto align-self-start">
                    <i class="bi bi-hourglass-split me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>

    <!-- Movement Summary (existing) -->
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-secondary bg-opacity-10 p-3 fs-3 text-secondary">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Movement Summary</h6>
                        <p class="mb-0 small text-muted">What moved in and out over a period? Detail log of all stock movements with type, item, and user.</p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/modules/reports/movement_summary.php"
                   class="btn btn-dark btn-sm mt-auto align-self-start">
                    <i class="bi bi-arrow-left-right me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
