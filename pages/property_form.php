<?php
require_once '../includes/auth.php';
require_login();
require_permission('properties.manage');
$title = 'Property Form';
$active = 'properties';

$id = (int)($_GET['id'] ?? 0);
$record = $id ? db_get("SELECT * FROM properties WHERE id = ?", [$id]) : null;
$isNew = !$record;

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $property_no = trim($_POST['property_no'] ?? '');
    $file_no = trim($_POST['file_no'] ?? '');
    $plot_no = trim($_POST['plot_no'] ?? '');
    $house_no = trim($_POST['house_no'] ?? '');
    $apartment_no = trim($_POST['apartment_no'] ?? '');
    $office_no = trim($_POST['office_no'] ?? '');
    $shop_no = trim($_POST['shop_no'] ?? '');
    $warehouse_no = trim($_POST['warehouse_no'] ?? '');
    $factory_no = trim($_POST['factory_no'] ?? '');
    $farm_house_no = trim($_POST['farm_house_no'] ?? '');
    $hall_no = trim($_POST['hall_no'] ?? '');
    $project_id = $id > 0 ? (int)($record['project_id'] ?? 0) : active_project_id();
    $project_id = $project_id ?: null;
    $block_id = (int)($_POST['block_id'] ?? 0) ?: null;
    $road_id = (int)($_POST['road_id'] ?? 0) ?: null;
    $street_id = (int)($_POST['street_id'] ?? 0) ?: null;
    $property_type_id = (int)($_POST['property_type_id'] ?? 0) ?: null;
    $property_category_id = (int)($_POST['property_category_id'] ?? 0) ?: null;
    $owner_id = (int)($_POST['owner_id'] ?? 0) ?: null;
    $customer_id = (int)($_POST['customer_id'] ?? 0) ?: null;
    $size_value = (float)($_POST['size_value'] ?? 0);
    $size_unit = $_POST['size_unit'] ?? 'marla';
    $status = $_POST['status'] ?? 'available';
    $corner = isset($_POST['corner']) ? 1 : 0;
    $main_boulevard = isset($_POST['main_boulevard']) ? 1 : 0;
    $park_facing = isset($_POST['park_facing']) ? 1 : 0;
    $sale_price = (float)($_POST['sale_price'] ?? 0);
    $rent_amount = (float)($_POST['rent_amount'] ?? 0);
    $extra_charges = (float)($_POST['extra_charges'] ?? 0);
    $possession_status = $_POST['possession_status'] ?? 'pending';
    $possession_date = $_POST['possession_date'] !== '' ? $_POST['possession_date'] : null;
    $description = trim($_POST['description'] ?? '');

    if ($property_no === '') {
        $property_no = next_number('PRP', 'properties', 'property_no');
    }

    $dup = db_get("SELECT id FROM properties WHERE property_no = ? AND id <> ?", [$property_no, $id]);
    if ($dup) {
        flash('danger', 'Property number already exists.');
    } elseif ($id > 0) {
        db_exec("UPDATE properties SET property_no=?, file_no=?, plot_no=?, house_no=?, apartment_no=?, office_no=?, shop_no=?, warehouse_no=?, factory_no=?, farm_house_no=?, hall_no=?, project_id=?, block_id=?, road_id=?, street_id=?, property_type_id=?, property_category_id=?, owner_id=?, customer_id=?, size_value=?, size_unit=?, status=?, corner=?, main_boulevard=?, park_facing=?, sale_price=?, rent_amount=?, extra_charges=?, possession_status=?, possession_date=?, description=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$property_no, $file_no, $plot_no, $house_no, $apartment_no, $office_no, $shop_no, $warehouse_no, $factory_no, $farm_house_no, $hall_no, $project_id, $block_id, $road_id, $street_id, $property_type_id, $property_category_id, $owner_id, $customer_id, $size_value, $size_unit, $status, $corner, $main_boulevard, $park_facing, $sale_price, $rent_amount, $extra_charges, $possession_status, $possession_date, $description, $id]);
        flash('success', 'Property updated successfully.');
        redirect('property_view.php?id=' . $id);
    } else {
        db_exec("INSERT INTO properties (property_no, file_no, plot_no, house_no, apartment_no, office_no, shop_no, warehouse_no, factory_no, farm_house_no, hall_no, project_id, block_id, road_id, street_id, property_type_id, property_category_id, owner_id, customer_id, size_value, size_unit, status, corner, main_boulevard, park_facing, sale_price, rent_amount, extra_charges, possession_status, possession_date, description, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$property_no, $file_no, $plot_no, $house_no, $apartment_no, $office_no, $shop_no, $warehouse_no, $factory_no, $farm_house_no, $hall_no, $project_id, $block_id, $road_id, $street_id, $property_type_id, $property_category_id, $owner_id, $customer_id, $size_value, $size_unit, $status, $corner, $main_boulevard, $park_facing, $sale_price, $rent_amount, $extra_charges, $possession_status, $possession_date, $description]);
        flash('success', 'Property added successfully.');
        redirect('properties.php');
    }
}

$projects = db_all("SELECT * FROM projects ORDER BY name");
$types = db_all("SELECT * FROM property_types ORDER BY name");
$categories = db_all("SELECT * FROM property_categories ORDER BY name");
$owners = db_all("SELECT * FROM owners ORDER BY full_name");
$customers = db_all("SELECT * FROM customers ORDER BY full_name");

$selProject = $record['project_id'] ?? active_project_id();
$selProject = (int)$selProject;
$blocks = $selProject ? db_all("SELECT * FROM blocks WHERE project_id = ? ORDER BY name", [$selProject]) : [];
$roads = $selProject ? db_all("SELECT * FROM roads WHERE project_id = ? ORDER BY name", [$selProject]) : [];
$streets = $selProject ? db_all("SELECT * FROM streets WHERE project_id = ? ORDER BY name", [$selProject]) : [];

include '../includes/header.php';
?>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="properties.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0"><?= $isNew ? 'Add Property' : 'Edit Property: ' . e($record['property_no']) ?></h5>
        <div class="ms-auto">
            <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Property</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-house-door me-2"></i>Identification</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Property ID</label>
                            <input type="text" name="property_no" class="form-control" value="<?= e($record['property_no'] ?? '') ?>" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">File Number</label>
                            <input type="text" name="file_no" class="form-control" value="<?= e($record['file_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Plot Number</label>
                            <input type="text" name="plot_no" class="form-control" value="<?= e($record['plot_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">House Number</label>
                            <input type="text" name="house_no" class="form-control" value="<?= e($record['house_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apartment Number</label>
                            <input type="text" name="apartment_no" class="form-control" value="<?= e($record['apartment_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Office Number</label>
                            <input type="text" name="office_no" class="form-control" value="<?= e($record['office_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shop Number</label>
                            <input type="text" name="shop_no" class="form-control" value="<?= e($record['shop_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Warehouse Number</label>
                            <input type="text" name="warehouse_no" class="form-control" value="<?= e($record['warehouse_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Factory Number</label>
                            <input type="text" name="factory_no" class="form-control" value="<?= e($record['factory_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Farm House Number</label>
                            <input type="text" name="farm_house_no" class="form-control" value="<?= e($record['farm_house_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Commercial Hall Number</label>
                            <input type="text" name="hall_no" class="form-control" value="<?= e($record['hall_no'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Location</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select" id="projectSelect" disabled>
                                <option value="">Select project</option>
                                <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $selProject === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                            </select>
                            <div class="form-text">Locked to the active project selected in the header.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Block</label>
                            <select name="block_id" class="form-select" id="blockSelect">
                                <option value="">Select</option>
                                <?php foreach ($blocks as $b): ?><option value="<?= $b['id'] ?>" <?= (int)($record['block_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Road</label>
                            <select name="road_id" class="form-select" id="roadSelect">
                                <option value="">Select</option>
                                <?php foreach ($roads as $b): ?><option value="<?= $b['id'] ?>" <?= (int)($record['road_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Street</label>
                            <select name="street_id" class="form-select" id="streetSelect">
                                <option value="">Select</option>
                                <?php foreach ($streets as $b): ?><option value="<?= $b['id'] ?>" <?= (int)($record['street_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Property Type</label>
                            <select name="property_type_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= (int)($record['property_type_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Property Category</label>
                            <select name="property_category_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($categories as $t): ?><option value="<?= $t['id'] ?>" <?= (int)($record['property_category_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-rulers me-2"></i>Size &amp; Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Size</label>
                            <input type="number" step="0.01" name="size_value" class="form-control" value="<?= e($record['size_value'] ?? 0) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Size Unit</label>
                            <select name="size_unit" class="form-select">
                                <?php foreach (['marla' => 'Marla', 'kanal' => 'Kanal', 'sqft' => 'Square Feet', 'sqy' => 'Square Yard'] as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($record['size_unit'] ?? 'marla') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['available', 'booked', 'reserved', 'sold', 'transferred', 'rental', 'occupied', 'vacant'] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($record['status'] ?? 'available') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sale Price</label>
                            <input type="number" step="0.01" name="sale_price" class="form-control" value="<?= e($record['sale_price'] ?? 0) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rent Amount</label>
                            <input type="number" step="0.01" name="rent_amount" class="form-control" value="<?= e($record['rent_amount'] ?? 0) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Extra Charges</label>
                            <input type="number" step="0.01" name="extra_charges" class="form-control" value="<?= e($record['extra_charges'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Possession Status</label>
                            <select name="possession_status" class="form-select">
                                <?php foreach (['pending', 'in_progress', 'completed'] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($record['possession_status'] ?? 'pending') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Possession Date</label>
                            <input type="date" name="possession_date" class="form-control" value="<?= e($record['possession_date'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= e($record['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-pin-angle me-2"></i>Features</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="corner" id="fCorner" <?= !empty($record['corner']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="fCorner">Corner Plot</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="main_boulevard" id="fBoulevard" <?= !empty($record['main_boulevard']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="fBoulevard">Main Boulevard</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="park_facing" id="fPark" <?= !empty($record['park_facing']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="fPark">Park Facing</label>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person-badge me-2"></i>Ownership</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($owners as $o): ?><option value="<?= $o['id'] ?>" <?= (int)($record['owner_id'] ?? 0) === (int)$o['id'] ? 'selected' : '' ?>><?= e($o['full_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Booked By Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($customers as $o): ?><option value="<?= $o['id'] ?>" <?= (int)($record['customer_id'] ?? 0) === (int)$o['id'] ? 'selected' : '' ?>><?= e($o['full_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button class="btn btn-primary btn-lg"><i class="bi bi-check-lg me-1"></i>Save Property</button>
    </div>
</form>

<script>
$(function () {
    $('#projectSelect').on('change', function () {
        var pid = $(this).val();
        $.getJSON('ajax.php', { action: 'blocks', id: pid }, function (data) {
            $('#blockSelect').html('<option value="">Select</option>' + data.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join(''));
        });
        $.getJSON('ajax.php', { action: 'roads', id: pid }, function (data) {
            $('#roadSelect').html('<option value="">Select</option>' + data.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join(''));
        });
        $.getJSON('ajax.php', { action: 'streets', id: pid }, function (data) {
            $('#streetSelect').html('<option value="">Select</option>' + data.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join(''));
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
