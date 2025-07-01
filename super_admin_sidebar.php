<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<div class="sidebar">
    <div class="sidebar-header">
        <img src="no-bg logo.png" alt="Logo" class="sidebar-logo">
    </div>
    <ul>
    <?php if (isSuperAdmin()): ?>
    <!-- Dashboard (Admin Only) -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'super_admin_dashboard.php' ? 'active' : '' ?>" 
        onclick="window.location.href='super_admin_dashboard.php'">
        <i class="fas fa-home"></i><br>
        <span>Dashboard</span>
    </li>
    
    <!-- Manage Users -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>" 
        onclick="window.location.href='manage_users.php'">
        <i class="fas fa-users"></i><br>
        <span>Manage Users</span>
    </li>

    <!-- System Edit -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'manage_system.php' ? 'active' : '' ?>" 
        onclick="window.location.href='manage_system.php'">
        <i class="fas fa-cogs"></i><br>
        <span>Manage System</span>
    </li>

    <br>
<br>
<br>
<br>

<br>
<br>
<br>

<br>
<br>
<br>

<br>
<br>
<br>

<br>
<br>

    
</div>

<?php endif; ?>

    
</div>



<style>
/* Sidebar Styling */
.sidebar {
    width: 70px;
    background-color:  #8a0000;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 20px;
    font-family: Arial, sans-serif;
    transition: width 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* Ensure space is evenly distributed */
}

/* Sidebar header */
.sidebar-header {
    display: flex;
    justify-content: center;
    padding: 0 20px;
    margin-bottom: 20px;
}

.sidebar-logo {
    width: 80px;
    height: 50px;
    margin-bottom: 50px;
    margin-top: 10px;
}

/* List Styling */
ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
    flex-grow: 2; /* Allow the list to take available space */
    color: white;
}

ul li {
    text-align: center;
    cursor: pointer;
    padding: 10px;
}

ul li i {
    font-size: 18px;
    display: block;
}

ul li span {
    font-size: 10px;
    display: block;
    margin-top: 4px;
    color: white;
}


ul li:hover {
    background-color: #444;
}

ul li.active {
    background-color: #D4AF37;
}

ul li.active:hover {
    background-color: #444;
}


/* Sidebar Bottom */
.sidebar-bottom ul {
    display: flex;
    flex-direction: column;
}

.sidebar-bottom li {
    margin-top: auto; /* Pushes the items to the bottom of the sidebar */
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        width: 60px;
    }

    .sidebar-logo {
        width: 30px;
        height: 30px;
    }

    ul li i {
        font-size: 18px;
    }
}

</style>