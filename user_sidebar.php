<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<div class="sidebar">
    <div class="sidebar-header">
        <img src="no-bg logo.png" alt="Logo" class="sidebar-logo">
    </div>
    <ul>
        <?php if (isAdmin()): ?>
            <!-- Dashboard (Admin Only) -->
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>" 
                onclick="window.location.href='admin_dashboard.php'">
                <i class="fas fa-home"></i>
            </li>

            <!-- Manage Users (Admin Only) -->
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_user.php' ? 'active' : '' ?>" 
                onclick="window.location.href='admin_user.php'">
                <i class="fas fa-users"></i>
            </li>
        <?php endif; ?>

        <ul>
    <!-- Dashboard -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'userdashboard.php' ? 'active' : '' ?>" 
        onclick="window.location.href='userdashboard.php'">
        <i class="fas fa-home"></i><br>
        <span>Dashboard</span>
    </li>

    <!-- To Do -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'userproject.php' ? 'active' : '' ?>" 
        onclick="window.location.href='userproject.php'">
        <i class="fas fa-tasks"></i><br>
        <span>Projects</span>
    </li>

    <!-- Logistics -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'userlogistics.php' ? 'active' : '' ?>" 
        onclick="window.location.href='userlogistics.php'">
        <i class="fas fa-truck"></i><br>
        <span>Logistics</span>
    </li>





<!-- Documents -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'userdocuments.php' ? 'active' : '' ?>" 
    onclick="window.location.href='userdocuments.php'">
    <i class="fas fa-file-alt"></i><br>
    <span>Docus</span>
</li>

    
    <!-- Portfolio -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'userportfolio.php' ? 'active' : '' ?>" 
        onclick="window.location.href='portfolio.php'">
        <i class="fas fa-folder"></i><br>
        <span>Portfolio</span>
    </li>

</ul>

<br>
<br>
<br>

<br>
<br>



<!-- Settings -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'usersettings.php' ? 'active' : '' ?>" 
    onclick="window.location.href='usersettings.php'">
    <i class="fas fa-cog"></i><br>
    <span>Settings</span>
</li>

<!-- Help -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'userhelp.php' ? 'active' : '' ?>" 
    onclick="window.location.href='userhelp.php'">
    <i class="fas fa-headset"></i><br>
    <span>Help</span>
</li>





    
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