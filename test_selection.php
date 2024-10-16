<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user'];

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Получаем список всех доступных тестов
$stmt = $pdo->prepare("SELECT * FROM tests");
$stmt->execute();
$tests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор теста</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Выбор теста</h1>
        <ul>
            <?php foreach ($tests as $test): ?>
                <li><a href="take_test.php?test_id=<?php echo $test['id']; ?>"><?php echo htmlspecialchars($test['title']); ?></a></li>
            <?php endforeach; ?>
        </ul>
        <a href="profile.php">В профиль</a>
    </div>
</body>
</html>