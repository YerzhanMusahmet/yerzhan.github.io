<?php
const BASPANA_CONFIG_DIR = __DIR__ . '/data';
const BASPANA_CONFIG_FILE = BASPANA_CONFIG_DIR . '/site_config.json';
const BASPANA_APPLICATIONS_FILE = BASPANA_CONFIG_DIR . '/consultation_requests.json';

function baspana_default_config() {
    return [
        'settings' => [
            'hero_title' => "Ипотекаңызды\nесептеңіз",
            'hero_text' => 'Отбасы банкінің және 7-20-25 сияқты мемлекеттік бағдарламалардың ішінен сіздің табысыңыз бен жинағыңызға ең тиімділерін талдап, тізіп береміз.',
            'badge_programs' => '11 Бағдарлама',
            'badge_rate' => '2% - 18% мөлшерлеме',
            'badge_ai' => 'AI Талдау',
            'footer_text' => '© 2026. Барлық ақпарат сайт ішінде қауіпсіз сақталады.',
        ],
        'custom_programs' => [],
        'hidden_programs' => [],
    ];
}

function baspana_default_programs_meta() {
    return [
        'baspana_7_20_25' => ['title' => '«7-20-25»', 'icon' => 'fa-home'],
        'nauryz_social' => ['title' => '«Наурыз» (Әлеуметтік)', 'icon' => 'fa-leaf'],
        'nauryz_jumysker' => ['title' => '«Наурыз жұмыскер»', 'icon' => 'fa-briefcase'],
        'otau' => ['title' => '«Отау»', 'icon' => 'fa-key'],
        'zhas_otbasy' => ['title' => '«Жас Отбасы»', 'icon' => 'fa-users'],
        'standard_50_50' => ['title' => 'Аралық заем (50/50)', 'icon' => 'fa-percent'],
        'umay' => ['title' => '«Ұмай» әйелдерге', 'icon' => 'fa-female'],
        'oz_uim' => ['title' => '«Өз үйім»', 'icon' => 'fa-building'],
        'green_mortgage' => ['title' => '«Жасыл ипотека»', 'icon' => 'fa-seedling'],
        'nauryz_askery' => ['title' => '«Наурыз Әскери»', 'icon' => 'fa-shield-alt'],
        'askery_baspana' => ['title' => '«Әскери баспана»', 'icon' => 'fa-star'],
    ];
}

function baspana_load_config() {
    if (!is_dir(BASPANA_CONFIG_DIR)) {
        mkdir(BASPANA_CONFIG_DIR, 0775, true);
    }

    $default = baspana_default_config();
    if (!file_exists(BASPANA_CONFIG_FILE)) {
        baspana_save_config($default);
        return $default;
    }

    $raw = file_get_contents(BASPANA_CONFIG_FILE);
    $config = json_decode($raw, true);
    if (!is_array($config)) {
        return $default;
    }

    $config['settings'] = array_merge($default['settings'], $config['settings'] ?? []);
    $config['custom_programs'] = is_array($config['custom_programs'] ?? null) ? $config['custom_programs'] : [];
    $config['hidden_programs'] = is_array($config['hidden_programs'] ?? null) ? array_values($config['hidden_programs']) : [];
    return $config;
}

function baspana_save_config(array $config) {
    if (!is_dir(BASPANA_CONFIG_DIR)) {
        mkdir(BASPANA_CONFIG_DIR, 0775, true);
    }

    file_put_contents(
        BASPANA_CONFIG_FILE,
        json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function baspana_get_site_settings() {
    $config = baspana_load_config();
    return $config['settings'];
}

function baspana_get_hidden_program_ids() {
    $config = baspana_load_config();
    return $config['hidden_programs'];
}

function baspana_get_custom_programs() {
    $config = baspana_load_config();
    return $config['custom_programs'];
}

function baspana_split_lines($value) {
    $lines = preg_split('/\R/u', trim((string)$value));
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $items[] = $line;
        }
    }
    return $items;
}

function baspana_slug($value) {
    $value = strtolower(trim((string)$value));
    $value = preg_replace('/[^a-z0-9_]+/u', '_', $value);
    $value = trim($value, '_');
    return $value !== '' ? $value : 'custom_' . time();
}

function baspana_program_from_post(array $post, ?array $existing = null) {
    $raw_id = trim((string)($post['program_id'] ?? ($existing['programId'] ?? '')));
    $id = baspana_slug($raw_id !== '' ? $raw_id : ($post['title'] ?? ''));
    if (substr($id, 0, 7) !== 'custom_') {
        $id = 'custom_' . $id;
    }

    return [
        'programId' => $id,
        'title' => trim($post['title'] ?? ''),
        'rate' => trim($post['rate'] ?? ''),
        'fee' => trim($post['fee'] ?? ''),
        'term' => trim($post['term'] ?? ''),
        'max_amount' => trim($post['max_amount'] ?? ''),
        'audience' => trim($post['audience'] ?? ''),
        'icon' => trim($post['icon'] ?? 'fa-home') ?: 'fa-home',
        'color' => trim($post['color'] ?? '#E12A2A') ?: '#E12A2A',
        'desc' => trim($post['desc'] ?? ''),
        'features' => baspana_split_lines($post['features'] ?? ''),
        'reqs' => baspana_split_lines($post['reqs'] ?? ''),
        'steps' => baspana_split_lines($post['steps'] ?? ''),
        'match_statuses' => array_values(array_filter(array_map('trim', explode(',', $post['match_statuses'] ?? '')))),
        'match_city' => trim($post['match_city'] ?? 'any'),
        'min_age' => (int)($post['min_age'] ?? 0),
        'max_age' => (int)($post['max_age'] ?? 0),
        'min_savings' => (int)($post['min_savings'] ?? 0),
        'min_income' => (int)($post['min_income'] ?? 0),
        'match_reason' => trim($post['match_reason'] ?? 'Админ қосқан бағдарлама шарттарына сәйкес келеді'),
        'created_at' => $existing['created_at'] ?? date('c'),
        'updated_at' => date('c'),
    ];
}

function baspana_custom_programs_for_js() {
    $programs = [];
    foreach (baspana_get_custom_programs() as $id => $program) {
        $programs[$id] = [
            'programId' => $program['programId'],
            'title' => $program['title'],
            'rate' => $program['rate'],
            'description' => $program['desc'],
            'features' => $program['features'] ?? [],
            'requirements' => $program['reqs'] ?? [],
            'steps' => $program['steps'] ?? [],
            'isCustom' => true,
            'matchStatuses' => $program['match_statuses'] ?? [],
            'matchCity' => $program['match_city'] ?? 'any',
            'minAge' => (int)($program['min_age'] ?? 0),
            'maxAge' => (int)($program['max_age'] ?? 0),
            'minSavings' => (int)($program['min_savings'] ?? 0),
            'minIncome' => (int)($program['min_income'] ?? 0),
            'matchReason' => $program['match_reason'] ?? 'Админ қосқан бағдарлама шарттарына сәйкес келеді',
        ];
    }
    return $programs;
}

function baspana_custom_programs_for_php() {
    $programs = [];
    foreach (baspana_get_custom_programs() as $id => $program) {
        $programs[$id] = [
            'title' => $program['title'],
            'rate' => $program['rate'],
            'fee' => $program['fee'],
            'term' => $program['term'],
            'max_amount' => $program['max_amount'],
            'audience' => $program['audience'],
            'icon' => $program['icon'] ?: 'fa-home',
            'color' => $program['color'] ?: '#E12A2A',
            'desc' => $program['desc'],
            'features' => $program['features'] ?? [],
            'reqs' => $program['reqs'] ?? [],
            'steps' => $program['steps'] ?? [],
        ];
    }
    return $programs;
}

function baspana_load_requests() {
    if (!is_dir(BASPANA_CONFIG_DIR)) {
        mkdir(BASPANA_CONFIG_DIR, 0775, true);
    }

    if (!file_exists(BASPANA_APPLICATIONS_FILE)) {
        baspana_save_requests([]);
        return [];
    }

    $raw = file_get_contents(BASPANA_APPLICATIONS_FILE);
    $requests = json_decode($raw, true);
    return is_array($requests) ? $requests : [];
}

function baspana_save_requests(array $requests) {
    if (!is_dir(BASPANA_CONFIG_DIR)) {
        mkdir(BASPANA_CONFIG_DIR, 0775, true);
    }

    file_put_contents(
        BASPANA_APPLICATIONS_FILE,
        json_encode(array_values($requests), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function baspana_add_request(array $request) {
    $requests = baspana_load_requests();
    $request['id'] = 'req_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
    $request['status'] = 'new';
    $request['created_at'] = date('c');
    $requests[] = $request;
    baspana_save_requests($requests);
    return $request['id'];
}

function baspana_update_request_status($request_id, $status) {
    $allowed = ['new', 'contacted', 'done'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $requests = baspana_load_requests();
    $updated = false;
    foreach ($requests as &$request) {
        if (($request['id'] ?? '') === $request_id) {
            $request['status'] = $status;
            $request['updated_at'] = date('c');
            $updated = true;
            break;
        }
    }
    unset($request);

    if ($updated) {
        baspana_save_requests($requests);
    }
    return $updated;
}

function baspana_delete_request($request_id) {
    $requests = baspana_load_requests();
    $filtered = array_values(array_filter($requests, function ($request) use ($request_id) {
        return ($request['id'] ?? '') !== $request_id;
    }));

    if (count($filtered) === count($requests)) {
        return false;
    }

    baspana_save_requests($filtered);
    return true;
}
?>
