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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_id'])) {
    // Получаем ID питомца и ID пользователя из сессии
    $pet_id = (int)$_POST['pet_id'];
    $user_id = $_SESSION['user_id'];

    // Удаляем питомца из базы данных только если он принадлежит текущему пользователю
    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pet_id, $user_id);

    if ($stmt->execute()) {
        // Перенаправляем обратно в личный кабинет с успешным уведомлением
        header('Location: account.php?pet_deleted=1');
        exit;
    } else {
        echo "Ошибка при удалении питомца: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
