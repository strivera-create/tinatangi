<?php
session_start();

// ─── CONFIG ───
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'tinatangi2025');
define('PRODUCTS_FILE', 'products.json');
define('UPLOAD_DIR', 'uploads/');

// Ensure uploads dir exists
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// ─── AUTH ───
if (isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: admin-cms.php');
        exit;
    } else {
        $loginError = 'Invalid username or password.';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-cms.php');
    exit;
}

$isLoggedIn = !empty($_SESSION['admin']);

// ─── LOAD PRODUCTS ───
function loadProducts() {
    if (!file_exists(PRODUCTS_FILE)) return [];
    return json_decode(file_get_contents(PRODUCTS_FILE), true) ?? [];
}

function saveProducts($products) {
    file_put_contents(PRODUCTS_FILE, json_encode(array_values($products), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generateId() {
    return (string)time() . rand(100, 999);
}

// ─── HANDLE IMAGE UPLOAD ───
function handleImageUpload($field = 'image') {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES[$field];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) return null;
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) return null;
    $filename = uniqid('img_') . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) return $destination;
    return null;
}

// ─── CRUD ACTIONS (only if logged in) ───
$success = '';
$error = '';

if ($isLoggedIn) {
    $products = loadProducts();

    // ADD product
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $imgPath = handleImageUpload('image');
        if (!$imgPath && !empty($_POST['image_url'])) $imgPath = trim($_POST['image_url']);
        if (!$imgPath) $imgPath = 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&q=80';

        $newProduct = [
            'id'             => generateId(),
            'name'           => trim($_POST['name'] ?? ''),
            'price'          => trim($_POST['price'] ?? '0'),
            'original_price' => trim($_POST['original_price'] ?? ''),
            'category'       => trim($_POST['category'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'image'          => $imgPath,
            'badge'          => trim($_POST['badge'] ?? ''),
            'stock'          => (int)($_POST['stock'] ?? 0),
            'featured'       => isset($_POST['featured']),
        ];

        if (empty($newProduct['name'])) {
            $error = 'Product name is required.';
        } else {
            $products[] = $newProduct;
            saveProducts($products);
            $success = "Product \"{$newProduct['name']}\" added successfully!";
        }
    }

    // EDIT product
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        foreach ($products as &$p) {
            if ($p['id'] === $id) {
                $imgPath = handleImageUpload('image');
                if (!$imgPath && !empty($_POST['image_url'])) $imgPath = trim($_POST['image_url']);
                if (!$imgPath) $imgPath = $p['image'];

                $p['name']           = trim($_POST['name'] ?? $p['name']);
                $p['price']          = trim($_POST['price'] ?? $p['price']);
                $p['original_price'] = trim($_POST['original_price'] ?? '');
                $p['category']       = trim($_POST['category'] ?? $p['category']);
                $p['description']    = trim($_POST['description'] ?? $p['description']);
                $p['image']          = $imgPath;
                $p['badge']          = trim($_POST['badge'] ?? '');
                $p['stock']          = (int)($_POST['stock'] ?? $p['stock']);
                $p['featured']       = isset($_POST['featured']);
                break;
            }
        }
        unset($p);
        saveProducts($products);
        $success = "Product updated successfully!";
    }

    // DELETE product
    if (isset($_GET['delete'])) {
        $delId = $_GET['delete'];
        $products = array_filter($products, fn($p) => $p['id'] !== $delId);
        saveProducts($products);
        $success = "Product deleted.";
        $products = loadProducts();
    }

    // TOGGLE FEATURED
    if (isset($_GET['toggle_featured'])) {
        $tid = $_GET['toggle_featured'];
        foreach ($products as &$p) {
            if ($p['id'] === $tid) { $p['featured'] = !$p['featured']; break; }
        }
        unset($p);
        saveProducts($products);
        header('Location: admin-cms.php');
        exit;
    }

    $products = loadProducts();
}

// For edit modal
$editProduct = null;
if ($isLoggedIn && isset($_GET['edit'])) {
    $products = loadProducts();
    foreach ($products as $p) {
        if ($p['id'] === $_GET['edit']) { $editProduct = $p; break; }
    }
}

$products = $isLoggedIn ? loadProducts() : [];
$stats = [
    'total'    => count($products),
    'featured' => count(array_filter($products, fn($p) => !empty($p['featured']))),
    'lowStock' => count(array_filter($products, fn($p) => ($p['stock'] ?? 0) < 5)),
    'cats'     => count(array_unique(array_column($products, 'category'))),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin CMS – Tinatangi Consumer Goods</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
  :root {
    --cream: #FAF7F2;
    --bark: #2C1810;
    --clay: #8B4513;
    --terracotta: #C1704A;
    --sage: #7A8C6E;
    --gold: #C9A84C;
    --gold-light: #F0D896;
    --stone: #E8E0D5;
    --stone-dark: #C5B9A8;
    --text: #1A1209;
    --text-muted: #7A6E62;
    --danger: #E03E3E;
    --danger-light: #FDECEA;
    --success: #3A8A5C;
    --success-light: #EAF5EE;
    --sidebar-w: 260px;
    --r: 14px;
    --shadow: 0 4px 24px rgba(44,24,16,0.08);
    --transition: 0.25s ease;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--text); min-height: 100vh; }

  /* ─── LOGIN ─── */
  .login-page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .login-visual {
    background: var(--bark);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 60px;
    position: relative;
    overflow: hidden;
  }

  .login-visual::before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: var(--terracotta);
    opacity: 0.15;
    top: -200px;
    right: -200px;
  }

  .login-visual::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: var(--gold);
    opacity: 0.1;
    bottom: -150px;
    left: -100px;
  }

  .login-brand {
    text-align: center;
    position: relative;
    z-index: 1;
  }

  .login-brand-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 52px;
    font-weight: 600;
    color: white;
    letter-spacing: 2px;
  }

  .login-brand-sub {
    font-size: 11px;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--gold-light);
    margin-top: 6px;
  }

  .login-tagline {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-style: italic;
    color: rgba(255,255,255,0.5);
    margin-top: 40px;
  }

  .login-form-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px;
    background: white;
  }

  .login-form-inner {
    width: 100%;
    max-width: 420px;
  }

  .login-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 600;
    color: var(--bark);
    margin-bottom: 8px;
  }

  .login-subtitle {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 40px;
    font-weight: 300;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  .form-control {
    width: 100%;
    padding: 14px 18px;
    border: 1.5px solid var(--stone);
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--text);
    background: white;
    transition: border-color 0.2s;
    outline: none;
  }

  .form-control:focus { border-color: var(--terracotta); }

  .form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237A6E62' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
  }

  textarea.form-control { resize: vertical; min-height: 90px; }

  .alert {
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
  }

  .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #f5c5c5; }
  .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #b8dfc8; }

  /* ─── ADMIN LAYOUT ─── */
  .admin-layout {
    display: flex;
    min-height: 100vh;
  }

  /* ─── SIDEBAR ─── */
  .sidebar {
    width: var(--sidebar-w);
    background: var(--bark);
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 50;
  }

  .sidebar-brand {
    padding: 32px 28px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }

  .sidebar-brand-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    color: white;
    letter-spacing: 0.5px;
  }

  .sidebar-brand-sub {
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin-top: 2px;
  }

  .sidebar-badge {
    display: inline-block;
    background: var(--terracotta);
    color: white;
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 50px;
    margin-top: 10px;
  }

  .sidebar-nav {
    padding: 24px 16px;
    flex: 1;
    overflow-y: auto;
  }

  .sidebar-nav-label {
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.3);
    padding: 0 12px;
    margin-bottom: 8px;
    margin-top: 20px;
  }

  .sidebar-nav-label:first-child { margin-top: 0; }

  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 10px;
    text-decoration: none;
    color: rgba(255,255,255,0.6);
    font-size: 13px;
    font-weight: 400;
    transition: all 0.2s;
    margin-bottom: 2px;
  }

  .sidebar-link:hover, .sidebar-link.active {
    background: rgba(255,255,255,0.1);
    color: white;
  }

  .sidebar-link.active { background: var(--terracotta); color: white; }

  .sidebar-link svg { width: 16px; height: 16px; flex-shrink: 0; }

  .sidebar-footer {
    padding: 20px 24px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }

  .sidebar-admin-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }

  .sidebar-avatar {
    width: 38px;
    height: 38px;
    background: var(--terracotta);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
  }

  .sidebar-admin-name { font-size: 13px; color: white; font-weight: 500; }
  .sidebar-admin-role { font-size: 11px; color: rgba(255,255,255,0.4); }

  .logout-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 16px;
    border-radius: 8px;
    background: rgba(255,255,255,0.06);
    border: none;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    transition: all 0.2s;
  }

  .logout-btn:hover { background: rgba(224,62,62,0.2); color: #f09090; }

  /* ─── MAIN CONTENT ─── */
  .admin-main {
    margin-left: var(--sidebar-w);
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  .admin-topbar {
    background: white;
    border-bottom: 1px solid var(--stone);
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 40;
  }

  .admin-topbar-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px;
    font-weight: 600;
    color: var(--bark);
  }

  .admin-topbar-sub {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
  }

  .admin-topbar-actions {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .admin-content {
    padding: 40px;
    flex: 1;
  }

  /* ─── STATS GRID ─── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
  }

  .stat-card {
    background: white;
    border-radius: var(--r);
    padding: 24px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
  }

  .stat-icon.bg-terracotta { background: rgba(193,112,74,0.12); }
  .stat-icon.bg-sage { background: rgba(122,140,110,0.12); }
  .stat-icon.bg-gold { background: rgba(201,168,76,0.12); }
  .stat-icon.bg-danger { background: rgba(224,62,62,0.10); }

  .stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 600;
    color: var(--bark);
    line-height: 1;
  }

  .stat-label {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 4px;
  }

  /* ─── TABLE CARD ─── */
  .card {
    background: white;
    border-radius: var(--r);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 32px;
  }

  .card-header {
    padding: 24px 28px;
    border-bottom: 1px solid var(--stone);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    color: var(--bark);
  }

  .card-body { padding: 0; }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }

  thead th {
    background: var(--cream);
    padding: 14px 20px;
    text-align: left;
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: 500;
    border-bottom: 1px solid var(--stone);
  }

  tbody tr {
    border-bottom: 1px solid var(--stone);
    transition: background 0.15s;
  }

  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: var(--cream); }

  td {
    padding: 16px 20px;
    vertical-align: middle;
  }

  .table-product-img {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    background: var(--stone);
  }

  .table-product-name {
    font-weight: 500;
    color: var(--bark);
    margin-bottom: 2px;
  }

  .table-product-cat {
    font-size: 11px;
    color: var(--text-muted);
    letter-spacing: 0.5px;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 500;
  }

  .badge-sale { background: rgba(193,112,74,0.1); color: var(--terracotta); }
  .badge-new { background: rgba(122,140,110,0.1); color: var(--sage); }
  .badge-bestseller { background: rgba(201,168,76,0.1); color: var(--clay); }
  .badge-artisan { background: rgba(44,24,16,0.08); color: var(--bark); }
  .badge-empty { background: var(--stone); color: var(--text-muted); }

  .featured-toggle {
    width: 40px;
    height: 22px;
    background: var(--stone);
    border-radius: 50px;
    position: relative;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    display: inline-block;
  }

  .featured-toggle::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    background: white;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    transition: left 0.2s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
  }

  .featured-toggle.on { background: var(--sage); }
  .featured-toggle.on::after { left: 21px; }

  .stock-badge {
    font-size: 12px;
    font-weight: 500;
  }

  .stock-low { color: var(--danger); }
  .stock-ok { color: var(--success); }

  .action-btns { display: flex; gap: 8px; }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.2s;
  }

  .btn-sm { padding: 6px 12px; font-size: 11px; }

  .btn-primary { background: var(--bark); color: white; }
  .btn-primary:hover { background: var(--terracotta); }
  .btn-outline { background: white; color: var(--bark); border: 1.5px solid var(--stone); }
  .btn-outline:hover { border-color: var(--bark); }
  .btn-danger { background: var(--danger-light); color: var(--danger); }
  .btn-danger:hover { background: var(--danger); color: white; }
  .btn-gold { background: var(--gold); color: var(--bark); }
  .btn-gold:hover { background: var(--clay); color: white; }

  /* ─── MODAL ─── */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(4px);
  }

  .modal {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 24px 80px rgba(0,0,0,0.2);
    animation: modalIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  }

  @keyframes modalIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }

  .modal-header {
    padding: 28px 32px 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .modal-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 600;
    color: var(--bark);
  }

  .modal-close {
    background: var(--stone);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
  }

  .modal-close:hover { background: var(--danger); color: white; }

  .modal-body { padding: 24px 32px 32px; }

  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .form-grid .form-group.full { grid-column: 1 / -1; }

  /* ─── IMAGE UPLOAD ─── */
  .img-upload-area {
    border: 2px dashed var(--stone-dark);
    border-radius: 12px;
    padding: 28px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
  }

  .img-upload-area:hover { border-color: var(--terracotta); background: rgba(193,112,74,0.03); }

  .img-upload-icon { font-size: 32px; margin-bottom: 8px; }
  .img-upload-text { font-size: 13px; color: var(--text-muted); }
  .img-upload-text strong { color: var(--terracotta); }

  .img-preview-wrap {
    margin-top: 12px;
    position: relative;
    display: inline-block;
  }

  .img-preview {
    width: 100%;
    max-width: 200px;
    height: 140px;
    object-fit: cover;
    border-radius: 10px;
    display: none;
  }

  .form-row { display: flex; gap: 12px; align-items: center; }
  .form-row .form-group { flex: 1; margin-bottom: 0; }
  .or-divider {
    font-size: 11px;
    color: var(--stone-dark);
    text-transform: uppercase;
    letter-spacing: 1px;
    white-space: nowrap;
    padding-top: 24px;
  }

  /* ─── DELETE CONFIRM ─── */
  .delete-confirm {
    text-align: center;
    padding: 20px 0;
  }

  .delete-confirm-icon { font-size: 48px; margin-bottom: 16px; }

  /* ─── EMPTY STATE ─── */
  .empty-state {
    text-align: center;
    padding: 80px 40px;
  }

  .empty-state-icon { font-size: 48px; margin-bottom: 16px; }

  .empty-state h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    color: var(--bark);
    margin-bottom: 8px;
  }

  .empty-state p { color: var(--text-muted); font-size: 14px; margin-bottom: 24px; }

  /* ─── RESPONSIVE ─── */
  @media (max-width: 1024px) {
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .admin-content { padding: 24px; }
    .admin-topbar { padding: 16px 24px; }
  }

  @media (max-width: 768px) {
    .login-page { grid-template-columns: 1fr; }
    .login-visual { display: none; }
    .sidebar { transform: translateX(-100%); }
    .admin-main { margin-left: 0; }
  }

  /* Search bar in topbar */
  .topbar-search {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--cream);
    border: 1.5px solid var(--stone);
    border-radius: 50px;
    padding: 8px 18px;
    transition: border-color 0.2s;
  }

  .topbar-search:focus-within { border-color: var(--terracotta); }

  .topbar-search input {
    border: none;
    background: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    outline: none;
    width: 200px;
    color: var(--text);
  }

  .checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
  }

  .checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--terracotta);
    cursor: pointer;
  }

  .checkbox-group label {
    font-size: 13px;
    color: var(--text);
    cursor: pointer;
  }
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ─── LOGIN PAGE ─── -->
<div class="login-page">
  <div class="login-visual">
    <div class="login-brand">
      <div class="login-brand-name">Tinatangi</div>
      <div class="login-brand-sub">Consumer Goods</div>
      <div class="login-tagline">"Crafted with intention & heart"</div>
    </div>
  </div>
  <div class="login-form-wrap">
    <div class="login-form-inner">
      <h1 class="login-title">Admin Portal</h1>
      <p class="login-subtitle">Sign in to manage your store's products and content.</p>
      <?php if (!empty($loginError)): ?>
      <div class="alert alert-error">⚠ <?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>
      <form method="POST" action="admin-cms.php">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary" style="width:100%;padding:16px;font-size:14px;border-radius:10px;justify-content:center;margin-top:8px;">
          Sign In →
        </button>
      </form>
      <p style="text-align:center;margin-top:24px;font-size:12px;color:var(--text-muted)">
        Default: <strong>admin</strong> / <strong>tinatangi2025</strong>
      </p>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ─── ADMIN LAYOUT ─── -->
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-name">Tinatangi</div>
      <div class="sidebar-brand-sub">Consumer Goods</div>
      <div class="sidebar-badge">Admin CMS</div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-nav-label">Main</div>
      <a href="admin-cms.php" class="sidebar-link active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <div class="sidebar-nav-label">Catalog</div>
      <a href="#" onclick="openAddModal()" class="sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
      </a>
      <a href="admin-cms.php" class="sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><polyline points="16 2 12 6 8 2"/></svg>
        All Products
      </a>
      <div class="sidebar-nav-label">Site</div>
      <a href="index.php" target="_blank" class="sidebar-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        View Store
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-admin-info">
        <div class="sidebar-avatar">A</div>
        <div>
          <div class="sidebar-admin-name">Administrator</div>
          <div class="sidebar-admin-role">Full Access</div>
        </div>
      </div>
      <a href="?logout" class="logout-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign Out
      </a>
    </div>
  </aside>

  <!-- Main -->
  <main class="admin-main">
    <!-- Top Bar -->
    <div class="admin-topbar">
      <div>
        <div class="admin-topbar-title">Product Manager</div>
        <div class="admin-topbar-sub">Manage your store catalog</div>
      </div>
      <div class="admin-topbar-actions">
        <a href="index.php" target="_blank" class="btn btn-outline btn-sm">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          View Store
        </a>
        <button class="btn btn-primary btn-sm" onclick="openAddModal()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Product
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="admin-content">

      <!-- Alerts -->
      <?php if ($success): ?>
      <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon bg-terracotta">📦</div>
          <div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Products</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-sage">⭐</div>
          <div>
            <div class="stat-value"><?= $stats['featured'] ?></div>
            <div class="stat-label">Featured</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-gold">🗂</div>
          <div>
            <div class="stat-value"><?= $stats['cats'] ?></div>
            <div class="stat-label">Categories</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-danger">⚠️</div>
          <div>
            <div class="stat-value"><?= $stats['lowStock'] ?></div>
            <div class="stat-label">Low Stock</div>
          </div>
        </div>
      </div>

      <!-- Products Table -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">All Products (<?= count($products) ?>)</div>
          <button class="btn btn-gold btn-sm" onclick="openAddModal()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Product
          </button>
        </div>
        <div class="card-body">
          <?php if (empty($products)): ?>
          <div class="empty-state">
            <div class="empty-state-icon">🌿</div>
            <h3>No products yet</h3>
            <p>Start building your catalog by adding your first product.</p>
            <button class="btn btn-primary" onclick="openAddModal()">Add First Product</button>
          </div>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Badge</th>
                <th>Stock</th>
                <th>Featured</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:14px">
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="table-product-img" onerror="this.src='https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=100&q=60'">
                    <div>
                      <div class="table-product-name"><?= htmlspecialchars($p['name']) ?></div>
                      <div class="table-product-cat"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 50, '…')) ?></div>
                    </div>
                  </div>
                </td>
                <td style="color:var(--text-muted);font-size:12px"><?= htmlspecialchars($p['category']) ?></td>
                <td>
                  <div style="font-weight:500;color:var(--bark)">₱<?= htmlspecialchars($p['price']) ?></div>
                  <?php if (!empty($p['original_price'])): ?>
                  <div style="font-size:11px;text-decoration:line-through;color:var(--stone-dark)">₱<?= htmlspecialchars($p['original_price']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $b = strtolower($p['badge'] ?? '');
                    $cls = match($b) {
                      'sale' => 'sale', 'new' => 'new',
                      'bestseller' => 'bestseller', 'artisan' => 'artisan',
                      default => 'empty'
                    };
                  ?>
                  <span class="badge badge-<?= $cls ?>"><?= $p['badge'] ?: '—' ?></span>
                </td>
                <td>
                  <span class="stock-badge <?= ($p['stock'] ?? 0) < 5 ? 'stock-low' : 'stock-ok' ?>">
                    <?= ($p['stock'] ?? 0) < 5 ? '⚠ ' : '✓ ' ?><?= (int)($p['stock'] ?? 0) ?> units
                  </span>
                </td>
                <td>
                  <a href="?toggle_featured=<?= urlencode($p['id']) ?>" class="featured-toggle <?= !empty($p['featured']) ? 'on' : '' ?>" title="Toggle featured"></a>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($p) ?>)'>
                      ✏ Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick='openDeleteModal("<?= htmlspecialchars($p['id']) ?>","<?= htmlspecialchars(addslashes($p['name'])) ?>")'>
                      🗑
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ─── ADD/EDIT MODAL ─── -->
<div class="modal-overlay" id="productModal" style="display:none" onclick="closeModalOnOverlay(event)">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modalTitle">Add New Product</h2>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="admin-cms.php" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="">

        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" id="f_name" class="form-control" placeholder="e.g. Artisan Coconut Soap" required>
          </div>
          <div class="form-group">
            <label class="form-label">Price (₱) *</label>
            <input type="number" name="price" id="f_price" class="form-control" placeholder="299.00" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Original Price (₱)</label>
            <input type="number" name="original_price" id="f_orig_price" class="form-control" placeholder="399.00 (optional)" step="0.01">
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <input type="text" name="category" id="f_category" class="form-control" placeholder="e.g. Skincare" list="categoryList">
            <datalist id="categoryList">
              <?php foreach (array_unique(array_column($products, 'category')) as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="form-group">
            <label class="form-label">Badge</label>
            <select name="badge" id="f_badge" class="form-control form-select">
              <option value="">None</option>
              <option value="New">New</option>
              <option value="Bestseller">Bestseller</option>
              <option value="Sale">Sale</option>
              <option value="Artisan">Artisan</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Stock Quantity</label>
            <input type="number" name="stock" id="f_stock" class="form-control" placeholder="50" min="0">
          </div>
          <div class="form-group full">
            <label class="form-label">Description</label>
            <textarea name="description" id="f_description" class="form-control" placeholder="Describe the product…"></textarea>
          </div>

          <!-- Image Upload -->
          <div class="form-group full">
            <label class="form-label">Product Image</label>
            <div class="img-upload-area" onclick="document.getElementById('f_image_file').click()">
              <div class="img-upload-icon">🖼</div>
              <div class="img-upload-text"><strong>Click to upload</strong> or drag & drop<br><span style="font-size:11px">JPG, PNG, WEBP up to 5MB</span></div>
            </div>
            <input type="file" name="image" id="f_image_file" accept="image/*" style="display:none" onchange="previewImage(this)">
            <div class="img-preview-wrap" style="text-align:center;width:100%;margin-top:12px">
              <img id="imgPreview" class="img-preview" src="" alt="Preview">
            </div>
          </div>
          <div class="form-group full">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Or paste Image URL</label>
                <input type="text" name="image_url" id="f_image_url" class="form-control" placeholder="https://…" oninput="previewUrl(this.value)">
              </div>
            </div>
          </div>

          <div class="form-group full">
            <div class="checkbox-group">
              <input type="checkbox" name="featured" id="f_featured">
              <label for="f_featured">Mark as Featured Product (shown on hero & banners)</label>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px">
          <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;padding:14px">
            Save Product
          </button>
          <button type="button" class="btn btn-outline" onclick="closeModal()" style="padding:14px 24px">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ─── DELETE CONFIRM MODAL ─── -->
<div class="modal-overlay" id="deleteModal" style="display:none" onclick="closeDeleteOnOverlay(event)">
  <div class="modal" style="max-width:400px">
    <div class="modal-body" style="padding:40px 32px;text-align:center">
      <div style="font-size:52px;margin-bottom:16px">🗑️</div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--bark);margin-bottom:8px">Delete Product?</h2>
      <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px">
        You are about to delete <strong id="deleteProductName"></strong>. This action cannot be undone.
      </p>
      <div style="display:flex;gap:12px;justify-content:center">
        <a id="deleteConfirmBtn" href="#" class="btn btn-danger" style="padding:12px 28px">
          Yes, Delete
        </a>
        <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline" style="padding:12px 24px">
          Cancel
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add New Product';
  document.getElementById('formAction').value = 'add';
  document.getElementById('formId').value = '';
  document.getElementById('productForm').reset();
  document.getElementById('imgPreview').style.display = 'none';
  document.getElementById('productModal').style.display = 'flex';
}

function openEditModal(p) {
  document.getElementById('modalTitle').textContent = 'Edit Product';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('formId').value = p.id;
  document.getElementById('f_name').value = p.name || '';
  document.getElementById('f_price').value = p.price || '';
  document.getElementById('f_orig_price').value = p.original_price || '';
  document.getElementById('f_category').value = p.category || '';
  document.getElementById('f_badge').value = p.badge || '';
  document.getElementById('f_stock').value = p.stock || 0;
  document.getElementById('f_description').value = p.description || '';
  document.getElementById('f_image_url').value = p.image || '';
  document.getElementById('f_featured').checked = p.featured == true || p.featured == 1;
  const prev = document.getElementById('imgPreview');
  if (p.image) { prev.src = p.image; prev.style.display = 'block'; }
  else { prev.style.display = 'none'; }
  document.getElementById('productModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('productModal').style.display = 'none';
}

function closeModalOnOverlay(e) {
  if (e.target === document.getElementById('productModal')) closeModal();
}

function openDeleteModal(id, name) {
  document.getElementById('deleteProductName').textContent = '"' + name + '"';
  document.getElementById('deleteConfirmBtn').href = 'admin-cms.php?delete=' + id;
  document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteOnOverlay(e) {
  if (e.target === document.getElementById('deleteModal')) {
    document.getElementById('deleteModal').style.display = 'none';
  }
}

function previewImage(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const prev = document.getElementById('imgPreview');
    prev.src = e.target.result;
    prev.style.display = 'block';
    document.getElementById('f_image_url').value = '';
  };
  reader.readAsDataURL(input.files[0]);
}

function previewUrl(url) {
  if (!url) return;
  const prev = document.getElementById('imgPreview');
  prev.src = url;
  prev.style.display = 'block';
}

// Auto-close alerts
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.transition = 'opacity 0.5s';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 500);
  });
}, 4000);

// Open edit modal from URL
<?php if ($editProduct): ?>
openEditModal(<?= json_encode($editProduct) ?>);
<?php endif; ?>
</script>

<?php endif; ?>
</body>
</html>
