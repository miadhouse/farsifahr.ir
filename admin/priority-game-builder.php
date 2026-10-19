<?php
// admin/priority-game-builder.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../incloud/functions.php';

if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

if (!is_super_admin() && !is_admin()) {
    die('دسترسی غیرمجاز: این بخش مخصوص ادمین است.');
}

$csrf_token = $_SESSION['csrf_token'] ?? generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طراحی بازی آموزش اولویت‌ها (حق تقدم) | پنل ادمین FarsiFahr</title>
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
        :root {
            --ink-outline: #1e1b4b;
            --panel-bg: #ffffff;
            --panel-sub: #f8fafc;
            --text-main: #0f172a;
            --text-muted-custom: #475569;
            --accent-blue: #2563eb;
            --accent-gold: #f59e0b;
        }

        html[data-bs-theme="dark"] {
            --ink-outline: #020617;
            --panel-bg: #1e293b;
            --panel-sub: #0f172a;
            --text-main: #f8fafc;
            --text-muted-custom: #94a3b8;
        }

        body {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: var(--text-main);
            font-family: 'Vazir', 'Tahoma', sans-serif;
            min-height: 100vh;
            padding-bottom: 48px;
        }

        /* Top Arcade Studio Header */
        .builder-topbar {
            position: sticky;
            top: 0;
            z-index: 1040;
            background: linear-gradient(180deg, #1e1b4b 0%, #0f172a 100%);
            border-bottom: 4px solid #020617;
            padding: 10px 18px;
            color: #ffffff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
        }

        .btn-2d {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-weight: 800;
            border: 3px solid #020617;
            border-radius: 12px;
            padding: 7px 15px;
            font-size: 0.84rem;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            user-select: none;
        }

        .btn-2d:active {
            transform: translateY(3px) !important;
        }

        .btn-2d-yellow {
            background: linear-gradient(180deg, #fde047 0%, #facc15 100%);
            color: #1e1b4b;
            box-shadow: 0 4px 0 #a16207;
        }

        .btn-2d-cyan {
            background: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%);
            color: #ffffff;
            box-shadow: 0 4px 0 #075985;
        }

        .btn-2d-green {
            background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
            color: #052e16;
            box-shadow: 0 4px 0 #14532d;
        }

        .btn-2d-purple {
            background: linear-gradient(180deg, #a855f7 0%, #7e22ce 100%);
            color: #ffffff;
            box-shadow: 0 4px 0 #4c1d95;
        }

        /* Mode Switcher Pills (Design Mode vs Live Simulator Mode) */
        .mode-switch-group {
            background: #090d16;
            padding: 4px;
            border-radius: 14px;
            border: 2px solid #334155;
            display: inline-flex;
            gap: 4px;
        }

        .mode-switch-btn {
            border: none;
            background: transparent;
            color: #94a3b8;
            font-weight: 800;
            font-size: 0.84rem;
            padding: 7px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.18s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .mode-switch-btn.active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 3px 10px rgba(37, 99, 235, 0.45);
        }

        .mode-switch-btn.active-sim {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.45);
        }

        /* Studio Panels */
        .studio-card {
            background: var(--panel-bg);
            border: 3.5px solid #020617;
            border-radius: 20px;
            box-shadow: 0 7px 0 #020617, 0 12px 24px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            margin-bottom: 16px;
        }

        .studio-card-header {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 11px 16px;
            font-weight: 900;
            font-size: 0.92rem;
            border-bottom: 3px solid #020617;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .studio-card-body {
            padding: 14px;
        }

        /* Intersection Type Selector Buttons */
        .intersection-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 8px;
        }

        .inter-type-btn {
            background: var(--panel-sub);
            border: 2.5px solid #cbd5e1;
            border-radius: 14px;
            padding: 8px 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
            color: var(--text-main);
            font-weight: 800;
            font-size: 0.78rem;
        }

        html[data-bs-theme="dark"] .inter-type-btn {
            border-color: #334155;
        }

        .inter-type-btn:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
        }

        .inter-type-btn.active {
            background: rgba(37, 99, 235, 0.12);
            border-color: #2563eb;
            box-shadow: 0 4px 0 #1d4ed8;
            color: #2563eb;
        }

        html[data-bs-theme="dark"] .inter-type-btn.active {
            color: #60a5fa;
        }

        /* Draggable Palette Items (Vehicles, Pedestrians, Signs, Traffic Lights) */
        .palette-section-title {
            font-size: 0.8rem;
            font-weight: 900;
            color: var(--text-muted-custom);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .palette-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }

        .palette-item {
            background: var(--panel-sub);
            border: 2.5px solid #475569;
            border-radius: 13px;
            padding: 8px 6px;
            text-align: center;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
            transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
            box-shadow: 0 3px 0 #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .palette-item:hover {
            transform: translateY(-2px);
            border-color: #38bdf8;
            box-shadow: 0 5px 0 #0284c7;
        }

        .palette-item:active {
            cursor: grabbing;
            transform: translateY(2px);
        }

        .palette-item-label {
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-main);
        }

        .palette-svg-preview {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* 3D Intersection Canvas Stage */
        .canvas-stage-wrapper {
            position: relative;
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            aspect-ratio: 6 / 5;
            min-height: 380px;
            border: 5px solid #020617;
            border-radius: 24px;
            box-shadow: 0 10px 0 #020617, 0 20px 40px rgba(0, 0, 0, 0.45);
            overflow: hidden;
            background: linear-gradient(180deg, #38bdf8 0%, #bae6fd 55%, #4ade80 100%);
            user-select: none;
        }

        /* Interactive Drop Slots on the 3D Intersection */
        .drop-slot-group {
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .drop-slot-group:hover,
        .drop-slot-group.drag-hover {
            background: rgba(34, 197, 94, 0.95) !important;
            color: #ffffff !important;
            border-color: #ffffff !important;
            border-style: solid !important;
            transform: translate(-50%, -50%) scale(1.16) !important;
            box-shadow: 0 0 14px rgba(34, 197, 94, 0.9);
            z-index: 20;
        }

        /* Priority Badge on Canvas */
        .actor-priority-badge {
            pointer-events: none;
        }

        /* Floating Drag Ghost for Builder */
        .builder-drag-ghost {
            position: fixed;
            z-index: 99999;
            pointer-events: none;
            background: rgba(15, 23, 42, 0.92);
            color: #fde047;
            border: 3px solid #fde047;
            border-radius: 14px;
            padding: 8px 12px;
            font-weight: 900;
            font-size: 0.8rem;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.45);
            display: flex;
            align-items: center;
            gap: 8px;
            transform: translate(-50%, -50%) scale(1.06);
        }

        /* Branch Configuration Tabs */
        .branch-tabs-nav {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }

        .branch-tab-btn {
            background: var(--panel-sub);
            border: 2.5px solid #64748b;
            border-radius: 12px;
            padding: 7px 6px;
            font-weight: 800;
            font-size: 0.78rem;
            color: var(--text-main);
            cursor: pointer;
            text-align: center;
            transition: all 0.15s ease;
        }

        .branch-tab-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #1e3a8a;
            box-shadow: 0 3px 0 #1e3a8a;
        }

        .branch-tab-btn.disabled-branch {
            opacity: 0.4;
            text-decoration: line-through;
        }

        /* Actor List Table in Builder */
        .actor-config-card {
            background: var(--panel-sub);
            border: 2.5px solid #64748b;
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 10px;
            box-shadow: 0 3px 0 rgba(15, 23, 42, 0.2);
        }

        /* Simulator Feedback Banner */
        .sim-result-box {
            border-radius: 16px;
            border: 3.5px solid #020617;
            padding: 14px 16px;
            margin-top: 12px;
            display: none;
        }

        .sim-result-win {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #064e3b;
        }

        .sim-result-lose {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #7f1d1d;
        }

        .sim-step-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1e293b;
            color: #fde047;
            border: 2px solid #020617;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        @keyframes blinkTurnSignal {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.15; }
        }

        .turn-signal-blink {
            animation: blinkTurnSignal 0.7s infinite;
        }
    </style>
</head>
<body>

    <!-- Top Admin Builder HUD -->
    <header class="builder-topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="index.php" class="btn-2d btn-2d-yellow">
                        <i class="bx bx-home-alt"></i>
                        <span>داشبورد</span>
                    </a>
                    <a href="priority-game.php" class="btn-2d btn-2d-cyan">
                        <i class="fas fa-gamepad"></i>
                        <span>اجرای بازی حق تقدم‌ها (کاربر)</span>
                    </a>
                    <span class="fw-black fs-6 ms-2 d-none d-md-inline" style="font-weight: 900;">
                        <i class="fas fa-traffic-light text-warning me-1"></i>
                        استودیو طراحی بازی اولویت‌ها (حق تقدم) — ویژه ادمین
                    </span>
                </div>

                <!-- Switch between Admin Editor and Interactive Player Simulator -->
                <div class="mode-switch-group">
                    <button type="button" class="mode-switch-btn active" id="btnModeDesign" onclick="switchStudioMode('design')">
                        <i class="fas fa-drafting-compass"></i>
                        <span>حالت طراحی (ادمین)</span>
                    </button>
                    <button type="button" class="mode-switch-btn" id="btnModePreview" onclick="switchStudioMode('preview')">
                        <i class="fas fa-play-circle"></i>
                        <span>تست تعاملی و حرکت خودروها</span>
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-2d btn-2d-purple" onclick="resetToNewScenario()">
                        <i class="fas fa-plus"></i>
                        <span>سناریو جدید</span>
                    </button>
                    <button type="button" class="btn-2d btn-2d-green" onclick="saveCurrentScenario()">
                        <i class="fas fa-save"></i>
                        <span>ذخیره سوال</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid mt-3">
        <div class="row g-3">

            <!-- =========================================================
                 RIGHT COLUMN (IN RTL): PALETTE, INTERSECTION TYPE & BRANCH SETTINGS
                 ========================================================= -->
            <div class="col-12 col-xl-4" id="designLeftSidebar">

                <!-- 1. Intersection Geometry & Quick Templates -->
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span><i class="fas fa-road text-warning me-2"></i> ۱. انتخاب نوع تقاطع و قالب آماده</span>
                        <span class="badge bg-primary" id="editingScenarioBadge">سناریو جدید</span>
                    </div>
                    <div class="studio-card-body">
                        <div class="intersection-type-grid mb-3">
                            <button type="button" class="inter-type-btn active" data-inter="cross4" onclick="setIntersectionType('cross4')">
                                <i class="fas fa-plus fa-lg d-block mb-1"></i>
                                چهارراه کامل
                            </button>
                            <button type="button" class="inter-type-btn" data-inter="t_no_top" onclick="setIntersectionType('t_no_top')">
                                <i class="fas fa-grip-lines fa-lg d-block mb-1"></i>
                                سه‌راهی T (بدون بالا)
                            </button>
                            <button type="button" class="inter-type-btn" data-inter="t_no_bottom" onclick="setIntersectionType('t_no_bottom')">
                                <i class="fas fa-grip-lines fa-lg d-block mb-1"></i>
                                سه‌راهی (بدون پایین)
                            </button>
                            <button type="button" class="inter-type-btn" data-inter="t_no_left" onclick="setIntersectionType('t_no_left')">
                                <i class="fas fa-grip-lines-vertical fa-lg d-block mb-1"></i>
                                سه‌راهی (بدون چپ)
                            </button>
                            <button type="button" class="inter-type-btn" data-inter="t_no_right" onclick="setIntersectionType('t_no_right')">
                                <i class="fas fa-grip-lines-vertical fa-lg d-block mb-1"></i>
                                سه‌راهی (بدون راست)
                            </button>
                        </div>

                        <!-- Sidewalk & Road Markings Toggles -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 border-top">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="toggleHasSidewalks" onchange="toggleIntersectionOption('hasSidewalks', this.checked)">
                                <label class="form-check-label small fw-bold" for="toggleHasSidewalks">دارای پیاده‌رو</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="toggleHasRoadMarkings" onchange="toggleIntersectionOption('hasRoadMarkings', this.checked)">
                                <label class="form-check-label small fw-bold" for="toggleHasRoadMarkings">دارای خط‌کشی خیابان</label>
                            </div>
                        </div>

                        <!-- Quick Sample Presets -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 pt-2 border-top">
                            <span class="small fw-bold text-muted">سوالات طراحی‌شده:</span>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" style="font-size: 0.74rem;" onclick="loadPresetScenario('preset1')">
                                    سوال ۱: تابلو ۲۰۵ (عبور همزمان)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning fw-bold" style="font-size: 0.74rem;" onclick="loadPresetScenario('preset2')">
                                    سوال ۲: تابلو ۳۰۱ (حق تقدم یکباره)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Drag & Drop Palette (Vehicles, Pedestrians, Signs, Traffic Lights) -->
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span><i class="fas fa-hand-pointer text-info me-2"></i> ۲. جعبه ابزار (بکشید و در ضلع دلخواه رها کنید)</span>
                    </div>
                    <div class="studio-card-body">
                        <div class="palette-section-title">
                            <i class="fas fa-car text-primary"></i>
                            <span>خودروها و وسایل نقلیه (رها کردن روی خط خیابان):</span>
                        </div>
                        <div class="palette-grid" id="vehiclesPaletteGrid"></div>

                        <div class="palette-section-title">
                            <i class="fas fa-walking text-success"></i>
                            <span>پیاده‌رو و عابران (رها کردن روی گذرگاه عابر هر ضلع):</span>
                        </div>
                        <div class="palette-grid" id="pedestriansPaletteGrid"></div>

                        <div class="palette-section-title">
                            <i class="fas fa-traffic-light text-danger"></i>
                            <span>تابلوها و چراغ راهنما (رها کردن روی پایه تابلو هر ضلع):</span>
                        </div>
                        <div class="palette-grid" id="signsPaletteGrid"></div>
                    </div>
                </div>

                <!-- 3. Per-Branch Detailed Inspector (Signs, Stacked Signs, Traffic Light, Crosswalk) -->
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span><i class="fas fa-sliders-h text-warning me-2"></i> ۳. تنظیمات دقیق هر ضلع (تابلو ترکیبی، چراغ، پیاده‌رو)</span>
                    </div>
                    <div class="studio-card-body">
                        <div class="branch-tabs-nav" id="branchTabsNav">
                            <button type="button" class="branch-tab-btn active" data-branch="bottom" onclick="selectActiveBranch('bottom')">ضلع پایین (جنوب)</button>
                            <button type="button" class="branch-tab-btn" data-branch="right" onclick="selectActiveBranch('right')">ضلع راست (شرق)</button>
                            <button type="button" class="branch-tab-btn" data-branch="top" onclick="selectActiveBranch('top')">ضلع بالا (شمال)</button>
                            <button type="button" class="branch-tab-btn" data-branch="left" onclick="selectActiveBranch('left')">ضلع چپ (غرب)</button>
                        </div>

                        <div id="branchInspectorContent"></div>
                    </div>
                </div>

            </div>

            <!-- =========================================================
                 CENTER COLUMN: 3D INTERSECTION CANVAS & SIMULATOR CONTROLS
                 ========================================================= -->
            <div class="col-12 col-xl-5">
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span id="canvasHeaderTitle">
                            <i class="fas fa-cube text-success me-2"></i>
                            محیط ۳ بعدی تقاطع سه‌رخ (اجزا را روی نقاط مشخص‌شده بکشید یا کلیک کنید)
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-warning fw-bold" onclick="resetVehiclesAnimation()" title="بازگرداندن خودروها به نقطه شروع">
                                <i class="fas fa-undo me-1"></i> بازنشانی حرکت
                            </button>
                        </div>
                    </div>
                    <div class="studio-card-body">

                        <!-- Question Banner Shown Above Map -->
                        <div class="p-3 mb-3 rounded-4 border border-2 border-dark" style="background: var(--panel-sub);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                <span class="badge bg-primary fw-bold" id="liveScenarioTitleBadge">سوال اولویت عبور</span>
                                <span class="small fw-bold text-muted" id="liveInstructionHint">
                                    در حالت تست: به ترتیب حق تقدم روی کاربران جاده کلیک کنید تا حرکت کنند!
                                </span>
                            </div>
                            <div class="fw-black" style="font-weight: 900; font-size: 0.98rem;" id="liveQuestionTextDisplay">
                                به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟ به ترتیب روی آن‌ها کلیک کنید.
                            </div>
                            <div class="small text-muted mt-1" dir="ltr" id="liveQuestionDeDisplay" style="display: none;"></div>
                        </div>

                        <!-- 3D Interactive Intersection Stage (Three.js 3/4 Elevated View) -->
                        <div class="canvas-stage-wrapper" id="canvasStageWrapper"></div>

                        <!-- Simulator Action Bar (Click Sequence & Check Answer Button) -->
                        <div class="mt-3 p-3 rounded-4 border border-3 border-dark" style="background: var(--panel-sub);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="fw-bold small">
                                    <i class="fas fa-mouse-pointer text-primary me-1"></i>
                                    ترتیب کلیک‌شده برای حرکت:
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap" id="clickedOrderPills">
                                    <span class="text-muted small">هنوز روی هیچ خودرو یا عابری کلیک نکرده‌اید.</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
                                <button type="button" class="btn-2d btn-2d-green px-4 py-2" onclick="evaluateSimulationAnswer()">
                                    <i class="fas fa-check-double"></i>
                                    <span>نمایش پاسخ و بررسی اولویت‌ها</span>
                                </button>
                                <button type="button" class="btn-2d btn-2d-yellow px-3 py-2" onclick="resetVehiclesAnimation()">
                                    <i class="fas fa-redo"></i>
                                    <span>شروع مجدد حرکت</span>
                                </button>
                            </div>

                            <!-- Evaluation Result & Explanation Panel -->
                            <div class="sim-result-box" id="simResultBox"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- =========================================================
                 LEFT COLUMN (IN RTL): PRIORITIES, DIRECTIONS, QUESTION TEXT & SAVED LIST
                 ========================================================= -->
            <div class="col-12 col-xl-3" id="designRightSidebar">

                <!-- 4. Placed Road Users List: Direction & Priority Order (1, 2, 3...) -->
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span><i class="fas fa-sort-numeric-down text-warning me-2"></i> ۴. تعیین مسیر و ترتیب اولویت‌ها</span>
                        <span class="badge bg-info text-dark" id="actorsCountBadge">0 عضو</span>
                    </div>
                    <div class="studio-card-body">
                        <p class="small text-muted mb-2">
                            برای هر کاربر جاده، <strong>مسیر حرکت (فلش)</strong> و <strong>شماره اولویت حرکت (۱، ۲، ۳...)</strong> را مشخص کنید:
                        </p>
                        <div id="placedActorsListContainer"></div>
                    </div>
                </div>

                <!-- 5. Question Text & Educational Explanation -->
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span><i class="fas fa-edit text-info me-2"></i> ۵. متن سوال و توضیح پاسخ</span>
                    </div>
                    <div class="studio-card-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">عنوان سناریو / شماره سوال:</label>
                            <input type="text" class="form-control form-control-sm fw-bold" id="scenarioTitleInput" placeholder="مثلاً: تقاطع ۱ - حق تقدم عابر و جاده اصلی" oninput="syncQuestionTextLive()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">متن سوال (فارسی):</label>
                            <textarea class="form-control form-control-sm fw-bold" id="scenarioQuestionInput" rows="2" placeholder="به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟" oninput="syncQuestionTextLive()">به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟ به ترتیب روی آن‌ها کلیک کنید.</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">متن سوال آلمانی (اختیاری):</label>
                            <input type="text" class="form-control form-control-sm" dir="ltr" id="scenarioQuestionDeInput" placeholder="In welcher Reihenfolge dürfen die Verkehrsteilnehmer fahren?" oninput="syncQuestionTextLive()">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">توضیح آموزشی پاسخ (نمایش پس از حل سوال):</label>
                            <textarea class="form-control form-control-sm" id="scenarioExplanationInput" rows="3" placeholder="توضیح دهید چرا این ترتیب حق تقدم صحیح است..."></textarea>
                        </div>
                        <button type="button" class="btn-2d btn-2d-green w-100 py-2" onclick="saveCurrentScenario()">
                            <i class="fas fa-save"></i>
                            <span>ذخیره سناریو در دیتابیس</span>
                        </button>
                    </div>
                </div>

                <!-- 6. Saved Scenarios List -->
                <div class="studio-card">
                    <div class="studio-card-header">
                        <span><i class="fas fa-database text-success me-2"></i> ۶. سوالات اولویت ساخته‌شده</span>
                        <button type="button" class="btn btn-sm btn-outline-light py-0 px-2" onclick="fetchSavedScenarios()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="studio-card-body" id="savedScenariosList" style="max-height: 320px; overflow-y: auto;">
                        <div class="text-center text-muted small py-3">در حال بارگذاری...</div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="assets/js/priority-3d-engine.js?v=1.9"></script>

    <script>
        const CSRF_TOKEN = <?= json_encode($csrf_token) ?>;

        // =========================================================================
        // 1. CATALOG DEFINITIONS: VEHICLES, PEDESTRIANS, SIGNS & TRAFFIC LIGHTS
        // =========================================================================
        const BRANCH_LABELS = {
            top: 'ضلع بالا (شمال)',
            right: 'ضلع راست (شرق)',
            bottom: 'ضلع پایین - من (جنوب)',
            left: 'ضلع چپ (غرب)'
        };

        const INTERSECTION_BRANCHES = {
            cross4: ['top', 'right', 'bottom', 'left'],
            t_no_top: ['right', 'bottom', 'left'],
            t_no_bottom: ['top', 'right', 'left'],
            t_no_left: ['top', 'right', 'bottom'],
            t_no_right: ['top', 'bottom', 'left']
        };

        const VEHICLE_CATALOG = {
            car_yellow: { id: 'car_yellow', category: 'vehicle', name: 'خودرو زرد', color: '#facc15', stroke: '#854d0e', roof: '#fef08a' },
            car_green:  { id: 'car_green',  category: 'vehicle', name: 'خودرو سبز', color: '#22c55e', stroke: '#14532d', roof: '#86efac' },
            car_blue:   { id: 'car_blue',   category: 'vehicle', name: 'خودرو آبی', color: '#3b82f6', stroke: '#1e3a8a', roof: '#93c5fd' },
            car_red:    { id: 'car_red',    category: 'vehicle', name: 'خودرو قرمز', color: '#ef4444', stroke: '#7f1d1d', roof: '#fca5a5' },
            bus:        { id: 'bus',        category: 'vehicle', name: 'اتوبوس زرد', color: '#facc15', stroke: '#854d0e', roof: '#fef08a' },
            tram:       { id: 'tram',       category: 'vehicle', name: 'تراموا', color: '#0891b2', stroke: '#164e63', roof: '#e0f2fe' },
            truck:      { id: 'truck',      category: 'vehicle', name: 'کامیون آبی', color: '#2563eb', stroke: '#1e3a8a', roof: '#93c5fd' },
            moto:       { id: 'moto',       category: 'vehicle', name: 'موتورسیکلت', color: '#ec4899', stroke: '#831843', roof: '#f9a8d4' },
            bike:       { id: 'bike',       category: 'vehicle', name: 'دوچرخه', color: '#06b6d4', stroke: '#164e63', roof: '#67e8f9' },
            scooter:    { id: 'scooter',    category: 'vehicle', name: 'اسکوتر برقی', color: '#a855f7', stroke: '#4c1d95', roof: '#d8b4fe' },
            police:     { id: 'police',     category: 'vehicle', name: 'خودرو پلیس/امداد', color: '#1e40af', stroke: '#0f172a', roof: '#ffffff' }
        };

        const PEDESTRIAN_CATALOG = {
            pedestrian: { id: 'pedestrian', category: 'pedestrian', name: 'عابر پیاده', color: '#10b981', stroke: '#064e3b' },
            ped_child:  { id: 'ped_child',  category: 'pedestrian', name: 'کودک در پیاده‌رو', color: '#f43f5e', stroke: '#881337' },
            ped_bike:   { id: 'ped_bike',   category: 'pedestrian', name: 'دوچرخه در گذرگاه', color: '#0ea5e9', stroke: '#0c4a6e' },
            ped_scooter:{ id: 'ped_scooter',category: 'pedestrian', name: 'اسکوتر در پیاده‌رو', color: '#8b5cf6', stroke: '#4c1d95' }
        };

        const PRIMARY_SIGNS = {
            none:               { id: 'none', name: 'بدون تابلو اول' },
            s306_priority_road: { id: 's306_priority_road', name: 'جاده اصلی - حق تقدم (306)' },
            s205_yield:         { id: 's205_yield', name: 'رعایت حق تقدم (205)' },
            s206_stop:          { id: 's206_stop', name: 'ایست کامل STOP (206)' },
            s301_priority_next: { id: 's301_priority_next', name: 'حق تقدم یکباره (301)' },
            s102_rvl:           { id: 's102_rvl', name: 'تقاطع راست به چپ (102)' },
            s307_end_priority:  { id: 's307_end_priority', name: 'پایان جاده اصلی (307)' },
            s350_crosswalk:     { id: 's350_crosswalk', name: 'گذرگاه عابر پیاده (350)' }
        };

        const SECONDARY_SIGNS = {
            none:                 { id: 'none', name: 'بدون تابلو مکمل (تک تابلو)' },
            abk_bottom_left:      { id: 'abk_bottom_left', name: 'مسیر جاده اصلی: به چپ (پایین به چپ)' },
            abk_bottom_right:     { id: 'abk_bottom_right', name: 'مسیر جاده اصلی: به راست (پایین به راست)' },
            abk_top_left:         { id: 'abk_top_left', name: 'مسیر جاده اصلی: بالا به چپ' },
            abk_top_right:        { id: 'abk_top_right', name: 'مسیر جاده اصلی: بالا به راست' },
            rad_cross:            { id: 'rad_cross', name: 'عبور دوچرخه از دو جهت' },
            s205_yield:           { id: 's205_yield', name: 'تابلو رعایت حق تقدم در زیر' }
        };

        const TRAFFIC_LIGHTS = {
            none:   { id: 'none', name: 'بدون چراغ راهنما' },
            green:  { id: 'green', name: 'چراغ سبز (حرکت)' },
            red:    { id: 'red', name: 'چراغ قرمز (ایست)' },
            yellow: { id: 'yellow', name: 'چراغ زرد (احتیاط)' },
            off:    { id: 'off', name: 'چراغ خاموش / غیرفعال' }
        };

        // =========================================================================
        // 2. SCENARIO STATE
        // =========================================================================
        let studioMode = 'design'; // 'design' | 'preview'
        let activeInspectorBranch = 'bottom';
        let currentScenarioId = 0;

        let scenarioState = {
            intersectionType: 'cross4',
            branches: {
                top:    { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null },
                right:  { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null },
                bottom: { hasCrosswalk: true,  trafficLight: 'none', primarySign: 's306_priority_road', secondarySign: 'abk_bottom_left', vehicle: null, pedestrian: null },
                left:   { hasCrosswalk: false, trafficLight: 'none', primarySign: 's205_yield', secondarySign: 'abk_bottom_left', vehicle: null, pedestrian: null }
            }
        };

        // Simulation state for clicking road users & animating along path
        let simClickedSequence = []; // array of actor keys e.g. 'vehicle_bottom', 'pedestrian_bottom'
        let simEvaluationResult = null; // null or { isAllCorrect, detailsByKey: { [key]: true|false } }
        let simAnimatedPositions = {}; // { [actorKey]: { x, y, angle, progress } }
        let activeAnimations = {};

        // =========================================================================
        // 3. TOP-DOWN 2D SVG GRAPHICS GENERATORS
        // =========================================================================
        function renderTopDownVehicleSVG(typeId, scale = 1) {
            const item = VEHICLE_CATALOG[typeId] || VEHICLE_CATALOG.car_yellow;
            if (typeId === 'tram') {
                return `
                    <g transform="scale(${scale})">
                        <line x1="-6" y1="-39" x2="-6" y2="39" stroke="#64748b" stroke-width="1.5" stroke-dasharray="3 2"/>
                        <line x1="6" y1="-39" x2="6" y2="39" stroke="#64748b" stroke-width="1.5" stroke-dasharray="3 2"/>
                        <rect x="-16" y="-37" width="32" height="74" rx="9" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-13" y="-33" width="26" height="9" rx="4" fill="#bae6fd" stroke="#0f172a" stroke-width="1.8"/>
                        <rect x="-12" y="-21" width="24" height="44" rx="4" fill="${item.roof}" stroke="#0f172a" stroke-width="1.5"/>
                        <polygon points="0,-13 8,-5 0,3 -8,-5" fill="none" stroke="#dc2626" stroke-width="2.2"/>
                        <line x1="-10" y1="-13" x2="10" y2="-13" stroke="#1e293b" stroke-width="2.5"/>
                        <circle cx="-9" cy="-35" r="2.5" fill="#fef08a"/>
                        <circle cx="9" cy="-35" r="2.5" fill="#fef08a"/>
                    </g>
                `;
            }
            if (typeId === 'bus') {
                return `
                    <g transform="scale(${scale})">
                        <rect x="-18" y="-36" width="36" height="72" rx="7" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-14" y="-31" width="28" height="11" rx="3" fill="#bae6fd" stroke="#0f172a" stroke-width="2"/>
                        <rect x="-13" y="-15" width="26" height="38" rx="4" fill="${item.roof}" stroke="#0f172a" stroke-width="1.5"/>
                        <circle cx="-11" cy="-35" r="3" fill="#fef08a"/>
                        <circle cx="11" cy="-35" r="3" fill="#fef08a"/>
                        <rect x="-14" y="31" width="8" height="3" rx="1" fill="#ef4444"/>
                        <rect x="6" y="31" width="8" height="3" rx="1" fill="#ef4444"/>
                    </g>
                `;
            }
            if (typeId === 'truck') {
                return `
                    <g transform="scale(${scale})">
                        <rect x="-18" y="-12" width="36" height="46" rx="4" fill="#e2e8f0" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-16" y="-34" width="32" height="22" rx="5" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-13" y="-30" width="26" height="8" rx="2" fill="#bae6fd" stroke="#0f172a" stroke-width="1.8"/>
                        <circle cx="-10" cy="-33" r="3" fill="#fef08a"/>
                        <circle cx="10" cy="-33" r="3" fill="#fef08a"/>
                    </g>
                `;
            }
            if (typeId === 'moto') {
                return `
                    <g transform="scale(${scale})">
                        <rect x="-4" y="-22" width="8" height="44" rx="4" fill="#1e293b"/>
                        <line x1="-13" y1="-12" x2="13" y2="-12" stroke="#0f172a" stroke-width="3.5" stroke-linecap="round"/>
                        <rect x="-7" y="-10" width="14" height="22" rx="6" fill="${item.color}" stroke="#0f172a" stroke-width="2.5"/>
                        <circle cx="0" cy="0" r="7" fill="#fde047" stroke="#0f172a" stroke-width="2.5"/>
                    </g>
                `;
            }
            if (typeId === 'bike' || typeId === 'scooter') {
                const isScooter = typeId === 'scooter';
                return `
                    <g transform="scale(${scale})">
                        <rect x="-3" y="-19" width="6" height="38" rx="3" fill="${item.color}" stroke="#0f172a" stroke-width="2"/>
                        <line x1="-11" y1="-10" x2="11" y2="-10" stroke="#0f172a" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="0" cy="${isScooter ? '2' : '0'}" r="6.5" fill="${item.roof}" stroke="#0f172a" stroke-width="2.5"/>
                    </g>
                `;
            }
            if (typeId === 'police') {
                return `
                    <g transform="scale(${scale})">
                        <rect x="-16" y="-28" width="32" height="56" rx="9" fill="#1e40af" stroke="#0f172a" stroke-width="3"/>
                        <rect x="-12" y="-20" width="24" height="10" rx="3" fill="#bae6fd" stroke="#0f172a" stroke-width="2"/>
                        <rect x="-12" y="-9" width="24" height="22" rx="4" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                        <rect x="-9" y="-2" width="8" height="5" rx="2" fill="#ef4444"/>
                        <rect x="1" y="-2" width="8" height="5" rx="2" fill="#3b82f6"/>
                        <circle cx="-10" cy="-27" r="3" fill="#fef08a"/>
                        <circle cx="10" cy="-27" r="3" fill="#fef08a"/>
                    </g>
                `;
            }
            // Standard 2D Top-Down Car (Green, Yellow, Blue, Red)
            return `
                <g transform="scale(${scale})">
                    <!-- Tires -->
                    <rect x="-18" y="-21" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="14" y="-21" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="-18" y="10" width="4" height="11" rx="2" fill="#0f172a"/>
                    <rect x="14" y="10" width="4" height="11" rx="2" fill="#0f172a"/>
                    <!-- Body -->
                    <rect x="-15" y="-27" width="30" height="54" rx="10" fill="${item.color}" stroke="#0f172a" stroke-width="3"/>
                    <!-- Windshield -->
                    <path d="M-11 -17 L11 -17 L9 -7 L-9 -7 Z" fill="#e0f2fe" stroke="#0f172a" stroke-width="2"/>
                    <!-- Roof -->
                    <rect x="-10" y="-7" width="20" height="18" rx="4" fill="${item.roof}" stroke="#0f172a" stroke-width="2"/>
                    <!-- Rear window -->
                    <path d="M-9 11 L9 11 L11 18 L-11 18 Z" fill="#bae6fd" stroke="#0f172a" stroke-width="1.8"/>
                    <!-- Headlights -->
                    <circle cx="-9" cy="-25" r="2.8" fill="#fef9c3"/>
                    <circle cx="9" cy="-25" r="2.8" fill="#fef9c3"/>
                    <!-- Taillights -->
                    <rect x="-11" y="23" width="6" height="2.8" rx="1" fill="#ef4444"/>
                    <rect x="5" y="23" width="6" height="2.8" rx="1" fill="#ef4444"/>
                </g>
            `;
        }

        function renderTopDownPedestrianSVG(typeId, scale = 1) {
            const item = PEDESTRIAN_CATALOG[typeId] || PEDESTRIAN_CATALOG.pedestrian;
            if (typeId === 'ped_bike' || typeId === 'ped_scooter') {
                return `
                    <g transform="scale(${scale})">
                        <rect x="-3" y="-15" width="6" height="30" rx="3" fill="${item.color}" stroke="#0f172a" stroke-width="2"/>
                        <circle cx="0" cy="0" r="7" fill="#fde047" stroke="#0f172a" stroke-width="2.5"/>
                    </g>
                `;
            }
            const r = typeId === 'ped_child' ? 6.5 : 8;
            return `
                <g transform="scale(${scale})">
                    <!-- Shoulders -->
                    <rect x="-11" y="-6" width="22" height="12" rx="6" fill="${item.color}" stroke="#0f172a" stroke-width="2.5"/>
                    <!-- Head -->
                    <circle cx="0" cy="0" r="${r}" fill="#fde047" stroke="#0f172a" stroke-width="2.5"/>
                </g>
            `;
        }

        function renderSignIconSVG(signId) {
            switch (signId) {
                case 's306_priority_road':
                    return `
                        <g>
                            <rect x="-13" y="-13" width="26" height="26" rx="2" transform="rotate(45)" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <rect x="-8.5" y="-8.5" width="17" height="17" rx="1" transform="rotate(45)" fill="#facc15" stroke="#0f172a" stroke-width="1.5"/>
                        </g>
                    `;
                case 's307_end_priority':
                    return `
                        <g>
                            <rect x="-13" y="-13" width="26" height="26" rx="2" transform="rotate(45)" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <rect x="-8.5" y="-8.5" width="17" height="17" rx="1" transform="rotate(45)" fill="#facc15" stroke="#0f172a" stroke-width="1.5"/>
                            <line x1="-11" y1="11" x2="11" y2="-11" stroke="#0f172a" stroke-width="3.5"/>
                        </g>
                    `;
                case 's205_yield':
                    return `
                        <g>
                            <polygon points="-16,-12 16,-12 0,15" fill="#ffffff" stroke="#dc2626" stroke-width="4" stroke-linejoin="round"/>
                        </g>
                    `;
                case 's206_stop':
                    return `
                        <g>
                            <polygon points="-7,-16 7,-16 16,-7 16,7 7,16 -7,16 -16,7 -16,-7" fill="#dc2626" stroke="#ffffff" stroke-width="2.5"/>
                            <text x="0" y="4" text-anchor="middle" fill="#ffffff" font-size="8.5" font-weight="900" font-family="sans-serif">STOP</text>
                        </g>
                    `;
                case 's301_priority_next':
                case 's102_rvl':
                    return `
                        <g>
                            <polygon points="0,-15 16,13 -16,13" fill="#ffffff" stroke="#dc2626" stroke-width="4" stroke-linejoin="round"/>
                            ${signId === 's102_rvl'
                                ? `<path d="M-5,2 L5,10 M5,2 L-5,10" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round"/>`
                                : `<path d="M0,10 L0,-3 M-4,1 L0,-4 L4,1" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" fill="none"/>`}
                        </g>
                    `;
                case 's350_crosswalk':
                    return `
                        <g>
                            <rect x="-14" y="-14" width="28" height="28" rx="4" fill="#2563eb" stroke="#ffffff" stroke-width="2"/>
                            <polygon points="0,-10 11,9 -11,9" fill="#ffffff"/>
                            <line x1="-7" y1="6" x2="7" y2="6" stroke="#0f172a" stroke-width="2" stroke-dasharray="2 2"/>
                        </g>
                    `;
                case 'abk_bottom_left':
                    return `
                        <g>
                            <rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <path d="M0,9 L0,0 L-10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="0" y1="-9" x2="0" y2="-2" stroke="#64748b" stroke-width="2"/>
                            <line x1="2" y1="0" x2="10" y2="0" stroke="#64748b" stroke-width="2"/>
                        </g>
                    `;
                case 'abk_bottom_right':
                    return `
                        <g>
                            <rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <path d="M0,9 L0,0 L10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="0" y1="-9" x2="0" y2="-2" stroke="#64748b" stroke-width="2"/>
                            <line x1="-10" y1="0" x2="-2" y2="0" stroke="#64748b" stroke-width="2"/>
                        </g>
                    `;
                case 'abk_top_left':
                    return `
                        <g>
                            <rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <path d="M0,-9 L0,0 L-10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="0" y1="2" x2="0" y2="9" stroke="#64748b" stroke-width="2"/>
                            <line x1="2" y1="0" x2="10" y2="0" stroke="#64748b" stroke-width="2"/>
                        </g>
                    `;
                case 'abk_top_right':
                    return `
                        <g>
                            <rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <path d="M0,-9 L0,0 L10,0" stroke="#0f172a" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="0" y1="2" x2="0" y2="9" stroke="#64748b" stroke-width="2"/>
                            <line x1="-10" y1="0" x2="-2" y2="0" stroke="#64748b" stroke-width="2"/>
                        </g>
                    `;
                case 'rad_cross':
                    return `
                        <g>
                            <rect x="-14" y="-11" width="28" height="22" rx="3" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <path d="M-9,-5 L9,-5 M-9,5 L9,5" stroke="#0f172a" stroke-width="2.2" stroke-linecap="round"/>
                            <circle cx="-4" cy="0" r="2.5" fill="#0f172a"/>
                            <circle cx="4" cy="0" r="2.5" fill="#0f172a"/>
                        </g>
                    `;
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

        // =========================================================================
        // 4. PALETTE INITIALIZATION & DRAG-AND-DROP ENGINE
        // =========================================================================
        function initPaletteGrids() {
            const vGrid = document.getElementById('vehiclesPaletteGrid');
            const pGrid = document.getElementById('pedestriansPaletteGrid');
            const sGrid = document.getElementById('signsPaletteGrid');

            vGrid.innerHTML = '';
            Object.values(VEHICLE_CATALOG).forEach(v => {
                const div = document.createElement('div');
                div.className = 'palette-item';
                div.innerHTML = `
                    <svg class="palette-svg-preview" viewBox="-25 -40 50 80">${renderTopDownVehicleSVG(v.id, 0.85)}</svg>
                    <span class="palette-item-label">${v.name}</span>
                `;
                attachPaletteDrag(div, { kind: 'vehicle', id: v.id, name: v.name });
                vGrid.appendChild(div);
            });

            pGrid.innerHTML = '';
            Object.values(PEDESTRIAN_CATALOG).forEach(p => {
                const div = document.createElement('div');
                div.className = 'palette-item';
                div.innerHTML = `
                    <svg class="palette-svg-preview" viewBox="-20 -20 40 40">${renderTopDownPedestrianSVG(p.id, 1.1)}</svg>
                    <span class="palette-item-label">${p.name}</span>
                `;
                attachPaletteDrag(div, { kind: 'pedestrian', id: p.id, name: p.name });
                pGrid.appendChild(div);
            });

            // Quick draggable signs & traffic lights
            const quickSigns = [
                { kind: 'primarySign', id: 's306_priority_road', name: 'جاده اصلی (306)', svg: renderSignIconSVG('s306_priority_road') },
                { kind: 'primarySign', id: 's205_yield', name: 'حق تقدم بدهید (205)', svg: renderSignIconSVG('s205_yield') },
                { kind: 'primarySign', id: 's206_stop', name: 'ایست STOP (206)', svg: renderSignIconSVG('s206_stop') },
                { kind: 'primarySign', id: 's102_rvl', name: 'راست قبل از چپ', svg: renderSignIconSVG('s102_rvl') },
                { kind: 'secondarySign', id: 'abk_bottom_left', name: 'مکمل پیچ به چپ', svg: renderSignIconSVG('abk_bottom_left') },
                { kind: 'secondarySign', id: 'abk_bottom_right', name: 'مکمل پیچ به راست', svg: renderSignIconSVG('abk_bottom_right') },
                { kind: 'trafficLight', id: 'green', name: 'چراغ سبز', svg: renderTrafficLightSVG('green') },
                { kind: 'trafficLight', id: 'red', name: 'چراغ قرمز', svg: renderTrafficLightSVG('red') },
                { kind: 'trafficLight', id: 'yellow', name: 'چراغ زرد', svg: renderTrafficLightSVG('yellow') }
            ];

            sGrid.innerHTML = '';
            quickSigns.forEach(s => {
                const div = document.createElement('div');
                div.className = 'palette-item';
                div.innerHTML = `
                    <svg class="palette-svg-preview" viewBox="-22 -26 44 52">${s.svg}</svg>
                    <span class="palette-item-label">${s.name}</span>
                `;
                attachPaletteDrag(div, s);
                sGrid.appendChild(div);
            });
        }

        let activePaletteDrag = null;

        function attachPaletteDrag(el, payload) {
            el.addEventListener('pointerdown', function(e) {
                if (e.button !== undefined && e.button !== 0) return;
                e.preventDefault();
                el.setPointerCapture(e.pointerId);

                const ghost = document.createElement('div');
                ghost.className = 'builder-drag-ghost';
                ghost.innerHTML = `<i class="fas fa-arrows-alt"></i> <span>${payload.name}</span>`;
                document.body.appendChild(ghost);

                activePaletteDrag = {
                    pointerId: e.pointerId,
                    payload: payload,
                    ghostEl: ghost
                };
                moveDragGhost(e.clientX, e.clientY);
            });

            el.addEventListener('pointermove', function(e) {
                if (!activePaletteDrag || activePaletteDrag.pointerId !== e.pointerId) return;
                e.preventDefault();
                moveDragGhost(e.clientX, e.clientY);
                highlightCanvasDropSlot(e.clientX, e.clientY);
            });

            el.addEventListener('pointerup', function(e) {
                if (!activePaletteDrag || activePaletteDrag.pointerId !== e.pointerId) return;
                finishPaletteDrop(e.clientX, e.clientY);
            });

            el.addEventListener('pointercancel', function() {
                cleanupPaletteDrag();
            });
        }

        function moveDragGhost(cx, cy) {
            if (!activePaletteDrag || !activePaletteDrag.ghostEl) return;
            activePaletteDrag.ghostEl.style.left = cx + 'px';
            activePaletteDrag.ghostEl.style.top = cy + 'px';
        }

        function getHoveredSvgDropSlot(cx, cy) {
            const slots = document.querySelectorAll('.drop-slot-group');
            for (const slot of slots) {
                const r = slot.getBoundingClientRect();
                if (cx >= r.left && cx <= r.right && cy >= r.top && cy <= r.bottom) {
                    return {
                        branch: slot.getAttribute('data-branch'),
                        slotType: slot.getAttribute('data-slot-type'),
                        el: slot
                    };
                }
            }
            return null;
        }

        function highlightCanvasDropSlot(cx, cy) {
            document.querySelectorAll('.drop-slot-group').forEach(el => el.classList.remove('drag-hover'));
            const hit = getHoveredSvgDropSlot(cx, cy);
            if (hit && hit.el) {
                hit.el.classList.add('drag-hover');
            }
        }

        function finishPaletteDrop(cx, cy) {
            const hit = getHoveredSvgDropSlot(cx, cy);
            const payload = activePaletteDrag ? activePaletteDrag.payload : null;
            cleanupPaletteDrag();

            if (!hit || !payload) return;
            const b = scenarioState.branches[hit.branch];
            if (!b) return;

            selectActiveBranch(hit.branch);

            if (payload.kind === 'vehicle') {
                const nextPriority = getNextDefaultPriority();
                b.vehicle = {
                    type: payload.id,
                    direction: getDefaultDirectionForBranch(hit.branch),
                    priority: b.vehicle ? b.vehicle.priority : nextPriority
                };
            } else if (payload.kind === 'pedestrian') {
                const nextPriority = getNextDefaultPriority();
                b.hasCrosswalk = true;
                b.pedestrian = {
                    type: payload.id,
                    direction: 'across',
                    priority: b.pedestrian ? b.pedestrian.priority : nextPriority
                };
            } else if (payload.kind === 'primarySign') {
                b.primarySign = payload.id;
            } else if (payload.kind === 'secondarySign') {
                b.secondarySign = payload.id;
            } else if (payload.kind === 'trafficLight') {
                b.trafficLight = payload.id;
            }

            resetVehiclesAnimation();
            renderAll();
        }

        function cleanupPaletteDrag() {
            if (activePaletteDrag && activePaletteDrag.ghostEl && activePaletteDrag.ghostEl.parentNode) {
                activePaletteDrag.ghostEl.parentNode.removeChild(activePaletteDrag.ghostEl);
            }
            activePaletteDrag = null;
            document.querySelectorAll('.drop-slot-group').forEach(el => el.classList.remove('drag-hover'));
        }

        function getNextDefaultPriority() {
            const actors = getAllPlacedActors();
            return actors.length + 1;
        }

        function getDefaultDirectionForBranch(branch) {
            const active = INTERSECTION_BRANCHES[scenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            const opposite = { bottom: 'top', top: 'bottom', right: 'left', left: 'right' }[branch];
            if (active.includes(opposite)) return 'straight';
            const rightTurn = { bottom: 'right', right: 'top', top: 'left', left: 'bottom' }[branch];
            if (active.includes(rightTurn)) return 'right';
            return 'left';
        }

        // =========================================================================
        // 5. GEOMETRY & TRAJECTORY CALCULATIONS (600x600 INTERSECTION)
        // =========================================================================
        const BRANCH_GEOMETRY = {
            bottom: {
                vehicleStart: { x: 337, y: 495, angle: 0 },
                pedStart:     { x: 392, y: 412, angle: -90 },
                pedEnd:       { x: 208, y: 412 },
                signSlot:     { x: 428, y: 458 }
            },
            top: {
                vehicleStart: { x: 263, y: 105, angle: 180 },
                pedStart:     { x: 208, y: 188, angle: 90 },
                pedEnd:       { x: 392, y: 188 },
                signSlot:     { x: 172, y: 142 }
            },
            right: {
                vehicleStart: { x: 495, y: 263, angle: -90 },
                pedStart:     { x: 412, y: 208, angle: 180 },
                pedEnd:       { x: 412, y: 392 },
                signSlot:     { x: 458, y: 172 }
            },
            left: {
                vehicleStart: { x: 105, y: 337, angle: 90 },
                pedStart:     { x: 188, y: 392, angle: 0 },
                pedEnd:       { x: 188, y: 208 },
                signSlot:     { x: 142, y: 428 }
            }
        };

        /**
         * محاسبه مسیر منحنی یا مستقیم حرکت هر خودرو یا عابر پیاده در تقاطع
         */
        function getActorTrajectoryPathData(actor) {
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

        function getAllPlacedActors() {
            const activeBranches = INTERSECTION_BRANCHES[scenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            const list = [];

            activeBranches.forEach(branch => {
                const bData = scenarioState.branches[branch];
                if (!bData) return;

                if (bData.vehicle && bData.vehicle.type) {
                    const meta = VEHICLE_CATALOG[bData.vehicle.type] || VEHICLE_CATALOG.car_yellow;
                    list.push({
                        key: `vehicle_${branch}`,
                        branch: branch,
                        slotType: 'vehicle',
                        type: bData.vehicle.type,
                        name: `${meta.name} (${BRANCH_LABELS[branch]})`,
                        shortName: meta.name,
                        direction: bData.vehicle.direction || 'straight',
                        priority: parseInt(bData.vehicle.priority || 1, 10),
                        color: meta.color
                    });
                }

                if (bData.pedestrian && bData.pedestrian.type) {
                    const meta = PEDESTRIAN_CATALOG[bData.pedestrian.type] || PEDESTRIAN_CATALOG.pedestrian;
                    list.push({
                        key: `pedestrian_${branch}`,
                        branch: branch,
                        slotType: 'pedestrian',
                        type: bData.pedestrian.type,
                        name: `${meta.name} (${BRANCH_LABELS[branch]})`,
                        shortName: meta.name,
                        direction: 'across',
                        priority: parseInt(bData.pedestrian.priority || 1, 10),
                        color: meta.color
                    });
                }
            });

            return list;
        }

        // =========================================================================
        // 6. RENDERING THE 3D INTERSECTION (THREE.JS 3/4 ELEVATED PERSPECTIVE)
        // =========================================================================
        let scene3D = null;

        function renderIntersectionSVG() {
            if (!scene3D) return;
            scene3D.renderScenario(
                scenarioState,
                simClickedSequence,
                simEvaluationResult,
                studioMode === 'design'
            );
        }

        // =========================================================================
        // 7. INTERACTIVE CLICK-TO-MOVE ANIMATION & PRIORITY EVALUATION
        // =========================================================================
        function handleCanvasActorClick(actorKey, branch) {
            if (studioMode === 'design' && branch) {
                const activeBranches = INTERSECTION_BRANCHES[scenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
                if (activeBranches.includes(branch)) {
                    activeInspectorBranch = branch;
                    renderBranchInspector();
                }
            }
            triggerActorMoveAndSelect(actorKey);
        }

        function triggerActorMoveAndSelect(actorKey) {
            const actors = getAllPlacedActors();
            const actor = actors.find(a => a.key === actorKey);
            if (!actor) return;

            simEvaluationResult = null;
            document.getElementById('simResultBox').style.display = 'none';

            if (!simClickedSequence.includes(actorKey)) {
                simClickedSequence.push(actorKey);
            }

            renderClickedOrderPills();
            if (scene3D) {
                scene3D.animateActorMovement(actorKey, simClickedSequence);
            }
        }

        function resetVehiclesAnimation() {
            simClickedSequence = [];
            simEvaluationResult = null;

            const resBox = document.getElementById('simResultBox');
            if (resBox) resBox.style.display = 'none';

            renderClickedOrderPills();
            if (scene3D) {
                scene3D.resetPositions();
                scene3D.renderScenario(scenarioState, simClickedSequence, simEvaluationResult, studioMode === 'design');
            }
        }

        function renderClickedOrderPills() {
            const container = document.getElementById('clickedOrderPills');
            const actors = getAllPlacedActors();

            if (simClickedSequence.length === 0) {
                container.innerHTML = `<span class="text-muted small">روی خودروها یا عابر پیاده در نقشه کلیک کنید تا به ترتیب حرکت کنند.</span>`;
                return;
            }

            container.innerHTML = simClickedSequence.map((key, idx) => {
                const actor = actors.find(a => a.key === key);
                const label = actor ? actor.name : key;
                return `
                    <span class="sim-step-pill">
                        <span>${idx + 1}.</span>
                        <span>${label}</span>
                    </span>
                `;
            }).join(' <i class="fas fa-arrow-left text-muted small"></i> ');
        }

        /**
         * بررسی پاسخ کاربر نسبت به اولویت‌های تعیین‌شده توسط ادمین
         * خطوط مسیر حرکت بر اساس صحت اولویت سبز یا قرمز می‌شوند
         */
        function evaluateSimulationAnswer() {
            const actors = getAllPlacedActors();
            if (actors.length === 0) {
                Swal.fire({ icon: 'info', title: 'هیچ عضوی در تقاطع نیست', text: 'ابتدا حداقل دو کاربر جاده را در تقاطع قرار دهید.' });
                return;
            }

            if (simClickedSequence.length < actors.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'همه اعضا انتخاب نشده‌اند!',
                    text: `شما روی ${simClickedSequence.length} مورد کلیک کرده‌اید. لطفاً به ترتیب حق تقدم روی تمام ${actors.length} کاربر جاده در تقاطع کلیک کنید و سپس روی نمایش پاسخ بزنید.`,
                    confirmButtonText: 'متوجه شدم'
                });
                return;
            }

            // Sort distinct admin priorities to map rank 1..N
            const sortedPriorities = [...actors].sort((a, b) => a.priority - b.priority);
            const detailsByKey = {};
            let allCorrect = true;
            const feedbackRows = [];

            actors.forEach(actor => {
                const userClickIndex = simClickedSequence.indexOf(actor.key);
                const userSelectedActorAtSameStep = actors.find(a => a.key === simClickedSequence[userClickIndex]);

                // Expected priority at this step when sorted by admin priority
                const expectedPriorityAtStep = sortedPriorities[userClickIndex] ? sortedPriorities[userClickIndex].priority : actor.priority;
                const isStepCorrect = (actor.priority === expectedPriorityAtStep);

                detailsByKey[actor.key] = isStepCorrect;
                if (!isStepCorrect) {
                    allCorrect = false;
                }

                const userOrderNum = userClickIndex + 1;
                if (isStepCorrect) {
                    feedbackRows.push(`
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 mb-1" style="background: rgba(34, 197, 94, 0.15); border: 1.5px solid #16a34a;">
                            <span class="fw-bold small"><i class="fas fa-check-circle text-success me-1"></i> ${actor.name}</span>
                            <span class="badge bg-success">درست (اولویت ${actor.priority})</span>
                        </div>
                    `);
                } else {
                    feedbackRows.push(`
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 mb-1" style="background: rgba(239, 68, 68, 0.15); border: 1.5px solid #dc2626;">
                            <span class="fw-bold small"><i class="fas fa-times-circle text-danger me-1"></i> ${actor.name}</span>
                            <span class="badge bg-danger">انتخاب شما: ${userOrderNum} | اولویت صحیح: ${actor.priority}</span>
                        </div>
                    `);
                }
            });

            simEvaluationResult = {
                isAllCorrect: allCorrect,
                detailsByKey: detailsByKey
            };

            // Re-render SVG so trajectory lines turn Green or Red!
            renderIntersectionSVG();

            const expText = document.getElementById('scenarioExplanationInput').value.trim();
            const resBox = document.getElementById('simResultBox');
            resBox.className = 'sim-result-box ' + (allCorrect ? 'sim-result-win' : 'sim-result-lose');
            resBox.innerHTML = `
                <div class="fw-black mb-2" style="font-weight: 900; font-size: 0.98rem;">
                    ${allCorrect
                        ? '<i class="fas fa-trophy text-success me-1"></i> عالی! ترتیب حق تقدم را کاملاً صحیح مشخص کردید (خطوط سبز شدند).'
                        : '<i class="fas fa-exclamation-triangle text-danger me-1"></i> در تعیین اولویت اشتباه داشتید! خطوط قرمز نشان‌دهنده اولویت‌های اشتباه هستند:'}
                </div>
                <div class="mb-2">${feedbackRows.join('')}</div>
                ${expText ? `<div class="p-2 rounded-3 mt-2 small" style="background: rgba(255,255,255,0.65); color:#0f172a;"><strong><i class="fas fa-lightbulb text-warning me-1"></i> توضیح آموزشی:</strong> ${expText}</div>` : ''}
            `;
            resBox.style.display = 'block';
        }

        // =========================================================================
        // 8. INSPECTOR & ROAD USERS CONFIGURATION PANELS
        // =========================================================================
        function setIntersectionType(type) {
            scenarioState.intersectionType = type;
            document.querySelectorAll('.inter-type-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-inter') === type);
            });

            const swEl = document.getElementById('toggleHasSidewalks');
            if (swEl) swEl.checked = (scenarioState.hasSidewalks !== false);
            const rmEl = document.getElementById('toggleHasRoadMarkings');
            if (rmEl) rmEl.checked = (scenarioState.hasRoadMarkings !== false);

            const activeBranches = INTERSECTION_BRANCHES[type] || INTERSECTION_BRANCHES.cross4;
            if (!activeBranches.includes(activeInspectorBranch)) {
                activeInspectorBranch = activeBranches[0];
            }

            resetVehiclesAnimation();
            renderAll();
        }

        function toggleIntersectionOption(optionKey, isChecked) {
            scenarioState[optionKey] = !!isChecked;
            resetVehiclesAnimation();
            renderAll();
        }

        function selectActiveBranch(branch) {
            const activeBranches = INTERSECTION_BRANCHES[scenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            if (!activeBranches.includes(branch)) return;
            activeInspectorBranch = branch;
            renderBranchInspector();
            renderIntersectionSVG();
        }

        function renderBranchInspector() {
            const activeBranches = INTERSECTION_BRANCHES[scenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            document.querySelectorAll('.branch-tab-btn').forEach(btn => {
                const b = btn.getAttribute('data-branch');
                const isAvailable = activeBranches.includes(b);
                btn.classList.toggle('disabled-branch', !isAvailable);
                btn.disabled = !isAvailable;
                btn.classList.toggle('active', b === activeInspectorBranch);
            });

            const bData = scenarioState.branches[activeInspectorBranch];
            const container = document.getElementById('branchInspectorContent');
            if (!bData) return;

            const vOptions = `<option value="">-- بدون خودرو در این ضلع --</option>` +
                Object.values(VEHICLE_CATALOG).map(v =>
                    `<option value="${v.id}" ${bData.vehicle && bData.vehicle.type === v.id ? 'selected' : ''}>${v.name}</option>`
                ).join('');

            const pOptions = `<option value="">-- بدون عابر در پیاده‌رو --</option>` +
                Object.values(PEDESTRIAN_CATALOG).map(p =>
                    `<option value="${p.id}" ${bData.pedestrian && bData.pedestrian.type === p.id ? 'selected' : ''}>${p.name}</option>`
                ).join('');

            const s1Options = Object.values(PRIMARY_SIGNS).map(s =>
                `<option value="${s.id}" ${bData.primarySign === s.id ? 'selected' : ''}>${s.name}</option>`
            ).join('');

            const s2Options = Object.values(SECONDARY_SIGNS).map(s =>
                `<option value="${s.id}" ${bData.secondarySign === s.id ? 'selected' : ''}>${s.name}</option>`
            ).join('');

            const lightOptions = Object.values(TRAFFIC_LIGHTS).map(l =>
                `<option value="${l.id}" ${bData.trafficLight === l.id ? 'selected' : ''}>${l.name}</option>`
            ).join('');

            container.innerHTML = `
                <div class="p-2 rounded-3 border mb-2" style="background: var(--panel-sub);">
                    <div class="fw-black small mb-2 text-primary">
                        <i class="fas fa-location-arrow me-1"></i> تنظیمات ${BRANCH_LABELS[activeInspectorBranch]}:
                    </div>

                    <!-- Vehicle in this branch -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">خودرو / وسیله نقلیه در این ضلع:</label>
                        <select class="form-select form-select-sm fw-bold" onchange="onBranchVehicleChange('${activeInspectorBranch}', this.value)">
                            ${vOptions}
                        </select>
                    </div>

                    <!-- Sidewalk / Crosswalk toggle & Pedestrian -->
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="chkCrosswalk_${activeInspectorBranch}"
                               ${bData.hasCrosswalk ? 'checked' : ''}
                               onchange="onBranchCrosswalkToggle('${activeInspectorBranch}', this.checked)">
                        <label class="form-check-label small fw-bold" for="chkCrosswalk_${activeInspectorBranch}">
                            دارای خط عابر پیاده (گذرگاه پیاده‌رو) در این ضلع
                        </label>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">عابر یا دوچرخه داخل پیاده‌رو این ضلع:</label>
                        <select class="form-select form-select-sm fw-bold" onchange="onBranchPedestrianChange('${activeInspectorBranch}', this.value)">
                            ${pOptions}
                        </select>
                    </div>

                    <hr class="my-2">

                    <!-- Traffic Light -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-traffic-light text-warning me-1"></i> چراغ راهنمایی در این ضلع:</label>
                        <select class="form-select form-select-sm fw-bold" onchange="onBranchSignChange('${activeInspectorBranch}', 'trafficLight', this.value)">
                            ${lightOptions}
                        </select>
                    </div>

                    <!-- Primary Sign -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-sign text-danger me-1"></i> تابلو اصلی (بالا):</label>
                        <select class="form-select form-select-sm fw-bold" onchange="onBranchSignChange('${activeInspectorBranch}', 'primarySign', this.value)">
                            ${s1Options}
                        </select>
                    </div>

                    <!-- Secondary Sign (Stacked under Primary Sign) -->
                    <div class="mb-1">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-layer-group text-info me-1"></i> تابلو دوم زیر تابلو اول (ترکیب دو تابلو):</label>
                        <select class="form-select form-select-sm fw-bold" onchange="onBranchSignChange('${activeInspectorBranch}', 'secondarySign', this.value)">
                            ${s2Options}
                        </select>
                    </div>
                </div>
            `;
        }

        function onBranchVehicleChange(branch, typeId) {
            const b = scenarioState.branches[branch];
            if (!typeId) {
                b.vehicle = null;
            } else {
                const nextP = getNextDefaultPriority();
                b.vehicle = {
                    type: typeId,
                    direction: b.vehicle ? b.vehicle.direction : getDefaultDirectionForBranch(branch),
                    priority: b.vehicle ? b.vehicle.priority : nextP
                };
            }
            resetVehiclesAnimation();
            renderAll();
        }

        function onBranchCrosswalkToggle(branch, checked) {
            scenarioState.branches[branch].hasCrosswalk = checked;
            renderAll();
        }

        function onBranchPedestrianChange(branch, typeId) {
            const b = scenarioState.branches[branch];
            if (!typeId) {
                b.pedestrian = null;
            } else {
                const nextP = getNextDefaultPriority();
                b.hasCrosswalk = true;
                b.pedestrian = {
                    type: typeId,
                    direction: 'across',
                    priority: b.pedestrian ? b.pedestrian.priority : nextP
                };
            }
            resetVehiclesAnimation();
            renderAll();
        }

        function onBranchSignChange(branch, field, value) {
            scenarioState.branches[branch][field] = value;
            renderAll();
        }

        function renderPlacedActorsList() {
            const actors = getAllPlacedActors();
            document.getElementById('actorsCountBadge').textContent = `${actors.length} عضو`;
            const container = document.getElementById('placedActorsListContainer');

            if (actors.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted small py-3 border rounded-3">
                        هنوز خودرو یا عابری در تقاطع قرار نداده‌اید. از جعبه ابزار بکشید و در ضلع دلخواه رها کنید.
                    </div>
                `;
                return;
            }

            const activeBranches = INTERSECTION_BRANCHES[scenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;

            container.innerHTML = actors.map(actor => {
                const isPed = actor.slotType === 'pedestrian';
                let dirSelectHTML = '';
                if (!isPed) {
                    dirSelectHTML = `
                        <select class="form-select form-select-sm fw-bold" style="width: 115px;"
                                onchange="updateActorDirection('${actor.branch}', '${actor.slotType}', this.value)">
                            <option value="straight" ${actor.direction === 'straight' ? 'selected' : ''}>⬆ مستقیم</option>
                            <option value="left" ${actor.direction === 'left' ? 'selected' : ''}>↰ پیچ به چپ</option>
                            <option value="right" ${actor.direction === 'right' ? 'selected' : ''}>↱ پیچ به راست</option>
                        </select>
                    `;
                } else {
                    dirSelectHTML = `<span class="badge bg-secondary">عبور از پیاده‌رو</span>`;
                }

                let priorityOptions = '';
                for (let p = 1; p <= Math.max(actors.length, 4); p++) {
                    priorityOptions += `<option value="${p}" ${actor.priority === p ? 'selected' : ''}>اولویت ${p}</option>`;
                }

                return `
                    <div class="actor-config-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-black small" style="font-weight: 900;">
                                <span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:${actor.color};border:1px solid #000;"></span>
                                ${actor.name}
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removePlacedActor('${actor.branch}', '${actor.slotType}')" title="حذف">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <label class="small text-muted d-block" style="font-size:0.7rem;">مسیر حرکت (فلش):</label>
                                ${dirSelectHTML}
                            </div>
                            <div>
                                <label class="small text-muted d-block" style="font-size:0.7rem;">ترتیب حق تقدم:</label>
                                <select class="form-select form-select-sm fw-bold border-primary" style="width: 105px;"
                                        onchange="updateActorPriority('${actor.branch}', '${actor.slotType}', this.value)">
                                    ${priorityOptions}
                                </select>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateActorDirection(branch, slotType, newDir) {
            const b = scenarioState.branches[branch];
            if (slotType === 'vehicle' && b.vehicle) {
                b.vehicle.direction = newDir;
            }
            resetVehiclesAnimation();
            renderAll();
        }

        function updateActorPriority(branch, slotType, newPriority) {
            const b = scenarioState.branches[branch];
            if (slotType === 'vehicle' && b.vehicle) {
                b.vehicle.priority = parseInt(newPriority, 10);
            } else if (slotType === 'pedestrian' && b.pedestrian) {
                b.pedestrian.priority = parseInt(newPriority, 10);
            }
            resetVehiclesAnimation();
            renderAll();
        }

        function removePlacedActor(branch, slotType) {
            const b = scenarioState.branches[branch];
            if (slotType === 'vehicle') b.vehicle = null;
            if (slotType === 'pedestrian') b.pedestrian = null;
            resetVehiclesAnimation();
            renderAll();
        }

        function switchStudioMode(mode) {
            studioMode = mode;
            const btnDesign = document.getElementById('btnModeDesign');
            const btnPreview = document.getElementById('btnModePreview');

            btnDesign.className = 'mode-switch-btn' + (mode === 'design' ? ' active' : '');
            btnPreview.className = 'mode-switch-btn' + (mode === 'preview' ? ' active-sim' : '');

            resetVehiclesAnimation();
            renderAll();
        }

        function syncQuestionTextLive() {
            const title = document.getElementById('scenarioTitleInput').value.trim() || 'سوال اولویت عبور';
            const qText = document.getElementById('scenarioQuestionInput').value.trim() || 'به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟';
            const qDe = document.getElementById('scenarioQuestionDeInput').value.trim();

            document.getElementById('liveScenarioTitleBadge').textContent = title;
            document.getElementById('liveQuestionTextDisplay').textContent = qText;
            const deEl = document.getElementById('liveQuestionDeDisplay');
            if (qDe) {
                deEl.textContent = qDe;
                deEl.style.display = 'block';
            } else {
                deEl.style.display = 'none';
            }
        }

        function renderAll() {
            renderBranchInspector();
            renderPlacedActorsList();
            renderIntersectionSVG();
            syncQuestionTextLive();
        }

        // =========================================================================
        // 9. PRESETS & DATABASE SAVE / LOAD / DELETE
        // =========================================================================
        function loadPresetScenario(presetName) {
            currentScenarioId = 0;

            if (presetName === 'preset1') {
                document.getElementById('editingScenarioBadge').textContent = 'سوال ۱';
                document.getElementById('scenarioTitleInput').value = 'سوال ۱: چهارراه با تابلو رعایت حق تقدم و عبور همزمان';
                document.getElementById('scenarioQuestionInput').value = 'به چه ترتیبی وسایل نقلیه حق تقدم عبور دارند؟ (شما در ضلع پایین با تابلوی رعایت حق تقدم مستقیم می‌روید، ماشین قرمز در سمت راست به راست می‌پیچد و کامیون آبی در سمت چپ مستقیم می‌رود)';
                document.getElementById('scenarioQuestionDeInput').value = 'In welcher Reihenfolge dürfen die Fahrzeuge diese Kreuzung überqueren?';
                document.getElementById('scenarioExplanationInput').value = 'کامیون آبی و ماشین قرمز می‌توانند همزمان عبور کنند (اولویت ۱) و من باید به هر دوی آن‌ها حق تقدم بدهم (اولویت ۲)؛ چون تابلوی من (مثلث قرمز برعکس - رعایت حق تقدم) ایجاب می‌کند به سمت چپ و راست حق تقدم داده شود. دو وسیله دیگر چون تداخل مسیر ندارند می‌توانند همزمان عبور کنند.';

                scenarioState = {
                    intersectionType: 'cross4',
                    hasSidewalks: false,
                    hasRoadMarkings: false,
                    branches: {
                        top:    { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null },
                        right:  { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: { type: 'car_red', direction: 'right', priority: 1 }, pedestrian: null },
                        bottom: { hasCrosswalk: false, trafficLight: 'none', primarySign: 's205_yield', secondarySign: 'none', vehicle: { type: 'car_green', direction: 'straight', priority: 2 }, pedestrian: null },
                        left:   { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: { type: 'truck', direction: 'straight', priority: 1 }, pedestrian: null }
                    }
                };
            } else if (presetName === 'preset2') {
                document.getElementById('editingScenarioBadge').textContent = 'سوال ۲';
                document.getElementById('scenarioTitleInput').value = 'سوال ۲: چهارراه با تابلو حق تقدم یکباره (۳۰۱) و گردش به چپ';
                document.getElementById('scenarioQuestionInput').value = 'به چه ترتیبی وسایل نقلیه حق تقدم عبور دارند؟ (شما در ضلع پایین با تابلوی ۳۰۱ «حق تقدم یکباره» قصد گردش به چپ دارید، در ضلع بالا موتورسیکلت مستقیم می‌رود و در سمت راست اتوبوس زرد مستقیم می‌رود)';
                document.getElementById('scenarioQuestionDeInput').value = 'In welcher Reihenfolge dürfen die Fahrzeuge bei Zeichen 301 (einmalige Vorfahrt) fahren?';
                document.getElementById('scenarioExplanationInput').value = 'ترتیب صحیح: ۱. موتورسیکلت، ۲. من (خودروی ضلع پایین)، ۳. اتوبوس زرد. دلیل: تابلوی شماره ۳۰۱ به معنای «حق تقدم یکباره» در تقاطع بعدی است. هر زمان شما این تابلو را دارید، مسیر روبروی شما (موتورسیکلت) نیز همین شرایط را دارد؛ بنابراین هر دوی شما نسبت به اتوبوس زرد در سمت راست حق تقدم دارید. از آنجا که شرایط شما و موتورسیکلت روبرو یکسان است، طبق قانون بین دو وسیله با شرایط یکسان، وسیله‌ای که مستقیم می‌رود بر وسیله‌ای که قصد گردش به چپ دارد مقدم است؛ پس ابتدا موتورسیکلت (۱)، سپس شما (۲) و در پایان اتوبوس زرد (۳) عبور می‌کنند.';

                scenarioState = {
                    intersectionType: 'cross4',
                    hasSidewalks: false,
                    hasRoadMarkings: false,
                    branches: {
                        top:    { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: { type: 'moto', direction: 'straight', priority: 1 }, pedestrian: null },
                        right:  { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: { type: 'bus', direction: 'straight', priority: 3 }, pedestrian: null },
                        bottom: { hasCrosswalk: false, trafficLight: 'none', primarySign: 's301_priority_next', secondarySign: 'none', vehicle: { type: 'car_green', direction: 'left', priority: 2 }, pedestrian: null },
                        left:   { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null }
                    }
                };
            }

            setIntersectionType(scenarioState.intersectionType);
        }

        function resetToNewScenario() {
            currentScenarioId = 0;
            document.getElementById('editingScenarioBadge').textContent = 'سناریو جدید';
            document.getElementById('scenarioTitleInput').value = '';
            document.getElementById('scenarioQuestionInput').value = 'به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟ به ترتیب روی آن‌ها کلیک کنید.';
            document.getElementById('scenarioQuestionDeInput').value = '';
            document.getElementById('scenarioExplanationInput').value = '';

            scenarioState = {
                intersectionType: 'cross4',
                hasSidewalks: false,
                hasRoadMarkings: false,
                branches: {
                    top:    { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null },
                    right:  { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null },
                    bottom: { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null },
                    left:   { hasCrosswalk: false, trafficLight: 'none', primarySign: 'none', secondarySign: 'none', vehicle: null, pedestrian: null }
                }
            };
            setIntersectionType('cross4');
        }

        function saveCurrentScenario() {
            const actors = getAllPlacedActors();
            if (actors.length === 0) {
                Swal.fire({ icon: 'warning', title: 'تقاطع خالی است', text: 'لطفاً حداقل یک یا دو کاربر جاده را در تقاطع قرار دهید.' });
                return;
            }

            const title = document.getElementById('scenarioTitleInput').value.trim() || 'سناریو حق تقدم';
            const question_text = document.getElementById('scenarioQuestionInput').value.trim();
            const question_de = document.getElementById('scenarioQuestionDeInput').value.trim();
            const explanation = document.getElementById('scenarioExplanationInput').value.trim();

            const body = new URLSearchParams();
            body.append('action', 'save');
            body.append('csrf_token', CSRF_TOKEN);
            body.append('id', currentScenarioId);
            body.append('title', title);
            body.append('question_text', question_text);
            body.append('question_de', question_de);
            body.append('explanation', explanation);
            body.append('intersection_type', scenarioState.intersectionType);
            body.append('scenario_data', JSON.stringify(scenarioState));

            fetch('../incloud/priority_game_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    currentScenarioId = data.id;
                    document.getElementById('editingScenarioBadge').textContent = 'ویرایش #' + data.id;
                    Swal.fire({ icon: 'success', title: 'ذخیره شد!', text: data.message, timer: 1800, showConfirmButton: false });
                    fetchSavedScenarios();
                } else {
                    Swal.fire({ icon: 'error', title: 'خطا', text: (data && data.message) || 'خطا در ذخیره سناریو' });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'خطا در ارتباط با سرور' });
            });
        }

        let cachedScenarios = [];

        function fetchSavedScenarios() {
            fetch('../incloud/priority_game_handler.php?action=list')
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('savedScenariosList');
                if (!data || !data.success || !data.scenarios || data.scenarios.length === 0) {
                    cachedScenarios = [];
                    container.innerHTML = `<div class="text-center text-muted small py-3">هنوز سوالی ذخیره نشده است.</div>`;
                    return;
                }
                cachedScenarios = data.scenarios;
                container.innerHTML = data.scenarios.map(item => `
                    <div class="p-2 rounded-3 border mb-2 d-flex align-items-center justify-content-between gap-2" style="background: var(--panel-sub);">
                        <div class="text-truncate">
                            <div class="fw-bold small text-truncate">#${item.id} - ${item.title}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">نوع: ${item.intersection_type}</div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm btn-primary py-0 px-2" onclick="loadScenarioById(${item.id})" title="بارگذاری و ویرایش">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteScenarioById(${item.id})" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            })
            .catch(err => console.error(err));
        }

        function loadScenarioById(id) {
            const item = cachedScenarios.find(s => parseInt(s.id, 10) === parseInt(id, 10));
            if (!item) return;

            currentScenarioId = parseInt(item.id, 10);
            document.getElementById('editingScenarioBadge').textContent = 'ویرایش #' + item.id;
            document.getElementById('scenarioTitleInput').value = item.title || '';
            document.getElementById('scenarioQuestionInput').value = item.question_text || '';
            document.getElementById('scenarioQuestionDeInput').value = item.question_de || '';
            document.getElementById('scenarioExplanationInput').value = item.explanation || '';

            if (item.scenario_data && item.scenario_data.branches) {
                scenarioState = JSON.parse(JSON.stringify(item.scenario_data));
            }
            setIntersectionType(scenarioState.intersectionType || 'cross4');
        }

        function deleteScenarioById(id) {
            Swal.fire({
                icon: 'warning',
                title: 'حذف سناریو؟',
                text: 'آیا مطمئن هستید که می‌خواهید این سوال اولویت را حذف کنید؟',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف',
                confirmButtonColor: '#dc2626'
            }).then(res => {
                if (!res.isConfirmed) return;
                const body = new URLSearchParams();
                body.append('action', 'delete');
                body.append('csrf_token', CSRF_TOKEN);
                body.append('id', id);

                fetch('../incloud/priority_game_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        if (currentScenarioId === id) resetToNewScenario();
                        fetchSavedScenarios();
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stageEl = document.getElementById('canvasStageWrapper');
            if (stageEl && window.Priority3DScene) {
                scene3D = new window.Priority3DScene(stageEl, {
                    showDropZones: true,
                    onActorClick: function(actorKey, branch) {
                        handleCanvasActorClick(actorKey, branch);
                    },
                    onBranchSelect: function(branch) {
                        selectActiveBranch(branch);
                    }
                });
            }
            initPaletteGrids();
            loadPresetScenario('preset1');
            fetchSavedScenarios();
        });
    </script>
</body>
</html>
