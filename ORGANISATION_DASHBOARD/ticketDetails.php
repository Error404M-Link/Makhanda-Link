<?php
session_start();
require_once "Error404Connection.php";

$loggedInUsername = $_SESSION['username'] ?? '';

if (empty($loggedInUsername)) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$ticketId = isset($_GET['ticketId']) ? intval($_GET['ticketId']) : 0;
$ticket = null;
$comments = [];

if ($ticketId > 0) {
    // 1. Fetch Ticket details along with Organisation's full name & status dates
    $sqlTicket = "
        SELECT 
            i.incident_id,
            i.incident_description,
            i.location_description,
            i.priority,
            i.date_reported,
            i.date_resolved,
            i.created_at,
            o.organisation_name,
            ms.service_type,
            ts.pending,
            ts.InProgress,
            ts.resolved
        FROM incident_ticket i
        INNER JOIN organisation o ON i.username = o.username
        LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
        LEFT JOIN ticket_status ts ON i.status_id = ts.status_id
        WHERE i.incident_id = ? AND i.username = ?
    ";

    if ($stmt = $conn->prepare($sqlTicket)) {
        $stmt->bind_param("is", $ticketId, $loggedInUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // 2. Fetch Comments along with Councillor/Official's full name
    if ($ticket) {
        $sqlComments = "
            SELECT 
                c.comment_id,
                c.comment_text,
                c.comment_date,
                u.first_Name,
                u.surname,
                u.User_role,
            FROM comment c
            LEFT JOIN user u ON c.username = u.username
            LEFT JOIN organisation o on u. username = o. username
            WHERE c.incident_id = ?
            ORDER BY c.comment_date ASC
        ";

        if ($stmtc = $conn->prepare($sqlComments)) {
            $stmtc->bind_param("i", $ticketId);
            $stmtc->execute();
            $resComments = $stmtc->get_result();
            while ($row = $resComments->fetch_assoc()) {
                $comments[] = $row;
            }
            $stmtc->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Progress & Details</title>
    <link rel="stylesheet" href="ticketDetails.css">
    <link rel="stylesheet" href="backButton.css">
    <style>

 * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


:root {
    --sidebar-width: 270px;
    --sidebar-collapsed-width: 85px;

    --green: #4b561b;
    --cream: #f4f0e7;
    --teal: #244f4b;
    --brown: #955b24;
    --text: #263238;
    --white: #ffffff;
    --border: #e4e0d7;
}


body {
    font-family: "Ubuntu", sans-serif;
    background: var(--cream);
    color: var(--text);
    min-height: 100vh;
    margin: 0;
    padding: 30px 15px;
}


a {
    text-decoration: none;
}


button,
input,
textarea,
select {
    font-family: "Ubuntu", sans-serif;
}


/* =========================================
   DASHBOARD CONTAINER
========================================= */

.dashboard-container {
    width: 100%;
    min-height: 100vh;
}

/* =========================================
   TOPBAR PROFILE
========================================= */

.topbar-profile {
    display: flex;
    align-items: center;

    gap: 10px;

    padding-left: 20px;

    border-left: 1px solid var(--border);

    color: var(--text);
}


.topbar-avatar {
    width: 42px;
    height: 42px;

    border-radius: 50%;

    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #ece8df;
}


.topbar-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.topbar-avatar .material-icons {
    font-size: 40px;
    color: var(--green);
}


.topbar-profile-info {
    display: flex;
    flex-direction: column;
}


.topbar-profile-info strong {
    font-size: 13px;
    font-weight: 600;

    color: var(--text);
}


.topbar-profile-info span {
    margin-top: 2px;

    font-size: 10px;

    color: #777;
}

.container {
    max-width: 850px;
    margin: 0 auto;
}

.back-button {
    color: #ffffff;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 20px;
}

.ticket-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.card-header h2 {
    margin: 0;
    color: #1e3d2f;
}

.read-only-badge {
    background: #e2e8f0;
    color: #64748b;
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.priority-badge {
    background: #0284c7;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
}

/* Status Tracker Animation */
.status-tracker {
    margin: 35px 0;
}

.tracker-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 20px;
}

.step {
    text-align: center;
    opacity: 0.35;
    transition: all 0.5s ease;
}

.step.active {
    opacity: 1;
}

.step-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #cbd5e1;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-weight: bold;
    transition: background 0.4s ease, transform 0.4s ease;
}

.step.active .step-icon {
    background: #16a34a;
    box-shadow: 0 0 12px rgba(22, 163, 74, 0.5);
    animation: popIn 0.5s ease forwards;
}

.step-title {
    margin: 0;
    font-weight: 600;
    font-size: 0.95rem;
}

.step-line {
    flex-grow: 1;
    height: 4px;
    background: #e2e8f0;
    margin: 0 10px;
    position: relative;
    top: -14px;
    overflow: hidden;
}

.step-line.active-line::after {
    content: '';
    display: block;
    height: 100%;
    background: #16a34a;
    animation: fillLine 0.8s ease-in-out forwards;
}

@keyframes fillLine {
    from { width: 0%; }
    to { width: 100%; }
}

@keyframes popIn {
    0% { transform: scale(0.8); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

/* Timeline Comments */
.comments-section {
    margin-top: 40px;
}

.timeline {
    position: relative;
    padding-left: 24px;
    border-left: 3px solid #16a34a;
    margin-top: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
    animation-delay: var(--delay);
}

.timeline-dot {
    position: absolute;
    left: -31px;
    top: 6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #16a34a;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #16a34a;
}

.timeline-content {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 15px;
    border-radius: 8px;
}

.author-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.role-badge {
    background: #dbeafe;
    color: #1e40af;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.comment-body {
    margin: 0 0 8px 0;
    color: #334155;
    line-height: 1.5;
}

.comment-time {
    font-size: 0.8rem;
    color: #94a3b8;
}

.no-comments {
    color: #64748b;
    font-style: italic;
}

.message {
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hidden comments state */
.timeline-item.hidden-comment {
    display: none;
}

/* Toggle Comments Button Styles */
.toggle-comments-wrapper {
    text-align: center;
    margin-top: 20px;
}

.btn-toggle-comments {
    background-color: #f1f5f9;
    color: #1e3d2f;
    border: 1px solid #cbd5e1;
    padding: 8px 18px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-toggle-comments:hover {
    background-color: #1e3d2f;
    color: #ffffff;
    border-color: #1e3d2f;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

</style>   
</head>
<body>
      <div class="header-nav">
                <a href="ticketHistory.php" class="backBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back To Reported Tickets </span>
                </a>
            </div>   

    <div class="container">

        <?php if ($ticket): ?>
            <div class="ticket-card">
                <header class="card-header">
                    <h2>Ticket #<?php echo htmlspecialchars($ticket['incident_id']); ?></h2>
                    <span class="read-only-badge">Read-Only View</span>
                </header>

                <div class="info-grid">
                    <p><strong>Organisation Name:</strong> <?php echo htmlspecialchars($ticket['organisation_name']); ?></p>
                    <p><strong>Service Type:</strong> <?php echo htmlspecialchars($ticket['service_type'] ?? 'General Fault'); ?></p>
                    <p><strong>Priority:</strong> <span class="badge priority-badge"><?php echo htmlspecialchars($ticket['priority'] ?? 'Normal'); ?></span></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($ticket['location_description'] ?? 'Not specified'); ?></p>
                    <p><strong>Date Reported:</strong> <?php echo date("d M Y, H:i", strtotime($ticket['date_reported'])); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['incident_description']); ?></p>
                </div>

                <!-- Animated Progress Tracker -->
                <div class="status-tracker">
                    <h3>Ticket Progress</h3>
                    <div class="tracker-steps">
                        <div class="step <?php echo !empty($ticket['pending']) ? 'active' : ''; ?>">
                            <div class="step-icon">1</div>
                            <p class="step-title">Pending</p>
                            <small><?php echo !empty($ticket['pending']) ? date("d M Y", strtotime($ticket['pending'])) : '—'; ?></small>
                        </div>

                        <div class="step-line <?php echo !empty($ticket['InProgress']) ? 'active-line' : ''; ?>"></div>

                        <div class="step <?php echo !empty($ticket['InProgress']) ? 'active' : ''; ?>">
                            <div class="step-icon">2</div>
                            <p class="step-title">In Progress</p>
                            <small><?php echo !empty($ticket['InProgress']) ? date("d M Y", strtotime($ticket['InProgress'])) : '—'; ?></small>
                        </div>

                        <div class="step-line <?php echo !empty($ticket['resolved']) ? 'active-line' : ''; ?>"></div>

                        <div class="step <?php echo !empty($ticket['resolved']) ? 'active' : ''; ?>">
                            <div class="step-icon">3</div>
                            <p class="step-title">Resolved</p>
                            <small><?php echo !empty($ticket['resolved']) ? date("d M Y", strtotime($ticket['resolved'])) : '—'; ?></small>
                        </div>
                    </div>
                </div>

                <!-- Timeline Comments Section -->
                <div class="comments-section">
                    <h3>Official Comments & Updates</h3>
                    <?php if (!empty($comments)): ?>
                        <?php 
                            $totalComments = count($comments);
                            $initialLimit = 3;
                        ?>
                        <div class="timeline" id="commentsTimeline">
                            <?php foreach ($comments as $index => $c): ?>
                                <?php 
                                    // Hide comments beyond the first 3
                                    $isHidden = ($index >= $initialLimit) ? 'hidden-comment' : '';
                                ?>
                                <div class="timeline-item <?php echo $isHidden; ?>" style="--delay: <?php echo ($index % $initialLimit) * 0.15; ?>s;">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div class="author-header">
                                            <strong><?php echo htmlspecialchars(($c['first_Name'] ?? 'Unknown') . ' ' . ($c['surname'] ?? '')); ?></strong>
                                            <span class="role-badge"><?php echo htmlspecialchars($c['User_role'] ?? 'Official'); ?></span>
                                        </div>
                                        <p class="comment-body"><?php echo htmlspecialchars($c['comment_text']); ?></p>
                                        <span class="comment-time"><?php echo date("d M Y, H:i", strtotime($c['comment_date'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalComments > $initialLimit): ?>
                            <div class="toggle-comments-wrapper">
                                <button type="button" id="toggleCommentsBtn" class="btn-toggle-comments" data-expanded="false">
                                    Show All Comments (<?php echo $totalComments; ?>)
                                </button>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p class="no-comments">No comments or updates added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="message">
                <h3>Ticket Not Found</h3>
                <p>Unable to locate this ticket or you do not have permission to view it.</p>
                <a class="details-button" href="ticketHistory.php">Back to History</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="ticketDetails.js"></script>
</body>
</html>

