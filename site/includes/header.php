<?php require_once __DIR__ . '/../config/db.php'; ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лисі Котики Прага</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>

<div class="top-bar">Доставка по всій Європі за 3–7 днів • Гарантія здоров’я 100%</div>

<header class="header">
    <div class="container">
        <img src="assets/images/logo.png" alt="Лисі Котики Прага" class="logo">
        <button id="mobileMenuBtn" class="mobile-menu-btn" aria-label="Відкрити меню" aria-expanded="false">☰</button>
        <nav class="nav">
            <a href="admin_requests.php">Заявки</a>
            <a href="index.html">Головна</a>
            <a href="#">Каталог</a>
            <a href="about.html">Про нас</a>
            <a href="#">Доставка</a>
            <a href="contacts.html">Контакти</a>
        </nav>
        <div class="search-cart">
            <input type="text" placeholder="Пошук котиків...">
            <a href="#" class="button">Кошик (0)</a>
        </div>
        <div class="phone">+420 777 123 456</div>
    </div>
</header>

<main class="container">