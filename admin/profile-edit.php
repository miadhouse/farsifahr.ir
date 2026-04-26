
<?php include("common/head.php"); ?>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <?php include("common/sidebar.php"); ?>

    <!-- Layout container -->
    <div class="layout-page">
      <?php include("common/navbar.php"); ?>

      <!-- Content wrapper -->
      <div class="content-wrapper">
        <!-- Content -->
        <?php include("pages/profile-edit-content.php"); ?>
        <!-- / Content -->

        <?php include("common/footer.php"); ?>

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