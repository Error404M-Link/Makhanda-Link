<?php
    // #################### VIEW TICKET (AJAX POPUP DATA) ####################
    // Called by publicFaultBoard.js when a "View Details" button is clicked.
    // Returns a single ticket's full detail, plus every attached image, as JSON.

    require "../Error404Connection.php";
    require "faultDataHelper.php";

    header('Content-Type: application/json');

    $incidentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($incidentId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid ticket id is required.']);
        exit;
    }

    $statusExpr = faultStatusCase();

    $sql = "SELECT
                i.incident_id,
                i.incident_description,
                i.location_description,
                i.priority,
                i.date_reported,
                i.date_resolved,
                i.latitude,
                i.longitude,
                ms.service_type AS category,
                w.ward_name,
                w.ward_number,
                ad.street_name,
                ad.postal_code,
                ts.pending,
                ts.InProgress,
                ts.resolved,
                ({$statusExpr}) AS ticket_status
            FROM incident_ticket i
            LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
            LEFT JOIN ward w ON i.ward_id = w.ward_id
            LEFT JOIN address_directory ad ON i.address_id = ad.address_id
            LEFT JOIN ticket_status ts ON i.status_id = ts.status_id
            WHERE i.incident_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found.']);
        exit;
    }

    // Pull every image attached to this ticket
    $images = [];
    $imgStmt = $conn->prepare("SELECT image_path FROM ticket_image WHERE incident_id = ? ORDER BY image_id ASC");
    $imgStmt->bind_param('i', $incidentId);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    while ($row = $imgResult->fetch_assoc()) {
        $images[] = $row['image_path'];
    }
    $imgStmt->close();

    $ticket['images'] = $images;

    echo json_encode($ticket);

    $conn->close();