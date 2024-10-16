<?php
session_start();
require 'db.php'; // Подключение к базе данных

if (!isset($_SESSION['user']) || !isset($_POST['test_id'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['user'];
$test_id = $_POST['test_id'];


// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    throw new Exception("Пользователь не найден.");
}

// Вставляем пустую запись результата теста
$stmt = $pdo->prepare("INSERT INTO results (user_id, test_id, score, passed, completed_at) VALUES (?, ?, 0, 0, NOW())");
$stmt->execute([$user['id'], $test_id]);
$result_id = $pdo->lastInsertId(); // ID новой записи в results

$score = 0;
$total_questions = count($_POST['answers']); // Количество вопросов

// Обрабатываем ответы пользователя
foreach ($_POST['answers'] as $question_id => $answer_id) {
    // Сохраняем ответ пользователя в таблицу user_answers
    $stmt = $pdo->prepare("INSERT INTO user_answers (result_id, question_id, answer_id, answered_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$result_id, $question_id, $answer_id]);

    // Проверяем правильность ответа
    $stmt = $pdo->prepare("SELECT is_correct FROM answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();

    // Убедитесь, что ответ найден
    if ($answer) {
        if ($answer['is_correct']) {
            $score++; // Увеличиваем счетчик правильных ответов
        }
    }
}

// Обновляем результат теста
$passed = $score >= ($total_questions / 2) ? 1 : 0; // Тест пройден, если правильных ответов >= 50%
$stmt = $pdo->prepare("UPDATE results SET score = ?, passed = ? WHERE id = ?");
$stmt->execute([$score, $passed, $result_id]);

// Перенаправляем пользователя на страницу с результатами
header("Location: view_test_results.php?test_id=" . $test_id);
exit();