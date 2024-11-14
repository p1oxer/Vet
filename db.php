<?php
$servername = "localhost"; // Локальный сервер
$username = "root"; // root — пользователь по умолчанию
$password = ""; // Обычно для root-пользователя на XAMPP пароль пустой
$dbname = "g919483h_12"; // Имя базы данных, которую ты создал

// Создаем подключение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем подключение
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
