<?php
session_start();
require 'db.php';
require_once __DIR__ . '/admin_auth.php';
ensure_admin_schema($pdo);

// Егер жүйеге кірмеген болса, логинге жіберу
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";

// Мәліметтерді жаңарту (Форма жіберілгенде)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $city = $_POST['city'];
    $social_status = $_POST['social_status'];
    $savings = $_POST['savings'];
    $income = $_POST['income'];

    $update_stmt = $pdo->prepare("UPDATE users SET name=?, age=?, city=?, social_status=?, savings=?, income=? WHERE id=?");
    if ($update_stmt->execute([$name, $age, $city, $social_status, $savings, $income, $user_id])) {
        $success_msg = "Мәліметтеріңіз сәтті сақталды!";
    }
}

// Пайдаланушының ең соңғы мәліметтерін алу
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$is_admin = current_user_is_admin($pdo);

// Прогресс бар үшін есептеу (Мақсатты жинақ ретінде шартты түрде 10 млн тг аламыз)
$current_savings = $user_data['savings'] ?: 0;
$target_savings = 10000000; 
if($current_savings > $target_savings) {
    $target_savings = $current_savings + 5000000; // Егер 10 млн-нан асса, мақсатты өсіреміз
}
$progress_percent = ($current_savings / $target_savings) * 100;
if($progress_percent > 100) $progress_percent = 100;
?>

<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Жеке кабинет - Baspana Smart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --kaspi-red: #E12A2A;
            --kaspi-red-hover: #C52222;
            --bg-gray: #F5F5F6;
            --bg-white: #FFFFFF;
            --text-dark: #1A1A1A;
            --text-gray: #757575;
            --border-light: #EBEBEB;
            --input-bg: #F1F1F1;
            --radius-card: 20px;
            --radius-btn: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-gray); color: var(--text-dark); line-height: 1.6; padding-top: 70px; }

        /* Жоғарғы Навигация */
        header { background: var(--bg-white); padding: 16px 5%; display: flex; justify-content: space-between; align-items: center; position: fixed; width: 100%; top: 0; z-index: 1000; border-bottom: 1px solid var(--border-light); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .hamburger-btn { font-size: 1.5rem; cursor: pointer; color: var(--text-dark); transition: 0.2s; }
        .hamburger-btn:hover { color: var(--kaspi-red); }
        .logo { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-back { display: flex; align-items: center; gap: 8px; color: var(--text-dark); text-decoration: none; font-weight: 600; font-size: 0.95rem; background: var(--bg-gray); padding: 8px 16px; border-radius: var(--radius-btn); transition: 0.2s; }
        .btn-back:hover { background: #E4E4E4; }
        
        /* Бүйірлік мәзір */
        .sidebar { position: fixed; top: 0; left: -320px; width: 300px; height: 100vh; background: var(--bg-white); z-index: 2000; transition: 0.3s ease; display: flex; flex-direction: column; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar.open { left: 0; }
        .sidebar-header { padding: 24px 20px 16px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header i { cursor: pointer; font-size: 1.2rem; color: var(--text-dark); transition: 0.2s; }
        .sidebar-header i:hover { color: var(--kaspi-red); }
        .sidebar-item { padding: 14px 16px; display: flex; align-items: center; gap: 16px; color: var(--text-dark); text-decoration: none; font-weight: 500; transition: 0.2s; border-radius: var(--radius-btn); margin: 4px 16px; font-size: 0.95rem; }
        .sidebar-item:hover { background: var(--bg-gray); }
        .sidebar-item.active { background: #FEECEB; color: var(--kaspi-red); }
        .sidebar-item i { width: 20px; text-align: center; color: var(--text-gray); }
        .sidebar-item.active i { color: var(--kaspi-red); }
        .sidebar-section-title { padding: 24px 16px 8px; font-size: 0.8rem; color: var(--text-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .menu-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1500; display: none; opacity: 0; transition: 0.3s; }
        .menu-overlay.open { opacity: 1; display: block; }

        /* ПРОФИЛЬ СТИЛДЕРІ */
        .profile-container { max-width: 1000px; margin: 40px auto 80px; padding: 0 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 32px; }
        
        .profile-card { background: var(--bg-white); border-radius: var(--radius-card); padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid var(--border-light); text-align: center; }
        .avatar-circle { width: 100px; height: 100px; background: #FEECEB; color: var(--kaspi-red); font-size: 2.5rem; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(225, 42, 42, 0.15); }
        .profile-name { font-size: 1.5rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
        .profile-email { color: var(--text-gray); font-size: 0.95rem; margin-bottom: 20px; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 8px; background: #ECFDF5; color: #059669; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; margin-bottom: 24px; }
        
        /* ПРОГРЕСС БАР (МАҚСАТ) */
        .goal-box { background: var(--bg-gray); padding: 20px; border-radius: 16px; text-align: left; border: 1px dashed #CCC; }
        .goal-title { font-size: 0.9rem; color: var(--text-gray); font-weight: 600; margin-bottom: 8px; display: flex; justify-content: space-between; }
        .goal-amount { font-size: 1.4rem; font-weight: 800; color: var(--text-dark); margin-bottom: 12px; }
        .progress-bg { width: 100%; height: 10px; background: #E0E0E0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--kaspi-red); width: <?php echo $progress_percent; ?>%; border-radius: 10px; transition: width 1s ease-in-out; }

        /* ФОРМА СТИЛДЕРІ */
        .settings-card { background: var(--bg-white); border-radius: var(--radius-card); padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid var(--border-light); }
        .settings-card h3 { margin-bottom: 24px; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--text-dark); }
        .settings-card h3 i { color: var(--kaspi-red); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .input-group { margin-bottom: 20px; }
        .input-group.full-width { grid-column: 1 / -1; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; color: var(--text-gray); }
        .input-group input, .input-group select { width: 100%; height: 52px; padding: 0 16px; border-radius: var(--radius-btn); border: 1px solid var(--border-light); font-size: 1rem; font-family: inherit; background: var(--input-bg); color: var(--text-dark); font-weight: 500; transition: 0.2s; appearance: none; }
        .input-group select { background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23757575' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px; }
        .input-group input:focus, .input-group select:focus { background: var(--bg-white); border-color: var(--kaspi-red); outline: none; box-shadow: 0 0 0 3px rgba(225,42,42,0.1); }
        
        .btn-main { background: var(--kaspi-red); color: #ffffff; border: none; height: 56px; border-radius: var(--radius-btn); font-size: 1.05rem; font-weight: 600; cursor: pointer; width: 100%; transition: 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-main:hover { background: var(--kaspi-red-hover); transform: translateY(-2px); box-shadow: 0 8px 16px rgba(225,42,42,0.2); }

        .success-msg { background: #ECFDF5; color: #059669; padding: 16px; border-radius: 12px; border: 1px solid #A7F3D0; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; font-weight: 500; }

        @media (max-width: 900px) {
            .profile-container { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="menu-overlay" id="menuOverlay" onclick="toggleSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo" style="text-decoration:none;"><i class="fas fa-home" style="color:var(--kaspi-red);"></i> Baspana</a>
        <i class="fas fa-times" onclick="toggleSidebar()"></i>
    </div>
    <div class="sidebar-menu">
        <a href="index.php#calculator" class="sidebar-item"><i class="fas fa-magic"></i> Smart Талдау</a>
        <a href="index.php#detailed-calc" class="sidebar-item"><i class="fas fa-sliders-h"></i> Толық калькулятор</a>
        <div class="sidebar-section-title">Ипотека түрлері</div>
        <a href="program.php?id=baspana_7_20_25" class="sidebar-item"><i class="fas fa-home"></i> «7-20-25»</a>
        <a href="program.php?id=nauryz_social" class="sidebar-item"><i class="fas fa-leaf"></i> «Наурыз»</a>
        <a href="program.php?id=nauryz_jumysker" class="sidebar-item"><i class="fas fa-briefcase"></i> «Наурыз жұмыскер»</a>
        <a href="program.php?id=otau" class="sidebar-item"><i class="fas fa-key"></i> «Отау»</a>
        <a href="program.php?id=zhas_otbasy" class="sidebar-item"><i class="fas fa-users"></i> «Жас Отбасы»</a>
        <a href="program.php?id=standard_50_50" class="sidebar-item"><i class="fas fa-percent"></i> Аралық заем</a>
        <a href="program.php?id=umay" class="sidebar-item"><i class="fas fa-female"></i> «Ұмай»</a>
        <a href="program.php?id=oz_uim" class="sidebar-item"><i class="fas fa-building"></i> «Өз үйім»</a>
        <a href="program.php?id=green_mortgage" class="sidebar-item"><i class="fas fa-seedling"></i> «Жасыл ипотека»</a>
        <a href="program.php?id=nauryz_askery" class="sidebar-item"><i class="fas fa-shield-alt"></i> «Наурыз Әскери»</a>
        <a href="program.php?id=askery_baspana" class="sidebar-item"><i class="fas fa-star"></i> «Әскери баспана»</a>
    </div>
    <div class="sidebar-bottom">
        <?php if($is_admin): ?>
            <a href="admin.php" class="sidebar-item"><i class="fas fa-user-shield"></i> Админ панель</a>
        <?php endif; ?>
        <a href="profile.php" class="sidebar-item active"><i class="fas fa-user"></i> Жеке кабинет</a>
        <a href="logout.php" class="sidebar-item" style="color: var(--kaspi-red);"><i class="fas fa-sign-out-alt"></i> Шығу</a>
    </div>
</div>

<header>
    <div class="header-left">
        <i class="fas fa-bars hamburger-btn" onclick="toggleSidebar()"></i>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Басты бетке қайту</a>
    </div>
</header>

<div class="profile-container">
    
    <!-- СОЛ ЖАҚ БАҒАН (ИНФО ЖӘНЕ ПРОГРЕСС) -->
    <div class="left-col">
        <div class="profile-card">
            <div class="avatar-circle">
                <?php echo mb_substr($user_data['name'], 0, 1); ?>
            </div>
            <h2 class="profile-name"><?php echo htmlspecialchars($user_data['name']); ?></h2>
            <div class="profile-email"><?php echo htmlspecialchars($user_data['email']); ?></div>

            <?php
                // Мәртебеге сай белгі қою
                $status_text = "Қарапайым жұмыскер";
                $status_icon = "fa-user";
                if($user_data['social_status'] == 'social') { $status_text = "Әлеуметтік осал топ"; $status_icon = "fa-hands-helping"; }
                if($user_data['social_status'] == 'young_family') { $status_text = "Жас отбасы"; $status_icon = "fa-users"; }
                if($user_data['social_status'] == 'young_pro') { $status_text = "Жас маман"; $status_icon = "fa-user-graduate"; }
                if($user_data['social_status'] == 'military') { $status_text = "Әскери қызметкер"; $status_icon = "fa-shield-alt"; }
            ?>
            <div class="status-badge">
                <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
            </div>

            <!-- ЖАҢА ФИШКА: ПРОГРЕСС БАР -->
            <div class="goal-box">
                <div class="goal-title">
                    <span>Менің жинағым</span>
                    <span>Мақсат: <?php echo number_format($target_savings, 0, '', ' '); ?> ₸</span>
                </div>
                <div class="goal-amount"><?php echo number_format($current_savings, 0, '', ' '); ?> ₸</div>
                <div class="progress-bg">
                    <div class="progress-fill"></div>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-gray); margin-top: 8px; text-align: center;">
                    Ипотекаға қол жеткізу үшін жинақты жалғастырыңыз!
                </p>
            </div>
        </div>
    </div>

    <!-- ОҢ ЖАҚ БАҒАН (ФОРМА ЖӘНЕ БАПТАУЛАР) -->
    <div class="right-col">
        <div class="settings-card">
            <h3><i class="fas fa-cog"></i> Жеке мәліметтерімді жаңарту</h3>
            <p style="color: var(--text-gray); margin-bottom: 24px; font-size: 0.95rem;">
                Осы мәліметтерді өзгертсеңіз, AI калькулятор сізге ең жаңа және дәл бағдарламаларды ұсынады.
            </p>

            <?php if(!empty($success_msg)): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form action="profile.php" method="POST">
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Толық аты-жөніңіз</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Жасыңыз</label>
                        <input type="number" name="age" value="<?php echo htmlspecialchars($user_data['age']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Аймақ</label>
                        <select name="city">
                            <option value="megapolis" <?php if($user_data['city']=='megapolis') echo 'selected'; ?>>Мегаполис (Аст/Алм/Шым)</option>
                            <option value="region" <?php if($user_data['city']=='region') echo 'selected'; ?>>Өңірлер / Ауыл</option>
                        </select>
                    </div>

                    <div class="input-group full-width">
                        <label>Әлеуметтік мәртебеңіз</label>
                        <select name="social_status">
                            <option value="standard" <?php if($user_data['social_status']=='standard') echo 'selected'; ?>>Жалпы топ (Жұмысшы, ЖК)</option>
                            <option value="social" <?php if($user_data['social_status']=='social') echo 'selected'; ?>>Әлеуметтік осал топ / Көпбалалы / Жетім</option>
                            <option value="young_family" <?php if($user_data['social_status']=='young_family') echo 'selected'; ?>>Жас отбасы (некеде 5 жылға дейін)</option>
                            <option value="young_pro" <?php if($user_data['social_status']=='young_pro') echo 'selected'; ?>>Жас маман / Ғалым / Дәрігер</option>
                            <option value="military" <?php if($user_data['social_status']=='military') echo 'selected'; ?>>Әскери қызметкер</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Жинағыңыз (₸)</label>
                        <input type="number" name="savings" value="<?php echo htmlspecialchars($user_data['savings']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Таза айлық табысыңыз (₸)</label>
                        <input type="number" name="income" value="<?php echo htmlspecialchars($user_data['income']); ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn-main" style="margin-top: 10px;">
                    <i class="fas fa-save"></i> Сақтау және Жаңарту
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('menuOverlay');
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            setTimeout(() => { overlay.style.display = 'none'; }, 300);
        } else {
            overlay.style.display = 'block';
            setTimeout(() => { sidebar.classList.add('open'); overlay.classList.add('open'); }, 10);
        }
    }
</script>

</body>
</html>
