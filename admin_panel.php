<?php
session_start(); // Стартуем сессию

// Проверяем, залогинен ли пользователь и является ли он администратором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php'); // Если нет доступа, перенаправляем на главную страницу
    exit;
}

// Подключаем файл с подключением к базе данных
require 'db.php';

// Получаем ID пользователя из сессии
$user_id = $_SESSION['user_id'];

// Запрашиваем имя пользователя из базы данных
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name, $user_email);
$stmt->fetch();
$stmt->close();

// Если у пользователя есть имя, сохраняем его в сессии для отображения в шапке
if (!empty($user_name)) {
    $_SESSION['user_name'] = $user_name;
} else {
    $_SESSION['user_email'] = $user_email;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
        <div class="header-container">
            <div class="logo">
                <img src="img/logo.png" alt="Логотип клиники">
            </div>
            <nav>
                <ul class="menu">
                    <li><a href="index.php">Главная страница</a></li>
                    <li class="dropdown">
                        <a href="#">О клинике</a>
                        <ul class="dropdown-menu">
                            <li><a href="promotions.php">Акции</a></li>
                            <li><a href="reviews.php">Отзывы</a></li>
                            <li><a href="team.php">Команда клиники</a></li>
                        </ul>
                    </li>
                    <li><a href="services.php">Услуги</a></li>
                    <li><a href="products.php">Товары в зоомагазине</a></li>
                    <li><a href="contacts.php">Контакты</a></li>

                    <!-- Проверяем, залогинен ли пользователь -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Если пользователь залогинен, показываем его имя, если есть, иначе email -->
                        <li class="dropdown">
                            <a href="#">
                                <?php 
                                    // Если имя пользователя существует, выводим его, иначе выводим email
                                    echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); 
                                ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="account.php">Профиль</a></li>
                            <!-- Проверка роли для отображения кнопки -->
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li><a href="admin_panel.php">Панель администратора</a></li>
                            <?php else: ?>
                                <li><a href="appointment.php">Записаться на прием</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Выйти из системы</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Если не залогинен, показываем ссылку на страницу входа -->
                        <li><a href="login.php">Войти в личный кабинет</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <h1>Добро пожаловать в панель администратора!</h1>
        <p>Здесь можно управлять пользователями, записями на приём, питомцами и контентом сайта.</p>

        <!-- Пример навигации по админ-панели -->
        <ul>
            <li><a href="manage_users.php">Управление пользователями</a></li>
            <li><a href="manage_appointments.php">Управление записями на приём</a></li>
            <li><a href="promotions.php">Управление акциями</a></li>
            <li><a href="services.php">Управление услугами</a></li>
            <li><a href="products.php">Управление товарами</a></li>
        </ul>
    </main>

    <footer>
        <p>&copy; 2024 Ветеринарная клиника. Все права защищены.</p>
    </footer>
</body>
</html>
