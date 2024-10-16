<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $password = $_POST['password'];
    $email = $_POST['email']; // Получаем введённый email
    $job_title = $_POST['job_title']; // Получаем выбранную работу
    $role_password = $_POST['role_password']; // Пароль для выбора роли администратора
    $role = 'user'; // По умолчанию роль пользователя

    // Проверка на роль администратора
    if (!empty($role_password)) {
        if ($role_password === '111') {
            $role = 'admin';
        } else {
            $error = "Неверный пароль для роли администратора.";
        }
    }

    // Проверка на существующего пользователя или email
    if (!isset($error)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $error = "Пользователь с таким email уже существует.";
        } else {
            // Хэшируем пароль для безопасности
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, password, email, role, job_title) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$first_name, $last_name, $hashed_password, $email, $role, $job_title])) {
                $_SESSION['user'] = $email; // Сохраняем email в сессию
                if ($role === 'admin') {
                    header("Location: admin.php"); // Перенаправляем на страницу администратора
                } else {
                    header("Location: profile.php"); // Перенаправляем на профиль пользователя
                }
                exit();
            } else {
                $error = "Ошибка при регистрации.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <input type="text" name="first_name" placeholder="Имя" required>
            <input type="text" name="last_name" placeholder="Фамилия" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <input type="email" name="email" placeholder="Электронная почта" required>
            <select name="job_title" required>
                <option value="главный бухгалтер">Главный бухгалтер</option>
                <option value="отдел кадров">Отдел кадров</option>
                <option value="смм специалист">СММ специалист</option>
                <option value="руководитель отдела">Руководитель отдела</option>
                <option value="программист">Программист</option>
            </select>
            <input type="password" name="role_password" placeholder="Пароль администратора (если требуется)">
            <button type="submit">Зарегистрироваться</button>
        </form>
        <a href="index.php">На главную</a>
    </div>
</body>
</html>