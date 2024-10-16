<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Проверка на существующего пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $email;
        header("Location: profile.php");
        exit();
    } else {
        $error = "Неверные учетные данные.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Авторизация</h1>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="email">Электронная почта:</label>
            <input type="email" id="email" name="email" placeholder="Введите вашу почту" required>
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
            <button type="submit">Войти</button>
        </form>
        <a href="index.php">На главную</a>
    </div>
</body>
</html> 
