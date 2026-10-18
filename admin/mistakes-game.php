<?php
// admin/mistakes-game.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../incloud/functions.php';
require_once __DIR__ . '/../incloud/questions.php';
require_once __DIR__ . '/../incloud/user-config-handler.php';

if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

check_user_configuration();

$user_id = $_SESSION['user_id'];
$csrf_token = $_SESSION['csrf_token'] ?? generate_csrf_token();

// دریافت تنظیمات کاربر (زبان و تاریخ امتحان)
$configHandler = new UserConfigHandler($pdo);
$userConfig = $configHandler->getUserConfig($user_id);
$examDateType = $userConfig ? $userConfig['exam_date_type'] : null;
$userLanguage = $userConfig ? $userConfig['language'] : 'DE';

$availableCondition = "";
if ($examDateType === 'before') {
    $availableCondition = " AND (q.available = 0 OR q.available = 1)";
} elseif ($examDateType === 'after') {
    $availableCondition = " AND (q.available = 0 OR q.available = 2)";
}

// بررسی محدودیت پلن رایگان در صورت عدم داشتن اشتراک VIP
$isVip = is_user_vip($user_id, $pdo);
$questionLimit = get_user_question_limit($user_id, $pdo);
$maxAccessibleId = null;

if (!$isVip && $questionLimit !== null) {
    $availWhere = "";
    if ($examDateType === 'before') {
        $availWhere = " AND (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availWhere = " AND (available = 0 OR available = 2)";
    }
    $stmtLimit = $pdo->prepare("SELECT id FROM questions WHERE 1=1 $availWhere ORDER BY id ASC LIMIT 1 OFFSET " . max(0, intval($questionLimit) - 1));
    $stmtLimit->execute();
    $limitId = $stmtLimit->fetchColumn();
    if ($limitId !== false) {
        $maxAccessibleId = intval($limitId);
    }
}

$accessCondition = "";
if ($maxAccessibleId !== null) {
    $accessCondition = " AND q.id <= " . intval($maxAccessibleId);
}

// شمارش کل سوالات اشتباه کاربر
$countMistakesSql = "
    SELECT COUNT(*)
    FROM questions q
    INNER JOIN user_question_stats uqs ON q.id = uqs.question_id
    WHERE uqs.user_id = :user_id
      AND uqs.incorrect >= 1
      $availableCondition
      $accessCondition
      AND EXISTS (
          SELECT 1 FROM answers a
          WHERE a.question_number = q.number
            AND (a.asw_type IS NULL OR a.asw_type != 2)
      )
";
$stmtCountMistakes = $pdo->prepare($countMistakesSql);
$stmtCountMistakes->execute([':user_id' => $user_id]);
$totalMistakesCount = (int) $stmtCountMistakes->fetchColumn();

// شمارش کل سوالات ۵ نمره‌ای قابل دسترس کاربر
$count5PointsSql = "
    SELECT COUNT(*)
    FROM questions q
    WHERE q.points = 5
      $availableCondition
      $accessCondition
      AND EXISTS (
          SELECT 1 FROM answers a
          WHERE a.question_number = q.number
            AND (a.asw_type IS NULL OR a.asw_type != 2)
      )
";
$stmtCount5Points = $pdo->prepare($count5PointsSql);
$stmtCount5Points->execute();
$total5PointsCount = (int) $stmtCount5Points->fetchColumn();

// شمارش کل سوالات مارک‌شده (نشان‌شده) کاربر
$countBookmarksSql = "
    SELECT COUNT(*)
    FROM questions q
    INNER JOIN question_bookmarks b ON q.id = b.question_id
    WHERE b.user_id = :user_id
      $availableCondition
      $accessCondition
      AND EXISTS (
          SELECT 1 FROM answers a
          WHERE a.question_number = q.number
            AND (a.asw_type IS NULL OR a.asw_type != 2)
      )
";
$stmtCountBookmarks = $pdo->prepare($countBookmarksSql);
$stmtCountBookmarks->execute([':user_id' => $user_id]);
$totalBookmarksCount = (int) $stmtCountBookmarks->fetchColumn();

// بررسی حالت انتخاب شده بازی
$selectedMode = $_GET['mode'] ?? '';
$validModes = ['mistakes', '5points', 'bookmarks'];
$isLobby = !in_array($selectedMode, $validModes, true);

$gameQuestions = [];
$modeTitle = 'بازی و یادگیری';

if (!$isLobby) {
    if ($selectedMode === 'mistakes') {
        $modeTitle = 'بازی با ۳۰ اشتباه اخیر';
        $questionsSql = "
            SELECT q.*, uqs.correct AS stat_correct, uqs.incorrect AS stat_incorrect
            FROM questions q
            INNER JOIN user_question_stats uqs ON q.id = uqs.question_id
            WHERE uqs.user_id = :user_id
              AND uqs.incorrect >= 1
              $availableCondition
              $accessCondition
              AND EXISTS (
                  SELECT 1 FROM answers a
                  WHERE a.question_number = q.number
                    AND (a.asw_type IS NULL OR a.asw_type != 2)
              )
            ORDER BY uqs.updated_at DESC, q.id ASC
            LIMIT 30
        ";
        $stmtQuestions = $pdo->prepare($questionsSql);
        $stmtQuestions->execute([':user_id' => $user_id]);
        $rawQuestions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($selectedMode === 'bookmarks') {
        $modeTitle = 'بازی با سوالات مارک‌شده';
        $questionsSql = "
            SELECT q.*, COALESCE(uqs.correct, 0) AS stat_correct, COALESCE(uqs.incorrect, 0) AS stat_incorrect
            FROM questions q
            INNER JOIN question_bookmarks b ON q.id = b.question_id
            LEFT JOIN user_question_stats uqs ON q.id = uqs.question_id AND uqs.user_id = :user_id
            WHERE b.user_id = :bookmark_user_id
              $availableCondition
              $accessCondition
              AND EXISTS (
                  SELECT 1 FROM answers a
                  WHERE a.question_number = q.number
                    AND (a.asw_type IS NULL OR a.asw_type != 2)
              )
            ORDER BY b.created_at DESC, q.id ASC
        ";
        $stmtQuestions = $pdo->prepare($questionsSql);
        $stmtQuestions->execute([':user_id' => $user_id, ':bookmark_user_id' => $user_id]);
        $rawQuestions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($selectedMode === '5points') {
        $modeTitle = 'بازی با سوالات ۵ نمره‌ای';
        $questionsSql = "
            SELECT q.*, COALESCE(uqs.correct, 0) AS stat_correct, COALESCE(uqs.incorrect, 0) AS stat_incorrect
            FROM questions q
            LEFT JOIN user_question_stats uqs ON q.id = uqs.question_id AND uqs.user_id = :user_id
            WHERE q.points = 5
              $availableCondition
              $accessCondition
              AND EXISTS (
                  SELECT 1 FROM answers a
                  WHERE a.question_number = q.number
                    AND (a.asw_type IS NULL OR a.asw_type != 2)
              )
            ORDER BY COALESCE(uqs.incorrect, 0) DESC, COALESCE(uqs.correct, 0) ASC, q.id ASC
        ";
        $stmtQuestions = $pdo->prepare($questionsSql);
        $stmtQuestions->execute([':user_id' => $user_id]);
        $rawQuestions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!empty($rawQuestions)) {
        $stmtAnswers = $pdo->prepare("
            SELECT id, question_number, text, en_text, farsi_text, info, is_image, original_content, asw_type, asw_corr, asw_hint
            FROM answers
            WHERE question_number = :qnum
            ORDER BY id ASC
        ");

        foreach ($rawQuestions as $q) {
            if ($userLanguage === 'EN') {
                if (!empty($q['en_text'])) {
                    $q['text'] = $q['en_text'];
                }
                if (!empty($q['asw_en'])) {
                    $q['asw_pretext'] = $q['asw_en'];
                }
            }

            $stmtAnswers->execute([':qnum' => $q['number']]);
            $answers = $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);

            if ($userLanguage === 'EN') {
                foreach ($answers as &$ans) {
                    if (!empty($ans['en_text'])) {
                        $ans['text'] = $ans['en_text'];
                    }
                }
                unset($ans);
            }

            if (!empty($answers)) {
                $gameQuestions[] = [
                    'id' => (int) $q['id'],
                    'number' => $q['number'],
                    'text' => $q['text'],
                    'farsi_text' => $q['farsi_text'] ?? '',
                    'info' => $q['info'] ?? '',
                    'asw_pretext' => $q['asw_pretext'] ?? '',
                    'asw_farsi' => $q['asw_farsi'] ?? '',
                    'picture' => $q['picture'] ?? '',
                    'points' => (int) ($q['points'] ?? 0),
                    'stat_correct' => (int) $q['stat_correct'],
                    'stat_incorrect' => (int) $q['stat_incorrect'],
                    'answers' => $answers
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($modeTitle) ?> | FarsiFahr</title>
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
           2D ARCADE GAME THEME & ENVIRONMENT
           ========================================================= */
        :root {
            --sky-top: #38bdf8;
            --sky-bottom: #bae6fd;
            --ink-outline: #1e1b4b;
            --board-bg: #fffbeb;
            --board-inner: #ffffff;
            --board-shadow: #d97706;
            --tile-bg: #ffffff;
            --tile-border: #1e293b;
            --tile-lip: #94a3b8;
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
            --tile-bg: #1e293b;
            --tile-border: #64748b;
            --tile-lip: #020617;
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

        /* 2D Game World Background Clouds & Retro Grid Pattern */
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
            background: linear-gradient(180deg, #312e81 0%, #1e1b4b 100%);
            border-bottom: 4px solid #0f0d26;
            box-shadow: 0 6px 0 rgba(15, 23, 42, 0.28);
            padding: 10px 14px;
            color: #ffffff;
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
            position: relative;
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
            background: linear-gradient(180deg, #fef08a 0%, #fde047 100%);
            color: #1e1b4b;
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

        .hud-score-star {
            color: #facc15;
            filter: drop-shadow(0 2px 0 #854d0e);
        }

        /* 2D Health/Stage Progress Bar */
        .hud-progress-shell {
            height: 14px;
            background: #090d16;
            border: 2.5px solid #475569;
            border-radius: 999px;
            padding: 2px;
            margin-top: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.7);
        }

        .hud-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #22c55e 0%, #4ade80 50%, #facc15 100%);
            border-radius: 999px;
            position: relative;
            transition: width 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .hud-progress-bar::after {
            content: '';
            position: absolute;
            top: 1px;
            left: 4px;
            right: 4px;
            height: 3px;
            background: rgba(255, 255, 255, 0.55);
            border-radius: 999px;
        }

        /* =========================================================
           2D GAME LOBBY (MODE SELECTION CARDS)
           ========================================================= */
        .game-container {
            position: relative;
            z-index: 2;
            max-width: 900px;
        }

        .lobby-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            border: 4px solid var(--ink-outline);
            border-radius: 22px;
            box-shadow: 0 8px 0 var(--ink-outline), inset 0 4px 0 rgba(255, 255, 255, 0.35);
            padding: 22px 20px;
            color: #ffffff;
            text-align: center;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }

        .lobby-mode-card {
            background: var(--board-inner);
            border: 4px solid var(--ink-outline);
            border-radius: 22px;
            padding: 22px 18px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            text-decoration: none;
            color: var(--text-main);
            cursor: pointer;
        }

        .lobby-mode-mistakes {
            box-shadow: 0 8px 0 #b91c1c, inset 0 4px 0 rgba(239, 68, 68, 0.15);
            border-color: #991b1b;
        }

        .lobby-mode-5points {
            box-shadow: 0 8px 0 #b45309, inset 0 4px 0 rgba(245, 158, 11, 0.18);
            border-color: #92400e;
        }

        .lobby-mode-bookmarks {
            box-shadow: 0 8px 0 #1d4ed8, inset 0 4px 0 rgba(59, 130, 246, 0.18);
            border-color: #1e40af;
        }

        .lobby-mode-card:hover {
            transform: translateY(-4px);
            color: var(--text-main);
        }

        .lobby-mode-card:active {
            transform: translateY(4px);
        }

        .mode-icon-box {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            border: 3px solid var(--ink-outline);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 14px;
            box-shadow: 0 4px 0 var(--ink-outline), inset 0 3px 0 rgba(255, 255, 255, 0.45);
        }

        .mode-icon-mistakes {
            background: linear-gradient(180deg, #fb7185 0%, #e11d48 100%);
            color: #ffffff;
        }

        .mode-icon-5points {
            background: linear-gradient(180deg, #fcd34d 0%, #f59e0b 100%);
            color: #1e1b4b;
        }

        .mode-icon-bookmarks {
            background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
            color: #ffffff;
        }

        .mode-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            border: 2px solid var(--ink-outline);
            margin-bottom: 12px;
        }

        .btn-play-mode {
            width: 100%;
            padding: 11px 16px;
            border-radius: 14px;
            border: 3px solid var(--ink-outline);
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
        }

        .btn-play-red {
            background: linear-gradient(180deg, #f43f5e 0%, #e11d48 100%);
            color: #ffffff;
            box-shadow: 0 5px 0 #881337, inset 0 2px 0 rgba(255, 255, 255, 0.4);
        }

        .btn-play-gold {
            background: linear-gradient(180deg, #fbbf24 0%, #d97706 100%);
            color: #1e1b4b;
            box-shadow: 0 5px 0 #78350f, inset 0 2px 0 rgba(255, 255, 255, 0.55);
        }

        .btn-play-blue {
            background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 5px 0 #1e3a8a, inset 0 2px 0 rgba(255, 255, 255, 0.4);
        }

        /* =========================================================
           2D QUEST BOARD (QUESTION & MEDIA)
           ========================================================= */
        .quest-board {
            background: var(--board-bg);
            border: 4px solid var(--ink-outline);
            border-radius: 22px;
            box-shadow: 0 7px 0 var(--board-shadow), 0 12px 20px rgba(15, 23, 42, 0.15);
            padding: 14px;
            margin-bottom: 14px;
            position: relative;
        }

        /* 2D Corner Bolts on Quest Board */
        .quest-board::before,
        .quest-board::after {
            content: '';
            position: absolute;
            top: 10px;
            width: 10px;
            height: 10px;
            background: #fbbf24;
            border: 2px solid var(--ink-outline);
            border-radius: 50%;
        }
        .quest-board::before { right: 10px; }
        .quest-board::after { left: 10px; }

        .quest-board-inner {
            background: var(--board-inner);
            border: 3px solid var(--ink-outline);
            border-radius: 16px;
            padding: 14px;
        }

        .question-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px dashed rgba(100, 116, 139, 0.35);
        }

        .q-code-badge {
            font-family: monospace;
            font-size: 0.82rem;
            background: #e0e7ff;
            color: #1e1b4b;
            border: 2px solid #1e1b4b;
            padding: 2px 9px;
            border-radius: 8px;
            font-weight: 800;
            box-shadow: 0 2px 0 #1e1b4b;
        }

        .q-status-pill {
            font-size: 0.76rem;
            padding: 3px 10px;
            border-radius: 999px;
            font-weight: 800;
            border: 2px solid var(--ink-outline);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .q-status-red { background: #fee2e2; color: #991b1b; }
        .q-status-yellow { background: #fef3c7; color: #92400e; }
        .q-status-blue { background: #dbeafe; color: #1e40af; }
        .q-status-green { background: #d1fae5; color: #065f46; }
        .q-status-gray { background: #f1f5f9; color: #334155; }

        .q-points-chip {
            background: linear-gradient(180deg, #fde047 0%, #f59e0b 100%);
            color: #1e1b4b;
            border: 2px solid #1e1b4b;
            box-shadow: 0 2px 0 #1e1b4b;
            border-radius: 8px;
            padding: 2px 10px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .question-de-text {
            direction: ltr;
            text-align: left;
            font-size: 1.04rem;
            font-weight: 800;
            line-height: 1.55;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .question-pretext {
            direction: ltr;
            text-align: left;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-sub);
            margin-bottom: 8px;
        }

        .question-media-box {
            text-align: center;
            margin-top: 10px;
            background: rgba(15, 23, 42, 0.04);
            border: 2.5px solid var(--ink-outline);
            border-radius: 14px;
            padding: 8px;
        }

        .question-media-box img {
            max-height: 205px;
            width: auto;
            max-width: 100%;
            border-radius: 10px;
            cursor: zoom-in;
            object-fit: contain;
        }

        .question-media-box video {
            max-height: 220px;
            width: 100%;
            max-width: 460px;
            border-radius: 10px;
            background: #000;
        }

        /* =========================================================
           2D DRAGGABLE ANSWER TILES / CRATES
           ========================================================= */
        .answers-deck-zone {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(6px);
            border: 3.5px dashed var(--ink-outline);
            border-radius: 20px;
            padding: 12px;
            margin-bottom: 14px;
            min-height: 82px;
            transition: all 0.2s ease;
        }

        html[data-bs-theme="dark"] .answers-deck-zone {
            background: rgba(15, 23, 42, 0.65);
            border-color: #64748b;
        }

        .answers-deck-zone.drag-over-pool {
            background: rgba(56, 189, 248, 0.2);
            border-style: solid;
        }

        .deck-title-bar {
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* 2D Game Tile for each Answer Option */
        .draggable-option {
            background: var(--tile-bg);
            border: 3px solid var(--tile-border);
            border-radius: 14px;
            box-shadow: 0 5px 0 var(--tile-border), inset 0 2px 0 rgba(255, 255, 255, 0.7);
            padding: 10px 12px;
            margin-bottom: 10px;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            transition: transform 0.14s ease, box-shadow 0.14s ease;
            position: relative;
        }

        html[data-bs-theme="dark"] .draggable-option {
            box-shadow: 0 5px 0 #020617, inset 0 2px 0 rgba(255, 255, 255, 0.08);
        }

        .draggable-option:last-child {
            margin-bottom: 0;
        }

        .draggable-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 0 var(--tile-border);
        }

        .draggable-option:active {
            cursor: grabbing;
            transform: translateY(2px);
            box-shadow: 0 3px 0 var(--tile-border);
        }

        .draggable-option.dragging-source {
            opacity: 0.3;
            transform: scale(0.96);
        }

        .tile-letter-badge {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: linear-gradient(180deg, #6366f1 0%, #4338ca 100%);
            color: #ffffff;
            border: 2px solid #1e1b4b;
            box-shadow: 0 2px 0 #1e1b4b;
            font-weight: 900;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .option-text-ltr {
            direction: ltr;
            text-align: left;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-main);
            flex-grow: 1;
            line-height: 1.45;
            word-break: break-word;
        }

        .option-quick-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .btn-quick-basket {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            border: 2.5px solid #1e1b4b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.82rem;
            transition: transform 0.1s ease;
        }

        .btn-quick-basket:active {
            transform: translateY(2px);
        }

        .btn-quick-green {
            background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
            color: #ffffff;
            box-shadow: 0 3px 0 #14532d;
        }

        .btn-quick-red {
            background: linear-gradient(180deg, #f87171 0%, #dc2626 100%);
            color: #ffffff;
            box-shadow: 0 3px 0 #7f1d1d;
        }

        .btn-quick-pool {
            background: linear-gradient(180deg, #e2e8f0 0%, #94a3b8 100%);
            color: #0f172a;
            box-shadow: 0 3px 0 #334155;
        }

        /* =========================================================
           2D ARCADE BASKETS (GREEN & RED CHESTS/PIPES)
           ========================================================= */
        .baskets-row {
            margin-bottom: 16px;
        }

        .basket-zone {
            border-radius: 20px;
            min-height: 195px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.18s ease;
            height: 100%;
            position: relative;
        }

        .basket-green {
            background: linear-gradient(180deg, rgba(209, 250, 229, 0.92) 0%, rgba(167, 243, 208, 0.95) 100%);
            border: 4px solid #065f46;
            box-shadow: 0 7px 0 #064e3b, 0 12px 20px rgba(6, 78, 59, 0.18);
        }

        html[data-bs-theme="dark"] .basket-green {
            background: linear-gradient(180deg, rgba(6, 78, 59, 0.65) 0%, rgba(4, 47, 46, 0.85) 100%);
            border-color: #10b981;
            box-shadow: 0 7px 0 #022c22;
        }

        .basket-red {
            background: linear-gradient(180deg, rgba(254, 226, 226, 0.92) 0%, rgba(254, 202, 202, 0.95) 100%);
            border: 4px solid #991b1b;
            box-shadow: 0 7px 0 #7f1d1d, 0 12px 20px rgba(127, 29, 29, 0.18);
        }

        html[data-bs-theme="dark"] .basket-red {
            background: linear-gradient(180deg, rgba(127, 29, 29, 0.65) 0%, rgba(69, 10, 10, 0.85) 100%);
            border-color: #ef4444;
            box-shadow: 0 7px 0 #450a0a;
        }

        .basket-zone.drag-over-active {
            transform: translateY(-5px) scale(1.02);
        }

        .basket-green.drag-over-active {
            box-shadow: 0 10px 0 #064e3b, 0 0 0 5px rgba(16, 185, 129, 0.45);
        }

        .basket-red.drag-over-active {
            box-shadow: 0 10px 0 #7f1d1d, 0 0 0 5px rgba(239, 68, 68, 0.45);
        }

        .basket-header {
            padding: 10px 12px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 900;
            font-size: 0.86rem;
            border-bottom: 3.5px solid var(--ink-outline);
            text-shadow: 0 2px 0 rgba(0, 0, 0, 0.28);
        }

        .basket-green .basket-header {
            background: linear-gradient(180deg, #22c55e 0%, #15803d 100%);
        }

        .basket-red .basket-header {
            background: linear-gradient(180deg, #ef4444 0%, #b91c1c 100%);
        }

        .basket-count-badge {
            background: #1e1b4b;
            color: #fde047;
            border: 2px solid #ffffff;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.76rem;
            font-weight: 900;
        }

        .basket-body {
            padding: 10px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .basket-empty-hint {
            margin: auto;
            text-align: center;
            padding: 14px 6px;
            pointer-events: none;
        }

        .basket-green .basket-empty-hint {
            color: #065f46;
        }
        html[data-bs-theme="dark"] .basket-green .basket-empty-hint {
            color: #6ee7b7;
        }

        .basket-red .basket-empty-hint {
            color: #991b1b;
        }
        html[data-bs-theme="dark"] .basket-red .basket-empty-hint {
            color: #fca5a5;
        }

        .basket-body .draggable-option {
            padding: 8px 10px;
            margin-bottom: 0;
            font-size: 0.84rem;
        }

        /* Floating Drag Ghost */
        .drag-ghost {
            position: fixed;
            z-index: 99999;
            pointer-events: none;
            background: #fef08a;
            border: 3.5px solid #1e1b4b;
            border-radius: 14px;
            padding: 10px 14px;
            box-shadow: 0 14px 0 rgba(30, 27, 75, 0.35), 0 18px 30px rgba(0, 0, 0, 0.3);
            transform: rotate(-2deg) scale(1.04);
            max-width: 310px;
            direction: ltr;
            text-align: left;
            font-weight: 800;
            font-size: 0.88rem;
            color: #1e1b4b;
        }

        /* 2D Arcade Solve Button ("باشه") */
        .solve-action-bar {
            text-align: center;
            margin: 16px 0;
        }

        .btn-solve-game {
            background: linear-gradient(180deg, #facc15 0%, #eab308 100%);
            color: #1e1b4b;
            border: 4px solid #1e1b4b;
            border-radius: 18px;
            padding: 12px 42px;
            font-size: 1.1rem;
            font-weight: 900;
            box-shadow: 0 7px 0 #854d0e, inset 0 3px 0 rgba(255, 255, 255, 0.7);
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-solve-game:hover {
            transform: translateY(-2px);
            box-shadow: 0 9px 0 #854d0e, inset 0 3px 0 rgba(255, 255, 255, 0.8);
        }

        .btn-solve-game:active {
            transform: translateY(5px);
            box-shadow: 0 2px 0 #854d0e;
        }

        /* =========================================================
           2D STAGE CLEAR / EVALUATION PANEL
           ========================================================= */
        .feedback-panel {
            display: none;
            animation: popInPanel 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            margin-top: 16px;
        }

        @keyframes popInPanel {
            from { opacity: 0; transform: scale(0.94) translateY(10px); }
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

        .answer-eval-card {
            background: var(--board-inner);
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 12px;
            border: 3px solid var(--ink-outline);
            box-shadow: 0 5px 0 rgba(15, 23, 42, 0.2);
        }

        .answer-eval-card.eval-correct {
            border-right: 8px solid #16a34a;
        }

        .answer-eval-card.eval-wrong {
            border-right: 8px solid #dc2626;
        }

        .eval-option-text {
            direction: ltr;
            text-align: left;
            font-weight: 800;
            font-size: 0.95rem;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .eval-reason-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.83rem;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 10px;
            border: 2px solid var(--ink-outline);
            margin-bottom: 8px;
        }

        .eval-reason-ok {
            background: #dcfce7;
            color: #14532d;
        }

        .eval-reason-err {
            background: #fee2e2;
            color: #7f1d1d;
        }

        .eval-fa-trans {
            font-size: 0.85rem;
            color: var(--text-main);
            background: rgba(59, 130, 246, 0.1);
            border-right: 4px solid #2563eb;
            padding: 7px 10px;
            border-radius: 8px;
            margin-top: 6px;
        }

        .eval-fa-info {
            font-size: 0.83rem;
            color: var(--text-sub);
            background: rgba(168, 85, 247, 0.1);
            border-right: 4px solid #9333ea;
            padding: 7px 10px;
            border-radius: 8px;
            margin-top: 6px;
        }

        .eval-fa-info p:last-child {
            margin-bottom: 0;
        }

        .question-knowledge-card {
            background: var(--board-inner);
            border: 4px solid var(--ink-outline);
            border-radius: 18px;
            box-shadow: 0 6px 0 var(--ink-outline);
            padding: 16px;
            margin-top: 14px;
            margin-bottom: 16px;
        }

        .knowledge-block-trans {
            background: rgba(37, 99, 235, 0.1);
            border-right: 4px solid #2563eb;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }

        .knowledge-block-info {
            background: rgba(139, 92, 246, 0.1);
            border-right: 4px solid #8b5cf6;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .knowledge-block-info p:last-child {
            margin-bottom: 0;
        }

        .knowledge-block-info img,
        .eval-fa-info img {
            max-width: 180px;
            height: auto;
            border-radius: 8px;
            margin: 8px 0;
        }

        .post-solve-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 18px;
            margin-bottom: 28px;
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

        @media (max-width: 767.98px) {
            .quest-board { padding: 10px; }
            .quest-board-inner { padding: 10px; }
            .question-de-text { font-size: 0.93rem; }
            .draggable-option { padding: 8px 10px; }
            .option-text-ltr { font-size: 0.82rem; }
            .tile-letter-badge { width: 26px; height: 26px; font-size: 0.75rem; }
            .basket-zone { min-height: 160px; }
            .basket-header { font-size: 0.76rem; padding: 8px 8px; }
            .baskets-row { margin-left: -4px; margin-right: -4px; }
            .baskets-row > .col-6 { padding-left: 4px; padding-right: 4px; }
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

    <!-- 2D Arcade Top HUD -->
    <header class="arcade-hud">
        <div class="container game-container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="index.php" class="btn-2d btn-2d-yellow">
                        <i class="bx bx-home-alt"></i>
                        <span>داشبورد</span>
                    </a>
                    <?php if (!$isLobby): ?>
                    <a href="mistakes-game.php" class="btn-2d btn-2d-cyan">
                        <i class="fas fa-th-large"></i>
                        <span>تغییر حالت بازی</span>
                    </a>
                    <?php endif; ?>
                    <a href="priority-game.php" class="btn-2d btn-2d-cyan" style="background: linear-gradient(180deg, #14b8a6 0%, #0d9488 100%); box-shadow: 0 4px 0 #115e59;">
                        <i class="fas fa-traffic-light"></i>
                        <span>بازی حق تقدم‌ها</span>
                    </a>
                    <?php if (is_super_admin() || is_admin()): ?>
                    <a href="priority-game-builder.php" class="btn-2d btn-2d-cyan" style="background: linear-gradient(180deg, #a855f7 0%, #7e22ce 100%); box-shadow: 0 4px 0 #4c1d95;">
                        <i class="fas fa-drafting-compass"></i>
                        <span>طراحی بازی اولویت‌ها (ادمین)</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!$isLobby && !empty($gameQuestions)): ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="hud-stat-pill" title="امتیاز کسب‌شده در این دور">
                        <i class="fas fa-star hud-score-star"></i>
                        <span>امتیاز: <strong id="hudScoreVal">0</strong></span>
                    </div>
                    <div class="hud-stat-pill">
                        <i class="fas fa-flag-checkered text-info"></i>
                        <span>مرحله <strong id="currentQIndexLabel">1</strong> / <strong><?= count($gameQuestions) ?></strong></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!$isLobby && !empty($gameQuestions)): ?>
            <div class="hud-progress-shell">
                <div class="hud-progress-bar" id="gameProgressBar" style="width: <?= round(100 / count($gameQuestions), 1) ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container game-container mt-3">
        <?php if ($isLobby): ?>
            <!-- =========================================================
                 2D GAME MODE SELECTION LOBBY
                 ========================================================= -->
            <div class="lobby-hero-banner mt-2">
                <h3 class="fw-black mb-2" style="font-weight: 900; text-shadow: 0 3px 0 #1e1b4b;">بازی و یادگیری گواهینامه</h3>
                <p class="mb-0" style="font-size: 0.9rem; opacity: 0.95; max-width: 580px; margin: 0 auto;">
                    یک حالت بازی را انتخاب کنید، گزینه‌های صحیح را در <strong>سبد سبز</strong> و گزینه‌های غلط را در <strong>سبد قرمز</strong> بیندازید و امتیاز بگیرید!
                </p>
            </div>

            <div class="row g-3">
                <!-- Mode 1: Play with 30 Recent Mistakes -->
                <div class="col-12 col-md-4">
                    <a href="mistakes-game.php?mode=mistakes" class="lobby-mode-card lobby-mode-mistakes text-center">
                        <div>
                            <div class="mode-icon-box mode-icon-mistakes">
                                <i class="fas fa-fire-alt"></i>
                            </div>
                            <span class="mode-count-pill" style="background: #fee2e2; color: #991b1b;">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?= $totalMistakesCount ?> سوال اشتباه در آمار شما</span>
                            </span>
                            <h4 style="font-weight: 900; font-size: 1.15rem;" class="mb-2">بازی با ۳۰ اشتباه اخیر</h4>
                            <p class="text-muted mb-0" style="font-size: 0.84rem; line-height: 1.6;">
                                سوالاتی که ۱ یا ۲ بار اشتباه پاسخ داده‌اید را در محیط بازی تمرین کنید تا به نقاط قوت شما تبدیل شوند.
                            </p>
                        </div>
                        <div class="btn-play-mode btn-play-red">
                            <i class="fas fa-play"></i>
                            <span>شروع بازی ۳۰ اشتباه اخیر</span>
                        </div>
                    </a>
                </div>

                <!-- Mode 2: Play with Marked (Bookmarked) Questions -->
                <div class="col-12 col-md-4">
                    <a href="mistakes-game.php?mode=bookmarks" class="lobby-mode-card lobby-mode-bookmarks text-center">
                        <div>
                            <div class="mode-icon-box mode-icon-bookmarks">
                                <i class="fas fa-bookmark"></i>
                            </div>
                            <span class="mode-count-pill" style="background: #dbeafe; color: #1e40af;">
                                <i class="fas fa-bookmark"></i>
                                <span><?= $totalBookmarksCount ?> سوال مارک‌شده شما</span>
                            </span>
                            <h4 style="font-weight: 900; font-size: 1.15rem;" class="mb-2">بازی با سوالات مارک‌شده</h4>
                            <p class="text-muted mb-0" style="font-size: 0.84rem; line-height: 1.6;">
                                سوالاتی که هنگام مطالعه مارک (نشان) کرده‌اید را در قالب بازی مرور و تثبیت کنید.
                            </p>
                        </div>
                        <div class="btn-play-mode btn-play-blue">
                            <i class="fas fa-play"></i>
                            <span>شروع بازی سوالات مارک‌شده</span>
                        </div>
                    </a>
                </div>

                <!-- Mode 3: Play with All 5-Point Questions -->
                <div class="col-12 col-md-4">
                    <a href="mistakes-game.php?mode=5points" class="lobby-mode-card lobby-mode-5points text-center">
                        <div>
                            <div class="mode-icon-box mode-icon-5points">
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="mode-count-pill" style="background: #fef3c7; color: #92400e;">
                                <i class="fas fa-bolt"></i>
                                <span><?= $total5PointsCount ?> سوال ۵ نمره‌ای مهم</span>
                            </span>
                            <h4 style="font-weight: 900; font-size: 1.15rem;" class="mb-2">بازی با سوالات ۵ نمره‌ای</h4>
                            <p class="text-muted mb-0" style="font-size: 0.84rem; line-height: 1.6;">
                                مهم‌ترین و پرنمره‌ترین سوالات آزمون (۵ امتیازی) را به شکل بازی تفکیک کنید و تسلط خود را به ۱۰۰٪ برسانید.
                            </p>
                        </div>
                        <div class="btn-play-mode btn-play-gold">
                            <i class="fas fa-play"></i>
                            <span>شروع بازی سوالات ۵ نمره‌ای</span>
                        </div>
                    </a>
                </div>
            </div>

        <?php elseif (empty($gameQuestions)): ?>
            <!-- Empty State when chosen mode has 0 questions -->
            <div class="quest-board text-center py-5 mt-3">
                <div class="quest-board-inner py-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: #dcfce7; border: 3px solid #1e1b4b; box-shadow: 0 4px 0 #1e1b4b; color: #16a34a; font-size: 2.2rem;">
                            <i class="fas fa-trophy"></i>
                        </span>
                    </div>
                    <h4 style="font-weight: 900;" class="mb-2">تبریک! در این بخش سوالی باقی نمانده است</h4>
                    <p class="text-muted mb-4" style="max-width: 460px; margin: 0 auto;">
                        هیچ سوالی برای «<?= htmlspecialchars($modeTitle) ?>» یافت نشد. می‌توانید حالت دیگر بازی را امتحان کنید!
                    </p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="mistakes-game.php" class="btn-2d btn-2d-cyan px-4 py-2">
                            <i class="fas fa-gamepad"></i>
                            <span>بازگشت به منوی بازی</span>
                        </a>
                        <a href="index.php" class="btn-2d btn-2d-yellow px-4 py-2">
                            <span>بازگشت به داشبورد</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <!-- =========================================================
                 2D ACTIVE GAMEPLAY AREA
                 ========================================================= -->
            <div id="gamePlayContainer">
                <!-- 1. 2D Quest Board: Question & Media (Top) -->
                <div class="quest-board">
                    <div class="quest-board-inner">
                        <div class="question-meta-row">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="q-code-badge" id="qCodeBadge"></span>
                                <span class="q-status-pill" id="qStatusPill"></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="q-points-chip">
                                    <i class="fas fa-bolt me-1"></i> ارزش سوال: <strong id="qPointsBadge">0</strong> امتیاز
                                </span>
                            </div>
                        </div>

                        <div class="question-de-text" id="qMainText"></div>
                        <div class="question-pretext" id="qPreText" style="display: none;"></div>

                        <div class="question-media-box" id="qMediaBox" style="display: none;"></div>
                    </div>
                </div>

                <!-- 2. 2D Answer Tiles Deck -->
                <div class="answers-deck-zone" id="answersPoolZone" data-zone="pool">
                    <div class="deck-title-bar">
                        <span><i class="fas fa-hand-rock me-1 text-primary"></i> کارت‌های پاسخ را بکشید و در سبد سبز یا قرمز بیندازید:</span>
                        <span class="badge bg-dark border border-light" id="poolCountBadge">0</span>
                    </div>
                    <div id="answersPoolList"></div>
                </div>

                <!-- 3. Two 2D Baskets: Green Basket & Red Basket (Bottom) -->
                <div class="row baskets-row">
                    <!-- Green Basket (Correct Answers) -->
                    <div class="col-6">
                        <div class="basket-zone basket-green" id="greenBasketZone" data-zone="green">
                            <div class="basket-header">
                                <span><i class="fas fa-check-circle me-1"></i> سبد سبز (صحیح)</span>
                                <span class="basket-count-badge" id="greenCountBadge">0</span>
                            </div>
                            <div class="basket-body" id="greenBasketList">
                                <div class="basket-empty-hint" id="greenEmptyHint">
                                    <!-- 2D SVG Green Basket Illustration -->
                                    <svg width="54" height="46" viewBox="0 0 64 54" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-1">
                                        <path d="M8 20H56L50 50H14L8 20Z" fill="#10B981" stroke="#064E3B" stroke-width="4" stroke-linejoin="round"/>
                                        <rect x="4" y="14" width="56" height="8" rx="4" fill="#34D399" stroke="#064E3B" stroke-width="4"/>
                                        <path d="M23 33L29 39L41 27" stroke="#FFFFFF" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <div style="font-size: 0.78rem; font-weight: 800;">پاسخ‌های <strong>درست</strong> اینجا</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Red Basket (Incorrect Answers) -->
                    <div class="col-6">
                        <div class="basket-zone basket-red" id="redBasketZone" data-zone="red">
                            <div class="basket-header">
                                <span><i class="fas fa-times-circle me-1"></i> سبد قرمز (غلط)</span>
                                <span class="basket-count-badge" id="redCountBadge">0</span>
                            </div>
                            <div class="basket-body" id="redBasketList">
                                <div class="basket-empty-hint" id="redEmptyHint">
                                    <!-- 2D SVG Red Basket Illustration -->
                                    <svg width="54" height="46" viewBox="0 0 64 54" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-1">
                                        <path d="M8 20H56L50 50H14L8 20Z" fill="#EF4444" stroke="#7F1D1D" stroke-width="4" stroke-linejoin="round"/>
                                        <rect x="4" y="14" width="56" height="8" rx="4" fill="#F87171" stroke="#7F1D1D" stroke-width="4"/>
                                        <path d="M25 28L39 42M39 28L25 42" stroke="#FFFFFF" stroke-width="4.5" stroke-linecap="round"/>
                                    </svg>
                                    <div style="font-size: 0.78rem; font-weight: 800;">پاسخ‌های <strong>غلط</strong> اینجا</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. 2D Arcade Confirm Button ("باشه") -->
                <div class="solve-action-bar" id="solveActionBar">
                    <button type="button" class="btn-solve-game" id="btnSolveQuestion" onclick="handleSolveQuestion()">
                        <i class="fas fa-gavel"></i>
                        <span>باشه (ثبت و بررسی)</span>
                    </button>
                    <div class="fw-bold mt-2" id="unplacedHintText" style="font-size: 0.8rem; color: var(--text-main);"></div>
                </div>

                <!-- 5. Detailed Evaluation, Persian Translation & Explanation, Repeat / Next -->
                <div class="feedback-panel" id="feedbackPanel">
                    <div id="overallResultBanner" class="overall-result-banner"></div>

                    <h6 style="font-weight: 900;" class="mb-2">
                        <i class="fas fa-search me-1 text-primary"></i> نتیجه تفکیک کارت‌های شما:
                    </h6>
                    <div id="answersEvaluationList"></div>

                    <!-- Question Translation & Explanation -->
                    <div class="question-knowledge-card" id="questionKnowledgeCard">
                        <div class="knowledge-block-trans" id="qTranslationBox">
                            <div class="fw-bold mb-1 text-primary" style="font-size: 0.85rem;">
                                <i class="fas fa-language me-1"></i> ترجمه فارسی سوال:
                            </div>
                            <div id="qTranslationContent" style="font-size: 0.92rem; line-height: 1.65;"></div>
                        </div>

                        <div class="knowledge-block-info" id="qExplanationBox">
                            <div class="fw-bold mb-1" style="font-size: 0.85rem; color: #7c3aed;">
                                <i class="fas fa-lightbulb me-1"></i> توضیح و نکته آموزشی سوال:
                            </div>
                            <div id="qExplanationContent" style="font-size: 0.9rem; line-height: 1.7;"></div>
                        </div>
                    </div>

                    <!-- Action Buttons: Repeat or Next Question -->
                    <div class="post-solve-actions">
                        <button type="button" class="btn-repeat-q" onclick="repeatCurrentQuestion()">
                            <i class="fas fa-redo"></i>
                            <span>تکرار این سوال</span>
                        </button>
                        <button type="button" class="btn-next-q" id="btnNextQuestion" onclick="goToNextQuestion()">
                            <span id="btnNextQuestionLabel">سوال بعدی</span>
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Round Summary Screen (shown after finishing all 30 questions) -->
            <div class="quest-board text-center py-5 mt-3" id="roundSummaryContainer" style="display: none;">
                <div class="quest-board-inner py-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 86px; height: 86px; background: #fde047; border: 4px solid #1e1b4b; box-shadow: 0 5px 0 #1e1b4b; color: #1e1b4b; font-size: 2.5rem;">
                            <i class="fas fa-trophy"></i>
                        </span>
                    </div>
                    <h4 style="font-weight: 900;" class="mb-2">پایان مرحله!</h4>
                    <p class="mb-4" style="font-weight: 700;">
                        در این دور، <strong id="summaryCorrectCount" class="text-success">0</strong> سوال را صحیح و
                        <strong id="summaryWrongCount" class="text-danger">0</strong> سوال را اشتباه تفکیک کردید.
                    </p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn-2d btn-2d-yellow px-4 py-2" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i>
                            <span>شروع مجدد این حالت</span>
                        </button>
                        <a href="mistakes-game.php" class="btn-2d btn-2d-cyan px-4 py-2">
                            <i class="fas fa-gamepad"></i>
                            <span>انتخاب حالت دیگر بازی</span>
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <button type="button" class="btn btn-dark btn-sm rounded-circle position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="بستن" style="width: 34px; height: 34px; z-index: 10;">
                        <i class="fas fa-times"></i>
                    </button>
                    <img id="zoomedModalImg" src="" alt="" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!$isLobby && !empty($gameQuestions)): ?>
    <script>
        const GAME_QUESTIONS = <?= json_encode($gameQuestions, JSON_UNESCAPED_UNICODE) ?>;
        const CSRF_TOKEN = <?= json_encode($csrf_token) ?>;

        let currentIndex = 0;
        let currentSolved = false;
        let currentShuffledAnswers = [];
        let answerPlacements = {};
        let sessionCorrectCount = 0;
        let sessionWrongCount = 0;
        let totalScore = 0;

        function shuffleArray(arr) {
            const copy = [...arr];
            for (let i = copy.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [copy[i], copy[j]] = [copy[j], copy[i]];
            }
            return copy;
        }

        function getStatusMeta(correct, incorrect) {
            correct = parseInt(correct || 0, 10);
            incorrect = parseInt(incorrect || 0, 10);

            if (correct === 0 && incorrect === 0) {
                return { cls: 'q-status-gray', icon: 'fa-circle', text: 'جدید (خاکستری)' };
            }
            if (correct >= 2 && incorrect === 0) {
                return { cls: 'q-status-green', icon: 'fa-check-circle', text: 'کاملاً آماده (سبز)' };
            }
            if (correct === 1 && incorrect === 0) {
                return { cls: 'q-status-blue', icon: 'fa-check', text: '۱ بار صحیح (آبی)' };
            }
            if (incorrect >= 2 && correct === 0) {
                return { cls: 'q-status-red', icon: 'fa-times-circle', text: '۲ بار اشتباه (قرمز)' };
            }
            if (incorrect === 1 && correct === 0) {
                return { cls: 'q-status-red', icon: 'fa-exclamation-circle', text: '۱ بار اشتباه (قرمز)' };
            }
            return { cls: 'q-status-yellow', icon: 'fa-adjust', text: '۱ بار درست و ۱ بار غلط (زرد)' };
        }

        function buildAnswerDisplayHTML(answer, index) {
            const isImage = parseInt(answer.is_image || 0, 10) === 1;
            if (isImage) {
                let imageName = '';
                let isEtcImage = false;
                if (answer.original_content) {
                    const etcMatch = answer.original_content.match(/%IMG_ETC%\/([^"']+)/);
                    if (etcMatch) {
                        imageName = etcMatch[1];
                        isEtcImage = true;
                    } else {
                        const answerMatch = answer.original_content.match(/%IMG_ANSWER%\/([^"']+)/);
                        if (answerMatch) {
                            imageName = answerMatch[1];
                            isEtcImage = false;
                        }
                    }
                }
                if (!imageName && answer.text) {
                    const numberMatch = answer.text.match(/\d+/);
                    if (numberMatch) {
                        imageName = `answer_${answer.id}.png`;
                    }
                }
                if (!imageName) {
                    imageName = `answer_${answer.id}.png`;
                }

                let imageUrl = '';
                let maxWidth = 90;
                if (isEtcImage || imageName.toLowerCase().startsWith('div')) {
                    imageUrl = `https://t24.theorie24.de/2025-01-v400/data/img/etc/de/${imageName}`;
                    maxWidth = 220;
                } else {
                    imageUrl = `https://t24.theorie24.de/2025-01-v400/data/img/answers/${imageName}`;
                    maxWidth = 90;
                }
                return `<img src="${imageUrl}" alt="Option ${index + 1}" class="img-fluid rounded" style="max-width:${maxWidth}px; height:auto;">`;
            }

            const div = document.createElement('div');
            div.textContent = answer.text || '';
            return div.innerHTML;
        }

        function renderQuestion(index) {
            const q = GAME_QUESTIONS[index];
            if (!q) return;

            currentSolved = false;
            currentShuffledAnswers = shuffleArray(q.answers);
            answerPlacements = {};
            currentShuffledAnswers.forEach(a => {
                answerPlacements[a.id] = 'pool';
            });

            // Header progress
            document.getElementById('currentQIndexLabel').textContent = (index + 1);
            const pct = Math.round(((index + 1) / GAME_QUESTIONS.length) * 100);
            document.getElementById('gameProgressBar').style.width = pct + '%';

            // Question Meta
            document.getElementById('qCodeBadge').textContent = q.number || ('#' + q.id);
            document.getElementById('qPointsBadge').textContent = q.points || 0;
            updateQuestionStatusPill(q.stat_correct, q.stat_incorrect);

            // Question Text & Pretext
            document.getElementById('qMainText').textContent = q.text || '';
            const preEl = document.getElementById('qPreText');
            if (q.asw_pretext && q.asw_pretext.trim()) {
                preEl.textContent = q.asw_pretext;
                preEl.style.display = 'block';
            } else {
                preEl.textContent = '';
                preEl.style.display = 'none';
            }

            // Question Media (Image or Video)
            const mediaBox = document.getElementById('qMediaBox');
            const fileName = (q.picture || '').trim();
            const ext = fileName ? fileName.split('.').pop().toLowerCase() : '';
            const fileBase = fileName ? fileName.replace(/\.[^/.]+$/, '') : '';

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                const imgUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + fileName;
                mediaBox.innerHTML = `<img src="${imgUrl}" alt="تصویر سوال" onclick="openZoomModal('${imgUrl}')">`;
                mediaBox.style.display = 'block';
            } else if (['mp4', 'm4v', 'webm'].includes(ext)) {
                const videoUrl = 'https://www.theorie24.de/live_images/_current_ws_2024-10-01_2025-04-01/videos/' + fileName;
                const posterUrl = 'https://t24.theorie24.de/2025-01-v400/data/img/images/' + fileBase + '_anfang.jpg';
                mediaBox.innerHTML = `
                    <video controls playsinline preload="metadata" poster="${posterUrl}">
                        <source src="${videoUrl}" type="video/mp4">
                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                    </video>
                `;
                mediaBox.style.display = 'block';
            } else {
                mediaBox.innerHTML = '';
                mediaBox.style.display = 'none';
            }

            document.getElementById('feedbackPanel').style.display = 'none';
            document.getElementById('solveActionBar').style.display = 'block';

            renderZones();
        }

        function updateQuestionStatusPill(correct, incorrect) {
            const pill = document.getElementById('qStatusPill');
            const meta = getStatusMeta(correct, incorrect);
            pill.className = 'q-status-pill ' + meta.cls;
            pill.innerHTML = `<i class="fas ${meta.icon}"></i> <span>${meta.text}</span>`;
        }

        function renderZones() {
            const poolList = document.getElementById('answersPoolList');
            const greenList = document.getElementById('greenBasketList');
            const redList = document.getElementById('redBasketList');

            poolList.innerHTML = '';
            greenList.querySelectorAll('.draggable-option').forEach(el => el.remove());
            redList.querySelectorAll('.draggable-option').forEach(el => el.remove());

            let poolCount = 0;
            let greenCount = 0;
            let redCount = 0;

            currentShuffledAnswers.forEach((ans, idx) => {
                const zone = answerPlacements[ans.id] || 'pool';
                const card = createOptionCardElement(ans, idx, zone);

                if (zone === 'green') {
                    greenCount++;
                    greenList.appendChild(card);
                } else if (zone === 'red') {
                    redCount++;
                    redList.appendChild(card);
                } else {
                    poolCount++;
                    poolList.appendChild(card);
                }
            });

            document.getElementById('poolCountBadge').textContent = poolCount;
            document.getElementById('greenCountBadge').textContent = greenCount;
            document.getElementById('redCountBadge').textContent = redCount;

            document.getElementById('greenEmptyHint').style.display = greenCount === 0 ? 'block' : 'none';
            document.getElementById('redEmptyHint').style.display = redCount === 0 ? 'block' : 'none';

            const hintEl = document.getElementById('unplacedHintText');
            if (poolCount > 0) {
                hintEl.textContent = `${poolCount} کارت هنوز بیرون سبدهاست.`;
            } else {
                hintEl.textContent = 'همه کارت‌ها داخل سبدها قرار گرفتند! حالا روی دکمه «باشه» بزنید.';
            }
        }

        function createOptionCardElement(answer, index, currentZone) {
            const el = document.createElement('div');
            el.className = 'draggable-option';
            el.setAttribute('data-answer-id', answer.id);

            const letters = ['A', 'B', 'C', 'D'];
            const letter = letters[index] || (index + 1);

            let quickBtns = '';
            if (!currentSolved) {
                if (currentZone === 'pool') {
                    quickBtns = `
                        <button type="button" class="btn-quick-basket btn-quick-green" title="انتقال به سبد سبز (صحیح)" onclick="moveOptionToZone(${answer.id}, 'green', event)">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn-quick-basket btn-quick-red" title="انتقال به سبد قرمز (غلط)" onclick="moveOptionToZone(${answer.id}, 'red', event)">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                } else if (currentZone === 'green') {
                    quickBtns = `
                        <button type="button" class="btn-quick-basket btn-quick-red" title="انتقال به سبد قرمز" onclick="moveOptionToZone(${answer.id}, 'red', event)">
                            <i class="fas fa-times"></i>
                        </button>
                        <button type="button" class="btn-quick-basket btn-quick-pool" title="بازگشت به لیست" onclick="moveOptionToZone(${answer.id}, 'pool', event)">
                            <i class="fas fa-undo"></i>
                        </button>
                    `;
                } else if (currentZone === 'red') {
                    quickBtns = `
                        <button type="button" class="btn-quick-basket btn-quick-green" title="انتقال به سبد سبز" onclick="moveOptionToZone(${answer.id}, 'green', event)">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn-quick-basket btn-quick-pool" title="بازگشت به لیست" onclick="moveOptionToZone(${answer.id}, 'pool', event)">
                            <i class="fas fa-undo"></i>
                        </button>
                    `;
                }
            }

            el.innerHTML = `
                <span class="tile-letter-badge">${letter}</span>
                <div class="option-text-ltr">${buildAnswerDisplayHTML(answer, index)}</div>
                <div class="option-quick-actions">${quickBtns}</div>
            `;

            if (!currentSolved) {
                attachPointerDrag(el, answer);
            } else {
                el.style.cursor = 'default';
            }

            return el;
        }

        function moveOptionToZone(answerId, targetZone, ev) {
            if (ev) {
                ev.stopPropagation();
                ev.preventDefault();
            }
            if (currentSolved) return;
            answerPlacements[answerId] = targetZone;
            renderZones();
        }

        // Unified Pointer/Touch/Mouse Drag-and-Drop System
        let activeDrag = null;

        function attachPointerDrag(cardEl, answer) {
            cardEl.addEventListener('pointerdown', function(e) {
                if (currentSolved) return;
                if (e.target.closest('.btn-quick-basket')) return;
                if (e.button !== undefined && e.button !== 0) return;

                e.preventDefault();
                cardEl.setPointerCapture(e.pointerId);

                const rect = cardEl.getBoundingClientRect();
                const ghost = document.createElement('div');
                ghost.className = 'drag-ghost';
                ghost.style.width = Math.min(rect.width, 310) + 'px';
                ghost.innerHTML = cardEl.querySelector('.option-text-ltr').innerHTML;
                document.body.appendChild(ghost);

                activeDrag = {
                    pointerId: e.pointerId,
                    answerId: answer.id,
                    sourceEl: cardEl,
                    ghostEl: ghost,
                    offsetX: Math.min(e.clientX - rect.left, 140),
                    offsetY: e.clientY - rect.top
                };

                cardEl.classList.add('dragging-source');
                updateGhostPosition(e.clientX, e.clientY);
            });

            cardEl.addEventListener('pointermove', function(e) {
                if (!activeDrag || activeDrag.pointerId !== e.pointerId) return;
                e.preventDefault();
                updateGhostPosition(e.clientX, e.clientY);
                highlightHoveredZone(e.clientX, e.clientY);
            });

            cardEl.addEventListener('pointerup', function(e) {
                if (!activeDrag || activeDrag.pointerId !== e.pointerId) return;
                finishPointerDrag(e.clientX, e.clientY);
            });

            cardEl.addEventListener('pointercancel', function(e) {
                if (!activeDrag || activeDrag.pointerId !== e.pointerId) return;
                cleanupPointerDrag();
            });
        }

        function updateGhostPosition(clientX, clientY) {
            if (!activeDrag || !activeDrag.ghostEl) return;
            activeDrag.ghostEl.style.left = (clientX - activeDrag.offsetX) + 'px';
            activeDrag.ghostEl.style.top = (clientY - activeDrag.offsetY) + 'px';
        }

        function getDropZoneAtPoint(clientX, clientY) {
            const greenEl = document.getElementById('greenBasketZone');
            const redEl = document.getElementById('redBasketZone');
            const poolEl = document.getElementById('answersPoolZone');

            const zones = [
                { name: 'green', el: greenEl },
                { name: 'red', el: redEl },
                { name: 'pool', el: poolEl }
            ];

            for (const z of zones) {
                if (!z.el) continue;
                const r = z.el.getBoundingClientRect();
                if (clientX >= r.left && clientX <= r.right && clientY >= r.top && clientY <= r.bottom) {
                    return z.name;
                }
            }
            return null;
        }

        function highlightHoveredZone(clientX, clientY) {
            const hovered = getDropZoneAtPoint(clientX, clientY);
            document.getElementById('greenBasketZone').classList.toggle('drag-over-active', hovered === 'green');
            document.getElementById('redBasketZone').classList.toggle('drag-over-active', hovered === 'red');
            document.getElementById('answersPoolZone').classList.toggle('drag-over-pool', hovered === 'pool');
        }

        function finishPointerDrag(clientX, clientY) {
            const targetZone = getDropZoneAtPoint(clientX, clientY);
            const ansId = activeDrag ? activeDrag.answerId : null;
            cleanupPointerDrag();

            if (ansId && targetZone) {
                answerPlacements[ansId] = targetZone;
                renderZones();
            }
        }

        function cleanupPointerDrag() {
            if (activeDrag) {
                if (activeDrag.ghostEl && activeDrag.ghostEl.parentNode) {
                    activeDrag.ghostEl.parentNode.removeChild(activeDrag.ghostEl);
                }
                if (activeDrag.sourceEl) {
                    activeDrag.sourceEl.classList.remove('dragging-source');
                }
                activeDrag = null;
            }
            document.getElementById('greenBasketZone').classList.remove('drag-over-active');
            document.getElementById('redBasketZone').classList.remove('drag-over-active');
            document.getElementById('answersPoolZone').classList.remove('drag-over-pool');
        }

        // Solve Question ("باشه")
        function handleSolveQuestion() {
            if (currentSolved) return;

            const q = GAME_QUESTIONS[currentIndex];
            if (!q) return;

            const unplaced = currentShuffledAnswers.filter(a => !answerPlacements[a.id] || answerPlacements[a.id] === 'pool');
            if (unplaced.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'کارت‌ها کامل در سبد نیستند!',
                    text: 'لطفاً تمام کارت‌های پاسخ را داخل سبد سبز (صحیح) یا سبد قرمز (غلط) بیندازید و سپس روی «باشه» بزنید.',
                    confirmButtonText: 'باشه!',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            currentSolved = true;

            let allCorrect = true;
            const evalItemsHTML = [];

            currentShuffledAnswers.forEach((ans, idx) => {
                const isOptionTruthy = parseInt(ans.asw_corr || 0, 10) === 1;
                const userBasket = answerPlacements[ans.id];
                const placedCorrectly = (isOptionTruthy && userBasket === 'green') || (!isOptionTruthy && userBasket === 'red');

                if (!placedCorrectly) {
                    allCorrect = false;
                }

                let reasonHTML = '';
                if (isOptionTruthy && userBasket === 'green') {
                    reasonHTML = `
                        <div class="eval-reason-badge eval-reason-ok">
                            <i class="fas fa-check-circle"></i>
                            <span>عالی! این گزینه <strong>صحیح</strong> است و به درستی در <strong>سبد سبز</strong> انداختید.</span>
                        </div>
                    `;
                } else if (!isOptionTruthy && userBasket === 'red') {
                    reasonHTML = `
                        <div class="eval-reason-badge eval-reason-ok">
                            <i class="fas fa-check-circle"></i>
                            <span>عالی! این گزینه <strong>غلط</strong> است و به درستی در <strong>سبد قرمز</strong> انداختید.</span>
                        </div>
                    `;
                } else if (isOptionTruthy && userBasket === 'red') {
                    reasonHTML = `
                        <div class="eval-reason-badge eval-reason-err">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>اشتباه! شما این پاسخ را به <strong>سبد قرمز</strong> انداختید، اما این گزینه <strong>صحیح</strong> است و باید در <strong>سبد سبز</strong> قرار می‌گرفت.</span>
                        </div>
                    `;
                } else if (!isOptionTruthy && userBasket === 'green') {
                    reasonHTML = `
                        <div class="eval-reason-badge eval-reason-err">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>اشتباه! شما این پاسخ را به <strong>سبد سبز</strong> انداختید، اما این گزینه <strong>غلط</strong> است و باید در <strong>سبد قرمز</strong> قرار می‌گرفت.</span>
                        </div>
                    `;
                }

                const faTrans = (ans.farsi_text && ans.farsi_text.trim())
                    ? `<div class="eval-fa-trans"><strong>ترجمه گزینه:</strong> ${ans.farsi_text}</div>`
                    : '';
                const faInfo = (ans.info && ans.info.trim())
                    ? `<div class="eval-fa-info"><strong>توضیح گزینه:</strong> ${ans.info}</div>`
                    : '';

                evalItemsHTML.push(`
                    <div class="answer-eval-card ${placedCorrectly ? 'eval-correct' : 'eval-wrong'}">
                        <div class="eval-option-text">${buildAnswerDisplayHTML(ans, idx)}</div>
                        ${reasonHTML}
                        ${faTrans}
                        ${faInfo}
                    </div>
                `);
            });

            if (allCorrect) {
                sessionCorrectCount++;
                totalScore += (parseInt(q.points || 4, 10) * 10);
                document.getElementById('hudScoreVal').textContent = totalScore;
            } else {
                sessionWrongCount++;
            }

            // Record answer in user_question_stats via update_answer_status.php
            recordQuestionStat(q.id, allCorrect);

            const bannerEl = document.getElementById('overallResultBanner');
            if (allCorrect) {
                bannerEl.className = 'overall-result-banner result-win';
                bannerEl.innerHTML = `
                    <i class="fas fa-trophy fa-2x"></i>
                    <div>
                        <div class="fs-6">آفرین! این مرحله را کاملاً درست حل کردید (+${parseInt(q.points || 4, 10) * 10} امتیاز)</div>
                        <div class="fw-normal" style="font-size: 0.82rem;">تمام کارت‌های صحیح و غلط را در سبد درست قرار دادید و در آمار شما ثبت شد.</div>
                    </div>
                `;
            } else {
                bannerEl.className = 'overall-result-banner result-lose';
                bannerEl.innerHTML = `
                    <i class="fas fa-times-circle fa-2x"></i>
                    <div>
                        <div class="fs-6">دقت کنید! در تفکیک کارت‌ها اشتباه داشتید.</div>
                        <div class="fw-normal" style="font-size: 0.82rem;">در پایین ببینید کدام کارت را در سبد اشتباه انداختید و ترجمه و توضیح فارسی را بخوانید.</div>
                    </div>
                `;
            }

            document.getElementById('answersEvaluationList').innerHTML = evalItemsHTML.join('');

            const qTransBox = document.getElementById('qTranslationBox');
            const qTransContent = document.getElementById('qTranslationContent');
            let transParts = [];
            if (q.farsi_text && q.farsi_text.trim()) {
                transParts.push(`<div>${q.farsi_text}</div>`);
            }
            if (q.asw_farsi && q.asw_farsi.trim()) {
                transParts.push(`<div class="mt-1 text-muted" style="font-size:0.86rem;">${q.asw_farsi}</div>`);
            }
            if (transParts.length > 0) {
                qTransContent.innerHTML = transParts.join('');
                qTransBox.style.display = 'block';
            } else {
                qTransContent.innerHTML = '<span class="text-muted">ترجمه فارسی برای این سوال ثبت نشده است.</span>';
                qTransBox.style.display = 'block';
            }

            const qExpBox = document.getElementById('qExplanationBox');
            const qExpContent = document.getElementById('qExplanationContent');
            if (q.info && q.info.trim()) {
                qExpContent.innerHTML = q.info;
                qExpBox.style.display = 'block';
            } else {
                qExpContent.innerHTML = '<span class="text-muted">توضیح فارسی برای این سوال ثبت نشده است.</span>';
                qExpBox.style.display = 'block';
            }

            const nextLabel = document.getElementById('btnNextQuestionLabel');
            if (currentIndex >= GAME_QUESTIONS.length - 1) {
                nextLabel.textContent = 'مشاهده نتیجه پایانی';
            } else {
                nextLabel.textContent = 'سوال بعدی';
            }

            renderZones();
            document.getElementById('solveActionBar').style.display = 'none';
            const feedbackPanel = document.getElementById('feedbackPanel');
            feedbackPanel.style.display = 'block';
            feedbackPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function recordQuestionStat(questionId, isCorrect) {
            const body = new URLSearchParams();
            body.append('csrf_token', CSRF_TOKEN);
            body.append('question_id', questionId);
            body.append('is_correct', isCorrect ? '1' : '0');

            fetch('../incloud/update_answer_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    const q = GAME_QUESTIONS[currentIndex];
                    if (q && q.id === questionId) {
                        q.stat_correct = data.correct;
                        q.stat_incorrect = data.incorrect;
                        updateQuestionStatusPill(data.correct, data.incorrect);
                    }
                }
            })
            .catch(err => {
                console.error('Error updating question status:', err);
            });
        }

        function repeatCurrentQuestion() {
            renderQuestion(currentIndex);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goToNextQuestion() {
            if (currentIndex < GAME_QUESTIONS.length - 1) {
                currentIndex++;
                renderQuestion(currentIndex);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                document.getElementById('gamePlayContainer').style.display = 'none';
                document.getElementById('summaryCorrectCount').textContent = sessionCorrectCount;
                document.getElementById('summaryWrongCount').textContent = sessionWrongCount;
                document.getElementById('roundSummaryContainer').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function openZoomModal(imgUrl) {
            const modalImg = document.getElementById('zoomedModalImg');
            modalImg.src = imgUrl;
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('imageZoomModal'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderQuestion(0);
        });
    </script>
    <?php endif; ?>
</body>
</html>
