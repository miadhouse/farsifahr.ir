<?php include("common/head.php"); ?>

<?php
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header("Location: workshop.php");
    exit();
}

$stmt = $pdo->prepare("SELECT w.*, wc.name as category_name 
                      FROM workshops w 
                      JOIN workshop_categories wc ON w.workshop_category_id = wc.id 
                      WHERE w.slug = ? AND w.is_active = 1");
$stmt->execute([$slug]);
$workshop = $stmt->fetch();

if (!$workshop) {
    header("Location: workshop.php");
    exit();
}
?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <?php include("common/sidebar.php"); ?>

    <!-- Layout container -->
    <div class="layout-page">
      <!-- Navbar -->
      <?php include("common/navbar.php"); ?>
      <!-- / Navbar -->

      <!-- Content wrapper -->
      <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Back Button -->
                    <div class="mb-3">
                        <a href="workshop.php" class="btn btn-label-secondary">
                            <i class="bx bx-chevron-right"></i> بازگشت به لیست آموزش‌ها
                        </a>
                    </div>

                    <div class="card overflow-hidden">
                        <?php if ($workshop['image']): ?>
                            <?php 
                                $image_src = $workshop['image'];
                                if (strpos($image_src, 'http') !== 0) {
                                    $image_src = '/miad/public/storage/' . $image_src;
                                }
                            ?>
                            <div class="text-center bg-light p-4">
                                <img src="<?php echo htmlspecialchars($image_src); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($workshop['title']); ?>" style="max-height: 300px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-label-primary me-2"><?php echo htmlspecialchars($workshop['category_name']); ?></span>
                                <small class="text-muted"><i class="bx bx-calendar-alt me-1"></i> <?php echo date('Y/m/d', strtotime($workshop['created_at'])); ?></small>
                            </div>
                            
                            <h2 class="mb-4"><?php echo htmlspecialchars($workshop['title']); ?></h2>
                            
                            <div class="workshop-content">
                                <?php echo $workshop['content']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- / Content -->

        <!-- Footer -->
        <?php include("common/footer.php"); ?>

        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
      </div>
      <!-- Content wrapper -->
    </div>
    <!-- / Layout page -->
  </div>

  <!-- Overlay -->
  <div class="layout-overlay layout-menu-toggle"></div>

  <!-- Drag Target Area To SlideIn Menu On Small Screens -->
  <div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->

<!-- Core JS -->
<?php include("common/scripts.php"); ?>

<style>
.workshop-content {
    line-height: 1.8;
    font-size: 1.1rem;
}
.workshop-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5rem 0;
}
</style>

</body>
</html>
