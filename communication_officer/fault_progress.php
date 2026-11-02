<?php

session_start();
if(!isset($_SESSION["username"])){
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}

require_once '../Error404Connection.php';
$searchKeyword = isset($_GET["search_keyword"]) ? trim($_GET["search_keyword"]) : '';

$ticket = null;
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
        WHERE 1=1";

$params = [];
$types   = "";

if ($searchKeyword !== '') {
    if (is_numeric($searchKeyword)) {
        $sql .= " AND it.incident_id = ?";
        $params[] = intval($searchKeyword);
        $types   .= "i";
    } else {
        $sql .= " AND (it.incident_description LIKE ? OR it.location_description LIKE ? OR s.service_type LIKE ?)";
        $searchTerm = "%" . $searchKeyword . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types   .= "sss";
    }
}

$sql .= " ORDER BY it.date_reported DESC LIMIT 1";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $ticket = $result->fetch_assoc();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makana Municipality - Service Fault Overview</title>
    
    <!-- GOOGLE FONTS: Lora & Ubuntu -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,700;1,700&family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="fault_progress.css"> 

    <style>
        body, input, select, button, textarea {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 400;
        }

        h1, h2, h3, h4, h5, h6, .main-title, .section-title {
            font-family: 'Lora', serif;
            font-weight: 700;
        }

        label, th, .backBtn, .btn-search, .badge, .brand-subtitle, .section-subtitle, .stage-title, .ward-badge, strong {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="header-navigation">
    <a href="javascript:history.back()" class="backBtn">
     Back
    </a>
    </div>
    <div class="container">
        <div class="brand-subtitle">Makana Municipality</div>
        <h1 class="main-title">Communication Officer</h1>

        <!-- PAGE SECTION TITLE -->
        <div class="section-header">
            <div class="section-subtitle">System Overview</div>
            <h2 class="section-title">Service Fault Overview</h2>
        </div>

        <!-- SEARCH BAR CARD -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label><i class="fa-solid fa-magnifying-glass"></i> Search Ticket</label>
                    <input type="text" name="search_keyword" placeholder="Search ID or Keyword..." value="<?php echo htmlspecialchars($searchKeyword); ?>">
                </div>

                <button type="submit" class="btn-search"><i class="fa-solid fa-search"></i> Search</button>
            </form>
        </div>

    
        <?php if ($ticket): ?>
            <?php
                $hasPending = !empty($ticket['pending']);
                $hasInProgress = !empty($ticket['InProgress']);
                $hasResolved = !empty($ticket['resolved']);

                $progressWidth = "0%";
                $currentStage = 'pending';
                $stageLabel = 'Pending';

                if ($hasResolved) {
                    $progressWidth = "100%";
                    $currentStage = 'resolved';
                    $stageLabel = 'Resolved';
                } elseif ($hasInProgress) {
                    $progressWidth = "50%";
                    $currentStage = 'in_progress';
                    $stageLabel = 'In Progress';
                }

                $pClass = strtolower($ticket['priority']);
            ?>

            <div class="ticket-card">
                <div class="ticket-card-header">
                    <h3>Ticket Number: <?php echo htmlspecialchars($ticket['incident_id']); ?> - <?php echo htmlspecialchars($ticket['service_type'] ?? 'General Service'); ?></h3>
                    <span class="ward-badge">Ward <?php echo htmlspecialchars($ticket['ward_id']); ?></span>
                </div>

            
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

                
                <div class="details-grid">
                    <div class="detail-item"><strong>Reported By</strong><span><?php echo htmlspecialchars($ticket['username']); ?></span></div>
                    <div class="detail-item"><strong>Location</strong><span><?php echo htmlspecialchars($ticket['location_description']); ?></span></div>
                    <div class="detail-item"><strong>Date Logged</strong><span><?php echo htmlspecialchars($ticket['date_reported']); ?></span></div>
                    <div class="detail-item"><strong>Date Resolved</strong><span><?php echo $ticket['date_resolved'] ? htmlspecialchars($ticket['date_resolved']) : 'N/A'; ?></span></div>
                </div>

               
                <div class="description-box">
                    <label>Description</label>
                    <p class="description-content"><?php echo nl2br(htmlspecialchars($ticket['incident_description'])); ?></p>
                </div>

              
                <div class="summary-row">
                    <span><strong>Priority:</strong> <span class="badge badge-<?php echo $pClass; ?>"><?php echo htmlspecialchars($ticket['priority']); ?></span></span>
                    <span><strong>Current Stage:</strong> <span class="badge badge-stage-<?php echo $currentStage; ?>"><?php echo $stageLabel; ?></span></span>
                </div>
            </div>

        <?php else: ?>
            <div class="no-results">
                <h3>No Matching Incident Records</h3>
                <p>No incident found matching your current search input.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
<?php
    $conn->close();
?>
<script src="Global dashboard layout.js"></script>