<li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
  <?php
  $user_sub = get_user_pending_subscription($_SESSION['user_id'], $pdo);
  ?>
  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
    data-bs-auto-close="outside" aria-expanded="false">
    <i class="bx bx-cart bx-sm"></i>
        <?php if ($user_sub != null || $user_sub != false && $user_sub['status'] == 'pending'): ?>
<span class="badge bg-danger rounded-pill badge-notifications">1</span>
<?php endif; ?>
  </a>
  <ul class="dropdown-menu dropdown-menu-end py-0">
    <li class="dropdown-menu-header border-bottom">
      <div class="dropdown-header d-flex align-items-center py-3">
        <h5 class="text-body mb-0 me-auto secondary-font">سبد خرید</h5>
        <span class=" text-body"><i class="bx fs-4 bx-cart"></i></a>
      </div>
    </li>
    <ul class="list-group list-group-flush">
    <?php if ($user_sub != null || $user_sub != false && $user_sub['status'] == 'pending'): ?>
      <li class="dropdown-notifications-list scrollable-container">
        <ul class="list-group list-group-flush">
          <li class="list-group-item list-group-item-action dropdown-notifications-item">
            <div class="d-flex">
              <div class="flex-shrink-0 me-3">
                <div class="avatar">
                  <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bx-cart"></i></span>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">درخواست اشتراک <?= $user_sub['plan_name'] ?></h6>
                <p class="mb-1"><span class="">سه ماهه</span> <span
                    class="text-body alert alert-success p-1 mx-1 fs-5"><?= $user_sub['amount_paid']?> </span> <span class="fs-7">تومان</span></p>
              </div>
              <div class="flex-shrink-0 dropdown-notifications-actions">
                <a href="javascript:void(0)" class="dropdown-notifications-read">در حال بازبینی</a>
                <a class=""><span class="bx bx-trash  text-warning"></span></a>
              </div>
            </div>
          </li>
        </ul>
      </li>
    <?php else: ?>
            <li class="dropdown-notifications-list scrollable-container m-4">فاکتوری جهت نمایش وجود ندارد</li>

    <?php endif; ?>
    </ul> 
    <li class="dropdown-menu-footer border-top">
      <a href="javascript:void(0);" class="dropdown-item d-flex justify-content-center p-3">
        مشاهده همه فاکتورها
      </a>
    </li>
  </ul>
</li>