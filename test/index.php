<?php include("../config/config.php");

if (isset($_POST['run_sync'])) {

    // 1) UPDATE: موجود در q ولی نه در q2
    $updateSql = "
        UPDATE questions q
        LEFT JOIN questions2 q2 ON q.number = q2.number
        SET q.available = 1
        WHERE q2.number IS NULL
    ";
    $updatedRows = $pdo->exec($updateSql);

    // 2) INSERT: موجود در q2 ولی نه در q
    $insertSql = "
        INSERT INTO questions
        (
            `number`, `picture`, `stvo`, `asw_pretext`, `points`,
            `basic`, `basic_mofa`, `mq_flag`,
            `category_id`, `classes`, `text`, `available`
        )
        SELECT
            q2.`number`, q2.`picture`, q2.`stvo`, q2.`asw_pretext`, q2.`points`,
            q2.`basic`, q2.`basic_mofa`, q2.`mq_flag`,
            q2.`category_id`, q2.`classes`, q2.`text`,
            2 AS available
        FROM questions2 q2
        LEFT JOIN questions q ON q2.number = q.number
        WHERE q.number IS NULL
    ";
    $insertedRows = $pdo->exec($insertSql);

    $done = true;
}

// --------------------
// دریافت اختلاف‌ها (برای نمایش)
// --------------------

// فقط در questions
$onlyInQ = $pdo->query("
    SELECT q.number
    FROM questions q
    LEFT JOIN questions2 q2 ON q.number = q2.number
    WHERE q2.number IS NULL
")->fetchAll(PDO::FETCH_COLUMN);

// فقط در questions2
$onlyInQ2 = $pdo->query("
    SELECT q2.number
    FROM questions2 q2
    LEFT JOIN questions q ON q2.number = q.number
    WHERE q.number IS NULL
")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <title>همگام‌سازی سوالات</title>
    <style>
        body {
            font-family: Tahoma;
            direction: rtl;
            padding: 20px;
        }

        h2 {
            margin-top: 30px;
        }

        ul {
            background: #f7f7f7;
            padding: 15px;
        }

        button {
            padding: 10px 25px;
            font-size: 15px;
        }

        .success {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h1>مقایسه و همگام‌سازی questions و questions2</h1>

    <?php if (!empty($done)): ?>
        <p class="success">
            ✅ عملیات انجام شد<br>
            🔹 تعداد آپدیت شده‌ها: <?= $updatedRows ?><br>
            🔹 تعداد اینزرت شده‌ها: <?= $insertedRows ?>
        </p>
    <?php endif; ?>

    <h2>📌 موجود در questions ولی نه در questions2 (available = 1)</h2>
    <p>تعداد: <?= count($onlyInQ) ?></p>
    <ul>
        <?php foreach ($onlyInQ as $n): ?>
            <li><?= htmlspecialchars($n) ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>📌 موجود در questions2 ولی نه در questions (available = 2)</h2>
    <p>تعداد: <?= count($onlyInQ2) ?></p>
    <ul>
        <?php foreach ($onlyInQ2 as $n): ?>
            <li><?= htmlspecialchars($n) ?></li>
        <?php endforeach; ?>
    </ul>

    <form method="post" onsubmit="return confirm('آیا از انجام عملیات همگام‌سازی مطمئن هستید؟');">
        <button type="submit" name="run_sync">
            🚀 اجرای همگام‌سازی
        </button>
    </form>

</body>

</html>