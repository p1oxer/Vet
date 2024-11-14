<?php
session_start(); // Стартуем сессию

require 'db.php'; // Подключаем базу данных

// Пагинация для отображения 4 врачей на странице
$doctors_per_page = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $doctors_per_page;

// Обработка добавления нового врача (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor']) && $_SESSION['role'] == 'admin') {
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $bio = $_POST['bio'];
    $photo = '';

    // Загрузка фотографии
    if (!empty($_FILES['photo']['name'])) {
        $photo = 'uploads/' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    // Добавляем нового врача в базу данных
    $stmt = $conn->prepare("INSERT INTO doctors (name, specialty, bio, photo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $specialty, $bio, $photo);
    $stmt->execute();
    $stmt->close();
    header('Location: team.php');
    exit;
}

// Обработка удаления врача (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_doctor']) && $_SESSION['role'] == 'admin') {
    $doctor_id = $_POST['doctor_id'];

    // Удаляем врача из базы данных
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $stmt->close();
    header('Location: team.php');
    exit;
}

// Обработка редактирования врача (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_doctor']) && $_SESSION['role'] == 'admin') {
    $doctor_id = $_POST['doctor_id'];
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $bio = $_POST['bio'];
    $photo = '';

    // Проверяем, загружена ли новая фотография
    if (!empty($_FILES['photo']['name'])) {
        $photo = 'uploads/' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialty = ?, bio = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $specialty, $bio, $photo, $doctor_id);
    } else {
        $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialty = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $specialty, $bio, $doctor_id);
    }

    $stmt->execute();
    $stmt->close();
    header('Location: team.php');
    exit;
}

// Получаем врачей из базы данных с ограничением для пагинации
$stmt = $conn->prepare("SELECT id, name, specialty, bio, photo FROM doctors LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $doctors_per_page, $offset);
$stmt->execute();
$stmt->bind_result($doctor_id, $doctor_name, $doctor_specialty, $doctor_bio, $doctor_photo);
$doctors = [];
while ($stmt->fetch()) {
    $doctors[] = [
        'id' => $doctor_id,
        'name' => $doctor_name,
        'specialty' => $doctor_specialty,
        'bio' => $doctor_bio,
        'photo' => $doctor_photo
    ];
}
$stmt->close();

// Подсчет общего количества врачей для пагинации
$result = $conn->query("SELECT COUNT(*) as total_doctors FROM doctors");
$row = $result->fetch_assoc();
$total_doctors = $row['total_doctors'];
$total_pages = ceil($total_doctors / $doctors_per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Команда клиники</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header class="view-5">
    <div class="view-6">
        <!-- Логотип в круге -->
        <img src="img/logo.png" alt="Логотип клиники">
    </div>
    
    <!-- Главное меню -->
    <nav class="view-7">
        <div class="frame"><a href="index.php" class="text-wrapper-24">Главная страница</a></div>
        <div class="frame dropdown-container">
            <a href="#" class="text-wrapper-24">О клинике</a>
            <ul class="dropdown-menu">
                <li><a href="promotions.php">Акции</a></li>
                <li><a href="reviews.php">Отзывы</a></li>
                <li><a href="team.php">Команда клиники</a></li>
            </ul>
        </div>

        <div class="frame"><a href="services.php" class="text-wrapper-24">Услуги</a></div>
        <div class="frame"><a href="products.php" class="text-wrapper-24">Товары в зоомагазине</a></div>
        <div class="frame"><a href="contacts.php" class="text-wrapper-24">Контакты</a></div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="frame dropdown-container">
                <a href="#" class="text-wrapper-24">
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
            <div class="frame"><a href="login.php" class="text-wrapper-24">Войти в личный кабинет</a></div>
        <?php endif; ?>
    </nav>

    <!-- Разделительная линия -->
    <div class="line"></div>

    <!-- Контактная информация -->
    <div class="contact-info">
        <p class="text-wrapper-25">+7 (999) 999 99 99</p>
        <p class="text-wrapper-26">г. Пермь, ул. Карбышева, д. 38</p>
    </div>
</header>

    <main>
        <h1>Команда клиники</h1>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button onclick="document.getElementById('add-doctor-form').style.display='block'">Добавить врача</button>
        <?php endif; ?>

        <div id="add-doctor-form" style="display: none;">
            <form action="team.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_doctor" value="1">
                <label for="name">ФИО:</label>
                <input type="text" id="name" name="name" required>
                <label for="specialty">Специальность:</label>
                <input type="text" id="specialty" name="specialty" required>
                <label for="bio">Биография:</label>
                <textarea id="bio" name="bio" required></textarea>
                <label for="photo">Фотография:</label>
                <input type="file" id="photo" name="photo" required>
                <button type="submit">Добавить врача</button>
            </form>
        </div>

        <!-- Отображение врачей -->
        <?php foreach ($doctors as $doctor): ?>
            <div class="doctor-card">
                <img src="<?php echo htmlspecialchars($doctor['photo']); ?>" alt="Фотография врача">
                <h2><?php echo htmlspecialchars($doctor['name']); ?></h2>
                <p><strong>Специальность:</strong> <?php echo htmlspecialchars($doctor['specialty']); ?></p>
                <button class="toggle-btn" onclick="toggleBio(<?php echo $doctor['id']; ?>)">Развернуть</button>
                <a href="appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="book-button">Записаться на прием</a>

                <div id="bio-<?php echo $doctor['id']; ?>" style="display: none;">
                    <p><?php echo htmlspecialchars($doctor['bio']); ?></p>
                
                </div>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <form action="team.php" method="POST" style="display:inline;">
                        <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                        <input type="hidden" name="delete_doctor" value="1">
                        <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить этого врача?');">Удалить</button>
                    </form>
                    <button onclick="document.getElementById('edit-doctor-form-<?php echo $doctor['id']; ?>').style.display='block'">Редактировать</button>

                    <div id="edit-doctor-form-<?php echo $doctor['id']; ?>" style="display: none;">
                        <form action="team.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                            <input type="hidden" name="edit_doctor" value="1">
                            <label for="name">ФИО:</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
                            <label for="specialty">Специальность:</label>
                            <input type="text" name="specialty" value="<?php echo htmlspecialchars($doctor['specialty']); ?>" required>
                            <label for="bio">Биография:</label>
                            <textarea name="bio" required><?php echo htmlspecialchars($doctor['bio']); ?></textarea>
                            <label for="photo">Фотография:</label>
                            <input type="file" name="photo">
                            <button type="submit">Сохранить изменения</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Пагинация -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="team.php?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </main>

    <footer>
        <!-- Подвал сайта -->
    </footer>

    <script>
        function toggleBio(doctorId) {
            var bioElement = document.getElementById('bio-' + doctorId);
            var toggleButton = document.getElementById('toggle-button-' + doctorId);

            if (bioElement.style.display === 'block') {
                bioElement.style.display = 'none';
                toggleButton.textContent = 'Развернуть';
            } else {
                bioElement.style.display = 'block';
                toggleButton.textContent = 'Свернуть';
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
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
</html>
