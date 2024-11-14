<?php
session_start(); // Стартуем сессию

// Проверяем, что пользователь авторизован и является администратором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Подключаем базу данных
require 'db.php';

// Обработка выбора врача
$doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null;

// Обработка отмены записи
if (isset($_POST['cancel_appointment']) && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->close();
}

// Получение списка врачей
$stmt = $conn->prepare("SELECT id, name FROM doctors");
$stmt->execute();
$stmt->bind_result($doc_id, $doc_name);
$doctors = [];
while ($stmt->fetch()) {
    $doctors[] = ['id' => $doc_id, 'name' => $doc_name];
}
$stmt->close();

// Получение записей для выбранного врача
$appointments = [];
if ($doctor_id) {
    $stmt = $conn->prepare("
        SELECT a.id, u.name, u.email, u.phone, a.pet_name, a.breed, a.age, a.date, a.time 
        FROM appointments a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.date, a.time
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $stmt->bind_result($appointment_id, $user_name, $user_email, $user_phone, $pet_name, $breed, $age, $date, $time);
    
    while ($stmt->fetch()) {
        $appointments[] = [
            'id' => $appointment_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'pet_name' => $pet_name,
            'breed' => $breed,
            'age' => $age,
            'date' => $date,
            'time' => $time
        ];
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление записями</title>
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
        <h1>Управление записями на прием</h1>

        <!-- Форма для выбора врача -->
        <form action="manage_appointments.php" method="POST">
            <label for="doctor_id">Выберите врача:</label>
            <select name="doctor_id" id="doctor_id" required>
                <option value="">Выберите врача</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?php echo $doctor['id']; ?>" <?php if ($doctor['id'] == $doctor_id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($doctor['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Показать записи</button>
        </form>

        <!-- Отображение записей -->
        <?php if ($doctor_id && !empty($appointments)): ?>
            <h2>Записи к врачу</h2>
            <table>
                <thead>
                    <tr>
                        <th>Имя пользователя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Питомец</th>
                        <th>Порода</th>
                        <th>Возраст</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['user_phone']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['breed']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['age']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                            <td>
                                <form action="manage_appointments.php" method="POST">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <button type="submit" name="cancel_appointment" onclick="return confirm('Вы уверены, что хотите отменить эту запись?');">Отменить запись</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($doctor_id): ?>
            <p>Нет записей на прием к этому врачу.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 Ветеринарная клиника. Все права защищены.</p>
    </footer>
</body>
</html>
