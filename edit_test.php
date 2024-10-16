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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id = $_POST['test_id'] ?? '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $job_title = $_POST['job_title'] ?? ''; // Получаем выбранную должность
    $questions = $_POST['questions'] ?? [];

    // Обновление теста
    if (!empty($test_id) && !empty($title) && !empty($job_title) && !empty($questions)) {
        $stmt = $pdo->prepare("UPDATE tests SET title = ?, job_title = ? WHERE id = ?");
        if ($stmt->execute([$title, $job_title, $test_id])) {
            // Обновление вопросов
            foreach ($questions as $question_id => $question_data) {
                $question_text = isset($question_data['text']) ? trim($question_data['text']) : '';

                if (isset($question_data['delete']) && $question_data['delete'] == '1') {
                    // Удаление вопроса
                    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
                    $stmt->execute([$question_id]);
                } else {
                    // Обновление вопроса
                    $stmt = $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
                    $stmt->execute([$question_text, $question_id]);

                    // Обновление вариантов ответов
                    foreach ($question_data['answers'] as $answer_id => $answer_data) {
                        if (isset($answer_data['delete']) && $answer_data['delete'] == '1') {
                            // Удаление ответа
                            $stmt = $pdo->prepare("DELETE FROM answers WHERE id = ?");
                            $stmt->execute([$answer_id]);
                        } else {
                            $answer_text = isset($answer_data['text']) ? trim($answer_data['text']) : '';
                            $is_correct = isset($answer_data['is_correct']) ? 1 : 0;
                            $stmt = $pdo->prepare("UPDATE answers SET answer_text = ?, is_correct = ? WHERE id = ?");
                            $stmt->execute([$answer_text, $is_correct, $answer_id]);
                        }
                    }
                }
            }

            // Перенаправление на админ панель после успешного обновления
            header("Location: admin.php"); // Замените на фактический URL вашей админ панели
            exit();
        } else {
            $message = "<p class='error'>Ошибка при обновлении теста.</p>";
        }
    } else {
        $message = "<p class='error'>Пожалуйста, заполните все поля.</p>";
    }
}

// Получаем список тестов
$stmt = $pdo->query("SELECT * FROM tests");
$tests = $stmt->fetchAll();

// Получаем уникальные должности для выбора
$stmt = $pdo->query("SELECT DISTINCT job_title FROM users");
$job_titles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Если выбран тест, получаем его вопросы
$selected_test_id = $_POST['test_id'] ?? '';
$questions = [];
$selected_job_title = '';
if ($selected_test_id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
    $stmt->execute([$selected_test_id]);
    $questions = $stmt->fetchAll();

    // Получаем информацию о тесте
    $stmt = $pdo->prepare("SELECT job_title FROM tests WHERE id = ?");
    $stmt->execute([$selected_test_id]);
    $selected_job_title = $stmt->fetchColumn();

    // Получаем варианты ответов для каждого вопроса
    foreach ($questions as &$question) {
        $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
        $stmt->execute([$question['id']]);
        $question['answers'] = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать тест</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // Функция для добавления нового ответа
        function addAnswer(questionId) {
            const answersContainer = document.getElementById(`answers_${questionId}`);
            const answerIndex = answersContainer.children.length; // Индекс следующего варианта

            const answerDiv = document.createElement('div');
            answerDiv.innerHTML = `
                <input type="text" name="questions[${questionId}][answers][new_${answerIndex}][text]" placeholder="Ответ ${answerIndex + 1}" required>
                <label><input type="checkbox" name="questions[${questionId}][answers][new_${answerIndex}][is_correct]"> Правильный ответ</label>
                <button type="button" onclick="removeAnswer(this)">Удалить</button>
            `;
            answersContainer.appendChild(answerDiv);
        }

        // Функция для удаления варианта ответа
        function removeAnswer(button) {
            button.parentElement.remove();
        }

        // Функция для удаления вопроса
        function removeQuestion(questionId) {
            document.getElementById(`question_${questionId}`).style.display = 'none';
            document.getElementById(`question_${questionId}_delete`).value = '1';
        }
    </script>
    <style>
        #questions {
            max-height: 400px; /* Максимальная высота для области с вопросами */
            overflow-y: auto; /* Включение вертикальной прокрутки */
            border: 1px solid #ccc; /* Добавление границы */
            padding: 10px; /* Внутренние отступы */
            margin-bottom: 20px; /* Отступ внизу */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Редактировать тест</h1>
        <?= $message ?>
        <form method="POST" action="edit_test.php">
            <select name="test_id" onchange="this.form.submit()">
                <option value="">Выберите тест</option>
                <?php foreach ($tests as $test): ?>
                    <option value="<?= $test['id'] ?>" <?= $test['id'] == $selected_test_id ? 'selected' : '' ?>><?= $test['title'] ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selected_test_id): ?>
            <form method="POST" action="edit_test.php">
                <input type="hidden" name="test_id" value="<?= $selected_test_id ?>">
                <input type="text" name="title" placeholder="Название теста" value="<?= htmlspecialchars($questions[0]['test_title'] ?? '') ?>" required>
                
                <label for="job_title">Должность:</label>
                <select name="job_title" required>
                    <option value="">Выберите должность</option>
                    <option value="available_to_all" <?= $selected_job_title === 'available_to_all' ? 'selected' : '' ?>>Доступно всем</option>
                    <?php foreach ($job_titles as $job_title): ?>
                        <option value="<?= htmlspecialchars($job_title) ?>" <?= $selected_job_title === $job_title ? 'selected' : '' ?>><?= htmlspecialchars($job_title) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="questions">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question" id="question_<?= $question['id'] ?>">
                            <h3>Вопрос <?= $index + 1 ?></h3>
                            <input type="text" name="questions[<?= $question['id'] ?>][text]" value="<?= htmlspecialchars($question['question_text']) ?>" placeholder="Текст вопроса" required>
                            <input type="hidden" id="question_<?= $question['id'] ?>_delete" name="questions[<?= $question['id'] ?>][delete]" value="0">
                            <div id="answers_<?= $question['id'] ?>">
                                <h4>Варианты ответов</h4>
                                <?php foreach ($question['answers'] as $answerIndex => $answer): ?>
                                    <div>
                                        <input type="text" name="questions[<?= $question['id'] ?>][answers][<?= $answer['id'] ?>][text]" value="<?= htmlspecialchars($answer['answer_text']) ?>" placeholder="Ответ" required>
                                        <label><input type="checkbox" name="questions[<?= $question['id'] ?>][answers][<?= $answer['id'] ?>][is_correct]" <?= $answer['is_correct'] ? 'checked' : '' ?>> Правильный ответ</label>
                                        <button type="button" onclick="removeAnswer(this)">Удалить</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addAnswer(<?= $question['id'] ?>)">Добавить вариант ответа</button>
                            <button type="button" onclick="removeQuestion(<?= $question['id'] ?>)">Удалить вопрос</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Сохранить изменения</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
