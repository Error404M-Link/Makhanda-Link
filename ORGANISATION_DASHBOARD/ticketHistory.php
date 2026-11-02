<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require "../Error404Connection.php";
    require_once "detailsOfTheLoggedInOrganisation.php";

    // Ensure user is logged in
    $loggedInUsername = $_SESSION['username'] ?? '';
    if (empty($loggedInUsername)) {
        header("Location: ../PUBLIC PAGES/login.php");
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
            i.location_description,
            i.latitude,
            i.longitude,
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

            $locationText = 'Location Unspecified';
            if (!empty($row['street_name'])) {
                $locationText = $row['street_name'];
            } elseif (!empty($row['location_description'])) {
                $locationText = $row['location_description'];
            } elseif (!empty($row['latitude']) && !empty($row['longitude'])) {
                $locationText = "GPS ({$row['latitude']}, {$row['longitude']})";
            }

            $userTickets[] = [
                'id'          => $row['incident_id'],
                'service'     => $row['service_type'] ?? 'General Fault',
                'street'      => $locationText,
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="viewAllTickets.css"> 
    <link rel="stylesheet" href="organisation.css"> 
    <style>
        .clickable-ticket {
            text-decoration: none;
            color: inherit;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            transition: background-color 0.2s ease;
        }
        .clickable-ticket:hover {
            background-color: #f8fafc;
        }
        .ticket-action {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-view-details {
            background-color: #1e3d2f;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-view-details:hover {
            background-color: #28a745;
        }
        <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f0e7;
            color: #263238;
        }


        a {
            text-decoration: none;
        }


        button,
        input,
        select {
            font-family: inherit;
        }


        /* SIDEBAR */

        .sidebar {
            width: 255px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #4b561b;
            color: #ffffff;
            z-index: 1000;
            transition: 0.3s ease;
            overflow-y: auto;
        }


        .sidebar-profile {
            padding: 35px 20px 25px;
            border-bottom:
                1px solid rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .sidebar-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .sidebar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .sidebar-avatar .material-icons {
            font-size: 42px;
            color: #ffffff;
        }


        .sidebar-profile-info {
            min-width: 0;
        }


        .sidebar-profile-info strong {
            display: block;
            color: #ffffff;
            font-size: 13px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .sidebar-profile-info span {
            display: block;
            color: rgba(255,255,255,0.72);
            font-size: 11px;
        }


        .sidebar-menu {
            padding: 25px 15px;
        }


        .sidebar-link {
            min-height: 46px;
            margin-bottom: 7px;
            padding: 0 15px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #ffffff;
            font-size: 13px;
            transition: 0.2s ease;
        }


        .sidebar-link:hover {
            background: rgba(255,255,255,0.12);
        }


        .sidebar-link.active {
            background: rgba(255,255,255,0.22);
            font-weight: bold;
        }


        .sidebar-icon {
            width: 20px;
            height: 20px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }


        .sidebar-material-icon {
            width: 20px;
            font-size: 20px;
        }


        /* =========================================
           MAIN WRAPPER
        ========================================= */

        .main-wrapper {
            margin-left: 255px;
            min-height: 100vh;
            transition: 0.3s ease;
        }


        /* TOPBAR */

        .topbar {
            height: 76px;
            background: #ffffff;
            border-bottom: 1px solid #e5e1d8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 500;
            box-shadow: 0 2px 7px rgba(0,0,0,0.04);
        }


        .topbar-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }


        .menu-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            color: #263238;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .menu-btn .material-icons {
            font-size: 28px;
        }


        .logo-link {
            display: flex;
            align-items: center;
        }


        .topbar-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }


        .topbar-right {
            display: flex;
            align-items: center;
        }


        .topbar-profile {
            padding-left: 20px;
            border-left: 1px solid #e5e1d8;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #263238;
        }


        .topbar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            background: #ece9df;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .topbar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .topbar-avatar .material-icons {
            font-size: 38px;
            color: #777;
        }


        .topbar-profile-info strong {
            display: block;
            font-size: 12px;
            margin-bottom: 3px;
        }


        .topbar-profile-info span {
            display: block;
            font-size: 10px;
            color: #999;
        }


        /* PAGE */

        .profile-page-content {
            padding: 55px 55px;
        }


        .page-title {
            margin-bottom: 20px;
            font-size: 27px;
            color: #263238;
        }


        /* NOTIFICATIONS */

        .successNotification,
        .errorNotification {
            padding: 13px 18px;
            margin-bottom: 18px;
            border-radius: 7px;
            text-align: center;
            font-size: 13px;
        }


        .successNotification {
            background: #d9f2df;
            border: 1px solid #b9dfc2;
            color: #176c2d;
        }


        .errorNotification {
            background: #fbe1df;
            border: 1px solid #efb8b4;
            color: #a82920;
        }


        /* PROFILE LAYOUT */

        .profile-layout {
            display: grid;
            grid-template-columns: 265px 1fr;
            gap: 22px;
            align-items: start;
        }


        .profile-sidebar-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 28px 20px;
        }


        .profileAvatarWrapper {
            width: 105px;
            height: 105px;
            margin: 0 auto 15px;
            position: relative;
        }


        .profileAvatarWrapper img {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4b561b;
        }


        .default-profile-icon {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            background: #ece9df;
            border: 2px solid #4b561b;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .default-profile-icon .material-icons {
            font-size: 85px;
            color: #777;
        }


        .editBadge {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            position: absolute;
            right: 2px;
            bottom: 4px;
            background: #ff7200;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #ffffff;
            font-size: 11px;
        }


        .profileImageForm {
            text-align: center;
        }


        .fileInputHidden {
            display: none;
        }


        .imageSaveButton {
            display: none;
            margin: 8px auto 15px;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            background: #4b561b;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
        }


        .profile-name {
            text-align: center;
            font-size: 16px;
            margin-bottom: 4px;
            color: #263238;
        }


        .profile-role {
            text-align: center;
            color: #929292;
            font-size: 12px;
            margin-bottom: 24px;
        }


        .profile-nav-button {
            width: 100%;
            height: 42px;
            margin-bottom: 7px;
            border: none;
            border-radius: 22px;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 15px;
            cursor: pointer;
            color: #7c878c;
            text-align: left;
        }


        .profile-nav-button.active {
            background: #fff0e6;
            color: #ff7200;
            font-weight: bold;
        }


        .profile-nav-button .material-icons {
            font-size: 18px;
        }


        /* =========================================
           FORM CARD
        ========================================= */

        .profile-content-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 32px 38px;
            min-height: 480px;
        }


        .profile-section-title {
            font-size: 20px;
            margin-bottom: 25px;
            color: #263238;
        }


        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-group.full {
            grid-column: 1 / -1;
        }


        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #8a969b;
            font-size: 12px;
        }


        .form-control {
            width: 100%;
            height: 42px;
            border: none;
            border-radius: 10px;
            background: #f5f6f7;
            padding: 0 16px;
            color: #263238;
            outline: none;
            font-size: 13px;
        }


        .form-control:focus {
            box-shadow: 0 0 0 2px rgba(75,86,27,0.18);
        }


        .username-wrapper {
            position: relative;
        }


        .username-wrapper .form-control {
            padding-right: 90px;
        }


        .verified-text {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #35b86b;
            font-size: 11px;
            font-weight: bold;
        }


        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 12px;
        }


        .discard-btn,
        .save-btn {
            min-width: 140px;
            height: 42px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }


        .discard-btn {
            background: #ffffff;
            color: #ff7200;
            border: 1px solid #ff7200;
        }


        .save-btn {
            background: #ff7200;
            color: #ffffff;
            border: 1px solid #ff7200;
        }


        /* =========================================
           PASSWORD
        ========================================= */

        .password-section {
            display: none;
        }


        .password-info {
            margin-bottom: 25px;
            color: #899398;
            font-size: 13px;
            line-height: 1.6;
        }


        /* =========================================
           MODAL
        ========================================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            padding: 20px;
        }


        .modal-overlay.show {
            display: flex;
        }


        .modal-box {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 15px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }


        .modal-box h3 {
            margin-bottom: 10px;
            color: #263238;
        }


        .modal-box p {
            margin-bottom: 20px;
            color: #7e888d;
            font-size: 13px;
            line-height: 1.5;
        }


        .modal-input {
            width: 100%;
            height: 43px;
            border: 1px solid #d6d6d6;
            border-radius: 8px;
            padding: 0 13px;
            outline: none;
            margin-bottom: 18px;
        }


        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }


        .modal-cancel,
        .modal-confirm {
            min-width: 100px;
            height: 40px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }


        .modal-cancel {
            background: #ffffff;
            color: #555;
            border: 1px solid #ccc;
        }


        .modal-confirm {
            background: #4b561b;
            color: #ffffff;
            border: none;
        }


        /* =========================================
           COLLAPSED SIDEBAR
        ========================================= */

        body.sidebar-collapsed .sidebar {
            width: 78px;
        }


        body.sidebar-collapsed .main-wrapper {
            margin-left: 78px;
        }


        body.sidebar-collapsed
        .sidebar-profile-info,

        body.sidebar-collapsed
        .sidebar-link span:not(.material-icons) {
            display: none;
        }


        body.sidebar-collapsed
        .sidebar-profile {
            justify-content: center;
            padding-left: 10px;
            padding-right: 10px;
        }


        body.sidebar-collapsed
        .sidebar-link {
            justify-content: center;
            padding: 0;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 900px) {

            .sidebar {
                transform: translateX(-100%);
            }


            body.mobile-sidebar-open
            .sidebar {
                transform: translateX(0);
            }


            .main-wrapper,
            body.sidebar-collapsed
            .main-wrapper {
                margin-left: 0;
            }


            .profile-page-content {
                padding: 30px 20px;
            }


            .profile-layout {
                grid-template-columns: 1fr;
            }


            .form-grid {
                grid-template-columns: 1fr;
            }


            .form-group.full {
                grid-column: auto;
            }

        }


        @media (max-width: 600px) {

            .topbar {
                padding: 0 15px;
            }


            .topbar-profile-info {
                display: none;
            }


            .profile-content-card {
                padding: 25px 20px;
            }


            .form-actions {
                flex-direction: column;
            }


            .discard-btn,
            .save-btn {
                width: 100%;
            }

        }

    </style>
</head>
<body>
    <main class="container" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">


        <div class="sidebar-profile">

            <div class="sidebar-avatar">

                <?php if (!empty($userData["image_path"])): ?>

                    <img
                        src="<?php echo htmlspecialchars($userData["image_path"]); ?>"
                        alt="Profile"
                    >

                <?php else: ?>

                    <span class="material-icons">
                        account_circle
                    </span>

                <?php endif; ?>

            </div>


            <div class="sidebar-profile-info">

                <strong>
                    <?php
                    echo htmlspecialchars(
                        ($userData["organisation_name"] ?? "") 
                    );
                    ?>
                </strong>

                <span>
                    Organisation
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">


            <a
                href="organisationdb.php"
                class="sidebar-link active"
            >

                <img
                    src="../icons/home.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="reportFaultTicketForm.php"
                class="sidebar-link"
            >

                <span class="material-icons sidebar-material-icon">
                    report_problem
                </span>

                <span>
                    Report Fault
                </span>

            </a>


            <a
                href="ticketHistory.php"
                class="sidebar-link"
            >

                <span class="material-icons sidebar-material-icon">
                    confirmation_number
                </span>

                <span>
                    My Tickets
                </span>

            </a>


            <a
                href="settings.php"
                class="sidebar-link"
            >

                <span class="material-icons sidebar-material-icon">
                    settings
                </span>

                <span>
                    Profile
                </span>

            </a>


            <a href="logout.php" class="sidebar-link">

                <span class="material-icons sidebar-material-icon">
                    logout
                </span>

                <span>
                    Logout
                </span>

            </a>


        </nav>


    </aside>

        <!-- HEADER NAVIGATION -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Reported Tickets</h2>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-card">
            <form method="GET" action="ticketHistory.php" class="filter-form">
                
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
                    <a href="ticketHistory.php" class="btn-reset">Reset</a>
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
                            <a href="ticketDetails.php?ticketId=<?php echo htmlspecialchars($ticket['id']); ?>" class="clickable-ticket">
                                <div class="ticket-info">
                                    <h4>#ID-<?php echo htmlspecialchars($ticket['id']); ?></h4>
                                    <p><strong><?php echo htmlspecialchars($ticket['service']); ?></strong> — <?php echo htmlspecialchars($ticket['description']); ?></p>
                                    <small>
                                        <?php echo htmlspecialchars($ticket['street']); ?> 
                                        (Ward: <?php echo htmlspecialchars($ticket['ward']); ?>) · 
                                        <?php echo htmlspecialchars($ticket['date']); ?>
                                    </small>
                                </div>
                                <div class="ticket-action">
                                    <span class="status <?php echo htmlspecialchars($ticket['statusClass']); ?>">
                                        <?php echo htmlspecialchars($ticket['statusLabel']); ?>
                                    </span>
                                    <span class="btn-view-details">View Progress →</span>
                                </div>
                            </a>
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