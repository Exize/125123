<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user']; // Получаем email пользователя из сессии

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || $user['role'] != 'admin') {
    header("Location: profile.php");
    exit();
}

// Удаление теста, если было отправлено действие
if (isset($_GET['delete_test_id'])) {
    $test_id = $_GET['delete_test_id'];

    // Удаляем все ответы, связанные с вопросами данного теста
    $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id IN (SELECT id FROM questions WHERE test_id = ?)");
    $stmt->execute([$test_id]);

    // Удаляем все вопросы, связанные с тестом
    $stmt = $pdo->prepare("DELETE FROM questions WHERE test_id = ?");
    $stmt->execute([$test_id]);

    // Удаляем сам тест
    $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ?");
    $stmt->execute([$test_id]);

    header("Location: admin.php");
    exit();
}

// Получаем список тестов для отображения
$stmt = $pdo->query("SELECT * FROM tests");
$tests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Админ панель</h1>
        <div id="admin-functions">
            <h2>Функции администратора</h2>
            <ul>
                <li><a href="create_test.php">Создать тест</a></li>
                <li><a href="edit_test.php">Редактировать тест</a></li>
                <li><a href="manage_users.php">Управление пользователями</a></li>
                <li><a href="assign_tests.php">Назначить тесты</a></li>
                <li><a href="view_results.php">Просмотр результатов</a></li>
                <li><a href="generate_reports.php">Построение отчетов</a></li>
            </ul>
        </div>

        <!-- Список тестов с возможностью удаления -->
        <h2>Список тестов</h2>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Название теста</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($tests as $test): ?>
                <tr>
                    <td><?= $test['id'] ?></td>
                    <td><?= htmlspecialchars($test['title']) ?></td>
                    <td>
    <a href="admin.php?delete_test_id=<?= $test['id'] ?>" class="delete-button" onclick="return confirm('Вы уверены, что хотите удалить этот тест?')">Удалить</a>
</td>
                </tr>
            <?php endforeach; ?>
        </table>

        <a href="profile.php">Назад</a>
    </div>
</body>
</html>
