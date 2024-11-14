<?php
session_start(); // Стартуем сессию

require 'db.php'; // Подключаем базу данных

// Обработка добавления нового товара (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']) && $_SESSION['role'] == 'admin') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $photo = '';

    // Проверка, загружено ли изображение
    if (!empty($_FILES['photo']['name'])) {
        // Устанавливаем путь для сохранения изображения
        $photo = 'uploads/products/' . basename($_FILES['photo']['name']);
        // Сохраняем изображение на сервер
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    // Добавляем новый товар в базу данных
    $stmt = $conn->prepare("INSERT INTO products (name, category, price, photo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $name, $category, $price, $photo);
    $stmt->execute();
    $stmt->close();
    header('Location: products.php');
    exit;
}

// Обработка удаления товара (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product']) && $_SESSION['role'] == 'admin') {
    $product_id = $_POST['product_id'];

    // Удаляем товар из базы данных
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
    header('Location: products.php');
    exit;
}

// Обработка редактирования товара (только для администратора)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product']) && $_SESSION['role'] == 'admin') {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $photo = '';

    // Проверяем, загружено ли новое изображение
    if (!empty($_FILES['photo']['name'])) {
        $photo = 'uploads/products/' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("ssdsi", $name, $category, $price, $photo, $product_id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $name, $category, $price, $product_id);
    }

    $stmt->execute();
    $stmt->close();
    header('Location: products.php');
    exit;
}

// Получаем товары из базы данных
$stmt = $conn->prepare("SELECT id, name, category, price, photo FROM products");
$stmt->execute();
$stmt->bind_result($product_id, $product_name, $product_category, $product_price, $product_photo);
$products = [];
while ($stmt->fetch()) {
    $products[] = [
        'id' => $product_id,
        'name' => $product_name,
        'category' => $product_category,
        'price' => $product_price,
        'photo' => $product_photo
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары в зоомагазине</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        // Функция для показа товаров по категориям
        function toggleCategory(category) {
            var items = document.getElementsByClassName('product-item-' + category);
            for (var i = 0; i < items.length; i++) {
                if (items[i].style.display === 'none') {
                    items[i].style.display = 'block';
                } else {
                    items[i].style.display = 'none';
                }
            }
        }
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
        <h1>Товары в зоомагазине</h1>

        <!-- Кнопка для добавления нового товара (только для администратора) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button onclick="document.getElementById('add-product-form').style.display='block'">Добавить товар</button>
        <?php endif; ?>

        <!-- Форма для добавления нового товара (скрыта по умолчанию) -->
        <div id="add-product-form" style="display: none;">
            <form action="products.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_product" value="1">
                <label for="name">Наименование:</label>
                <input type="text" id="name" name="name" required>

                <label for="category">Вид товара:</label>
                <select id="category" name="category" required>
                    <option value="Витамины">Витамины</option>
                    <option value="Дезинфекция">Дезинфекция</option>
                    <option value="Игрушки">Игрушки</option>
                    <option value="Корма">Корма</option>
                    <option value="Лакомства">Лакомства</option>
                    <option value="Намордники/поводки">Намордники/поводки</option>
                    <option value="Переноски">Переноски</option>
                    <option value="Повседневный уход">Повседневный уход</option>
                    <option value="Противопаразитарные">Противопаразитарные</option>
                </select>

                <label for="price">Цена:</label>
                <input type="number" id="price" name="price" required>

                <label for="photo">Фото товара:</label>
                <input type="file" id="photo" name="photo">

                <button type="submit">Добавить товар</button>
            </form>
        </div>

        <!-- Отображение категорий товаров -->
        <h2>Категории товаров</h2>
        <ul>
            <li><a href="#" onclick="toggleCategory('Витамины')">Витамины</a></li>
            <li><a href="#" onclick="toggleCategory('Дезинфекция')">Дезинфекция</a></li>
            <li><a href="#" onclick="toggleCategory('Игрушки')">Игрушки</a></li>
            <li><a href="#" onclick="toggleCategory('Корма')">Корма</a></li>
            <li><a href="#" onclick="toggleCategory('Лакомства')">Лакомства</a></li>
            <li><a href="#" onclick="toggleCategory('Намордники/поводки')">Намордники/поводки</a></li>
            <li><a href="#" onclick="toggleCategory('Переноски')">Переноски</a></li>
            <li><a href="#" onclick="toggleCategory('Повседневный уход')">Повседневный уход</a></li>
            <li><a href="#" onclick="toggleCategory('Противопаразитарные')">Противопаразитарные</a></li>
        </ul>

        <!-- Отображение товаров по категориям -->
        <?php foreach ($products as $product): ?>
            <div class="product-item-<?php echo htmlspecialchars($product['category']); ?>" style="display: none;">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>Цена: <?php echo number_format($product['price'], 2); ?> руб.</p>

                <?php if (!empty($product['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($product['photo']); ?>" alt="Фото товара" style="max-width: 150px;">
                <?php endif; ?>

                <!-- Если пользователь администратор, показываем кнопки редактирования и удаления -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <form action="products.php" method="POST" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="delete_product" value="1">
                        <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">Удалить</button>
                    </form>
                    <button onclick="document.getElementById('edit-product-form-<?php echo $product['id']; ?>').style.display='block'">Редактировать</button>

                    <!-- Форма для редактирования товара (скрыта по умолчанию) -->
                    <div id="edit-product-form-<?php echo $product['id']; ?>" style="display: none;">
                        <form action="products.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="edit_product" value="1">
                            <label for="name">Наименование:</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

                            <label for="category">Вид товара:</label>
                            <select name="category" required>
                                <option value="Витамины" <?php if ($product['category'] == 'Витамины') echo 'selected'; ?>>Витамины</option>
                                <option value="Дезинфекция" <?php if ($product['category'] == 'Дезинфекция') echo 'selected'; ?>>Дезинфекция</option>
                                <option value="Игрушки" <?php if ($product['category'] == 'Игрушки') echo 'selected'; ?>>Игрушки</option>
                                <option value="Корма" <?php if ($product['category'] == 'Корма') echo 'selected'; ?>>Корма</option>
                                <option value="Лакомства" <?php if ($product['category'] == 'Лакомства') echo 'selected'; ?>>Лакомства</option>
                                <option value="Намордники/поводки" <?php if ($product['category'] == 'Намордники/поводки') echo 'selected'; ?>>Намордники/поводки</option>
                                <option value="Переноски" <?php if ($product['category'] == 'Переноски') echo 'selected'; ?>>Переноски</option>
                                <option value="Повседневный уход" <?php if ($product['category'] == 'Повседневный уход') echo 'selected'; ?>>Повседневный уход</option>
                                <option value="Противопаразитарные" <?php if ($product['category'] == 'Противопаразитарные') echo 'selected'; ?>>Противопаразитарные</option>
                            </select>

                            <label for="price">Цена:</label>
                            <input type="number" name="price" value="<?php echo number_format($product['price'], 2); ?>" required>

                            <label for="photo">Фото товара:</label>
                            <input type="file" name="photo">

                            <button type="submit">Сохранить изменения</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>

    <footer>
        <p>&copy; 2024 Ветеринарная клиника. Все права защищены.</p>
    </footer>
</body>
</html>
