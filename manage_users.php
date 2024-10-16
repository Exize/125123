<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user'];

// Получаем информацию о текущем пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user['role'] != 'admin') {
    header("Location: profile.php");
    exit();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $job_title = $_POST['job_title']; // Получаем значение должности
    $role = $_POST['role'];

    // Обновляем информацию о пользователе, включая изменение должности
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, job_title = ?, role = ? WHERE id = ?");
    if ($stmt->execute([$first_name, $last_name, $email, $job_title, $role, $user_id])) {
        echo "<p class='success'>Пользователь успешно обновлен.</p>";
    } else {
        echo "<p class='error'>Ошибка при обновлении пользователя.</p>";
    }
}

// Получаем всех пользователей для отображения в форме
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Управление пользователями</h1>
        <form method="POST" action="manage_users.php">
            <!-- Выбор пользователя для редактирования -->
            <select name="user_id" required>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo $user['first_name'] . " " . $user['last_name']; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Поля для редактирования имени, фамилии, почты -->
            <input type="text" name="first_name" placeholder="Новое имя" required>
            <input type="text" name="last_name" placeholder="Новая фамилия" required>
            <input type="email" name="email" placeholder="Новая электронная почта" required>

            <!-- Поле для выбора новой должности -->
            <select name="job_title" required>
                <option value="главный бухгалтер">Главный бухгалтер</option>
                <option value="отдел кадров">Отдел кадров</option>
                <option value="смм специалист">СММ специалист</option>
                <option value="руководитель отдела">Руководитель отдела</option>
                <option value="программист">Программист</option>
            </select>

            <!-- Поле для выбора роли пользователя -->
            <select name="role" required>
                <option value="user">Пользователь</option>
                <option value="admin">Администратор</option>
            </select>

            <button type="submit">Обновить пользователя</button>
        </form>
        <a href="admin.php">Назад</a>
    </div>
</body>
</html>
