<?php
session_start(); // Сессия должна стартовать перед использованием данных сессии

require 'db.php'; // Подключаем базу данных

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Хэшируем пароль
    $role = 'user'; // Роль по умолчанию

    // Проверяем, существует ли пользователь с таким email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['register_error'] = "Пользователь с таким email уже зарегистрирован.";
        header('Location: login.php');
        exit;
    } else {
        // Вставляем нового пользователя
        $stmt = $conn->prepare("INSERT INTO users (email, phone, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $phone, $password, $role);

        if ($stmt->execute()) {
            // Сохраняем данные пользователя в сессию
            $_SESSION['user_id'] = $conn->insert_id; // ID нового пользователя
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = $role; // Сохраняем роль в сессии

            // Перенаправляем в личный кабинет
            header('Location: account.php');
            exit;
        } else {
            $_SESSION['register_error'] = "Ошибка при регистрации: " . $stmt->error;
            header('Location: login.php');
            exit;
        }
    }

    $stmt->close();
    $conn->close();
}
