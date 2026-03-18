<?php
// Load products
$productsFile = 'products.json';
$products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) : [];
$categories = array_unique(array_column($products, 'category'));
$featuredProducts = array_filter($products, fn($p) => !empty($p['featured']));

// Filter by category
$activeCategory = $_GET['category'] ?? 'All';
$filteredProducts = $activeCategory === 'All' ? $products : array_filter($products, fn($p) => $p['category'] === $activeCategory);

// Search
$search = $_GET['search'] ?? '';
if ($search) {
    $filteredProducts = array_filter($filteredProducts, fn($p) => stripos($p['name'], $search) !== false || stripos($p['description'], $search) !== false);
}

$filteredProducts = array_values($filteredProducts);
$heroProduct = $featuredProducts ? array_values($featuredProducts)[0] : ($products[0] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tinatangi Consumer Goods – Crafted for You</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
<style>
  :root {
    --cream: #FAF7F2;
    --warm-white: #FFFDF9;
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
    --card-bg: #FFFFFF;
    --nav-h: 80px;
    --r: 16px;
    --shadow: 0 8px 40px rgba(44,24,16,0.08);
    --shadow-hover: 0 20px 60px rgba(44,24,16,0.15);
    --transition: 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--text);
    overflow-x: hidden;
  }

  /* ─── TOP BAR ─── */
  .topbar {
    background: var(--bark);
    color: var(--gold-light);
    text-align: center;
    padding: 10px;
    font-size: 12px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 300;
  }

  /* ─── NAV ─── */
  nav {
    position: sticky;
    top: 0;
    z-index: 100;
    height: var(--nav-h);
    background: rgba(250,247,242,0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--stone);
    display: flex;
    align-items: center;
    padding: 0 48px;
    gap: 40px;
  }

  .nav-logo {
    display: flex;
    flex-direction: column;
    text-decoration: none;
    margin-right: auto;
  }

  .nav-logo-main {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    color: var(--bark);
    letter-spacing: 1px;
    line-height: 1;
  }

  .nav-logo-sub {
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--terracotta);
    font-weight: 400;
    margin-top: 2px;
  }

  .nav-links {
    display: flex;
    gap: 32px;
    list-style: none;
  }

  .nav-links a {
    text-decoration: none;
    font-size: 13px;
    font-weight: 400;
    color: var(--text-muted);
    letter-spacing: 0.5px;
    transition: color 0.2s;
    position: relative;
  }

  .nav-links a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0; right: 0;
    height: 1px;
    background: var(--terracotta);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s;
  }

  .nav-links a:hover { color: var(--bark); }
  .nav-links a:hover::after { transform: scaleX(1); }

  .nav-actions {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .nav-search {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--stone);
    border-radius: 50px;
    padding: 8px 16px;
    border: 1px solid transparent;
    transition: border-color 0.2s;
  }

  .nav-search:focus-within {
    border-color: var(--terracotta);
    background: white;
  }

  .nav-search input {
    border: none;
    background: none;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: var(--text);
    width: 160px;
  }

  .nav-search input::placeholder { color: var(--text-muted); }

  .nav-search button {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    padding: 0;
    transition: color 0.2s;
  }

  .nav-search button:hover { color: var(--terracotta); }

  .cart-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bark);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 10px 20px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    text-decoration: none;
  }

  .cart-btn:hover {
    background: var(--terracotta);
    transform: translateY(-1px);
  }

  .cart-count {
    background: var(--gold);
    color: var(--bark);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* ─── HERO ─── */
  .hero {
    min-height: calc(100vh - var(--nav-h) - 40px);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    padding: 0;
    overflow: hidden;
  }

  .hero-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 80px 64px 80px 80px;
    position: relative;
  }

  .hero-eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 28px;
    animation: fadeUp 0.8s ease both;
  }

  .hero-eyebrow-line {
    width: 40px;
    height: 1px;
    background: var(--terracotta);
  }

  .hero-eyebrow-text {
    font-size: 11px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--terracotta);
    font-weight: 500;
  }

  .hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(52px, 5vw, 80px);
    font-weight: 500;
    line-height: 1.05;
    color: var(--bark);
    margin-bottom: 24px;
    animation: fadeUp 0.8s 0.1s ease both;
  }

  .hero-title em {
    font-style: italic;
    color: var(--terracotta);
  }

  .hero-desc {
    font-size: 16px;
    line-height: 1.7;
    color: var(--text-muted);
    max-width: 420px;
    margin-bottom: 48px;
    font-weight: 300;
    animation: fadeUp 0.8s 0.2s ease both;
  }

  .hero-actions {
    display: flex;
    gap: 16px;
    align-items: center;
    animation: fadeUp 0.8s 0.3s ease both;
  }

  .btn-primary {
    background: var(--bark);
    color: white;
    padding: 16px 36px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: background var(--transition), transform 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: none;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
  }

  .btn-primary:hover {
    background: var(--terracotta);
    transform: translateY(-2px);
  }

  .btn-ghost {
    color: var(--bark);
    text-decoration: none;
    font-size: 14px;
    font-weight: 400;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: gap 0.2s;
  }

  .btn-ghost:hover { gap: 14px; }
  .btn-ghost:hover .arrow { color: var(--terracotta); }

  .hero-stats {
    display: flex;
    gap: 40px;
    margin-top: 64px;
    padding-top: 40px;
    border-top: 1px solid var(--stone);
    animation: fadeUp 0.8s 0.4s ease both;
  }

  .hero-stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 600;
    color: var(--bark);
    line-height: 1;
  }

  .hero-stat-label {
    font-size: 11px;
    color: var(--text-muted);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 4px;
  }

  .hero-visual {
    position: relative;
    overflow: hidden;
    background: var(--stone);
  }

  .hero-img-main {
    width: 100%;
    height: 100%;
    object-fit: cover;
    animation: scaleIn 1.2s ease both;
  }

  .hero-badge {
    position: absolute;
    bottom: 48px;
    left: -20px;
    background: white;
    border-radius: var(--r);
    padding: 20px 24px;
    box-shadow: var(--shadow);
    animation: slideInLeft 0.8s 0.5s ease both;
    max-width: 200px;
  }

  .hero-badge-label {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--terracotta);
    margin-bottom: 4px;
  }

  .hero-badge-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--bark);
    line-height: 1.2;
  }

  .hero-badge-price {
    font-size: 14px;
    color: var(--text-muted);
    margin-top: 6px;
  }

  .hero-badge-price strong {
    color: var(--terracotta);
    font-size: 18px;
  }

  /* ─── MARQUEE ─── */
  .marquee-section {
    background: var(--bark);
    padding: 18px 0;
    overflow: hidden;
  }

  .marquee-track {
    display: flex;
    gap: 0;
    animation: marquee 30s linear infinite;
    width: max-content;
  }

  .marquee-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 0 40px;
    white-space: nowrap;
  }

  .marquee-text {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-style: italic;
    color: var(--gold-light);
    letter-spacing: 1px;
  }

  .marquee-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--terracotta);
  }

  /* ─── SECTIONS ─── */
  section { padding: 100px 80px; }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 56px;
  }

  .section-eyebrow {
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--terracotta);
    font-weight: 500;
    margin-bottom: 12px;
  }

  .section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(36px, 3.5vw, 52px);
    font-weight: 500;
    color: var(--bark);
    line-height: 1.1;
  }

  .section-title em { font-style: italic; }

  /* ─── CATEGORIES ─── */
  .categories-section { background: var(--warm-white); }

  .category-pills {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 48px;
  }

  .category-pill {
    padding: 10px 24px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 400;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid var(--stone-dark);
    color: var(--text-muted);
    background: white;
    transition: all 0.2s;
    letter-spacing: 0.3px;
  }

  .category-pill:hover,
  .category-pill.active {
    background: var(--bark);
    border-color: var(--bark);
    color: white;
  }

  /* ─── PRODUCT GRID ─── */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 28px;
  }

  .product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: transform var(--transition), box-shadow var(--transition);
    cursor: pointer;
    position: relative;
  }

  .product-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
  }

  .product-img-wrap {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
    background: var(--stone);
  }

  .product-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
  }

  .product-card:hover .product-img { transform: scale(1.08); }

  .product-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    background: var(--bark);
    color: white;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 50px;
    z-index: 2;
  }

  .product-badge.sale { background: var(--terracotta); }
  .product-badge.new { background: var(--sage); }
  .product-badge.artisan { background: var(--gold); color: var(--bark); }

  .product-wishlist {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    background: white;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s;
    z-index: 2;
    box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  }

  .product-card:hover .product-wishlist {
    opacity: 1;
    transform: scale(1);
  }

  .product-wishlist:hover { background: var(--terracotta); color: white; }

  .product-quick-add {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--bark);
    color: white;
    text-align: center;
    padding: 14px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    cursor: pointer;
    border: none;
    font-family: 'DM Sans', sans-serif;
    width: 100%;
  }

  .product-quick-add:hover { background: var(--terracotta); }

  .product-card:hover .product-quick-add { transform: translateY(0); }

  .product-info {
    padding: 20px 20px 24px;
  }

  .product-category {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--terracotta);
    margin-bottom: 6px;
  }

  .product-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 600;
    color: var(--bark);
    margin-bottom: 8px;
    line-height: 1.3;
  }

  .product-price-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .product-price {
    font-size: 18px;
    font-weight: 500;
    color: var(--bark);
  }

  .product-price-orig {
    font-size: 13px;
    color: var(--stone-dark);
    text-decoration: line-through;
  }

  /* ─── FEATURED BANNER ─── */
  .featured-banner {
    background: var(--bark);
    border-radius: 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    overflow: hidden;
    margin-bottom: 80px;
  }

  .featured-banner-content {
    padding: 72px 64px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .featured-banner-eyebrow {
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 20px;
  }

  .featured-banner-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    font-weight: 500;
    color: white;
    line-height: 1.1;
    margin-bottom: 20px;
  }

  .featured-banner-title em {
    font-style: italic;
    color: var(--gold-light);
  }

  .featured-banner-desc {
    font-size: 15px;
    color: rgba(255,255,255,0.65);
    line-height: 1.7;
    margin-bottom: 40px;
    max-width: 380px;
    font-weight: 300;
  }

  .featured-banner-img {
    object-fit: cover;
    width: 100%;
    height: 100%;
    min-height: 400px;
  }

  /* ─── WHY US ─── */
  .why-section { background: var(--stone); }

  .why-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 32px;
  }

  .why-card {
    text-align: center;
    padding: 40px 24px;
    background: white;
    border-radius: 20px;
  }

  .why-icon {
    width: 56px;
    height: 56px;
    background: var(--cream);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 24px;
  }

  .why-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 600;
    color: var(--bark);
    margin-bottom: 10px;
  }

  .why-desc {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.6;
  }

  /* ─── NEWSLETTER ─── */
  .newsletter-section {
    background: linear-gradient(135deg, var(--terracotta), var(--clay));
    text-align: center;
    padding: 100px 80px;
  }

  .newsletter-section .section-title { color: white; }
  .newsletter-section .section-eyebrow { color: var(--gold-light); }

  .newsletter-desc {
    font-size: 16px;
    color: rgba(255,255,255,0.8);
    margin-bottom: 40px;
    font-weight: 300;
  }

  .newsletter-form {
    display: flex;
    gap: 12px;
    max-width: 480px;
    margin: 0 auto;
  }

  .newsletter-form input {
    flex: 1;
    padding: 16px 24px;
    border-radius: 50px;
    border: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    outline: none;
  }

  /* ─── FOOTER ─── */
  footer {
    background: var(--bark);
    color: rgba(255,255,255,0.7);
    padding: 80px 80px 40px;
  }

  .footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 60px;
    margin-bottom: 60px;
  }

  .footer-brand-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
  }

  .footer-brand-tag {
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 20px;
  }

  .footer-desc {
    font-size: 13px;
    line-height: 1.7;
    max-width: 280px;
    font-weight: 300;
    margin-bottom: 28px;
  }

  .footer-socials {
    display: flex;
    gap: 12px;
  }

  .footer-social {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
  }

  .footer-social:hover {
    background: var(--terracotta);
    border-color: var(--terracotta);
    color: white;
  }

  .footer-col-title {
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: white;
    margin-bottom: 20px;
    font-weight: 500;
  }

  .footer-links {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .footer-links a {
    text-decoration: none;
    color: rgba(255,255,255,0.55);
    font-size: 13px;
    transition: color 0.2s;
    font-weight: 300;
  }

  .footer-links a:hover { color: var(--gold-light); }

  .footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: rgba(255,255,255,0.35);
  }

  /* ─── NO PRODUCTS ─── */
  .no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 40px;
    color: var(--text-muted);
  }

  .no-products-icon { font-size: 48px; margin-bottom: 16px; }

  .no-products h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    color: var(--bark);
    margin-bottom: 8px;
  }

  /* ─── ANIMATIONS ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes scaleIn {
    from { transform: scale(1.1); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }

  @keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-40px); }
    to { opacity: 1; transform: translateX(0); }
  }

  @keyframes marquee {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
  }

  /* ─── SCROLL REVEAL ─── */
  .reveal {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }

  .reveal.visible {
    opacity: 1;
    transform: translateY(0);
  }

  /* ─── TOAST ─── */
  .toast {
    position: fixed;
    bottom: 32px;
    right: 32px;
    background: var(--bark);
    color: white;
    padding: 16px 24px;
    border-radius: 12px;
    font-size: 14px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    z-index: 9999;
  }

  .toast.show {
    transform: translateY(0);
    opacity: 1;
  }

  /* ─── RESPONSIVE ─── */
  @media (max-width: 1024px) {
    nav { padding: 0 32px; }
    section { padding: 80px 40px; }
    .hero { grid-template-columns: 1fr; min-height: auto; }
    .hero-visual { height: 50vw; min-height: 350px; }
    .hero-content { padding: 60px 40px; }
    .footer-grid { grid-template-columns: 1fr 1fr; }
    .why-grid { grid-template-columns: 1fr 1fr; }
    footer { padding: 60px 40px 32px; }
    .featured-banner { grid-template-columns: 1fr; }
    .featured-banner-img { height: 300px; }
  }

  @media (max-width: 640px) {
    nav { padding: 0 20px; gap: 16px; }
    .nav-links { display: none; }
    .nav-search input { width: 100px; }
    section { padding: 60px 20px; }
    .hero-content { padding: 48px 24px; }
    .hero-stats { flex-wrap: wrap; gap: 24px; }
    .footer-grid { grid-template-columns: 1fr; gap: 40px; }
    footer { padding: 48px 24px 28px; }
    .newsletter-form { flex-direction: column; }
    .why-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">Free delivery on orders over ₱1,500 &nbsp;·&nbsp; All natural, locally sourced ingredients &nbsp;·&nbsp; 30-day returns</div>

<!-- Navigation -->
<nav>
  <a href="index.php" class="nav-logo">
    <span class="nav-logo-main">Tinatangi</span>
    <span class="nav-logo-sub">Consumer Goods</span>
  </a>
  <ul class="nav-links">
    <li><a href="#products">Shop</a></li>
    <li><a href="#about">Our Story</a></li>
    <li><a href="#categories">Collections</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div class="nav-actions">
    <form class="nav-search" method="GET" action="index.php">
      <button type="submit">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
      <input type="text" name="search" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
      <?php if ($activeCategory !== 'All'): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($activeCategory) ?>">
      <?php endif; ?>
    </form>
    <a href="#" class="cart-btn" id="cartBtn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Cart
      <span class="cart-count" id="cartCount">0</span>
    </a>
  </div>
</nav>

<!-- Hero -->
<section class="hero" id="home">
  <div class="hero-content">
    <div class="hero-eyebrow">
      <div class="hero-eyebrow-line"></div>
      <span class="hero-eyebrow-text">Crafted from the Philippines</span>
    </div>
    <h1 class="hero-title">
      Goods Crafted<br>
      With <em>Intention</em><br>
      & Heart
    </h1>
    <p class="hero-desc">
      Each product in our collection is thoughtfully made using natural, locally sourced ingredients — celebrating the richness of Philippine biodiversity.
    </p>
    <div class="hero-actions">
      <a href="#products" class="btn-primary">
        Explore Collection
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
      </a>
      <a href="#about" class="btn-ghost">
        Our Story <span class="arrow">→</span>
      </a>
    </div>
    <div class="hero-stats">
      <div>
        <div class="hero-stat-num">100%</div>
        <div class="hero-stat-label">Natural</div>
      </div>
      <div>
        <div class="hero-stat-num"><?= count($products) ?>+</div>
        <div class="hero-stat-label">Products</div>
      </div>
      <div>
        <div class="hero-stat-num">12k+</div>
        <div class="hero-stat-label">Happy Customers</div>
      </div>
    </div>
  </div>
  <?php if ($heroProduct): ?>
  <div class="hero-visual">
    <img src="<?= htmlspecialchars($heroProduct['image']) ?>" alt="<?= htmlspecialchars($heroProduct['name']) ?>" class="hero-img-main">
    <div class="hero-badge">
      <div class="hero-badge-label">Featured Product</div>
      <div class="hero-badge-name"><?= htmlspecialchars($heroProduct['name']) ?></div>
      <div class="hero-badge-price">From <strong>₱<?= htmlspecialchars($heroProduct['price']) ?></strong></div>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- Marquee -->
<div class="marquee-section">
  <div class="marquee-track">
    <?php $items = ['Natural Ingredients','Philippine Made','Eco-Friendly','Artisan Crafted','Sustainably Sourced','Zero Waste','Cruelty Free','Family Owned']; ?>
    <?php for ($i = 0; $i < 4; $i++): foreach ($items as $item): ?>
    <div class="marquee-item">
      <span class="marquee-text"><?= $item ?></span>
      <div class="marquee-dot"></div>
    </div>
    <?php endforeach; endfor; ?>
  </div>
</div>

<!-- Products Section -->
<section class="categories-section" id="products">
  <div class="section-header">
    <div>
      <div class="section-eyebrow">Our Collection</div>
      <h2 class="section-title">Shop <em>All</em> Products</h2>
    </div>
    <a href="index.php" class="btn-ghost">View All <span class="arrow">→</span></a>
  </div>

  <!-- Category Filter -->
  <div class="category-pills">
    <a href="index.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="category-pill <?= $activeCategory === 'All' ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
    <a href="?category=<?= urlencode($cat) ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="category-pill <?= $activeCategory === $cat ? 'active' : '' ?>">
      <?= htmlspecialchars($cat) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Products Grid -->
  <div class="products-grid" id="productsGrid">
    <?php if (empty($filteredProducts)): ?>
    <div class="no-products">
      <div class="no-products-icon">🌿</div>
      <h3>No products found</h3>
      <p><?= $search ? "No results for \"<strong>".htmlspecialchars($search)."</strong>\"" : "No products in this category yet." ?></p>
      <br><a href="index.php" class="btn-primary" style="margin-top:16px;display:inline-flex">Clear filters</a>
    </div>
    <?php else: ?>
    <?php foreach ($filteredProducts as $p): 
      $badgeClass = strtolower($p['badge'] ?? '');
    ?>
    <div class="product-card reveal">
      <div class="product-img-wrap">
        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-img" loading="lazy">
        <?php if (!empty($p['badge'])): ?>
        <span class="product-badge <?= $badgeClass ?>"><?= htmlspecialchars($p['badge']) ?></span>
        <?php endif; ?>
        <button class="product-wishlist" title="Add to wishlist">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
        <button class="product-quick-add" onclick="addToCart('<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= htmlspecialchars($p['price']) ?>')">
          + Add to Cart
        </button>
      </div>
      <div class="product-info">
        <div class="product-category"><?= htmlspecialchars($p['category']) ?></div>
        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="product-price-row">
          <span class="product-price">₱<?= htmlspecialchars($p['price']) ?></span>
          <?php if (!empty($p['original_price'])): ?>
          <span class="product-price-orig">₱<?= htmlspecialchars($p['original_price']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Featured Banner -->
<?php if (!empty($featuredProducts)): 
  $fp = array_values($featuredProducts);
  $banner = $fp[min(1, count($fp)-1)];
?>
<section style="padding: 0 80px 100px; background: var(--warm-white);" id="categories">
  <div class="featured-banner reveal">
    <div class="featured-banner-content">
      <div class="featured-banner-eyebrow">✦ Spotlight</div>
      <h2 class="featured-banner-title">Discover Our<br><em>Bestselling</em> Pick</h2>
      <p class="featured-banner-desc"><?= htmlspecialchars($banner['description']) ?></p>
      <a href="#products" class="btn-primary" style="align-self:flex-start;background:var(--gold);color:var(--bark)">
        Shop Now
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
      </a>
    </div>
    <img src="<?= htmlspecialchars($banner['image']) ?>" alt="<?= htmlspecialchars($banner['name']) ?>" class="featured-banner-img">
  </div>
</section>
<?php endif; ?>

<!-- Why Us -->
<section class="why-section" id="about">
  <div class="section-header">
    <div>
      <div class="section-eyebrow">Why Tinatangi</div>
      <h2 class="section-title">Our <em>Promise</em></h2>
    </div>
  </div>
  <div class="why-grid">
    <div class="why-card reveal">
      <div class="why-icon">🌿</div>
      <div class="why-title">100% Natural</div>
      <p class="why-desc">Every ingredient is carefully selected from natural, Philippine-grown sources.</p>
    </div>
    <div class="why-card reveal" style="transition-delay:0.1s">
      <div class="why-icon">🤝</div>
      <div class="why-title">Artisan Made</div>
      <p class="why-desc">Crafted by skilled local artisans supporting community livelihoods.</p>
    </div>
    <div class="why-card reveal" style="transition-delay:0.2s">
      <div class="why-icon">🌍</div>
      <div class="why-title">Eco-Friendly</div>
      <p class="why-desc">Sustainable packaging and zero-waste practices in every step.</p>
    </div>
    <div class="why-card reveal" style="transition-delay:0.3s">
      <div class="why-icon">💛</div>
      <div class="why-title">With Love</div>
      <p class="why-desc">Every product is a labor of love, made for the people who use them.</p>
    </div>
  </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section" id="contact">
  <div class="section-eyebrow">Stay in the Loop</div>
  <h2 class="section-title" style="margin-bottom:16px">Get <em>Exclusive</em> Offers</h2>
  <p class="newsletter-desc">Subscribe to receive new arrivals, seasonal deals, and wellness tips.</p>
  <form class="newsletter-form" onsubmit="handleNewsletter(event)">
    <input type="email" placeholder="Enter your email address" required>
    <button type="submit" class="btn-primary" style="white-space:nowrap">Subscribe →</button>
  </form>
</section>

<!-- Footer -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-brand-name">Tinatangi</div>
      <div class="footer-brand-tag">Consumer Goods</div>
      <p class="footer-desc">Rooted in Philippine culture and craftsmanship. We create goods that celebrate the beauty of natural living.</p>
      <div class="footer-socials">
        <a href="#" class="footer-social">f</a>
        <a href="#" class="footer-social">in</a>
        <a href="#" class="footer-social">ig</a>
        <a href="#" class="footer-social">tw</a>
      </div>
    </div>
    <div>
      <div class="footer-col-title">Shop</div>
      <ul class="footer-links">
        <?php foreach ($categories as $cat): ?>
        <li><a href="?category=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a></li>
        <?php endforeach; ?>
        <li><a href="index.php">All Products</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-col-title">Company</div>
      <ul class="footer-links">
        <li><a href="#">About Us</a></li>
        <li><a href="#">Our Artisans</a></li>
        <li><a href="#">Sustainability</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#">Press</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-col-title">Support</div>
      <ul class="footer-links">
        <li><a href="#">FAQs</a></li>
        <li><a href="#">Shipping Info</a></li>
        <li><a href="#">Returns</a></li>
        <li><a href="#">Track Order</a></li>
        <li><a href="admin-cms.php">Admin</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> Tinatangi Consumer Goods. All rights reserved.</span>
    <span>Made with ♥ in the Philippines</span>
  </div>
</footer>

<!-- Toast Notification -->
<div class="toast" id="toast">
  <span>✓</span>
  <span id="toastMsg">Added to cart!</span>
</div>

<script>
// Cart
let cart = JSON.parse(localStorage.getItem('tinatangi_cart') || '[]');
updateCartCount();

function addToCart(name, price) {
  const existing = cart.find(i => i.name === name);
  if (existing) existing.qty++;
  else cart.push({ name, price, qty: 1 });
  localStorage.setItem('tinatangi_cart', JSON.stringify(cart));
  updateCartCount();
  showToast(`${name} added to cart!`);
}

function updateCartCount() {
  const total = cart.reduce((s, i) => s + i.qty, 0);
  document.getElementById('cartCount').textContent = total;
}

function showToast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

function handleNewsletter(e) {
  e.preventDefault();
  showToast('Thank you for subscribing!');
  e.target.reset();
}

// Scroll reveal
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Wishlist
document.querySelectorAll('.product-wishlist').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.style.background = 'var(--terracotta)';
    btn.style.color = 'white';
    btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
    showToast('Added to wishlist!');
  });
});
</script>
</body>
</html>
