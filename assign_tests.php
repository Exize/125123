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
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// Проверка роли пользователя
if ($user['role'] != 'admin') {
    header("Location: profile.php");
    exit();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_title = $_POST['job_title'];
    $test_id = $_POST['test_id'];

    // Проверка корректности входных данных
    if (!is_numeric($test_id)) {
        echo "<p class='error'>Некорректные данные.</p>";
    } else {
        // Вставка данных в базу для каждой профессии
        $stmt = $pdo->prepare("UPDATE tests SET job_title = :job_title WHERE id = :test_id");
        if ($stmt->execute(['job_title' => $job_title, 'test_id' => $test_id])) {
            echo "<p class='success'>Тест успешно назначен для профессии: $job_title.</p>";
        } else {
            echo "<p class='error'>Ошибка при назначении теста.</p>";
        }
    }
}

// Получение списка профессий
$job_titles = [
    'главный бухгалтер',
    'отдел кадров',
    'смм специалист',
    'руководитель отдела',
    'программист'
];

// Получение списка тестов
$stmt = $pdo->query("SELECT id, title FROM tests"); // Используйте правильное название поля
$tests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Назначить тест</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Назначить тест</h1>
        <form method="POST" action="assign_tests.php">
            <label for="job_title">Выберите профессию:</label>
            <select name="job_title" id="job_title" required>
                <?php foreach ($job_titles as $title): ?>
                    <option value="<?php echo $title; ?>"><?php echo htmlspecialchars($title); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="test_id">Выберите тест:</label>
            <select name="test_id" id="test_id" required>
                <?php foreach ($tests as $test): ?>
                    <option value="<?php echo $test['id']; ?>"><?php echo htmlspecialchars($test['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Назначить тест</button>
        </form>
        <a href="admin.php">Назад</a>
    </div>
</body>
</html>
