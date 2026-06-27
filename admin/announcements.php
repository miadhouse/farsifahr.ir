<?php 
include("common/head.php"); 

// علامت‌گذاری به عنوان خوانده شده به محض باز کردن این صفحه
if (isset($_SESSION['user_id'])) {
    mark_all_announcements_as_read($_SESSION['user_id']);
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
            <?php include("pages/announcements-content.php"); ?>
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
<!-- build:js assets/vendor/js/core.js -->
<?php include("common/scripts.php"); ?>

</body>
</html>
