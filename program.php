<?php
session_start();
require 'db.php';
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/program_store.php';
ensure_admin_schema($pdo);

function page_clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$user_data = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
}
$is_admin = current_user_is_admin($pdo);

// 11 БАҒДАРЛАМАНЫҢ КЕҢЕЙТІЛГЕН ТОЛЫҚ БАЗАСЫ
$programs = [
    '7_20_25' => [
        'title' => '«7-20-25» ипотекасы',
        'rate' => '7%',
        'fee' => '20%-дан бастап',
        'term' => '25 жылға дейін',
        'max_amount' => '25 млн ₸ дейін',
        'audience' => 'Баспанасы жоқ ҚР азаматтары',
        'icon' => 'fa-home',
        'color' => '#E12A2A', // Kaspi Red
        'desc' => 'Алғашқы нарықтан (жаңа үйлерден) баспана алуға арналған Ұлттық банктің мемлекеттік бағдарламасы. Бұл бағдарлама арқылы сіз құрылыс компаниясынан тікелей жаңа пәтер ала аласыз.',
        'features' => ['Қазақстан аумағында сіздің атыңызда ешқандай тұрғын үй болмауы тиіс', 'Тек жаңа құрылыс нысандарынан ғана үй алуға болады', 'Басқа ипотекалық қарыздардың болмауы шарт'],
        'reqs' => ['Жеке куәлік', 'Жұмыс орнынан соңғы 6 айдағы табысы туралы анықтама (зейнетақы жарналарымен)', 'Сатып алынатын пәтердің құжаттары'],
        'steps' => ['Құрылыс компаниясынан пайдалануға берілген жаңа үй таңдау', 'Серіктес банктерге (Halyk, BCC, Freedom Bank, т.б.) жүгіну', 'Табысты растап, несиені рәсімдеу']
    ],
    'nauryz' => [
        'title' => '«Наурыз» ипотекасы (Әлеуметтік)',
        'rate' => '7%',
        'fee' => '10-20%',
        'term' => '19 жылға дейін',
        'max_amount' => '36 млн ₸ дейін',
        'audience' => 'Әлеуметтік осал топтар',
        'icon' => 'fa-leaf',
        'color' => '#059669', // Emerald
        'desc' => 'Халықтың әлеуметтік осал топтарына және әкімдік кезегінде тұрғандарға арналған 2026 жылғы жаңа жеңілдетілген бағдарлама.',
        'features' => ['Депозитте 2 млн. теңгеден артық сомма болуы керек', 'Астана мен Алматыда максималды сома 36 млн, өңірлерде 30 млн', 'Baspana Market арқылы онлайн жүзеге асады'],
        'reqs' => ['Кезекте тұрғаныңызды растайтын статус', 'Отбасы банкіндегі депозиттік шот', 'Соңғы 6 айдағы табысты растау'],
        'steps' => ['Шотта 2 миллион теңгенің бар екеніне көз жеткізу', 'Baspana Market порталында өтінім беру', 'Мақұлданғаннан кейін жаңа немесе салынып жатқан үй таңдау']
    ],
    'nauryz_jumysker' => [
        'title' => '«Наурыз жұмыскер»',
        'rate' => '9%',
        'fee' => '20%',
        'term' => '19 жылға дейін',
        'max_amount' => '36 млн ₸ дейін',
        'audience' => 'Барлық санаттағы жұмыскерлер',
        'icon' => 'fa-briefcase',
        'color' => '#2563EB', // Blue
        'desc' => 'Кез келген салада ресми жұмыс істейтін азаматтарға арналған ауқымды бағдарлама. Кезекте тұрудың қажеті жоқ.',
        'features' => ['Соңғы 5 жылда атыңызда үйдің болмауы', 'Екі қосалқы қарыз алушыны тартуға рұқсат', 'Ресми табыстың кемінде 6 ай болуы'],
        'reqs' => ['Отбасы банкіндегі депозит (1-2 млн тг жинағымен)', 'Жеке куәлік', 'Табысты растайтын үзінді көшірме'],
        'steps' => ['Отбасы банкінен депозит ашу', 'Baspana Market арқылы онлайн өтінім беру', 'Ұпай бойынша іріктеуден өту', 'Жаңа ғимараттан пәтер таңдау']
    ],
    'otau' => [
        'title' => '«Отау» бағдарламасы',
        'rate' => '9%',
        'fee' => '20%',
        'term' => '19 жылға дейін',
        'max_amount' => '30 млн ₸ дейін',
        'audience' => 'Жастар мен тұрақты салымшылар',
        'icon' => 'fa-key',
        'color' => '#D97706', // Amber
        'desc' => 'Отбасы банкінің тұрақты салымшыларына және жастарға арналған классикалық бағдарлама. Мұнда депозиттің жасы үлкен рөл атқарады.',
        'features' => ['Депозитіңіздің ашылғанына кемінде 18 ай болуы керек', 'Алматы/Астанада тек жаңа үй, өңірлерде екінші нарықтан (ескі үй) алуға болады', '35 жасқа дейінгі жастарға басымдық беріледі'],
        'reqs' => ['Депозитте жиналған сома 1 млн теңгеден көп болуы тиіс', 'Соңғы 5 жылда баспана болмауы'],
        'steps' => ['Депозит мерзімін тексеру (18 ай)', 'Baspana Market-те өтінім қалдыру', 'Ұпай саны бойынша тізімге ілігу', 'Тұрғын үйді таңдап, бағалату']
    ],
    'zhas_otbasy' => [
        'title' => '«Жас Отбасы»',
        'rate' => '6% (кейін 5%)',
        'fee' => '50%',
        'term' => '9 жылға дейін',
        'max_amount' => '100 млн ₸ дейін',
        'audience' => 'Жас отбасылар',
        'icon' => 'fa-users',
        'color' => '#DB2777', // Pink
        'desc' => 'Жаңадан отау құрған (некеге тұрғанына 5 жыл толмаған) жас отбасыларға арналған өнім. Жас шектеуі жоқ.',
        'features' => ['Мөлшерлеме: бастапқыда 6%, кейін 5%-ға дейін төмендейді', 'Кез келген дайын үйді, жерді сатып алуға немесе үй салуға болады', 'Жарнаны 1 жыл бойы жинауға немесе бірден салуға болады'],
        'reqs' => ['Неке туралы куәлік (5 жыл толмаған)', 'Үй құнының 50%-ы депозитке салынуы тиіс'],
        'steps' => ['Отбасы банкінен ерлі-зайыптының біреуінің атына депозит ашу', 'Үй құнының 50%-ын депозитке салу', 'Таңдаған үйді бағалату', 'Банкке барып несиені ресімдеу']
    ],
    'standard_50_50' => [
        'title' => 'Аралық заем (50/50)',
        'rate' => '5%',
        'fee' => '50%',
        'term' => '25 жылға дейін',
        'max_amount' => '100 млн ₸ дейін',
        'audience' => 'Жинағы бар барлық азаматтар',
        'icon' => 'fa-percent',
        'color' => '#4F46E5', // Indigo
        'desc' => 'Сіздің жинағыңыз жеткілікті болғандықтан, ешқандай мемлекеттік кезексіз стандартты жүйемен кез келген үйді алу тиімді.',
        'features' => ['Қолыңызда үй құнының 50%-ы болса қолданылады', 'Мөлшерлеме 3 жылдан соң 3.5%-ға дейін төмендейді', 'Атыңызда басқа үйдің болуы кедергі емес'],
        'reqs' => ['Пәтер құнының тең жартысы (50%) қолда болуы', 'Табысты растау немесе жанама табысты көрсету'],
        'steps' => ['Таңдаған үйіңізді табу', 'Отбасы банкіне барып депозит ашу және 50% соманы салу', 'Кредиттік өтінім беру']
    ],
    'umay' => [
        'title' => '«Ұмай» әйелдер ипотекасы',
        'rate' => '14.4% (жеңілдетілген)',
        'fee' => '15-20%',
        'term' => '25 жылға дейін',
        'max_amount' => '30 млн ₸ дейін',
        'audience' => 'Қазақстандық әйелдер',
        'icon' => 'fa-female',
        'color' => '#C026D3', // Fuchsia
        'desc' => 'Қазақстандық әйелдерге арналған тиімді баспана бағдарламасы. Табысы орташа әйелдерге бағытталған.',
        'features' => ['Екінші нарықтан (ескі үй) да сатып алуға болады', 'Меншікте басқа жылжымайтын мүліктің болуына шектеулер жоқ', 'Жөндеу жұмыстарына да несие алуға болады (3.7 млн тг дейін)'],
        'reqs' => ['Отбасының жалпы табысы 320 000 теңгеден аспауы керек (немесе шектеусіз, жарнаға байланысты)', 'Әйел азаматша болуы'],
        'steps' => ['Табысты растау', 'Кез келген нарықтан пәтер іздеу', 'Банкке өтінім қалдыру']
    ],
    'oz_uim' => [
        'title' => '«Өз үйім»',
        'rate' => '6-7%',
        'fee' => '20%',
        'term' => '25 жылға дейін',
        'max_amount' => '100 млн ₸ дейін',
        'audience' => 'Жинағы бар азаматтар',
        'icon' => 'fa-building',
        'color' => '#0D9488', // Teal
        'desc' => 'Қолында бастапқы жарнасы бар адамдар үшін үлкен сомада қарыз алуға мүмкіндік беретін Отбасы банкінің өнімі.',
        'features' => ['Пәтер кезегін күтудің қажеті жоқ', 'Кез келген құрылыс компаниясынан жаңа үй алуға болады', 'Тіркеу (прописка) орны маңызды емес'],
        'reqs' => ['Құрылыс компаниясынан салынған жаңа тұрғын үй болуы', 'Тұрақты табысты растау'],
        'steps' => ['Үйдің құнының кемінде 20%-ын жинау', 'Құрылыс компаниясынан үй табу', 'Отбасы банкі арқылы несиені ресімдеу']
    ],
    'green_mortgage' => [
        'title' => '«Жасыл ипотека»',
        'rate' => '12.5% (мүгедектерге 7%)',
        'fee' => '20%',
        'term' => '25 жылға дейін',
        'max_amount' => '35 млн ₸ дейін',
        'audience' => 'Экологиялық үй алғысы келетіндер',
        'icon' => 'fa-seedling',
        'color' => '#16A34A', // Green
        'desc' => '«Жасыл стандартқа» (экологиялық таза, энергия үнемдейтін) сәйкес келетін нысаннан тұрғын үй сатып алуға берілетін қарыз.',
        'features' => ['Тек экологиялық сертификаты бар үйлерге жарамды', 'Мүгедектігі бар адамдар үшін жеңілдетілген мөлшерлеме (7%)', 'Кезексіз беріледі'],
        'reqs' => ['Құрылыс компаниясы берген ӨМІР, BREEAM немесе LEED сертификаттары', 'Бастапқы жарнаның болуы'],
        'steps' => ['Отбасы банкінің сайтынан экологиялық үйлер тізімін алу', 'Құрылыс компаниясынан пәтер броньдау', 'Несиеге өтінім беру']
    ],
    'nauryz_askery' => [
        'title' => '«Наурыз Әскери»',
        'rate' => '9%',
        'fee' => '0%-дан бастап',
        'term' => '19 жылға дейін',
        'max_amount' => '36 млн ₸ дейін',
        'audience' => 'Әскери қызметкерлер',
        'icon' => 'fa-shield-alt',
        'color' => '#475569', // Slate
        'desc' => 'Әскери қызметшілерге арналған жаңа ауқымды бағдарлама. ТҮТ (тұрғын үй төлемдері) арқылы өтеледі.',
        'features' => ['Бастапқы жарнасыз (0%) алуға болады', '20%-50% бастапқы жарна салынса, 80 млн. теңгеге дейін үй алуға болады', 'Тек жаңа үйлерге арналған'],
        'reqs' => ['Әскери немесе арнайы орган қызметкері екенін растау', 'Тұрғын үй төлемдерінің (ТҮТ) түсіп тұруы'],
        'steps' => ['Тұрғын үй төлемдерін растау', 'Baspana Market арқылы жаңа үй таңдау', 'Банктен мақұлдау алу']
    ],
    'askery_baspana' => [
        'title' => '«Әскери баспана»',
        'rate' => '8%',
        'fee' => '15-50%',
        'term' => '25 жылға дейін',
        'max_amount' => '100 млн ₸ дейін',
        'audience' => 'ТҮТ алатын әскерилер',
        'icon' => 'fa-star',
        'color' => '#EAB308', // Yellow
        'desc' => 'Әскери қызметшілерге ескі (қайталама нарықтағы) үйлерді де сатып алуға мүмкіндік беретін дәстүрлі бағдарлама.',
        'features' => ['Кез келген үйді (тіпті ескі үй мен жерді) сатып алуға болады', 'Төлемді мемлекеттен бөлінетін ТҮТ арқылы жабуға болады', 'Конкурстық негізде іріктеледі'],
        'reqs' => ['Әскери шоттың болуы', 'Жеткілікті жинақ немесе ТҮТ қаражаты', 'Қызметтегі мерзімі'],
        'steps' => ['Арнайы Әскери шот ашу', 'ТҮТ аударымдарын бағыттау', 'Банкке барып рәсімдеу']
    ]
];

// URL-дан ID алу (ескі сілтемелердегі alias-тарды да қолдайды)
$program_aliases = [
    'baspana_7_20_25' => '7_20_25',
    'nauryz_social' => 'nauryz'
];

foreach (baspana_get_hidden_program_ids() as $hidden_program_id) {
    unset($programs[$program_aliases[$hidden_program_id] ?? $hidden_program_id]);
}
$programs = array_merge($programs, baspana_custom_programs_for_php());

$requested_program_id = $_GET['id'] ?? '7_20_25';
$program_id = $program_aliases[$requested_program_id] ?? $requested_program_id;
$fallback_program_id = array_key_exists('7_20_25', $programs) ? '7_20_25' : array_key_first($programs);
if ($fallback_program_id === null) {
    die('Қазір сайтта көрсетілетін ипотекалық бағдарлама жоқ.');
}
$program_id = array_key_exists($program_id, $programs) ? $program_id : $fallback_program_id;
$current_program = $programs[$program_id];
$current_program_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $current_program['color'] ?? '') ? $current_program['color'] : '#E12A2A';

if (empty($_SESSION['consultation_csrf_token'])) {
    $_SESSION['consultation_csrf_token'] = bin2hex(random_bytes(32));
}

$consultation_success = $_SESSION['consultation_success'] ?? '';
unset($_SESSION['consultation_success']);
$consultation_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'consultation_request') {
    if (!hash_equals($_SESSION['consultation_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $consultation_error = 'Қауіпсіздік қатесі. Бетті жаңартып, қайта жіберіңіз.';
    } else {
        $request_name = trim($_POST['name'] ?? '');
        $request_phone = trim($_POST['phone'] ?? '');
        $request_email = trim($_POST['email'] ?? '');
        $request_message = trim($_POST['message'] ?? '');

        if ($request_name === '' || $request_phone === '') {
            $consultation_error = 'Атыңыз бен телефон нөміріңізді енгізіңіз.';
        } elseif ($request_email !== '' && !filter_var($request_email, FILTER_VALIDATE_EMAIL)) {
            $consultation_error = 'Email форматы дұрыс емес.';
        } else {
            baspana_add_request([
                'program_id' => $program_id,
                'program_title' => $current_program['title'] ?? $program_id,
                'name' => $request_name,
                'phone' => $request_phone,
                'email' => $request_email,
                'message' => $request_message,
                'user_id' => $_SESSION['user_id'] ?? null,
            ]);
            $_SESSION['consultation_success'] = 'Өтінішіңіз қабылданды. Админ панельге жіберілді.';
            header('Location: program.php?id=' . rawurlencode($requested_program_id) . '#consultation');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo page_clean($current_program['title']); ?> - Baspana Smart</title>
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
            --radius-card: 14px;
            --radius-btn: 12px;
            --prog-color: <?php echo page_clean($current_program_color); ?>;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #F7F7F8; color: var(--text-dark); line-height: 1.6; padding-top: 70px; }

        /* Жоғарғы Навигация */
        header { background: var(--bg-white); padding: 16px 5%; display: flex; justify-content: space-between; align-items: center; position: fixed; width: 100%; top: 0; z-index: 1000; border-bottom: 1px solid var(--border-light); }
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

        /* БАҒДАРЛАМА БЕТІНІҢ СТИЛДЕРІ (ЖАҢАРТЫЛҒАН) */
        .program-container { max-width: 1100px; margin: 40px auto 80px; padding: 0 20px; }
        
        .prog-header { background: var(--bg-white); border-radius: var(--radius-card); padding: 40px; display: flex; align-items: center; gap: 32px; margin-bottom: 32px; border: 1px solid var(--border-light); position: relative; overflow: hidden; }
        .prog-header::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: var(--prog-color); }
        
        .prog-icon { width: 88px; height: 88px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; background: var(--prog-color); flex-shrink: 0; }
        .prog-title { font-size: 2.2rem; font-weight: 800; margin-bottom: 12px; color: var(--text-dark); letter-spacing: -0.5px; }
        .prog-desc { font-size: 1.05rem; color: var(--text-gray); max-width: 700px; line-height: 1.6; }

        /* ЖЫЛДАМ СТАТИСТИКА БЛОГЫ */
        .quick-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--bg-white); padding: 24px; border-radius: var(--radius-card); border: 1px solid var(--border-light); display: flex; flex-direction: column; gap: 8px; }
        .stat-card i { font-size: 1.5rem; color: var(--prog-color); margin-bottom: 8px; }
        .stat-title { font-size: 0.85rem; color: var(--text-gray); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.4rem; font-weight: 800; color: var(--text-dark); }

        .content-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 32px; }
        
        .card { background: var(--bg-white); padding: 32px; border-radius: var(--radius-card); border: 1px solid var(--border-light); margin-bottom: 32px; }
        .card h3 { font-size: 1.4rem; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: var(--text-dark); }
        .card h3 i { color: var(--prog-color); font-size: 1.2rem; }
        
        .list-items { list-style: none; }
        .list-items li { margin-bottom: 16px; display: flex; gap: 16px; font-size: 1.05rem; align-items: flex-start; color: var(--text-dark); }
        .list-items i { color: var(--prog-color); margin-top: 4px; font-size: 1.1rem; }

        /* ТАЙМЛАЙН (ҚАДАМДАР) СТИЛІ */
        .timeline { position: relative; margin-left: 16px; padding-left: 32px; border-left: 2px dashed var(--border-light); list-style: none; }
        .timeline li { position: relative; margin-bottom: 28px; font-size: 1.05rem; color: var(--text-dark); }
        .timeline li:last-child { margin-bottom: 0; }
        .timeline li::before { 
            content: ''; position: absolute; left: -41px; top: 2px; 
            width: 16px; height: 16px; background: var(--prog-color); 
            border-radius: 50%; border: 4px solid var(--bg-white); 
            box-shadow: 0 0 0 2px var(--border-light); 
        }

        .apply-btn { 
            display: flex; justify-content: center; align-items: center; gap: 10px;
            width: 100%; padding: 16px; background: var(--prog-color); color: white; 
            text-decoration: none; border-radius: var(--radius-btn); font-size: 1rem; 
            font-weight: 700; transition: 0.2s; cursor: pointer; border: none; 
            margin-top: 14px;
        }
        .apply-btn:hover { filter: brightness(1.05); transform: translateY(-1px); }

        .call-to-action-box { background: var(--bg-gray); border-radius: var(--radius-btn); padding: 22px; border: 1px solid var(--border-light); }
        .call-to-action-box p { font-size: 0.95rem; color: var(--text-gray); margin-bottom: 16px; }
        .consult-form { display: grid; gap: 12px; margin-top: 16px; }
        .consult-form label { display: block; color: var(--text-gray); font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; text-align: left; }
        .consult-form input, .consult-form textarea { width: 100%; border: 1px solid transparent; border-radius: var(--radius-btn); background: #fff; padding: 13px 14px; color: var(--text-dark); font: inherit; outline: none; }
        .consult-form textarea { min-height: 92px; resize: vertical; }
        .consult-form input:focus, .consult-form textarea:focus { border-color: var(--prog-color); box-shadow: 0 0 0 3px rgba(225,42,42,0.1); }
        .form-note { font-size: 0.82rem; color: var(--text-gray); text-align: left; }
        .form-alert { padding: 12px 14px; border-radius: var(--radius-btn); font-size: 0.92rem; font-weight: 600; margin-bottom: 12px; }
        .form-alert.success { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
        .form-alert.error { background: #FEECEB; color: #C52222; border: 1px solid #FAD2D2; }

        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
            .prog-header { flex-direction: column; text-align: center; gap: 20px; padding: 30px 20px; }
            .prog-header::before { width: 100%; height: 6px; }
            .quick-stats { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 500px) {
            .quick-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- БҮЙІРЛІК МӘЗІР -->
<div class="menu-overlay" id="menuOverlay" onclick="toggleSidebar()"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo"><i class="fas fa-home"></i> Baspana</a>
        <i class="fas fa-times" onclick="toggleSidebar()"></i>
    </div>
    
    <div class="sidebar-menu">
        <a href="index.php#calculator" class="sidebar-item"><i class="fas fa-magic"></i> Smart Талдау</a>
        <a href="index.php#detailed-calc" class="sidebar-item"><i class="fas fa-sliders-h"></i> Толық калькулятор</a>
        
        <div class="sidebar-section-title">Мемлекеттік бағдарламалар</div>
        
        <!-- БАРЛЫҚ БАҒДАРЛАМАЛАРДЫ ШЫҒАРУ (ДИНАМИКАЛЫҚ) -->
        <?php foreach ($programs as $key => $prog): ?>
            <a href="program.php?id=<?php echo rawurlencode($key); ?>" class="sidebar-item <?php echo ($key == $program_id) ? 'active' : ''; ?>">
                <i class="fas <?php echo page_clean($prog['icon'] ?? 'fa-home'); ?>"></i> <?php echo page_clean($prog['title']); ?>
            </a>
        <?php endforeach; ?>
        <?php if($is_admin): ?>
            <div class="sidebar-section-title">Басқару</div>
            <a href="admin.php" class="sidebar-item"><i class="fas fa-user-shield"></i> Админ панель</a>
        <?php endif; ?>
    </div>
</div>

<header>
    <div class="header-left">
        <i class="fas fa-bars hamburger-btn" onclick="toggleSidebar()"></i>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Басты бетке қайту</a>
    </div>
</header>

<div class="program-container">
    
    <div class="prog-header">
        <div class="prog-icon"><i class="fas <?php echo page_clean($current_program['icon'] ?? 'fa-home'); ?>"></i></div>
        <div>
            <h1 class="prog-title"><?php echo page_clean($current_program['title']); ?></h1>
            <p class="prog-desc"><?php echo page_clean($current_program['desc']); ?></p>
        </div>
    </div>

    <!-- ЖЫЛДАМ СТАТИСТИКА (QUICK STATS) -->
    <div class="quick-stats">
        <div class="stat-card">
            <i class="fas fa-percent"></i>
            <span class="stat-title">Мөлшерлеме</span>
            <span class="stat-value"><?php echo page_clean($current_program['rate']); ?></span>
        </div>
        <div class="stat-card">
            <i class="fas fa-wallet"></i>
            <span class="stat-title">Алғашқы жарна</span>
            <span class="stat-value"><?php echo page_clean($current_program['fee']); ?></span>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-alt"></i>
            <span class="stat-title">Несие мерзімі</span>
            <span class="stat-value"><?php echo page_clean($current_program['term']); ?></span>
        </div>
        <div class="stat-card">
            <i class="fas fa-money-bill-wave"></i>
            <span class="stat-title">Максималды сома</span>
            <span class="stat-value"><?php echo page_clean($current_program['max_amount']); ?></span>
        </div>
    </div>

    <div class="content-grid">
        <div class="left-col">
            <div class="card">
                <h3><i class="fas fa-bullseye"></i> Кімдерге арналған?</h3>
                <p style="font-size: 1.1rem; font-weight: 600; color: var(--prog-color); padding: 16px; background: var(--bg-gray); border-radius: 12px; margin-bottom: 20px;">
                    <?php echo page_clean($current_program['audience']); ?>
                </p>
                
                <h3 style="margin-top: 30px;"><i class="fas fa-list-check"></i> Басты артықшылықтары</h3>
                <ul class="list-items">
                    <?php foreach(($current_program['features'] ?? []) as $feature): ?>
                        <li><i class="fas fa-check-circle"></i> <span><?php echo page_clean($feature); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card">
                <h3><i class="fas fa-folder-open"></i> Қажетті құжаттар мен талаптар</h3>
                <ul class="list-items">
                    <?php foreach(($current_program['reqs'] ?? []) as $req): ?>
                        <li><i class="fas fa-file-alt" style="color: #94A3B8;"></i> <span><?php echo page_clean($req); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="right-col">
            <div class="card">
                <h3><i class="fas fa-shoe-prints"></i> Қадамдық нұсқаулық</h3>
                <ul class="timeline">
                    <?php foreach(($current_program['steps'] ?? []) as $step): ?>
                        <li><?php echo page_clean($step); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <div style="margin-top: 40px;" id="consultation">
                    <div class="call-to-action-box">
                        <p>Осы бағдарлама бойынша жеке кеңес алу үшін өтініш қалдырыңыз. Өтініш админ панельге бірден түседі.</p>
                        <?php if($consultation_success): ?>
                            <div class="form-alert success"><i class="fas fa-check-circle"></i> <?php echo page_clean($consultation_success); ?></div>
                        <?php endif; ?>
                        <?php if($consultation_error): ?>
                            <div class="form-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo page_clean($consultation_error); ?></div>
                        <?php endif; ?>
                        <form method="POST" class="consult-form">
                            <input type="hidden" name="action" value="consultation_request">
                            <input type="hidden" name="csrf_token" value="<?php echo page_clean($_SESSION['consultation_csrf_token']); ?>">
                            <div>
                                <label>Атыңыз</label>
                                <input type="text" name="name" value="<?php echo page_clean($_POST['name'] ?? ($user_data['name'] ?? '')); ?>" placeholder="Атыңызды енгізіңіз" required>
                            </div>
                            <div>
                                <label>Телефон</label>
                                <input type="tel" name="phone" value="<?php echo page_clean($_POST['phone'] ?? ''); ?>" placeholder="+7 700 000 00 00" required>
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo page_clean($_POST['email'] ?? ($user_data['email'] ?? '')); ?>" placeholder="mail@example.kz">
                            </div>
                            <div>
                                <label>Қысқаша сұрағыңыз</label>
                                <textarea name="message" placeholder="Мысалы: бастапқы жарнам жеткілікті ме?"><?php echo page_clean($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <button class="apply-btn" type="submit">
                                Кеңес алуға өтініш қалдыру <i class="fas fa-arrow-right"></i>
                            </button>
                            <div class="form-note">Бағдарлама: <?php echo page_clean($current_program['title']); ?></div>
                        </form>
                    </div>
                </div>
            </div>
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
