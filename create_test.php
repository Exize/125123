<?php
session_start();
require 'db.php'; // Подключаем файл с подключением к базе данных

// Проверка, что пользователь авторизован и является администратором
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user']; // Получаем email пользователя из сессии

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Проверка роли
if ($user['role'] != 'admin') {
    header("Location: profile.php");
    exit();
}

$message = "";

// Получаем список профессий для выпадающего меню
$job_titles_stmt = $pdo->query("SELECT DISTINCT job_title FROM users");
$job_titles = $job_titles_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $job_title = $_POST['job_title'] ?? null;
    $questions = $_POST['questions'] ?? [];

    if (!empty($title) && !empty($job_title) && !empty($questions)) {
        // Вставляем новый тест
        $stmt = $pdo->prepare("INSERT INTO tests (title, job_title) VALUES (?, ?)");
        if ($stmt->execute([$title, $job_title])) {
            $test_id = $pdo->lastInsertId(); // Получаем ID вставленного теста

            // Вставляем вопросы и варианты ответов
            foreach ($questions as $question) {
                $question_text = trim($question['text']);
                $stmt = $pdo->prepare("INSERT INTO questions (test_id, question_text) VALUES (?, ?)");
                $stmt->execute([$test_id, $question_text]);
                $question_id = $pdo->lastInsertId(); // Получаем ID вставленного вопроса

                // Вставляем варианты ответов
                foreach ($question['answers'] as $answer) {
                    $answer_text = trim($answer['text']);
                    $is_correct = isset($answer['is_correct']) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $answer_text, $is_correct]);
                }
            }

            $message = "<p class='success'>Тест и вопросы успешно добавлены.</p>";
        } else {
            $message = "<p class='error'>Ошибка при создании теста.</p>";
        }
    } else {
        $message = "<p class='error'>Пожалуйста, заполните все поля.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать тест</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function addQuestion() {
            const questionContainer = document.getElementById('questions');
            const questionIndex = questionContainer.children.length;

            const questionDiv = document.createElement('div');
            questionDiv.classList.add('question');
            questionDiv.innerHTML = `
                <h3>Вопрос ${questionIndex + 1} <button type="button" onclick="removeQuestion(this)">Удалить вопрос</button></h3>
                <input type="text" name="questions[${questionIndex}][text]" placeholder="Текст вопроса" required>
                <div class="answers" data-question-index="${questionIndex}">
                    <h4>Варианты ответов</h4>
                    ${generateAnswerFields(questionIndex)}
                </div>
                <button type="button" onclick="addAnswer(this, ${questionIndex})">Добавить вариант ответа</button>
            `;
            questionContainer.appendChild(questionDiv);
        }

        function generateAnswerFields(questionIndex) {
            let answerFields = '';
            for (let i = 0; i < 4; i++) {
                answerFields += `
                    <div>
                        <input type="text" name="questions[${questionIndex}][answers][${i}][text]" placeholder="Ответ ${i + 1}" required>
                        <label><input type="checkbox" name="questions[${questionIndex}][answers][${i}][is_correct]"> Правильный ответ</label>
                        <button type="button" onclick="removeAnswer(this)">Удалить</button>
                    </div>
                `;
            }
            return answerFields;
        }

        function addAnswer(button, questionIndex) {
            const answersContainer = button.parentElement;
            const answerCount = answersContainer.children.length - 1; // Number of current answer fields

            const answerDiv = document.createElement('div');
            answerDiv.innerHTML = `
                <input type="text" name="questions[${questionIndex}][answers][${answerCount}][text]" placeholder="Ответ ${answerCount + 5}" required>
                <label><input type="checkbox" name="questions[${questionIndex}][answers][${answerCount}][is_correct]"> Правильный ответ</label>
                <button type="button" onclick="removeAnswer(this)">Удалить</button>
            `;
            answersContainer.insertBefore(answerDiv, button); // Insert before the button
        }

        function removeAnswer(button) {
            button.parentElement.remove();
        }

        function removeQuestion(button) {
            button.closest('.question').remove();
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
        <h1>Создать тест</h1>
        <?= $message ?>
        <form method="POST" action="create_test.php">
            <input type="text" name="title" placeholder="Название теста" required>
            <select name="job_title" required>
                <option value="">Выберите профессию</option>
                <?php foreach ($job_titles as $job): ?>
                    <option value="<?= htmlspecialchars($job) ?>"><?= htmlspecialchars($job) ?></option>
                <?php endforeach; ?>
                <option value="доступно всем">Доступно всем</option>
            </select>
            <div id="questions"></div>
            <button type="button" onclick="addQuestion()">Добавить вопрос</button>
            <button type="submit">Создать тест</button>
        </form>
        <a href="admin.php" style="color: white;">Назад</a>
    </div>
</body>
</html>
