<?php
session_start();

require_once "../Error404Connection.php";

if (
    !isset($_SESSION["username"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "ward_councillor" 
) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$username = $_SESSION["username"];
$ward_id = (int)($_SESSION["ward_id"] ?? 0);


$display_name = $username;
$profileImg = "profile.jpg";

$sql = "SELECT u.first_Name, u.surname, pi.image_path, wc.ward_id
        FROM ward_councillor wc
        INNER JOIN user u ON wc.username = u.username
        LEFT JOIN profile_images pi ON u.profile_id = pi.profile_id
        WHERE wc.username = ?
        LIMIT 1";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $name = trim(($row["first_Name"] ?? "") . " " . ($row["surname"] ?? ""));
        if ($name !== "") {
            $display_name = $name;
        }
        if (!empty($row["image_path"])) {
            $profileImg = $row["image_path"];
        }
        if (!empty($row["ward_id"])) {
            $ward_id = (int)$row["ward_id"];
        }
    }
    mysqli_stmt_close($stmt);
}


$sqlSummary = "
    SELECT
        COUNT(*) AS total,
        SUM(status_id = 1) AS pending,
        SUM(status_id = 2) AS in_progress,
        SUM(status_id = 3) AS resolved
    FROM incident_ticket
    WHERE ward_id = ?
";
$stmt = mysqli_prepare($conn, $sqlSummary);
mysqli_stmt_bind_param($stmt, "i", $ward_id);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$total = (int)($summary["total"] ?? 0);
$pending = (int)($summary["pending"] ?? 0);
$progress = (int)($summary["in_progress"] ?? 0);
$resolved = (int)($summary["resolved"] ?? 0);

/* ================= INCIDENTS BY WARD ================= */
$wardLabels = [];
$wardData = [];

$sqlWard = "
    SELECT w.ward_name, COUNT(i.incident_id) AS total
    FROM ward w
    LEFT JOIN incident_ticket i ON w.ward_id = i.ward_id
    GROUP BY w.ward_id, w.ward_name
    ORDER BY w.ward_id
";
$resultWard = mysqli_query($conn, $sqlWard);
if (!$resultWard) {
    die("Ward Query Error: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($resultWard)) {
    $wardLabels[] = $row["ward_name"];
    $wardData[] = (int)$row["total"];
}

/* ================= INCIDENTS BY SERVICE (ASSIGNED WARD) ================= */
$serviceLabels = [];
$serviceData = [];

$sqlService = "
    SELECT ms.service_type, COUNT(i.incident_id) AS total
    FROM municipal_service ms
    LEFT JOIN incident_ticket i ON ms.service_id = i.service_id AND i.ward_id = ?
    GROUP BY ms.service_id, ms.service_type
    ORDER BY total DESC
";
$stmt = mysqli_prepare($conn, $sqlService);
mysqli_stmt_bind_param($stmt, "i", $ward_id);
mysqli_stmt_execute($stmt);
$resultService = mysqli_stmt_get_result($stmt);
if (!$resultService) {
    die("Service Query Error: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($resultService)) {
    $serviceLabels[] = $row["service_type"];
    $serviceData[] = (int)$row["total"];
}
mysqli_stmt_close($stmt);

/* ================= STATUS & PRIORITY (ASSIGNED WARD) ================= */
$statusLabels = ["Pending", "In Progress", "Resolved"];
$statusData = [$pending, $progress, $resolved];

$priorityLabels = [];
$priorityData = [];

$sqlPriority = "
    SELECT priority, COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    GROUP BY priority
";
$stmt = mysqli_prepare($conn, $sqlPriority);
mysqli_stmt_bind_param($stmt, "i", $ward_id);
mysqli_stmt_execute($stmt);
$resultPriority = mysqli_stmt_get_result($stmt);
if (!$resultPriority) {
    die("Priority Query Error: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($resultPriority)) {
    $priorityLabels[] = $row["priority"] ?? 'Unassigned';
    $priorityData[] = (int)$row["total"];
}
mysqli_stmt_close($stmt);

/* ================= INCIDENTS OVER TIME (ASSIGNED WARD) ================= */
$dateLabels = [];
$dateData = [];

$sqlDate = "
    SELECT DATE(date_reported) AS report_date, COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    GROUP BY DATE(date_reported)
    ORDER BY report_date ASC
";
$stmt = mysqli_prepare($conn, $sqlDate);
mysqli_stmt_bind_param($stmt, "i", $ward_id);
mysqli_stmt_execute($stmt);
$resultDate = mysqli_stmt_get_result($stmt);
if (!$resultDate) {
    die("Date Query Error: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($resultDate)) {
    $dateLabels[] = $row["report_date"];
    $dateData[] = (int)$row["total"];
}
mysqli_stmt_close($stmt);

/* ================= SHARED SIDEBAR / TOP BAR VALUES ================= */

$userData['image_path'] = $profileImg;
$fullName               = $display_name;
$activePage             = 'analytics';

// Unread messages (sidebar badge)
$unread_count = 0;
$unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM internal_messages
    WHERE receiver_username = ?
    AND is_read = 0
");
if ($unread_stmt) {
    $unread_stmt->bind_param("s", $username);
    $unread_stmt->execute();
    $unread_data  = $unread_stmt->get_result()->fetch_assoc();
    $unread_count = (int) ($unread_data['unread_count'] ?? 0);
    $unread_stmt->close();
}

// Unread notifications (top bar bell)
$unreadNotificationCount = 0;
$notif_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'new_ticket'
    AND is_read = 0
");
if ($notif_stmt) {
    $notif_stmt->bind_param("s", $username);
    $notif_stmt->execute();
    $notif_data = $notif_stmt->get_result()->fetch_assoc();
    $unreadNotificationCount = (int) ($notif_data['unread_count'] ?? 0);
    $notif_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Link Analytics</title>

   <script>
try { if (localStorage.getItem("sidebarState") === "closed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}
</script>

<link rel="stylesheet" href="Wardcouncillo.css?v=<?php echo filemtime(__DIR__ . '/Wardcouncillo.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="dashboard.css?v=<?php echo filemtime(__DIR__ . '/dashboard.css'); ?>">>
</head>

<body>

<div class="container">

    <header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>

    <a href="logout.php" class="logo-link" title="M-LINK Homepage">
        <img src="makanaLogo.svg" alt="M-Link logo" class="topbar-logo">
    </a>

    <div class="top-right">
        <a href="notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="notification-icon-wrapper">
                <img src="../icons/add_alert.svg" alt="" class="topbar-icon-img">
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="notification-badge">
                        <?php echo $unreadNotificationCount > 99 ? "99+" : $unreadNotificationCount; ?>
                    </span>
                <?php endif; ?>
            </span>
            <span class="topbar-icon-label">Notifications</span>
        </a>

        <a href="messages.php" class="topbar-icon-btn" title="Messages">
            <img src="../icons/message.svg" alt="" class="topbar-icon-img">
            <span class="topbar-icon-label">Messages</span>
        </a>

        <div class="user">
            <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Picture" id="profileImage">
            <div class="user-details">
                <h4><?php echo htmlspecialchars($display_name); ?></h4>
                <small>Ward Councillor</small>
            </div>
        </div>
    </div>
</header>

<?php include 'ward_sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main-content" id="mainContent">

        <div class="dashboard">

            <!-- PAGE HEADER -->
            <div class="header">
                <h1>M-Link Analytics</h1>
                <p>Municipal Incident Reporting & Analytics for <strong>Ward <?php echo $ward_id; ?></strong></p>
            </div>

            <!-- SUMMARY CARDS -->
            <div class="cards">

                <div class="card card-total">
                    <h3>Total Incidents</h3>
                    <div class="number"><?php echo $total; ?></div>
                </div>

                <div class="card card-pending">
                    <h3>Pending</h3>
                    <div class="number"><?php echo $pending; ?></div>
                </div>

                <div class="card card-progress">
                    <h3>In Progress</h3>
                    <div class="number"><?php echo $progress; ?></div>
                </div>

                <div class="card card-resolved">
                    <h3>Resolved</h3>
                    <div class="number"><?php echo $resolved; ?></div>
                </div>

            </div>

            <!-- CHARTS -->
            <div class="charts">

                <!-- WARD -->
                <div class="chart-container">
                    <h2>Incidents by Ward</h2>
                    <div class="chart-box">
                        <canvas id="wardChart"></canvas>
                    </div>
                </div>

                <!-- SERVICE -->
                <div class="chart-container">
                    <h2>Faults by Service</h2>
                    <div class="chart-box">
                        <canvas id="serviceChart"></canvas>
                    </div>
                </div>

                <!-- STATUS -->
                <div class="chart-container">
                    <h2>Incident Status</h2>
                    <div class="chart-box">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- PRIORITY -->
                <div class="chart-container">
                    <h2>Priority Distribution</h2>
                    <div class="chart-box">
                        <canvas id="priorityChart"></canvas>
                    </div>
                </div>

                <!-- TIME -->
                <div class="chart-container full-width">
                    <h2>Incidents Reported Over Time</h2>
                    <div class="chart-box">
                        <canvas id="dateChart"></canvas>
                    </div>
                </div>

            </div>

        </div>

    </main>

</div>

<!-- ================= CHART DATA ================= -->
<script>
    const currentWardId = <?php echo json_encode($ward_id); ?>;
    const wardLabels = <?php echo json_encode($wardLabels); ?>;
    const wardData = <?php echo json_encode($wardData); ?>;

    const serviceLabels = <?php echo json_encode($serviceLabels); ?>;
    const serviceData = <?php echo json_encode($serviceData); ?>;

    const statusLabels = <?php echo json_encode($statusLabels); ?>;
    const statusData = <?php echo json_encode($statusData); ?>;

    const priorityLabels = <?php echo json_encode($priorityLabels); ?>;
    const priorityData = <?php echo json_encode($priorityData); ?>;

    const dateLabels = <?php echo json_encode($dateLabels); ?>;
    const dateData = <?php echo json_encode($dateData); ?>;
</script>

<!-- CHART JAVASCRIPT -->
<script src="dashboard.js"></script>

 <!-- FOOTER -->
        <footer>
            <p>
                © 2026 M-LINK | Empowering Communities
            </p>
        </footer>
</body>

</html>
