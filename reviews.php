<?php
session_start(); // Стартуем сессию

require 'db.php'; // Подключаем базу данных

// Проверяем, был ли отзыв отправлен
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['comment'])) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $comment = $_POST['comment'];

    // Добавляем новый отзыв в базу данных
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, name, email, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $name, $email, $comment);
    $stmt->execute();
    $stmt->close();
}

// Удаление отзыва (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_review']) && $_SESSION['role'] == 'admin') {
    $review_id = $_POST['review_id']; // Получаем ID отзыва

    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $review_id);

    if ($stmt->execute()) {
        echo "Отзыв успешно удалён.";
    } else {
        echo "Ошибка при удалении отзыва: " . $stmt->error;
    }
    $stmt->close();
}

// Пагинация отзывов
$reviews_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $reviews_per_page;

// Получаем отзывы с ограничением для пагинации
$stmt = $conn->prepare("SELECT id, name, email, comment, created_at FROM reviews ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $reviews_per_page, $offset);
$stmt->execute();
$stmt->bind_result($review_id, $review_name, $review_email, $review_comment, $review_created_at);
$reviews = [];
while ($stmt->fetch()) {
    $reviews[] = [
        'id' => $review_id,
        'name' => $review_name,
        'email' => $review_email,
        'comment' => $review_comment,
        'created_at' => $review_created_at
    ];
}
$stmt->close();

// Подсчет общего количества отзывов для пагинации
$result = $conn->query("SELECT COUNT(*) as total_reviews FROM reviews");
$row = $result->fetch_assoc();
$total_reviews = $row['total_reviews'];
$total_pages = ceil($total_reviews / $reviews_per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Шапка страницы -->
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
        <h1>Отзывы</h1>

        <!-- Кнопка "Оставить отзыв" для зарегистрированных пользователей -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <button onclick="document.getElementById('review-form').style.display='block'">Оставить отзыв</button>
        <?php else: ?>
            <p>Только зарегистрированные пользователи могут оставлять отзывы.</p>
        <?php endif; ?>

        <!-- Форма для отправки отзыва -->
        <div id="review-form" style="display: none;">
            <form action="reviews.php" method="POST">
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>

                <label for="comment">Комментарий:</label>
                <textarea id="comment" name="comment" required></textarea>

                <button type="submit">Отправить отзыв</button>
            </form>
        </div>

        <!-- Отображение отзывов -->
        <h2>Отзывы наших клиентов</h2>
        <?php foreach ($reviews as $review): ?>
            <div class="review">
                <p><strong><?php echo htmlspecialchars($review['name']); ?></strong> (<?php echo htmlspecialchars($review['email']); ?>)</p>
                <p><?php echo htmlspecialchars($review['comment']); ?></p>
                <small>Оставлен: <?php echo $review['created_at']; ?></small>

                <!-- Если пользователь администратор, показываем кнопку удаления -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <form action="reviews.php" method="POST">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" name="delete_review" onclick="return confirm('Вы уверены, что хотите удалить этот отзыв?');">Удалить</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Пагинация -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="reviews.php?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Ветеринарная клиника. Все права защищены.</p>
    </footer>
</body>
</html>
