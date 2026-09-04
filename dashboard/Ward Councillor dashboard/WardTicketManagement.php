<?php

include "dbconnection.php";

$sql = "SELECT ticketId, wardId, category, locationDescription,
               latitude, longitude, description, status,
               imagePath, createdAt
        FROM faultTickets
        ORDER BY createdAt DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error retrieving tickets: " . mysqli_error($conn));
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
            background color: green;
            margin: 20px;
        }

        h1 {
            text-align: center;
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

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        img {
            width: 100px;
            height: auto;
        }

        .view-button {
            background-color: #239e56;
            color: white;
            padding: 7px 12px;
            border-radius: 4px;
        }

        .view-button:hover {
            background-color: #236833;
        }

    </style>

</head>

<body>

    <h1>Ward Ticket Management</h1>

    <table>

        <tr>
            <th>Ticket ID</th>
            <th>Ward ID</th>
            <th>Category</th>
            <th>Location</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Description</th>
            <th>Status</th>
            <th>Image</th>
            <th>Date Created</th>
            <th>Action</th>
        </tr>

        <?php

        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

                echo "<tr>";

                echo "<td>" . htmlspecialchars($row['ticketId']) . "</td>";

                echo "<td>" . htmlspecialchars($row['wardId']) . "</td>";

                echo "<td>" . htmlspecialchars($row['category']) . "</td>";

                echo "<td>" . htmlspecialchars($row['locationDescription']) . "</td>";

                echo "<td>" . htmlspecialchars($row['latitude']) . "</td>";

                echo "<td>" . htmlspecialchars($row['longitude']) . "</td>";

                echo "<td>" . htmlspecialchars($row['description']) . "</td>";

                echo "<td>" . htmlspecialchars($row['status']) . "</td>";

                echo "<td>";

                if (!empty($row['imagePath'])) {

                    echo "<img src='" . htmlspecialchars($row['imagePath']) . "' alt='Ticket Image'>";

                } else {

                    echo "No Image";

                }

                echo "</td>";

                echo "<td>" . htmlspecialchars($row['createdAt']) . "</td>";

                echo "<td>
                        <a class='view-btn' href='ticketDetails.php?ticketId=" 
                        . urlencode($row['ticketId']) . "'>
                        View
                        </a>
                      </td>";

                echo "</tr>";

            }

        } else {

            echo "<tr>";

            echo "<td colspan='11'>No tickets found.</td>";

            echo "</tr>";

        }

        ?>

    </table>

</body>

</html>