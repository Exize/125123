<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || !isset($_GET['test_id'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user'];
$test_id = $_GET['test_id'];

try {
    // Получаем информацию о пользователе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Получаем результаты теста для пользователя
    $stmt = $pdo->prepare("SELECT * FROM results WHERE user_id = ? AND test_id = ?");
    $stmt->execute([$user['id'], $test_id]);
    $result = $stmt->fetch();

    if (!$result) {
        header("Location: profile.php");
        exit();
    }

    // Получаем название теста
    $stmt = $pdo->prepare("SELECT title FROM tests WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch();

    // Получаем вопросы для теста
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Ошибка: " . htmlspecialchars($e->getMessage());
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты теста</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .results-container {
            max-height: 60vh;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #444;
            background-color: #1e1e1e;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Результаты теста: <?php echo htmlspecialchars($test['title']); ?></h1>

        <div class="results-container">
            <?php if (empty($questions)): ?>
                <p>Нет данных по вопросам этого теста.</p>
            <?php else: ?>
                <ul>
                    <?php
                    $correct_answers = 0;

                    foreach ($questions as $question) {
                        echo "<li><strong>Вопрос: " . htmlspecialchars($question['question_text']) . "</strong><br>";

                        // Получаем варианты ответов для данного вопроса
                        $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
                        $stmt->execute([$question['id']]);
                        $answers = $stmt->fetchAll();

                        // Показываем ответы и отмечаем правильный
                        foreach ($answers as $index => $answer) {
                            $is_correct = $index == $question['correct_answer_index'] ? "(правильный)" : "";
                            echo htmlspecialchars($answer['answer_text']) . " $is_correct<br>";
                        }

                        // Получаем ответ пользователя для данного вопроса
                        $stmt = $pdo->prepare("SELECT * FROM user_answers WHERE question_id = ? AND result_id = ?");
                        $stmt->execute([$question['id'], $result['id']]);
                        $user_answer = $stmt->fetch();

                        if ($user_answer) {
                            $user_answer_id = $user_answer['answer_id'];

                            // Находим текст ответа пользователя
                            foreach ($answers as $answer) {
                                if ($answer['id'] == $user_answer_id) {
                                    echo "Ваш ответ: " . htmlspecialchars($answer['answer_text']) . "<br>";
                                    break;
                                }
                            }

                            // Проверяем, правильный ли ответ пользователя
                            $stmt = $pdo->prepare("SELECT is_correct FROM answers WHERE id = ?");
                            $stmt->execute([$user_answer_id]);
                            $correct_answer = $stmt->fetch();

                            if ($correct_answer && $correct_answer['is_correct']) {
                                $correct_answers++;
                            } else {
                                echo "Правильный ответ: " . htmlspecialchars($answers[$question['correct_answer_index']]['answer_text']) . "<br>";
                            }
                        } else {
                            echo "Вы не ответили на этот вопрос.<br>";
                        }

                        echo "</li><br>";
                    }

                    $total_questions = count($questions);
                    ?>
                </ul>

                <p>Правильных ответов: <?php echo $correct_answers; ?> из <?php echo $total_questions; ?></p>
            <?php endif; ?>
        </div>

        <a href="profile.php">Назад</a>
    </div>
</body>
</html>
