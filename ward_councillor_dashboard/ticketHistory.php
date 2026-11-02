<?php

session_start();

require_once 'Error404Connection.php';

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "ward_councillor") {
    header("Location: PUBLIC PAGES/login.php");
    exit;
}

$ward_id = isset($_SESSION["ward_id"]) ? (int) $_SESSION["ward_id"] : 0;

$sql = "SELECT incident_id, ward_id, priority, location_description, incident_description,
               status_id, created_at
        FROM incident_ticket
        WHERE ward_id = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ward_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error retrieving tickets: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ticket History</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: darkgreen;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: white;
            text-align: center;
        }

        .heading {
            color: white;
            text-align: center;
        }

        .back-button {
            color: white;
        }

        .ticket-card {
            background-color: white;
            padding: 30px;
            margin: 20px auto;
            border-radius: 10px;
            max-width: 800px;
        }

    </style>

</head>

<body>

    <h1>Ticket History</h1>

    <p class="heading">REPORTED FAULTS</p>

    <a class="back-button" href="wardcouncilorDashboard.php">
        Back to Dashboard
    </a>

    <br><br>

    <?php

    if ($result && $result->num_rows > 0) {

        while ($ticket = $result->fetch_assoc()) {

    ?>

        <div class="ticket-card">

            <h3>
                Ticket #<?php echo $ticket['incident_id']; ?>
            </h3>

            <p>
                <strong>WARD:</strong>
                <?php echo $ticket['ward_id']; ?>
            </p>

            <p>
                <strong>LOCATION:</strong>
                <?php echo $ticket['location_description']; ?>
            </p>

            <p>
                <strong>DESCRIPTION:</strong>
                <?php echo $ticket['incident_description']; ?>
            </p>

            <p>
                <strong>STATUS:</strong>
                <?php echo $ticket['status_id']; ?>
            </p>

            <p>
                <strong>DATE REPORTED:</strong>
                <?php echo $ticket['created_at']; ?>
            </p>

            <?php if (!empty($ticket['imagePath'])) { ?>

                <p>
                    <strong>ATTACHMENT:</strong>

                    <a href="<?php echo $ticket['imagePath']; ?>" target="_blank">
                        View Photo
                    </a>
                </p>

            <?php } ?>

            <a href="ticketDetails.php?incident_id=<?php echo $ticket['incident_id']; ?>">
                View Details
            </a>

        </div>

    <?php

        }

    } else {

        echo "<div class='ticket-card'>";
        echo "<p>No reported tickets found.</p>";
        echo "</div>";

    }

    $conn->close();

    ?>

</body>

</html>