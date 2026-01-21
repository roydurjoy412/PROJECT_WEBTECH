<div class="sidebar">
    <div class="user-panel">
        <img src="images/user_avatar.png" alt="User" class="avatar-img">
        
        <div class="user-name"><?php echo $_SESSION['username']; ?></div>
        <div class="user-role">(<?php echo $_SESSION['role']; ?>)</div>
    </div>

    <a href="dashboard.php" class="nav-link <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-simple"></i> Dashboard
    </a>
    
    <?php if($_SESSION['role'] !== 'Salesman'): ?>
        <a href="categories.php" class="nav-link <?php echo ($activePage == 'category') ? 'active' : ''; ?>">
            <i class="fa-solid fa-list-ul"></i> Category
        </a>

        <a href="products.php" class="nav-link <?php echo ($activePage == 'product') ? 'active' : ''; ?>">
            <i class="fa-solid fa-cubes"></i> Product
        </a>
    <?php endif; ?>

    <a href="customers.php" class="nav-link <?php echo ($activePage == 'customer') ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i> Customer
    </a>

    <?php if($_SESSION['role'] !== 'Salesman'): ?>
        <a href="suppliers.php" class="nav-link <?php echo ($activePage == 'supplier') ? 'active' : ''; ?>">
            <i class="fa-solid fa-truck-fast"></i> Supplier
        </a>
    <?php endif; ?>

    <a href="orders.php" class="nav-link <?php echo ($activePage == 'order') ? 'active' : ''; ?>">
        <i class="fa-solid fa-cart-shopping"></i> Order
    </a>
        <a href="reports.php" class="nav-link <?php echo ($activePage == 'reports') ? 'active' : ''; ?>">
    <i class="fa-solid fa-chart-line"></i> Reports
</a>
    <?php if($_SESSION['role'] !== 'Salesman'): ?>
        <a href="purchase_orders.php" class="nav-link <?php echo ($activePage == 'purchase') ? 'active' : ''; ?>">
            <i class="fa-solid fa-truck-ramp-box"></i> Purchase Order
        </a>
    <?php endif; ?>

    <?php if($_SESSION['role'] === 'Admin'): ?>
    <a href="users.php" class="nav-link <?php echo ($activePage == 'users') ? 'active' : ''; ?>">
        <i class="fa-solid fa-people-roof"></i> System Users
    </a>
    <?php endif; ?>

    <a href="auth/logout.php" class="nav-link" style="margin-top: auto; color: #ff6b6b;">
        <i class="fa-solid fa-right-from-bracket"></i> Log Out
    </a>
</div>