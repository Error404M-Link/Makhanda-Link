<?php

session_start();

require_once "Error404Connection.php";

if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

if (
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "admin"
) {
    die("Access denied.");
}

$sql = "
    SELECT
        incident_id,
        ward_id,
        service_id,
        location_description,
        latitude,
        longitude,
        incident_description,
        priority,
        status_id,
        date_reported,
        created_at
    FROM incident_ticket
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Error retrieving tickets: " . $conn->error);
}

$totalTickets = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>M-Link | Municipal Tickets</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f0e7;
            color: #333;
            min-height: 100vh;
        }

        .dashboard-content {
            padding: 35px;
            flex: 1;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 30px;
            font-weight: 600;
            color: #333;
            margin-bottom: 7px;
        }

        .page-heading p {
            font-size: 14px;
            color: #777;
        }

        .user-count {
            min-width: 150px;
            background: white;
            border: 1px solid #ddd7ca;
            border-radius: 6px;
            padding: 17px 25px;
            text-align: center;
        }

        .user-count span {
            display: block;
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }

        .user-count strong {
            display: block;
            font-size: 27px;
            color: #4b561b;
        }

        .dashboard-panel {
            background: white;
            border: 1px solid #ddd7ca;
            border-radius: 7px;
            overflow: hidden;
        }

        .panel-header {
            padding: 22px 25px;
            border-bottom: 1px solid #ddd7ca;
        }

        .ticket-search {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 8px;
        }

        .search-input-wrapper {
            position: relative;
            flex: 1;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            pointer-events: none;
        }

        .ticket-search input {
            width: 100%;
            height: 42px;
            padding: 0 15px 0 42px;
            border: 1px solid #d2d7dc;
            border-radius: 5px;
            background: #f8f9fa;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #333;
            outline: none;
        }

        .ticket-search input::placeholder {
            color: #888;
        }

        .ticket-search input:focus {
            border-color: #5f7022;
            background: white;
        }

        .ticket-search button {
            height: 42px;
            padding: 0 22px;
            border: none;
            border-radius: 5px;
            background: #5f7022;
            color: white;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .ticket-search button:hover {
            background: #4b591a;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1250px;
        }

        thead {
            background: #f7f5ef;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #ddd7ca;
        }

        td {
            padding: 14px 16px;
            font-size: 13px;
            color: #333;
            border-bottom: 1px solid #ebe7df;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #faf9f5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .ticket-id {
            color: #4b561b;
            font-weight: 600;
        }

        .priority {
            display: inline-block;
            background: #f0f2e8;
            color: #59651f;
            padding: 5px 9px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .ticket-status {
            display: inline-block;
            background: #e1ecd2;
            color: #4b681c;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .view-button {
            display: inline-block;
            text-decoration: none;
            background: #5f7022;
            color: white;
            padding: 8px 13px;
            border-radius: 4px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 600;
        }

        .view-button:hover {
            background: #4b591a;
        }

        .description {
            max-width: 220px;
            line-height: 1.4;
        }

        .coordinates {
            white-space: nowrap;
        }

        .empty-state {
            padding: 60px 25px;
            text-align: center;
        }

        .empty-state h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 13px;
            color: #777;
        }

        @media (max-width: 800px) {

            .dashboard-content {
                padding: 20px;
            }

            .page-heading {
                flex-direction: column;
                align-items: stretch;
            }

            .user-count {
                width: 100%;
            }

        }

        @media (max-width: 600px) {

            .dashboard-content {
                padding: 18px;
            }

            .ticket-search {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input-wrapper {
                width: 100%;
            }

            .ticket-search button {
                width: 100%;
            }

            .page-heading h1 {
                font-size: 25px;
            }

        }

    
</style>

<!-- FONTS + ICONS USED BY THE SHARED ADMIN SIDEBAR / TOPBAR -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
>
<!-- SHARED ADMIN SIDEBAR / TOPBAR / FOOTER STYLES (keep this LAST in the head) -->
<link rel="stylesheet" href="adminLayout.css">

</head>

<body>

<?php
$adminActivePage = "tickets";
require __DIR__ . "/adminHeader.php";
?>

    <div class="dashboard-content">

        <section class="page-heading">

            <div>

                <h1>
                    Ward Ticket Management
                </h1>

                <p>
                    View and manage reported faults submitted.
                </p>

            </div>

            <div class="user-count">

                <span>
                    Total Tickets
                </span>

                <strong>
                    <?= number_format($totalTickets) ?>
                </strong>

            </div>

        </section>

        <section class="dashboard-panel">

            <div class="panel-header">

                <div class="ticket-search">

                    <div class="search-input-wrapper">

                        <span class="search-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>

                        <input
                            type="text"
                            id="ticketSearch"
                            placeholder="Search tickets..."
                        >

                    </div>

                    <button
                        type="button"
                        id="searchButton"
                    >
                        Search
                    </button>

                </div>

            </div>

            <div class="table-container">

                <?php if ($result && $result->num_rows > 0): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>Incident ID</th>

                                <th>Ward</th>

                                <th>Service</th>

                                <th>Location</th>

                                <th>Description</th>

                                <th>Priority</th>

                                <th>Status</th>

                                <th>Date Reported</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <strong class="ticket-id">

                                            #<?= htmlspecialchars(
                                                $row["incident_id"]
                                            ) ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row["ward_id"] ??
                                            "Not assigned"
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row["service_id"] ??
                                            "Not assigned"
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row["location_description"] ??
                                            "Not provided"
                                        ) ?>

                                    </td>

                                    <td>

                                        <div class="description">

                                            <?= htmlspecialchars(
                                                $row["incident_description"] ??
                                                "Not provided"
                                            ) ?>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="priority">

                                            <?= htmlspecialchars(
                                                $row["priority"] ??
                                                "Not specified"
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="ticket-status">

                                            <?= htmlspecialchars(
                                                $row["status_id"] ??
                                                "Unknown"
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row["date_reported"] ??
                                            $row["created_at"] ??
                                            "Not available"
                                        ) ?>

                                    </td>

                                    <td>

                                        <a
                                            href="ticketDetails.php?ticketId=<?= urlencode(
                                                $row["incident_id"]
                                            ) ?>"
                                            class="view-button"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            No Reported Tickets
                        </h3>

                        <p>
                            There are currently no reported faults in the system.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    </div>

    <?php require __DIR__ . "/adminFooter.php"; ?>

<script>

const searchInput =
    document.getElementById("ticketSearch");

const searchButton =
    document.getElementById("searchButton");

function searchTickets() {

    const searchValue =
        searchInput.value.toLowerCase().trim();

    const rows =
        document.querySelectorAll(
            ".table-container tbody tr"
        );

    rows.forEach(function(row) {

        const rowText =
            row.textContent.toLowerCase();

        if (rowText.includes(searchValue)) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}

searchButton.addEventListener(
    "click",
    searchTickets
);

searchInput.addEventListener(
    "keyup",
    function(event) {

        if (event.key === "Enter") {

            searchTickets();

        }

    }
);

searchInput.addEventListener(
    "input",
    function() {

        if (
            searchInput.value.trim() === ""
        ) {

            searchTickets();

        }

    }
);

</script>

</body>

</html>

<?php

$conn->close();

?>