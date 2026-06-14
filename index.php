<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepali Delights - Authentic Nepali Cuisine</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav>
        <div class="logo">Nepali Delights</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="menu.php">Menu</a></li>
            <li><a href="#fan-favorites">Fan Favorites</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
       <div class="user-actions">
    <?php if (isset($_SESSION['user_id'])): ?>
        <span style="color:#ed8936; margin-right:15px;">
            Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="logout.php" style="color:#e53e3e; text-decoration:none; font-weight:bold;">
            Logout
        </a>
    <?php else: ?>
        <button class="login" onclick="location.href='login.php'">Login / Register</button>
    <?php endif; ?>
</div>
    </nav>

    <header class="hero" style="background-image: url('https://nht-api.nepalhikingteam.com/media/attachments/Nepali-Foods.jpg');">
        <div class="hero-content">
            <h1>Authentic Nepali Flavors Delivered Fresh</h1>
            <p>Discover crave-worthy Himalayan dishes packed with flavor, perfect for family meals and special occasions.</p>
            <a href="menu.php">
                <button class="see-menu">See Menu</button>
            </a>
        </div>
    </header>

    <section id="fan-favorites" class="fan-favorites">
        <h2>Fan Favorites</h2>
        <p>Discover full of flavor, family-approved recipes that are easy to make.</p>
        <div class="cards">
            <div class="card">
                <img src="https://spontaneoustomato.com/wp-content/uploads/2014/03/img_6037-5.jpg?w=640" alt="Steamed Momos">
                <div class="card-title">Steamed Momos</div>
            </div>
            <div class="card">
                <img src="https://junifoods.com/wp-content/uploads/2023/04/easy-chicken-choila.png" alt="Chicken Choila">
                <div class="card-title">Chicken Choila</div>
            </div>
            <div class="card">
                <img src="https://delishglobe.com/wp-content/uploads/2025/05/Nepalese-Dal-Bhat-Lentil-and-Rice-Platter.png" alt="Dal Bhat">
                <div class="card-title">Dal Bhat</div>
            </div>
            <div class="card">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgbQp28cpuyWIOPS4qpqvuZ4YEy5ULCU1x2q9ZGy35njJmy_bKU_3Jv83DiAr7NJjk_K-w1jGxksF29Ob768W8vjn4iH4LOh19WclawAOHEbzGXvvPxcf4FgrK4xmbTf2q4AVRrum_guKE/s1600/Vegan+spicy+noodle+soup+nepali+thukpa+3.jpg" alt="Thukpa">
                <div class="card-title">Thukpa</div>
            </div>
        </div>
    </section>

    <section class="mains">
        <h2>Mains</h2>
        <p>Our hearty main courses packed with flavor.</p>
        <div class="cards">
            <div class="card">
                <img src="https://www.tastingtable.com/img/gallery/7-nepali-dishes-you-need-to-try-at-least-once/intro-1759769496.jpg" alt="Aloo Tama">
                <div class="card-title">Aloo Tama</div>
            </div>
        </div>
    </section>

    <section class="appetizers">
        <h2>Appetizers</h2>
        <p>Start your meal with bold and spicy flavors.</p>
        <div class="cards">
            <div class="card">
                <img src="https://bhojannepal.com/images/menu/a21d6b3909cd3bc357bcb42f19ce348b.jpg" alt="Buff Choila">
                <div class="card-title">Choila</div>
            </div>
        </div>
    </section>

    <section class="desserts">
        <h2>Desserts</h2>
        <p>Sweet treats to complete your Nepali feast.</p>
        <div class="cards">
            <div class="card">
                <img src="https://www.shutterstock.com/image-photo/selroti-famous-nepali-style-sweet-260nw-1910218087.jpg" alt="Sel Roti">
                <div class="card-title">Sel Roti</div>
            </div>
            <div class="card">
                <img src="https://nepaltraveller.com/laravel-filemanager/photos/54/juju%20dhau/FF-RZy6VUAE190-.jpg" alt="King Curd - Juju Dhau">
                <div class="card-title">King Curd</div>
            </div>
        </div>
    </section>

    <section id="about" class="about">
        <h2>About Us</h2>
        <div class="about-container">
            <div class="about-text">
                <h3>Our Food</h3>
                <p>We serve authentic Nepali cuisine including Aloo Tama, Choila, traditional appetizers, refreshing drinks, and delicious desserts. Our ingredients are fresh, locally sourced, and prepared with traditional recipes.</p>

                <h3>Our Location</h3>
                <p>We are located at <strong>Babarmall, Kathmandu</strong>.<br>Visit us for a cozy dining experience with authentic flavors.</p>

                <h3>Our Chef</h3>
                <p>Our head chef <strong>Dawa Dong</strong> brings years of experience in traditional Nepali cooking, delivering rich taste and quality in every dish.</p>
            </div>

            <div class="about-image">
                <img src="https://source.unsplash.com/600x400/?nepali,food,restaurant" alt="Nepali restaurant atmosphere">
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <h2>Contact Us</h2>
        <p>Have questions? Reach out to us!</p>
        <p><strong>Email:</strong> info@nepalidelights.com</p>
        <p><strong>Phone:</strong> +977 980-1234567</p>
        <p><strong>Address:</strong> Babarmall, Kathmandu, Nepal</p>
    </section>

    <footer style="text-align:center; padding: 40px 20px; background:#2d3748; margin-top:60px; color:#a0aec0;">
        <p>© 2026 Nepali Delights. All rights reserved.</p>
        <p style="color:#ed8936; font-weight:bold;">Thank You!!</p>
    </footer>

</body>
</html>