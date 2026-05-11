<?php
// اتصال به دیتابیس (همان کدی که نوشتی)
$host = 'localhost';
$db   = 'question_answer';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// گرفتن تمام دسته‌بندی‌ها
$stmt = $pdo->query("SELECT * FROM categories ORDER BY index_code");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// دسته‌بندی داده‌ها به صورت درختی
$tree = [];
foreach ($categories as $cat) {
    if ($cat['parent_id'] == null) {
        $tree[$cat['id']] = $cat;
        $tree[$cat['id']]['children'] = [];
    }
}
foreach ($categories as $cat) {
    if ($cat['level'] == 1) {
        $tree[$cat['parent_id']]['children'][$cat['id']] = $cat;
        $tree[$cat['parent_id']]['children'][$cat['id']]['children'] = [];
    }
}
foreach ($categories as $cat) {
    if ($cat['level'] == 2) {
        foreach ($tree as &$root) {
            if (isset($root['children'][$cat['parent_id']])) {
                $root['children'][$cat['parent_id']]['children'][] = $cat;
            }
        }
    }
}

// تقسیم دسته‌ها به دو ستون
$treeArray = array_values($tree);
$halfCount = ceil(count($treeArray) / 2);
$firstColumn = array_slice($treeArray, 0, $halfCount);
$secondColumn = array_slice($treeArray, $halfCount);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دسته‌بندی‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Tahoma', sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .page-title {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }
        
        .accordion-item {
            border: none;
            margin-bottom: 1rem;
            border-radius: 15px !important;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .accordion-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .accordion-button {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 1.2rem 1.5rem;
            border-radius: 15px !important;
            transition: all 0.3s ease;
        }
        
        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: none;
        }
        
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23495057'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }
        
        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }
        
        .accordion-body {
            padding: 1.5rem;
            background: #ffffff;
        }
        
        .sub-accordion .accordion-item {
            margin-bottom: 0.5rem;
            border-radius: 10px !important;
        }
        
        .sub-accordion .accordion-button {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            font-size: 0.95rem;
            padding: 1rem;
            color: #1976d2;
        }
        
        .sub-accordion .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
        }
        
        .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 0.5rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-group-item:hover {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateX(-5px);
            cursor: pointer;
        }
        
        .badge {
            background: linear-gradient(45deg, #667eea, #764ba2) !important;
            padding: 0.5rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* .column-separator {
            background: linear-gradient(to bottom, transparent, rgba(102, 126, 234, 0.3), transparent);
            width: 2px;
            margin: 0 1rem;
        } */
        
        .category-icon {
            margin-left: 0.5rem;
            color: #667eea;
        }
        
        /* @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .column-separator {
                display: none;
            }
        }
         */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <div class="main-container p-4 mx-auto fade-in">
            <h1 class="page-title">
                <i class="fas fa-sitemap category-icon"></i>
                دسته‌بندی‌های سوالات
            </h1>
            
            <div class="row">
                <!-- ستون اول -->
                <div class="col-12 col-md-6">
                    <div class="accordion" id="firstColumnAccordion">
                        <?php foreach ($firstColumn as $index => $rootCat): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading1_<?= $rootCat['id'] ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse1_<?= $rootCat['id'] ?>" aria-expanded="false">
                                        <i class="fas fa-folder category-icon"></i>
                                        <?= htmlspecialchars($rootCat['title']) ?>
                                    </button>
                                </h2>
                                <div id="collapse1_<?= $rootCat['id'] ?>" class="accordion-collapse collapse" 
                                     data-bs-parent="#firstColumnAccordion">
                                    <div class="accordion-body">
                                        <div class="accordion sub-accordion" id="subAccordion1_<?= $rootCat['id'] ?>">
                                            <?php foreach ($rootCat['children'] as $level1Index => $level1Cat): ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="headingL1_1_<?= $level1Cat['id'] ?>">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                data-bs-target="#collapseL1_1_<?= $level1Cat['id'] ?>" aria-expanded="false">
                                                            <i class="fas fa-folder-open category-icon"></i>
                                                            <?= htmlspecialchars($level1Cat['title']) ?>
                                                        </button>
                                                    </h2>
                                                    <div id="collapseL1_1_<?= $level1Cat['id'] ?>" class="accordion-collapse collapse"
                                                         data-bs-parent="#subAccordion1_<?= $rootCat['id'] ?>">
                                                        <div class="accordion-body">
                                                            <div class="list-group list-group-flush">
                                                                <?php foreach ($level1Cat['children'] as $level2Cat): ?>
                                                                    <div class="list-group-item">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="fas fa-file-alt category-icon"></i>
                                                                            <span><?= htmlspecialchars($level2Cat['title']) ?></span>
                                                                        </div>
                                                                        <span class="badge">
                                                                            <i class="fas fa-question-circle me-1"></i>
                                                                            <?= $level2Cat['question_count'] ?> سوال
                                                                        </span>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                
                <!-- ستون دوم -->
                <div class="col-12 col-md-6">
                    <div class="accordion" id="secondColumnAccordion">
                        <?php foreach ($secondColumn as $index => $rootCat): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading2_<?= $rootCat['id'] ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse2_<?= $rootCat['id'] ?>" aria-expanded="false">
                                        <i class="fas fa-folder category-icon"></i>
                                        <?= htmlspecialchars($rootCat['title']) ?>
                                    </button>
                                </h2>
                                <div id="collapse2_<?= $rootCat['id'] ?>" class="accordion-collapse collapse" 
                                     data-bs-parent="#secondColumnAccordion">
                                    <div class="accordion-body">
                                        <div class="accordion sub-accordion" id="subAccordion2_<?= $rootCat['id'] ?>">
                                            <?php foreach ($rootCat['children'] as $level1Index => $level1Cat): ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="headingL1_2_<?= $level1Cat['id'] ?>">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                data-bs-target="#collapseL1_2_<?= $level1Cat['id'] ?>" aria-expanded="false">
                                                            <i class="fas fa-folder-open category-icon"></i>
                                                            <?= htmlspecialchars($level1Cat['title']) ?>
                                                        </button>
                                                    </h2>
                                                    <div id="collapseL1_2_<?= $level1Cat['id'] ?>" class="accordion-collapse collapse"
                                                         data-bs-parent="#subAccordion2_<?= $rootCat['id'] ?>">
                                                        <div class="accordion-body">
                                                            <div class="list-group list-group-flush">
                                                                <?php foreach ($level1Cat['children'] as $level2Cat): ?>
                                                                    <div class="list-group-item">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="fas fa-file-alt category-icon"></i>
                                                                            <span><?= htmlspecialchars($level2Cat['title']) ?></span>
                                                                        </div>
                                                                        <span class="badge">
                                                                            <i class="fas fa-question-circle me-1"></i>
                                                                            <?= $level2Cat['question_count'] ?> سوال
                                                                        </span>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // انیمیشن لود صفحه
        document.addEventListener('DOMContentLoaded', function() {
            const accordionItems = document.querySelectorAll('.accordion-item');
            accordionItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    item.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
        
        // افکت hover برای آیتم‌های لیست
        document.addEventListener('DOMContentLoaded', function() {
            const listItems = document.querySelectorAll('.list-group-item');
            listItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.background = 'linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.background = 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)';
                });
            });
        });
    </script>
</body>
</html>