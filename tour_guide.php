<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Guide | <?= e(setting('company_name', APP_NAME)) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background: #f4f6fb; }
    .guide-hero { background: linear-gradient(135deg, #2d6cdf 0%, #1d4ed8 60%, #16369e 100%); color: #fff; }
    .guide-hero .brand-icon { font-size: 40px; }
    .toc { position: sticky; top: 20px; }
    .toc a { display: block; padding: 6px 12px; border-radius: 8px; color: #334155; text-decoration: none; font-size: 13px; }
    .toc a:hover, .toc a.active { background: #e2e8f0; color: #1d4ed8; font-weight: 500; }
    .module-card { border: 1px solid #e2e8f0; border-radius: 14px; transition: box-shadow .15s; }
    .module-card:hover { box-shadow: 0 6px 20px rgba(29, 78, 216, .08); }
    .module-icon { width: 44px; height: 44px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; }
    .section-anchor { scroll-margin-top: 20px; }
    .list-check li { margin-bottom: 4px; }
    .login-box { background: #fff; border: 1px dashed #94a3b8; border-radius: 12px; }
</style>
</head>
<body>

<div class="guide-hero pb-4 pt-4">
    <div class="container">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="brand-icon"><i class="bi bi-building"></i></div>
            <div>
                <h3 class="fw-bold mb-0"><?= e(setting('company_name', APP_NAME)) ?> — User Guide</h3>
                <div class="opacity-75 small">Complete tour of the Real Estate ERP: har module kya karta hai, kaise use karein</div>
            </div>
            <div class="ms-auto">
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-light fw-medium"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="toc card p-2">
                <div class="fw-semibold text-muted px-2 py-1 small text-uppercase">On this page</div>
                <a href="#welcome">Welcome</a>
                <a href="#getting-started">Getting Started</a>
                <a href="#crm">CRM</a>
                <a href="#real-estate">Real Estate</a>
                <a href="#parties">Parties</a>
                <a href="#sales">Sales</a>
                <a href="#rentals">Rentals</a>
                <a href="#operations">Operations</a>
                <a href="#finance">Finance</a>
                <a href="#documents">Documents</a>
                <a href="#reports">Reports</a>
                <a href="#system">System</a>
                <a href="#tips">Tips &amp; Shortcuts</a>
                <a href="#login-info">Default Login</a>
            </div>
        </div>

        <div class="col-lg-9">
            <div id="welcome" class="section-anchor card module-card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold"><i class="bi bi-stars text-primary me-2"></i>Welcome</h5>
                    <p class="mb-2">Ye ek <strong>Real Estate ERP</strong> hai — property ka business chalane ke liye ek complete system. Isme aap leads se lekar sale/rental, receipts, aur poori accounting tak sab kuch manage kar sakte hain.</p>
                    <p class="mb-0 text-muted small">Har module users ki <strong>Roles &amp; Permissions</strong> ke hisaab se nazar aata hai — jo role ko permission di gayi hai wohi modules dikhte hain.</p>
                </div>
            </div>

            <div id="getting-started" class="section-anchor card module-card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold"><i class="bi bi-rocket-takeoff text-primary me-2"></i>Getting Started</h5>
                    <ol class="list-check mb-0">
                        <li><strong>Login</strong> — apna username/password daalein. (Default: <code>admin</code> / <code>admin123</code>)</li>
                        <li><strong>Dashboard</strong> — sab se pehle yahan aayenge. Top par cash &amp; bank balance, sales, aur aaj ki activities ke cards hain. Ye poori company ka at-a-glance summary hai.</li>
                        <li><strong>Sidebar menu</strong> — sab modules yahan hain. Bade groups (CRM, Sales, Finance waghera) pe click karne se unke pages khulte hain. Jo page aap par hain woh menu highlight hota hai.</li>
                        <li><strong>Search</strong> — har list page ke upar search box hai; usme type karte hi table filter hota hai.</li>
                    </ol>
                </div>
            </div>

            <div id="crm" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#0ea5e9"><i class="bi bi-funnel"></i></span><h6 class="fw-bold mb-0">CRM</h6></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Customers banane se pehle unhe leads ke roop me track karein.</p>
                    <ul class="list-check mb-0 small">
                        <li><strong>Leads</strong> — potential customers. Lead add karein, status update karein (new, contacted, interested...), follow-ups record karein.</li>
                        <li><strong>Call Logs</strong> — leads/customers se ki gayi calls ka record.</li>
                        <li><strong>Meetings</strong> — site visit / office meetings schedule karein.</li>
                        <li><strong>Tasks</strong> — team ko kaam assign karein aur complete karein.</li>
                    </ul>
                </div>
            </div>

            <div id="real-estate" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#6366f1"><i class="bi bi-grid-1x2"></i></span><h6 class="fw-bold mb-0">Real Estate</h6></div>
                <div class="card-body">
                    <ul class="list-check mb-0 small">
                        <li><strong>Projects</strong> — housing schemes (DHA, Bahria waghera). Project ke andar <em>Blocks → Roads → Streets</em> ka structure hai; cascading dropdowns khud aapko sahi options dete hain.</li>
                        <li><strong>Property Inventory</strong> — har plot/house/shop/office ka record: file no, size, price, status (<code>available</code>, <code>booked</code>, <code>sold</code>...). Property par photos aur documents attach kar sakte hain.</li>
                        <li><strong>Status</strong> — property ka status baqi system ke saath sync hota hai (booking hone par <em>booked</em>, cancel par <em>available</em>).</li>
                    </ul>
                </div>
            </div>

            <div id="parties" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#8b5cf6"><i class="bi bi-people"></i></span><h6 class="fw-bold mb-0">Parties</h6></div>
                <div class="card-body">
                    <ul class="list-check mb-0 small">
                        <li><strong>Customers</strong> — property kharidne wale. Har customer ka detail page par <em>Ledger</em> hai: bookings (debit) − receipts (credit) = outstanding. Ledger par date filter bhi lagta hai. Customer-to-customer balance transfer yahan adjust hota hai.</li>
                        <li><strong>Owners</strong> — property malik jo apni properties is system par bechte hain. Unka commission rate bhi yahan set hota hai.</li>
                        <li><strong>Dealers / Agents</strong> — sales me commission ke saath kaam karte hain. View page par unki sales aur commission payments record hoti hain.</li>
                        <li><strong>Vendors</strong> — suppliers (maal/khidmat dene wale). Unki payment history view page par rakhi jaati hai.</li>
                        <li><strong>General Party</strong> — kisi bhi aam party ka ledger. Detail page par 3 tarah ki entries hoti hain: <strong>Payable</strong> (ham par qarz) <em class="text-danger">+</em>, <strong>Paid</strong> <em class="text-danger">−</em>, <strong>Receiving</strong> <em class="text-danger">−</em>. Balance = Payable − Paid − Receiving. Har entry par journal voucher bhi banta hai (accounting me reflect hota hai).</li>
                    </ul>
                </div>
            </div>

            <div id="sales" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#10b981"><i class="bi bi-bag-check"></i></span><h6 class="fw-bold mb-0">Sales</h6></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Bechne ka poora flow, step by step:</p>
                    <ul class="list-check mb-0 small">
                        <li><strong>Quotations</strong> — customer ko price offer (quote).</li>
                        <li><strong>Bookings</strong> — customer ne property <em>book</em> kar li. Yahan se property ka status <code>booked</code> ho jata hai. Installment plan bhi ban jata hai.</li>
                        <li><strong>Sale Agreements</strong> — booked property ka final agreement.</li>
                        <li><strong>Installments</strong> — booking ke installment schedule aur unki paid/overdue status.</li>
                        <li><strong>Receipts</strong> — customer se payment lena. Payment method, bank, cheque reference sab yahan record hota hai aur customer ke ledger me credit aata hai.</li>
                    </ul>
                </div>
            </div>

            <div id="rentals" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#f59e0b"><i class="bi bi-house-heart"></i></span><h6 class="fw-bold mb-0">Rentals</h6></div>
                <div class="card-body">
                    <ul class="list-check mb-0 small">
                        <li><strong>Rental Agreements</strong> — property kiraya par dene ka contract; rent schedule (monthly due dates) auto banta hai.</li>
                        <li><strong>Tenants</strong> — kiraidar ka record (cnic, contact, address).</li>
                        <li><strong>Rent Collection</strong> — maahana kiraya wusooli; overdue rent yahan nazar aata hai.</li>
                        <li><strong>Owner Settlements</strong> — kiraye se malik ko hone wali settlement.</li>
                    </ul>
                </div>
            </div>

            <div id="operations" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#14b8a6"><i class="bi bi-tools"></i></span><h6 class="fw-bold mb-0">Operations</h6></div>
                <div class="card-body">
                    <ul class="list-check mb-0 small">
                        <li><strong>Utilities</strong> — electricity/gas/water connections. Meter readings aur monthly <em>utility bills</em> yahan banaye jate hain (bills generate karna).</li>
                        <li><strong>Maintenance</strong> — complaints record karein, technicians assign karein, aur maintenance tasks complete karein.</li>
                    </ul>
                </div>
            </div>

            <div id="finance" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#0d9488"><i class="bi bi-wallet2"></i></span><h6 class="fw-bold mb-0">Finance</h6></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Poori company ki accounting yahan hoti hai (double-entry):</p>
                    <ul class="list-check mb-0 small">
                        <li><strong>Chart of Accounts</strong> — har account (Cash, Bank, Expenses...) ka code yahan milta hai. Bank account har bank ke liye khud ban jata hai (<code>1001-xxx</code>).</li>
                        <li><strong>Vouchers</strong> — har accounting entry (receipt, payment, journal) yahan record hoti hai.</li>
                        <li><strong>Transfers / Withdrawals</strong> — paise ka transfer: bank→cash (withdraw), bank→bank, customer booking withdraw, owner/partner withdraw, aur customer-to-customer balance transfer.</li>
                        <li><strong>General Ledger</strong> — kisi bhi account ke andar aane wali sab entries (debit/credit).</li>
                        <li><strong>Trial Balance</strong> — saare accounts ka debit/credit summary; balancing check karne ke liye.</li>
                        <li><strong>Profit &amp; Loss</strong> — income vs expenses, net profit/loss.</li>
                        <li><strong>Balance Sheet</strong> — assets, liabilities, equity; financial position.</li>
                    </ul>
                </div>
            </div>

            <div id="documents" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#64748b"><i class="bi bi-folder2-open"></i></span><h6 class="fw-bold mb-0">Documents</h6></div>
                <div class="card-body">
                    <p class="small mb-0">Company ke important documents ka vault — agreements, approvals, files waghera yahan upload karke rakh sakte hain. Har document par title, type aur file attach hoti hai.</p>
                </div>
            </div>

            <div id="reports" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#ef4444"><i class="bi bi-bar-chart"></i></span><h6 class="fw-bold mb-0">Reports</h6></div>
                <div class="card-body">
                    <ul class="list-check mb-0 small">
                        <li><strong>Sales Report</strong> — period-wise sales summary.</li>
                        <li><strong>Rental Report</strong> — rental income summary.</li>
                        <li><strong>Recovery Report</strong> — kitna paisa wusool hua (receipts).</li>
                        <li><strong>Outstanding Report</strong> — kis customer ka kitna outstanding hai.</li>
                        <li><strong>Income / Expense</strong> — dono ka comparison.</li>
                    </ul>
                </div>
            </div>

            <div id="system" class="section-anchor card module-card mb-4">
                <div class="card-header bg-transparent d-flex align-items-center gap-2"><span class="module-icon" style="background:#334155"><i class="bi bi-gear"></i></span><h6 class="fw-bold mb-0">System</h6></div>
                <div class="card-body">
                    <ul class="list-check mb-0 small">
                        <li><strong>Master Data</strong> — base data: Countries, Cities, Areas, Societies, Property Types, Banks, Payment Methods, Expense/Income Categories, Document Types, Branches. Pehle yahan se data set karein taake baqi forms me options milen.</li>
                        <li><strong>Company Settings</strong> — company ka naam, address, phone, logo, currency.</li>
                        <li><strong>Users</strong> — login users banayein (naam, username, password, role).</li>
                        <li><strong>Roles &amp; Permissions</strong> — har role ko kaunse modules dekhne/manage karne ki ijazat hai. <em>Super Admin</em> sab kuch kar sakta hai.</li>
                    </ul>
                </div>
            </div>

            <div id="tips" class="section-anchor card module-card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold"><i class="bi bi-lightbulb text-warning me-2"></i>Tips &amp; Shortcuts</h5>
                    <ul class="list-check mb-0 small">
                        <li>Har list page ke upar <strong>search box</strong> hai — type karte hi filter ho jata hai.</li>
                        <li>Customer Ledger me <strong>date filter</strong> lagakar kisi waqfe ka balance dekh sakte hain.</li>
                        <li>Status sab jagah <strong>colored badges</strong> me dikhte hain (green = active/paid, red = overdue/cancelled, yellow = pending).</li>
                        <li>Numbers <strong>currency</strong> me dikhte hain (jo Company Settings me set hai).</li>
                        <li>Forms me <strong>auto numbers</strong> hain — blank chorhne par system khud number bana deta hai (jaise <code>PRP-0001</code>, <code>JV-0001</code>).</li>
                        <li>Koi ghalti ho jaye to records <strong>delete</strong> kiye ja sakte hain (agar system ijazat de).</li>
                    </ul>
                </div>
            </div>

            <div id="login-info" class="section-anchor">
                <div class="login-box p-4 text-center">
                    <h6 class="fw-bold mb-2"><i class="bi bi-key me-1"></i>Default Login</h6>
                    <div class="fs-5 fw-semibold">admin &nbsp;·&nbsp; admin123</div>
                    <div class="small text-muted mt-1 mb-3">Pehli dafa system install karne ke baad yahi login use hota hai.</div>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary px-4"><i class="bi bi-box-arrow-in-right me-1"></i>Login to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="py-4 text-center text-muted small">
    <?= e(setting('company_name', APP_NAME)) ?> — Real Estate ERP &nbsp;·&nbsp; <a href="<?= BASE_URL ?>/login.php">Login</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
