<?php
session_start();
require 'db.php'; // Подключаем базу данных

// Проверяем, является ли пользователь администратором
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Обработка добавления новой акции (если администратор отправил форму)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_promotion' && $isAdmin) {
    $title = $_POST['title'];
    $short_description = $_POST['short_description'];
    $full_description = $_POST['full_description'];
    $price = $_POST['price'];
    $discount_price = $_POST['discount_price'];

    // Добавляем акцию в базу данных
    $stmt = $conn->prepare("INSERT INTO promotions (title, short_description, full_description, price, discount_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $short_description, $full_description, $price, $discount_price);
    if ($stmt->execute()) {
        echo "Акция добавлена успешно!";
    } else {
        echo "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка удаления акции
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_promotion' && $isAdmin) {
    $promotion_id = $_POST['promotion_id'];
    $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
    $stmt->bind_param("i", $promotion_id);
    if ($stmt->execute()) {
        echo "Акция удалена успешно!";
    } else {
        echo "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка редактирования акции
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'edit_promotion' && $isAdmin) {
    $promotion_id = $_POST['promotion_id'];
    $title = $_POST['title'];
    $short_description = $_POST['short_description'];
    $full_description = $_POST['full_description'];
    $price = $_POST['price'];
    $discount_price = $_POST['discount_price'];

    $stmt = $conn->prepare("UPDATE promotions SET title = ?, short_description = ?, full_description = ?, price = ?, discount_price = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $title, $short_description, $full_description, $price, $discount_price, $promotion_id);
    if ($stmt->execute()) {
        echo "Акция обновлена успешно!";
    } else {
        echo "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

// Получаем акции из базы данных
$sql = "SELECT * FROM promotions";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Акции</title>
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
        <h1>Акции ветеринарной клиники</h1>

        <?php if ($isAdmin): ?>
            <!-- Кнопка для добавления новой акции -->
            <button onclick="toggleAddForm()">Добавить новую акцию</button>

            <!-- Форма добавления новой акции (скрытая по умолчанию) -->
            <div id="add-form" style="display:none;">
                <h2>Добавить новую акцию</h2>
                <form action="promotions.php" method="POST">
                    <input type="hidden" name="action" value="add_promotion">
                    <label for="title">Название акции:</label>
                    <input type="text" id="title" name="title" required>

                    <label for="short_description">Краткое описание:</label>
                    <textarea id="short_description" name="short_description" required></textarea>

                    <label for="full_description">Полное описание:</label>
                    <textarea id="full_description" name="full_description" required></textarea>

                    <label for="price">Полная цена:</label>
                    <input type="number" id="price" name="price" required>

                    <label for="discount_price">Цена со скидкой:</label>
                    <input type="number" id="discount_price" name="discount_price" required>

                    <button type="submit">Добавить акцию</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="promotions-container">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="promotion-block">
                    <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                    <p><?php echo htmlspecialchars($row['short_description']); ?></p>
                    <button onclick="toggleDetails(<?php echo $row['id']; ?>)">Узнать больше</button>

                    <div id="details-<?php echo $row['id']; ?>" style="display:none;">
                        <p><?php echo nl2br(htmlspecialchars($row['full_description'])); ?></p>
                        <p>Итого: <?php echo $row['discount_price']; ?> вместо <?php echo $row['price']; ?> рублей</p>
                    </div>

                    <?php if ($isAdmin): ?>
                        <!-- Кнопки редактирования и удаления для администратора -->
                        <button 
                            class="edit-btn"
                            data-id="<?php echo $row['id']; ?>"
                            data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>"
                            data-short-description="<?php echo htmlspecialchars($row['short_description'], ENT_QUOTES); ?>"
                            data-full-description="<?php echo htmlspecialchars($row['full_description'], ENT_QUOTES); ?>"
                            data-price="<?php echo $row['price']; ?>"
                            data-discount-price="<?php echo $row['discount_price']; ?>"
                            onclick="openEditModal(this)">Редактировать</button>
                        <form action="promotions.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_promotion">
                            <input type="hidden" name="promotion_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту акцию?');">Удалить</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Модальное окно для редактирования акции -->
        <div id="editModal" style="display:none;">
            <h2>Редактировать акцию</h2>
            <form action="promotions.php" method="POST">
                <input type="hidden" name="action" value="edit_promotion">
                <input type="hidden" id="edit_promotion_id" name="promotion_id">

                <label for="edit_title">Название акции:</label>
                <input type="text" id="edit_title" name="title" required>

                <label for="edit_short_description">Краткое описание:</label>
                <textarea id="edit_short_description" name="short_description" required></textarea>

                <label for="edit_full_description">Полное описание:</label>
                <textarea id="edit_full_description" name="full_description" required></textarea>

                <label for="edit_price">Полная цена:</label>
                <input type="number" id="edit_price" name="price" required>

                <label for="edit_discount_price">Цена со скидкой:</label>
                <input type="number" id="edit_discount_price" name="discount_price" required>

                <button type="submit">Сохранить изменения</button>
                <button type="button" onclick="closeEditModal()">Отмена</button>
            </form>
        </div>
    </main>

    <script>
        function toggleAddForm() {
            const form = document.getElementById('add-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function toggleDetails(id) {
            const details = document.getElementById('details-' + id);
            details.style.display = details.style.display === "none" ? "block" : "none";
        }

        function openEditModal(button) {
            // Получаем данные из data-* атрибутов кнопки
            const id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const shortDescription = button.getAttribute('data-short-description');
            const fullDescription = button.getAttribute('data-full-description');
            const price = button.getAttribute('data-price');
            const discountPrice = button.getAttribute('data-discount-price');

            // Заполняем форму для редактирования данными
            document.getElementById('edit_promotion_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_short_description').value = shortDescription;
            document.getElementById('edit_full_description').value = fullDescription;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_discount_price').value = discountPrice;

            // Показываем модальное окно
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>

    <footer>
        <!-- Подвал сайта -->
    </footer>
</body>
</html>
