<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/program_store.php';
require_admin($pdo);

$config = baspana_load_config();
$message = '';
$error = '';

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

function admin_selected($value, $current) {
    return (string)$value === (string)$current ? 'selected' : '';
}

function admin_checked($value, array $items) {
    return in_array($value, $items, true) ? 'checked' : '';
}

function admin_textarea_lines($items) {
    return admin_clean(implode("\n", is_array($items) ? $items : []));
}

function admin_form_program(?array $program = null) {
    return array_merge([
        'programId' => '',
        'title' => '',
        'rate' => '',
        'fee' => '',
        'term' => '',
        'max_amount' => '',
        'audience' => '',
        'icon' => 'fa-home',
        'color' => '#E12A2A',
        'desc' => '',
        'features' => [],
        'reqs' => [],
        'steps' => [],
        'match_statuses' => [],
        'match_city' => 'any',
        'min_age' => 0,
        'max_age' => 0,
        'min_savings' => 0,
        'min_income' => 0,
        'match_reason' => 'Админ қосқан бағдарлама шарттарына сәйкес келеді',
    ], $program ?? []);
}

function admin_request_status_label($status) {
    $labels = [
        'new' => 'Жаңа',
        'contacted' => 'Байланысты',
        'done' => 'Аяқталды',
    ];
    return $labels[$status] ?? 'Жаңа';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Қауіпсіздік қатесі. Бетті жаңартып, қайта көріңіз.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_settings') {
            $config['settings'] = array_merge($config['settings'], [
                'hero_title' => trim($_POST['hero_title'] ?? ''),
                'hero_text' => trim($_POST['hero_text'] ?? ''),
                'badge_programs' => trim($_POST['badge_programs'] ?? ''),
                'badge_rate' => trim($_POST['badge_rate'] ?? ''),
                'badge_ai' => trim($_POST['badge_ai'] ?? ''),
                'footer_text' => trim($_POST['footer_text'] ?? ''),
            ]);
            baspana_save_config($config);
            $message = 'Сайт мәтіндері сақталды.';
        }

        if ($action === 'save_visibility') {
            $default_ids = array_keys(baspana_default_programs_meta());
            $hidden = array_values(array_intersect($_POST['hidden_programs'] ?? [], $default_ids));
            $config['hidden_programs'] = $hidden;
            baspana_save_config($config);
            $message = 'Бағдарламалардың көрінуі жаңартылды.';
        }

        if ($action === 'save_program') {
            $existing_id = $_POST['existing_id'] ?? '';
            $existing = $existing_id && isset($config['custom_programs'][$existing_id]) ? $config['custom_programs'][$existing_id] : null;
            $program = baspana_program_from_post($_POST, $existing);

            if ($program['title'] === '' || $program['rate'] === '' || $program['desc'] === '') {
                $error = 'Бағдарлама атауы, мөлшерлеме және сипаттамасы міндетті.';
            } else {
                if ($existing_id && $existing_id !== $program['programId']) {
                    unset($config['custom_programs'][$existing_id]);
                }
                $config['custom_programs'][$program['programId']] = $program;
                baspana_save_config($config);
                $message = 'Ипотекалық бағдарлама сақталды.';
            }
        }

        if ($action === 'delete_program') {
            $program_id = $_POST['program_id'] ?? '';
            if (isset($config['custom_programs'][$program_id])) {
                unset($config['custom_programs'][$program_id]);
                baspana_save_config($config);
                $message = 'Бағдарлама жойылды.';
            }
        }

        if ($action === 'update_request_status') {
            if (baspana_update_request_status($_POST['request_id'] ?? '', $_POST['status'] ?? 'new')) {
                $message = 'Өтініш статусы жаңартылды.';
            }
        }

        if ($action === 'delete_request') {
            if (baspana_delete_request($_POST['request_id'] ?? '')) {
                $message = 'Өтініш жойылды.';
            }
        }

        $config = baspana_load_config();
    }
}

$edit_id = $_GET['edit'] ?? '';
$editing_program = $edit_id && isset($config['custom_programs'][$edit_id]) ? $config['custom_programs'][$edit_id] : null;
$form_program = admin_form_program($editing_program);
$default_programs = baspana_default_programs_meta();
$hidden_programs = $config['hidden_programs'];
$custom_programs = $config['custom_programs'];
$settings = $config['settings'];
$requests = array_reverse(baspana_load_requests());
$new_request_count = count(array_filter($requests, fn($request) => ($request['status'] ?? 'new') === 'new'));
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель - Baspana Smart</title>
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
            --success-bg: #ECFDF5;
            --success-text: #047857;
            --danger-bg: #FEECEB;
            --danger-text: #C52222;
            --radius-card: 14px;
            --radius-btn: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #F7F7F8; color: var(--text-dark); line-height: 1.5; padding-top: 72px; }
        header { position: fixed; top: 0; left: 0; right: 0; background: var(--bg-white); border-bottom: 1px solid var(--border-light); padding: 16px 5%; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .logo { color: var(--text-dark); text-decoration: none; font-size: 1.25rem; font-weight: 800; display: inline-flex; align-items: center; gap: 10px; }
        .logo i { color: var(--kaspi-red); }
        .top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .top-actions a { text-decoration: none; color: var(--text-dark); font-weight: 600; background: var(--bg-gray); border-radius: var(--radius-btn); padding: 10px 14px; display: inline-flex; gap: 8px; align-items: center; }
        .top-actions a:hover { background: #E4E4E4; }
        main { max-width: 1180px; margin: 36px auto 70px; padding: 0 20px; }
        .admin-title { display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; margin-bottom: 24px; }
        h1 { font-size: 2rem; letter-spacing: 0; }
        .muted { color: var(--text-gray); font-size: 0.95rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-box { background: #fff; border: 1px solid var(--border-light); border-radius: var(--radius-card); padding: 20px; }
        .stat-label { color: var(--text-gray); font-size: 0.84rem; font-weight: 600; margin-bottom: 8px; }
        .stat-value { font-size: 1.8rem; font-weight: 800; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        .card { background: var(--bg-white); border: 1px solid var(--border-light); border-radius: var(--radius-card); padding: 28px; box-shadow: none; }
        .card.full { grid-column: 1 / -1; }
        .card h2 { font-size: 1.25rem; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: var(--kaspi-red); }
        .card-intro { color: var(--text-gray); margin-bottom: 22px; font-size: 0.94rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field { margin-bottom: 16px; }
        .field.full { grid-column: 1 / -1; }
        label { display: block; color: var(--text-gray); font-weight: 600; font-size: 0.86rem; margin-bottom: 7px; }
        input, textarea, select { width: 100%; border: 1px solid transparent; background: var(--input-bg); color: var(--text-dark); border-radius: var(--radius-btn); padding: 13px 14px; font-size: 0.98rem; outline: none; transition: 0.2s; }
        input:focus, textarea:focus, select:focus { background: #fff; border-color: var(--kaspi-red); box-shadow: 0 0 0 3px rgba(225,42,42,0.1); }
        textarea { min-height: 108px; resize: vertical; }
        .btn-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .btn { border: none; border-radius: var(--radius-btn); padding: 13px 18px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-primary { background: var(--kaspi-red); color: #fff; }
        .btn-primary:hover { background: var(--kaspi-red-hover); }
        .btn-light { background: var(--bg-gray); color: var(--text-dark); }
        .btn-light:hover { background: #E4E4E4; }
        .btn-danger { background: var(--danger-bg); color: var(--danger-text); }
        .notice { padding: 14px 16px; border-radius: var(--radius-btn); margin-bottom: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .notice.success { background: var(--success-bg); color: var(--success-text); border: 1px solid #A7F3D0; }
        .notice.error { background: var(--danger-bg); color: var(--danger-text); border: 1px solid #FAD2D2; }
        .program-list { display: grid; gap: 12px; }
        .program-row { display: flex; justify-content: space-between; gap: 16px; align-items: center; background: var(--bg-gray); border-radius: var(--radius-btn); padding: 14px; }
        .program-main { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .program-main i { color: var(--kaspi-red); width: 20px; text-align: center; }
        .program-title { font-weight: 700; overflow-wrap: anywhere; }
        .program-meta { color: var(--text-gray); font-size: 0.84rem; }
        .request-list { display: grid; gap: 12px; }
        .request-row { display: grid; grid-template-columns: 1.2fr 1fr auto; gap: 14px; align-items: center; background: var(--bg-gray); border-radius: var(--radius-btn); padding: 16px; }
        .request-name { font-weight: 800; margin-bottom: 4px; }
        .request-line { color: var(--text-gray); font-size: 0.88rem; overflow-wrap: anywhere; }
        .status-pill { display: inline-flex; align-items: center; gap: 6px; width: fit-content; padding: 6px 10px; border-radius: 999px; background: #fff; color: var(--text-dark); font-size: 0.8rem; font-weight: 800; margin-bottom: 8px; }
        .status-pill.new { color: var(--kaspi-red); background: var(--danger-bg); }
        .status-pill.contacted { color: #2563EB; background: #EFF6FF; }
        .status-pill.done { color: var(--success-text); background: var(--success-bg); }
        .request-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .request-actions select { min-width: 140px; background: #fff; }
        .inline-form { margin: 0; }
        .visibility-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 10px; }
        .check-item { display: flex; align-items: center; gap: 10px; background: var(--bg-gray); border-radius: var(--radius-btn); padding: 12px; font-weight: 600; }
        .check-item input { width: auto; }
        .hint { color: var(--text-gray); font-size: 0.82rem; margin-top: 6px; }
        @media (max-width: 900px) {
            .grid, .form-grid, .stats-grid, .request-row { grid-template-columns: 1fr; }
            .card.full, .field.full { grid-column: auto; }
            .admin-title, header { flex-direction: column; align-items: stretch; }
            .top-actions a { justify-content: center; }
            .request-actions { justify-content: flex-start; }
        }
    </style>
</head>
<body>
<header>
    <a href="index.php" class="logo"><i class="fas fa-home"></i> Baspana Smart</a>
    <div class="top-actions">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Сайтқа қайту</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Шығу</a>
    </div>
</header>

<main>
    <div class="admin-title">
        <div>
            <h1>Админ панель</h1>
            <p class="muted">Бағдарламаларды, басты бет мәтіндерін және сайтта көрінетін бөлімдерді басқарыңыз.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="notice success"><i class="fas fa-check-circle"></i> <?php echo admin_clean($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice error"><i class="fas fa-exclamation-circle"></i> <?php echo admin_clean($error); ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Жаңа өтініштер</div>
            <div class="stat-value"><?php echo (int)$new_request_count; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Барлық өтініштер</div>
            <div class="stat-value"><?php echo count($requests); ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Админ қосқан бағдарламалар</div>
            <div class="stat-value"><?php echo count($custom_programs); ?></div>
        </div>
    </div>

    <div class="grid">
        <section class="card full">
            <h2><i class="fas fa-inbox"></i> Кеңес алуға өтініштер</h2>
            <p class="card-intro">Бағдарлама бетінен жіберілген өтініштер осы жерге түседі.</p>
            <?php if (empty($requests)): ?>
                <p class="muted">Әзірге өтініш түскен жоқ.</p>
            <?php else: ?>
                <div class="request-list">
                    <?php foreach ($requests as $request): ?>
                        <?php $status = $request['status'] ?? 'new'; ?>
                        <div class="request-row">
                            <div>
                                <div class="status-pill <?php echo admin_clean($status); ?>"><i class="fas fa-circle"></i> <?php echo admin_clean(admin_request_status_label($status)); ?></div>
                                <div class="request-name"><?php echo admin_clean($request['name'] ?? ''); ?></div>
                                <div class="request-line"><i class="fas fa-phone"></i> <?php echo admin_clean($request['phone'] ?? ''); ?></div>
                                <?php if (!empty($request['email'])): ?>
                                    <div class="request-line"><i class="fas fa-envelope"></i> <?php echo admin_clean($request['email']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="program-title"><?php echo admin_clean($request['program_title'] ?? $request['program_id'] ?? ''); ?></div>
                                <div class="request-line"><?php echo admin_clean(date('d.m.Y H:i', strtotime($request['created_at'] ?? 'now'))); ?></div>
                                <?php if (!empty($request['message'])): ?>
                                    <div class="request-line" style="margin-top: 8px;"><?php echo admin_clean($request['message']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="request-actions">
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_clean($_SESSION['admin_csrf_token']); ?>">
                                    <input type="hidden" name="action" value="update_request_status">
                                    <input type="hidden" name="request_id" value="<?php echo admin_clean($request['id'] ?? ''); ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="new" <?php echo admin_selected('new', $status); ?>>Жаңа</option>
                                        <option value="contacted" <?php echo admin_selected('contacted', $status); ?>>Байланысты</option>
                                        <option value="done" <?php echo admin_selected('done', $status); ?>>Аяқталды</option>
                                    </select>
                                </form>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Бұл өтінішті жою керек пе?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_clean($_SESSION['admin_csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete_request">
                                    <input type="hidden" name="request_id" value="<?php echo admin_clean($request['id'] ?? ''); ?>">
                                    <button class="btn btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2><i class="fas fa-pen-to-square"></i> Сайт мәтіндері</h2>
            <p class="card-intro">Басты беттегі тақырып, қысқаша түсіндірме, бейдждер және футер мәтіні.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo admin_clean($_SESSION['admin_csrf_token']); ?>">
                <input type="hidden" name="action" value="save_settings">
                <div class="field">
                    <label>Басты тақырып</label>
                    <textarea name="hero_title" required><?php echo admin_clean($settings['hero_title']); ?></textarea>
                    <div class="hint">Жолды бөлу үшін Enter басыңыз.</div>
                </div>
                <div class="field">
                    <label>Түсіндірме мәтін</label>
                    <textarea name="hero_text" required><?php echo admin_clean($settings['hero_text']); ?></textarea>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>1-бейдж</label>
                        <input type="text" name="badge_programs" value="<?php echo admin_clean($settings['badge_programs']); ?>" required>
                    </div>
                    <div class="field">
                        <label>2-бейдж</label>
                        <input type="text" name="badge_rate" value="<?php echo admin_clean($settings['badge_rate']); ?>" required>
                    </div>
                    <div class="field full">
                        <label>3-бейдж</label>
                        <input type="text" name="badge_ai" value="<?php echo admin_clean($settings['badge_ai']); ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label>Футер мәтіні</label>
                    <input type="text" name="footer_text" value="<?php echo admin_clean($settings['footer_text']); ?>" required>
                </div>
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Мәтіндерді сақтау</button>
            </form>
        </section>

        <section class="card">
            <h2><i class="fas fa-eye-slash"></i> Бағдарламаларды жасыру</h2>
            <p class="card-intro">Стандарт бағдарламаны сайттан уақытша алып тастау үшін белгілеп сақтаңыз.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo admin_clean($_SESSION['admin_csrf_token']); ?>">
                <input type="hidden" name="action" value="save_visibility">
                <div class="visibility-grid">
                    <?php foreach ($default_programs as $program_id => $program): ?>
                        <label class="check-item">
                            <input type="checkbox" name="hidden_programs[]" value="<?php echo admin_clean($program_id); ?>" <?php echo admin_checked($program_id, $hidden_programs); ?>>
                            <span><i class="fas <?php echo admin_clean($program['icon']); ?>"></i> <?php echo admin_clean($program['title']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="btn-row" style="margin-top: 18px;">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Көрінуді сақтау</button>
                </div>
            </form>
        </section>

        <section class="card full" id="program-form">
            <h2><i class="fas fa-building-columns"></i> <?php echo $editing_program ? 'Бағдарламаны өңдеу' : 'Жаңа ипотекалық бағдарлама қосу'; ?></h2>
            <p class="card-intro">Әр шартты, құжатты және қадамды жеке жолға жазыңыз. Талдау шарттары Smart Талдауда қашан шығатынын анықтайды.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo admin_clean($_SESSION['admin_csrf_token']); ?>">
                <input type="hidden" name="action" value="save_program">
                <input type="hidden" name="existing_id" value="<?php echo admin_clean($editing_program['programId'] ?? ''); ?>">

                <div class="form-grid">
                    <div class="field">
                        <label>Бағдарлама ID</label>
                        <input type="text" name="program_id" value="<?php echo admin_clean($form_program['programId']); ?>" placeholder="mysaly_zhana_bagdarlama">
                        <div class="hint">Бос қалса, атауынан автоматты жасалады.</div>
                    </div>
                    <div class="field">
                        <label>Атауы</label>
                        <input type="text" name="title" value="<?php echo admin_clean($form_program['title']); ?>" required>
                    </div>
                    <div class="field">
                        <label>Мөлшерлеме</label>
                        <input type="text" name="rate" value="<?php echo admin_clean($form_program['rate']); ?>" placeholder="7%" required>
                    </div>
                    <div class="field">
                        <label>Алғашқы жарна</label>
                        <input type="text" name="fee" value="<?php echo admin_clean($form_program['fee']); ?>" placeholder="20%-дан бастап">
                    </div>
                    <div class="field">
                        <label>Несие мерзімі</label>
                        <input type="text" name="term" value="<?php echo admin_clean($form_program['term']); ?>" placeholder="25 жылға дейін">
                    </div>
                    <div class="field">
                        <label>Максималды сома</label>
                        <input type="text" name="max_amount" value="<?php echo admin_clean($form_program['max_amount']); ?>" placeholder="30 млн ₸ дейін">
                    </div>
                    <div class="field">
                        <label>Кімдерге арналған?</label>
                        <input type="text" name="audience" value="<?php echo admin_clean($form_program['audience']); ?>" placeholder="Жас отбасылар">
                    </div>
                    <div class="field">
                        <label>Иконка класы</label>
                        <input type="text" name="icon" value="<?php echo admin_clean($form_program['icon']); ?>" placeholder="fa-home">
                        <div class="hint">Font Awesome класы, мысалы: fa-key, fa-leaf.</div>
                    </div>
                    <div class="field">
                        <label>Түс</label>
                        <input type="color" name="color" value="<?php echo admin_clean($form_program['color']); ?>">
                    </div>
                    <div class="field">
                        <label>Smart Талдауда шығу себебі</label>
                        <input type="text" name="match_reason" value="<?php echo admin_clean($form_program['match_reason']); ?>">
                    </div>
                    <div class="field full">
                        <label>Сипаттамасы</label>
                        <textarea name="desc" required><?php echo admin_clean($form_program['desc']); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Шарттар</label>
                        <textarea name="features"><?php echo admin_textarea_lines($form_program['features']); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Қажетті құжаттар</label>
                        <textarea name="reqs"><?php echo admin_textarea_lines($form_program['reqs']); ?></textarea>
                    </div>
                    <div class="field full">
                        <label>Өтінім қадамдары</label>
                        <textarea name="steps"><?php echo admin_textarea_lines($form_program['steps']); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Әлеуметтік мәртебелер</label>
                        <input type="text" name="match_statuses" value="<?php echo admin_clean(implode(',', $form_program['match_statuses'])); ?>" placeholder="standard,social,young_family">
                        <div class="hint">Бос қалса, барлық мәртебеге шығады.</div>
                    </div>
                    <div class="field">
                        <label>Аймақ</label>
                        <select name="match_city">
                            <option value="any" <?php echo admin_selected('any', $form_program['match_city']); ?>>Барлығы</option>
                            <option value="megapolis" <?php echo admin_selected('megapolis', $form_program['match_city']); ?>>Мегаполис</option>
                            <option value="region" <?php echo admin_selected('region', $form_program['match_city']); ?>>Өңірлер / Ауыл</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Минималды жас</label>
                        <input type="number" name="min_age" value="<?php echo (int)$form_program['min_age']; ?>" min="0">
                    </div>
                    <div class="field">
                        <label>Максималды жас</label>
                        <input type="number" name="max_age" value="<?php echo (int)$form_program['max_age']; ?>" min="0">
                    </div>
                    <div class="field">
                        <label>Минималды жинақ</label>
                        <input type="number" name="min_savings" value="<?php echo (int)$form_program['min_savings']; ?>" min="0">
                    </div>
                    <div class="field">
                        <label>Минималды табыс</label>
                        <input type="number" name="min_income" value="<?php echo (int)$form_program['min_income']; ?>" min="0">
                    </div>
                </div>

                <div class="btn-row">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Бағдарламаны сақтау</button>
                    <?php if ($editing_program): ?>
                        <a class="btn btn-light" href="admin.php#program-form"><i class="fas fa-plus"></i> Жаңа бағдарлама</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card full">
            <h2><i class="fas fa-list-check"></i> Админ қосқан бағдарламалар</h2>
            <p class="card-intro">Бұл тізімдегі бағдарламаларды толық өңдеуге немесе біржола жоюға болады.</p>
            <?php if (empty($custom_programs)): ?>
                <p class="muted">Әзірге админ қосқан бағдарлама жоқ.</p>
            <?php else: ?>
                <div class="program-list">
                    <?php foreach ($custom_programs as $program_id => $program): ?>
                        <div class="program-row">
                            <div class="program-main">
                                <i class="fas <?php echo admin_clean($program['icon'] ?? 'fa-home'); ?>"></i>
                                <div>
                                    <div class="program-title"><?php echo admin_clean($program['title'] ?? $program_id); ?></div>
                                    <div class="program-meta"><?php echo admin_clean($program_id); ?> · <?php echo admin_clean($program['rate'] ?? ''); ?></div>
                                </div>
                            </div>
                            <div class="btn-row">
                                <a class="btn btn-light" href="admin.php?edit=<?php echo rawurlencode($program_id); ?>#program-form"><i class="fas fa-pen"></i> Өңдеу</a>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Бұл бағдарламаны жою керек пе?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo admin_clean($_SESSION['admin_csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete_program">
                                    <input type="hidden" name="program_id" value="<?php echo admin_clean($program_id); ?>">
                                    <button class="btn btn-danger" type="submit"><i class="fas fa-trash"></i> Жою</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
