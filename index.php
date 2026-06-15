<?php
session_start();
// СІЗДІҢ БАЗАҒА ҚОСЫЛУ ФАЙЛЫҢЫЗДЫ ҚАЙТА ҚОСТЫМ (БҰЗЫЛМАЙДЫ)
require 'db.php'; 
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/program_store.php';
ensure_admin_schema($pdo);

// XSS және CSRF қорғанысы (Диплом үшін)
function clean_xss($data) {
    if (!$data) return '';
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_data = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
}

$is_admin = current_user_is_admin($pdo);
$site_settings = baspana_get_site_settings();
$hidden_program_ids = baspana_get_hidden_program_ids();
$program_menu_items = baspana_default_programs_meta();
foreach ($hidden_program_ids as $hidden_program_id) {
    unset($program_menu_items[$hidden_program_id]);
}
foreach (baspana_get_custom_programs() as $program_id => $program) {
    $program_menu_items[$program_id] = [
        'title' => $program['title'] ?? $program_id,
        'icon' => $program['icon'] ?? 'fa-home',
    ];
}

$default_program_cards = [
    'baspana_7_20_25' => ['title' => '«7-20-25» ипотекасы', 'rate' => '7%', 'icon' => 'fa-home', 'desc' => 'Жаңа үйлерден баспана алуға арналған тұрақты мөлшерлемелі бағдарлама.'],
    'nauryz_social' => ['title' => '«Наурыз» (Әлеуметтік)', 'rate' => '7%', 'icon' => 'fa-leaf', 'desc' => 'Әлеуметтік осал топтар мен кезекте тұрғандарға арналған жеңілдетілген бағыт.'],
    'nauryz_jumysker' => ['title' => '«Наурыз жұмыскер»', 'rate' => '9%', 'icon' => 'fa-briefcase', 'desc' => 'Ресми табысы бар азаматтарға арналған кеңейтілген ипотека.'],
    'otau' => ['title' => '«Отау» бағдарламасы', 'rate' => '9%', 'icon' => 'fa-key', 'desc' => 'Жастар мен тұрақты салымшыларға арналған бағдарлама.'],
    'zhas_otbasy' => ['title' => '«Жас Отбасы»', 'rate' => '6% - 5%', 'icon' => 'fa-users', 'desc' => 'Некеге тұрғанына 5 жыл толмаған жас отбасыларға арналған өнім.'],
    'standard_50_50' => ['title' => 'Аралық заем (50/50)', 'rate' => '5%', 'icon' => 'fa-percent', 'desc' => 'Үй құнының жартысы жиналған жағдайда кезексіз рәсімделетін заем.'],
    'umay' => ['title' => '«Ұмай» әйелдерге', 'rate' => '14.4%', 'icon' => 'fa-female', 'desc' => 'Қазақстандық әйелдерге арналған ипотекалық бағдарлама.'],
    'oz_uim' => ['title' => '«Өз үйім»', 'rate' => '6% - 7%', 'icon' => 'fa-building', 'desc' => 'Жаңа тұрғын үйді бастапқы жарнамен алуға арналған өнім.'],
    'green_mortgage' => ['title' => '«Жасыл ипотека»', 'rate' => '7% - 12.5%', 'icon' => 'fa-seedling', 'desc' => 'Экологиялық сертификаты бар үйлерді алуға арналған бағдарлама.'],
    'nauryz_askery' => ['title' => '«Наурыз Әскери»', 'rate' => '9%', 'icon' => 'fa-shield-alt', 'desc' => 'Әскери қызметкерлерге арналған жаңа тұрғын үй бағыты.'],
    'askery_baspana' => ['title' => '«Әскери баспана»', 'rate' => '6% - 8%', 'icon' => 'fa-star', 'desc' => 'ТҮТ алатын әскерилерге екінші нарықтан да үй алуға мүмкіндік береді.'],
];
$program_search_items = [];
foreach ($default_program_cards as $program_id => $program_card) {
    if (!in_array($program_id, $hidden_program_ids, true)) {
        $program_search_items[$program_id] = $program_card;
    }
}
foreach (baspana_get_custom_programs() as $program_id => $program) {
    $program_search_items[$program_id] = [
        'title' => $program['title'] ?? $program_id,
        'rate' => $program['rate'] ?? '',
        'icon' => $program['icon'] ?? 'fa-home',
        'desc' => $program['desc'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="kk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Baspana Smart - Болашақ үйіңіз осында</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--kaspi-red:#E12A2A;--kaspi-red-hover:#C52222;--bg-gray:#F7F7F8;--bg-white:#FFFFFF;--text-dark:#1A1A1A;--text-gray:#757575;--border-light:#EBEBEB;--input-bg:#F1F1F1;--radius-card:14px;--radius-btn:12px;}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;scroll-behavior:smooth;}
body{background-color:var(--bg-gray);color:var(--text-dark);line-height:1.5;overflow-x:hidden;}
header{background:var(--bg-white);padding:16px 5%;display:flex;justify-content:space-between;align-items:center;position:fixed;width:100%;top:0;z-index:1000;border-bottom:1px solid var(--border-light);}
.header-left{display:flex;align-items:center;gap:20px;}
.hamburger-btn{font-size:1.5rem;color:var(--text-dark);cursor:pointer;padding:4px;transition:0.2s;}
.hamburger-btn:hover{color:var(--kaspi-red);}
.logo{font-size:1.4rem;font-weight:800;color:var(--text-dark);display:flex;align-items:center;gap:8px;}
.logo i{color:var(--kaspi-red);}
nav{display:flex;gap:24px;align-items:center;}
nav a{text-decoration:none;color:var(--text-dark);font-weight:500;font-size:0.95rem;transition:0.2s;}
nav a:not(.btn-login):hover{color:var(--kaspi-red);}
.btn-login{background:var(--bg-gray);color:var(--text-dark)!important;padding:10px 20px;border-radius:var(--radius-btn);font-weight:600;transition:0.2s;}
.btn-login:hover{background:#E4E4E4;}
.btn-logout{color:var(--kaspi-red)!important;}

.sidebar{position:fixed;top:0;left:-320px;width:300px;height:100vh;background:var(--bg-white);color:var(--text-dark);z-index:2000;transition:left 0.3s ease;display:flex;flex-direction:column;box-shadow:4px 0 24px rgba(0,0,0,0.08);}
.sidebar.open{left:0;}
.sidebar-header{display:flex;justify-content:space-between;align-items:center;padding:24px 20px 16px;border-bottom:1px solid var(--border-light);}
.sidebar-header i{cursor:pointer;font-size:1.2rem;color:var(--text-dark);transition:0.2s;}
.sidebar-header i:hover{color:var(--kaspi-red);}
.sidebar-menu{list-style:none;padding:16px;margin:0;flex-grow:1;overflow-y:auto;}
.sidebar-item{padding:14px 16px;margin-bottom:4px;border-radius:var(--radius-btn);display:flex;align-items:center;gap:16px;cursor:pointer;transition:0.2s;color:var(--text-dark);text-decoration:none;font-size:0.95rem;font-weight:500;}
.sidebar-item:hover{background:var(--bg-gray);}
.sidebar-item i{width:20px;text-align:center;font-size:1.1rem;color:var(--text-gray);}
.sidebar-item.active{background:#FEECEB;color:var(--kaspi-red);}
.sidebar-item.active i{color:var(--kaspi-red);}
.sidebar-section{padding:24px 16px 8px;font-size:0.8rem;color:var(--text-gray);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;}
.sidebar-bottom{padding:16px;border-top:1px solid var(--border-light);}

.menu-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:1500;display:none;opacity:0;transition:0.3s;}
.menu-overlay.open{opacity:1;display:block;}

/* ЖАЛПЫ МОДАЛЬДЫ ТЕРЕЗЕ СТИЛДЕРІ */
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:2500;display:none;opacity:0;transition:0.3s ease;backdrop-filter:blur(3px);}
.modal-overlay.open{opacity:1;display:block;}
.modal-content{position:fixed;top:50%;left:50%;transform:translate(-50%,-60%);background:var(--bg-white);width:90%;max-width:550px;max-height:90vh;overflow-y:auto;padding:40px 32px;border-radius:var(--radius-card);z-index:2600;box-shadow:0 10px 40px rgba(0,0,0,0.15);display:none;opacity:0;transition:all 0.3s ease;}
.modal-content.open{opacity:1;display:block;transform:translate(-50%,-50%);}
.modal-close{position:absolute;top:20px;right:24px;font-size:1.5rem;color:var(--text-gray);cursor:pointer;transition:0.2s;background:var(--bg-gray);border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;}
.modal-close:hover{color:var(--kaspi-red);background:#FEECEB;}

.hero{margin-top:60px;padding:60px 5% 40px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:40px;max-width:1200px;margin:0 auto;}
.hero-text{flex:1;min-width:320px;padding-top:40px;}
.hero-text h1{font-size:3rem;font-weight:800;margin-bottom:16px;line-height:1.1;color:var(--text-dark);letter-spacing:-1px;}
.hero-text p{font-size:1.1rem;color:var(--text-gray);margin-bottom:32px;max-width:400px;}
.badges{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px;}
.badge-item{background:var(--bg-white);padding:10px 20px;border-radius:20px;font-weight:500;display:flex;align-items:center;gap:8px;border:1px solid var(--border-light);font-size:0.9rem;}

.calculator-card{flex:1;min-width:350px;max-width:480px;background:var(--bg-white);padding:32px;border-radius:var(--radius-card);box-shadow:0 4px 20px rgba(0,0,0,0.04);}
.calculator-card h3{margin-bottom:24px;color:var(--text-dark);font-size:1.5rem;font-weight:700;}
.input-group{margin-bottom:16px;}
.input-group label{display:block;margin-bottom:6px;font-weight:500;font-size:0.85rem;color:var(--text-gray);}
.input-group input,.input-group select{width:100%;height:52px;padding:0 16px;border-radius:var(--radius-btn);border:1px solid transparent;font-size:1rem;font-family:inherit;background:var(--input-bg);color:var(--text-dark);font-weight:500;transition:0.2s;appearance:none;}
.input-group select{background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23757575' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");background-repeat:no-repeat;background-position:right 16px center;background-size:16px;}
.input-group input:focus,.input-group select:focus{background:var(--bg-white);border-color:var(--kaspi-red);outline:none;}
.btn-main{background:var(--kaspi-red);color:#ffffff;border:none;height:56px;border-radius:var(--radius-btn);font-size:1.05rem;font-weight:600;cursor:pointer;width:100%;margin-top:8px;transition:0.2s;display:flex;justify-content:center;align-items:center;gap:8px;text-decoration:none;}
.btn-main:hover{background:var(--kaspi-red-hover);}

.full-calc-container{max-width:1200px;margin:0 auto 60px;background:var(--bg-white);border-radius:var(--radius-card);padding:40px;box-shadow:0 4px 20px rgba(0,0,0,0.04);border:1px solid var(--border-light);}
.manual-rate-field{max-width:320px;margin-bottom:32px;}
input[type=range]{-webkit-appearance:none;width:100%;background:transparent;margin-top:10px;}
input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;height:24px;width:24px;border-radius:50%;background:var(--bg-white);border:5px solid var(--kaspi-red);cursor:pointer;margin-top:-10px;box-shadow:0 2px 6px rgba(0,0,0,0.15);}
input[type=range]::-webkit-slider-runnable-track{width:100%;height:4px;cursor:pointer;background:#E0E0E0;border-radius:2px;}
input[type=range]:focus{outline:none;}
.range-label-container{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;}
.range-title{color:var(--text-gray);font-size:0.95rem;font-weight:500;}
.range-value{font-size:1.2rem;font-weight:700;color:var(--text-dark);}
.calc-results-box{background:var(--bg-gray);padding:32px;border-radius:var(--radius-card);}
.calc-res-item{margin-bottom:24px;}
.calc-res-label{color:var(--text-gray);font-size:0.9rem;font-weight:500;margin-bottom:4px;}
.calc-res-val{font-size:1.5rem;font-weight:800;color:var(--text-dark);}
.calc-res-val.highlight{color:var(--kaspi-red);font-size:2rem;}

.result-section{padding:40px 5%;display:none; background: var(--bg-white); border-top: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light); margin-bottom: 60px;}
.result-container{max-width:1300px;margin:0 auto;}
.programs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-top: 24px; }
.program-tile { background: var(--bg-white); border: 1px solid var(--border-light); border-radius: var(--radius-card); padding: 28px; display: flex; flex-direction: column; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: 0.3s ease; position: relative; overflow: hidden;}
.program-tile::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--border-light); transition: 0.3s; }
.program-tile:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(0,0,0,0.08); border-color: var(--border-light); }
.program-tile:hover::before { background: var(--kaspi-red); }

.tile-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px; }
.tile-title { color: var(--text-dark); font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px;}
.tile-reason { color: #059669; font-size: 0.85rem; font-weight: 600; background: #ECFDF5; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; }
.rate-badge { background: #FEECEB; color: var(--kaspi-red); padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 1.2rem; }

.info-list{list-style:none;padding:0;margin-bottom:20px;}
.info-list li{margin-bottom:10px;display:flex;gap:10px;font-size:0.95rem;color:var(--text-dark);background:var(--bg-gray);padding:12px;border-radius:10px; align-items: flex-start;}
.info-list i{color:var(--kaspi-red);margin-top:3px;font-size:1rem;}
.steps-container{background:var(--bg-gray); border:1px solid var(--border-light);padding:20px;border-radius:16px;margin-top:auto;}
.steps-container h4{margin-bottom:16px;color:var(--text-dark);font-size:1rem;}
.steps-container ol{padding-left:20px;color:var(--text-gray);font-weight:500;line-height:1.6;font-size: 0.9rem;}
.steps-container li{margin-bottom:10px;padding-left:6px;}
.steps-container li::marker{color:var(--kaspi-red);font-weight:bold;font-size:1rem;}

.section-title{text-align:center;font-size:2rem;color:var(--text-dark);margin:60px 0 40px;font-weight:800;letter-spacing:-0.5px;}
.info-sections{padding:0 5% 60px;display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:24px;max-width:1200px;margin:0 auto;}
.info-card{background:var(--bg-white);border-radius:var(--radius-card);padding:32px;box-shadow:0 4px 12px rgba(0,0,0,0.03);display:flex;flex-direction:column;border:1px solid var(--border-light);}
.info-card h3{color:var(--text-dark);margin-bottom:16px;font-size:1.4rem;font-weight:700;letter-spacing:-0.5px;}
.info-card p{color:var(--text-gray);margin-bottom:20px;font-size:0.95rem;}
.tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;}
.tag{background:var(--input-bg);color:var(--text-dark);padding:6px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;}
.info-card ul{padding-left:20px;margin-bottom:25px;color:var(--text-dark);font-weight:500;}
.info-card ul li{margin-bottom:10px;}
.btn-outline{margin-top:auto;padding:14px 24px;background:var(--input-bg);color:var(--text-dark);border-radius:var(--radius-btn);text-decoration:none;font-weight:600;text-align:center;transition:background 0.2s;cursor:pointer;border:none;font-size:1rem;}
.btn-outline:hover{background:#E4E4E4;}
.program-finder{max-width:1200px;margin:0 auto 60px;padding:0 5%;}
.finder-shell{background:var(--bg-white);border:1px solid var(--border-light);border-radius:var(--radius-card);padding:28px;box-shadow:none;}
.finder-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-end;margin-bottom:20px;}
.finder-head h2{font-size:1.7rem;font-weight:800;letter-spacing:0;color:var(--text-dark);}
.finder-head p{color:var(--text-gray);font-size:0.95rem;margin-top:6px;}
.finder-search{max-width:360px;position:relative;width:100%;}
.finder-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-gray);}
.finder-search input{width:100%;height:48px;border:1px solid var(--border-light);background:var(--bg-gray);border-radius:var(--radius-btn);padding:0 14px 0 40px;font-size:0.96rem;outline:none;}
.finder-search input:focus{border-color:var(--kaspi-red);background:#fff;}
.finder-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;}
.finder-card{border:1px solid var(--border-light);border-radius:var(--radius-btn);padding:18px;text-decoration:none;color:var(--text-dark);background:#fff;display:flex;flex-direction:column;gap:12px;transition:0.2s;min-height:190px;}
.finder-card:hover{border-color:#D7D7D7;transform:translateY(-2px);}
.finder-card-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;}
.finder-icon{width:40px;height:40px;border-radius:12px;background:#FEECEB;color:var(--kaspi-red);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.finder-rate{font-weight:800;color:var(--kaspi-red);background:#FEECEB;border-radius:999px;padding:6px 10px;font-size:0.86rem;}
.finder-title{font-size:1rem;font-weight:800;line-height:1.25;}
.finder-desc{color:var(--text-gray);font-size:0.9rem;line-height:1.45;flex:1;}
.finder-link{font-weight:700;color:var(--text-dark);font-size:0.92rem;}
.calculator-card,.full-calc-container,.program-tile,.info-card{box-shadow:none;border:1px solid var(--border-light);}
.program-tile:hover,.finder-card:hover{box-shadow:none;}

footer{background:var(--bg-white);color:var(--text-gray);text-align:center;padding:32px;border-top:1px solid var(--border-light);font-size:0.9rem;}

.modal-content ul { padding-left: 20px; margin-bottom: 20px; color: var(--text-dark); line-height: 1.6; }
.modal-content ul li { margin-bottom: 10px; }
.modal-content p { color: var(--text-gray); margin-bottom: 20px; line-height: 1.6; }

@media (max-width:900px){
    .hero{padding-top:40px;flex-direction:column;align-items:center;text-align:center;}
    .hero-text{padding-top:0;}
    .hero-text p{margin:0 auto 30px;}
    .badges{justify-content:center;}
    .calculator-card{width:100%;max-width:100%;}
    .full-calc-container > div{grid-template-columns:1fr!important;}
    .finder-head{align-items:stretch;flex-direction:column;}
    .finder-search{max-width:100%;}
    nav{display:none;}
}
</style>
</head>
<body>

<input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="menu-overlay" id="menuOverlay" onclick="toggleSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo" style="font-size: 1.2rem;"><i class="fas fa-home"></i> Baspana</div>
        <i class="fas fa-times" onclick="toggleSidebar()"></i>
    </div>
    <div class="sidebar-menu">
        <a href="#calculator" class="sidebar-item" onclick="toggleSidebar()">
            <i class="fas fa-magic"></i> Smart Талдау
        </a>
        <a href="#detailed-calc" class="sidebar-item" onclick="toggleSidebar()">
            <i class="fas fa-sliders-h"></i> Толық калькулятор
        </a>
        <a href="#developers" class="sidebar-item" onclick="toggleSidebar()">
            <i class="fas fa-city"></i> Құрылыс компаниялары
        </a>
        <a href="#" class="sidebar-item" onclick="openModal('orkenModal'); return false;">
            <i class="fas fa-clipboard-check"></i> Өркенге тест
        </a>
        <a href="#kezek" class="sidebar-item" onclick="toggleSidebar()">
            <i class="fas fa-info-circle"></i> Пайдалы ақпарат
        </a>

        <div class="sidebar-section" style="margin-top: 10px;">Барлық бағдарламалар</div>
        <?php foreach ($program_menu_items as $program_id => $program_meta): ?>
            <a href="program.php?id=<?php echo rawurlencode($program_id); ?>" class="sidebar-item">
                <i class="fas <?php echo clean_xss($program_meta['icon'] ?? 'fa-home'); ?>"></i> <?php echo clean_xss($program_meta['title'] ?? $program_id); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="sidebar-bottom">
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($is_admin): ?>
                <a href="admin.php" class="sidebar-item"><i class="fas fa-user-shield"></i> Админ панель</a>
            <?php endif; ?>
            <a href="profile.php" class="sidebar-item"><i class="fas fa-user"></i> Жеке кабинет</a>
            <a href="logout.php" class="sidebar-item" style="color: var(--kaspi-red);"><i class="fas fa-sign-out-alt"></i> Шығу</a>
        <?php else: ?>
            <a href="login.php" class="sidebar-item"><i class="fas fa-sign-in-alt"></i> Жүйеге кіру</a>
        <?php endif; ?>
    </div>
</div>

<header>
    <div class="header-left">
        <i class="fas fa-bars hamburger-btn" onclick="toggleSidebar()"></i>
        <div class="logo"><i class="fas fa-home"></i> Baspana Smart</div>
    </div>
    <nav>
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($is_admin): ?><a href="admin.php"><i class="fas fa-user-shield" style="color: var(--kaspi-red);"></i> Админ панель</a><?php endif; ?>
            <a href="profile.php" style="color: var(--text-dark);"><i class="fas fa-user-circle" style="color: var(--kaspi-red);"></i> <?php echo clean_xss($user_data['name'] ?? ''); ?></a>
            <a href="logout.php" class="btn-logout">Шығу</a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Кіру</a>
        <?php endif; ?>
    </nav>
</header>

<!-- БАРЛЫҚ МОДАЛЬДЫ ТЕРЕЗЕЛЕР -->
<div class="modal-overlay" id="mainModalOverlay" onclick="closeAllModals()"></div>

<div class="modal-content" id="orkenModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="text-align: center; margin-bottom: 24px; font-size: 1.5rem; font-weight: 700;">
        <i class="fas fa-clipboard-check" style="color: var(--kaspi-red); margin-right: 10px;"></i> «Өркен» тесті
    </h3>
    <div class="input-group">
        <label>Сіздің жасыңыз 18-ден асты ма?</label>
        <select id="orkenQ1"><option value="yes">Иә, 18-ден астым</option><option value="no">Жоқ, кәмелетке толмадым</option></select>
    </div>
    <div class="input-group">
        <label>Атыңызда немесе отбасыңызда үй бар ма?</label>
        <select id="orkenQ2"><option value="no">Жоқ, ешқандай үй жоқ (соңғы 5 жыл)</option><option value="yes">Иә, үй бар</option><option value="emergency">Үй бар, бірақ апатты</option></select>
    </div>
    <div class="input-group">
        <label>Тіркеуіңіз (Прописка)</label>
        <select id="orkenQ3"><option value="mega_3_plus">Мегаполисте (3 жылдан АСТЫ)</option><option value="mega_less_3">Мегаполисте (3 жылдан АЗ)</option><option value="regions">Өңірлер / Ауыл</option></select>
    </div>
    <button class="btn-main" onclick="checkOrkenEligibility()">Нәтижені тексеру</button>
    <div id="orken-result" style="margin-top: 24px; display: none; padding: 16px; border-radius: 12px; font-size: 1rem; line-height: 1.5;"></div>
</div>

<div class="modal-content" id="kezekteModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="margin-bottom: 16px; font-size: 1.5rem; font-weight: 700;">Үй кезегі (Kezekte.kz)</h3>
    <p>Мемлекеттен 2% немесе 5% мөлшерлемемен баспана алу үшін әкімдік кезегіне тұрудың толық нұсқаулығы.</p>
    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Кімдер тұра алады?</h4>
    <ul>
        <li>Көпбалалы аналар мен отбасылар</li>
        <li>1 және 2 топтағы мүгедектер, мүгедек бала асыраушылар</li>
        <li>Жетім балалар</li>
        <li>Мемлекеттік қызметкерлер мен бюджеттік ұйым жұмысшылары</li>
    </ul>
    <button class="btn-main" onclick="closeAllModals()">Түсіндім</button>
</div>

<div class="modal-content" id="depositModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="margin-bottom: 16px; font-size: 1.5rem; font-weight: 700;">Отбасы банкінің депозиттері</h3>
    <p>Тұрғын үй құрылыс жинақ жүйесі арқылы Қазақстандағы ең төменгі мөлшерлемемен (3.5% - 5%) ипотека алуға болады.</p>
    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Жүйе қалай жұмыс істейді?</h4>
    <ul>
        <li><strong>Аралық заем (50/50):</strong> Егер сізде баспана құнының 50%-ы бірден болса, оны депозитке салып, қалған 50%-ын банктен 5% мөлшерлемемен аласыз.</li>
        <li><strong>Мемлекеттік сыйлықақы:</strong> Жыл сайын жинағыңызға мемлекеттен 20% сыйлықақы қосылып отырады.</li>
    </ul>
    <button class="btn-main" onclick="closeAllModals()">Жабу</button>
</div>

<div class="modal-content" id="applicationModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="text-align: center; margin-bottom: 10px; font-size: 1.5rem; font-weight: 700;">Өтінім қалдыру</h3>
    <p style="text-align: center; margin-bottom: 24px;">Таңдалған бағдарлама бойынша банк менеджері сізбен хабарласуы үшін нөміріңізді қалдырыңыз.</p>
    <div class="input-group">
        <label>Телефон нөміріңіз:</label>
        <input type="text" id="appPhone" placeholder="+7 (___) ___-__-__">
    </div>
    <button class="btn-main" onclick="submitApplication()">Өтінімді жіберу</button>
    <div id="app-success" style="display: none; background: #E8F5E9; color: #2E7D32; padding: 16px; border-radius: 12px; margin-top: 16px; text-align: center; border: 1px solid #A5D6A7;">
        <i class='fas fa-check-circle'></i> Өтінім сәтті қабылданды! Біздің маман сізбен жақын арада байланысады.
    </div>
</div>

<!-- ЖАҢА МОДАЛЬДАР (ҚҰРЫЛЫС КОМПАНИЯЛАРЫ ТУРАЛЫ + СІЛТЕМЕЛЕР) -->
<div class="modal-content" id="biModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="margin-bottom: 16px; font-size: 1.5rem; font-weight: 700; color: var(--kaspi-red);">BI Group құрылыс холдингі</h3>
    <p>Қазақстанның жылжымайтын мүлік нарығындағы сөзсіз көшбасшы. 1995 жылдан бері жұмыс істеп келе жатқан алып корпорация. Олар тек пәтерлер емес, мектептер, ауруханалар мен ірі инфрақұрылымдық жобаларды жүзеге асырады.</p>
    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Артықшылықтары:</h4>
    <ul>
        <li><strong>Сенімділік:</strong> Құрылыс нысандарын мерзімінен бұрын немесе уақытылы тапсыру көрсеткіші өте жоғары.</li>
        <li><strong>Smart Home жүйесі:</strong> Жаңа кешендері "Ақылды үй" жүйесімен қамтамасыз етіледі.</li>
        <li><strong>Жасыл ипотека:</strong> Отбасы банкінің "Жасыл ипотека" (7-12.5%) бағдарламасына тікелей қатысады.</li>
    </ul>
    <!-- ТІКЕЛЕЙ СІЛТЕМЕ -->
    <a href="https://bi.group/" target="_blank" class="btn-main" style="background: #2E3192; margin-bottom: 12px; text-decoration: none;">Ресми сайтына өту <i class="fas fa-external-link-alt"></i></a>
    <button class="btn-outline" style="width: 100%;" onclick="closeAllModals()">Жабу</button>
</div>

<div class="modal-content" id="bazisModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="margin-bottom: 16px; font-size: 1.5rem; font-weight: 700; color: var(--kaspi-red);">BAZIS-A корпорациясы</h3>
    <p>1991 жылы құрылған, Қазақстандағы ең танымал әрі байырғы құрылыс компанияларының бірі. Негізінен премиум, бизнес және жайлылық санатындағы тұрғын үй кешендерін тұрғызады.</p>
    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Артықшылықтары:</h4>
    <ul>
        <li><strong>Сейсмикалық қауіпсіздік:</strong> Алматы қаласындағы үйлері 9 балдық жер сілкінісіне төтеп беретін монолитті технологиямен салынады.</li>
        <li><strong>Орналасуы:</strong> Қаланың ең престижді, инфрақұрылымы дамыған аудандарында бой көтереді.</li>
    </ul>
    <!-- ТІКЕЛЕЙ СІЛТЕМЕ -->
    <a href="https://bazis.kz/" target="_blank" class="btn-main" style="background: #000000; margin-bottom: 12px; text-decoration: none;">Ресми сайтына өту <i class="fas fa-external-link-alt"></i></a>
    <button class="btn-outline" style="width: 100%;" onclick="closeAllModals()">Жабу</button>
</div>

<div class="modal-content" id="ramsModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="margin-bottom: 16px; font-size: 1.5rem; font-weight: 700; color: var(--kaspi-red);">RAMS Qazaqstan</h3>
    <p>1997 жылдан бері Қазақстан нарығында жұмыс істеп келе жатқан ірі халықаралық (түрік-қазақ) құрылыс компаниясы.</p>
    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Артықшылықтары:</h4>
    <ul>
        <li><strong>"All-in-One":</strong> Үй ішінде тұрғындарға тегін фитнес-залдар, кинотеатрлар мен коворкинг орталықтары орналасады.</li>
        <li><strong>Өзіндік бөліп төлеу:</strong> Ипотекадан бөлек, компания клиенттерге пайызсыз, тікелей өздерінен бөліп төлеу мүмкіндігін жиі ұсынады.</li>
    </ul>
    <!-- ТІКЕЛЕЙ СІЛТЕМЕ -->
    <a href="https://ramsqazaqstan.kz/" target="_blank" class="btn-main" style="background: #111827; margin-bottom: 12px; text-decoration: none;">Ресми сайтына өту <i class="fas fa-external-link-alt"></i></a>
    <button class="btn-outline" style="width: 100%;" onclick="closeAllModals()">Жабу</button>
</div>

<div class="modal-content" id="qazaqModal">
    <span class="modal-close" onclick="closeAllModals()"><i class="fas fa-times"></i></span>
    <h3 style="margin-bottom: 16px; font-size: 1.5rem; font-weight: 700; color: var(--kaspi-red);">Qazaq Stroy</h3>
    <p>Соңғы жылдары нарықта өте жылдам дамып келе жатқан отандық құрылыс компаниясы. Негізгі бағыты — халыққа қолжетімді үйлер салу.</p>
    <h4 style="margin-bottom: 10px; color: var(--text-dark);">Артықшылықтары:</h4>
    <ul>
        <li><strong>Қолжетімді баға:</strong> Басқа компаниялармен салыстырғанда шаршы метрі әлдеқайда арзан.</li>
        <li><strong>Жылдамдық:</strong> Құрылыс қарқыны өте жоғары, үйлер мерзімінен бұрын тапсырылады.</li>
    </ul>
    <!-- ТІКЕЛЕЙ СІЛТЕМЕ -->
    <a href="https://qazaqstroy.kz/" target="_blank" class="btn-main" style="background: #0056A4; margin-bottom: 12px; text-decoration: none;">Ресми сайтына өту <i class="fas fa-external-link-alt"></i></a>
    <button class="btn-outline" style="width: 100%;" onclick="closeAllModals()">Жабу</button>
</div>

<!-- SMART ТАЛДАУ БӨЛІМІ -->
<section class="hero" id="calculator">
    <div class="hero-text">
        <h1><?php echo nl2br(clean_xss($site_settings['hero_title'] ?? 'Ипотекаңызды есептеңіз')); ?></h1>
        <p><?php echo clean_xss($site_settings['hero_text'] ?? ''); ?></p>
        <div class="badges">
            <span class="badge-item"><i class="fas fa-shield-alt" style="color: var(--kaspi-red);"></i> <?php echo clean_xss($site_settings['badge_programs'] ?? '11 Бағдарлама'); ?></span>
            <span class="badge-item"><i class="fas fa-percentage" style="color: var(--kaspi-red);"></i> <?php echo clean_xss($site_settings['badge_rate'] ?? '2% - 18% мөлшерлеме'); ?></span>
            <span class="badge-item"><i class="fas fa-bolt" style="color: var(--kaspi-red);"></i> <?php echo clean_xss($site_settings['badge_ai'] ?? 'AI Талдау'); ?></span>
        </div>
    </div>

   <div class="calculator-card">
        <h3>Мәліметтеріңіз</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="input-group">
                <label>Жасыңыз</label>
                <input type="number" id="userAge" placeholder="28" value="<?php echo clean_xss($user_data['age'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label>Аймақ</label>
                <select id="userCity">
                    <option value="megapolis" <?php if(isset($user_data['city']) && $user_data['city']=='megapolis') echo 'selected'; ?>>Мегаполис</option>
                    <option value="region" <?php if(isset($user_data['city']) && $user_data['city']=='region') echo 'selected'; ?>>Өңірлер / Ауыл</option>
                </select>
            </div>
        </div>
        <div class="input-group">
            <label>Әлеуметтік мәртебеңіз</label>
            <select id="userStatus">
                <option value="standard" <?php if(isset($user_data['social_status']) && $user_data['social_status']=='standard') echo 'selected'; ?>>Жалпы топ (Жұмысшы, ЖК)</option>
                <option value="social" <?php if(isset($user_data['social_status']) && $user_data['social_status']=='social') echo 'selected'; ?>>Әлеуметтік осал топ / Көпбалалы</option>
                <option value="young_family" <?php if(isset($user_data['social_status']) && $user_data['social_status']=='young_family') echo 'selected'; ?>>Жас отбасы (некеде 5 жылға дейін)</option>
                <option value="young_pro" <?php if(isset($user_data['social_status']) && $user_data['social_status']=='young_pro') echo 'selected'; ?>>Жас маман / Ғалым / Дәрігер</option>
                <option value="military" <?php if(isset($user_data['social_status']) && $user_data['social_status']=='military') echo 'selected'; ?>>Әскери қызметкер</option>
            </select>
        </div>
        <div class="input-group">
            <label>Қазіргі жинағыңыз (Бастапқы жарна ₸)</label>
            <input type="number" id="userSavings" placeholder="Мысалы: 5000000" value="<?php echo clean_xss($user_data['savings'] ?? ''); ?>">
        </div>
        <div class="input-group">
            <label>Таза айлық табысыңыз (₸)</label>
            <input type="number" id="userIncome" placeholder="Мысалы: 400000" value="<?php echo clean_xss($user_data['income'] ?? ''); ?>">
        </div>
        <button class="btn-main" onclick="analyzeData()">AI Талдауды бастау</button>
    </div>
</section>

<section class="result-section" id="result-section">
    <div class="result-container" id="result-container"></div>
</section>

<section class="program-finder" id="programs">
    <div class="finder-shell">
        <div class="finder-head">
            <div>
                <h2>Ипотекалық бағдарламалар</h2>
                <p>Бағдарламаны іздеп, толық шарттарын ашыңыз.</p>
            </div>
            <div class="finder-search">
                <i class="fas fa-search"></i>
                <input type="search" id="programSearch" placeholder="Бағдарламаны іздеу" oninput="filterProgramCards()">
            </div>
        </div>
        <div class="finder-grid" id="programFinderGrid">
            <?php foreach ($program_search_items as $program_id => $program): ?>
                <a class="finder-card" href="program.php?id=<?php echo rawurlencode($program_id); ?>" data-search="<?php echo clean_xss(mb_strtolower(($program['title'] ?? '') . ' ' . ($program['rate'] ?? '') . ' ' . ($program['desc'] ?? ''), 'UTF-8')); ?>">
                    <div class="finder-card-top">
                        <div class="finder-icon"><i class="fas <?php echo clean_xss($program['icon'] ?? 'fa-home'); ?>"></i></div>
                        <div class="finder-rate"><?php echo clean_xss($program['rate'] ?? ''); ?></div>
                    </div>
                    <div class="finder-title"><?php echo clean_xss($program['title'] ?? $program_id); ?></div>
                    <div class="finder-desc"><?php echo clean_xss($program['desc'] ?? ''); ?></div>
                    <div class="finder-link">Толығырақ білу <i class="fas fa-arrow-right"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ҚОЛМЕН ЕСЕПТЕУ КАЛЬКУЛЯТОРЫ -->
<section style="padding: 0 5%;" id="detailed-calc">
    <div class="full-calc-container">
        <h2 style="color: var(--text-dark); margin-bottom: 24px; font-weight: 800; font-size: 2rem;">Ипотеканы дәл есептеу</h2>
        <div class="input-group manual-rate-field">
            <label>Жылдық пайыздық мөлшерлеме (%)</label>
            <input type="number" id="interestRate" min="0" max="100" step="0.1" value="5" oninput="updateDetailedCalc()">
        </div>
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px;">
            <div>
                <div style="margin-bottom: 32px;">
                    <div class="range-label-container"><span class="range-title">Тұрғын үй құны:</span><span class="range-value" id="dispPrice">20 000 000 ₸</span></div>
                    <input type="range" id="sliderPrice" min="3000000" max="100000000" step="500000" value="20000000" oninput="updateDetailedCalc()">
                </div>
                <div style="margin-bottom: 32px;">
                    <div class="range-label-container"><span class="range-title">Бастапқы жарна / Жинақ:</span><span class="range-value" id="dispDeposit">10 000 000 ₸</span></div>
                    <input type="range" id="sliderDeposit" min="0" max="100000000" step="500000" value="10000000" oninput="updateDetailedCalc()">
                </div>
                <div style="margin-bottom: 20px;">
                    <div class="range-label-container"><span class="range-title">Заем мерзімі (ай):</span><span class="range-value" id="dispTerm">120 ай</span></div>
                    <input type="range" id="sliderTerm" min="6" max="300" step="6" value="120" oninput="updateDetailedCalc()">
                </div>
            </div>
            <div class="calc-results-box">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="calc-res-item"><div class="calc-res-label">Қажетті заем сомасы:</div><div class="calc-res-val" id="resLoanAmt">10 000 000 ₸</div></div>
                    <div class="calc-res-item"><div class="calc-res-label">Жылдық мөлшерлеме:</div><div class="calc-res-val" style="color: var(--kaspi-red);" id="resRateAmt">5.0 %</div></div>
                </div>
                <div class="calc-res-item" style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 24px;">
                    <div class="calc-res-label" style="font-size: 1rem;">Айлық төлем:</div><div class="calc-res-val highlight" id="resMonthlyAmt">106 065 ₸</div>
                </div>
                <div class="calc-res-item" style="margin-top: 10px;">
                    <div class="calc-res-label">Артық төлем (Переплата):</div><div class="calc-res-val" id="resOverpayAmt">2 727 800 ₸</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ЖАҢА БӨЛІМ: ҚҰРЫЛЫС КОМПАНИЯЛАРЫ (ЗАСТРОЙЩИКТЕР) -->
<h2 class="section-title" id="developers">Қазақстандағы Сенімді Құрылыс Компаниялары</h2>
<section class="info-sections">
    <div class="info-card">
        <h3><i class="far fa-building" style="color: var(--kaspi-red);"></i> BI Group</h3>
        <p>Қазақстанның құрылыс нарығындағы №1 көшбасшы корпорация. Ең көп «Жасыл ипотека» жобалары осында.</p>
        <div class="tags"><span class="tag">28 жыл нарықта</span><span class="tag">Smart Home</span><span class="tag">Сенімділік</span></div>
        <button class="btn-outline" onclick="openModal('biModal')">Толық ақпарат оқу</button>
    </div>

    <div class="info-card">
        <h3><i class="far fa-building" style="color: var(--kaspi-red);"></i> BAZIS-A</h3>
        <p>30 жылдық тарихы бар, сейсмикалық қауіпсіздік пен премиум архитектураны біріктірген корпорация.</p>
        <div class="tags"><span class="tag">Сейсмотұрақтылық</span><span class="tag">Премиум сапа</span><span class="tag">Инфрақұрылым</span></div>
        <button class="btn-outline" onclick="openModal('bazisModal')">Толық ақпарат оқу</button>
    </div>

    <div class="info-card">
        <h3><i class="far fa-building" style="color: var(--kaspi-red);"></i> RAMS Qazaqstan</h3>
        <p>Халықаралық сапа стандарттарын енгізген түрік-қазақ құрылыс компаниясы. "All-in-one" концепциясының иесі.</p>
        <div class="tags"><span class="tag">All-in-One</span><span class="tag">Түрік сапасы</span><span class="tag">Рассрочка</span></div>
        <button class="btn-outline" onclick="openModal('ramsModal')">Толық ақпарат оқу</button>
    </div>

    <div class="info-card">
        <h3><i class="far fa-building" style="color: var(--kaspi-red);"></i> Qazaq Stroy</h3>
        <p>Жылдам құрылыс қарқынымен және жас отбасыларға арналған қолжетімді бағамен танылған компания.</p>
        <div class="tags"><span class="tag">Қолжетімді баға</span><span class="tag">Комфорт класс</span><span class="tag">Жылдамдық</span></div>
        <button class="btn-outline" onclick="openModal('qazaqModal')">Толық ақпарат оқу</button>
    </div>
</section>

<!-- ЕСКІ АҚПАРАТ БӨЛІМІ (KEZEK) -->
<h2 class="section-title" id="kezek">Анықтама және Ақпараттар</h2>
<section class="info-sections">
    <div class="info-card" style="border: 2px solid var(--kaspi-red);">
        <h3>Жаңа «Өркен» порталы</h3>
        <p>14 мемлекеттік дерекқормен байланысқандықтан, қағаз құжат жинаудың қажеті жоқ.</p>
        <div class="tags"><span class="tag" style="background: #FEECEB; color: var(--kaspi-red);">Цифрлық жүйе</span><span class="tag" style="background: #FEECEB; color: var(--kaspi-red);">SMS растау</span></div>
        <ul><li>Мегаполистерде кемінде 3 жыл тіркеу қажет.</li><li>Отбасы мүшелеріне SMS растау келеді.</li></ul>
        <button class="btn-outline" onclick="openModal('orkenModal')">Тесттен өту</button>
    </div>

    <div class="info-card">
        <h3>«7-20-25» бағдарламасы</h3>
        <p>Жаңа үйлерді сатып алуға арналған Ұлттық Банктің әлеуметтік ипотекалық бағдарламасы.</p>
        <div class="tags"><span class="tag">7% мөлшерлеме</span><span class="tag">20% алғашқы жарна</span></div>
        <ul><li>Басты талап: Атыңызда басқа тұрғын үй болмауы тиіс.</li><li>Тек жаңа пәтер алуға болады.</li></ul>
        <a href="program.php?id=7_20_25" class="btn-outline">Толық нұсқаулық</a>
    </div>

    <div class="info-card">
        <h3>Үй кезегіне тұру (Kezekte.kz)</h3>
        <p>Мемлекеттен жеңілдетілген 2% немесе 5% мөлшерлемемен үй алу үшін дәстүрлі кезекке тұру әдісі.</p>
        <div class="tags"><span class="tag">Көпбалалы</span><span class="tag">Мүгедектер</span><span class="tag">Мемқызметкерлер</span></div>
        <ul><li>Басты талап: Соңғы 5 жылда үй болмауы тиіс.</li></ul>
        <button class="btn-outline" onclick="openModal('kezekteModal')">Толық нұсқаулық</button>
    </div>

    <div class="info-card">
        <h3>Отбасы банкінің депозит жүйесі</h3>
        <p>Қазақстандағы ең төменгі пайыздар (3.5% - 5%) тек Тұрғын үй құрылыс жинақ жүйесінде.</p>
        <div class="tags"><span class="tag">Аралық заем</span><span class="tag">Сыйлықақы 20%</span></div>
        <ul><li>Үй құнының 50%-ы болса, несиені 5%-бен аласыз.</li></ul>
        <button class="btn-outline" onclick="openModal('depositModal')">Толық шарттар</button>
    </div>
</section>

<footer>
    <div style="font-weight: 700; color: var(--text-dark); margin-bottom: 8px; font-size: 1.2rem;">Baspana Smart</div>
    <p><?php echo clean_xss($site_settings['footer_text'] ?? '© 2026. Барлық ақпарат сайт ішінде қауіпсіз сақталады.'); ?></p>
</footer>

<script>
const programsData = {
    baspana_7_20_25: {
        programId: "7_20_25",
        title: "«7-20-25» ипотекасы", rate: "7%",
        description: "Алғашқы нарықтан (жаңа үйлерден) баспана алуға арналған Ұлттық банктің мемлекеттік бағдарламасы.",
        features: ["Бастапқы жарна: тұрғын үй құнының 20%-ынан бастап", "Мөлшерлеме: 7% (өзгермейді)", "Несие мерзімі: 25 жылға дейін", "Ең жоғарғы сома: Астана, Алматы үшін - 25 млн тг.", "Қойылатын талап: Қазақстан аумағында сіздің атыңызда ешқандай тұрғын үй болмауы тиіс."],
        requirements: ["Жеке куәлік", "Жұмыс орнынан соңғы 6 айдағы табысы туралы анықтама (зейнетақы жарналарымен бірге)", "Сатып алынатын пәтердің бағалау актісі мен құжаттары"],
        steps: ["Құрылыс компаниясынан пайдалануға берілген жаңа үй таңдау", "Серіктес банктерге (Halyk, BCC, Freedom Bank) жүгіну", "Табысты растап, несиені рәсімдеу"]
    },
    nauryz_social: {
        programId: "nauryz",
        title: "«Наурыз» ипотекасы", rate: "7%",
        description: "Халықтың әлеуметтік осал топтарына және әкімдік кезегінде тұрғандарға арналған 2026 жылғы жеңілдетілген бағдарлама.",
        features: ["Бастапқы жарна: 10-20%", "Мөлшерлеме: 7%", "Максималды сома: Алматы мен Астана үшін 36 млн. тг"],
        requirements: ["Кезекте тұрғаныңызды растайтын статус", "Отбасы банкінде кемінде 2 млн теңгелік депозиттің болуы", "Соңғы 6 айдағы табыс"],
        steps: ["Шотта 2 миллион теңгенің бар екеніне көз жеткізу", "Baspana Market порталында өтінім беру", "Мақұлданғаннан кейін жаңа үй таңдау"]
    },
    nauryz_jumysker: {
        programId: "nauryz_jumysker",
        title: "«Наурыз жұмыскер»", rate: "9%",
        description: "Кез келген салада ресми жұмыс істейтін азаматтарға арналған ауқымды бағдарлама.",
        features: ["Бастапқы жарна: 20%", "Мөлшерлеме: 9%", "Басты талап: 3-6 айдан бастап ресми табыстың болуы."],
        requirements: ["Отбасы банкіндегі депозит (2 млн тг)", "Соңғы 5 жылда атыңызда үйдің болмауы", "Жұмыс орнынан анықтама"],
        steps: ["Табысыңызды растау (кемінде 3 ай үздіксіз)", "Baspana Market арқылы өтінім беру", "Жаңа ғимараттан пәтер таңдау"]
    },
    otau: {
        programId: "otau",
        title: "«Отау» бағдарламасы", rate: "9%",
        description: "Отбасы банкінің тұрақты салымшыларына және жастарға арналған классикалық бағдарлама.",
        features: ["Бастапқы жарна: 20%", "Мөлшерлеме: 9%", "Алматы/Астанада тек жаңа үй, өңірлерде екінші нарықтан да алуға болады."],
        requirements: ["Депозитіңіздің ашылғанына кемінде 18 ай болуы керек.", "Депозитте жиналған сома 1 млн теңгеден көп болуы тиіс."],
        steps: ["Депозит мерзімін тексеру", "Baspana Market-те өтінім қалдыру", "Ұпай саны бойынша тізімге ілігу"]
    },
    zhas_otbasy: {
        programId: "zhas_otbasy",
        title: "«Жас Отбасы»", rate: "6% - 5%",
        description: "Жаңадан отау құрған (некеге тұрғанына 5 жыл толмаған) жас отбасыларға арналған өнім.",
        features: ["Бастапқы жарна: 50%", "Мөлшерлеме: бастапқыда 6%, кейін 5%-ға дейін төмендейді", "Кез келген дайын үй немесе жер алып, үй салуға болады."],
        requirements: ["Неке туралы куәлік (некеге 5 жыл толмауы тиіс)", "Үй құнының 50%-ы қолда болуы (немесе 1 жыл жинау керек)"],
        steps: ["Отбасы банкінен ерлі-зайыптының біреуі депозит ашу", "Үй құнының 50%-ын депозитке салу", "Банкке барып несиені ресімдеу"]
    },
    standard_50_50: {
        programId: "standard_50_50",
        title: "Аралық заем (50/50)", rate: "5%",
        description: "Сіздің жинағыңыз жеткілікті болғандықтан, ешқандай кезексіз стандартты жүйемен кез келген үйді алу тиімді.",
        features: ["Қолыңызда үй құнының 50%-ы бар болса қолданылады", "Мөлшерлеме: 5% (3 жылдан соң 3.5%-ға дейін төмендейді)", "Кез келген үйді шектеусіз алуға болады"],
        requirements: ["Пәтер құнының тең жартысы (50%) қолда болуы", "Пәтерді бағалау актісі"],
        steps: ["Таңдаған үйіңізді табу", "Отбасы банкіне барып депозит ашу", "Кредиттік өтінім беру"]
    },
    umay: {
        programId: "umay",
        title: "«Ұмай» әйелдер ипотекасы", rate: "14.4%",
        description: "Қазақстандық әйелдерге арналған тиімді баспана бағдарламасы.",
        features: ["Тұрғын үйді қайталама нарықта да сатып алуға болады", "Меншікте басқа жылжымайтын мүліктің болуына шектеулер жоқ"],
        requirements: ["Әйел азаматша болуы", "Ресми табыстың болуы", "Екі қосалқы қарыз алушыны тартуға рұқсат"],
        steps: ["Табысты растау", "Кез келген нарықтан пәтер іздеу", "Банкке өтінім қалдыру"]
    },
    oz_uim: {
        programId: "oz_uim",
        title: "«Өз үйім»", rate: "6% - 7%",
        description: "Қолында бастапқы жарнасы бар адамдар үшін үлкен сомада қарыз алуға мүмкіндік береді.",
        features: ["Бастапқы жарна: 20%", "Сыйақы: 6-7%", "Қарыз сомасы: 100 млн. теңгеге дейін"],
        requirements: ["Құрылыс компаниясынан салынған жаңа тұрғын үй болуы", "Табысты растау"],
        steps: ["Үйдің құнының кемінде 20%-ын жинау", "Таңдаған үйіңізді табу", "Отбасы банкі арқылы несиені ресімдеу"]
    },
    green_mortgage: {
        programId: "green_mortgage",
        title: "«Жасыл ипотека»", rate: "7% - 12.5%",
        description: "«Жасыл стандартқа» сәйкес келетін нысаннан тұрғын үй сатып алуға берілетін қарыз.",
        features: ["Бастапқы жарна: 20%-дан бастап", "Сыйақы: 12,5 %. Мүгедектігі бар адамдар үшін - 7%"],
        requirements: ["Құрылыс компаниясы берген ӨМІР, BREEAM немесе LEED сертификаттары"],
        steps: ["Экологиялық үйлер тізімін алу", "Құрылыс компаниясынан сертификат сұрау", "Несиеге өтінім беру"]
    },
    nauryz_askery: {
        programId: "nauryz_askery",
        title: "«Наурыз Әскери»", rate: "9%",
        description: "Әскери қызметшілерге арналған жаңа ауқымды бағдарлама.",
        features: ["Бастапқы жарна: 0-50%", "Сыйақы: 9%", "Ерекшелік: 20%-50% бастапқы жарна салынса, 80 млн. теңгеге дейін үй алуға болады"],
        requirements: ["Әскери немесе арнайы орган қызметкері екенін растау", "Тұрғын үй төлемдерінің (ТҮТ) түсіп тұруы"],
        steps: ["Тұрғын үй төлемдерін растау", "Baspana Market арқылы жаңа үй таңдау", "Өтінім беру"]
    },
    askery_baspana: {
        programId: "askery_baspana",
        title: "«Әскери баспана»", rate: "6% - 8%",
        description: "Әскери қызметшілерге ескі үйлерді де сатып алуға мүмкіндік беретін бағдарлама.",
        features: ["Кез келген үйді сатып алуға болады", "Бастапқы жарна: 15-50%", "Төлемді мемлекеттен бөлінетін ТҮТ арқылы жабуға болады"],
        requirements: ["Әскери шоттың болуы", "Жеткілікті жинақ немесе ТҮТ қаражаты"],
        steps: ["Отбасы банкінде арнайы 'Әскери' шот ашу", "ТҮТ аударымдарын сол шотқа бағыттау", "Банкке барып рәсімдеу"]
    }
};
const hiddenProgramIds = <?php echo json_encode($hidden_program_ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const customProgramsData = <?php echo json_encode(baspana_custom_programs_for_js(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
Object.assign(programsData, customProgramsData);

function filterProgramCards() {
    const input = document.getElementById('programSearch');
    const query = (input?.value || '').trim().toLowerCase();
    document.querySelectorAll('.finder-card').forEach((card) => {
        const haystack = card.dataset.search || '';
        card.style.display = haystack.includes(query) ? 'flex' : 'none';
    });
}

function analyzeData() {
    const age = parseInt(document.getElementById('userAge').value);
    const city = document.getElementById('userCity').value;
    const status = document.getElementById('userStatus').value;
    const savings = parseInt(document.getElementById('userSavings').value) || 0;
    const income = parseInt(document.getElementById('userIncome').value);
    const resultContainer = document.getElementById('result-container');
    const resultSection = document.getElementById('result-section');

    if (!age || !income) { alert("Өтінеміз, мәліметтерді толық енгізіңіз!"); return; }

    let matchedPrograms = [];
    const getProg = (key) => {
        if (hiddenProgramIds.includes(key) || !programsData[key]) return null;
        return JSON.parse(JSON.stringify(programsData[key]));
    };

    if (savings >= 4000000 && income >= 300000) {
        let p = getProg('baspana_7_20_25');
        if (p) {
            p.matchReason = "Жинағыңыз бен табысыңыз жеткілікті";
            matchedPrograms.push(p);
        }
    }
    if (status === 'social') {
        let p = getProg('nauryz_social');
        if (p) {
            if(savings < 2000000) p.features.push("<strong style='color: var(--kaspi-red);'>Маңызды:</strong> Өтінім үшін депозитте 2 млн тг болуы шарт.");
            p.matchReason = "Әлеуметтік осал топ мәртебесіне сай";
            matchedPrograms.push(p);
        }
    }
    if (status !== 'social' && income > 150000) {
        let p = getProg('nauryz_jumysker');
        if (p) {
            if(savings < 2000000) p.features.push("<strong style='color: var(--kaspi-red);'>Маңызды:</strong> Депозитке 2 млн тг толықтыру керек.");
            p.matchReason = "Ресми табысы бар жұмыскерлерге арналған";
            matchedPrograms.push(p);
        }
    }
    if (age <= 35 || status === 'young_family' || status === 'young_pro') {
        let p = getProg('otau');
        if (p) {
            p.features.push("<strong style='color: var(--text-dark)'>Ескерту:</strong> Депозит мерзімі 18 айға толмаса, күтуге тура келеді.");
            p.matchReason = "Жасыңызға немесе мәртебеңізге сай келеді";
            matchedPrograms.push(p);
        }
    }
    if (status === 'young_family') {
        let p = getProg('zhas_otbasy');
        if (p) {
            p.matchReason = "Жас отбасы мәртебесіне сәйкес арнайы ұсыныс";
            matchedPrograms.push(p);
        }
    }
    if (savings >= 8000000) {
        let p = getProg('standard_50_50');
        if (p) {
            p.matchReason = "Жинағыңыз үй құнының 50%-на жетуі мүмкін";
            matchedPrograms.push(p);
        }
    }
    if (income >= 200000 && status !== 'military') {
        let p = getProg('umay');
        if (p) {
            p.matchReason = "Әйел азаматшаларға арналған мүмкіндік (егер әйел болсаңыз)";
            matchedPrograms.push(p);
        }
    }
    if (savings >= 15000000) {
        let p = getProg('oz_uim');
        if (p) {
            p.matchReason = "Ірі көлемдегі жинағыңыз кезексіз үй алуға мүмкіндік береді";
            matchedPrograms.push(p);
        }
    }
    if (income >= 400000 && city === 'megapolis') {
        let p = getProg('green_mortgage');
        if (p) {
            p.matchReason = "Жоғары табысыңыз экологиялық үйлер алуға сай";
            matchedPrograms.push(p);
        }
    }
    if (status === 'military') {
        let p1 = getProg('nauryz_askery');
        if (p1) {
            p1.matchReason = "Әскери қызметкерлерге арналған жаңа бағдарлама";
            matchedPrograms.push(p1);
        }

        if (savings >= 3000000) {
            let p2 = getProg('askery_baspana');
            if (p2) {
                p2.matchReason = "Жинағыңыз бар әскери қызметкер ретінде";
                matchedPrograms.push(p2);
            }
        }
    }

    Object.values(customProgramsData).forEach((customProgram) => {
        const statuses = customProgram.matchStatuses || [];
        const cityMatches = !customProgram.matchCity || customProgram.matchCity === 'any' || customProgram.matchCity === city;
        const statusMatches = statuses.length === 0 || statuses.includes(status);
        const ageMatches = (!customProgram.minAge || age >= customProgram.minAge) && (!customProgram.maxAge || age <= customProgram.maxAge);
        const savingsMatches = !customProgram.minSavings || savings >= customProgram.minSavings;
        const incomeMatches = !customProgram.minIncome || income >= customProgram.minIncome;

        if (cityMatches && statusMatches && ageMatches && savingsMatches && incomeMatches) {
            const p = JSON.parse(JSON.stringify(customProgram));
            p.matchReason = p.matchReason || "Админ қосқан бағдарлама шарттарына сәйкес келеді";
            matchedPrograms.push(p);
        }
    });

    if (matchedPrograms.length === 0) {
        resultContainer.innerHTML = `<h3 style="text-align:center; color: var(--kaspi-red);">Өкінішке қарай, мәліметтерге сәйкес бағдарлама табылмады.</h3>`;
        resultSection.style.display = "block";
        return;
    }

    let finalHTML = `<h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 5px; color: var(--text-dark); text-align: center;">Сізге сәйкес келетін бағдарламалар (${matchedPrograms.length}):</h2>`;
    finalHTML += `<div class="programs-grid">`;

    matchedPrograms.forEach((prog) => {
        let featuresHTML = (prog.features || []).map(f => `<li><i class="fas fa-check"></i> <span>${f}</span></li>`).join('');
        let reqsHTML = (prog.requirements || []).map(r => `<li><i class="fas fa-file-alt"></i> <span>${r}</span></li>`).join('');
        let stepsHTML = (prog.steps || []).map(s => `<li>${s}</li>`).join('');
        
        finalHTML += `
            <div class="program-tile">
                <div class="tile-header">
                    <div>
                        <div class="tile-title">${prog.title}</div>
                        <div class="tile-reason"><i class="fas fa-magic"></i> ${prog.matchReason}</div>
                    </div>
                    <div class="rate-badge">${prog.rate}</div>
                </div>
                <p style="color: var(--text-gray); margin-bottom: 20px; font-size: 0.95rem; line-height: 1.6;">${prog.description}</p>
                
                <h4 style="margin-bottom: 12px; color: var(--text-dark); font-size: 1rem;">Шарттар:</h4>
                <ul class="info-list" style="margin-bottom: 16px;">${featuresHTML}</ul>
                
                <h4 style="margin-bottom: 12px; color: var(--text-dark); font-size: 1rem;">Қажетті құжаттар:</h4>
                <ul class="info-list" style="background: var(--bg-gray); border: 1px solid var(--border-light); padding: 12px; border-radius: 12px; font-size: 0.9rem;">${reqsHTML}</ul>
                
                <div class="steps-container">
                    <h4>Өтінім қадамдары</h4>
                    <ol>${stepsHTML}</ol>
                </div>
                
                <a href="program.php?id=${encodeURIComponent(prog.programId)}" class="btn-main" style="margin-top: 24px; height: 50px;">
                    <i class="fas fa-circle-info"></i> Толығырақ білу
                </a>
            </div>
        `;
    });

    finalHTML += `</div>`;
    resultContainer.innerHTML = finalHTML;
    resultSection.style.display = "block";
    setTimeout(() => { resultSection.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100);
}

function getDetailedRate() {
    const rate = parseFloat(document.getElementById('interestRate').value);
    return Number.isFinite(rate) && rate > 0 ? rate : 0;
}
function updateDetailedCalc() {
    let price = parseInt(document.getElementById('sliderPrice').value);
    let deposit = parseInt(document.getElementById('sliderDeposit').value);
    if (deposit > price) { deposit = price; document.getElementById('sliderDeposit').value = deposit; }
    let term = parseInt(document.getElementById('sliderTerm').value);
    let detailedRate = getDetailedRate();

    document.getElementById('dispPrice').innerText = price.toLocaleString('ru-RU') + " ₸";
    document.getElementById('dispDeposit').innerText = deposit.toLocaleString('ru-RU') + " ₸";
    document.getElementById('dispTerm').innerText = term + " ай";

    let loanAmount = price - deposit;
    if(loanAmount < 0) loanAmount = 0;
    document.getElementById('resLoanAmt').innerText = loanAmount.toLocaleString('ru-RU') + " ₸";
    document.getElementById('resRateAmt').innerText = detailedRate.toFixed(1) + " %";

    let monthlyRate = detailedRate / 100 / 12;
    let monthlyPayment = 0;
    if (loanAmount > 0 && detailedRate > 0) {
        monthlyPayment = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, term)) / (Math.pow(1 + monthlyRate, term) - 1);
    } else if (loanAmount > 0 && detailedRate === 0) {
        monthlyPayment = loanAmount / term;
    }
    let overpayment = (monthlyPayment * term) - loanAmount;
    if (overpayment < 0) overpayment = 0;
    document.getElementById('resMonthlyAmt').innerText = Math.round(monthlyPayment).toLocaleString('ru-RU') + " ₸";
    document.getElementById('resOverpayAmt').innerText = Math.round(overpayment).toLocaleString('ru-RU') + " ₸";
}
window.addEventListener('DOMContentLoaded', () => { updateDetailedCalc(); });

function checkOrkenEligibility() {
    const q1 = document.getElementById('orkenQ1').value;
    const q2 = document.getElementById('orkenQ2').value;
    const q3 = document.getElementById('orkenQ3').value;
    const resultDiv = document.getElementById('orken-result');
    let isEligible = true;
    let errors = [];

    if (q1 === 'no') { isEligible = false; errors.push("Кезекке тек 18 жастан асқан азаматтар тұра алады."); }
    if (q2 === 'yes') { isEligible = false; errors.push("Соңғы 5 жылда үй болмауы тиіс."); }
    if (q3 === 'mega_less_3') { isEligible = false; errors.push("Мегаполисте кемінде 3 жыл тіркеу қажет."); }

    resultDiv.style.display = "block";
    if (isEligible) {
        resultDiv.style.background = "#E8F5E9"; resultDiv.style.color = "#2E7D32"; resultDiv.style.border = "1px solid #A5D6A7";
        resultDiv.innerHTML = "<strong><i class='fas fa-check-circle'></i> Құттықтаймыз!</strong><br><br> Сіздің мәліметтеріңіз «Өркен» порталының талаптарына сай келеді.<br><br>Электронды кілтіңізді (ЭЦҚ) дайындап, банкке хабарласуға болады.";
    } else {
        resultDiv.style.background = "#FEECEB"; resultDiv.style.color = "#C52222"; resultDiv.style.border = "1px solid #FAD2D2";
        resultDiv.innerHTML = "<strong><i class='fas fa-exclamation-circle'></i> Өкінішке қарай:</strong><br><ul style='margin-top: 15px; padding-left: 20px; text-align: left;'>" + errors.map(e => "<li style='margin-bottom: 8px;'>" + e + "</li>").join('') + "</ul>";
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('menuOverlay');
    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open'); overlay.classList.remove('open');
    } else {
        sidebar.classList.add('open'); overlay.classList.add('open');
    }
}

function openModal(modalId) {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('menuOverlay').classList.remove('open');
    
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('mainModalOverlay');
    
    overlay.style.display = 'block';
    modal.style.display = 'block';
    
    setTimeout(() => { overlay.classList.add('open'); modal.classList.add('open'); }, 10);
}

function closeAllModals() {
    const overlay = document.getElementById('mainModalOverlay');
    const modals = document.querySelectorAll('.modal-content');
    
    overlay.classList.remove('open');
    modals.forEach(m => m.classList.remove('open'));
    
    setTimeout(() => {
        overlay.style.display = 'none';
        modals.forEach(m => m.style.display = 'none');
        document.getElementById('app-success').style.display = 'none';
    }, 300);
}

function submitApplication() {
    const phone = document.getElementById('appPhone').value;
    if(phone.length < 5) {
        alert("Өтінеміз, телефон нөміріңізді дұрыс енгізіңіз!");
        return;
    }
    document.getElementById('app-success').style.display = 'block';
    setTimeout(() => { closeAllModals(); }, 3000);
}
</script>
</body>
</html>
