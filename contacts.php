<?php
session_start(); // Стартуем сессию

require 'db.php'; // Подключаем базу данных

// Обработка добавления нового контакта (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_contact']) && $_SESSION['role'] == 'admin') {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $working_hours = $_POST['working_hours'];

    // Добавляем новый контакт в базу данных
    $stmt = $conn->prepare("INSERT INTO contacts (phone, email, address, working_hours) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $phone, $email, $address, $working_hours);
    $stmt->execute();
    $stmt->close();
    header('Location: contacts.php');
    exit;
}

// Обработка редактирования контактов (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_contact']) && $_SESSION['role'] == 'admin') {
    $contact_id = $_POST['contact_id'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $working_hours = $_POST['working_hours'];

    // Обновляем контакт в базе данных
    $stmt = $conn->prepare("UPDATE contacts SET phone = ?, email = ?, address = ?, working_hours = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $phone, $email, $address, $working_hours, $contact_id);
    $stmt->execute();
    $stmt->close();
    header('Location: contacts.php');
    exit;
}

// Обработка удаления контактов (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_contact']) && $_SESSION['role'] == 'admin') {
    $contact_id = $_POST['contact_id'];

    // Удаляем контакт из базы данных
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $stmt->close();
    header('Location: contacts.php');
    exit;
}

// Получаем контактные данные из базы данных
$stmt = $conn->prepare("SELECT id, phone, email, address, working_hours FROM contacts");
$stmt->execute();
$stmt->bind_result($contact_id, $contact_phone, $contact_email, $contact_address, $contact_working_hours);
$contacts = [];
while ($stmt->fetch()) {
    $contacts[] = [
        'id' => $contact_id,
        'phone' => $contact_phone,
        'email' => $contact_email,
        'address' => $contact_address,
        'working_hours' => $contact_working_hours
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
    <script type="text/javascript">
        // Функция для встраивания Яндекс карты
        function init() {
            var myMap = new ymaps.Map("map", {
                center: [58.119255, 56.379013], // Координаты клиники (например, Москва)
                zoom: 16
            });

            var myPlacemark = new ymaps.Placemark([58.119255, 56.379013], {
                hintContent: 'Клиника',
                balloonContent: 'Ветеринарная клиника'
            });

            myMap.geoObjects.add(myPlacemark);
        }

        ymaps.ready(init);
    </script>
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
        <h1>Контакты</h1>

        <!-- Кнопка для добавления новых контактов (только для администратора) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button onclick="document.getElementById('add-contact-form').style.display='block'">Добавить контакт</button>
        <?php endif; ?>

        <!-- Форма для добавления контакта (скрыта по умолчанию) -->
        <div id="add-contact-form" style="display: none;">
            <form action="contacts.php" method="POST">
                <input type="hidden" name="add_contact" value="1">
                <label for="phone">Телефон:</label>
                <input type="text" id="phone" name="phone" required>

                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>

                <label for="address">Адрес:</label>
                <textarea id="address" name="address" required></textarea>

                <label for="working_hours">Режим работы:</label>
                <textarea id="working_hours" name="working_hours" required></textarea>

                <button type="submit">Добавить контакт</button>
            </form>
        </div>

        <!-- Отображение контактной информации -->
        <h2>Контактная информация</h2>
        <?php foreach ($contacts as $contact): ?>
            <div class="contact-info">
                <p><strong>Телефон:</strong> <?php echo htmlspecialchars($contact['phone']); ?></p>
                <p><strong>E-mail:</strong> <?php echo htmlspecialchars($contact['email']); ?></p>
                <p><strong>Адрес:</strong> <?php echo htmlspecialchars($contact['address']); ?></p>
                <p><strong>Режим работы:</strong> <?php echo htmlspecialchars($contact['working_hours']); ?></p>

                <!-- Если пользователь администратор, показываем кнопки редактирования и удаления -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <form action="contacts.php" method="POST" style="display:inline;">
                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                        <input type="hidden" name="delete_contact" value="1">
                        <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить этот контакт?');">Удалить</button>
                    </form>

                    <button onclick="document.getElementById('edit-contact-form-<?php echo $contact['id']; ?>').style.display='block'">Редактировать</button>

                    <!-- Форма для редактирования контакта (скрыта по умолчанию) -->
                    <div id="edit-contact-form-<?php echo $contact['id']; ?>" style="display: none;">
                        <form action="contacts.php" method="POST">
                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                            <input type="hidden" name="edit_contact" value="1">
                            <label for="phone">Телефон:</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>" required>

                            <label for="email">E-mail:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($contact['email']); ?>" required>

                            <label for="address">Адрес:</label>
                            <textarea name="address" required><?php echo htmlspecialchars($contact['address']); ?></textarea>

                            <label for="working_hours">Режим работы:</label>
                            <textarea name="working_hours" required><?php echo htmlspecialchars($contact['working_hours']); ?></textarea>

                            <button type="submit">Сохранить изменения</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Яндекс Карта -->
        <h2>Карта проезда</h2>
        <div id="map" style="width: 100%; height: 400px;"></div>

    </main>

    <footer>
        <p>&copy; 2024 Ветеринарная клиника. Все права защищены.</p>
    </footer>
</body>
</html>
