<?php

include 'Error404Connection.php';

$sql = "SELECT incident_id, ward_id, priority, location_description,
             status_id, created_at
        FROM incident_ticket
        ORDER BY created_at DESC";

$result = $conn->query($sql);

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

    <a class="back-button" href="residentDashboard.php">
        Back to Dashboard
    </a>

    <br><br>

    <?php

    if ($result && $result->num_rows > 0) {

        while ($ticket = $result->fetch_assoc()) {

    ?>

        <div class="ticket-card">

            <h3>
                Ticket #<?php echo $ticket['ticketId']; ?>
            </h3>

            <p>
                <strong>WARD:</strong>
                <?php echo $ticket['wardId']; ?>
            </p>

            <p>
                <strong>CATEGORY:</strong>
                <?php echo $ticket['category']; ?>
            </p>

            <p>
                <strong>LOCATION:</strong>
                <?php echo $ticket['locationDescription']; ?>
            </p>

            <p>
                <strong>COORDINATES:</strong>
                <?php echo $ticket['latitude'] . ", " . $ticket['longitude']; ?>
            </p>

            <p>
                <strong>DESCRIPTION:</strong>
                <?php echo $ticket['description']; ?>
            </p>

            <p>
                <strong>STATUS:</strong>
                <?php echo $ticket['status']; ?>
            </p>

            <p>
                <strong>DATE REPORTED:</strong>
                <?php echo $ticket['createdAt']; ?>
            </p>

            <?php if (!empty($ticket['imagePath'])) { ?>

                <p>
                    <strong>ATTACHMENT:</strong>

                    <a href="<?php echo $ticket['imagePath']; ?>" target="_blank">
                        View Photo
                    </a>
                </p>

            <?php } ?>

            <a href="ticketDetails.php?ticketId=<?php echo $ticket['ticketId']; ?>">
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