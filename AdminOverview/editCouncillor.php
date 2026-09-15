<?php

include "Error404Connection.php";

$username = $_GET['username'] ?? '';

if (empty($username)) {
    die("No username supplied.");
}

$stmt = $conn->prepare("
    SELECT username,
           ward_id,
           date_appointed,
           contact
    FROM ward_councillor
    WHERE username = ?
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Councillor not found.");
}

$row = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Councillor</title>

    <link rel="stylesheet" href="editCouncillor.css">

</head>


<body>


<div class="contentWorkspace">

    <section class="tableContainer">

        <h1>Edit Councillor</h1>


        <form method="POST" action="assignedCouncillor.php">


            <!-- ACTION -->

            <input
                type="hidden"
                name="action"
                value="update"
            >


            <!-- USERNAME -->

            <input
                type="hidden"
                name="username"
                value="<?= htmlspecialchars($row['username']) ?>"
            >


            <label>Username</label>

            <input
                type="email"
                value="<?= htmlspecialchars($row['username']) ?>"
                readonly
            >


            <br><br>


            <!-- WARD -->

            <label>Ward ID</label>

            <input
                type="number"
                name="ward_id"
                value="<?= htmlspecialchars($row['ward_id'] ?? '') ?>"
                required
            >


            <br><br>


            <!-- DATE -->

            <label>Date Appointed</label>

            <input
                type="date"
                name="date_appointed"
                value="<?= htmlspecialchars($row['date_appointed'] ?? '') ?>"
            >


            <br><br>


            <!-- CONTACT -->

            <label>Contact</label>

            <input
                type="text"
                name="contact"
                value="<?= htmlspecialchars($row['contact'] ?? '') ?>"
                required
            >


            <br><br>


            <!-- BUTTONS -->

            <button type="submit">
                Update Councillor
            </button>


            <a href="assignedCouncillor.php">
                Cancel
            </a>


        </form>

    </section>

</div>

</body>

</html>