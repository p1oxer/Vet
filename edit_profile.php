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
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $phone = isset($_POST['phone']) ? preg_replace('/\D/', '', $_POST['phone']) : null; // Удаляем все нецифровые символы

    // Проверяем, чтобы обязательные поля не были пустыми
    if (empty($name) || empty($email) || empty($phone)) {
        echo "Все поля обязательны для заполнения.";
        exit;
    }

    // Проверяем длину номера телефона
    if (strlen($phone) !== 11) {
        echo "Некорректный номер телефона. Введите полный номер.";
        exit;
    }

    // Проверка, что email уникален (если он был изменён)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "Этот email уже зарегистрирован.";
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Обновляем данные пользователя
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $user_id);

    if ($stmt->execute()) {
        // Обновляем email в сессии, если он был изменён
        $_SESSION['user_email'] = $email;

        // Перенаправляем обратно в личный кабинет
        header('Location: account.php?success=1');
        exit;
    } else {
        echo "Ошибка при обновлении профиля: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
