<?php
session_start();
require_once 'db.php'; // Подключаем файл с подключением к базе данных

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user'];

try {
    // Получаем информацию о пользователе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin') {
        header("Location: profile.php");
        exit();
    }

// Получаем результаты тестирования из таблицы results
$stmt = $pdo->query("SELECT results.id, CONCAT(users.first_name, ' ', users.last_name) AS username, tests.title, results.score, results.passed, results.completed_at 
                     FROM results 
                     JOIN users ON results.user_id = users.id 
                     JOIN tests ON results.test_id = tests.id");
    $results = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Ошибка подключения к базе данных: " . htmlspecialchars($e->getMessage());
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр результатов</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Просмотр результатов</h1>
        <table>
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Тест</th>
                    <th>Результат</th>
                    <th>Прохождение</th>
                    <th>Дата завершения</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['username']); ?></td>
                            <td><?php echo htmlspecialchars($result['title']); ?></td>
                            <td><?php echo htmlspecialchars($result['score']); ?></td>
                            <td><?php echo $result['passed'] ? 'Да' : 'Нет'; ?></td>
                            <td><?php echo htmlspecialchars($result['completed_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Нет результатов для отображения</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="admin.php">Назад</a>
    </div>
</body>
</html>
