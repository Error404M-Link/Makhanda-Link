<?php

include "Error404Connection.php";

/* SQL query to retrieve all incident tickets */

$sql = "SELECT username,
               last_login_date,
               registered_date
        FROM  admin
        ORDER BY last_login_date DESC";

/* Execute the SQL query */

$result = $conn->query($sql);

/* Check for SQL errors */

if (!$result) {
    die("Error retrieving tickets: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN Dashboard</title>

    <!-- CSS -->
    <link rel="stylesheet" href="admindashboard.css">
    <link rel="stylesheet" href="dashboardSidebar.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>  

 
<div class="container">

    <!--#################### SIDE BAR ###########################-->

    <input type="checkbox" id="sidebarToggle">
    <label for="sidebarToggle" class="hamburger">
        <span></span><span></span><span></span>
    </label>

    <aside class="sidebar">
        <div class="logo">
            <img src="MakanaLogo.svg" alt="M-link logo" class="sidebar-logo">
            <h2>M-LINK</h2>
            <p>Makhanda Connect</p>
        </div>

        <div class="profile">
            <img src="profile.jpg" alt="Profile">
            <h3>Qhamani Somhlahlo</h3>
            <p>System Admin</p>
        </div>

        <ul class="menu">
            <li class="active">
                <a href="#dashboard">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="WardTicketManagement.php">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Municipal Tickets</span>
                </a>
            </li>
            <li>
                <a href="generateSummaryForm.php">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Wards</span>
                </a>
            </li>
            <li>
                <a href="UserM.php">
                    <i class="fa-solid fa-ticket"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="userDashboard.html">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Ward Councillors</span>
                </a>
            </li>
            <li>
                <a href="manageContent.php">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Manage Content</span>
                </a>
            </li>
            <li>
                <a href="profile_settings.js">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="settings.html">
                    <i class="fa-solid fa-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li>
                <a href="homepage.php" class="logout-link" >
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>


    <!-- ================= MAIN ================= -->

    <main class="main-content">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="icons">

                <div class="icon">
                    <i class="fa-regular fa-bell"></i>
                    <span>3</span>
                </div>

                <div class="icon">
                    <i class="fa-regular fa-envelope"></i>
                    <span>2</span>
                </div>

            </div>

            <div class="user">

                <img src="profile.jpg" alt="">

                <div>

                    <h4>Qhamani Somhlahlo</h4>

                    <small>System Admin</small>

                </div>

            </div>

        </header>

        <!-- SUMMARY CARDS -->

        <section class="cards">

            <div class="card">

                <i class="fa-solid fa-triangle-exclamation"></a></i>

                <h4>Helpdesk Tickets</h4>

                <h2>15</h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-file-circle-plus"></i>

                <h4>User Access Logs </h4>

                <h2>815</h2>

            </div>

            <div class="card">

                <a href="UserM.php"></a>
                <i class="fa-solid fa-circle-check"></i>

                <h4>User Management</h4>

                <h2>97</h2>

            </div>

            <div class="card">

                <a href="ward_notices.php"></a>
                <i class="fa-solid fa-bullhorn"><a href=></a></i>

                <h4>Active Notices</h4>

                <h2>12</h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-calendar"></i>

                <h4>Patch Management</h4>

                <h2>8</h2>

            </div>

        </section>

        <!-- WELCOME SECTION -->

        <section class="welcome">

            <div class="welcome-icon">

                <i class="fa-solid fa-building-columns"></i>

            </div>

            <h1>Welcome Back, Qhamani Somhlahlo!</h1>

            <p>
                Here's what's happening in Makhanda.
            </p>

            <p>
                Use the menu to navigate, view municipal tickets,
                manage content and stay updated with community
                information.
            </p>

        </section>

        <!-- FOOTER -->
    <footer>

        <p>© 2026 M-LINK | Empowering Communities</p>

    </footer>
        

    </main>

</div>
 
<script src="dashboard.js"></script>

</body>
</html>
