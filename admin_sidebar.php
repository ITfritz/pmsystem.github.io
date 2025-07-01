<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<div class="sidebar">
    <div class="sidebar-header">
        <img src="no-bg logo.png" alt="Logo" class="sidebar-logo">
    </div>
    <ul>
        <?php if (isAdmin()): ?>
            <!-- Dashboard (Admin Only) -->
    <!-- Dashboard -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>" 
        onclick="window.location.href='admin_dashboard.php'">
        <i class="fas fa-home"></i><br>
        <span>Dashboard</span>
    </li>
    

    <!-- To Do -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'project.php' ? 'active' : '' ?>" 
        onclick="window.location.href='project.php'">
        <i class="fas fa-tasks"></i><br>
        <span>Projects</span>
    </li>

        <?php endif; ?>

        <ul>

    <!-- Logistics -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'logistics.php' ? 'active' : '' ?>" 
        onclick="window.location.href='logistics.php'">
        <i class="fas fa-truck"></i><br>
        <span>Logistics</span>
    </li>

    <!-- Analytics -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>" 
    onclick="window.location.href='analytics.php'">
    <i class="fas fa-chart-line"></i><br>
    <span>Stats</span>
</li>

   <!-- Calendar -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : '' ?>" 
    onclick="window.location.href='calendar.php'">
    <i class="fas fa-calendar-alt"></i><br>
    <span>Calendar</span>
</li>


    <!-- Documents -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : '' ?>" 
    onclick="window.location.href='documents.php'">
    <i class="fas fa-file-alt"></i><br>
    <span>Docus</span>
</li>

    <!-- Portfolio -->
    <li class="<?= basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'active' : '' ?>" 
        onclick="window.location.href='portfolio.php'">
        <i class="fas fa-folder"></i><br>
        <span>Portfolio</span>
    </li>

</ul>

<br>
<br>
<br>

<!-- Settings -->
<li class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" 
    onclick="window.location.href='settings.php'">
    <i class="fas fa-cog"></i><br>
    <span>Settings</span>
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