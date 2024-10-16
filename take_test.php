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

    // Получаем информацию о тесте
    $stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch();

    if (!$test) {
        header("Location: profile.php");
        exit();
    }

    // Получаем вопросы и ответы для теста
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $score = 0;
        $user_answers = $_POST['answers'] ?? [];

        foreach ($questions as $question) {
            $question_id = $question['id'];
            $correct_answer_index = $question['correct_answer_index'];

            if (isset($user_answers[$question_id]) && $user_answers[$question_id] == $correct_answer_index) {
                $score++;
            }
        }

        // Рассчитываем, прошел ли пользователь тест (более 50% правильных ответов)
        $passed = ($score / count($questions)) >= 0.5 ? 1 : 0;

        // Сохраняем результаты теста в таблицу results
        $stmt = $pdo->prepare("INSERT INTO results (user_id, test_id, score, passed, completed_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user['id'], $test_id, $score, $passed]);

        header("Location: view_test_results.php?test_id=$test_id");
        exit();
    }
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
    <title>Прохождение теста</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($test['title']); ?></h1>
        <?php if (empty($questions)): ?>
            <p>В этом тесте пока нет вопросов. Пожалуйста, вернитесь позже.</p>
        <?php else: ?>
            <form method="POST" action="take_test.php?test_id=<?php echo htmlspecialchars($test_id); ?>">
                <?php foreach ($questions as $question): ?>
                    <div class="question">
                        <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ?");
                        $stmt->execute([$question['id']]);
                        $answers = $stmt->fetchAll();
                        ?>
                        <?php foreach ($answers as $index => $answer): ?>
                            <label>
                                <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $index; ?>" required>
                                <?php echo htmlspecialchars($answer['answer_text']); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit">Завершить тест</button>
            </form>
        <?php endif; ?>
        <a href="select_test.php">Назад</a>
    </div>
</body>
</html>
