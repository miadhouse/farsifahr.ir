<div class="container-xxl flex-grow-1 container-p-y">
    <div class="main-container application ">
        <div class="header">
            <h3><i class="fas fa-car"></i> سیستم مطالعه سوالات آزمون رانندگی</h3>
        </div>

        <div class="content-area">
            <div id="statsSection" style="display: none">
                <div class="row mb-4 text-center">
                    <div class="col-4 ">
                        <div class="stats-card  p-2 bordered">
                            <div class="stats-number" id="totalQuestions">0</div>
                            <div> سوال</div>
                        </div>
                    </div>
                    <div class="col-4 ">
                        <div class="stats-card  p-2 bordered">
                            <div class="stats-number" id="totalCategories">0</div>
                            <div>دسته بندی </div>
                        </div>
                    </div>
                    <div class="col-4  ">
                        <div class="stats-card p-2 bordered">
                            <div class="stats-number" id="dataVersion">-</div>
                            <div>نسخه فعال</div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="contentArea">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <h4>در حال بارگذاری...</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Panel -->
<div class="category-panel" id="categoryPanel">
    <div class="category-panel-content">
        <div class="category-info">
            <div class="category-title" id="panelCategoryTitle"></div>
            <div class="category-stats" id="panelCategoryStats"></div>
        </div>
        <div class="category-actions">
            <button class="category-btn btn-review" id="btnReview">
                <i class="fas fa-book-open me-2"></i>مرور
            </button>
            <button class="category-btn btn-practice" id="btnPractice">
                <i class="fas fa-pencil-alt me-2"></i>تمرین
            </button>
            <button class="category-btn btn-close" id="btnClose">
                <i class="fas fa-times me-2"></i>خروج
            </button>
        </div>
    </div>
</div>

<!-- Question Selection Modal -->
<div class="modal fade question-select-modal" id="questionSelectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-list-check me-2"></i>انتخاب سوالات برای
                    <span id="modalMode"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- کنترل‌های انتخاب -->
                <div class="selection-controls">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary" id="selectAllBtn">
                                    <i class="fas fa-check-double me-1"></i>انتخاب همه
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="deselectAllBtn">
                                    <i class="fas fa-times me-1"></i>حذف همه
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="selected-count" id="selectedCount">0 انتخاب شده</span>
                        </div>
                    </div>
                </div>

                <!-- لیست سوالات -->
                <div id="questionsList" style="max-height: 400px; overflow-y: auto">
                    <!-- Questions will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>انصراف
                    </button>
                    <button type="button" class="btn btn-success" id="startSessionBtn" disabled>
                        <i class="fas fa-play me-2"></i>شروع
                        <span id="sessionType"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Scripts -->