<?php
session_start();
session_destroy(); // Завершаем сессию

// Перенаправляем на страницу входа
header('Location: index.php');
exit;
?>
