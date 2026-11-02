<?php
require_once '../Error404Connection.php';
require_once 'detailsOfTheLoggedInUser.php';   // starts the session; sets $username, $fullName, $profileImg, $userData

// Check user login & role
if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "ward_councillor") {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$loggedInUsername = $_SESSION['username'];
$councillorWardId = null;

// Fetch Councillor's Ward
$sqlWard = "SELECT ward_id FROM ward_councillor WHERE username = ?";
$stmtWard = $conn->prepare($sqlWard);
if ($stmtWard) {
    $stmtWard->bind_param("s", $loggedInUsername);
    $stmtWard->execute();
    $resultWard = $stmtWard->get_result();
    if ($wardRow = $resultWard->fetch_assoc()) {
        $councillorWardId = (int)$wardRow['ward_id'];
    }
    $stmtWard->close();
}

$incident_id  = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : 0;
$toastMessage = "";
$toastType    = "success";

/*Fetch full ticket details (with joined service + status timestamps)*/
function fetchTicketDetails($conn, $incident_id) {
    if ($incident_id <= 0) {
        return null;
    }

    $sql = "SELECT
                it.incident_id,
                it.ward_id,
                it.service_id,
                s.service_type,
                it.location_description,
                it.incident_description,
                it.priority,
                it.status_id,
                it.date_reported,
                it.date_resolved,
                it.username,
                ts.pending,
                ts.InProgress,
                ts.resolved
            FROM incident_ticket it
            LEFT JOIN municipal_service s ON it.service_id = s.service_id
            LEFT JOIN ticket_status ts ON it.status_id = ts.status_id
            WHERE it.incident_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error (ticket fetch): " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $incident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticketRow = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    $stmt->close();

    return $ticketRow;
}

$ticket = fetchTicketDetails($conn, $incident_id);

/* HANDLE "CONFIRM CHANGES"*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'confirm_changes') {

    if ($ticket === null) {
        $toastMessage = "This ticket could not be found.";
        $toastType = "error";
    } elseif ((int)$ticket['ward_id'] !== $councillorWardId) {
        $toastMessage = "Permission Denied: You can only modify tickets within your designated ward.";
        $toastType = "error";
    } else {
        $status_id      = (int)$ticket['status_id'];
        $new_priority   = trim($_POST['priority_level'] ?? '');
        $target_stage   = trim($_POST['target_stage'] ?? '');
        $status_comment = trim($_POST['status_comment'] ?? '');
        $comment_text   = trim($_POST['comment_text'] ?? '');

        $allowed_priorities = ['Low', 'Normal', 'High', 'Urgent'];
        $allowed_stages     = ['pending', 'in_progress', 'resolved'];

        $changesSummary = [];
        $hasError = false;
        $now = date('Y-m-d H:i:s');

        /* ---- Priority ---- */
        if ($new_priority !== '' && in_array($new_priority, $allowed_priorities) && strcasecmp($new_priority, (string)$ticket['priority']) !== 0) {
            $stmtPri = $conn->prepare("UPDATE incident_ticket SET priority = ? WHERE incident_id = ?");
            if ($stmtPri) {
                $stmtPri->bind_param("si", $new_priority, $incident_id);
                if ($stmtPri->execute()) {
                    $changesSummary[] = "priority set to '{$new_priority}'";
                    $pNote = "[Priority Updated] Changed priority to {$new_priority}.";
                    $sqlC = $conn->prepare("INSERT INTO comment (incident_id, username, comment_text, comment_date) VALUES (?, ?, ?, NOW())");
                    if ($sqlC) {
                        $sqlC->bind_param("iss", $incident_id, $loggedInUsername, $pNote);
                        $sqlC->execute();
                        $sqlC->close();
                    }
                } else {
                    $hasError = true;
                }
                $stmtPri->close();
            } else {
                $hasError = true;
            }
        }

        /* ---- Processing stage ---- */
        $currentStage = 'pending';
        if (!empty($ticket['resolved'])) {
            $currentStage = 'resolved';
        } elseif (!empty($ticket['InProgress'])) {
            $currentStage = 'in_progress';
        }

        $updateSuccess = false;

        if ($target_stage !== '' && in_array($target_stage, $allowed_stages) && $target_stage !== $currentStage) {

            if ($status_id <= 0) {
                $hasError = true; // ticket has no ticket_status row
            } else {

                if ($target_stage === 'pending') {

                    $stmtUp = $conn->prepare("UPDATE ticket_status SET InProgress = NULL, resolved = NULL WHERE status_id = ?");
                    $stmtUp->bind_param("i", $status_id);
                    $updateSuccess = $stmtUp->execute();
                    $stmtUp->close();

                    $stmtInc = $conn->prepare("UPDATE incident_ticket SET date_resolved = NULL WHERE incident_id = ?");
                    $stmtInc->bind_param("i", $incident_id);
                    $stmtInc->execute();
                    $stmtInc->close();

                } elseif ($target_stage === 'in_progress') {

                    $stmtUp = $conn->prepare("UPDATE ticket_status SET InProgress = ?, resolved = NULL WHERE status_id = ?");
                    $stmtUp->bind_param("si", $now, $status_id);
                    $updateSuccess = $stmtUp->execute();
                    $stmtUp->close();

                    $stmtInc = $conn->prepare("UPDATE incident_ticket SET date_resolved = NULL WHERE incident_id = ?");
                    $stmtInc->bind_param("i", $incident_id);
                    $stmtInc->execute();
                    $stmtInc->close();

                } elseif ($target_stage === 'resolved') {

                    $stmtUp = $conn->prepare("UPDATE ticket_status SET InProgress = IFNULL(InProgress, ?), resolved = ? WHERE status_id = ?");
                    $stmtUp->bind_param("ssi", $now, $now, $status_id);
                    $updateSuccess = $stmtUp->execute();
                    $stmtUp->close();

                    $stmtInc = $conn->prepare("UPDATE incident_ticket SET date_resolved = ? WHERE incident_id = ?");
                    $stmtInc->bind_param("si", $now, $incident_id);
                    $stmtInc->execute();
                    $stmtInc->close();
                }

                if ($updateSuccess) {
                    $changesSummary[] = "stage set to '" . ucfirst(str_replace('_', ' ', $target_stage)) . "'";
                } else {
                    $hasError = true;
                }
            }
        }

        /* ---- Notify resident (only after a successful stage change) ---- */
        if ($updateSuccess && in_array($target_stage, ['in_progress', 'resolved'], true)) {

            $residentUsername = $ticket['username'];

            if ($target_stage === 'in_progress') {
                $notificationTitle   = "Ticket #" . $incident_id . " is In Progress";
                $notificationMessage = "Your reported fault is now being handled.";
            } else {
                $notificationTitle   = "Ticket #" . $incident_id . " has been Resolved";
                $notificationMessage = "Your reported fault has been marked as Resolved.";
            }

            $sqlNotification = $conn->prepare("
                INSERT INTO notification
                    (username, incident_id, notification_title, notification_message, notification_type, is_read)
                VALUES (?, ?, ?, ?, 'ticket_update', 0)
            ");

            if ($sqlNotification) {
                $sqlNotification->bind_param("siss", $residentUsername, $incident_id, $notificationTitle, $notificationMessage);
                if (!$sqlNotification->execute()) {
                    $hasError = true;
                }
                $sqlNotification->close();
            } else {
                $hasError = true;
            }
        }

        /* ---- Status comment ---- */
        if (!empty($status_comment)) {
            $statusNote = "[Status Update - " . ucfirst(str_replace('_', ' ', $target_stage)) . "] " . $status_comment;

            $sqlComment = $conn->prepare("INSERT INTO comment (incident_id, username, comment_text, comment_date) VALUES (?, ?, ?, NOW())");
            if ($sqlComment) {
                $sqlComment->bind_param("iss", $incident_id, $loggedInUsername, $statusNote);
                $sqlComment->execute();
                $sqlComment->close();
            }
        }

        /* ---- New comment ---- */
        if (!empty($comment_text)) {
            $stmtComment = $conn->prepare("INSERT INTO comment (incident_id, username, comment_text, comment_date) VALUES (?, ?, ?, NOW())");
            if ($stmtComment) {
                $stmtComment->bind_param("iss", $incident_id, $loggedInUsername, $comment_text);
                if ($stmtComment->execute()) {
                    $changesSummary[] = "comment added";
                } else {
                    $hasError = true;
                }
                $stmtComment->close();
            }
        }

        /* ---- Toast ---- */
        if ($hasError) {
            $toastMessage = "Some changes could not be saved. Please try again.";
            $toastType = "error";
        } elseif (!empty($changesSummary)) {
            $toastMessage = "Changes saved: " . implode(', ', $changesSummary) . ".";
            $toastType = "success";
        } else {
            $toastMessage = "No changes to save.";
            $toastType = "error";
        }

        $ticket = fetchTicketDetails($conn, $incident_id);
    }
}

/* COMMENTS*/
$comments = [];
if ($ticket !== null) {
    $stmtComments = $conn->prepare("
        SELECT comment_id, incident_id, username, comment_text, comment_date
        FROM comment
        WHERE incident_id = ?
        ORDER BY comment_date DESC
    ");
    if ($stmtComments) {
        $stmtComments->bind_param("i", $incident_id);
        $stmtComments->execute();
        $resultComments = $stmtComments->get_result();
        while ($commentRow = $resultComments->fetch_assoc()) {
            $comments[] = $commentRow;
        }
        $stmtComments->close();
    }
}

$isMyWard = ($ticket !== null && (int)$ticket['ward_id'] === $councillorWardId);

/* UNREAD NOTIFICATIONS */
$unreadNotificationCount = 0;

$notification_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'new_ticket'
    AND is_read = 0
");

if ($notification_stmt) {
    $notification_stmt->bind_param("s", $username);
    $notification_stmt->execute();
    $nRow = $notification_stmt->get_result()->fetch_assoc();
    $unreadNotificationCount = (int)($nRow['unread_count'] ?? 0);
    $notification_stmt->close();
}

/* UNREAD MESSAGES side*/
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
    $uRow = $unread_stmt->get_result()->fetch_assoc();
    $unread_count = (int)($uRow['unread_count'] ?? 0);
    $unread_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details #<?php echo $incident_id; ?></title>


    <script>
    try { if (localStorage.getItem("sidebarState") === "closed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}
    </script>

    <link rel="stylesheet" href="Wardcouncillo.css?v=<?php echo filemtime(__DIR__ . '/Wardcouncillo.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* Keeps the original look of the ticket content (body itself is styled by Wardcouncillo.css) */
        .main-content { font-family: Arial, sans-serif; color: #333; }

        .header-nav { max-width: 1000px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; }
        .back-button { color: #1b442b; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .back-button:hover { text-decoration: underline; }

        .ticket-card { background-color: white; padding: 30px; margin: 0 auto 20px auto; border-radius: 12px; max-width: 1000px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; }
        .ticket-card h3 { color: #1b442b; margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }

        /* TWO-COLUMN LAYOUT FOR SIDE-BY-SIDE SECTIONS */
        .two-column-layout {
            display: flex;
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
            align-items: flex-start;
        }

        .status-card, .comments-card {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            flex: 1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            box-sizing: border-box;
        }

        .status-card h3, .comments-card h3 {
            color: #1b442b;
            margin-top: 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        /* PROGRESS BAR STYLES */
        .progress-container { margin: 25px 0 10px 0; }
        .progressbar { display: flex; justify-content: space-between; list-style-type: none; padding: 0; margin: 0; position: relative; }
        .progressbar::before { content: ''; position: absolute; top: 18px; left: 0; width: 100%; height: 4px; background-color: #e0e0e0; z-index: 1; }
        .progressbar-fill { position: absolute; top: 18px; left: 0; height: 4px; background-color: #239e56; z-index: 1; transition: width 0.4s ease; }
        .progressbar li { position: relative; z-index: 2; text-align: center; width: 33.33%; }
        .progressbar li .icon-box { width: 40px; height: 40px; line-height: 40px; border-radius: 50%; background-color: #e0e0e0; color: #777; margin: 0 auto 8px auto; font-weight: bold; transition: all 0.3s; }
        .progressbar li.active .icon-box { background-color: #239e56; color: white; }
        .progressbar li.current .icon-box { box-shadow: 0 0 0 5px rgba(35, 158, 86, 0.25); }
        .progressbar li .stage-title { font-weight: 600; font-size: 14px; color: #555; }
        .progressbar li .stage-date { font-size: 11px; color: #888; display: block; margin-top: 2px; }

        /* DETAILS GRID */
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .detail-item { background: #f8fafc; padding: 12px 15px; border-radius: 6px; border-left: 4px solid #1b442b; }
        .detail-item strong { display: block; font-size: 12px; color: #64748b; text-transform: uppercase; }
        .detail-item span { font-size: 15px; font-weight: 600; color: #1e293b; }

        /* STATUS CONTROLS */
        .status-btn-group { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
        .btn-status { padding: 10px 18px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; background-color: #f8fafc; color: #334155; }
        .btn-status:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-status:hover:not(:disabled) { background-color: #e2e8f0; }
        .btn-status.stage-selected { box-shadow: 0 0 0 3px rgba(27, 68, 43, 0.45); background-color: #e2e8f0; }

        /* PRIORITY CONTROLS */
        .control-group-title { font-size: 13px; font-weight: 700; color: #1b442b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .priority-btn-group { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .btn-priority { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #f8fafc; color: #334155; cursor: pointer; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; }
        .btn-priority:hover { background-color: #e2e8f0; }
        .btn-priority.active-low { background-color: #f1f5f9; color: #475569; border-color: #94a3b8; }
        .btn-priority.active-normal { background-color: #fef2f2; color: #dc2626; border-color: #fca5a5; font-weight: 800; }
        .btn-priority.active-high { background-color: #fef2f2; color: #dc2626; border-color: #dc2626; font-weight: 800; }
        .btn-priority.active-urgent { background-color: #dc2626; color: #ffffff; border-color: #991b1b; font-weight: 800; }

        /* COMMENT STYLES */
        .comment { border-bottom: 1px solid #e2e8f0; padding: 12px 0; }
        .comment-user { font-weight: bold; color: #1b442b; font-size: 14px; }
        .comment-date { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        .comment-text { margin-top: 6px; line-height: 1.4; color: #334155; font-size: 14px; }
        .comment-form textarea { width: 100%; min-height: 80px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical; box-sizing: border-box; }

        .btn-toggle-comments { background: none; border: none; color: #1b442b; font-weight: 700; cursor: pointer; padding: 10px 0 0 0; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
        .btn-toggle-comments:hover { text-decoration: underline; }

        .ward-warning { background-color: #fffbebf8; border-left: 4px solid #f59e0b; padding: 12px 15px; border-radius: 6px; color: #b45309; margin-bottom: 15px; font-size: 14px; }

        /* TOAST POPUP (sits below the top bar) */
        .toast { position: fixed; top: 90px; right: 30px; background-color: #10b981; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 12px; z-index: 1000; animation: slideIn 0.3s forwards; }
        .toast.error { background-color: #ef4444; }

        @keyframes slideIn { from { transform: translateY(-100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* CONFIRM CHANGES BAR */
        .confirm-bar { max-width: 1000px; margin: 20px auto; text-align: right; }
        .confirm-btn { background-color: #1b442b; color: white; border: none; padding: 14px 30px; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: 0.2s; }
        .confirm-btn:hover { background-color: #153621; transform: translateY(-1px); }

        @media (max-width: 768px) {
            .two-column-layout { flex-direction: column; }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- TOP BAR -->
    <header class="topbar">

        <!-- HAMBURGER -->
        <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="true">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- M-LINK LOGO -->
        <a href="logout.php" class="logo-link" title="M-LINK Homepage">
            <img src="makanaLogo.svg" alt="M-Link logo" class="topbar-logo">
        </a>

        <div class="top-right">

            <!-- NOTIFICATIONS -->
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

            <!-- MESSAGES -->
            <a href="messages.php" class="topbar-icon-btn" title="Messages">
                <img src="../icons/message.svg" alt="" class="topbar-icon-img">
                <span class="topbar-icon-label">Messages</span>
            </a>

            <!-- USER -->
            <div class="user">
                <img src="<?php echo $profileImg; ?>" alt="Profile Picture" id="profileImage">
                <div class="user-details">
                    <h4><?php echo $fullName; ?></h4>
                    <small>Ward Councillor</small>
                </div>
            </div>

        </div>
    </header>

    <!-- SIDEBAR  -->
    <?php $activePage = 'tickets'; include 'ward_sidebar.php'; ?>

    <!--  MAIN CONTENT  -->
    <main class="main-content" id="mainContent">

        <div class="header-nav">
            <a class="back-button" href="WardTicketManagement.php">
                <i class="fa-solid fa-arrow-left"></i> Back to Ticket Management
            </a>
        </div>

        <?php if ($ticket !== null): ?>

            <?php
                $hasInProgress = !empty($ticket['InProgress']);
                $hasResolved   = !empty($ticket['resolved']);

                $progressWidth = "0%";
                $currentStage  = 'pending';

                if ($hasResolved) {
                    $progressWidth = "100%";
                    $currentStage  = 'resolved';
                } elseif ($hasInProgress) {
                    $progressWidth = "50%";
                    $currentStage  = 'in_progress';
                }
            ?>

            <!-- TICKET SUMMARY CARD -->
            <div class="ticket-card">
                <h3>Ticket #<?php echo htmlspecialchars($ticket['incident_id']); ?></h3>

                <!-- PROGRESS BAR -->
                <div class="progress-container">
                    <ul class="progressbar">
                        <div class="progressbar-fill" style="width: <?php echo $progressWidth; ?>;"></div>

                        <li class="active <?php echo (!$hasInProgress && !$hasResolved) ? 'current' : ''; ?>">
                            <div class="icon-box"><i class="fa-solid fa-clock"></i></div>
                            <span class="stage-title">Pending</span>
                            <span class="stage-date"><?php echo $ticket['pending'] ? date('d M Y H:i', strtotime($ticket['pending'])) : ''; ?></span>
                        </li>

                        <li class="<?php echo $hasInProgress ? 'active' : ''; ?> <?php echo ($hasInProgress && !$hasResolved) ? 'current' : ''; ?>">
                            <div class="icon-box"><i class="fa-solid fa-gears"></i></div>
                            <span class="stage-title">In Progress</span>
                            <span class="stage-date"><?php echo $ticket['InProgress'] ? date('d M Y H:i', strtotime($ticket['InProgress'])) : ''; ?></span>
                        </li>

                        <li class="<?php echo $hasResolved ? 'active current' : ''; ?>">
                            <div class="icon-box"><i class="fa-solid fa-check"></i></div>
                            <span class="stage-title">Resolved</span>
                            <span class="stage-date"><?php echo $ticket['resolved'] ? date('d M Y H:i', strtotime($ticket['resolved'])) : ''; ?></span>
                        </li>
                    </ul>
                </div>

                <!-- DETAILS GRID -->
                <div class="details-grid">
                    <div class="detail-item"><strong>Logged By</strong><span><?php echo htmlspecialchars($ticket['username']); ?></span></div>
                    <div class="detail-item"><strong>Service Category</strong><span><?php echo htmlspecialchars($ticket['service_type'] ?? 'ID: ' . $ticket['service_id']); ?></span></div>
                    <div class="detail-item"><strong>Ward</strong><span>Ward <?php echo htmlspecialchars($ticket['ward_id']); ?></span></div>
                    <div class="detail-item"><strong>Priority</strong><span><?php echo htmlspecialchars($ticket['priority']); ?></span></div>
                    <div class="detail-item"><strong>Location</strong><span><?php echo htmlspecialchars($ticket['location_description']); ?></span></div>
                    <div class="detail-item"><strong>Date Reported</strong><span><?php echo htmlspecialchars($ticket['date_reported']); ?></span></div>
                </div>

                <div style="margin-top: 20px;">
                    <strong>Description:</strong>
                    <p style="background: #f8fafc; padding: 15px; border-radius: 6px; margin-top: 5px; border: 1px solid #e2e8f0;"><?php echo nl2br(htmlspecialchars($ticket['incident_description'])); ?></p>
                </div>
            </div>

            <?php if ($isMyWard): ?>
            <form method="POST" action="" id="ticketForm">
                <input type="hidden" name="action_type" value="confirm_changes">
                <input type="hidden" name="priority_level" id="priorityInput" value="<?php echo htmlspecialchars($ticket['priority'] ?? 'Normal'); ?>">
                <input type="hidden" name="target_stage" id="stageInput" value="<?php echo htmlspecialchars($currentStage); ?>">
            <?php endif; ?>

            <!-- SIDE-BY-SIDE CONTAINER FOR STATUS & COMMENTS -->
            <div class="two-column-layout">

                <!-- UPDATE STAGE CARD -->
                <div class="status-card">
                    <h3>Update Ticket Stage</h3>

                    <?php if (!$isMyWard): ?>
                        <div class="ward-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <strong>Read-Only Access:</strong> Ticket belongs to <strong>Ward <?php echo htmlspecialchars($ticket['ward_id']); ?></strong>. You can only update tickets in Ward <?php echo htmlspecialchars((string)$councillorWardId); ?>.
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 20px;">
                            <div class="control-group-title"><i class="fa-solid fa-flag"></i> Ticket Priority</div>

                            <div class="priority-btn-group">
                                <?php $currP = strtolower($ticket['priority'] ?? ''); ?>
                                <button type="button" onclick="selectPriority(this, 'Low')" class="btn-priority <?php echo $currP === 'low' ? 'active-low' : ''; ?>">Low</button>
                                <button type="button" onclick="selectPriority(this, 'Normal')" class="btn-priority <?php echo $currP === 'normal' ? 'active-normal' : ''; ?>">Normal</button>
                                <button type="button" onclick="selectPriority(this, 'High')" class="btn-priority <?php echo $currP === 'high' ? 'active-high' : ''; ?>"><i class="fa-solid fa-triangle-exclamation"></i> High</button>
                                <button type="button" onclick="selectPriority(this, 'Urgent')" class="btn-priority <?php echo $currP === 'urgent' ? 'active-urgent' : ''; ?>"><i class="fa-solid fa-bolt"></i> Urgent</button>
                            </div>
                        </div>

                        <hr style="margin: 15px 0; border: 0; border-top: 1px solid #e2e8f0;">

                        <div>
                            <div class="control-group-title"><i class="fa-solid fa-list-check"></i> Processing Stage</div>

                            <div class="status-btn-group">
                                <button type="button" onclick="selectStage(this, 'pending')" class="btn-status <?php echo $currentStage === 'pending' ? 'stage-selected' : ''; ?>">
                                    <i class="fa-solid fa-rotate-left"></i> Reset to Pending
                                </button>
                                <button type="button" onclick="selectStage(this, 'in_progress')" class="btn-status <?php echo $currentStage === 'in_progress' ? 'stage-selected' : ''; ?>">
                                    <i class="fa-solid fa-spinner"></i> In Progress
                                </button>
                                <button type="button" onclick="selectStage(this, 'resolved')" class="btn-status <?php echo $currentStage === 'resolved' ? 'stage-selected' : ''; ?>">
                                    <i class="fa-solid fa-circle-check"></i> Resolved
                                </button>
                            </div>

                            <textarea name="status_comment" rows="3" placeholder="Optional comment log with status update..." style="width: 100%; margin-top: 15px; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; box-sizing: border-box;"></textarea>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- COMMENTS CARD -->
                <div class="comments-card">
                    <h3>Comments &amp; Audit Trail</h3>

                    <div id="commentsContainer">
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $index => $comment): ?>
                                <div class="comment comment-item" style="<?php echo $index >= 3 ? 'display: none;' : ''; ?>">
                                    <div class="comment-user"><i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($comment['username']); ?></div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></div>
                                    <div class="comment-date"><?php echo htmlspecialchars($comment['comment_date']); ?></div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (count($comments) > 3): ?>
                                <button type="button" class="btn-toggle-comments" id="toggleCommentsBtn" onclick="toggleComments()">
                                    <i class="fa-solid fa-chevron-down"></i> Show All Comments (<?php echo count($comments); ?>)
                                </button>
                            <?php endif; ?>

                        <?php else: ?>
                            <p style="color: #64748b; font-size: 14px;">No comments or update notes added yet.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($isMyWard): ?>
                        <div class="comment-form" style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                            <textarea name="comment_text" placeholder="Write a comment..."></textarea>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- CONFIRM CHANGES BUTTON -->
            <?php if ($isMyWard): ?>
                <div class="confirm-bar">
                    <button type="submit" class="confirm-btn">
                        <i class="fa-solid fa-check-double"></i> Confirm Changes
                    </button>
                </div>
            </form>
            <?php endif; ?>

        <?php else: ?>
            <div class="ticket-card" style="text-align: center;">
                <h3>No Ticket Found</h3>
                <p>The requested ticket could not be located.</p>
                <a class="back-button" href="WardTicketManagement.php">Back to Ticket Management</a>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Sidebar open/close button (shared by all ward councillor pages) -->
<script src="ward_sidebar.js"></script>

<script>
    function selectPriority(btn, value) {
        var input = document.getElementById('priorityInput');
        if (input) { input.value = value; }

        document.querySelectorAll('.btn-priority').forEach(function (b) {
            b.classList.remove('active-low', 'active-normal', 'active-high', 'active-urgent');
        });

        var classMap = { Low: 'active-low', Normal: 'active-normal', High: 'active-high', Urgent: 'active-urgent' };
        btn.classList.add(classMap[value]);
    }

    function selectStage(btn, value) {
        var input = document.getElementById('stageInput');
        if (input) { input.value = value; }

        document.querySelectorAll('.btn-status').forEach(function (b) {
            b.classList.remove('stage-selected');
        });
        btn.classList.add('stage-selected');
    }

    function toggleComments() {
        var comments = document.querySelectorAll('.comment-item');
        var btn = document.getElementById('toggleCommentsBtn');
        var isExpanding = btn.getAttribute('data-expanded') !== 'true';

        comments.forEach(function (item, idx) {
            if (idx >= 3) {
                item.style.display = isExpanding ? 'block' : 'none';
            }
        });

        if (isExpanding) {
            btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Show Less';
            btn.setAttribute('data-expanded', 'true');
        } else {
            btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> Show All Comments (' + comments.length + ')';
            btn.setAttribute('data-expanded', 'false');
        }
    }
</script>

<!-- TOAST NOTIFICATION -->
<?php if (!empty($toastMessage)): ?>
    <div id="toast" class="toast <?php echo $toastType; ?>">
        <i class="fa-solid <?php echo $toastType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo htmlspecialchars($toastMessage); ?></span>
    </div>
    <script>
        setTimeout(function () {
            var toast = document.getElementById('toast');
            if (toast) toast.style.display = 'none';
        }, 4000);
    </script>
<?php endif; ?>

</body>
</html>
<?php
$conn->close();
?>