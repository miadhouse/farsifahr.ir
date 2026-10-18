<?php
// admin/priority-game.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../incloud/functions.php';
require_once __DIR__ . '/../incloud/user-config-handler.php';

if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

check_user_configuration();

$user_id = $_SESSION['user_id'];
$isAdminUser = is_super_admin() || is_admin();

/**
 * ایجاد جدول priority_scenarios و درج مراحل پیش‌فرض در صورت خالی بودن جدول
 */
function ensureAndFetchPriorityScenarios(PDO $pdo): array
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `priority_scenarios` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `question_text` TEXT NOT NULL,
            `question_de` TEXT DEFAULT NULL,
            `explanation` TEXT DEFAULT NULL,
            `intersection_type` VARCHAR(50) NOT NULL DEFAULT 'cross4',
            `scenario_data` LONGTEXT NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $count = (int) $pdo->query("SELECT COUNT(*) FROM priority_scenarios WHERE is_active = 1")->fetchColumn();
    if ($count === 0) {
        $defaults = [
            [
                'title' => 'مرحله ۱: جاده اصلی پیچشی به چپ و عابر پیاده',
                'question_text' => 'به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟ به ترتیب روی آن‌ها کلیک کنید.',
                'question_de' => 'In welcher Reihenfolge dürfen die Verkehrsteilnehmer hier fahren?',
                'explanation' => 'خودرو زرد در جاده اصلی قرار دارد و قصد گردش به چپ را دارد، اما چون عابر پیاده در حال عبور از عرض خیابانِ مسیر گردش اوست، ابتدا عابر پیاده (اولویت ۱)، سپس خودرو زرد در جاده اصلی (اولویت ۲) و در نهایت اتوبوس که تابلوی رعایت حق تقدم دارد (اولویت ۳) عبور می‌کنند.',
                'intersection_type' => 'cross4',
                'scenario_data' => [
                    'intersectionType' => 'cross4',
                    'branches' => [
                        'top'    => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's205_yield', 'secondarySign' => 'none', 'vehicle' => ['type' => 'bus', 'direction' => 'straight', 'priority' => 3], 'pedestrian' => null],
                        'right'  => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => null, 'pedestrian' => null],
                        'bottom' => ['hasCrosswalk' => true,  'trafficLight' => 'none', 'primarySign' => 's306_priority_road', 'secondarySign' => 'abk_bottom_left', 'vehicle' => ['type' => 'car_yellow', 'direction' => 'left', 'priority' => 2], 'pedestrian' => ['type' => 'pedestrian', 'direction' => 'across', 'priority' => 1]],
                        'left'   => ['hasCrosswalk' => true,  'trafficLight' => 'none', 'primarySign' => 's306_priority_road', 'secondarySign' => 'abk_bottom_left', 'vehicle' => null, 'pedestrian' => null]
                    ]
                ]
            ],
            [
                'title' => 'مرحله ۲: سه‌راهی بدون تابلو (قانون راست قبل از چپ)',
                'question_text' => 'در این سه‌راهی هم‌سطح، به چه ترتیبی وسایل نقلیه اجازه حرکت دارند؟',
                'question_de' => 'Wer darf an dieser Kreuzung zuerst fahren (Rechts vor Links)?',
                'explanation' => 'در تقاطع‌های بدون تابلو یا دارای تابلوی ۱۰۲، قانون «راست قبل از چپ» (Rechts vor Links) حاکم است: سمت راستِ دوچرخه خالی است پس اول دوچرخه (۱)، سپس سمت راستِ خودرو سبز خالی می‌شود (۲) و در پایان خودرو قرمز (۳) عبور می‌کند.',
                'intersection_type' => 't_no_top',
                'scenario_data' => [
                    'intersectionType' => 't_no_top',
                    'branches' => [
                        'top'    => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => null, 'pedestrian' => null],
                        'right'  => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's102_rvl', 'secondarySign' => 'none', 'vehicle' => ['type' => 'bike', 'direction' => 'left', 'priority' => 1], 'pedestrian' => null],
                        'bottom' => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's102_rvl', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_green', 'direction' => 'right', 'priority' => 2], 'pedestrian' => null],
                        'left'   => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's102_rvl', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_red', 'direction' => 'straight', 'priority' => 3], 'pedestrian' => null]
                    ]
                ]
            ],
            [
                'title' => 'مرحله ۳: چراغ راهنمایی سبز و گردش به چپ',
                'question_text' => 'چراغ راهنمایی برای ضلع بالا و پایین سبز است. ترتیب صحیح عبور را مشخص کنید.',
                'question_de' => 'Die Ampel zeigt Grün. In welcher Reihenfolge dürfen die Fahrzeuge fahren?',
                'explanation' => 'فرمان چراغ راهنمایی بر تابلوهای حق تقدم مقدم است. خودرو آبی و زرد هر دو چراغ سبز دارند، اما چون خودرو زرد به چپ می‌پیچد باید به خودرو آبی که مستقیم می‌آید راه بدهد: ۱. خودرو آبی، ۲. خودرو زرد، ۳. موتورسیکلت (پشت چراغ قرمز).',
                'intersection_type' => 'cross4',
                'scenario_data' => [
                    'intersectionType' => 'cross4',
                    'branches' => [
                        'top'    => ['hasCrosswalk' => false, 'trafficLight' => 'green', 'primarySign' => 's205_yield', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_blue', 'direction' => 'straight', 'priority' => 1], 'pedestrian' => null],
                        'right'  => ['hasCrosswalk' => false, 'trafficLight' => 'red',   'primarySign' => 's306_priority_road', 'secondarySign' => 'none', 'vehicle' => ['type' => 'moto', 'direction' => 'right', 'priority' => 3], 'pedestrian' => null],
                        'bottom' => ['hasCrosswalk' => false, 'trafficLight' => 'green', 'primarySign' => 's206_stop', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_yellow', 'direction' => 'left', 'priority' => 2], 'pedestrian' => null],
                        'left'   => ['hasCrosswalk' => false, 'trafficLight' => 'red',   'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => null, 'pedestrian' => null]
                    ]
                ]
            ],
            [
                'title' => 'مرحله ۴: چهارراه با تابلو جاده اصلی و تابلو ایست (STOP)',
                'question_text' => 'روی کاربران جاده به ترتیبی که حق تقدم عبور دارند کلیک کنید.',
                'question_de' => 'In welcher Reihenfolge fahren die Fahrzeuge bei Vorfahrtstraße und STOP-Schild?',
                'explanation' => 'خودرو سبز در جاده اصلی (تابلو ۳۰۶) قرار دارد و اول عبور می‌کند (۱). کامیون در ضلع چپ تابلوی رعایت حق تقدم (۲۰۵) دارد اما به راست می‌پیچد (۲) و خودرو قرمز که تابلوی ایست STOP دارد و به چپ می‌پیچد باید به روبرو راه بدهد (۳).',
                'intersection_type' => 'cross4',
                'scenario_data' => [
                    'intersectionType' => 'cross4',
                    'branches' => [
                        'top'    => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's306_priority_road', 'secondarySign' => 'none', 'vehicle' => null, 'pedestrian' => null],
                        'right'  => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's206_stop', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_red', 'direction' => 'left', 'priority' => 3], 'pedestrian' => null],
                        'bottom' => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's306_priority_road', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_green', 'direction' => 'straight', 'priority' => 1], 'pedestrian' => null],
                        'left'   => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's205_yield', 'secondarySign' => 'none', 'vehicle' => ['type' => 'truck', 'direction' => 'right', 'priority' => 2], 'pedestrian' => null]
                    ]
                ]
            ]
        ];

        $stmtIns = $pdo->prepare("
            INSERT INTO priority_scenarios (title, question_text, question_de, explanation, intersection_type, scenario_data, is_active)
            VALUES (:title, :question_text, :question_de, :explanation, :intersection_type, :scenario_data, 1)
        ");
        foreach ($defaults as $d) {
            $stmtIns->execute([
                ':title' => $d['title'],
                ':question_text' => $d['question_text'],
                ':question_de' => $d['question_de'],
                ':explanation' => $d['explanation'],
                ':intersection_type' => $d['intersection_type'],
                ':scenario_data' => json_encode($d['scenario_data'], JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    $stmt = $pdo->query("SELECT id, title, question_text, question_de, explanation, intersection_type, scenario_data FROM priority_scenarios WHERE is_active = 1 ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['scenario_data'] = json_decode($r['scenario_data'], true) ?: [];
    }
    unset($r);
    return $rows;
}

$scenarios = ensureAndFetchPriorityScenarios($pdo);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بازی حق تقدم‌ها | FarsiFahr</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="assets/css/demo.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('farsifahr_theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
        })();
    </script>
    <style>
        /* =========================================================
           2D ARCADE GAME THEME FOR RIGHT-OF-WAY (PRIORITY) GAME
           ========================================================= */
        :root {
            --sky-top: #38bdf8;
            --sky-bottom: #bae6fd;
            --ink-outline: #1e1b4b;
            --board-bg: #fffbeb;
            --board-inner: #ffffff;
            --board-shadow: #d97706;
            --text-main: #0f172a;
            --text-sub: #475569;
        }

        html[data-bs-theme="dark"] {
            --sky-top: #0f172a;
            --sky-bottom: #1e1b4b;
            --ink-outline: #020617;
            --board-bg: #1e293b;
            --board-inner: #0f172a;
            --board-shadow: #020617;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        body {
            background: linear-gradient(180deg, var(--sky-top) 0%, var(--sky-bottom) 100%);
            background-attachment: fixed;
            color: var(--text-main);
            font-family: 'Vazir', 'Tahoma', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding-bottom: 50px;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(rgba(255, 255, 255, 0.28) 2px, transparent 2px),
                linear-gradient(to bottom, transparent 88%, rgba(34, 197, 94, 0.18) 88%, rgba(22, 163, 74, 0.25) 100%);
            background-size: 32px 32px, 100% 100%;
            pointer-events: none;
            z-index: 0;
        }

        .game-world-clouds {
            position: fixed;
            top: 55px;
            left: 0;
            right: 0;
            height: 160px;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            opacity: 0.55;
        }

        html[data-bs-theme="dark"] .game-world-clouds {
            opacity: 0.15;
        }

        .cloud-svg {
            position: absolute;
            fill: #ffffff;
            filter: drop-shadow(0 4px 0 rgba(30, 27, 75, 0.12));
            animation: floatCloud 28s linear infinite;
        }

        .cloud-1 { top: 10px; right: 8%; width: 120px; animation-duration: 34s; }
        .cloud-2 { top: 60px; left: 12%; width: 95px; animation-duration: 26s; animation-direction: reverse; }
        .cloud-3 { top: 25px; left: 45%; width: 140px; animation-duration: 40s; }

        @keyframes floatCloud {
            0% { transform: translateX(0); }
            50% { transform: translateX(35px); }
            100% { transform: translateX(0); }
        }

        /* 2D Top Arcade HUD */
        .arcade-hud {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: linear-gradient(180deg, #0f766e 0%, #115e59 100%);
            border-bottom: 4px solid #042f2e;
            box-shadow: 0 6px 0 rgba(15, 23, 42, 0.28);
            padding: 10px 14px;
            color: #ffffff;
        }

        .game-container {
            position: relative;
            z-index: 2;
            max-width: 780px;
        }

        .btn-2d {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 800;
            border: 3px solid var(--ink-outline);
            border-radius: 12px;
            padding: 6px 14px;
            font-size: 0.82rem;
            text-decoration: none;
            cursor: pointer;
            user-select: none;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .btn-2d:active {
            transform: translateY(3px) !important;
            box-shadow: 0 1px 0 var(--ink-outline) !important;
        }

        .btn-2d-yellow {
            background: linear-gradient(180deg, #fde047 0%, #facc15 100%);
            color: #1e1b4b;
            box-shadow: 0 4px 0 #a16207, inset 0 2px 0 rgba(255, 255, 255, 0.65);
        }

        .btn-2d-yellow:hover {
            color: #1e1b4b;
            filter: brightness(1.04);
        }

        .btn-2d-cyan {
            background: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%);
            color: #ffffff;
            box-shadow: 0 4px 0 #075985, inset 0 2px 0 rgba(255, 255, 255, 0.45);
        }

        .btn-2d-cyan:hover {
            color: #ffffff;
            filter: brightness(1.06);
        }

        .hud-stat-pill {
            background: #0f172a;
            border: 2.5px solid #475569;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6), 0 3px 0 #020617;
            border-radius: 12px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 800;
            color: #f8fafc;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hud-progress-shell {
            height: 14px;
            background: #042f2e;
            border: 2.5px solid #2dd4bf;
            border-radius: 999px;
            padding: 2px;
            margin-top: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.7);
        }

        .hud-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #22c55e 0%, #4ade80 50%, #facc15 100%);
            border-radius: 999px;
            transition: width 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* 2D Quest Board */
        .quest-board {
            background: var(--board-bg);
            border: 4px solid var(--ink-outline);
            border-radius: 22px;
            box-shadow: 0 7px 0 var(--board-shadow), 0 12px 20px rgba(15, 23, 42, 0.15);
            padding: 14px;
            margin-bottom: 14px;
            position: relative;
        }

        .quest-board-inner {
            background: var(--board-inner);
            border: 3px solid var(--ink-outline);
            border-radius: 16px;
            padding: 14px;
        }

        /* 2D Intersection Stage Frame */
        .intersection-frame {
            width: 100%;
            max-width: 550px;
            margin: 0 auto;
            aspect-ratio: 1 / 1;
            border: 4.5px solid var(--ink-outline);
            border-radius: 22px;
            box-shadow: 0 8px 0 var(--ink-outline), 0 16px 32px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            background: #4ade80;
            user-select: none;
            position: relative;
        }

        #priorityGameSvg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .placed-actor-group {
            cursor: pointer;
            transition: filter 0.18s ease;
        }

        .placed-actor-group:hover {
            filter: drop-shadow(0 0 8px rgba(250, 204, 21, 0.95));
        }

        @keyframes blinkTurnSignal {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.15; }
        }

        .turn-signal-blink {
            animation: blinkTurnSignal 0.7s infinite;
        }

        /* Clicked Sequence Bar */
        .sequence-bar {
            background: var(--board-inner);
            border: 3.5px solid var(--ink-outline);
            border-radius: 18px;
            box-shadow: 0 5px 0 var(--ink-outline);
            padding: 12px 14px;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        .step-order-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            border: 2.5px solid #1e1b4b;
            box-shadow: 0 3px 0 #1e1b4b;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        /* Confirm Solve Button */
        .btn-solve-game {
            background: linear-gradient(180deg, #facc15 0%, #eab308 100%);
            color: #1e1b4b;
            border: 4px solid #1e1b4b;
            border-radius: 18px;
            padding: 12px 36px;
            font-size: 1.05rem;
            font-weight: 900;
            box-shadow: 0 7px 0 #854d0e, inset 0 3px 0 rgba(255, 255, 255, 0.7);
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-solve-game:active {
            transform: translateY(5px);
            box-shadow: 0 2px 0 #854d0e;
        }

        /* Evaluation Panel */
        .feedback-panel {
            display: none;
            margin-top: 16px;
            animation: popInPanel 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes popInPanel {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .overall-result-banner {
            border-radius: 18px;
            border: 4px solid var(--ink-outline);
            padding: 14px 18px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 800;
        }

        .overall-result-banner.result-win {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: #052e16;
            box-shadow: 0 6px 0 #14532d;
        }

        .overall-result-banner.result-lose {
            background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
            color: #450a0a;
            box-shadow: 0 6px 0 #7f1d1d;
        }

        .actor-eval-row {
            background: var(--board-inner);
            border: 3px solid var(--ink-outline);
            border-radius: 14px;
            padding: 10px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            box-shadow: 0 4px 0 rgba(15, 23, 42, 0.15);
        }

        .actor-eval-row.eval-ok {
            border-right: 8px solid #16a34a;
        }

        .actor-eval-row.eval-err {
            border-right: 8px solid #dc2626;
        }

        .btn-repeat-q {
            background: linear-gradient(180deg, #fde047 0%, #f59e0b 100%);
            color: #1e1b4b;
            border: 3.5px solid #1e1b4b;
            box-shadow: 0 5px 0 #92400e;
            border-radius: 14px;
            padding: 10px 24px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-next-q {
            background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
            color: #052e16;
            border: 3.5px solid #1e1b4b;
            box-shadow: 0 5px 0 #14532d;
            border-radius: 14px;
            padding: 10px 28px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

    <!-- 2D Clouds Background -->
    <div class="game-world-clouds" aria-hidden="true">
        <svg class="cloud-svg cloud-1" viewBox="0 0 120 60"><path d="M25,50 h70 a18,18 0 0,0 0,-36 a24,24 0 0,0 -44,-8 a20,20 0 0,0 -26,44 z"/></svg>
        <svg class="cloud-svg cloud-2" viewBox="0 0 120 60"><path d="M25,50 h70 a18,18 0 0,0 0,-36 a24,24 0 0,0 -44,-8 a20,20 0 0,0 -26,44 z"/></svg>
        <svg class="cloud-svg cloud-3" viewBox="0 0 120 60"><path d="M25,50 h70 a18,18 0 0,0 0,-36 a24,24 0 0,0 -44,-8 a20,20 0 0,0 -26,44 z"/></svg>
    </div>

    <!-- Top 2D Arcade HUD -->
    <header class="arcade-hud">
        <div class="container game-container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="index.php" class="btn-2d btn-2d-yellow">
                        <i class="bx bx-home-alt"></i>
                        <span>داشبورد</span>
                    </a>
                    <a href="mistakes-game.php" class="btn-2d btn-2d-cyan">
                        <i class="fas fa-gamepad"></i>
                        <span>بازی سوالات</span>
                    </a>
                    <?php if ($isAdminUser): ?>
                    <a href="priority-game-builder.php" class="btn-2d btn-2d-cyan" style="background: linear-gradient(180deg, #a855f7 0%, #7e22ce 100%); box-shadow: 0 4px 0 #4c1d95;">
                        <i class="fas fa-drafting-compass"></i>
                        <span>طراحی سوال اولویت‌ها (ادمین)</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($scenarios)): ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="hud-stat-pill">
                        <i class="fas fa-star text-warning"></i>
                        <span>امتیاز: <strong id="hudScoreVal">0</strong></span>
                    </div>
                    <div class="hud-stat-pill">
                        <i class="fas fa-traffic-light text-info"></i>
                        <span>مرحله <strong id="currentStageLabel">1</strong> / <strong><?= count($scenarios) ?></strong></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($scenarios)): ?>
            <div class="hud-progress-shell">
                <div class="hud-progress-bar" id="stageProgressBar" style="width: <?= round(100 / count($scenarios), 1) ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container game-container mt-3">
        <?php if (empty($scenarios)): ?>
            <div class="quest-board text-center py-5 mt-3">
                <div class="quest-board-inner py-4">
                    <h4 class="fw-black mb-2">هنوز مرحله‌ای ثبت نشده است</h4>
                    <p class="text-muted mb-3">به زودی مراحل جدید بازی حق تقدم‌ها اضافه خواهد شد.</p>
                    <a href="index.php" class="btn-2d btn-2d-yellow px-4 py-2">بازگشت به داشبورد</a>
                </div>
            </div>
        <?php else: ?>

            <div id="activePriorityGameArea">
                <!-- 1. Question Header Board -->
                <div class="quest-board">
                    <div class="quest-board-inner">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                            <span class="badge bg-primary fs-6 fw-bold" id="stageTitleBadge"></span>
                            <span class="badge bg-warning text-dark border border-dark fw-bold">
                                <i class="fas fa-hand-pointer me-1"></i> به ترتیب حق تقدم روی کاربران جاده کلیک کنید!
                            </span>
                        </div>
                        <div class="fw-black mb-1" style="font-weight: 900; font-size: 1rem;" id="stageQuestionFa"></div>
                        <div class="text-muted small" dir="ltr" id="stageQuestionDe" style="display: none; text-align: left; font-weight: 700;"></div>
                    </div>
                </div>

                <!-- 2. 2D Interactive Top-Down Intersection Canvas -->
                <div class="intersection-frame">
                    <svg id="priorityGameSvg" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg"></svg>
                </div>

                <!-- 3. User Clicked Sequence Bar & Reset Action -->
                <div class="sequence-bar">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <span class="fw-black small" style="font-weight: 900;">
                            <i class="fas fa-route text-primary me-1"></i>
                            ترتیب حرکت انتخاب‌شده توسط شما:
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold border-2 border-dark" onclick="resetCurrentStageMoves()">
                            <i class="fas fa-undo me-1"></i> بازنشانی حرکت
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap" id="userSequencePills"></div>
                </div>

                <!-- 4. Confirm / Check Answer Button ("نمایش پاسخ") -->
                <div class="text-center my-3" id="solveButtonWrapper">
                    <button type="button" class="btn-solve-game" onclick="checkUserPriorityAnswer()">
                        <i class="fas fa-check-double"></i>
                        <span>نمایش پاسخ و بررسی اولویت‌ها</span>
                    </button>
                </div>

                <!-- 5. Evaluation & Educational Explanation Panel -->
                <div class="feedback-panel" id="priorityFeedbackPanel">
                    <div class="overall-result-banner" id="priorityResultBanner"></div>

                    <div class="quest-board">
                        <div class="quest-board-inner">
                            <h6 class="fw-black mb-2" style="font-weight: 900;">
                                <i class="fas fa-list-ol text-primary me-1"></i> بررسی ترتیب اولویت‌های شما:
                            </h6>
                            <div id="priorityActorsBreakdown"></div>

                            <div class="p-3 rounded-4 mt-3" id="priorityExplanationBox" style="background: rgba(37, 99, 235, 0.1); border-right: 4px solid #2563eb;">
                                <div class="fw-bold text-primary small mb-1">
                                    <i class="fas fa-lightbulb me-1"></i> قانون حق تقدم و توضیح آموزشی:
                                </div>
                                <div id="priorityExplanationText" style="font-size: 0.92rem; line-height: 1.7;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 flex-wrap mt-3 mb-4">
                        <button type="button" class="btn-repeat-q" onclick="repeatCurrentStage()">
                            <i class="fas fa-redo"></i>
                            <span>تکرار این تقاطع</span>
                        </button>
                        <button type="button" class="btn-next-q" onclick="goToNextStage()">
                            <span id="nextStageBtnText">تقاطع بعدی</span>
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Final Summary Screen -->
            <div class="quest-board text-center py-5 mt-3" id="prioritySummaryScreen" style="display: none;">
                <div class="quest-board-inner py-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 86px; height: 86px; background: #fde047; border: 4px solid #1e1b4b; box-shadow: 0 5px 0 #1e1b4b; color: #1e1b4b; font-size: 2.5rem;">
                            <i class="fas fa-trophy"></i>
                        </span>
                    </div>
                    <h4 style="font-weight: 900;" class="mb-2">پایان مراحل بازی حق تقدم‌ها!</h4>
                    <p class="fw-bold mb-4">
                        شما <strong id="sumWinCount" class="text-success">0</strong> تقاطع را کاملاً صحیح و
                        <strong id="sumLoseCount" class="text-danger">0</strong> تقاطع را با خطا پشت سر گذاشتید.
                    </p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn-2d btn-2d-yellow px-4 py-2" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i>
                            <span>شروع مجدد بازی</span>
                        </button>
                        <a href="index.php" class="btn-2d btn-2d-cyan px-4 py-2">
                            <i class="bx bx-home-alt"></i>
                            <span>بازگشت به داشبورد</span>
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($scenarios)): ?>
    <script>
        const SCENARIOS = <?= json_encode($scenarios, JSON_UNESCAPED_UNICODE) ?>;

        const BRANCH_LABELS = {
            top: 'ضلع بالا',
            right: 'ضلع راست',
            bottom: 'ضلع پایین',
            left: 'ضلع چپ'
        };

        const INTERSECTION_BRANCHES = {
            cross4: ['top', 'right', 'bottom', 'left'],
            t_no_top: ['right', 'bottom', 'left'],
            t_no_bottom: ['top', 'right', 'left'],
            t_no_left: ['top', 'right', 'bottom'],
            t_no_right: ['top', 'bottom', 'left']
        };

        const VEHICLE_CATALOG = {
            car_yellow: { name: 'خودرو زرد', color: '#facc15', roof: '#fef08a' },
            car_green:  { name: 'خودرو سبز', color: '#22c55e', roof: '#86efac' },
            car_blue:   { name: 'خودرو آبی', color: '#3b82f6', roof: '#93c5fd' },
            car_red:    { name: 'خودرو قرمز', color: '#ef4444', roof: '#fca5a5' },
            bus:        { name: 'اتوبوس', color: '#f97316', roof: '#fdba74' },
            truck:      { name: 'کامیون', color: '#64748b', roof: '#cbd5e1' },
            moto:       { name: 'موتورسیکلت', color: '#ec4899', roof: '#f9a8d4' },
            bike:       { name: 'دوچرخه', color: '#06b6d4', roof: '#67e8f9' },
            scooter:    { name: 'اسکوتر برقی', color: '#a855f7', roof: '#d8b4fe' },
            police:     { name: 'خودرو پلیس/امداد', color: '#1e40af', roof: '#ffffff' }
        };

        const PEDESTRIAN_CATALOG = {
            pedestrian: { name: 'عابر پیاده', color: '#10b981' },
            ped_child:  { name: 'کودک در پیاده‌رو', color: '#f43f5e' },
            ped_bike:   { name: 'دوچرخه در گذرگاه', color: '#0ea5e9' },
            ped_scooter:{ name: 'اسکوتر در پیاده‌رو', color: '#8b5cf6' }
        };

        const BRANCH_GEOMETRY = {
            bottom: { vehicleStart: { x: 337, y: 495, angle: 0 },   pedStart: { x: 392, y: 412, angle: -90 }, pedEnd: { x: 208, y: 412 }, signSlot: { x: 428, y: 458 } },
            top:    { vehicleStart: { x: 263, y: 105, angle: 180 }, pedStart: { x: 208, y: 188, angle: 90 },  pedEnd: { x: 392, y: 188 }, signSlot: { x: 172, y: 142 } },
            right:  { vehicleStart: { x: 495, y: 263, angle: -90 }, pedStart: { x: 412, y: 208, angle: 180 }, pedEnd: { x: 412, y: 392 }, signSlot: { x: 458, y: 172 } },
            left:   { vehicleStart: { x: 105, y: 337, angle: 90 },  pedStart: { x: 188, y: 392, angle: 0 },   pedEnd: { x: 188, y: 208 }, signSlot: { x: 142, y: 428 } }
        };

        let currentStageIdx = 0;
        let currentScenarioState = null;
        let userClickedKeys = [];
        let stageEvaluated = null;
        let animatedPositions = {};
        let runningAnimFrames = {};
        let totalScore = 0;
        let winStagesCount = 0;
        let loseStagesCount = 0;

        function renderTopDownVehicleSVG(typeId) {
            const item = VEHICLE_CATALOG[typeId] || VEHICLE_CATALOG.car_yellow;
            if (typeId === 'bus') {
                return `
                    <g>
                        <rect x="-18" y="-36" width="36" height="72" rx="7" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-14" y="-31" width="28" height="11" rx="3" fill="#bae6fd" stroke="#0f172a" stroke-width="2"/>
                        <rect x="-13" y="-15" width="26" height="38" rx="4" fill="${item.roof}" stroke="#0f172a" stroke-width="1.5"/>
                        <circle cx="-11" cy="-35" r="3" fill="#fef08a"/>
                        <circle cx="11" cy="-35" r="3" fill="#fef08a"/>
                    </g>
                `;
            }
            if (typeId === 'truck') {
                return `
                    <g>
                        <rect x="-18" y="-12" width="36" height="46" rx="4" fill="#e2e8f0" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-16" y="-34" width="32" height="22" rx="5" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-13" y="-30" width="26" height="8" rx="2" fill="#bae6fd" stroke="#0f172a" stroke-width="1.8"/>
                    </g>
                `;
            }
            if (typeId === 'moto') {
                return `
                    <g>
                        <rect x="-4" y="-22" width="8" height="44" rx="4" fill="#1e293b"/>
                        <line x1="-13" y1="-12" x2="13" y2="-12" stroke="#0f172a" stroke-width="3.5" stroke-linecap="round"/>
                        <rect x="-7" y="-10" width="14" height="22" rx="6" fill="${item.color}" stroke="#0f172a" stroke-width="2.5"/>
                        <circle cx="0" cy="0" r="7" fill="#fde047" stroke="#0f172a" stroke-width="2.5"/>
                    </g>
                `;
            }
            if (typeId === 'bike' || typeId === 'scooter') {
                return `
                    <g>
                        <rect x="-3" y="-19" width="6" height="38" rx="3" fill="${item.color}" stroke="#0f172a" stroke-width="2"/>
                        <line x1="-11" y1="-10" x2="11" y2="-10" stroke="#0f172a" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="0" cy="0" r="6.5" fill="${item.roof}" stroke="#0f172a" stroke-width="2.5"/>
                    </g>
                `;
            }
            if (typeId === 'police') {
                return `
                    <g>
                        <rect x="-16" y="-28" width="32" height="56" rx="9" fill="#1e40af" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-12" y="-20" width="24" height="10" rx="3" fill="#bae6fd" stroke="#0f172a" stroke-width="2"/>
                        <rect x="-12" y="-9" width="24" height="22" rx="4" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                        <rect x="-9" y="-2" width="8" height="5" rx="2" fill="#ef4444"/>
                        <rect x="1" y="-2" width="8" height="5" rx="2" fill="#3b82f6"/>
                    </g>
                `;
            }
            return `
                <g>
                    <rect x="-18" y="-21" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="14" y="-21" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="-18" y="10" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="14" y="10" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="-15" y="-27" width="30" height="54" rx="10" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                    <path d="M-11 -17 L11 -17 L9 -7 L-9 -7 Z" fill="#e0f2fe" stroke="#0f172a" stroke-width="2"/>
                    <rect x="-10" y="-7" width="20" height="18" rx="4" fill="${item.roof}" stroke="#0f172a" stroke-width="2"/>
                    <path d="M-9 11 L9 11 L11 18 L-11 18 Z" fill="#bae6fd" stroke="#0f172a" stroke-width="1.8"/>
                    <circle cx="-9" cy="-25" r="2.8" fill="#fef9c3"/>
                    <circle cx="9" cy="-25" r="2.8" fill="#fef9c3"/>
                </g>
            `;
        }

        function renderTopDownPedestrianSVG(typeId) {
            const item = PEDESTRIAN_CATALOG[typeId] || PEDESTRIAN_CATALOG.pedestrian;
            if (typeId === 'ped_bike' || typeId === 'ped_scooter') {
                return `
                    <g>
                        <rect x="-3" y="-15" width="6" height="30" rx="3" fill="${item.color}" stroke="#0f172a" stroke-width="2"/>
                        <circle cx="0" cy="0" r="7" fill="#fde047" stroke="#0f172a" stroke-width="2.5"/>
                    </g>
                `;
            }
            return `
                <g>
                    <rect x="-11" y="-6" width="22" height="12" rx="6" fill="${item.color}" stroke="#0f172a" stroke-width="2.5"/>
                    <circle cx="0" cy="0" r="8" fill="#fde047" stroke="#0f172a" stroke-width="2.5"/>
                </g>
            `;
        }

        function renderSignIconSVG(signId) {
            switch (signId) {
                case 's306_priority_road':
                    return `<g><rect x="-13" y="-13" width="26" height="26" rx="2" transform="rotate(45)" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><rect x="-8.5" y="-8.5" width="17" height="17" rx="1" transform="rotate(45)" fill="#facc15" stroke="#0f172a" stroke-width="1.5"/></g>`;
                case 's307_end_priority':
                    return `<g><rect x="-13" y="-13" width="26" height="26" rx="2" transform="rotate(45)" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><rect x="-8.5" y="-8.5" width="17" height="17" rx="1" transform="rotate(45)" fill="#facc15" stroke="#0f172a" stroke-width="1.5"/><line x1="-11" y1="11" x2="11" y2="-11" stroke="#0f172a" stroke-width="3.5"/></g>`;
                case 's205_yield':
                    return `<g><polygon points="-16,-12 16,-12 0,15" fill="#ffffff" stroke="#dc2626" stroke-width="4" stroke-linejoin="round"/></g>`;
                case 's206_stop':
                    return `<g><polygon points="-7,-16 7,-16 16,-7 16,7 7,16 -7,16 -16,7 -16,-7" fill="#dc2626" stroke="#ffffff" stroke-width="2.5"/><text x="0" y="4" text-anchor="middle" fill="#ffffff" font-size="8.5" font-weight="900" font-family="sans-serif">STOP</text></g>`;
                case 's301_priority_next':
                case 's102_rvl':
                    return `<g><polygon points="0,-15 16,13 -16,13" fill="#ffffff" stroke="#dc2626" stroke-width="4" stroke-linejoin="round"/>${signId === 's102_rvl' ? `<path d="M-5,2 L5,10 M5,2 L-5,10" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round"/>` : `<path d="M0,10 L0,-3 M-4,1 L0,-4 L4,1" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" fill="none"/>`}</g>`;
                case 's350_crosswalk':
                    return `<g><rect x="-14" y="-14" width="28" height="28" rx="4" fill="#2563eb" stroke="#ffffff" stroke-width="2"/><polygon points="0,-10 11,9 -11,9" fill="#ffffff"/></g>`;
                case 'abk_bottom_left':
                    return `<g><rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><path d="M0,9 L0,0 L-10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/><line x1="0" y1="-9" x2="0" y2="-2" stroke="#64748b" stroke-width="2"/><line x1="2" y1="0" x2="10" y2="0" stroke="#64748b" stroke-width="2"/></g>`;
                case 'abk_bottom_right':
                    return `<g><rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><path d="M0,9 L0,0 L10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/><line x1="0" y1="-9" x2="0" y2="-2" stroke="#64748b" stroke-width="2"/><line x1="-10" y1="0" x2="-2" y2="0" stroke="#64748b" stroke-width="2"/></g>`;
                case 'abk_top_left':
                    return `<g><rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><path d="M0,-9 L0,0 L-10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/><line x1="0" y1="2" x2="0" y2="9" stroke="#64748b" stroke-width="2"/><line x1="2" y1="0" x2="10" y2="0" stroke="#64748b" stroke-width="2"/></g>`;
                case 'abk_top_right':
                    return `<g><rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><path d="M0,-9 L0,0 L10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/><line x1="0" y1="2" x2="0" y2="9" stroke="#64748b" stroke-width="2"/><line x1="-10" y1="0" x2="-2" y2="0" stroke="#64748b" stroke-width="2"/></g>`;
                case 'rad_cross':
                    return `<g><rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/><path d="M-9,-5 L9,-5 M-9,5 L9,5" stroke="#0f172a" stroke-width="2.2" stroke-linecap="round"/></g>`;
                default:
                    return '';
            }
        }

        function renderTrafficLightSVG(lightState) {
            if (!lightState || lightState === 'none') return '';
            const isRed = lightState === 'red';
            const isYellow = lightState === 'yellow';
            const isGreen = lightState === 'green';
            return `
                <g>
                    <rect x="-10" y="-22" width="20" height="44" rx="6" fill="#1e293b" stroke="#020617" stroke-width="2.5"/>
                    <circle cx="0" cy="-13" r="5" fill="${isRed ? '#ef4444' : '#450a0a'}" stroke="#020617" stroke-width="1.2"/>
                    <circle cx="0" cy="0" r="5" fill="${isYellow ? '#facc15' : '#422006'}" stroke="#020617" stroke-width="1.2"/>
                    <circle cx="0" cy="13" r="5" fill="${isGreen ? '#22c55e' : '#052e16'}" stroke="#020617" stroke-width="1.2"/>
                </g>
            `;
        }

        function getStageActors() {
            if (!currentScenarioState || !currentScenarioState.branches) return [];
            const activeBranches = INTERSECTION_BRANCHES[currentScenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            const list = [];

            activeBranches.forEach(branch => {
                const b = currentScenarioState.branches[branch];
                if (!b) return;
                if (b.vehicle && b.vehicle.type) {
                    const meta = VEHICLE_CATALOG[b.vehicle.type] || VEHICLE_CATALOG.car_yellow;
                    list.push({
                        key: `vehicle_${branch}`,
                        branch: branch,
                        slotType: 'vehicle',
                        type: b.vehicle.type,
                        name: `${meta.name} (${BRANCH_LABELS[branch]})`,
                        direction: b.vehicle.direction || 'straight',
                        priority: parseInt(b.vehicle.priority || 1, 10)
                    });
                }
                if (b.pedestrian && b.pedestrian.type) {
                    const meta = PEDESTRIAN_CATALOG[b.pedestrian.type] || PEDESTRIAN_CATALOG.pedestrian;
                    list.push({
                        key: `pedestrian_${branch}`,
                        branch: branch,
                        slotType: 'pedestrian',
                        type: b.pedestrian.type,
                        name: `${meta.name} (${BRANCH_LABELS[branch]})`,
                        direction: 'across',
                        priority: parseInt(b.pedestrian.priority || 1, 10)
                    });
                }
            });
            return list;
        }

        function getTrajectoryPathD(actor) {
            const b = actor.branch;
            if (actor.slotType === 'pedestrian') {
                const g = BRANCH_GEOMETRY[b];
                return `M ${g.pedStart.x} ${g.pedStart.y} L ${g.pedEnd.x} ${g.pedEnd.y}`;
            }
            const start = BRANCH_GEOMETRY[b].vehicleStart;
            const dir = actor.direction || 'straight';

            if (b === 'bottom') {
                if (dir === 'straight') return `M ${start.x} ${start.y} L 337 65`;
                if (dir === 'right')    return `M ${start.x} ${start.y} L 337 365 Q 337 337 365 337 L 535 337`;
                if (dir === 'left')     return `M ${start.x} ${start.y} L 337 315 Q 337 263 285 263 L 65 263`;
            }
            if (b === 'top') {
                if (dir === 'straight') return `M ${start.x} ${start.y} L 263 535`;
                if (dir === 'right')    return `M ${start.x} ${start.y} L 263 235 Q 263 263 235 263 L 65 263`;
                if (dir === 'left')     return `M ${start.x} ${start.y} L 263 285 Q 263 337 315 337 L 535 337`;
            }
            if (b === 'right') {
                if (dir === 'straight') return `M ${start.x} ${start.y} L 65 263`;
                if (dir === 'right')    return `M ${start.x} ${start.y} L 365 263 Q 337 263 337 235 L 337 65`;
                if (dir === 'left')     return `M ${start.x} ${start.y} L 315 263 Q 263 263 263 315 L 263 535`;
            }
            if (b === 'left') {
                if (dir === 'straight') return `M ${start.x} ${start.y} L 535 337`;
                if (dir === 'right')    return `M ${start.x} ${start.y} L 235 337 Q 263 337 263 365 L 263 535`;
                if (dir === 'left')     return `M ${start.x} ${start.y} L 285 337 Q 337 337 337 285 L 337 65`;
            }
            return `M ${start.x} ${start.y} L 300 300`;
        }

        function loadStage(index) {
            const sc = SCENARIOS[index];
            if (!sc) return;

            Object.keys(runningAnimFrames).forEach(k => cancelAnimationFrame(runningAnimFrames[k]));
            runningAnimFrames = {};
            animatedPositions = {};
            userClickedKeys = [];
            stageEvaluated = null;

            currentScenarioState = sc.scenario_data || { intersectionType: sc.intersection_type || 'cross4', branches: {} };

            document.getElementById('currentStageLabel').textContent = (index + 1);
            const pct = Math.round(((index + 1) / SCENARIOS.length) * 100);
            document.getElementById('stageProgressBar').style.width = pct + '%';

            document.getElementById('stageTitleBadge').textContent = sc.title || `مرحله ${index + 1}`;
            document.getElementById('stageQuestionFa').textContent = sc.question_text || '';
            const deEl = document.getElementById('stageQuestionDe');
            if (sc.question_de && sc.question_de.trim()) {
                deEl.textContent = sc.question_de;
                deEl.style.display = 'block';
            } else {
                deEl.style.display = 'none';
            }

            document.getElementById('priorityFeedbackPanel').style.display = 'none';
            document.getElementById('solveButtonWrapper').style.display = 'block';

            renderUserSequencePills();
            renderStageSVG();
        }

        function renderStageSVG() {
            const svg = document.getElementById('priorityGameSvg');
            const activeBranches = INTERSECTION_BRANCHES[currentScenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            const actors = getStageActors();

            let html = `
                <defs>
                    <marker id="arrow-normal" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="5.5" markerHeight="5.5" orient="auto-start-reverse">
                        <path d="M 0 1 L 10 5 L 0 9 z" fill="#fde047" stroke="#0f172a" stroke-width="1"/>
                    </marker>
                    <marker id="arrow-green" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 1 L 10 5 L 0 9 z" fill="#22c55e" stroke="#052e16" stroke-width="1"/>
                    </marker>
                    <marker id="arrow-red" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 1 L 10 5 L 0 9 z" fill="#ef4444" stroke="#450a0a" stroke-width="1"/>
                    </marker>
                </defs>
                <rect x="0" y="0" width="600" height="600" fill="#4ade80"/>
                <g opacity="0.85">
                    <circle cx="65" cy="65" r="28" fill="#16a34a" stroke="#14532d" stroke-width="3"/><circle cx="58" cy="58" r="18" fill="#22c55e"/>
                    <circle cx="535" cy="65" r="28" fill="#16a34a" stroke="#14532d" stroke-width="3"/><circle cx="528" cy="58" r="18" fill="#22c55e"/>
                    <circle cx="65" cy="535" r="28" fill="#16a34a" stroke="#14532d" stroke-width="3"/><circle cx="58" cy="528" r="18" fill="#22c55e"/>
                    <circle cx="535" cy="535" r="28" fill="#16a34a" stroke="#14532d" stroke-width="3"/><circle cx="528" cy="528" r="18" fill="#22c55e"/>
                </g>
            `;

            // Sidewalks & Roads
            let sidewalkHTML = `<rect x="205" y="205" width="190" height="190" rx="18" fill="#cbd5e1" stroke="#475569" stroke-width="3"/>`;
            let asphaltHTML = `<rect x="225" y="225" width="150" height="150" fill="#334155"/>`;
            let markingsHTML = '';

            if (activeBranches.includes('top')) {
                sidewalkHTML += `<rect x="205" y="0" width="190" height="225" fill="#cbd5e1" stroke="#475569" stroke-width="3"/>`;
                asphaltHTML  += `<rect x="225" y="0" width="150" height="230" fill="#334155"/>`;
                markingsHTML += `<line x1="300" y1="10" x2="300" y2="170" stroke="#f8fafc" stroke-width="4" stroke-dasharray="18 14"/>`;
            }
            if (activeBranches.includes('bottom')) {
                sidewalkHTML += `<rect x="205" y="375" width="190" height="225" fill="#cbd5e1" stroke="#475569" stroke-width="3"/>`;
                asphaltHTML  += `<rect x="225" y="370" width="150" height="230" fill="#334155"/>`;
                markingsHTML += `<line x1="300" y1="430" x2="300" y2="590" stroke="#f8fafc" stroke-width="4" stroke-dasharray="18 14"/>`;
            }
            if (activeBranches.includes('right')) {
                sidewalkHTML += `<rect x="375" y="205" width="225" height="190" fill="#cbd5e1" stroke="#475569" stroke-width="3"/>`;
                asphaltHTML  += `<rect x="370" y="225" width="230" height="150" fill="#334155"/>`;
                markingsHTML += `<line x1="430" y1="300" x2="590" y2="300" stroke="#f8fafc" stroke-width="4" stroke-dasharray="18 14"/>`;
            }
            if (activeBranches.includes('left')) {
                sidewalkHTML += `<rect x="0" y="205" width="225" height="190" fill="#cbd5e1" stroke="#475569" stroke-width="3"/>`;
                asphaltHTML  += `<rect x="0" y="225" width="230" height="150" fill="#334155"/>`;
                markingsHTML += `<line x1="10" y1="300" x2="170" y2="300" stroke="#f8fafc" stroke-width="4" stroke-dasharray="18 14"/>`;
            }

            html += sidewalkHTML + asphaltHTML + markingsHTML;

            // Crosswalks & Road Stop/Yield markings
            activeBranches.forEach(branch => {
                const b = currentScenarioState.branches ? currentScenarioState.branches[branch] : null;
                if (!b) return;
                if (b.hasCrosswalk) {
                    if (branch === 'bottom') {
                        for (let x = 232; x <= 362; x += 18) html += `<rect x="${x}" y="395" width="10" height="32" rx="2" fill="#f8fafc" opacity="0.92"/>`;
                    } else if (branch === 'top') {
                        for (let x = 232; x <= 362; x += 18) html += `<rect x="${x}" y="173" width="10" height="32" rx="2" fill="#f8fafc" opacity="0.92"/>`;
                    } else if (branch === 'right') {
                        for (let y = 232; y <= 362; y += 18) html += `<rect x="395" y="${y}" width="32" height="10" rx="2" fill="#f8fafc" opacity="0.92"/>`;
                    } else if (branch === 'left') {
                        for (let y = 232; y <= 362; y += 18) html += `<rect x="173" y="${y}" width="32" height="10" rx="2" fill="#f8fafc" opacity="0.92"/>`;
                    }
                }
                if (b.primarySign === 's206_stop' || b.primarySign === 's205_yield') {
                    const dash = b.primarySign === 's206_stop' ? '' : 'stroke-dasharray="8 6"';
                    if (branch === 'bottom') html += `<line x1="302" y1="435" x2="373" y2="435" stroke="#ffffff" stroke-width="6" ${dash}/>`;
                    if (branch === 'top')    html += `<line x1="227" y1="165" x2="298" y2="165" stroke="#ffffff" stroke-width="6" ${dash}/>`;
                    if (branch === 'right')  html += `<line x1="435" y1="227" x2="435" y2="298" stroke="#ffffff" stroke-width="6" ${dash}/>`;
                    if (branch === 'left')   html += `<line x1="165" y1="302" x2="165" y2="373" stroke="#ffffff" stroke-width="6" ${dash}/>`;
                }
            });

            // Trajectory Paths (Yellow before solve; Green/Red after solve)
            actors.forEach(actor => {
                const d = getTrajectoryPathD(actor);
                let strokeColor = '#fde047';
                let markerId = 'arrow-normal';
                let strokeWidth = 4.5;

                if (stageEvaluated && stageEvaluated.detailsByKey) {
                    const ok = stageEvaluated.detailsByKey[actor.key];
                    strokeColor = ok ? '#22c55e' : '#ef4444';
                    markerId = ok ? 'arrow-green' : 'arrow-red';
                    strokeWidth = 6.5;
                }

                html += `
                    <path id="game_traj_${actor.key}" d="${d}" fill="none" stroke="#0f172a" stroke-width="${strokeWidth + 3}" stroke-linecap="round" opacity="0.45"/>
                    <path d="${d}" fill="none" stroke="${strokeColor}" stroke-width="${strokeWidth}" stroke-dasharray="10 7" stroke-linecap="round" marker-end="url(#${markerId})"/>
                `;
            });

            // Signs & Traffic Lights
            activeBranches.forEach(branch => {
                const b = currentScenarioState.branches ? currentScenarioState.branches[branch] : null;
                if (!b) return;
                const pos = BRANCH_GEOMETRY[branch].signSlot;
                const hasPrimary = b.primarySign && b.primarySign !== 'none';
                const hasSecondary = b.secondarySign && b.secondarySign !== 'none';
                const hasLight = b.trafficLight && b.trafficLight !== 'none';
                if (!hasPrimary && !hasSecondary && !hasLight) return;

                html += `<g transform="translate(${pos.x}, ${pos.y})">`;
                html += `<rect x="-3" y="-28" width="6" height="62" rx="2" fill="#475569" stroke="#0f172a" stroke-width="1.5"/>`;
                html += `<ellipse cx="0" cy="34" rx="12" ry="5" fill="#1e293b" stroke="#0f172a" stroke-width="1.5"/>`;

                if (hasLight) {
                    const lx = (hasPrimary || hasSecondary) ? -22 : 0;
                    html += `<g transform="translate(${lx}, -4)">${renderTrafficLightSVG(b.trafficLight)}</g>`;
                }
                if (hasPrimary && hasSecondary) {
                    html += `<g transform="translate(0, -16) scale(0.92)">${renderSignIconSVG(b.primarySign)}</g>`;
                    html += `<g transform="translate(0, 13) scale(0.88)">${renderSignIconSVG(b.secondarySign)}</g>`;
                } else if (hasPrimary) {
                    html += `<g transform="translate(0, -4)">${renderSignIconSVG(b.primarySign)}</g>`;
                } else if (hasSecondary) {
                    html += `<g transform="translate(0, -4)">${renderSignIconSVG(b.secondarySign)}</g>`;
                }
                html += `</g>`;
            });

            // Placed Road Users (Clickable to move in order!)
            actors.forEach(actor => {
                const basePos = actor.slotType === 'pedestrian'
                    ? BRANCH_GEOMETRY[actor.branch].pedStart
                    : BRANCH_GEOMETRY[actor.branch].vehicleStart;

                const anim = animatedPositions[actor.key];
                const x = anim ? anim.x : basePos.x;
                const y = anim ? anim.y : basePos.y;
                const angle = anim ? anim.angle : basePos.angle;

                const clickIdx = userClickedKeys.indexOf(actor.key);
                const userOrder = clickIdx !== -1 ? (clickIdx + 1) : null;

                let turnSignalHTML = '';
                if (actor.slotType === 'vehicle' && (actor.direction === 'left' || actor.direction === 'right')) {
                    const sigX = actor.direction === 'left' ? -13 : 13;
                    turnSignalHTML = `<circle class="turn-signal-blink" cx="${sigX}" cy="-25" r="4.5" fill="#f97316" stroke="#ffffff" stroke-width="1.5"/>`;
                }

                let badgeBg = '#0f172a';
                let badgeStroke = '#fde047';
                let badgeLabel = userOrder !== null ? userOrder : '؟';

                if (stageEvaluated && stageEvaluated.detailsByKey) {
                    const ok = stageEvaluated.detailsByKey[actor.key];
                    badgeBg = ok ? '#16a34a' : '#dc2626';
                    badgeStroke = '#ffffff';
                    badgeLabel = `${userOrder || '؟'}`;
                } else if (userOrder !== null) {
                    badgeBg = '#2563eb';
                    badgeStroke = '#fde047';
                }

                const graphic = actor.slotType === 'pedestrian'
                    ? renderTopDownPedestrianSVG(actor.type)
                    : renderTopDownVehicleSVG(actor.type);

                html += `
                    <g class="placed-actor-group" id="game_actor_${actor.key}"
                       transform="translate(${x}, ${y})"
                       onclick="onUserClickRoadActor('${actor.key}')">
                        <g transform="rotate(${angle})">
                            ${graphic}
                            ${turnSignalHTML}
                        </g>
                        <g transform="translate(22, -24)">
                            <rect x="-14" y="-12" width="28" height="24" rx="8" fill="${badgeBg}" stroke="${badgeStroke}" stroke-width="2.5"/>
                            <text x="0" y="4" text-anchor="middle" fill="#ffffff" font-size="11.5" font-weight="900">${badgeLabel}</text>
                        </g>
                    </g>
                `;
            });

            svg.innerHTML = html;
        }

        function onUserClickRoadActor(actorKey) {
            if (stageEvaluated) return;

            const actors = getStageActors();
            const actor = actors.find(a => a.key === actorKey);
            if (!actor) return;

            if (!userClickedKeys.includes(actorKey)) {
                userClickedKeys.push(actorKey);
            }
            animateActorMove(actor);
            renderUserSequencePills();
            renderStageSVG();
        }

        function animateActorMove(actor) {
            const pathEl = document.getElementById(`game_traj_${actor.key}`);
            if (!pathEl) return;

            const totalLen = pathEl.getTotalLength();
            if (!totalLen || totalLen <= 0) return;

            if (runningAnimFrames[actor.key]) {
                cancelAnimationFrame(runningAnimFrames[actor.key]);
            }

            const duration = 1050;
            const startTime = performance.now();

            function step(now) {
                const elapsed = now - startTime;
                const t = Math.min(elapsed / duration, 1);
                const eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;

                const dist = eased * (totalLen * 0.88);
                const pt = pathEl.getPointAtLength(dist);
                const ptAhead = pathEl.getPointAtLength(Math.min(dist + 4, totalLen));
                const deg = (Math.atan2(ptAhead.y - pt.y, ptAhead.x - pt.x) * 180 / Math.PI) + 90;

                animatedPositions[actor.key] = { x: pt.x, y: pt.y, angle: deg };

                const gNode = document.getElementById(`game_actor_${actor.key}`);
                if (gNode) {
                    gNode.setAttribute('transform', `translate(${pt.x}, ${pt.y})`);
                    const innerRot = gNode.querySelector('g');
                    if (innerRot) innerRot.setAttribute('transform', `rotate(${deg})`);
                }

                if (t < 1) {
                    runningAnimFrames[actor.key] = requestAnimationFrame(step);
                } else {
                    delete runningAnimFrames[actor.key];
                }
            }

            runningAnimFrames[actor.key] = requestAnimationFrame(step);
        }

        function resetCurrentStageMoves() {
            if (stageEvaluated) return;
            Object.keys(runningAnimFrames).forEach(k => cancelAnimationFrame(runningAnimFrames[k]));
            runningAnimFrames = {};
            animatedPositions = {};
            userClickedKeys = [];
            renderUserSequencePills();
            renderStageSVG();
        }

        function renderUserSequencePills() {
            const container = document.getElementById('userSequencePills');
            const actors = getStageActors();

            if (userClickedKeys.length === 0) {
                container.innerHTML = `<span class="text-muted small">هنوز روی هیچ کاربری در تقاطع کلیک نکرده‌اید. روی کسی که اول حق تقدم دارد کلیک کنید!</span>`;
                return;
            }

            container.innerHTML = userClickedKeys.map((key, idx) => {
                const a = actors.find(x => x.key === key);
                return `<span class="step-order-chip"><span>${idx + 1}.</span> <span>${a ? a.name : key}</span></span>`;
            }).join(' <i class="fas fa-arrow-left text-muted small"></i> ');
        }

        function checkUserPriorityAnswer() {
            if (stageEvaluated) return;

            const actors = getStageActors();
            if (userClickedKeys.length < actors.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ترتیب کامل نیست!',
                    text: `لطفاً به ترتیب حق تقدم روی تمام ${actors.length} کاربر جاده در تقاطع کلیک کنید تا حرکت کنند، سپس روی نمایش پاسخ بزنید.`,
                    confirmButtonText: 'باشه!'
                });
                return;
            }

            const sortedByAdmin = [...actors].sort((a, b) => a.priority - b.priority);
            const detailsByKey = {};
            let allCorrect = true;
            const breakdownHTML = [];

            actors.forEach(actor => {
                const userClickIdx = userClickedKeys.indexOf(actor.key);
                const expectedPriorityAtStep = sortedByAdmin[userClickIdx] ? sortedByAdmin[userClickIdx].priority : actor.priority;
                const isOk = (actor.priority === expectedPriorityAtStep);

                detailsByKey[actor.key] = isOk;
                if (!isOk) allCorrect = false;

                const userNum = userClickIdx + 1;
                if (isOk) {
                    breakdownHTML.push(`
                        <div class="actor-eval-row eval-ok">
                            <span class="fw-bold"><i class="fas fa-check-circle text-success me-1"></i> ${actor.name}</span>
                            <span class="badge bg-success">عالی! اولویت ${actor.priority} صحیح است</span>
                        </div>
                    `);
                } else {
                    breakdownHTML.push(`
                        <div class="actor-eval-row eval-err">
                            <span class="fw-bold"><i class="fas fa-times-circle text-danger me-1"></i> ${actor.name}</span>
                            <span class="badge bg-danger">انتخاب شما: اولویت ${userNum} | اولویت صحیح: ${actor.priority}</span>
                        </div>
                    `);
                }
            });

            stageEvaluated = { isAllCorrect: allCorrect, detailsByKey: detailsByKey };

            if (allCorrect) {
                winStagesCount++;
                totalScore += 50;
                document.getElementById('hudScoreVal').textContent = totalScore;
            } else {
                loseStagesCount++;
            }

            // Re-render SVG so lines turn Green or Red!
            renderStageSVG();

            const banner = document.getElementById('priorityResultBanner');
            if (allCorrect) {
                banner.className = 'overall-result-banner result-win';
                banner.innerHTML = `
                    <i class="fas fa-trophy fa-2x"></i>
                    <div>
                        <div class="fs-6">آفرین! ترتیب حق تقدم این تقاطع را کاملاً درست تشخیص دادید (+۵۰ امتیاز)</div>
                        <div class="fw-normal small">تمام مسیرهای حرکت روی نقشه سبز شدند.</div>
                    </div>
                `;
            } else {
                banner.className = 'overall-result-banner result-lose';
                banner.innerHTML = `
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <div>
                        <div class="fs-6">دقت کنید! در تشخیص ترتیب حق تقدم اشتباه داشتید.</div>
                        <div class="fw-normal small">مسیرهای قرمز روی نقشه نشان‌دهنده اولویت‌هایی هستند که اشتباه انتخاب کردید.</div>
                    </div>
                `;
            }

            document.getElementById('priorityActorsBreakdown').innerHTML = breakdownHTML.join('');
            const sc = SCENARIOS[currentStageIdx];
            document.getElementById('priorityExplanationText').textContent = (sc && sc.explanation) ? sc.explanation : 'توضیحی برای این تقاطع ثبت نشده است.';

            document.getElementById('nextStageBtnText').textContent = (currentStageIdx >= SCENARIOS.length - 1) ? 'مشاهده نتیجه پایانی' : 'تقاطع بعدی';

            document.getElementById('solveButtonWrapper').style.display = 'none';
            const fbPanel = document.getElementById('priorityFeedbackPanel');
            fbPanel.style.display = 'block';
            fbPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function repeatCurrentStage() {
            loadStage(currentStageIdx);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goToNextStage() {
            if (currentStageIdx < SCENARIOS.length - 1) {
                currentStageIdx++;
                loadStage(currentStageIdx);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                document.getElementById('activePriorityGameArea').style.display = 'none';
                document.getElementById('sumWinCount').textContent = winStagesCount;
                document.getElementById('sumLoseCount').textContent = loseStagesCount;
                document.getElementById('prioritySummaryScreen').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadStage(0);
        });
    </script>
    <?php endif; ?>
</body>
</html>
