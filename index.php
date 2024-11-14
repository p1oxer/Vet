<?php
session_start(); // Запускаем сессию

// Подключаем файл для работы с базой данных
require 'db.php';

// Проверяем, залогинен ли пользователь
if (isset($_SESSION['user_id'])) {
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
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Ветеринарная клиника</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="wrapper">
        <header class="header">
            <div class="container">
                <div class="header__body">
                    <div class="header__logo">
                        <!-- Логотип в круге -->
                        <img src="img/logo.png" alt="Логотип клиники">
                    </div>

                    <!-- Главное меню -->
                    <nav class="header__nav">
                        <ul class="header__list">
                            <li class="header__item"><a href="index.php" class="header__link">Главная</a></li>
                            <li class="header__item dropdown-container">
                                <a href="#" class="header__link">О клинике</a>
                                <ul class="dropdown-menu">
                                    <li><a href="promotions.php">Акции</a></li>
                                    <li><a href="reviews.php">Отзывы</a></li>
                                    <li><a href="team.php">Команда клиники</a></li>
                                </ul>
                            </li>
                            <li class="header__item"><a href="services.php" class="header__link">Услуги</a></li>
                            <li class="header__item"><a href="products.php" class="header__link">Товары</a></li>
                            <li class="header__item"><a href="contacts.php" class="header__link">Контакты</a></li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="header__item dropdown-container">
                                    <a href="#" class="header__link">
                                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); ?>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="account.php">Профиль</a></li>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <li><a href="admin_panel.php">Панель администратора</a></li>
                                        <?php else: ?>
                                            <li><a href="appointment.php">Записаться на прием</a></li>
                                        <?php endif; ?>
                                        <li><a href="logout.php">Выйти из системы</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="header__item"><a href="login.php" class="header__link">Войти в личный кабинет</a></div>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <!-- Контактная информация -->
                    <div class="contact-info">
                        <a href="tel:+79999999999">+7 (999) 999 99 99</a>
                        <p>г. Пермь, ул. Карбышева, д. 38</p>
                    </div>
                    <div class="header__menu menu">
                        <button type="button" class="menu__icon icon-menu"><span></span></button>
                        <nav class="menu__body">
                            <ul class="menu__list">
                                <li class="menu__item"><a href="" class="menu__link">123</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </header>
        <!-- Основное содержание -->

        <main>
            <!-- Консультация -->
            <section class="intro">
                <img class="intro__bg intro__bg--img" src="img/slider1.jpg" alt="">
                <img class="intro__bg" src="img/intro-bg.png" alt="">
                <div class="container">
                    <div class="intro__body">
                        <div class="intro__content">
                            <h2 class="intro__title">Профессорская ветеринарная клиника</h2>
                            <p class="intro__description">Мы заботимся о каждом пациенте и предлагаем качественную помощь для ваших питомцев. Опытные врачи и современное оборудование – все для здоровья вашего друга!</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- О нас -->
            <section class="about-section">
                <h2 class="about-title">О нас</h2>
                <div class="about-content">
                    <div class="about-text">
                        <p class="about-highlight">Наши приоритеты — это здоровье пациента и спокойствие владельца.</p>
                        <p class="about-paragraph">
                            Наша клиника — это не просто место для лечения, это центр заботы и любви к животным. Мы понимаем, как важно для каждого владельца чувствовать уверенность и доверие к ветеринару, когда речь идет о здоровье их питомцев. Наш главный принцип — здоровье пациента превыше всего. Каждый день мы работаем, чтобы предоставлять профессиональную, квалифицированную помощь, основанную на опыте и современном оборудовании. Наши врачи относятся к каждому пациенту с теплом и вниманием, учитывая особенности и потребности каждого, независимо от вида, породы и возраста.
                        </p>
                    </div>

                    <div class="about-stats">
                        <div class="stat-block">
                            <div class="stat-number">10+</div>
                            <p class="stat-text">лет успешной работы в сфере оказания ветеринарных услуг</p>
                        </div>
                        <div class="stat-block">
                            <div class="stat-number">10 000+</div>
                            <p class="stat-text">качественно проведенных плановых и внеплановых операций</p>
                        </div>
                        <div class="stat-block">
                            <div class="stat-number">15+</div>
                            <p class="stat-text">профессионалов в клинике с профильным образованием и любовью к своему делу</p>
                        </div>
                        <div class="stat-block">
                            <div class="stat-number">150 000+</div>
                            <p class="stat-text">посетителей уже обратилось за ветеринарной помощью в нашу ветклинику</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Раздел услуг -->
            <section class="overlap-group-wrapper">
                <div class="overlap-8">
                    <h2 class="text-wrapper-6">Услуги</h2>
                    <div class="overlap-9">
                        <h3 class="text-wrapper-7">Хирургия</h3>
                    </div>
                    <div class="overlap-10">
                        <h3 class="text-wrapper-8">Рентген</h3>
                    </div>
                    <div class="overlap-11">
                        <h3 class="text-wrapper-9">Экг</h3>
                    </div>
                    <div class="overlap-12">
                        <h3 class="text-wrapper-10">Лаб. Диагностика</h3>
                    </div>
                    <div class="overlap-13">
                        <h3 class="text-wrapper-11">УЗИ</h3>
                    </div>
                    <div class="view-2">
                        <div class="overlap-group-2"><a href="services.php" class="text-wrapper-12">Все услуги</a></div>
                    </div>
                </div>
            </section>
        </main>
        <!-- Подвал страницы -->
        <footer>
            <div class="footer-content">
                <p>&copy; 2024 Ветеринарная клиника. Все права защищены.</p>
                <p>Телефон: +7 (999) 123-45-67 | Адрес: г. Москва, ул. Питомцев, д. 1</p>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dropdownContainers = document.querySelectorAll(".dropdown-container");

            dropdownContainers.forEach(container => {
                let timeout;

                container.addEventListener("mouseenter", () => {
                    clearTimeout(timeout);
                    container.querySelector(".dropdown-menu").style.display = "block";
                });

                container.addEventListener("mouseleave", () => {
                    timeout = setTimeout(() => {
                        container.querySelector(".dropdown-menu").style.display = "none";
                    }, 200); // задержка перед скрытием меню
                });
            });
        });
    </script>
</body>
<script src="js/script.js"></script>

</html>