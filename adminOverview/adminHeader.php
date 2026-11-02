<?php
/*
 * adminHeader.php
 * ---------------------------------------------------------------------
 * Shared M-LINK admin SIDEBAR + TOPBAR (identical to admindashboard.php).
 *
 * HOW TO USE IN A PAGE
 *   1. In <head>, load the fonts/icons and (LAST) the layout stylesheet:
 *        <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
 *        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
 *        <link rel="stylesheet" href="adminLayout.css">
 *   2. Straight after <body>, open a PHP block that sets the active menu
 *      key and includes this file:
 *        $adminActivePage = "tickets";
 *        require __DIR__ . "/adminHeader.php";
 *   3. Put the page content here.
 *   4. Before the page's own script tags and the closing body tag, open a
 *      PHP block and include the footer:
 *        require __DIR__ . "/adminFooter.php";
 *
 * The page must already have started the session and loaded $conn
 * (require_once "Error404Connection.php") before this file is included.
 *
 * TO ADD / RENAME / REORDER A SIDEBAR LINK: edit $layoutNavItems below.
 * It is the only place the menu lives, so every page stays consistent.
 * ---------------------------------------------------------------------
 */

// ---------------------------------------------------------------------
// SIDEBAR MENU  (key, link, label, icon file inside ../icons/)
// ---------------------------------------------------------------------
$layoutNavItems = [
    ["key" => "dashboard",   "href" => "admindashboard.php",      "label" => "Dashboard",                 "icon" => "dashboard.svg"],
    ["key" => "tickets",     "href" => "WardTicketManagement.php", "label" => "Municipal Tickets",         "icon" => "ticket.svg"],
    ["key" => "councillors", "href" => "WardCouncillor.php",      "label" => "Assign Councillors",        "icon" => "man.svg"],
    ["key" => "reports",     "href" => "reports.php",             "label" => "Reports",                   "icon" => "reports.svg"],
    ["key" => "users",       "href" => "UserM.php",               "label" => "User Management",           "icon" => "manage_accounts.svg"],
    ["key" => "services",    "href" => "manageServices.php",      "label" => "Manage Municipal Services", "icon" => "service.svg"],
    ["key" => "verify",      "href" => "verifyAccount.php",       "label" => "Verify Accounts",           "icon" => "pendingVerification.svg"],
    ["key" => "staff",       "href" => "assignedCouncillor.php",  "label" => "All Staff Members",         "icon" => "manage_accounts.svg"],
    ["key" => "notices",     "href" => "manageNotices.php",       "label" => "Manage Notices",            "icon" => "notifications.svg"],
    ["key" => "addadmin",    "href" => "addAdmin.php",            "label" => "Add Admin",                 "icon" => "profile.svg"],
    ["key" => "messages",    "href" => "messages.php",            "label" => "Messages",                  "icon" => "message.svg"],
    ["key" => "profile",     "href" => "settings.php",            "label" => "Profile",                   "icon" => "account_circle.svg"],
    ["key" => "inactive",    "href" => "inactiveAccounts.php",    "label" => "Inactive Accounts",         "icon" => "unarchive.svg"],
    ["key" => "logout",      "href" => "logout.php",              "label" => "Logout",                    "icon" => "logout.svg"],
];

$layoutActive = $adminActivePage ?? "";

// ---------------------------------------------------------------------
// LOGGED-IN USER INFORMATION (sidebar profile + topbar profile + badges)
// Every query is wrapped in try/catch so a missing table or a database
// hiccup can never break the page that included this file.
// ---------------------------------------------------------------------
$layoutUsername = $_SESSION["username"] ?? "";
$layoutName     = $layoutUsername !== "" ? $layoutUsername : "Administrator";
$layoutRole     = trim($_SESSION["user_role"] ?? $_SESSION["role"] ?? "admin");
$layoutImage    = "";
$layoutSignups  = 0;
$layoutUnread   = 0;

if (isset($conn) && $conn instanceof mysqli && $layoutUsername !== "") {

    // name, role and profile picture
    try {
        $layoutStmt = $conn->prepare("
            SELECT u.first_Name, u.surname, u.User_role, p.image_path
            FROM `user` u
            LEFT JOIN profile_images p
                ON u.profile_id = p.profile_id
            WHERE u.username = ?
            LIMIT 1
        ");
        if ($layoutStmt) {
            $layoutStmt->bind_param("s", $layoutUsername);
            $layoutStmt->execute();
            $layoutResult = $layoutStmt->get_result();
            if ($layoutResult && $layoutRow = $layoutResult->fetch_assoc()) {
                $layoutFull = trim(($layoutRow["first_Name"] ?? "") . " " . ($layoutRow["surname"] ?? ""));
                if ($layoutFull !== "") {
                    $layoutName = $layoutFull;
                }
                if (!empty($layoutRow["User_role"])) {
                    $layoutRole = $layoutRow["User_role"];
                }
                $layoutImage = trim($layoutRow["image_path"] ?? "");
            }
            $layoutStmt->close();
        }
    } catch (\Throwable $layoutError) {
        // keep the fallbacks above
    }

    // new Ward Councillor / Municipal Staff sign-ups still waiting for verification - bell badge
    try {
        $layoutSignupResult = $conn->query("
            SELECT COUNT(*) AS total
            FROM `user`
            WHERE verified = 0
            AND LOWER(REPLACE(User_role, '_', ' ')) IN ('ward councillor', 'municipal staff')
        ");
        if ($layoutSignupResult && $layoutSignupRow = $layoutSignupResult->fetch_assoc()) {
            $layoutSignups = (int)$layoutSignupRow["total"];
        }
    } catch (\Throwable $layoutError) {
        // badge simply stays hidden
    }

    // unread internal messages for this user - message badge
    try {
        $layoutMsgStmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM internal_messages
            WHERE receiver_username = ?
            AND is_read = 0
        ");
        if ($layoutMsgStmt) {
            $layoutMsgStmt->bind_param("s", $layoutUsername);
            $layoutMsgStmt->execute();
            $layoutMsgResult = $layoutMsgStmt->get_result();
            if ($layoutMsgResult && $layoutMsgRow = $layoutMsgResult->fetch_assoc()) {
                $layoutUnread = (int)$layoutMsgRow["total"];
            }
            $layoutMsgStmt->close();
        }
    } catch (\Throwable $layoutError) {
        // badge simply stays hidden
    }
}

// #########################################################################
// PROFILE PICTURE URL
// uploaded picture -> profile.jpg in this folder -> ../profile.jpg -> icon
// ########################################################################
$layoutProfileUrl = "";

if ($layoutImage !== "") {
    $layoutPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $layoutImage);
    if (file_exists($layoutPath)) {
        $layoutProfileUrl = $layoutImage . "?v=" . filemtime($layoutPath);
    }
}

if ($layoutProfileUrl === "") {
    foreach (["profile.jpg", "../profile.jpg"] as $layoutFallback) {
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $layoutFallback))) {
            $layoutProfileUrl = $layoutFallback;
            break;
        }
    }
}
?>
<div class="dashboard-container">

    <!--
         SIDEBAR
     -->
    <aside
        class="sidebar"
        id="sidebar"
    >

        <!-- ADMIN PROFILE -->
        <div class="sidebar-profile">

            <div class="sidebar-avatar">
                <?php if ($layoutProfileUrl !== ""): ?>
                    <img
                        src="<?php echo htmlspecialchars($layoutProfileUrl, ENT_QUOTES, "UTF-8"); ?>"
                        alt="Administrator Profile"
                    >
                <?php else: ?>
                    <span class="material-icons">
                        account_circle
                    </span>
                <?php endif; ?>
            </div>

            <div class="sidebar-profile-info">
                <strong>
                    <?php echo htmlspecialchars($layoutName); ?>
                </strong>
                <span>
                    <?php echo htmlspecialchars($layoutRole); ?>
                </span>
            </div>

        </div>

        <!-- SIDEBAR MENU -->
        <nav class="sidebar-menu">
            <?php foreach ($layoutNavItems as $layoutItem): ?>
                <a
                    href="<?php echo htmlspecialchars($layoutItem["href"]); ?>"
                    class="sidebar-link<?php echo $layoutItem["key"] === $layoutActive ? " active" : ""; ?>"
                    title="<?php echo htmlspecialchars($layoutItem["label"]); ?>"
                    <?php echo $layoutItem["key"] === $layoutActive ? 'aria-current="page"' : ""; ?>
                >
                    <img
                        src="../icons/<?php echo htmlspecialchars($layoutItem["icon"]); ?>"
                        alt=""
                        class="sidebar-icon"
                    >
                    <span>
                        <?php echo htmlspecialchars($layoutItem["label"]); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>

    </aside>

    <!-- 
         MAIN WRAPPER - closed in adminFooter.php
    -->
    <div
        class="main-wrapper"
        id="mainWrapper"
    >

        <!-- 
             TOPBAR
         -->
        <header class="topbar">

            <div class="topbar-left">

                <!-- MENU -->
                <button
                    type="button"
                    class="menu-btn"
                    id="menuBtn"
                    title="Menu"
                    aria-label="Toggle menu"
                >
                    <span class="material-icons">
                        menu
                    </span>
                </button>

                <!-- M-LINK LOGO -->
                <a
                    href="../PUBLIC%20PAGES/homepage.php"
                    class="logo-link"
                    title="Go to M-LINK Homepage"
                >
                    <img
                        src="mlinkLogo.png"
                        alt="M-LINK Logo"
                        class="topbar-logo"
                    >
                </a>

            </div>

            <div class="topbar-right">

                <!-- NOTIFICATIONS - new sign-ups waiting for verification -->
                <a
                    href="verifyAccount.php"
                    class="topbar-icon-btn"
                    title="Notifications"
                >
                    <span class="topbar-icon-wrap">
                        <img
                            src="../icons/add_alert.svg"
                            alt="Notifications"
                            class="topbar-icon-img"
                        >
                        <?php if ($layoutSignups > 0): ?>
                            <span class="topbar-badge">
                                <?php echo $layoutSignups > 99 ? "99+" : $layoutSignups; ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span>
                        Notifications
                    </span>
                </a>

                <!-- ############## MESSAGES #######################-->
                <a
                    href="messages.php"
                    class="topbar-icon-btn"
                    title="Messages"
                >
                    <span class="topbar-icon-wrap">
                        <img
                            src="../icons/message.svg"
                            alt="Messages"
                            class="topbar-icon-img"
                        >
                        <?php if ($layoutUnread > 0): ?>
                            <span class="topbar-badge">
                                <?php echo $layoutUnread > 99 ? "99+" : $layoutUnread; ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span>
                        Messages
                    </span>
                </a>

                <!-- ######## ADMIN PROFILE #############-->
                <a
                    href="settings.php"
                    class="topbar-profile"
                    title="Profile"
                >
                    <div class="topbar-avatar">
                        <?php if ($layoutProfileUrl !== ""): ?>
                            <img
                                src="<?php echo htmlspecialchars($layoutProfileUrl, ENT_QUOTES, "UTF-8"); ?>"
                                alt="Administrator Profile"
                            >
                        <?php else: ?>
                            <span class="material-icons">
                                account_circle
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="topbar-profile-info">
                        <strong>
                            <?php echo htmlspecialchars($layoutName); ?>
                        </strong>
                        <span>
                            <?php echo htmlspecialchars($layoutRole); ?>
                        </span>
                    </div>
                </a>

            </div>

        </header>