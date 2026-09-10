<?php
// Fetch all categories and subcategories
$stmt = $pdo->query("SELECT * FROM workshop_categories ORDER BY parent_id ASC, name ASC");
$all_categories = $stmt->fetchAll();

$parent_categories = [];
$sub_categories = [];

foreach ($all_categories as $cat) {
    if (!$cat['parent_id']) {
        $parent_categories[] = $cat;
    } else {
        $sub_categories[$cat['parent_id']][] = $cat;
    }
}

// Fetch all workshops
$stmt = $pdo->query("SELECT w.*, wc.name as category_name 
                    FROM workshops w 
                    JOIN workshop_categories wc ON w.workshop_category_id = wc.id 
                    WHERE w.is_active = 1 
                    ORDER BY w.created_at DESC");
$all_workshops = $stmt->fetchAll();

// Count workshops for each category (including children)
$workshop_counts = [];
foreach ($all_workshops as $workshop) {
    $cat_id = $workshop['workshop_category_id'];
    $workshop_counts[$cat_id] = ($workshop_counts[$cat_id] ?? 0) + 1;
}

// Aggregate counts for parents
foreach ($sub_categories as $parent_id => $subs) {
    foreach ($subs as $sub) {
        if (isset($workshop_counts[$sub['id']])) {
            $workshop_counts[$parent_id] = ($workshop_counts[$parent_id] ?? 0) + $workshop_counts[$sub['id']];
        }
    }
}

// Fetch all question videos
$all_videos = [];
try {
    $stmtVideos = $pdo->query("
        SELECT qv.*, q.number as question_number, q.farsi_text, q.text as german_text 
        FROM question_videos qv 
        JOIN questions q ON qv.question_id = q.id 
        ORDER BY qv.created_at DESC
    ");
    if ($stmtVideos) {
        $all_videos = $stmtVideos->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching question videos in workshop.php: " . $e->getMessage());
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-label-primary">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg me-3">
                        <span class="avatar-initial rounded bg-primary"><i class="bx bx-book-reader fs-3"></i></span>
                    </div>
                    <div>
                        <h4 class="mb-0">کارگاه آموزش</h4>
                        <p class="mb-0">مجموعه آموزش‌های تخصصی برای موفقیت در آزمون گواهینامه</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($parent_categories) && empty($all_videos)): ?>
        <div class="row">
            <div class="col-12 text-center py-5">
                <img src="assets/img/illustrations/boy-with-rocket-dark.png" alt="No Data" width="200" class="mb-3">
                <h5>هنوز آموزشی منتشر نشده است.</h5>
                <p class="text-muted">به زودی آموزش‌های جدید در این بخش قرار خواهد گرفت.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Sidebar for Categories -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">دسته‌بندی‌ها</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($parent_categories as $parent): ?>
                            <div class="list-group-item p-0 border-bottom-0">
                                <a href="javascript:void(0);" class="list-group-item list-group-item-action category-toggle fw-bold bg-light d-flex align-items-center" 
                                   data-target="sub-wrapper-<?php echo $parent['id']; ?>">
                                    <i class="bx bx-chevron-left me-2 category-chevron"></i>
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                    <span class="badge bg-label-secondary rounded-pill ms-auto">
                                        <?php echo $workshop_counts[$parent['id']] ?? 0; ?>
                                    </span>
                                </a>
                                <?php if (isset($sub_categories[$parent['id']])): ?>
                                    <div id="sub-wrapper-<?php echo $parent['id']; ?>" class="list-group list-group-flush ps-3 border-bottom subcategory-wrapper" style="display: none;">
                                        <?php foreach ($sub_categories[$parent['id']] as $sub): ?>
                                            <a href="#cat-<?php echo $sub['id']; ?>" class="list-group-item list-group-item-action category-filter py-1" 
                                               data-category="<?php echo $sub['id']; ?>"
                                               data-name="<?php echo htmlspecialchars($sub['name']); ?>"
                                               data-description="<?php echo htmlspecialchars($sub['description'] ?? ''); ?>"
                                               style="font-size: 0.9em;">
                                                <i class="bx bx-chevron-left me-1"></i> <?php echo htmlspecialchars($sub['name']); ?>
                                                <span class="badge bg-label-secondary rounded-pill ms-auto" style="font-size: 0.8em;">
                                                    <?php echo $workshop_counts[$sub['id']] ?? 0; ?>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- بخش جدید ویدیوهای آموزشی -->
                        <div class="list-group-item p-0 border-top mt-3 pt-2">
                            <a href="javascript:void(0);" id="video-section-tab" class="list-group-item list-group-item-action fw-bold bg-label-warning d-flex align-items-center py-2" style="border-radius: 8px;">
                                <i class="bx bx-video-recording me-2 text-warning fs-4"></i>
                                ویدیوهای آموزشی
                                <span class="badge bg-warning text-white rounded-pill ms-auto">
                                    <?php echo count($all_videos); ?>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workshop List / Video Gallery -->
            <div class="col-md-9">
                <!-- Category Header -->
                <div id="category-header" class="mb-4" style="display: none;">
                    <div class="card border-0 shadow-none bg-transparent">
                        <div class="card-body p-0 ps-2">
                            <h3 id="category-title" class="mb-2 text-primary"></h3>
                            <p id="category-description" class="text-muted mb-0" style="line-height: 1.6;"></p>
                        </div>
                    </div>
                    <hr class="mt-4">
                </div>

                <!-- No category selected state -->
                <div id="no-category-selected" class="card border-0 shadow-none bg-transparent text-center py-5">
                    <div class="card-body">
                        <i class="bx bx-subdirectory-left text-muted mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="text-muted">لطفاً برای مشاهده مطالب، یکی از دسته‌بندی‌ها را از منوی سمت راست انتخاب کنید.</h5>
                    </div>
                </div>

                <!-- Empty category state -->
                <div id="empty-category-message" class="card border-0 shadow-none bg-transparent text-center py-5" style="display: none;">
                    <div class="card-body">
                        <i class="bx bx-info-circle text-warning mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="text-warning">هیچ آموزشی در این دسته‌بندی یافت نشد.</h5>
                        <p class="text-muted">به زودی آموزش‌های جدید در این بخش قرار خواهد گرفت.</p>
                    </div>
                </div>

                <!-- Workshop container -->
                <div class="row" id="workshop-container">
                    <?php foreach ($all_workshops as $workshop): ?>
                        <?php 
                        // Find parent category ID if this is a subcategory
                        $current_cat_id = $workshop['workshop_category_id'];
                        $parent_id = 0;
                        foreach ($all_categories as $c) {
                            if ($c['id'] == $current_cat_id) {
                                $parent_id = $c['parent_id'];
                                break;
                            }
                        }
                        ?>
                        <div class="col-md-6 mb-4 workshop-card" 
                             data-category-id="<?php echo $current_cat_id; ?>"
                             data-parent-id="<?php echo $parent_id; ?>"
                             style="display: none;">
                            <div class="card h-100 shadow-sm border-0">
                                <?php if ($workshop['image']): ?>
                                    <?php 
                                        $image_src = $workshop['image'];
                                        if (strpos($image_src, 'http') !== 0) {
                                            $image_src = '/miad/public/storage/' . $image_src;
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" class="card-img-top p-3" alt="<?php echo htmlspecialchars($workshop['title']); ?>" style="height: 200px; object-fit: contain; background-color: #f8f9fa;">
                                <?php else: ?>
                                    <div class="card-img-top bg-label-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bx bx-image bx-lg text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-label-info"><?php echo htmlspecialchars($workshop['category_name']); ?></span>
                                        <small class="text-muted"><?php echo date('Y/m/d', strtotime($workshop['created_at'])); ?></small>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($workshop['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo mb_strimwidth(strip_tags($workshop['content']), 0, 120, '...'); ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent border-0 pt-0">
                                    <a href="workshop-details.php?slug=<?php echo $workshop['slug']; ?>" class="btn btn-outline-primary w-100">مشاهده آموزش</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Video gallery container -->
                <div class="row" id="video-gallery-container" style="display: none;">
                    <?php if (empty($all_videos)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="bx bx-video-off text-muted mb-3" style="font-size: 3.5rem;"></i>
                            <h5 class="text-muted">هنوز هیچ ویدیو آموزشی آپلود نشده است.</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_videos as $video): ?>
                            <?php 
                                $video_src = $video['video_url'];
                                if (strpos($video_src, 'http') !== 0) {
                                    $video_src = '/miad/public/storage/' . $video_src;
                                }
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="position-relative">
                                        <video src="<?php echo htmlspecialchars($video_src); ?>" controls class="card-img-top" style="height: 180px; object-fit: cover; background-color: #000; border-top-left-radius: 8px; border-top-right-radius: 8px;"></video>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-label-warning">سوال <?php echo htmlspecialchars($video['question_number']); ?></span>
                                            <small class="text-muted"><?php echo date('Y/m/d', strtotime($video['created_at'])); ?></small>
                                        </div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($video['title'] ?: 'ویدیو آموزشی سوال ' . $video['question_number']); ?></h5>
                                        <p class="card-text text-muted" style="font-size: 0.9em; line-height: 1.6;">
                                            <?php echo htmlspecialchars(mb_strimwidth(strip_tags($video['farsi_text'] ?: $video['german_text']), 0, 100, '...')); ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 pt-0">
                                        <a href="../app/index.php?questions=<?php echo $video['question_id']; ?>" target="_blank" class="btn btn-outline-warning w-100">
                                            <i class="bx bx-link-external me-1"></i> مشاهده سوال مرتبط
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.category-toggle');
    const filters = document.querySelectorAll('.category-filter');
    const cards = document.querySelectorAll('.workshop-card');
    const header = document.getElementById('category-header');
    const title = document.getElementById('category-title');
    const desc = document.getElementById('category-description');
    const noSelectionMsg = document.getElementById('no-category-selected');
    const emptyMsg = document.getElementById('empty-category-message');
    
    const videoSectionTab = document.getElementById('video-section-tab');
    const workshopContainer = document.getElementById('workshop-container');
    const videoGalleryContainer = document.getElementById('video-gallery-container');

    // Accordion functionality for parent categories
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const target = document.getElementById(targetId);
            
            if (target) {
                const isVisible = target.style.display === 'block';
                
                // Close all other subcategory wrappers
                document.querySelectorAll('.subcategory-wrapper').forEach(wrapper => {
                    if (wrapper.id !== targetId) {
                        wrapper.style.display = 'none';
                        const parentLink = document.querySelector(`[data-target="${wrapper.id}"]`);
                        if (parentLink) {
                            const chev = parentLink.querySelector('.category-chevron');
                            if (chev) {
                                chev.classList.remove('bx-chevron-down');
                                chev.classList.add('bx-chevron-left');
                            }
                        }
                    }
                });
                
                // Toggle this wrapper
                if (isVisible) {
                    target.style.display = 'none';
                    const chev = this.querySelector('.category-chevron');
                    if (chev) {
                        chev.classList.remove('bx-chevron-down');
                        chev.classList.add('bx-chevron-left');
                    }
                } else {
                    target.style.display = 'block';
                    const chev = this.querySelector('.category-chevron');
                    if (chev) {
                        chev.classList.remove('bx-chevron-left');
                        chev.classList.add('bx-chevron-down');
                    }
                }
            }
        });
    });

    // Filtering functionality for subcategories
    filters.forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Deactivate video tab if active
            if (videoSectionTab) {
                videoSectionTab.classList.remove('active');
            }
            if (videoGalleryContainer) {
                videoGalleryContainer.style.display = 'none';
            }
            if (workshopContainer) {
                workshopContainer.style.display = 'flex';
            }
            
            // Update active class on subcategories
            filters.forEach(f => f.classList.remove('active'));
            this.classList.add('active');

            const categoryId = this.getAttribute('data-category');
            const categoryName = this.getAttribute('data-name');
            const categoryDesc = this.getAttribute('data-description');

            // Hide the default placeholder
            if (noSelectionMsg) {
                noSelectionMsg.style.display = 'none';
            }

            // Update Header
            if (header) {
                header.style.display = 'block';
                title.innerText = categoryName;
                desc.innerText = categoryDesc || '';
            }

            // Filter cards
            let visibleCardsCount = 0;
            cards.forEach(card => {
                const cardCatId = card.getAttribute('data-category-id');
                if (cardCatId === categoryId) {
                    card.style.display = 'block';
                    visibleCardsCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show empty message if no cards match
            if (emptyMsg) {
                if (visibleCardsCount === 0) {
                    emptyMsg.style.display = 'block';
                } else {
                    emptyMsg.style.display = 'none';
                }
            }
        });
    });

    // Handle Educational Videos Tab Click
    if (videoSectionTab) {
        videoSectionTab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Deactivate standard categories
            filters.forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            
            // Close subcategory accordion list
            document.querySelectorAll('.subcategory-wrapper').forEach(wrapper => {
                wrapper.style.display = 'none';
            });
            document.querySelectorAll('.category-chevron').forEach(chev => {
                chev.classList.remove('bx-chevron-down');
                chev.classList.add('bx-chevron-left');
            });
            
            // Hide standard layouts
            if (noSelectionMsg) noSelectionMsg.style.display = 'none';
            if (emptyMsg) emptyMsg.style.display = 'none';
            if (workshopContainer) workshopContainer.style.display = 'none';
            
            // Show Header
            if (header) {
                header.style.display = 'block';
                title.innerText = 'ویدیوهای آموزشی سوالات آیین نامه';
                desc.innerText = 'لیست کامل ویدیوهای آموزشی ضبط شده برای سوالات آیین نامه به زبان فارسی.';
            }
            
            // Show video gallery
            if (videoGalleryContainer) {
                videoGalleryContainer.style.display = 'flex';
            }
        });
    }
});
</script>
