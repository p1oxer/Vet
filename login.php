<?php
// Подключаем файл для подключения к базе данных
require 'db.php';

session_start(); // Стартуем сессию для хранения данных пользователя

// Если форма была отправлена методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['login']; // Получаем логин (email)
    $password = $_POST['password']; // Получаем пароль

    // Поиск пользователя в базе данных
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Проверка правильности пароля
        if (password_verify($password, $user['password'])) {
            // Сохраняем данные пользователя в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role']; // Сохраняем роль пользователя из базы данных

            // Проверяем, если администратор — перенаправляем в админ-панель
            if ($_SESSION['role'] == 'admin') {
                header('Location: admin_panel.php');
            } else {
                header('Location: account.php');
            }
            exit;
        } else {
            // Если пароль неправильный, сохраняем сообщение об ошибке в сессии
            $_SESSION['login_error'] = "Неверный пароль.";
            header('Location: login.php');
            exit;
        }
    } else {
        // Если пользователь с таким email не найден
        $_SESSION['login_error'] = "Пользователь с таким логином не найден.";
        header('Location: login.php');
        exit;
    }

    $stmt->close(); // Закрываем запрос
    $conn->close(); // Закрываем соединение с базой данных
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
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

    <main class="auth-container">
        <h1 id="form-title">Вход в личный кабинет</h1>

        <!-- Форма входа -->
        <form id="loginForm" action="login.php" method="POST">
            <!-- Обрабатываем данные через login.php -->
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Войти</button>

            <!-- Выводим сообщение об ошибке при входе -->
            <?php
            if (isset($_SESSION['login_error'])) {
                echo '<p style="color: red;">' . $_SESSION['login_error'] . '</p>';
                unset($_SESSION['login_error']); // Убираем сообщение об ошибке после отображения
            }
            ?>

            <small><a href="#" id="switchToRegister">Регистрация</a></small>
        </form>

        <!-- Форма регистрации (скрыта по умолчанию) -->
        <form id="registerForm" action="register.php" method="POST" style="display: none;">
            <!-- Обрабатываем данные через register.php -->
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>

            <label for="phone">Номер телефона:</label>
            <input type="text" id="phone" name="phone" required placeholder="+7-XXX-XXX-XX-XX">

            <label for="regPassword">Пароль:</label>
            <input type="password" id="regPassword" name="password" required>

            <label for="confirmPassword">Повторите пароль:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required>

            <button type="submit" id="register-btn">Регистрация</button>

            <p id="errorMessage" style="color: red;">
                <!-- Здесь будут отображаться ошибки при регистрации -->
                <?php
                if (isset($_SESSION['register_error'])) {
                    echo $_SESSION['register_error'];
                    unset($_SESSION['register_error']);
                }
                ?>
            </p>

            <small><a href="#" id="switchToLogin">У меня уже есть аккаунт</a></small>
        </form>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>
