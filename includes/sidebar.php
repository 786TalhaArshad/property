<nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item <?= active_menu('dashboard') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
    </div>

    <?php if (has_permission('crm.view')): ?>
    <div class="nav-section">CRM</div>
    <div class="nav-item <?= active_menu('leads') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/leads.php"><i class="bi bi-funnel"></i>Leads</a>
    </div>
    <div class="nav-item <?= active_menu('calls') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/call_logs.php"><i class="bi bi-telephone"></i>Call Logs</a>
    </div>
    <div class="nav-item <?= active_menu('meetings') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/meetings.php"><i class="bi bi-calendar-event"></i>Meetings</a>
    </div>
    <div class="nav-item <?= active_menu('tasks') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/tasks.php"><i class="bi bi-check2-square"></i>Tasks</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('projects.view') || has_permission('properties.view')): ?>
    <div class="nav-section">Real Estate</div>
    <?php if (has_permission('projects.view')): ?>
    <div class="nav-item <?= active_menu('projects') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/projects.php"><i class="bi bi-grid-1x2"></i>Projects</a>
    </div>
    <?php endif; ?>
    <?php if (has_permission('properties.view')): ?>
    <div class="nav-item <?= active_menu('properties') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/properties.php"><i class="bi bi-house-door"></i>Property Inventory</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (has_permission('customers.view') || has_permission('owners.view') || has_permission('dealers.view')): ?>
    <div class="nav-section">Parties</div>
    <?php if (has_permission('customers.view')): ?>
    <div class="nav-item <?= active_menu('customers') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/customers.php"><i class="bi bi-people"></i>Customers</a>
    </div>
    <?php endif; ?>
    <?php if (has_permission('owners.view')): ?>
    <div class="nav-item <?= active_menu('owners') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/owners.php"><i class="bi bi-person-badge"></i>Owners</a>
    </div>
    <?php endif; ?>
    <?php if (has_permission('dealers.view')): ?>
    <div class="nav-item <?= active_menu('dealers') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/dealers.php"><i class="bi bi-handshake"></i>Dealers / Agents</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (has_permission('sales.view')): ?>
    <div class="nav-section">Sales</div>
    <div class="nav-item <?= active_menu('quotations') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/quotations.php"><i class="bi bi-file-earmark-text"></i>Quotations</a>
    </div>
    <div class="nav-item <?= active_menu('bookings') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/bookings.php"><i class="bi bi-journal-check"></i>Bookings</a>
    </div>
    <div class="nav-item <?= active_menu('agreements') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/agreements.php"><i class="bi bi-file-earmark-check"></i>Sale Agreements</a>
    </div>
    <div class="nav-item <?= active_menu('installments') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/installments.php"><i class="bi bi-calendar2-check"></i>Installments</a>
    </div>
    <div class="nav-item <?= active_menu('receipts') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/receipts.php"><i class="bi bi-cash-coin"></i>Receipts</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('rentals.view')): ?>
    <div class="nav-section">Rentals</div>
    <div class="nav-item <?= active_menu('rental_agreements') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/rental_agreements.php"><i class="bi bi-house-heart"></i>Rental Agreements</a>
    </div>
    <div class="nav-item <?= active_menu('tenants') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/tenants.php"><i class="bi bi-person-check"></i>Tenants</a>
    </div>
    <div class="nav-item <?= active_menu('rent_collections') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/rent_collections.php"><i class="bi bi-cash"></i>Rent Collection</a>
    </div>
    <div class="nav-item <?= active_menu('owner_settlements') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/owner_settlements.php"><i class="bi bi-wallet2"></i>Owner Settlements</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('utilities.view') || has_permission('maintenance.view')): ?>
    <div class="nav-section">Operations</div>
    <?php if (has_permission('utilities.view')): ?>
    <div class="nav-item <?= active_menu('utilities') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/utilities.php"><i class="bi bi-plug"></i>Utilities</a>
    </div>
    <?php endif; ?>
    <?php if (has_permission('maintenance.view')): ?>
    <div class="nav-item <?= active_menu('maintenance') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/maintenance.php"><i class="bi bi-tools"></i>Maintenance</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (has_permission('accounting.view')): ?>
    <div class="nav-section">Finance</div>
    <div class="nav-item <?= active_menu('chart_of_accounts') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/chart_of_accounts.php"><i class="bi bi-diagram-3"></i>Chart of Accounts</a>
    </div>
    <div class="nav-item <?= active_menu('vouchers') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/vouchers.php"><i class="bi bi-receipt"></i>Vouchers</a>
    </div>
    <div class="nav-item <?= active_menu('ledger') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/ledger.php"><i class="bi bi-book"></i>General Ledger</a>
    </div>
    <div class="nav-item <?= active_menu('trial_balance') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/trial_balance.php"><i class="bi bi-columns-gap"></i>Trial Balance</a>
    </div>
    <div class="nav-item <?= active_menu('profit_loss') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/profit_loss.php"><i class="bi bi-graph-up-arrow"></i>Profit &amp; Loss</a>
    </div>
    <div class="nav-item <?= active_menu('balance_sheet') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/balance_sheet.php"><i class="bi bi-layers"></i>Balance Sheet</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('documents.view')): ?>
    <div class="nav-section">Utilities</div>
    <div class="nav-item <?= active_menu('documents') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/documents.php"><i class="bi bi-folder2-open"></i>Documents</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('reports.view')): ?>
    <div class="nav-section">Reports</div>
    <div class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/sales_report.php"><i class="bi bi-bar-chart"></i>Sales Report</a>
    </div>
    <div class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/rental_report.php"><i class="bi bi-bar-chart"></i>Rental Report</a>
    </div>
    <div class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/recovery_report.php"><i class="bi bi-bar-chart"></i>Recovery Report</a>
    </div>
    <div class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/outstanding_report.php"><i class="bi bi-bar-chart"></i>Outstanding Report</a>
    </div>
    <div class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/income_expense_report.php"><i class="bi bi-bar-chart"></i>Income / Expense</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('notifications.view')): ?>
    <div class="nav-item <?= active_menu('notifications') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/notifications.php"><i class="bi bi-bell"></i>Notifications</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('master.view') || has_permission('settings.manage')): ?>
    <div class="nav-section">System</div>
    <?php if (has_permission('master.view')): ?>
    <div class="nav-item">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subMaster">
            <i class="bi bi-journal-text"></i>Master Data <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse" id="subMaster">
            <a class="nav-link" href="<?= BASE_URL ?>/pages/countries.php">Countries</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/cities.php">Cities</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/areas.php">Areas</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/societies.php">Societies</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/property_types.php">Property Types</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/property_categories.php">Property Categories</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/amenities.php">Amenities</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/payment_methods.php">Payment Methods</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/banks.php">Banks</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/expense_categories.php">Expense Categories</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/income_categories.php">Income Categories</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/document_types.php">Document Types</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/branches.php">Branches</a>
        </div>
    </div>
    <?php endif; ?>
    <?php if (has_permission('settings.manage')): ?>
    <div class="nav-item <?= active_menu('settings') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/settings.php"><i class="bi bi-gear"></i>Company Settings</a>
    </div>
    <div class="nav-item <?= active_menu('users') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/users.php"><i class="bi bi-person-lock"></i>Users</a>
    </div>
    <div class="nav-item <?= active_menu('roles') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/roles.php"><i class="bi bi-shield-lock"></i>Roles &amp; Permissions</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="nav-item mt-3">
        <a class="nav-link" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a>
    </div>
</nav>
