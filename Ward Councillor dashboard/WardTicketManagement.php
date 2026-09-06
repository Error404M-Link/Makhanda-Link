<?php

include "Error404Connection.php";

/* SQL query to retrieve all incident tickets */

$sql = "SELECT incident_id,
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
        ORDER BY created_at DESC";

/* Execute the SQL query */

$result = $conn->query($sql);

/* Check for SQL errors */

if (!$result) {
    die("Error retrieving tickets: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ward Ticket Management</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7df;
            margin: 20px;
        }

        h1 {
            text-align: center;
            color: #1b442b;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th {
            background-color: #1b442b;
            color: white;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .view-btn {
            display: inline-block;
            background-color: #239e56;
            color: white;
            padding: 7px 12px;
            border-radius: 4px;
            text-decoration: none;
        }

        .view-btn:hover {
            background-color: #236833;
        }

        .no-tickets {
            text-align: center;
            padding: 20px;
            color: #777;
        }

    </style>

</head>

<body>

    <h1>Ward Ticket Management</h1>

    <table>

        <tr>

            <th>Incident ID</th>

            <th>Ward ID</th>

            <th>Service ID</th>

            <th>Location</th>

            <th>Latitude</th>

            <th>Longitude</th>

            <th>Description</th>

            <th>Priority</th>

            <th>Status</th>

            <th>Date Reported</th>

            <th>Action</th>

        </tr>


        <?php

        /* Check whether tickets were found */

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

        ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($row['incident_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['ward_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['service_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['location_description']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['latitude']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['longitude']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['incident_description']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['priority']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['status_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['date_reported']); ?>
                    </td>

                    <td>

                        <a class="view-btn"
                           href="ticketDetails.php?ticketId=<?php echo urlencode($row['incident_id']); ?>">

                            View

                        </a>

                    </td>

                </tr>

        <?php

            }

        } else {

        ?>

            <tr>

                <td colspan="11" class="no-tickets">

                    No tickets found.

                </td>

            </tr>

        <?php

        }

        $conn->close();

        ?>

    </table>

</body>

</html>