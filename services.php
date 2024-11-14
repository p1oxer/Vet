<?php
session_start(); // Стартуем сессию

require 'db.php'; // Подключаем базу данных

// Обработка добавления новой услуги (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service']) && $_SESSION['role'] == 'admin') {
    $title = $_POST['title'];
    $name = $_POST['name'];
    $details = $_POST['details'];

    $target_dir = "uploads/";
    $uploadOk = 1;
    $target_file = "";

    // Проверка, загружен ли файл
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Проверка, является ли файл изображением
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "Файл не является изображением.";
            $uploadOk = 0;
        }

        // Проверка размера файла
        if ($_FILES["image"]["size"] > 500000) {
            echo "Файл слишком большой.";
            $uploadOk = 0;
        }

        // Разрешить только определенные форматы
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo "Разрешены только файлы JPG, JPEG, PNG и GIF.";
            $uploadOk = 0;
        }

        // Загрузка файла, если проверки пройдены
        if ($uploadOk == 1) {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                echo "Ошибка при загрузке файла.";
                $uploadOk = 0;
            }
        }
    }

    // Добавляем запись в базу данных, даже если изображение отсутствует
    $stmt = $conn->prepare("INSERT INTO services (title, name, details, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $name, $details, $target_file);
    $stmt->execute();
    $stmt->close();
    header('Location: services.php');
    exit;
}




// Обработка редактирования услуги (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_service']) && $_SESSION['role'] == 'admin') {
    $service_id = $_POST['service_id'];
    $title = $_POST['title'];
    $name = $_POST['name'];
    $details = $_POST['details'];
    $target_file = ""; // Переменная для хранения пути к новому файлу изображения, если он загружен

    // Проверяем, загружено ли новое изображение
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Проверка, является ли файл изображением
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "Файл не является изображением.";
            $uploadOk = 0;
        }

        // Проверка размера файла
        if ($_FILES["image"]["size"] > 500000) {
            echo "Файл слишком большой.";
            $uploadOk = 0;
        }

        // Разрешаем только определенные форматы
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo "Разрешены только файлы JPG, JPEG, PNG и GIF.";
            $uploadOk = 0;
        }

        // Загрузка файла
        if ($uploadOk == 1) {
            // Удаление старого изображения, если существует
            $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $stmt->bind_result($old_image);
            $stmt->fetch();
            $stmt->close();

            if (!empty($old_image) && file_exists($old_image)) {
                unlink($old_image);
            }

            // Загружаем новое изображение
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                echo "Ошибка при загрузке файла.";
                $uploadOk = 0;
            }
        }
    } else {
        // Если изображение не загружено, оставляем прежний путь
        $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $stmt->bind_result($target_file);
        $stmt->fetch();
        $stmt->close();
    }

    // Обновляем услугу в базе данных
    $stmt = $conn->prepare("UPDATE services SET title = ?, name = ?, details = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $name, $details, $target_file, $service_id);
    $stmt->execute();
    $stmt->close();
    header('Location: services.php');
    exit;
}

// Обработка удаления услуги (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_service']) && $_SESSION['role'] == 'admin') {
    $service_id = $_POST['service_id'];

    // Получаем путь к изображению, если оно существует
    $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($image_path);
    $stmt->fetch();
    $stmt->close();

    // Удаляем изображение, если оно существует
    if (!empty($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }

    // Удаляем услугу из базы данных
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->close();
    header('Location: services.php');
    exit;
}


// Получаем услуги из базы данных
$stmt = $conn->prepare("SELECT id, title, name, details, image FROM services");
$stmt->execute();
$stmt->bind_result($service_id, $service_title, $service_name, $service_details, $service_image);
$services = [];
while ($stmt->fetch()) {
    $services[] = [
        'id' => $service_id,
        'title' => $service_title,
        'name' => $service_name,
        'details' => $service_details,
        'image' => $service_image
    ];
}
$stmt->close();



?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Ветеринарная клиника</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body class="page-services">
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

    <main>


        <h1 class="text-wrapper-012 block-title">Услуги клиники</h1>
        <!-- Кнопка для добавления новой услуги (только для администратора) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button onclick="document.getElementById('add-service-form').style.display='block'">Добавить услугу</button>
        <?php endif; ?>

        <!-- Форма для добавления новой услуги (скрыта по умолчанию) -->
        <div id="add-service-form" style="display: none;">
            <form action="services.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_service" value="1">

                <label for="title">Заголовок услуги:</label>
                <input type="text" id="title" name="title" required>

                <label for="name">Название услуги:</label>
                <input type="text" id="name" name="name" required>

                <label for="details">Описание услуги:</label>
                <textarea id="details" name="details" required></textarea>

                <label for="image">Изображение услуги:</label>
                <input type="file" id="image" name="image">

                <button type="submit">Добавить услугу</button>
            </form>
        </div>


        <!-- Отображение списка услуг -->
        <?php foreach ($services as $service): ?>
            <div class="services-section">
                <div class="container">
                    <div class="service-block">
                        <div class="line-top"></div>

                        <div class="service-content">
                            <div class="service-image">
                                <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                            </div>

                            <div class="service-text">
                                <div class="service-header">
                                    <span class="service-title"><?php echo htmlspecialchars($service['name']); ?></span>
                                    <span class="service-subtitle"><?php echo htmlspecialchars($service['title']); ?></span>
                                    <div class="toggle-icon" onclick="toggleDetails(<?php echo $service['id']; ?>)">
                                        <img id="icon-<?php echo $service['id']; ?>" src="img/7e11c14f-9b67-4b4d-9e03-36d1bc1673a8.png" alt="Раскрыть">
                                    </div>
                                </div>
                                <div id="details-<?php echo $service['id']; ?>" class="service-description"><?php echo htmlspecialchars($service['details']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Кнопки для редактирования и удаления (только для администратора) -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <button onclick="document.getElementById('edit-service-form-<?php echo $service['id']; ?>').style.display='block'">Редактировать</button>
                    <form action="services.php" method="POST" style="display:inline;">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        <input type="hidden" name="delete_service" value="1">
                        <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту услугу?');">Удалить</button>
                    </form>

                    <!-- Форма для редактирования услуги (скрыта по умолчанию) -->
                    <div id="edit-service-form-<?php echo $service['id']; ?>" style="display: none;">
                        <form action="services.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <input type="hidden" name="edit_service" value="1">

                            <label for="title">Заголовок услуги:</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($service['title']); ?>" required>

                            <label for="name">Название услуги:</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>

                            <label for="details">Описание услуги:</label>
                            <textarea name="details" required><?php echo htmlspecialchars($service['details']); ?></textarea>

                            <label for="image">Изображение услуги:</label>
                            <input type="file" name="image">

                            <button type="submit">Сохранить изменения</button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
            </div>
        <?php endforeach; ?>
    </main>
    <script>
        // Функция для показа и скрытия подробностей
        function toggleDetails(id) {
            var details = document.getElementById('details-' + id);
            var icon = document.getElementById('icon-' + id);

            if (details.style.display === 'none' || !details.style.display) {
                details.style.display = 'block';
                icon.src = 'img/4d63e729-8c73-49bc-a3cc-f6a83e485d71.png'; // Иконка для скрытия
            } else {
                details.style.display = 'none';
                icon.src = 'img/7e11c14f-9b67-4b4d-9e03-36d1bc1673a8.png'; // Иконка для раскрытия
            }
        }


        // Устанавливаем начальное состояние скрытия для описаний
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.service-description').forEach(function (description) {
                description.style.display = 'none';
            });
        });


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

</html>