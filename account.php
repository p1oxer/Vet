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

// Получаем данные пользователя из сессии
$user_id = $_SESSION['user_id'];

// Запрос на получение информации о пользователе
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone);
$stmt->fetch();
$stmt->close();

// Если у пользователя есть имя, сохраняем его в сессии
if (!empty($name)) {
    $_SESSION['user_name'] = $name;
} else {
    $_SESSION['user_name'] = null; // Если имени нет, сохраняем в сессии null
    $_SESSION['user_email'] = $email; // Сохраняем email для отображения
}

// Запрос на получение информации о питомцах
$pets_stmt = $conn->prepare("SELECT id, name, breed, age FROM pets WHERE user_id = ?");
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets_stmt->bind_result($pet_id, $pet_name, $pet_breed, $pet_age); // Привязка 4 переменных
$pets = [];
while ($pets_stmt->fetch()) {
    $pets[] = [
        'id' => $pet_id,
        'name' => $pet_name,
        'breed' => $pet_breed,
        'age' => $pet_age
    ];
}
$pets_stmt->close();

// Запрос на получение записей пользователя на прием
$appointments_stmt = $conn->prepare("SELECT appointments.id, appointments.date, appointments.time, doctors.name AS doctor_name 
                        FROM appointments 
                        JOIN doctors ON appointments.doctor_id = doctors.id 
                        WHERE appointments.user_id = ?");
$appointments_stmt->bind_param("i", $user_id);
$appointments_stmt->execute();
$appointments_stmt->bind_result($appointment_id, $appointment_date, $appointment_time, $doctor_name);
$appointments = [];
while ($appointments_stmt->fetch()) {
    $appointments[] = [
        'id' => $appointment_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'doctor_name' => $doctor_name
    ];
}
$appointments_stmt->close();

// Обработка отмены записи на прием
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];

    // Запрос на удаление записи
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);

    if ($stmt->execute()) {
        echo "Запись успешно отменена.";
    } else {
        echo "Ошибка при отмене записи: " . $stmt->error;
    }

    $stmt->close();
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

    <main>
        <h2>Профиль</h2>
        <p>Имя: <?php echo $name ? htmlspecialchars($name) : 'Укажите имя'; ?></p>
        <p>Email: <?php echo htmlspecialchars($email); ?></p>
        <p>Телефон: <?php echo $phone ? htmlspecialchars($phone) : 'Укажите номер телефона'; ?></p>
        <button onclick="document.getElementById('edit-profile-form').style.display='block'">Редактировать профиль</button>

        <!-- Форма редактирования профиля (скрыта по умолчанию) -->
        <div id="edit-profile-form" style="display: none;">
            <form action="edit_profile.php" method="POST">
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                
                <label for="phone">Номер телефона:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

                <button type="submit">Сохранить изменения</button>
            </form>
        </div>

        <h2>Мои питомцы</h2>
        <?php if (count($pets) > 0): ?>
            <ul>
                <?php foreach ($pets as $pet): ?>
                    <li>
                        Кличка: <?php echo htmlspecialchars($pet['name']); ?>,
                        Порода: <?php echo htmlspecialchars($pet['breed']); ?>,
                        Возраст: <?php echo htmlspecialchars($pet['age']); ?> лет
                        <form action="delete_pet.php" method="POST" style="display:inline;">
                            <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                            <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить питомца?');">Удалить</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Вы еще не рассказали нам о вашем друге.</p>  
        <?php endif; ?>
        
        <button onclick="document.getElementById('add-pet-form').style.display='block'">Добавить питомца</button>

        <!-- Форма добавления питомца (скрыта по умолчанию) -->
        <div id="add-pet-form" style="display: none;">
            <form action="add_pet.php" method="POST">
                <label for="pet-name">Кличка:</label>
                <input type="text" id="pet-name" name="pet_name" required>

                <label for="pet-breed">Порода:</label>
                <input type="text" id="pet-breed" name="pet_breed">

                <label for="pet-age">Возраст:</label>
                <input type="number" id="pet-age" name="pet_age" min="0" required>

                <button type="submit">Добавить питомца</button>
            </form>
        </div>

        <h2>Ваши записи на прием</h2>
        <?php if (count($appointments) > 0): ?>
            <ul>
                <?php foreach ($appointments as $appointment): ?>
                    <li>
                        <strong>Дата:</strong> <?php echo htmlspecialchars($appointment['date']); ?><br>
                        <strong>Время:</strong> <?php echo htmlspecialchars($appointment['time']); ?><br>
                        <strong>Врач:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                        <form action="account.php" method="POST" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <button type="submit" name="cancel_appointment" onclick="return confirm('Вы уверены, что хотите отменить запись?');">Отменить запись</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>У вас нет записей на прием.</p>
        <?php endif; ?>
    </main>

    <footer>

    </footer>
</body>
</html>
