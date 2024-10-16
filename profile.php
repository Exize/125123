<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user']; // Получаем email из сессии

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Если пользователь не найден, перенаправляем на страницу входа или выводим сообщение об ошибке
    echo "Пользователь не найден. Пожалуйста, проверьте ваши учетные данные.";
    exit();
}

// Получаем список всех доступных тестов для пользователя
$stmt = $pdo->prepare("SELECT * FROM tests WHERE job_title = ? OR job_title = 'available to all'");
$stmt->execute([$user['job_title']]);
$tests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Профиль</h1>
        <div id="profile-info">
            <p><strong>Имя пользователя:</strong> 
               <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
            </p>
            <p><strong>Электронная почта:</strong> 
               <?php echo htmlspecialchars($user['email']); ?>
            </p>
            <p><strong>Роль:</strong> 
               <?php echo htmlspecialchars($user['role']); ?>
            </p>
        </div>
        <button id="start-test">Начать тест</button>
        <div id="test-selection" style="display: none;">
            <h2>Выберите тест:</h2>
            <ul>
                <?php foreach ($tests as $test): ?>
                    <li><a href="take_test.php?test_id=<?php echo $test['id']; ?>"><?php echo htmlspecialchars($test['title']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php if ($user['role'] == 'admin'): ?>
            <a href="admin.php" class="admin-link">Вход в админ-панель</a>
        <?php endif; ?>
        <form action="logout.php" method="post">
            <button type="submit">Выйти</button>
        </form>
    </div>
    <script src="profile.js"></script>
    <script>
    document.getElementById('start-test').addEventListener('click', function() {
        document.getElementById('test-selection').style.display = 'block';
    });
    </script>
</body>
</html>
