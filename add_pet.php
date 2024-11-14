<?php
session_start(); // Стартуем сессию

// Проверяем, вошёл ли пользователь в систему
if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован, перенаправляем на страницу входа
    header('Location: login.php');
    exit;
}

// Подключаем файл с подключением к базе данных
require 'db.php';

// Проверяем, что форма отправлена методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $user_id = $_SESSION['user_id'];
    $pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : null;
    $pet_breed = isset($_POST['pet_breed']) ? trim($_POST['pet_breed']) : null;
    $pet_age = isset($_POST['pet_age']) ? (int)$_POST['pet_age'] : null;

    // Проверяем, чтобы обязательные поля не были пустыми
    if (empty($pet_name) || $pet_age === null || $pet_age < 0) {
        echo "Пожалуйста, заполните все обязательные поля и укажите корректный возраст.";
        exit;
    }

    // Вставляем данные о питомце в базу данных
    $stmt = $conn->prepare("INSERT INTO pets (user_id, name, breed, age) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $pet_name, $pet_breed, $pet_age);

    if ($stmt->execute()) {
        // Перенаправляем обратно в личный кабинет с успешным уведомлением
        header('Location: account.php?pet_added=1');
        exit;
    } else {
        echo "Ошибка при добавлении питомца: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
