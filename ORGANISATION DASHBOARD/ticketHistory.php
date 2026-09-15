<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require "../Error404Connection.php";
    require_once "detailsOfTheLoggedInUser.php";

    // Ensure user is logged in
    $loggedInUsername = $_SESSION['username'] ?? '';
    if (empty($loggedInUsername)) {
        header("Location: login.php");
        exit;
    }

    $filterWard     = $_GET['ward_id'] ?? '';
    $filterCategory = $_GET['category_id'] ?? '';
    $filterStatus   = $_GET['status'] ?? '';
    $sortDate       = $_GET['sort_date'] ?? 'DESC'; // Default to newest first

    $wardsList = [];
    $wardRes = $conn->query("SELECT ward_id, ward_name FROM ward ORDER BY ward_name ASC");
    if ($wardRes) {
        while ($w = $wardRes->fetch_assoc()) {
            $wardsList[] = $w;
        }
    }

    $categoriesList = [];
    $catRes = $conn->query("SELECT service_id, service_type FROM municipal_service ORDER BY service_type ASC");
    if ($catRes) {
        while ($c = $catRes->fetch_assoc()) {
            $categoriesList[] = $c;
        }
    }

    $whereClauses = ["i.username = ?"];
    $bindParams   = [$loggedInUsername];
    $bindTypes    = "s";

    if (!empty($filterWard)) {
        $whereClauses[] = "i.ward_id = ?";
        $bindParams[]   = (int)$filterWard;
        $bindTypes     .= "i";
    }

    if (!empty($filterCategory)) {
        $whereClauses[] = "i.service_id = ?";
        $bindParams[]   = (int)$filterCategory;
        $bindTypes     .= "i";
    }

    if ($filterStatus === 'open') {
        $whereClauses[] = "ts.InProgress IS NULL AND ts.resolved IS NULL";
    } elseif ($filterStatus === 'in_progress') {
        $whereClauses[] = "ts.InProgress IS NOT NULL AND ts.resolved IS NULL";
    } elseif ($filterStatus === 'resolved') {
        $whereClauses[] = "ts.resolved IS NOT NULL";
    }

    // Order direction validation
    $sortOrder = (strtoupper($sortDate) === 'ASC') ? 'ASC' : 'DESC';

    $sql = "
        SELECT 
            i.incident_id,
            ms.service_type,
            ad.street_name,
            w.ward_name,
            i.date_reported,
            i.incident_description,
            ts.pending,
            ts.InProgress,
            ts.resolved
        FROM 
            incident_ticket i
        LEFT JOIN 
            municipal_service ms ON i.service_id = ms.service_id
        LEFT JOIN 
            address_directory ad ON i.address_id = ad.address_id
        LEFT JOIN 
            ward w ON i.ward_id = w.ward_id
        INNER JOIN 
            ticket_status ts ON i.status_id = ts.status_id
        WHERE 
            " . implode(" AND ", $whereClauses) . "
        ORDER BY 
            i.date_reported $sortOrder
    ";

    $userTickets = [];
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            if (!empty($row['resolved'])) {
                $statusLabel = 'Resolved';
                $statusClass = 'resolved-status';
            } elseif (!empty($row['InProgress'])) {
                $statusLabel = 'In Progress';
                $statusClass = 'progress-status';
            } else {
                $statusLabel = 'Open';
                $statusClass = 'open-status';
            }

            $formattedDate = date("d M Y, H:i", strtotime($row['date_reported']));

            $userTickets[] = [
                'id'          => $row['incident_id'],
                'service'     => $row['service_type'] ?? 'General Fault',
                'street'      => $row['street_name'] ?? 'Location Unspecified',
                'ward'        => $row['ward_name'] ?? 'N/A',
                'description' => $row['incident_description'] ?? '',
                'date'        => $formattedDate,
                'statusLabel' => $statusLabel,
                'statusClass' => $statusClass
            ];
        }
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reported Tickets</title>
    <!-- Include your original page stylesheet here -->
    <link rel="stylesheet" href="viewAllTickets.css"> 
    <link rel="stylesheet" href="residence.css"> 
</head>
<body>
    <input type="checkbox" id="sidebarToggle">
    <main class="container" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">

        <!-- ================= TOP BAR ================= -->    
        <header class="topbar">
            
            <!-- Hamburger -->
            <label for="sidebarToggle" class="menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <!-- Logo -->
            <div class="top-logo">
                <h2>M-LINK</h2>
                <p>Makhanda Connect</p>
            </div>

            <!-- Right side -->
            <!-- <div class="top-right">
                <div class="icon">
                    <span>2</span>
                </div>
                <div class="profile">
                    <img src="<?php echo $profileImg; ?>" alt="Resident Profile">
                    <h3><?php echo $fullName; ?></h3>
                    <p><?php echo $userRole; ?></p>
                    <span><?php echo $wardNum; ?></span>
                </div>
            </div> -->
        </header>

        <!-- ####### NAVIGATION/SIDEBAR ####### -->
        <aside class="sidebar" id="sidebar">
            <!-- Profile -->
            <div class="profile">
                <img src="<?php echo $profileImg; ?>" alt="Resident Profile">
                <h3><?php echo $fullName; ?></h3>
                <p><?php echo $userRole; ?></p>
                <span><?php echo $wardNum; ?></span>
            </div>

            <!-- Navigation -->
            <ul class="menu">
                <!-- DASHBOARD -->
                <li class="active">
                    <span>
                        Resident Dashboard
                    </span>
                </li>
                <!-- TICKETS -->
                <li class="menu-title">
                    TICKETS
                </li>
                <li>
                    <span>
                        <a href="reportFaultTicketForm.php" style="color: inherit; text-decoration: none;">
                            Create Ticket / Report a Fault
                        </a>
                    </span>
                </li>
                <li>
                    <span>
                        <a href="ticketHistory.php" style="color: inherit; text-decoration: none;"> Ticket History </a>
                    </span>
                </li>
                <!-- ACCOUNT -->

                <li class="menu-title">
                    ACCOUNT
                </li>
                <li>
                    <span>
                        <a href="settings.php" style="color: inherit; text-decoration: none;">Profile</a>
                    </span>
                </li>
                <!-- COMMUNITY -->
                <li class="menu-title">
                    COMMUNITY
                </li>
                <li>
                    <span>
                        Reviews & FAQ
                    </span>
                </li>
                <li>
                    <span>
                        Ward Information & Notices
                    </span>
                </li>
                <!-- LOGOUT -->
                <li class="logout">
                    <span>
                        Logout
                    </span>
                </li>
            </ul>
        </aside>

        <!-- HEADER NAVIGATION -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>My Reported Tickets</h2>
            <a href="dashboard.php" style="text-decoration: none; color: #0056b3; font-weight: 600;">← Back to Dashboard</a>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-card">
            <form method="GET" action="viewAllTickets.php" class="filter-form">
                
                <!-- Ward Filter -->
                <div class="filter-group">
                    <label for="ward_id">Ward</label>
                    <select name="ward_id" id="ward_id">
                        <option value="">All Wards</option>
                        <?php foreach ($wardsList as $w): ?>
                            <option value="<?php echo $w['ward_id']; ?>" <?php echo ($filterWard == $w['ward_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($w['ward_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category / Service Filter -->
                <div class="filter-group">
                    <label for="category_id">Category</label>
                    <select name="category_id" id="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?php echo $cat['service_id']; ?>" <?php echo ($filterCategory == $cat['service_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['service_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="open" <?php echo ($filterStatus === 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo ($filterStatus === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo ($filterStatus === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>

                <!-- Date Sorting -->
                <div class="filter-group">
                    <label for="sort_date">Date Sort</label>
                    <select name="sort_date" id="sort_date">
                        <option value="DESC" <?php echo ($sortOrder === 'DESC') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="ASC" <?php echo ($sortOrder === 'ASC') ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">Apply Filters</button>
                    <a href="view_all_tickets.php" class="btn-reset">Reset</a>
                </div>

            </form>
        </div>

        <!-- TICKET LISTING PANEL -->
        <div class="panel tickets-panel">
            <div class="panel-header">
                <h3>Filtered Tickets (<?php echo count($userTickets); ?>)</h3>
            </div>

            <div class="ticket-list">
                <?php if (!empty($userTickets)): ?>
                    <?php foreach ($userTickets as $ticket): ?>
                        <div class="ticket">
                            <div class="ticket-info">
                                <h4>#ID-<?php echo htmlspecialchars($ticket['id']); ?></h4>
                                <p><strong><?php echo htmlspecialchars($ticket['service']); ?></strong> — <?php echo htmlspecialchars($ticket['description']); ?></p>
                                <small>
                                    <?php echo htmlspecialchars($ticket['street']); ?> 
                                    (Ward: <?php echo htmlspecialchars($ticket['ward']); ?>) · 
                                    <?php echo htmlspecialchars($ticket['date']); ?>
                                </small>
                            </div>
                            <span class="status <?php echo htmlspecialchars($ticket['statusClass']); ?>">
                                <?php echo htmlspecialchars($ticket['statusLabel']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ticket" style="padding: 20px; text-align: center;">
                        <p style="color: #888; margin: 0;">No tickets found matching your filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

</body>
</html>