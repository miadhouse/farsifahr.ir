<?php
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM exam_history WHERE user_id = ? ORDER BY exam_date DESC");
$stmt->execute([$user_id]);
$exams = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="card">
        <h5 class="card-header"><?= __('exam_simulator') ?></h5>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= __('date') ?></th>
                        <th><?= __('score') ?></th>
                        <th><?= __('correct_answers') ?></th>
                        <th><?= __('status') ?></th>
                        <th><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php if (empty($exams)): ?>
                        <tr>
                            <td colspan="6" class="text-center"><?= __('no_records_found') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exams as $index => $exam): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= $exam['exam_date'] ?></td>
                                <td><?= $exam['error_points'] ?> نمره منفی</td>
                                <td><?= $exam['correct_count'] ?> از <?= $exam['total_questions'] ?></td>
                                <td>
                                    <?php if ($exam['passed']): ?>
                                        <span class="badge bg-label-success"><?= __('passed') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-label-danger"><?= __('failed') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $wrongIds = json_decode($exam['wrong_questions'], true);
                                    if (!empty($wrongIds)): 
                                        $idsStr = implode(',', $wrongIds);
                                    ?>
                                        <a href="../app/index.php?mode=practice&questions=<?= $idsStr ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-pencil me-1"></i> تمرین اشتباهات
                                        </a>
                                        <a href="../app/index.php?mode=review&questions=<?= $idsStr ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bx bx-show me-1"></i> مرور اشتباهات
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">بدون اشتباه</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
