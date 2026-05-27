<?php
require_once __DIR__ . '/incloud/functions.php';
require_once __DIR__ . '/incloud/subscription-functions.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: index.php");
    exit();
}

$title = $post['title'] . ' | ' . __('site_title', 'Farsi Fahr');
?>
<!DOCTYPE html>
<html lang="<?= get_current_lang() ?>" dir="<?= get_lang_dir() ?>">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 160)) ?>">
    
    <link href="assets/images/favicon.svg" rel="shortcut icon" type="image/x-icon">
    <?php if (get_lang_dir() === 'rtl'): ?>
    <link href="assets/css/vendor/bootstrap.min.rtl.css" rel="stylesheet">
    <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="assets/css/style.rtl.css" rel="stylesheet">
    <link href="assets/css/landing-custom.css" rel="stylesheet">
    <link href="assets/css/vendor/fontawesome.css" rel="stylesheet">
    <link href="assets/css/font-ir.css" rel="stylesheet">
    
    <style>
        .blog-details-area {
            padding: 100px 0;
            background-color: #0f1113;
            color: #fff;
        }
        .blog-content {
            font-size: 1.2rem;
            line-height: 2;
            color: #adb5bd;
        }
        .blog-content img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            margin: 20px 0;
        }
        .blog-meta {
            margin-bottom: 30px;
            color: #667eea;
        }
        .blog-meta span {
            margin-left: 20px;
        }
        .featured-image {
            width: 100%;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .back-btn {
            margin-bottom: 30px;
            display: inline-block;
            color: #fff;
            text-decoration: none;
        }
        .back-btn:hover {
            color: #667eea;
        }
    </style>
</head>

<body class="dark-style">
    <div class="blog-details-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <a href="index.php" class="back-btn">
                        <i class="fa-regular fa-arrow-right me-2"></i> <?= __('back_to_home', 'بازگشت به خانه') ?>
                    </a>
                    
                    <h1 class="mb-4"><?= htmlspecialchars($post['title']) ?></h1>
                    
                    <div class="blog-meta">
                        <span><i class="fa-regular fa-calendar me-2"></i> <?= date('Y-m-d', strtotime($post['published_at'] ?: $post['created_at'])) ?></span>
                        <span><i class="fa-regular fa-user me-2"></i> <?= htmlspecialchars($post['author_name']) ?></span>
                    </div>
                    
                    <?php if ($post['image']): ?>
                        <img src="<?= SITE_URL ?>panel/storage/<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="featured-image">
                    <?php endif; ?>
                    
                    <div class="blog-content">
                        <?= $post['content'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer copied from index.php simplified -->
    <footer class="footer-area footer-style-two-wrapper bg-color-footer bg_images tmp-section-gap">
        <div class="container text-center">
            <p><?= COPYRIGHT_TEXT ?></p>
        </div>
    </footer>

    <script src="assets/js/vendor/jquery.js"></script>
    <script src="assets/js/vendor/bootstrap.min.js"></script>
</body>
</html>
