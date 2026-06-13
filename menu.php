<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Nepali Delights</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .price { font-weight: bold; color: #ed8936; margin-top: 8px; }
        .quantity-controls { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 10px; }
        .qty-btn { background: #4a5568; border: none; color: white; width: 30px; height: 30px; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: all 0.2s; }
        .qty-btn:hover:not(:disabled) { background: #ed8936; }
        .qty-btn:disabled { background: #2d3748; cursor: not-allowed; opacity: 0.6; }
        .quantity-display { min-width: 30px; text-align: center; font-size: 1.15rem; font-weight: bold; color: #ed8936; }
        .cart-info { text-align: center; margin: 25px 0; font-size: 1.2rem; color: #e2e8f0; }
        .cart-info strong { color: #ed8936; }
        .cart-summary { background: #2d3748; border-radius: 10px; padding: 20px; max-width: 500px; margin: 20px auto; color: #e2e8f0; display: none; }
        .cart-summary ul { list-style: none; padding: 0; margin: 10px 0; }
        .cart-summary li { padding: 8px 0; border-bottom: 1px solid #4a5568; }
        .cart-summary li:last-child { border-bottom: none; }
        .total-section { text-align: center; margin: 50px 0; }
        .confirm-btn { background: #38a169; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: background 0.3s; }
        .confirm-btn:hover { background: #2f855a; }
    </style>
</head>
<body>

<nav>
    <div class="logo">Nepali Delights</div>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
    </ul>
    <div class="user-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="color:#ed8936; margin-right:15px;">
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="logout.php" style="color:#e53e3e; text-decoration:none;">Logout</a>
        <?php else: ?>
            <button class="login" onclick="location.href='login.php'">Login / Register</button>
        <?php endif; ?>
    </div>
</nav>

<div class="cart-info">
    Items in cart: <strong id="cartItemCount">0</strong> 
    | Total: Rs. <strong id="cartTotal">0</strong>
</div>

<div class="cart-summary" id="cartSummary">
    <h3>Your Cart</h3>
    <ul id="cartItemsList"></ul>
    <p><strong>Total: Rs. <span id="summaryTotal">0</span></strong></p>
</div>

<section class="mains"><h2>Mains</h2><div class="cards"></div></section>
<section class="appetizers"><h2>Appetizers</h2><div class="cards"></div></section>
<section class="desserts"><h2>Desserts</h2><div class="cards"></div></section>
<section class="drinks"><h2>Drinks</h2><div class="cards"></div></section>

<div class="total-section">
    <button class="confirm-btn" id="confirmBtn" onclick="showOrderSummary()">Confirm Order</button>
</div>

<script>
    let cart = JSON.parse(localStorage.getItem("cart") || "{}");

    async function loadMenu() {
        try {
            const res = await fetch('api/get_menu.php');
            if (!res.ok) throw new Error('Failed to fetch menu');
            const items = await res.json();

            document.querySelectorAll('.cards').forEach(el => el.innerHTML = '');

            const containers = {
                mains: document.querySelector('.mains .cards'),
                appetizers: document.querySelector('.appetizers .cards'),
                desserts: document.querySelector('.desserts .cards'),
                drinks: document.querySelector('.drinks .cards'),
                others: document.querySelector('.mains .cards')
            };

            items.forEach(item => {
                const card = document.createElement('div');
                card.className = 'card';
                card.dataset.name = item.name;
                card.dataset.price = item.price;

                card.innerHTML = `
                    <img src="${item.image_url || 'https://via.placeholder.com/300x200?text=' + encodeURIComponent(item.name)}" alt="${item.name}">
                    <div class="card-title">${item.name}</div>
                    <div class="price">Rs. ${parseFloat(item.price).toFixed(2)}</div>
                    <div class="quantity-controls">
                        <button class="qty-btn minus" disabled>−</button>
                        <span class="quantity-display">0</span>
                        <button class="qty-btn plus">+</button>
                    </div>
                `;

                const target = containers[item.category?.toLowerCase()] || containers.others;
                if (target) target.appendChild(card);
            });

            attachQuantityListeners();
            updateCartDisplay();
        } catch (err) {
            console.error('Menu load error:', err);
            alert("Cannot load menu. Please try again later.");
        }
    }

    function attachQuantityListeners() {
        document.querySelectorAll('.card').forEach(card => {
            const name = card.dataset.name;
            const price = Number(card.dataset.price);
            const plus = card.querySelector('.plus');
            const minus = card.querySelector('.minus');
            const display = card.querySelector('.quantity-display');

            if (!cart[name]) cart[name] = { name, price, quantity: 0 };

            function syncDisplay() {
                display.textContent = cart[name].quantity;
                minus.disabled = cart[name].quantity <= 0;
            }

            plus.addEventListener('click', () => {
                cart[name].quantity++;
                syncDisplay();
                updateCartDisplay();
            });

            minus.addEventListener('click', () => {
                if (cart[name].quantity > 0) {
                    cart[name].quantity--;
                    syncDisplay();
                    updateCartDisplay();
                }
            });

            syncDisplay();
        });
    }

    function updateCartDisplay() {
        let itemCount = 0, total = 0;
        for (let key in cart) {
            if (cart[key] && cart[key].quantity > 0) {
                itemCount += cart[key].quantity;
                total += cart[key].price * cart[key].quantity;
            }
        }

        document.getElementById("cartItemCount").innerText = itemCount;
        document.getElementById("cartTotal").innerText = total;

        const summaryDiv = document.getElementById("cartSummary");
        const list = document.getElementById("cartItemsList");
        const summaryTotal = document.getElementById("summaryTotal");

        if (list && summaryTotal) {
            list.innerHTML = "";
            let hasItems = false;
            for (let key in cart) {
                if (cart[key] && cart[key].quantity > 0) {
                    hasItems = true;
                    const li = document.createElement("li");
                    li.textContent = `${cart[key].name} × ${cart[key].quantity} = Rs. ${cart[key].price * cart[key].quantity}`;
                    list.appendChild(li);
                }
            }
            summaryTotal.textContent = total;
            summaryDiv.style.display = hasItems ? "block" : "none";
        }

        localStorage.setItem("cart", JSON.stringify(cart));
    }

    // Final corrected version with eSewa payment trigger
    function showOrderSummary() {
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const username   = <?php echo json_encode($_SESSION['username'] ?? 'Guest'); ?>;

        if (!isLoggedIn) {
            if (confirm("Login required to confirm your order!\n\nGo to login page now?")) {
                window.location.href = "login.php";
            }
            return;
        }

        let total = 0;
        let hasItems = false;

        for (let key in cart) {
            if (cart[key] && cart[key].quantity > 0) {
                hasItems = true;
                total += cart[key].price * cart[key].quantity;
            }
        }

        if (!hasItems) {
            alert("Nothing in the cart.\nPlease add some items first.");
            return;
        }

        let message = `Hello ${username}!\n\nYour order summary:\n\n`;
        for (let key in cart) {
            if (cart[key] && cart[key].quantity > 0) {
                const subtotal = cart[key].price * cart[key].quantity;
                message += `${cart[key].name} × ${cart[key].quantity} = Rs. ${subtotal.toFixed(2)}\n`;
            }
        }
        message += `\n────────────────────\nTotal: Rs. ${total.toFixed(2)}\n\nConfirm your order and proceed to payment?`;

        if (!confirm(message)) return;

        // Save order first
        fetch('api/save_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart, total: total.toFixed(2) }),
            credentials: 'include'
        })
        .then(res => {
            console.log('[DEBUG] save_order status:', res.status);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            console.log('[DEBUG] save_order response:', data);
            if (!data.success) {
                throw new Error(data.error || 'Order save failed');
            }

            const orderId = data.order_id;

            // Optional: Show saved message
            alert(`Order saved! ID: ${orderId}\nRedirecting to eSewa payment...`);

            // Trigger eSewa payment
            initiateEsewaPayment(orderId, total);
        })
        .catch(err => {
            console.error('[DEBUG] save_order error:', err);
            alert("Failed to save order: " + err.message);
        });
    }

    // eSewa payment initiation with debug
    async function initiateEsewaPayment(orderId, amount) {
        console.log('[DEBUG] Starting eSewa for Order:', orderId, 'Amount:', amount);

        try {
            const payload = {
                amount: amount,
                order_id: orderId,
                product_name: 'Nepali Delights Order'
            };

            console.log('[DEBUG] Sending to esewa_initiate:', payload);

            const res = await fetch('api/esewa_initiate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'include'
            });

            console.log('[DEBUG] esewa_initiate status:', res.status);

            if (!res.ok) {
                const errorText = await res.text();
                throw new Error(`Initiate failed: ${res.status} - ${errorText}`);
            }

            const result = await res.json();

            console.log('[DEBUG] esewa_initiate response:', result);

            if (!result.success) {
                throw new Error(result.error || 'Payment setup failed');
            }

            console.log('[DEBUG] Creating eSewa form to:', result.form_url);

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = result.form_url;

            Object.entries(result.form_data).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            console.log('[DEBUG] Submitting form to eSewa...');
            form.submit();
        } catch (err) {
            console.error('[DEBUG] eSewa initiation error:', err);
            alert("Cannot start eSewa payment: " + err.message);
        }
    }

    document.addEventListener("DOMContentLoaded", loadMenu);
</script>

</body>
</html>