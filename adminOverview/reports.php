<?php

session_start();

require_once "Error404Connection.php";

//check if administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

//check administrator role
$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);

if ($userRole !== "admin") {

    die("Access denied.");

}

//get administrator profile for sidebar and topbar
$adminName = $adminUsername;
$adminRole = "System Admin";
$profileImage = "";

$adminProfileQuery = "
    SELECT
        u.first_Name,
        u.surname,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
";

$adminProfileStmt = $conn->prepare($adminProfileQuery);

if ($adminProfileStmt) {

    $adminProfileStmt->bind_param("s", $adminUsername);
    $adminProfileStmt->execute();

    $adminProfileResult = $adminProfileStmt->get_result();

    if (
        $adminProfileResult &&
        $adminProfileResult->num_rows > 0
    ) {

        $adminProfile = $adminProfileResult->fetch_assoc();

        $fullName = trim(
            ($adminProfile["first_Name"] ?? "")
            . " "
            . ($adminProfile["surname"] ?? "")
        );

        if ($fullName !== "") {
            $adminName = $fullName;
        }

        $profileImage = trim(
            $adminProfile["image_path"] ?? ""
        );
    }

    $adminProfileStmt->close();
}

//filters
$wardFilter = (int)($_GET["ward_id"] ?? 0);
$serviceFilter = (int)($_GET["service_id"] ?? 0);
$priorityFilter = trim($_GET["priority"] ?? "");

//get wards
$wards = [];

$wardResult = $conn->query("
    SELECT
        ward_id,
        ward_number,
        ward_name
    FROM ward
    ORDER BY ward_number ASC
");

if ($wardResult) {

    while ($row = $wardResult->fetch_assoc()) {

        $wards[] = $row;

    }

}

//get municipal services
$services = [];

$serviceResult = $conn->query("
    SELECT
        service_id,
        service_type
    FROM municipal_service
    WHERE active = 1
    ORDER BY service_type ASC
");

if ($serviceResult) {

    while ($row = $serviceResult->fetch_assoc()) {

        $services[] = $row;

    }

}

//build filters
$whereConditions = [];
$params = [];
$types = "";

if ($wardFilter > 0) {

    $whereConditions[] = "it.ward_id = ?";
    $params[] = $wardFilter;
    $types .= "i";

}

if ($serviceFilter > 0) {

    $whereConditions[] = "it.service_id = ?";
    $params[] = $serviceFilter;
    $types .= "i";

}

if ($priorityFilter !== "") {

    $whereConditions[] = "it.priority = ?";
    $params[] = $priorityFilter;
    $types .= "s";

}

$whereSql = "";

if (!empty($whereConditions)) {

    $whereSql =
        " WHERE "
        . implode(
            " AND ",
            $whereConditions
        );

}

//run prepared query
function runReportQuery(
    $conn,
    $sql,
    $types = "",
    $params = []
) {

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die(
            "Database error: "
            . $conn->error
        );

    }

    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );

    }

    if (!$stmt->execute()) {

        die(
            "Database error: "
            . $stmt->error
        );

    }

    return $stmt;

}

//total tickets
$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket it
    $whereSql
";

$stmt = runReportQuery(
    $conn,
    $sql,
    $types,
    $params
);

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$totalTickets =
    (int)($row["total"] ?? 0);

$stmt->close();

//resolved tickets
$resolvedConditions =
    $whereConditions;

$resolvedConditions[] =
    "it.date_resolved IS NOT NULL";

$resolvedWhere =
    " WHERE "
    . implode(
        " AND ",
        $resolvedConditions
    );

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket it
    $resolvedWhere
";

$stmt = runReportQuery(
    $conn,
    $sql,
    $types,
    $params
);

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$resolvedTickets =
    (int)($row["total"] ?? 0);

$stmt->close();

//unresolved tickets
$unresolvedConditions =
    $whereConditions;

$unresolvedConditions[] =
    "it.date_resolved IS NULL";

$unresolvedWhere =
    " WHERE "
    . implode(
        " AND ",
        $unresolvedConditions
    );

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket it
    $unresolvedWhere
";

$stmt = runReportQuery(
    $conn,
    $sql,
    $types,
    $params
);

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$unresolvedTickets =
    (int)($row["total"] ?? 0);

$stmt->close();

//tickets by ward
$wardConditions = [];
$wardParams = [];
$wardTypes = "";

if ($wardFilter > 0) {

    $wardConditions[] =
        "w.ward_id = ?";

    $wardParams[] =
        $wardFilter;

    $wardTypes .= "i";

}

if ($serviceFilter > 0) {

    $wardConditions[] =
        "it.service_id = ?";

    $wardParams[] =
        $serviceFilter;

    $wardTypes .= "i";

}

if ($priorityFilter !== "") {

    $wardConditions[] =
        "it.priority = ?";

    $wardParams[] =
        $priorityFilter;

    $wardTypes .= "s";

}

$wardWhere = "";

if (!empty($wardConditions)) {

    $wardWhere =
        " WHERE "
        . implode(
            " AND ",
            $wardConditions
        );

}

$sql = "
    SELECT
        w.ward_id,
        w.ward_number,
        w.ward_name,
        COUNT(it.incident_id) AS total_tickets

    FROM ward w

    LEFT JOIN incident_ticket it
        ON w.ward_id = it.ward_id

    $wardWhere

    GROUP BY
        w.ward_id,
        w.ward_number,
        w.ward_name

    ORDER BY
        w.ward_number ASC
";

$stmtWard = runReportQuery(
    $conn,
    $sql,
    $wardTypes,
    $wardParams
);

$wardResult =
    $stmtWard->get_result();

$wardRows = [];

while (
    $row =
    $wardResult->fetch_assoc()
) {

    $wardRows[] = $row;

}

//tickets by service
$serviceConditions = [
    "ms.active = 1"
];

$serviceParams = [];
$serviceTypes = "";

if ($serviceFilter > 0) {

    $serviceConditions[] =
        "ms.service_id = ?";

    $serviceParams[] =
        $serviceFilter;

    $serviceTypes .= "i";

}

if ($wardFilter > 0) {

    $serviceConditions[] =
        "it.ward_id = ?";

    $serviceParams[] =
        $wardFilter;

    $serviceTypes .= "i";

}

if ($priorityFilter !== "") {

    $serviceConditions[] =
        "it.priority = ?";

    $serviceParams[] =
        $priorityFilter;

    $serviceTypes .= "s";

}

$serviceWhere =
    " WHERE "
    . implode(
        " AND ",
        $serviceConditions
    );

$sql = "
    SELECT
        ms.service_id,
        ms.service_type,
        COUNT(it.incident_id) AS total_tickets

    FROM municipal_service ms

    LEFT JOIN incident_ticket it
        ON ms.service_id = it.service_id

    $serviceWhere

    GROUP BY
        ms.service_id,
        ms.service_type

    ORDER BY
        total_tickets DESC,
        ms.service_type ASC
";

$stmtService = runReportQuery(
    $conn,
    $sql,
    $serviceTypes,
    $serviceParams
);

$serviceResult =
    $stmtService->get_result();

$serviceRows = [];

while (
    $row =
    $serviceResult->fetch_assoc()
) {

    $serviceRows[] = $row;

}

//tickets by priority
$sql = "
    SELECT

        COALESCE(
            NULLIF(
                TRIM(it.priority),
                ''
            ),
            'Not Set'
        ) AS priority_name,

        COUNT(*) AS total_tickets

    FROM incident_ticket it

    $whereSql

    GROUP BY
        COALESCE(
            NULLIF(
                TRIM(it.priority),
                ''
            ),
            'Not Set'
        )

    ORDER BY
        total_tickets DESC
";

$stmtPriority = runReportQuery(
    $conn,
    $sql,
    $types,
    $params
);

$priorityResult =
    $stmtPriority->get_result();

$priorityRows = [];

while (
    $row =
    $priorityResult->fetch_assoc()
) {

    $priorityRows[] = $row;

}

//complaint history
$sql = "
    SELECT
        it.incident_id,
        it.username,
        it.priority,
        it.incident_description,
        it.location_description,
        it.date_reported,
        it.date_resolved,

        w.ward_number,

        ms.service_type

    FROM incident_ticket it

    LEFT JOIN ward w
        ON it.ward_id = w.ward_id

    LEFT JOIN municipal_service ms
        ON it.service_id = ms.service_id

    $whereSql

    ORDER BY
        it.date_reported DESC

    LIMIT 50
";

$stmtHistory = runReportQuery(
    $conn,
    $sql,
    $types,
    $params
);

$historyResult =
    $stmtHistory->get_result();

//prepare ward graph data
$wardLabels = [];
$wardData = [];

foreach ($wardRows as $ward) {

    $wardLabels[] =
        "Ward "
        . $ward["ward_number"];

    $wardData[] =
        (int)$ward["total_tickets"];

}

//prepare service graph data
$serviceLabels = [];
$serviceData = [];

foreach ($serviceRows as $service) {

    $serviceLabels[] =
        $service["service_type"];

    $serviceData[] =
        (int)$service["total_tickets"];

}

//prepare priority graph data
$priorityLabels = [];
$priorityData = [];

foreach ($priorityRows as $priority) {

    $priorityLabels[] =
        $priority["priority_name"];

    $priorityData[] =
        (int)$priority["total_tickets"];

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    M-LINK | Reports
</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--cream);
    color: #333;
}

a {
    text-decoration: none;
}

.page-heading {
    padding: 30px 35px 0;
}

.page-heading h1 {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 29px;
    font-weight: 600;
    color: var(--teal);
}

.container {
    width: calc(100% - 70px);
    max-width: 1500px;
    margin: 25px auto 40px;
}

/* filters */

.filter-card {
    background: white;

    border: 1px solid #ddd7ca;

    border-radius: 8px;

    padding: 18px 20px;

    margin-bottom: 22px;
}

.filter-form {
    display: flex;

    gap: 10px;

    align-items: center;

    flex-wrap: wrap;
}

.filter-form select {
    min-width: 180px;

    height: 40px;

    padding: 0 11px;

    background: #f7f8fa;

    border: 1px solid #d7dbe0;

    border-radius: 5px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 13px;

    outline: none;
}

.filter-form select:focus {
    border-color: #5f7022;

    background: white;
}

.button {
    min-height: 40px;

    padding: 0 17px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;
}

.button-primary {
    background: #5f7022;

    color: white;
}

.button-primary:hover {
    background: #4b591a;
}

.button-secondary {
    background: white;

    color: #555;

    border: 1px solid #d1cbc0;
}

.button-secondary:hover {
    background: #f4f1eb;
}

/* summary cards */

.summary-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 22px;
}

.summary-card {
    background: white;

    border: 1px solid #ddd7ca;

    border-radius: 8px;

    padding: 22px;

    border-top: 5px solid;
}

.summary-card h3 {
    font-size: 13px;

    color: #777;

    margin-bottom: 12px;
}

.summary-number {
    font-size: 30px;

    font-weight: 700;
}

.summary-card.total {
    border-top-color: #2563eb;
}

.summary-card.total .summary-number {
    color: #2563eb;
}

.summary-card.unresolved {
    border-top-color: #d97706;
}

.summary-card.unresolved .summary-number {
    color: #d97706;
}

.summary-card.resolved {
    border-top-color: #3f7d32;
}

.summary-card.resolved .summary-number {
    color: #3f7d32;
}

/* graph grid */

.chart-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 22px;

    margin-bottom: 22px;
}

.chart-card {
    background: white;

    border: 1px solid #ddd7ca;

    border-radius: 8px;

    padding: 20px 22px;

    min-height: 400px;
}

.chart-card h2 {
    font-size: 17px;

    margin-bottom: 20px;
}

.chart-wrapper {
    position: relative;

    width: 100%;

    height: 320px;
}

/* ward graph */

.ward-chart {
    border-top: 5px solid #2563eb;
}

.ward-chart h2 {
    color: #2563eb;
}

/* service graph */

.service-chart {
    border-top: 5px solid #7c3aed;
}

.service-chart h2 {
    color: #7c3aed;
}

.service-chart-layout {
    display: grid;

    grid-template-columns:
        minmax(0, 1.15fr)
        minmax(210px, 0.85fr);

    gap: 20px;

    align-items: center;

    height: 320px;
}

.service-chart-canvas {
    position: relative;

    width: 100%;

    height: 290px;

    min-width: 0;
}

.service-details {
    min-height: 230px;

    padding:
        20px
        10px
        20px
        25px;

    border-left:
        1px solid #e5e0d7;

    display: flex;

    flex-direction: column;

    justify-content: center;
}

.service-details h3 {
    font-size: 20px;

    line-height: 1.3;

    color: #7c3aed;

    margin-bottom: 18px;

    word-break: break-word;
}

.service-name-row {
    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 16px;

    color: #555;

    font-size: 13px;
}

.service-colour-dot {
    width: 14px;

    height: 14px;

    border-radius: 50%;

    background: #7c3aed;

    flex-shrink: 0;
}

.service-ticket-number {
    font-size: 42px;

    line-height: 1;

    font-weight: 700;

    color: #263238;

    margin-top: 5px;
}

.service-ticket-text {
    margin-top: 5px;

    margin-bottom: 18px;

    color: #777;

    font-size: 13px;
}

.service-percentage {
    background: #f7f5ef;

    border-radius: 5px;

    padding: 11px 13px;

    color: #666;

    font-size: 12px;

    line-height: 1.5;
}

/* priority graph */

.priority-chart {
    border-top: 5px solid #d97706;
}

.priority-chart h2 {
    color: #d97706;
}

/* resolution graph */

.resolution-chart {
    border-top: 5px solid #3f7d32;
}

.resolution-chart h2 {
    color: #3f7d32;
}

/* complaint history */

.history-card {
    background: white;

    border: 1px solid #ddd7ca;

    border-top: 5px solid #9f1239;

    border-radius: 8px;

    overflow-x: auto;
}

.history-card h2 {
    padding: 19px 22px;

    color: #9f1239;

    font-size: 17px;

    border-bottom:
        1px solid #e5e0d7;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}

thead {
    background: #f7f5ef;
}

th {
    padding: 13px 15px;

    text-align: left;

    font-size: 11px;

    font-weight: 600;

    color: #666;

    text-transform: uppercase;

    border-bottom:
        1px solid #ddd7ca;
}

td {
    padding: 14px 15px;

    font-size: 13px;

    vertical-align: top;

    border-bottom:
        1px solid #ebe7df;
}

tbody tr:hover {
    background: #faf9f5;
}

.description {
    max-width: 250px;

    line-height: 1.5;
}

.status-resolved {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 4px;

    background: #dcfce7;

    color: #166534;

    font-size: 11px;

    font-weight: 600;
}

.status-unresolved {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 4px;

    background: #ffedd5;

    color: #9a3412;

    font-size: 11px;

    font-weight: 600;
}

.empty-state {
    padding: 35px 20px;

    text-align: center;

    color: #777;

    font-size: 13px;
}

/* responsive */

@media (max-width: 1000px) {

    .summary-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .chart-grid {
        grid-template-columns:
            1fr;
    }

}

@media (max-width: 750px) {

    .service-chart-layout {
        grid-template-columns:
            1fr;

        height: auto;
    }

    .service-chart-canvas {
        height: 280px;
    }

    .service-details {
        border-left: none;

        border-top:
            1px solid #e5e0d7;

        padding:
            20px
            5px
            5px;

        min-height: auto;
    }

}

@media (max-width: 650px) {

    .container {
        width:
            calc(100% - 30px);
    }

    .summary-grid {
        grid-template-columns:
            1fr;
    }

    .filter-form {
        flex-direction: column;

        align-items: stretch;
    }

    .filter-form select,
    .filter-form .button {
        width: 100%;
    }

    .chart-wrapper {
        height: 280px;
    }

}

/* dashboard shell responsive */

@media (max-width: 750px) {

    .page-heading {
        padding: 25px 18px 0;
    }

    .container {
        width: calc(100% - 36px);
        margin-top: 20px;
    }
}

@media (max-width: 480px) {

    .page-heading {
        padding: 20px 12px 0;
    }

    .container {
        width: calc(100% - 24px);
    }
}

</style>

<!-- FONTS + ICONS USED BY THE SHARED ADMIN SIDEBAR / TOPBAR -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- SHARED ADMIN SIDEBAR / TOPBAR / FOOTER STYLES (keep this LAST in the head) -->
<link rel="stylesheet" href="adminLayout.css">

</head>

<body>

<?php
$adminActivePage = "reports";
require __DIR__ . "/adminHeader.php";
?>

        <div class="page-heading">
            <h1>Reports</h1>
        </div>

<div class="container">

<!-- filters -->

<div class="filter-card">

<form
    method="GET"
    action="reports.php"
    class="filter-form"
>

    <select name="ward_id">

        <option value="0">
            All Wards
        </option>

        <?php foreach (
            $wards as $ward
        ): ?>

            <option
                value="<?= (int)$ward["ward_id"] ?>"
                <?= $wardFilter ===
                    (int)$ward["ward_id"]
                    ? "selected"
                    : ""
                ?>
            >

                Ward <?= (int)$ward["ward_number"] ?>

            </option>

        <?php endforeach; ?>

    </select>

    <select name="service_id">

        <option value="0">
            All Services
        </option>

        <?php foreach (
            $services as $service
        ): ?>

            <option
                value="<?= (int)$service["service_id"] ?>"
                <?= $serviceFilter ===
                    (int)$service["service_id"]
                    ? "selected"
                    : ""
                ?>
            >

                <?= htmlspecialchars(
                    $service["service_type"]
                ) ?>

            </option>

        <?php endforeach; ?>

    </select>

    <select name="priority">

        <option value="">
            All Priorities
        </option>

        <option
            value="Low"
            <?= $priorityFilter === "Low"
                ? "selected"
                : ""
            ?>
        >
            Low
        </option>

        <option
            value="Medium"
            <?= $priorityFilter === "Medium"
                ? "selected"
                : ""
            ?>
        >
            Medium
        </option>

        <option
            value="High"
            <?= $priorityFilter === "High"
                ? "selected"
                : ""
            ?>
        >
            High
        </option>

        <option
            value="Critical"
            <?= $priorityFilter === "Critical"
                ? "selected"
                : ""
            ?>
        >
            Critical
        </option>

    </select>

    <button
        type="submit"
        class="button button-primary"
    >
        Filter
    </button>

    <a
        href="reports.php"
        class="button button-secondary"
    >
        Clear
    </a>

</form>

</div>

<!-- summary -->

<section class="summary-grid">

    <div class="summary-card total">

        <h3>
            Total Tickets
        </h3>

        <div class="summary-number">

            <?= number_format(
                $totalTickets
            ) ?>

        </div>

    </div>

    <div class="summary-card unresolved">

        <h3>
            Unresolved Tickets
        </h3>

        <div class="summary-number">

            <?= number_format(
                $unresolvedTickets
            ) ?>

        </div>

    </div>

    <div class="summary-card resolved">

        <h3>
            Resolved Tickets
        </h3>

        <div class="summary-number">

            <?= number_format(
                $resolvedTickets
            ) ?>

        </div>

    </div>

</section>

<!-- graphs -->

<section class="chart-grid">

    <!-- tickets by ward -->

    <div class="chart-card ward-chart">

        <h2>
            Tickets by Ward
        </h2>

        <div class="chart-wrapper">

            <canvas id="wardChart"></canvas>

        </div>

    </div>

    <!-- tickets by service -->

    <div class="chart-card service-chart">

        <h2>
            Tickets by Service
        </h2>

        <div class="service-chart-layout">

            <div class="service-chart-canvas">

                <canvas id="serviceChart"></canvas>

            </div>

            <div class="service-details">

                <h3 id="selectedServiceName">
                    Select a Service
                </h3>

                <div class="service-name-row">

                    <span
                        class="service-colour-dot"
                        id="selectedServiceColour"
                    ></span>

                    <span id="selectedServiceLabel">
                        Click a service on the graph
                    </span>

                </div>

                <div
                    class="service-ticket-number"
                    id="selectedServiceNumber"
                >
                    -
                </div>

                <div class="service-ticket-text">
                    Tickets
                </div>

                <div
                    class="service-percentage"
                    id="selectedServicePercentage"
                >
                    Select a service to view its details.
                </div>

            </div>

        </div>

    </div>

    <!-- tickets by priority -->

    <div class="chart-card priority-chart">

        <h2>
            Tickets by Priority
        </h2>

        <div class="chart-wrapper">

            <canvas id="priorityChart"></canvas>

        </div>

    </div>

    <!-- resolution progress -->

    <div class="chart-card resolution-chart">

        <h2>
            Resolution Progress
        </h2>

        <div class="chart-wrapper">

            <canvas id="resolutionChart"></canvas>

        </div>

    </div>

</section>

<!-- complaint history -->

<section class="history-card">

<h2>
    Complaint History
</h2>

<?php if (
    $historyResult &&
    $historyResult->num_rows > 0
): ?>

<table>

<thead>

<tr>

    <th>ID</th>

    <th>Resident</th>

    <th>Service</th>

    <th>Ward</th>

    <th>Priority</th>

    <th>Description</th>

    <th>Location</th>

    <th>Reported</th>

    <th>Status</th>

    <th>Resolved</th>

</tr>

</thead>

<tbody>

<?php while (
    $ticket =
    $historyResult->fetch_assoc()
): ?>

<tr>

<td>

    <?= (int)$ticket[
        "incident_id"
    ] ?>

</td>

<td>

    <?= htmlspecialchars(
        $ticket["username"]
        ?? "-"
    ) ?>

</td>

<td>

    <?= htmlspecialchars(
        $ticket["service_type"]
        ?? "Not Assigned"
    ) ?>

</td>

<td>

<?php

if (
    $ticket["ward_number"]
    !== null
) {

    echo "Ward "
        . (int)$ticket[
            "ward_number"
        ];

} else {

    echo "-";

}

?>

</td>

<td>

    <?= htmlspecialchars(
        $ticket["priority"]
        ?? "Not Set"
    ) ?>

</td>

<td>

<div class="description">

<?php

$description =
    $ticket[
        "incident_description"
    ] ?? "";

if (
    strlen($description) > 100
) {

    echo htmlspecialchars(
        substr(
            $description,
            0,
            100
        )
    )
    . "...";

} else {

    echo htmlspecialchars(
        $description
    );

}

?>

</div>

</td>

<td>

    <?= htmlspecialchars(
        $ticket[
            "location_description"
        ]
        ?? "-"
    ) ?>

</td>

<td>

<?php

if (
    !empty(
        $ticket[
            "date_reported"
        ]
    )
) {

    echo date(
        "d M Y H:i",
        strtotime(
            $ticket[
                "date_reported"
            ]
        )
    );

} else {

    echo "-";

}

?>

</td>

<td>

<?php if (
    !empty(
        $ticket[
            "date_resolved"
        ]
    )
): ?>

<span class="status-resolved">
    Resolved
</span>

<?php else: ?>

<span class="status-unresolved">
    Unresolved
</span>

<?php endif; ?>

</td>

<td>

<?php

if (
    !empty(
        $ticket[
            "date_resolved"
        ]
    )
) {

    echo date(
        "d M Y H:i",
        strtotime(
            $ticket[
                "date_resolved"
            ]
        )
    );

} else {

    echo "-";

}

?>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<div class="empty-state">
    No ticket history found.
</div>

<?php endif; ?>

</section>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>

<script>

//database data from PHP

const wardLabels =
    <?= json_encode(
        $wardLabels
    ) ?>;

const wardData =
    <?= json_encode(
        $wardData
    ) ?>;

const serviceLabels =
    <?= json_encode(
        $serviceLabels
    ) ?>;

const serviceData =
    <?= json_encode(
        $serviceData
    ) ?>;

const priorityLabels =
    <?= json_encode(
        $priorityLabels
    ) ?>;

const priorityData =
    <?= json_encode(
        $priorityData
    ) ?>;

const resolvedTickets =
    <?= (int)$resolvedTickets ?>;

const unresolvedTickets =
    <?= (int)$unresolvedTickets ?>;

//general graph colours

const graphColours = [

    "#2563eb",
    "#7c3aed",
    "#d97706",
    "#3f7d32",
    "#9f1239",
    "#0891b2",
    "#db2777",
    "#65a30d",
    "#9333ea",
    "#ea580c",
    "#0284c7",
    "#15803d",
    "#be123c",
    "#a16207"

];

//tickets by ward

new Chart(
    document.getElementById(
        "wardChart"
    ),
    {

        type:
            "bar",

        data: {

            labels:
                wardLabels,

            datasets: [

                {

                    label:
                        "Tickets",

                    data:
                        wardData,

                    backgroundColor:
                        graphColours,

                    borderRadius:
                        5,

                    borderSkipped:
                        false

                }

            ]

        },

        options: {

            responsive:
                true,

            maintainAspectRatio:
                false,

            plugins: {

                legend: {

                    display:
                        false

                },

                tooltip: {

                    callbacks: {

                        label:
                            function(context) {

                                return (
                                    context.raw
                                    + " tickets"
                                );

                            }

                    }

                }

            },

            scales: {

                y: {

                    beginAtZero:
                        true,

                    ticks: {

                        precision:
                            0

                    }

                },

                x: {

                    grid: {

                        display:
                            false

                    }

                }

            }

        }

    }
);

//tickets by service

const serviceColours = [

    "#7c3aed",
    "#d97706",
    "#3f7d32",
    "#9f1239",
    "#0891b2",
    "#db2777",
    "#65a30d",
    "#2563eb",
    "#ea580c",
    "#15803d",
    "#9333ea",
    "#be123c",
    "#a16207",
    "#0284c7"

];

const totalServiceTickets =
    serviceData.reduce(
        function(total, tickets) {

            return (
                total
                + Number(tickets)
            );

        },
        0
    );

const serviceChart =
    new Chart(
        document.getElementById(
            "serviceChart"
        ),
        {

            type:
                "doughnut",

            data: {

                labels:
                    serviceLabels,

                datasets: [

                    {

                        data:
                            serviceData,

                        backgroundColor:
                            serviceColours,

                        borderColor:
                            "#ffffff",

                        borderWidth:
                            3,

                        hoverOffset:
                            12

                    }

                ]

            },

            options: {

                responsive:
                    true,

                maintainAspectRatio:
                    false,

                cutout:
                    "62%",

                plugins: {

                    legend: {

                        display:
                            false

                    },

                    tooltip: {

                        enabled:
                            true,

                        callbacks: {

                            label:
                                function(context) {

                                    const serviceName =
                                        context.label;

                                    const ticketCount =
                                        Number(
                                            context.raw
                                        );

                                    return (
                                        serviceName
                                        + ": "
                                        + ticketCount
                                        + " tickets"
                                    );

                                }

                        }

                    }

                },

                onClick:
                    function(
                        event,
                        elements
                    ) {

                        if (
                            elements.length === 0
                        ) {

                            return;

                        }

                        const index =
                            elements[0].index;

                        const serviceName =
                            serviceLabels[
                                index
                            ];

                        const ticketCount =
                            Number(
                                serviceData[
                                    index
                                ]
                            );

                        const serviceColour =
                            serviceColours[
                                index %
                                serviceColours.length
                            ];

                        let percentage = 0;

                        if (
                            totalServiceTickets > 0
                        ) {

                            percentage =
                                (
                                    ticketCount /
                                    totalServiceTickets
                                )
                                * 100;

                        }

                        document
                            .getElementById(
                                "selectedServiceName"
                            )
                            .textContent =
                                serviceName;

                        document
                            .getElementById(
                                "selectedServiceName"
                            )
                            .style.color =
                                serviceColour;

                        document
                            .getElementById(
                                "selectedServiceLabel"
                            )
                            .textContent =
                                serviceName;

                        document
                            .getElementById(
                                "selectedServiceColour"
                            )
                            .style.backgroundColor =
                                serviceColour;

                        document
                            .getElementById(
                                "selectedServiceNumber"
                            )
                            .textContent =
                                ticketCount;

                        document
                            .getElementById(
                                "selectedServicePercentage"
                            )
                            .textContent =
                                percentage.toFixed(1)
                                + "% of service tickets";

                    }

            }

        }
    );

//tickets by priority

const priorityColours =
    priorityLabels.map(
        function(priority) {

            const value =
                priority.toLowerCase();

            if (value === "low") {

                return "#3f7d32";

            }

            if (value === "medium") {

                return "#eab308";

            }

            if (value === "high") {

                return "#ea580c";

            }

            if (value === "critical") {

                return "#b91c1c";

            }

            return "#6b7280";

        }
    );

new Chart(
    document.getElementById(
        "priorityChart"
    ),
    {

        type:
            "bar",

        data: {

            labels:
                priorityLabels,

            datasets: [

                {

                    label:
                        "Tickets",

                    data:
                        priorityData,

                    backgroundColor:
                        priorityColours,

                    borderRadius:
                        5,

                    borderSkipped:
                        false

                }

            ]

        },

        options: {

            responsive:
                true,

            maintainAspectRatio:
                false,

            plugins: {

                legend: {

                    display:
                        false

                },

                tooltip: {

                    callbacks: {

                        label:
                            function(context) {

                                return (
                                    context.raw
                                    + " tickets"
                                );

                            }

                    }

                }

            },

            scales: {

                y: {

                    beginAtZero:
                        true,

                    ticks: {

                        precision:
                            0

                    }

                },

                x: {

                    grid: {

                        display:
                            false

                    }

                }

            }

        }

    }
);

//resolution progress

new Chart(
    document.getElementById(
        "resolutionChart"
    ),
    {

        type:
            "doughnut",

        data: {

            labels: [

                "Resolved",
                "Unresolved"

            ],

            datasets: [

                {

                    data: [

                        resolvedTickets,
                        unresolvedTickets

                    ],

                    backgroundColor: [

                        "#3f7d32",
                        "#d97706"

                    ],

                    borderColor:
                        "#ffffff",

                    borderWidth:
                        3,

                    hoverOffset:
                        10

                }

            ]

        },

        options: {

            responsive:
                true,

            maintainAspectRatio:
                false,

            cutout:
                "65%",

            plugins: {

                legend: {

                    position:
                        "bottom",

                    labels: {

                        padding:
                            20,

                        usePointStyle:
                            true

                    }

                },

                tooltip: {

                    callbacks: {

                        label:
                            function(context) {

                                return (
                                    context.label
                                    + ": "
                                    + context.raw
                                    + " tickets"
                                );

                            }

                    }

                }

            }

        }

    }
);
</script>

</body>

</html>

<?php

$stmtWard->close();
$stmtService->close();
$stmtPriority->close();
$stmtHistory->close();

$conn->close();

?>