<?php

require "dbconnections.php";

// Total incidents
$sqlTotal = "SELECT COUNT(*) AS total FROM incident_ticket";
$resultTotal = mysqli_query($conn, $sqlTotal);
$total = mysqli_fetch_assoc($resultTotal)['total'];

// Pending
$sqlPending = "SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE status_id = 1";
$resultPending = mysqli_query($conn, $sqlPending);
$pending = mysqli_fetch_assoc($resultPending)['total'];

// In Progress
$sqlProgress = "SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE status_id = 2";
$resultProgress = mysqli_query($conn, $sqlProgress);
$progress = mysqli_fetch_assoc($resultProgress)['total'];

// Resolved
$sqlResolved = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE status_id = 3";

$resultResolved = mysqli_query($conn, $sqlResolved);
$resolved = mysqli_fetch_assoc($resultResolved)['total'];


/*INCIDENTS BY WARD*/

$wardLabels = [];
$wardData = [];

$sqlWard = "SELECT w.ward_name, COUNT(i.incident_id) AS total
    FROM incident_ticket i
    JOIN wards w
    ON i.ward_id = w.ward_id
    GROUP BY w.ward_id, w.ward_name
    ORDER BY w.ward_id";

$resultWard = mysqli_query($conn, $sqlWard);
while ($row = mysqli_fetch_assoc($resultWard)) {
    $wardLabels[] = $row['ward_name'];
    $wardData[] = $row['total'];
}

/*INCIDENTS BY SERVICE*/
$serviceLabels = [];
$serviceData = [];

$sqlService = "SELECT ms.service_name, COUNT(i.incident_id) AS total
    FROM incident_ticket i
    JOIN municipal_servive ms
    ON i.service_id = ms.service_id
    GROUP BY ms.service_id, ms.service_name
    ORDER BY total DESC";

$resultService = mysqli_query($conn, $sqlService);

while ($row = mysqli_fetch_assoc($resultService)) {
    $serviceLabels[] = $row['service_name'];
    $serviceData[] = $row['total'];
}



$statusLabels = [];
$statusData = [];

$sqlStatus = "SELECT ts.status_name, COUNT(i.incident_id) AS total
    FROM incident_ticket i
    JOIN ticket_status ts
    ON i.status_id = ts.status_id
    GROUP BY ts.status_id, ts.status_name
    ORDER BY total DESC";

$resultStatus = mysqli_query($conn, $sqlStatus);
while ($row = mysqli_fetch_assoc($resultStatus)) {
    $statusLabels[] = $row['status_name'];
    $statusData[] = $row['total'];
}


/*PRIORITY*/
$priorityLabels = [];
$priorityData = [];
$sqlPriority = "SELECT priority, COUNT(*) AS total
    FROM incident_ticket
    GROUP BY priority";

$resultPriority = mysqli_query($conn, $sqlPriority);
while ($row = mysqli_fetch_assoc($resultPriority)) {
    $priorityLabels[] = $row['priority'];
    $priorityData[] = $row['total'];

}
/*INCIDENTS OVER TIME*/
$dateLabels = [];
$dateData = [];
$sqlDate = "SELECT DATE(date_reported) AS report_date,
    COUNT(*) AS total
    FROM incident_ticket
    GROUP BY DATE(date_reported)
    ORDER BY report_date";

$resultDate = mysqli_query($conn, $sqlDate);

while ($row = mysqli_fetch_assoc($resultDate)) {

    $dateLabels[] = $row['report_date'];

    $dateData[] = $row['total'];

}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>M-Link Dashboard</title>
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="dashboard.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>


<body>

<div class="dashboard">
    <!-- HEADER -->

    <div class="header">

        <h1>M-Link Dashboard</h1>

        <p>
            Municipal Incident Reporting & Analytics
        </p>

    </div>



    <!-- SUMMARY CARDS -->

    <div class="cards">


        <div class="card">

            <h3>Total Incidents</h3>

            <div class="number">

                <?php echo $total; ?>

            </div>

        </div>



        <div class="card">

            <h3>Pending</h3>

            <div class="number">

                <?php echo $pending; ?>

            </div>

        </div>



        <div class="card">

            <h3>In Progress</h3>

            <div class="number">

                <?php echo $progress; ?>

            </div>

        </div>



        <div class="card">

            <h3>Resolved</h3>

            <div class="number">

                <?php echo $resolved; ?>

            </div>

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


<!-- Dashboard JavaScript -->

<script>

    const wardLabels =
        <?php echo json_encode($wardLabels); ?>;

    const wardData =
        <?php echo json_encode($wardData); ?>;


    const serviceLabels =
        <?php echo json_encode($serviceLabels); ?>;

    const serviceData =
        <?php echo json_encode($serviceData); ?>;


    const statusLabels =
        <?php echo json_encode($statusLabels); ?>;

    const statusData =
        <?php echo json_encode($statusData); ?>;


    const priorityLabels =
        <?php echo json_encode($priorityLabels); ?>;

    const priorityData =
        <?php echo json_encode($priorityData); ?>;


    const dateLabels =
        <?php echo json_encode($dateLabels); ?>;

    const dateData =
        <?php echo json_encode($dateData); ?>;

</script>


<script src="dashboard.js"></script>


</body>

</html>