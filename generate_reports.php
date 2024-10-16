<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user'];

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user['role'] != 'admin') {
    header("Location: profile.php");
    exit();
}

// Получаем статистику по результатам тестирования из таблицы results
$stmt = $pdo->query("SELECT tests.title, AVG(results.score) AS avg_score, COUNT(results.id) AS total_attempts 
                     FROM results 
                     JOIN tests ON results.test_id = tests.id 
                     GROUP BY tests.id");
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Построение отчетов</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Построение отчетов</h1>
        <table>
            <thead>
                <tr>
                    <th>Тест</th>
                    <th>Средний результат</th>
                    <th>Общее количество попыток</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reports): ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['title']); ?></td>
                            <td><?php echo number_format($report['avg_score'], 2); ?></td>
                            <td><?php echo number_format($report['total_attempts']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Нет данных для отображения</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="admin.php">Назад</a>
    </div>
</body>
</html>
