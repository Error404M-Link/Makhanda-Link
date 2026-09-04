
<?php


include "dbconnections.php";

// SQL query to retrieve all ward tickets
$sql = "SELECT ticketId, wardId, category, locationDescription,
               latitude, longitude, description, status,
               imagePath, createdAt
        FROM faultTickets
        ORDER BY createdAt DESC";

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

        img {
            width: 100px;
            height: auto;
            border-radius: 5px;
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

        // Check whether tickets were found
        if (mysqli_num_rows($result) > 0) {

            // Display each ticket
            while ($row = mysqli_fetch_assoc($result)) {

                echo "<tr>";

                echo "<td>" .
                     htmlspecialchars($row['ticketId']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['wardId']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['category']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['locationDescription']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['latitude']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['longitude']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['description']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['status']) .
                     "</td>";

                // Display image if available
                echo "<td>";

                if (!empty($row['imagePath'])) {

                    echo "<img src='" .
                         htmlspecialchars($row['imagePath']) .
                         "' alt='Ticket Image'>";

                } else {

                    echo "No Image";

                }

                echo "</td>";

                echo "<td>" .
                     htmlspecialchars($row['createdAt']) .
                     "</td>";

                // View ticket details
                echo "<td>";

                echo "<a class='view-btn'
                         href='ticketDetails.php?ticketId=" .
                         urlencode($row['ticketId']) .
                         "'>
                         View
                      </a>";

                echo "</td>";

                echo "</tr>";
            }

        } else {

            echo "<tr>";

            echo "<td colspan='11' class='no-tickets'>";
            echo "No tickets found.";
            echo "</td>";

            echo "</tr>";
        }

        ?>

    </table>

</body>

</html>
