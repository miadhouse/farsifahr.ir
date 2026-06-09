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

// Fetch approved comments (only top-level ones)
$stmtComments = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' AND parent_id IS NULL ORDER BY created_at DESC");
$stmtComments->execute([$post_id]);
$comments = $stmtComments->fetchAll();

// Function to fetch replies
function get_replies($parent_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE parent_id = ? AND status = 'approved' ORDER BY created_at ASC");
    $stmt->execute([$parent_id]);
    return $stmt->fetchAll();
}

$title = $post['title'] . ' | ' . __('site_title', 'farsifahr');
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
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
            margin-bottom: 50px;
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
        
        /* Comments Section Styles */
        .comments-section {
            border-top: 1px solid #2b2e35;
            padding-top: 50px;
            margin-top: 50px;
        }
        .comment-item {
            background: #1e2125;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .reply-item {
            margin-right: 40px; /* For RTL */
            border-right: 2px solid #667eea;
            background: #16191d;
        }
        [dir="ltr"] .reply-item {
            margin-right: 0;
            margin-left: 40px;
            border-right: none;
            border-left: 2px solid #667eea;
        }
        .comment-author {
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }
        .comment-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .comment-content {
            color: #adb5bd;
        }
        .reply-btn {
            font-size: 0.9rem;
            color: #667eea;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
        }
        .comment-form {
            background: #1e2125;
            padding: 30px;
            border-radius: 15px;
            margin-top: 50px;
        }
        .form-control {
            background-color: #0f1113;
            border: 1px solid #2b2e35;
            color: #fff;
        }
        .form-control:focus {
            background-color: #0f1113;
            border-color: #667eea;
            color: #fff;
            box-shadow: none;
        }
        #replying-to-box {
            background: #2b2e35;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: none;
            justify-content: space-between;
            align-items: center;
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
                        <img src="<?= rtrim(SITE_URL, '/') ?>/panel/storage/<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="featured-image">
                    <?php endif; ?>
                    
                    <div class="blog-content">
                        <?= $post['content'] ?>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="comments-section" id="comments">
                        <h3 class="mb-4"><?= __('comments_title', 'نظرات کاربران') ?></h3>
                        
                        <?php if (empty($comments)): ?>
                            <p class="text-muted"><?= __('no_comments_yet', 'هنوز نظری ثبت نشده است. اولین نفر باشید!') ?></p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item" id="comment-<?= $comment['id'] ?>">
                                    <div class="comment-author"><?= htmlspecialchars($comment['author_name']) ?></div>
                                    <div class="comment-date"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></div>
                                    <div class="comment-content"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                                    <span class="reply-btn" onclick="setReply(<?= $comment['id'] ?>, '<?= htmlspecialchars($comment['author_name']) ?>')">
                                        <i class="fa-regular fa-reply me-1"></i> <?= __('reply', 'پاسخ') ?>
                                    </span>
                                </div>
                                
                                <!-- Replies -->
                                <?php 
                                $replies = get_replies($comment['id'], $pdo);
                                foreach ($replies as $reply):
                                ?>
                                    <div class="comment-item reply-item" id="comment-<?= $reply['id'] ?>">
                                        <div class="comment-author"><?= htmlspecialchars($reply['author_name']) ?></div>
                                        <div class="comment-date"><?= date('Y-m-d H:i', strtotime($reply['created_at'])) ?></div>
                                        <div class="comment-content"><?= nl2br(htmlspecialchars($reply['content'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Comment Form -->
                        <div class="comment-form" id="respond">
                            <h4 class="mb-4"><?= __('leave_a_comment', 'ارسال نظر') ?></h4>
                            
                            <div id="replying-to-box">
                                <span><?= __('replying_to', 'در حال پاسخ به:') ?> <strong id="reply-author"></strong></span>
                                <button type="button" class="btn btn-sm btn-link text-danger" onclick="cancelReply()"><?= __('cancel', 'انصراف') ?></button>
                            </div>

                            <form id="commentForm">
                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                <input type="hidden" name="parent_id" id="parent_id" value="">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><?= __('name_label', 'نام شما') ?></label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><?= __('email_label', 'ایمیل شما') ?></label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?= __('comment_text_label', 'متن نظر') ?></label>
                                    <textarea class="form-control" name="content" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary px-5"><?= __('submit_comment', 'ارسال نظر') ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-area footer-style-two-wrapper bg-color-footer bg_images tmp-section-gap">
        <div class="container text-center">
            <p><?= COPYRIGHT_TEXT ?></p>
        </div>
    </footer>

    <script src="assets/js/vendor/jquery.js"></script>
    <script src="assets/js/vendor/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function setReply(id, author) {
            $('#parent_id').val(id);
            $('#reply-author').text(author);
            $('#replying-to-box').css('display', 'flex');
            $('html, body').animate({
                scrollTop: $("#respond").offset().top - 100
            }, 500);
        }

        function cancelReply() {
            $('#parent_id').val('');
            $('#replying-to-box').hide();
        }

        $(document).ready(function() {
            $('#commentForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('در حال ارسال...');
                
                $.ajax({
                    url: 'incloud/submit_comment.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'موفقیت',
                                text: response.message,
                                confirmButtonText: 'متوجه شدم'
                            });
                            $('#commentForm')[0].reset();
                            cancelReply();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطا',
                                text: response.message,
                                confirmButtonText: 'تلاش مجدد'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا',
                            text: 'خطا در برقراری ارتباط با سرور',
                            confirmButtonText: 'متوجه شدم'
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('ارسال نظر');
                    }
                });
            });
        });
    </script>
</body>
</html>
