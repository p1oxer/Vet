<?php
session_start(); // Стартуем сессию

require 'db.php'; // Подключаем базу данных

// Получаем всех врачей для выбора в форме
$stmt = $conn->prepare("SELECT id, name FROM doctors");
$stmt->execute();
$stmt->bind_result($doctor_id, $doctor_name);
$doctors = [];
while ($stmt->fetch()) {
    $doctors[] = [
        'id' => $doctor_id,
        'name' => $doctor_name
    ];
}
$stmt->close();

// Проверяем, был ли выбран врач
$selected_doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

// Пагинация по неделям (7 дней на страницу)
$days_per_page = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $days_per_page;

// Устанавливаем начальную дату для текущей страницы
$start_date = date('Y-m-d', strtotime("+$offset days"));
$end_date = date('Y-m-d', strtotime("+".($days_per_page - 1 + $offset)." days"));

// Массив доступных часов
$available_hours = [
    '09:00:00', '10:00:00', '11:00:00', '12:00:00',
    '13:00:00', '14:00:00', '15:00:00', '16:00:00'
];

// Проверяем, была ли запись на прием отправлена
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_appointment']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $pet_id = $_POST['pet_id'];

    // Получаем информацию о пользователе
    $stmt = $conn->prepare("SELECT name, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_name, $phone);
    $stmt->fetch();
    $stmt->close();

    // Получаем информацию о питомце
    $stmt = $conn->prepare("SELECT name, breed, age FROM pets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pet_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($pet_name, $pet_breed, $pet_age);
    $stmt->fetch();
    $stmt->close();

    // Если имя не заполнено, используем email
    $name = $user_name ?? $_SESSION['user_email'];

    // Добавляем запись в базу данных
    $stmt = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, date, time, name, phone, pet_name, breed, age) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssssi", $user_id, $selected_doctor_id, $date, $time, $name, $phone, $pet_name, $pet_breed, $pet_age);
    $stmt->execute();
    $stmt->close();
    $_SESSION['appointment_success'] = "Вы успешно записались на прием";
    header("Location: appointment.php?doctor_id=$selected_doctor_id&page=$page");
    exit;
}

// Получаем занятые записи на текущую неделю для выбранного врача
$stmt = $conn->prepare("SELECT date, time FROM appointments WHERE doctor_id = ? AND date BETWEEN ? AND ?");
$stmt->bind_param("iss", $selected_doctor_id, $start_date, $end_date);
$stmt->execute();
$stmt->bind_result($booked_date, $booked_time);
$booked_appointments = [];
while ($stmt->fetch()) {
    $booked_appointments["$booked_date $booked_time"] = true;
}
$stmt->close();

// Получаем питомцев пользователя
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $pets_stmt = $conn->prepare("SELECT id, name FROM pets WHERE user_id = ?");
    $pets_stmt->bind_param("i", $user_id);
    $pets_stmt->execute();
    $pets_stmt->bind_result($pet_id, $pet_name);
    $pets = [];
    while ($pets_stmt->fetch()) {
        $pets[] = ['id' => $pet_id, 'name' => $pet_name];
    }
    $pets_stmt->close();
}

// Подсчет общего количества дней (для расчета общего количества страниц)
$total_days = 30;  // Предположим, что показываем расписание на месяц вперед
$total_pages = ceil($total_days / $days_per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на прием</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        .schedule-table th, .schedule-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .schedule-table th {
            background-color: #f2f2f2;
        }
        .schedule-table .available {
            background-color: #dff0d8;
            cursor: pointer;
        }
        .schedule-table .booked {
            background-color: #f2dede;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
        }
        .pagination .active {
            font-weight: bold;
        }
        .no-pets-warning {
            color: red;
            margin-top: 20px;
        }
    </style>
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
                                    echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); 
                                ?>
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
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Войти в личный кабинет</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <!-- Если врач не выбран, показываем форму выбора врача -->
        <?php if (!$selected_doctor_id): ?>
            <h1>Выберите врача для записи</h1>
            <form action="appointment.php" method="GET">
                <label for="doctor_id">Выберите врача:</label>
                <select name="doctor_id" id="doctor_id" required>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Показать расписание</button>
            </form>
            <?php exit; ?>
        <?php endif; ?>

        <h1>Запись на прием к <?php echo htmlspecialchars($doctors[array_search($selected_doctor_id, array_column($doctors, 'id'))]['name']); ?></h1>

        <!-- Выводим сообщение об успешной записи -->
        <?php if (isset($_SESSION['appointment_success'])): ?>
            <p style="color: green;"><?php echo $_SESSION['appointment_success']; unset($_SESSION['appointment_success']); ?></p>
        <?php endif; ?>

        <!-- Таблица записи на прием -->
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Время</th>
                    <?php for ($i = 0; $i < $days_per_page; $i++): ?>
                        <th><?php echo date('d-m-Y', strtotime("+$i days", strtotime($start_date))); ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($available_hours as $hour): ?>
                    <tr>
                        <td><?php echo date('H:i', strtotime($hour)); ?></td>
                        <?php for ($i = 0; $i < $days_per_page; $i++): ?>
                            <?php
                            $current_date = date('Y-m-d', strtotime("+$i days", strtotime($start_date)));
                            $appointment_key = "$current_date $hour";
                            ?>
                            <td class="<?php echo isset($booked_appointments[$appointment_key]) ? 'booked' : 'available'; ?>" <?php if (!isset($booked_appointments[$appointment_key])): ?>onclick="handleAppointmentClick('<?php echo $current_date; ?>', '<?php echo $hour; ?>')"<?php endif; ?>>
                                <?php echo isset($booked_appointments[$appointment_key]) ? 'Занято' : 'Свободно'; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Форма для записи на прием -->
        <div id="appointment-form" style="display: none;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (empty($pets)): ?>
                    <p class="no-pets-warning">У вас еще нет добавленных питомцев. <button onclick="document.getElementById('add-pet-form').style.display='block'">Указать питомца</button></p>
                <?php else: ?>
                    <form action="appointment.php?doctor_id=<?php echo $selected_doctor_id; ?>&page=<?php echo $page; ?>" method="POST">
                        <input type="hidden" id="appointment-date" name="date">
                        <input type="hidden" id="appointment-time" name="time">
                        
                        <label for="pet_id">Выберите питомца:</label>
                        <select name="pet_id" id="pet_id" required>
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?php echo $pet['id']; ?>"><?php echo htmlspecialchars($pet['name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" name="schedule_appointment">Записаться на прием</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p>Чтобы записаться на прием, <a href="login.php">войдите в систему</a>.</p>
            <?php endif; ?>
        </div>

        <!-- Форма для добавления питомца -->
        <div id="add-pet-form" style="display: none;">
            <form action="add_pet.php" method="POST">
                <label for="pet-name">Кличка питомца:</label>
                <input type="text" id="pet-name" name="pet_name" required>

                <label for="pet-breed">Порода питомца:</label>
                <input type="text" id="pet-breed" name="pet_breed" required>

                <label for="pet-age">Возраст питомца:</label>
                <input type="number" id="pet-age" name="pet_age" min="0" required>

                <button type="submit">Добавить питомца</button>
            </form>
        </div>

        <!-- Пагинация -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="appointment.php?doctor_id=<?php echo $selected_doctor_id; ?>&page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </main>

    <footer>
        <!-- Подвал сайта -->
    </footer>

    <script>
        function handleAppointmentClick(date, time) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php';
            <?php else: ?>
                document.getElementById('appointment-form').style.display = 'block';
                document.getElementById('appointment-date').value = date;
                document.getElementById('appointment-time').value = time;
            <?php endif; ?>
        }
    </script>
</body>
</html>
