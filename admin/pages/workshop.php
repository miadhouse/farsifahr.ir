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
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-label-primary">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-lg me-3">
                        <span class="avatar-initial rounded bg-primary"><i class="bx bx-academic-cap bx-sm"></i></span>
                    </div>
                    <div>
                        <h4 class="mb-0">کارگاه آموزش</h4>
                        <p class="mb-0">مجموعه آموزش‌های تخصصی برای موفقیت در آزمون گواهینامه</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($parent_categories)): ?>
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
                        <a href="#all" class="list-group-item list-group-item-action active category-filter" 
                           data-category="all" 
                           data-name="همه آموزش‌ها" 
                           data-description="در این بخش تمامی آموزش‌های مربوط به گواهینامه رانندگی آلمان را مشاهده می‌کنید.">
                            همه آموزش‌ها
                        </a>
                        <?php foreach ($parent_categories as $parent): ?>
                            <div class="list-group-item p-0 border-bottom-0">
                                <a href="#cat-<?php echo $parent['id']; ?>" class="list-group-item list-group-item-action category-filter fw-bold bg-light" 
                                   data-category="<?php echo $parent['id']; ?>"
                                   data-name="<?php echo htmlspecialchars($parent['name']); ?>"
                                   data-description="<?php echo htmlspecialchars($parent['description'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                    <span class="badge bg-label-secondary rounded-pill ms-auto">
                                        <?php echo $workshop_counts[$parent['id']] ?? 0; ?>
                                    </span>
                                </a>
                                <?php if (isset($sub_categories[$parent['id']])): ?>
                                    <div class="list-group list-group-flush ps-3 border-bottom">
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
                    </div>
                </div>
            </div>

            <!-- Workshop List -->
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
                             data-parent-id="<?php echo $parent_id; ?>">
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
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filters = document.querySelectorAll('.category-filter');
    const cards = document.querySelectorAll('.workshop-card');
    const header = document.getElementById('category-header');
    const title = document.getElementById('category-title');
    const desc = document.getElementById('category-description');

    filters.forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active class
            filters.forEach(f => f.classList.remove('active'));
            this.classList.add('active');

            const categoryId = this.getAttribute('data-category');
            const categoryName = this.getAttribute('data-name');
            const categoryDesc = this.getAttribute('data-description');

            // Update Header
            if (categoryId === 'all') {
                header.style.display = 'none';
            } else {
                header.style.display = 'block';
                title.innerText = categoryName;
                desc.innerText = categoryDesc;
            }

            cards.forEach(card => {
                const cardCatId = card.getAttribute('data-category-id');
                const cardParentId = card.getAttribute('data-parent-id');

                if (categoryId === 'all' || cardCatId === categoryId || cardParentId === categoryId) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>
