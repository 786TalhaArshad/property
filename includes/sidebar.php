<nav class="sidebar-nav">
    <div class="nav-item <?= active_menu('dashboard') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
    </div>

    <?php if (has_permission('crm.view')): ?>
    <?php $crm = submenu_state(['leads', 'calls', 'meetings', 'tasks']); ?>
    <div class="nav-item <?= $crm ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subCrm">
            <i class="bi bi-funnel"></i>CRM <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $crm ? 'show' : '' ?>" id="subCrm">
            <a class="nav-link <?= active_menu('leads') ?>" href="<?= BASE_URL ?>/pages/leads.php">Leads</a>
            <a class="nav-link <?= active_menu('calls') ?>" href="<?= BASE_URL ?>/pages/call_logs.php">Call Logs</a>
            <a class="nav-link <?= active_menu('meetings') ?>" href="<?= BASE_URL ?>/pages/meetings.php">Meetings</a>
            <a class="nav-link <?= active_menu('tasks') ?>" href="<?= BASE_URL ?>/pages/tasks.php">Tasks</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('projects.view') || has_permission('properties.view')): ?>
    <?php $realEstate = submenu_state(['projects', 'properties']); ?>
    <div class="nav-item <?= $realEstate ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subRealEstate">
            <i class="bi bi-grid-1x2"></i>Real Estate <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $realEstate ? 'show' : '' ?>" id="subRealEstate">
            <?php if (has_permission('projects.view')): ?>
            <a class="nav-link <?= active_menu('projects') ?>" href="<?= BASE_URL ?>/pages/projects.php">Projects</a>
            <?php endif; ?>
            <?php if (has_permission('properties.view')): ?>
            <a class="nav-link <?= active_menu('properties') ?>" href="<?= BASE_URL ?>/pages/properties.php">Property Inventory</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('customers.view') || has_permission('owners.view') || has_permission('dealers.view') || has_permission('vendors.view') || has_permission('general_parties.view')): ?>
    <?php $parties = submenu_state(['customers', 'owners', 'dealers', 'vendors', 'general_parties']); ?>
    <div class="nav-item <?= $parties ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subParties">
            <i class="bi bi-people"></i>Parties <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $parties ? 'show' : '' ?>" id="subParties">
            <?php if (has_permission('customers.view')): ?>
            <a class="nav-link <?= active_menu('customers') ?>" href="<?= BASE_URL ?>/pages/customers.php">Customers</a>
            <?php endif; ?>
            <?php if (has_permission('owners.view')): ?>
            <a class="nav-link <?= active_menu('owners') ?>" href="<?= BASE_URL ?>/pages/owners.php">Owners</a>
            <?php endif; ?>
            <?php if (has_permission('dealers.view')): ?>
            <a class="nav-link <?= active_menu('dealers') ?>" href="<?= BASE_URL ?>/pages/dealers.php">Dealers / Agents</a>
            <?php endif; ?>
            <?php if (has_permission('vendors.view')): ?>
            <a class="nav-link <?= active_menu('vendors') ?>" href="<?= BASE_URL ?>/pages/vendors.php">Vendors</a>
            <?php endif; ?>
            <?php if (has_permission('general_parties.view')): ?>
            <a class="nav-link <?= active_menu('general_parties') ?>" href="<?= BASE_URL ?>/pages/general_parties.php">General Party</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('sales.view')): ?>
    <?php $sales = submenu_state(['quotations', 'bookings', 'agreements', 'installments', 'receipts']); ?>
    <div class="nav-item <?= $sales ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subSales">
            <i class="bi bi-bag-check"></i>Sales <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $sales ? 'show' : '' ?>" id="subSales">
            <a class="nav-link <?= active_menu('quotations') ?>" href="<?= BASE_URL ?>/pages/quotations.php">Quotations</a>
            <a class="nav-link <?= active_menu('bookings') ?>" href="<?= BASE_URL ?>/pages/bookings.php">Bookings</a>
            <a class="nav-link <?= active_menu('agreements') ?>" href="<?= BASE_URL ?>/pages/agreements.php">Sale Agreements</a>
            <a class="nav-link <?= active_menu('installments') ?>" href="<?= BASE_URL ?>/pages/installments.php">Installments</a>
            <a class="nav-link <?= active_menu('receipts') ?>" href="<?= BASE_URL ?>/pages/receipts.php">Receipts</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('rentals.view')): ?>
    <?php $rentals = submenu_state(['rental_agreements', 'tenants', 'rent_collections', 'owner_settlements']); ?>
    <div class="nav-item <?= $rentals ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subRentals">
            <i class="bi bi-house-heart"></i>Rentals <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $rentals ? 'show' : '' ?>" id="subRentals">
            <a class="nav-link <?= active_menu('rental_agreements') ?>" href="<?= BASE_URL ?>/pages/rental_agreements.php">Rental Agreements</a>
            <a class="nav-link <?= active_menu('tenants') ?>" href="<?= BASE_URL ?>/pages/tenants.php">Tenants</a>
            <a class="nav-link <?= active_menu('rent_collections') ?>" href="<?= BASE_URL ?>/pages/rent_collections.php">Rent Collection</a>
            <a class="nav-link <?= active_menu('owner_settlements') ?>" href="<?= BASE_URL ?>/pages/owner_settlements.php">Owner Settlements</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('utilities.view') || has_permission('maintenance.view')): ?>
    <?php $operations = submenu_state(['utilities', 'maintenance']); ?>
    <div class="nav-item <?= $operations ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subOperations">
            <i class="bi bi-tools"></i>Operations <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $operations ? 'show' : '' ?>" id="subOperations">
            <?php if (has_permission('utilities.view')): ?>
            <a class="nav-link <?= active_menu('utilities') ?>" href="<?= BASE_URL ?>/pages/utilities.php">Utilities</a>
            <?php endif; ?>
            <?php if (has_permission('maintenance.view')): ?>
            <a class="nav-link <?= active_menu('maintenance') ?>" href="<?= BASE_URL ?>/pages/maintenance.php">Maintenance</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('accounting.view')): ?>
    <?php $finance = submenu_state(['chart_of_accounts', 'expense_heads', 'income_heads', 'vouchers', 'transfers', 'roznamcha', 'ledger', 'trial_balance', 'profit_loss', 'balance_sheet']); ?>
    <div class="nav-item <?= $finance ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subFinance">
            <i class="bi bi-wallet2"></i>Finance <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $finance ? 'show' : '' ?>" id="subFinance">
            <a class="nav-link <?= active_menu('chart_of_accounts') ?>" href="<?= BASE_URL ?>/pages/chart_of_accounts.php">Chart of Accounts</a>
            <a class="nav-link <?= active_menu('expense_heads') ?>" href="<?= BASE_URL ?>/pages/expense_heads.php">Expense Heads</a>
            <a class="nav-link <?= active_menu('income_heads') ?>" href="<?= BASE_URL ?>/pages/income_heads.php">Income Heads</a>
            <a class="nav-link <?= active_menu('vouchers') ?>" href="<?= BASE_URL ?>/pages/vouchers.php">Vouchers</a>
            <a class="nav-link <?= active_menu('transfers') ?>" href="<?= BASE_URL ?>/pages/transfers.php">Transfers / Withdrawals</a>
            <a class="nav-link <?= active_menu('roznamcha') ?>" href="<?= BASE_URL ?>/pages/roznamcha.php">Roznamcha (Day Book)</a>
            <a class="nav-link <?= active_menu('ledger') ?>" href="<?= BASE_URL ?>/pages/ledger.php">General Ledger</a>
            <a class="nav-link <?= active_menu('trial_balance') ?>" href="<?= BASE_URL ?>/pages/trial_balance.php">Trial Balance</a>
            <a class="nav-link <?= active_menu('profit_loss') ?>" href="<?= BASE_URL ?>/pages/profit_loss.php">Profit &amp; Loss</a>
            <a class="nav-link <?= active_menu('balance_sheet') ?>" href="<?= BASE_URL ?>/pages/balance_sheet.php">Balance Sheet</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('documents.view')): ?>
    <div class="nav-item <?= active_menu('documents') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/documents.php"><i class="bi bi-folder2-open"></i>Documents</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('reports.view')): ?>
    <div class="nav-item <?= active_menu('reports') ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subReports">
            <i class="bi bi-bar-chart"></i>Reports <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= active_menu('reports') ? 'show' : '' ?>" id="subReports">
            <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/sales_report.php">Sales Report</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/rental_report.php">Rental Report</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/recovery_report.php">Recovery Report</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/outstanding_report.php">Outstanding Report</a>
            <a class="nav-link" href="<?= BASE_URL ?>/pages/reports/income_expense_report.php">Income / Expense</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (has_permission('notifications.view')): ?>
    <div class="nav-item <?= active_menu('notifications') ?>">
        <a class="nav-link" href="<?= BASE_URL ?>/pages/notifications.php"><i class="bi bi-bell"></i>Notifications</a>
    </div>
    <?php endif; ?>

    <?php if (has_permission('master.view') || has_permission('settings.manage')): ?>
    <?php $system = submenu_state(['master', 'settings', 'users', 'roles']); ?>
    <div class="nav-item <?= $system ?>">
        <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subSystem">
            <i class="bi bi-gear"></i>System <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $system ? 'show' : '' ?>" id="subSystem">
            <?php if (has_permission('master.view')): ?>
            <div class="nav-item <?= active_menu('master') ?>">
                <a class="nav-link" href="#" data-toggle="nav-parent" data-target="#subMaster">
                    <i class="bi bi-journal-text"></i>Master Data <i class="bi bi-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= active_menu('master') ? 'show' : '' ?>" id="subMaster">
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
            <a class="nav-link <?= active_menu('settings') ?>" href="<?= BASE_URL ?>/pages/settings.php">Company Settings</a>
            <a class="nav-link <?= active_menu('users') ?>" href="<?= BASE_URL ?>/pages/users.php">Users</a>
            <a class="nav-link <?= active_menu('roles') ?>" href="<?= BASE_URL ?>/pages/roles.php">Roles &amp; Permissions</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="nav-item mt-3">
        <a class="nav-link" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a>
    </div>
</nav>
