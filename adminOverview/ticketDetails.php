<?php

session_start();

require_once "Error404Connection.php";

//check if administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$ticket = null;

if (isset($_GET['ticketId'])) {

    $ticketId = intval($_GET['ticketId']);

    $sql = "SELECT incident_id, username, service_id, ward_id, address_id,
                   status_id, incident_description, location_description,
                   priority, date_reported, date_resolved, created_at
            FROM incident_ticket
            WHERE incident_id = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $ticketId);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $ticket = $result->fetch_assoc();

        }

        $stmt->close();

    }

}



// Only an administrator, or the person who reported the ticket, may open it.
// Anyone else is treated exactly like "ticket not found", so ticket numbers
// cannot be probed.
$viewerRole =
    strtolower(
        trim(
            (string)($_SESSION["user_role"] ?? $_SESSION["role"] ?? "")
        )
    );

if (
    $ticket !== null &&
    $viewerRole !== "admin" &&
    strcasecmp(
        (string)($ticket["username"] ?? ""),
        (string)$_SESSION["username"]
    ) !== 0
) {

    $ticket = null;

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-Link | Ticket Details</title>

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

        .page-heading {
            text-align: center;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 32px;
            font-weight: 600;
            color: #333;
        }

        .back-button {
            display: inline-block;
            background: #5f7022;
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .back-button:hover {
            background: #4b591a;
        }

        .ticket-panel {
            background: white;
            border: 1px solid #ddd7ca;
            border-radius: 7px;
            max-width: 900px;
            margin: 0 auto;
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e0d7;
        }

        .panel-header h3 {
            font-size: 18px;
            color: #333;
        }

        .ticket-details {
            padding: 25px;
        }

        .ticket-details h3 {
            color: #4b561b;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            padding: 13px 0;
            border-bottom: 1px solid #eeeae2;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            width: 180px;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            flex: 1;
            font-size: 13px;
            color: #444;
            line-height: 1.5;
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

        .status {
            display: inline-block;
            background: #e1ecd2;
            color: #4b681c;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .message {
            background: white;
            border: 1px solid #ddd7ca;
            padding: 40px 30px;
            margin: 20px auto;
            border-radius: 7px;
            max-width: 800px;
            text-align: center;
        }

        .message h3 {
            font-size: 19px;
            color: #444;
            margin-bottom: 20px;
        }

        .details-button {
            display: inline-block;
            background: #5f7022;
            color: white;
            padding: 9px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .details-button:hover {
            background: #4b591a;
        }

        @media (max-width: 800px) {

            .page-heading h1 {
                font-size: 28px;
            }

        }

        @media (max-width: 600px) {

            .page-heading h1 {
                font-size: 25px;
            }

            .ticket-details {
                padding: 18px;
            }

            .detail-row {
                display: block;
            }

            .detail-label {
                width: 100%;
                margin-bottom: 5px;
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
$adminActivePage = "tickets";
require __DIR__ . "/adminHeader.php";
?>
        

    <main class="dashboard-content">

        <div class="page-heading">

            <h1>
                Ticket Details
            </h1>

        </div>

        <a
            class="back-button"
            href="WardTicketManagement.php"
        >
            ← Back to Municipal Tickets
        </a>

        <?php if ($ticket !== null) { ?>

            <div class="ticket-panel">

                <div class="panel-header">

                    <h3>
                        Reported Fault Information
                    </h3>

                </div>

                <div class="ticket-details">

                    <h3>
                        Ticket #<?php echo htmlspecialchars($ticket['incident_id']); ?>
                    </h3>

                    <div class="detail-row">

                        <div class="detail-label">
                            USERNAME
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['username']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            SERVICE ID
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['service_id']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            WARD
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['ward_id']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            ADDRESS ID
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['address_id']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            INCIDENT
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['incident_description']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            LOCATION
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['location_description']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            PRIORITY
                        </div>

                        <div class="detail-value">

                            <span class="priority">
                                <?php echo htmlspecialchars($ticket['priority']); ?>
                            </span>

                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            STATUS
                        </div>

                        <div class="detail-value">

                            <span class="status">
                                <?php echo htmlspecialchars($ticket['status_id']); ?>
                            </span>

                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            DATE REPORTED
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['date_reported']); ?>
                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            DATE RESOLVED
                        </div>

                        <div class="detail-value">

                            <?php

                            if (!empty($ticket['date_resolved'])) {

                                echo htmlspecialchars($ticket['date_resolved']);

                            } else {

                                echo "Not resolved yet";

                            }

                            ?>

                        </div>

                    </div>

                    <div class="detail-row">

                        <div class="detail-label">
                            CREATED AT
                        </div>

                        <div class="detail-value">
                            <?php echo htmlspecialchars($ticket['created_at']); ?>
                        </div>

                    </div>

                </div>

            </div>

        <?php } else { ?>

            <div class="message">

                <h3>
                    No Ticket Found
                </h3>

                <a
                    class="details-button"
                    href="WardTicketManagement.php"
                >
                    Back to Municipal Tickets
                </a>

            </div>

        <?php } ?>

    </main>

    <?php require __DIR__ . "/adminFooter.php"; ?>

</body>

</html>

<?php

$conn->close();

?>