<!-- Load Data Files -->

<?php include("common/head.php"); ?>

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
            <?php include("pages/practice.php"); ?>
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

<!-- <script>
  class DrivingLicenseApp {
    constructor() {
      // تنظیم متغیرهای ثابت به جای انتخاب کاربر
      this.selectedClass = "6"; // کلاس ثابت
      this.selectedDataVersion = 2; // نسخه ثابت (نسخه 2)
      this.currentData = {};
      this.currentCategoryId = null;
      this.currentMode = null;
      this.selectedQuestions = [];
      this.init();
    }

    init() {
      this.setupModalEventListeners();
      // بلافاصله داده‌ها را لود می‌کنیم
      this.loadData();
    }

    setupModalEventListeners() {
      // انتخاب همه سوالات
      $("#selectAllBtn").on("click", () => {
        $(".question-checkbox").prop("checked", true);
        $(".question-list-item").addClass("selected");
        this.updateSelectedCount();
      });

      // حذف انتخاب همه سوالات
      $("#deselectAllBtn").on("click", () => {
        $(".question-checkbox").prop("checked", false);
        $(".question-list-item").removeClass("selected");
        this.updateSelectedCount();
      });

      // تغییر وضعیت انتخاب سوال
      $(document).on("change", ".question-checkbox", (e) => {
        const checkbox = $(e.target);
        const listItem = checkbox.closest(".question-list-item");

        if (checkbox.is(":checked")) {
          listItem.addClass("selected");
        } else {
          listItem.removeClass("selected");
        }

        this.updateSelectedCount();
      });

      // شروع جلسه (تمرین یا مرور)
      $("#startSessionBtn").on("click", () => {
        this.startSession();
      });
    }

    updateSelectedCount() {
      const selectedCount = $(".question-checkbox:checked").length;
      $("#selectedCount").text(`${selectedCount} انتخاب شده`);

      // فعال/غیرفعال کردن دکمه شروع
      $("#startSessionBtn").prop("disabled", selectedCount === 0);
    }

    loadData() {
      $("#contentArea").html(
        '<div class="loading"><i class="fas fa-spinner fa-spin fa-3x"></i><h4>در حال بارگذاری...</h4></div>'
      );

      setTimeout(() => {
        try {
          this.initializeData();
          this.displayContent();
          this.updateStats();
          this.setupCategoryPanel();
        } catch (e) {
          console.error("خطا در بارگذاری داده‌ها:", e);
          $("#contentArea").html(`
                              <div class="alert alert-danger">
                                  <i class="fas fa-exclamation-triangle"></i>
                                  خطا در بارگذاری داده‌ها: ${e.message}
                              </div>
                          `);
        }
      }, 500);
    }

    initializeData() {
      const version = this.selectedDataVersion;

      try {
        // پاک کردن داده‌های قبلی
        if (typeof dbTblQ !== "undefined") {
          dbTblQ = {};
        }
        if (typeof dbTableSets !== "undefined") {
          dbTableSets = {};
        }

        // Initialize data based on version
        if (version === 1) {
          console.log("Loading Data Version 1...");

          if (typeof initDb1TableQuestions === "function") {
            initDb1TableQuestions();
            console.log(
              "Data 1 Questions initialized:",
              Object.keys(dbTblQ || {}).length
            );
          }
          if (typeof initDb1TableSets === "function") {
            initDb1TableSets();
            console.log(
              "Data 1 Sets initialized:",
              Object.keys(dbTableSets || {}).length
            );
          }
          if (
            typeof initQuestionInfoDb1 === "function" &&
            typeof dbTblQ !== "undefined" &&
            Object.keys(dbTblQ).length > 0
          ) {
            initQuestionInfoDb1("de");
            console.log("Data 1 Question infos initialized");
          }

          this.currentData = {
            categories:
              typeof getCategoryTree1Data === "function"
                ? getCategoryTree1Data()
                : [],
            questions: typeof dbTblQ !== "undefined" ? dbTblQ : {},
            sets: typeof dbTableSets !== "undefined" ? dbTableSets : {},
          };
        } else if (version === 2) {
          console.log("Loading Data Version 2...");

          if (typeof initDb2TableQuestions === "function") {
            initDb2TableQuestions();
            console.log(
              "Data 2 Questions initialized:",
              Object.keys(dbTblQ || {}).length
            );
          }
          if (typeof initDb2TableSets === "function") {
            initDb2TableSets();
            console.log(
              "Data 2 Sets initialized:",
              Object.keys(dbTableSets || {}).length
            );
          }
          if (
            typeof initQuestionInfoDb2 === "function" &&
            typeof dbTblQ !== "undefined" &&
            Object.keys(dbTblQ).length > 0
          ) {
            initQuestionInfoDb2("de");
            console.log("Data 2 Question infos initialized");
          }

          this.currentData = {
            categories:
              typeof getCategoryTree2Data === "function"
                ? getCategoryTree2Data()
                : [],
            questions: typeof dbTblQ !== "undefined" ? dbTblQ : {},
            sets: typeof dbTableSets !== "undefined" ? dbTableSets : {},
          };
        }

        console.log("Data initialized successfully:", {
          version: version,
          categories: this.currentData.categories.length,
          questions: Object.keys(this.currentData.questions).length,
          sets: Object.keys(this.currentData.sets).length,
        });
        const allQuestionNumbers = this.getAllFilteredQuestions().map(q => q.number);
        console.log("تمام شماره‌های سوالات:", allQuestionNumbers);
        // تشخیص اینکه واقعاً کدام نسخه لود شده
        const firstQuestionKey = Object.keys(this.currentData.questions)[0];
        if (firstQuestionKey) {
          const firstQuestion =
            this.currentData.questions[firstQuestionKey];
          console.log("First question sample:", {
            key: firstQuestionKey,
            number: firstQuestion.number,
            text: firstQuestion.text
              ? firstQuestion.text.substring(0, 50)
              : "No text",
          });
        }
      } catch (e) {
        console.error("Error in initializeData:", e);
        throw e;
      }
    }

    displayContent() {
      const categories = this.currentData.categories;
      if (!categories || categories.length === 0) {
        $("#contentArea").html(
          '<div class="alert alert-warning">هیچ داده‌ای یافت نشد</div>'
        );
        return;
      }

      let html =
        '<div class="accordion accordion-custom" id="mainAccordion">';

      categories.forEach((mainCategory, index) => {
        html += this.createCategoryAccordion(
          mainCategory,
          `main${index}`,
          "mainAccordion",
          0
        );
      });

      html += "</div>";
      $("#contentArea").html(html);
    }

    createCategoryAccordion(category, accordionId, parentId, level = 0) {
      const hasChildren = category.children && category.children.length > 0;

      const getTotalQuestionCount = (cat) => {
        let count = 0;

        const directQuestions = this.getQuestionsForCategory(cat.id);
        count += directQuestions.length;

        if (cat.children && cat.children.length > 0) {
          cat.children.forEach((child) => {
            count += getTotalQuestionCount(child);
          });
        }

        return count;
      };

      const totalQuestionCount = getTotalQuestionCount(category);

      let html = `
          <div class="accordion-item">
              <h2 class="accordion-header" id="heading${accordionId}">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                          data-bs-target="#collapse${accordionId}" aria-expanded="false"
                          data-category-id="${category.id}">
                      <i class="fas fa-folder me-2"></i>
                      ${this.getCategoryTitle(category)}
                      <span class="badge bg-primary ms-2">${totalQuestionCount} سوال</span>
                  </button>
              </h2>
              <div id="collapse${accordionId}" class="accordion-collapse collapse"
                   data-bs-parent="#${parentId}">
                  <div class="accordion-body">
          `;

      if (hasChildren) {
        html += `<div class="accordion nested-accordion" id="nested${accordionId}">`;
        category.children.forEach((child, childIndex) => {
          html += this.createCategoryAccordion(
            child,
            `${accordionId}_${childIndex}`,
            `nested${accordionId}`,
            level + 1
          );
        });
        html += "</div>";
      } else if (level >= 2) {
        html += `
                <div class="level3-message">
                  <i class="fas fa-info-circle fa-2x mb-3"></i>
                  <h5>دسته‌بندی سطح سوم</h5>
                  <p class="mb-0">برای مشاهده سوالات این دسته از پنل پایین صفحه استفاده کنید</p>
                </div>
              `;
      }

      if (level < 2) {
        const categoryQuestions = this.getQuestionsForCategory(category.id);
        if (categoryQuestions.length > 0) {
          html += '<div class="mt-3">';
          categoryQuestions.forEach((question) => {
            html += this.createQuestionHTML(question);
          });
          html += "</div>";
        }
      }

      html += `
                  </div>
              </div>
          </div>
      `;

      return html;
    }

    getCategoryTitle(category) {
      if (category.titles) {
        return category.titles.DE || category.titles.GB || "نام نامشخص";
      }
      return category.content || "نام نامشخص";
    }

    getQuestionsForCategory(categoryId) {
      const questions = [];
      const dbTblQ = this.currentData.questions;

      for (let key in dbTblQ) {
        const question = dbTblQ[key];

        // بررسی دسته بندی
        if (
          !question.category_id ||
          !question.category_id.includes(`,${categoryId},`)
        ) {
          continue;
        }

        // اعمال فیلتر بر اساس کلاس انتخابی
        if (this.shouldIncludeQuestion(question)) {
          questions.push(question);
        }
      }

      return questions;
    }

    getAllFilteredQuestions() {
      const allQuestions = [];
      const dbTblQ = this.currentData.questions;

      for (let key in dbTblQ) {
        const question = dbTblQ[key];

        if (this.shouldIncludeQuestion(question)) {
          allQuestions.push(question);
        }
      }

      return allQuestions;
    }

    shouldIncludeQuestion(question) {
      // اگر کلاس B (شناسه 6) انتخاب شده است
      if (this.selectedClass === "6") {
        // شرط کلاس: classes باید null، خالی، یا شامل ',6,' باشد
        const classCondition =
          !question.classes ||
          question.classes == "" ||
          question.classes.includes(",6,");

        // شرط basic: NOT (basic = 0 AND basic_mofa = 1)
        const basicCondition = !(
          question.basic == 0 && question.basic_mofa == 1
        );

        return classCondition && basicCondition;
      } else {
        // برای سایر کلاس‌ها
        if (question.classes && question.classes.trim() !== "") {
          // اگر سوال کلاس خاصی دارد، باید شامل کلاس انتخابی باشد
          return question.classes.includes(`,${this.selectedClass},`);
        } else {
          // اگر سوال کلاس خاصی ندارد، برای همه کلاس‌ها مناسب است
          return true;
        }
      }
    }

    createQuestionHTML(question) {
      const decodedText = this.decodeROT13(question.text);
      const info = question.info ? this.decodeROT13(question.info) : "";

      let html = `
                      <div class="question-item">
                          <div class="question-number">${question.number}</div>
                          <div class="question-text mb-3">
                              <strong>${decodedText}</strong>
                          </div>
                  `;

      for (let i = 1; i <= 3; i++) {
        if (question[`asw_${i}`]) {
          const isCorrect = question[`asw_corr${i}`] === 1;
          const cssClass = isCorrect ? "correct" : "incorrect";
          html += `
                              <div class="answer-option ${cssClass}">
                                  <i class="fas ${isCorrect ? "fa-check" : "fa-times"
            } me-2"></i>
                                  ${question[`asw_${i}`]}
                              </div>
                          `;
        }
      }

      if (info) {
        html += `
                          <div class="question-info mt-3">
                              <i class="fas fa-info-circle me-2"></i>
                              <strong>توضیحات:</strong>
                              <div>${info}</div>
                          </div>
                      `;
      }

      html += `
                          <div class="question-meta mt-2">
                              <small class="text-muted">
                                  <i class="fas fa-star"></i> امتیاز: ${question.points
        } |
                                  <i class="fas fa-flag"></i> اساسی: ${question.basic ? "بله" : "خیر"
        }
                              </small>
                          </div>
                      </div>
                  `;

      return html;
    }

    decodeROT13(str) {
      if (!str) return "";
      return str.replace(/[a-zA-Z]/g, function (c) {
        return String.fromCharCode(
          (c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26
        );
      });
    }

    updateStats() {
      const filteredQuestions = this.getAllFilteredQuestions();
      const totalQuestions = filteredQuestions.length;

      const totalCategories = this.countCategories(
        this.currentData.categories
      );

      $("#totalQuestions").text(totalQuestions);
      $("#totalCategories").text(totalCategories);
      $("#dataVersion").text(this.selectedDataVersion);
      $("#statsSection").show();
    }

    countCategories(categories) {
      let count = 0;
      categories.forEach((category) => {
        count++;
        if (category.children) {
          count += this.countCategories(category.children);
        }
      });
      return count;
    }

    setupCategoryPanel() {
      if (
        !this.currentData ||
        !this.currentData.categories ||
        this.currentData.categories.length === 0
      ) {
        console.log("Data not loaded yet, skipping category panel setup");
        return;
      }

      $(document).on(
        "click",
        ".nested-accordion .accordion-button",
        (e) => {
          const accordionId = $(e.currentTarget)
            .attr("data-bs-target")
            .replace("#collapse", "");

          const categoryTitle = $(e.currentTarget)
            .text()
            .trim()
            .replace(/\s*\d+ سوال$/, "");
          const questionCount = $(e.currentTarget).find(".badge").text();

          this.currentCategoryId =
            this.extractCategoryIdFromAccordion(accordionId);

          this.showCategoryPanel(categoryTitle, questionCount, accordionId);

          this.saveSelectedCategoryToCookie(
            accordionId,
            categoryTitle,
            questionCount
          );
        }
      );

      $("#btnClose").on("click", () => {
        this.hideCategoryPanel();
        this.removeCategoryFromCookie();
      });

      $("#btnReview").on("click", () => {
        this.currentMode = "review";
        this.openQuestionSelectionModal();
      });

      $("#btnPractice").on("click", () => {
        this.currentMode = "practice";
        this.openQuestionSelectionModal();
      });

      this.checkCategoryCookie();
    }

    extractCategoryIdFromAccordion(accordionId) {
      if (
        !this.currentData ||
        !this.currentData.categories ||
        !Array.isArray(this.currentData.categories)
      ) {
        console.log("Data not ready for extractCategoryIdFromAccordion");
        return null;
      }

      const category = this.findCategoryByAccordionId(
        accordionId,
        this.currentData.categories
      );
      return category ? category.id : null;
    }

    findCategoryByAccordionId(
      accordionId,
      categories = null,
      currentPath = []
    ) {
      if (!categories) {
        if (!this.currentData || !this.currentData.categories) {
          console.log("Categories data not available");
          return null;
        }
        categories = this.currentData.categories;
      }

      if (!Array.isArray(categories)) {
        console.log("Categories is not an array:", categories);
        return null;
      }

      for (let i = 0; i < categories.length; i++) {
        const category = categories[i];
        let currentAccordionPath = [...currentPath];

        if (currentPath.length === 0) {
          currentAccordionPath.push(`main${i}`);
        } else {
          currentAccordionPath.push(
            `${currentPath[currentPath.length - 1]}_${i}`
          );
        }

        const currentAccordionId =
          currentAccordionPath[currentAccordionPath.length - 1];

        if (currentAccordionId === accordionId) {
          return category;
        }

        if (
          category.children &&
          Array.isArray(category.children) &&
          category.children.length > 0
        ) {
          const found = this.findCategoryByAccordionId(
            accordionId,
            category.children,
            currentAccordionPath
          );
          if (found) return found;
        }
      }
      return null;
    }

    openQuestionSelectionModal() {
      if (!this.currentCategoryId) {
        alert("خطا در شناسایی دسته بندی");
        return;
      }

      const modeText = this.currentMode === "practice" ? "تمرین" : "مرور";
      $("#modalMode").text(modeText);
      $("#sessionType").text(modeText);

      const categoryQuestions = this.getCategoryQuestionsForModal();

      if (categoryQuestions.length === 0) {
        alert("هیچ سوالی برای این دسته بندی یافت نشد");
        return;
      }

      this.populateQuestionsList(categoryQuestions);
      $("#questionSelectModal").modal("show");

      // انتخاب همه سوالات به صورت پیش‌فرض
      $(".question-checkbox").prop("checked", true);
      $(".question-list-item").addClass("selected");
      this.updateSelectedCount();
    }

    getCategoryQuestionsForModal() {
      if (!this.currentCategoryId) {
        console.log("No category ID selected");
        return [];
      }

      console.log(
        "Getting questions for category ID:",
        this.currentCategoryId
      );

      const currentCategory = this.findCategoryById(this.currentCategoryId);

      if (!currentCategory) {
        console.log("Category not found in data structure");
        const directQuestions = this.getQuestionsForCategory(
          this.currentCategoryId
        );
        console.log(
          "Found questions by direct search:",
          directQuestions.length
        );
        return directQuestions;
      }

      console.log("Found category:", currentCategory);

      if (currentCategory.children && currentCategory.children.length > 0) {
        console.log(
          "Category has children, collecting all questions from children..."
        );

        const getAllQuestionsFromChildren = (category) => {
          let questions = [];

          const directQuestions = this.getQuestionsForCategory(category.id);
          console.log(
            `Direct questions for category ${category.id}:`,
            directQuestions.length
          );
          questions = questions.concat(directQuestions);

          if (category.children && category.children.length > 0) {
            category.children.forEach((child) => {
              const childQuestions = getAllQuestionsFromChildren(child);
              console.log(
                `Child questions for category ${child.id}:`,
                childQuestions.length
              );
              questions = questions.concat(childQuestions);
            });
          }

          return questions;
        };

        const allQuestions = getAllQuestionsFromChildren(currentCategory);
        console.log(
          "All questions before deduplication:",
          allQuestions.length
        );

        const uniqueQuestions = [];
        const seenKeys = new Set();

        allQuestions.forEach((question) => {
          const questionKey =
            question.number || question.id || question.key;
          if (!seenKeys.has(questionKey)) {
            seenKeys.add(questionKey);
            uniqueQuestions.push(question);
          }
        });

        console.log(
          "Total unique questions including children:",
          uniqueQuestions.length
        );
        return uniqueQuestions;
      } else {
        const categoryQuestions = this.getQuestionsForCategory(
          this.currentCategoryId
        );
        console.log(
          "Found questions for leaf category:",
          categoryQuestions.length
        );
        return categoryQuestions;
      }
    }

    findCategoryById(categoryId, categories = null) {
      if (!categories) {
        categories = this.currentData.categories;
      }

      for (let category of categories) {
        if (category.id == categoryId) {
          return category;
        }

        if (category.children && category.children.length > 0) {
          const found = this.findCategoryById(
            categoryId,
            category.children
          );
          if (found) return found;
        }
      }
      return null;
    }

    populateQuestionsList(questions) {
      let html = "";

      questions.forEach((question, index) => {
        const decodedText = this.decodeROT13(question.text);
        const truncatedText =
          decodedText.length > 100
            ? decodedText.substring(0, 100) + "..."
            : decodedText;

        const questionValue =
          question.id || question.number || question.key || index;

        html += `
                <div class="question-list-item" data-question-id="${questionValue}">
                  <div class="d-flex align-items-center">
                    <input type="checkbox" class="question-checkbox me-3" value="${questionValue}">
                    <div class="flex-grow-1">
                      <div class="fw-bold mb-1">
                        <span class="badge bg-primary me-2">${question.number
          }</span>
                        <span class="badge bg-secondary me-2">${question.points
          } امتیاز</span>
                        ${question.basic
            ? '<span class="badge bg-warning">اساسی</span>'
            : ""
          }
                      </div>
                      <div class="question-text-preview">${truncatedText}</div>
                    </div>
                  </div>
                </div>
              `;
      });

      $("#questionsList").html(html);
      this.updateSelectedCount();

      console.log(
        "Populated questions list with",
        questions.length,
        "questions"
      );
    }

    startSession() {
      const selectedQuestionIds = [];
      $(".question-checkbox:checked").each(function () {
        const questionId = $(this).val();
        selectedQuestionIds.push(questionId);
      });

      if (selectedQuestionIds.length === 0) {
        alert("لطفاً حداقل یک سوال انتخاب کنید");
        return;
      }

      const startBtn = $("#startSessionBtn");
      const originalText = startBtn.html();
      startBtn
        .html('<i class="fas fa-spinner fa-spin me-2"></i>در حال پردازش...')
        .prop("disabled", true);

      try {
        const availableQuestions = this.getCategoryQuestionsForModal();
        const selectedQuestions = [];

        selectedQuestionIds.forEach((questionId) => {
          const question = availableQuestions.find(
            (q) =>
              q.id == questionId ||
              q.number == questionId ||
              q.key == questionId ||
              String(q.id) === String(questionId) ||
              String(q.number) === String(questionId)
          );

          if (question) {
            const completeQuestion = {
              id: question.id || question.number,
              number: question.number,
              text: this.decodeROT13(question.text),
              info: question.info ? this.decodeROT13(question.info) : "",
              points: question.points,
              basic: question.basic,
              category_id: question.category_id,
              classes: question.classes,
              asw_pretext: question.asw_pretext,
              answers: [
                {
                  text: question.asw_1,
                  isCorrect: question.asw_corr1 === 1,
                },
                {
                  text: question.asw_2,
                  isCorrect: question.asw_corr2 === 1,
                },
                {
                  text: question.asw_3,
                  isCorrect: question.asw_corr3 === 1,
                },
              ].filter((answer) => answer.text),
              asw_1: question.asw_1,
              asw_2: question.asw_2,
              asw_3: question.asw_3,
              asw_type_1: question.asw_type_1,
              asw_hint_1: question.asw_hint_1,
              asw_corr1: question.asw_corr1,
              asw_corr2: question.asw_corr2,
              asw_corr3: question.asw_corr3,
              correctAnswer: question.asw_1,
              picture: question.picture,
            };

            selectedQuestions.push(completeQuestion);
          }
        });

        const sessionData = {
          mode: this.currentMode,
          questions: selectedQuestions,
          categoryId: this.currentCategoryId,
          selectedClass: this.selectedClass,
          dataVersion: this.selectedDataVersion,
          timestamp: new Date().toISOString(),
          questionCount: selectedQuestions.length,
        };

        $("#questionSelectModal").modal("hide");
        this.redirectToPhp(sessionData);

        console.log("آرایه سوالات انتخاب شده:", sessionData);
        console.log(`تعداد سوالات انتخاب شده: ${selectedQuestions.length}`);
        console.log(
          `نوع جلسه: ${this.currentMode === "practice" ? "تمرین" : "مرور"}`
        );
      } catch (error) {
        console.error("Error in startSession:", error);
        alert(`خطا در پردازش: ${error.message}`);
        startBtn.html(originalText).prop("disabled", false);
      }
    }

    redirectToPhp(sessionData) {
      const phpFileName =
        this.currentMode === "practice" ? "application/test.php" : "application/test.php";

      const form = $("<form>")
        .attr("method", "POST")
        .attr("action", phpFileName)
        .hide();

      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "session_data")
          .val(JSON.stringify(sessionData))
      );
      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "mode")
          .val(sessionData.mode)
      );
      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "category_id")
          .val(sessionData.categoryId)
      );
      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "class_id")
          .val(sessionData.selectedClass)
      );
      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "data_version")
          .val(sessionData.dataVersion)
      );
      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "question_count")
          .val(sessionData.questionCount)
      );
      form.append(
        $("<input>")
          .attr("type", "hidden")
          .attr("name", "questions")
          .val(JSON.stringify(sessionData.questions))
      );

      $("body").append(form);
      form.submit();
    }

    getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2)
        return decodeURIComponent(parts.pop().split(";").shift());
    }

    showCategoryPanel(title, stats, categoryId) {
      $("#panelCategoryTitle").text(title);
      $("#panelCategoryStats").text(stats);
      $("#categoryPanel").addClass("show");
      $("#categoryPanel").data("category-id", categoryId);

      this.currentCategoryId =
        this.extractCategoryIdFromAccordion(categoryId);
      console.log(
        "Category panel shown, currentCategoryId set to:",
        this.currentCategoryId
      );
    }

    hideCategoryPanel() {
      $("#categoryPanel").removeClass("show");
      this.currentCategoryId = null;
    }

    saveSelectedCategoryToCookie(categoryId, title, stats) {
      const categoryData = {
        accordionId: categoryId,
        title: title,
        stats: stats,
        timestamp: new Date().getTime(),
      };
      const cookieValue = encodeURIComponent(JSON.stringify(categoryData));
      document.cookie = `selectedCategory=${cookieValue}; path=/; max-age=${60 * 60 * 24 * 7
        }`;

      console.log("Saved category to cookie:", categoryData);
    }

    removeCategoryFromCookie() {
      document.cookie =
        "selectedCategory=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }

    checkCategoryCookie() {
      if (
        !this.currentData ||
        !this.currentData.categories ||
        !Array.isArray(this.currentData.categories)
      ) {
        console.log(
          "Data not ready for checking category cookie, skipping..."
        );
        return;
      }

      const cookieValue = this.getCookie("selectedCategory");
      if (!cookieValue) {
        console.log("No category cookie found");
        return;
      }

      try {
        const categoryData = JSON.parse(cookieValue);
        console.log("Found category in cookie:", categoryData);

        if (new Date().getTime() - categoryData.timestamp < 86400000) {
          const actualCategoryId = this.extractCategoryIdFromAccordion(
            categoryData.accordionId
          );

          if (actualCategoryId) {
            this.showCategoryPanel(
              categoryData.title,
              categoryData.stats,
              categoryData.accordionId
            );

            this.currentCategoryId = actualCategoryId;
            console.log(
              "Restored from cookie - currentCategoryId:",
              this.currentCategoryId
            );
          } else {
            console.log(
              "Could not extract category ID from accordion ID, removing cookie"
            );
            this.removeCategoryFromCookie();
          }
        } else {
          console.log("Category cookie expired, removing...");
          this.removeCategoryFromCookie();
        }
      } catch (e) {
        console.error("Error parsing category cookie:", e);
        this.removeCategoryFromCookie();
      }
    }
  }

  $(document).ready(() => {
    new DrivingLicenseApp();
  });
</script> -->
</body>

</html>