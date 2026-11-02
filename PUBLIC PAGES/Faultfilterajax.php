<?php
    // #################### AJAX ENDPOINT: LIVE SEARCH / FILTER ####################
    // Called by publicFaultBoard.js. Returns JSON containing both the rendered
    // card/list markup (for #faultResultsContainer) and a slim ticket list for
    // re-plotting the Leaflet map, so the two stay in sync after every filter.

    require "../Error404Connection.php";
    require "faultDataHelper.php";

    header('Content-Type: application/json');

    // allFaultTickets.php sends unresolvedOnly=1 so its grid only ever shows open faults
    $onlyUnresolved = isset($_GET['unresolvedOnly']) && $_GET['unresolvedOnly'] === '1';

    // The 30-pin map cap only applies where the caller asks for it (PublicFaultBoard.php).
    // allFaultTickets.php sends no "limit" param, so a filtered search there still returns
    // every match, matching its "see everything" purpose.
    $limit = (isset($_GET['limit']) && ctype_digit($_GET['limit'])) ? (int) $_GET['limit'] : null;

    $filters = [
        'search'   => $_GET['search']   ?? '',
        'category' => $_GET['category'] ?? '',
        'status'   => $_GET['status']   ?? '',
        'ward'     => $_GET['ward']     ?? '',
    ];

    $tickets = fetchFaultTickets($conn, $filters, $onlyUnresolved, $limit);

    $cardsHtml = '';
    if (!empty($tickets)) {
        foreach ($tickets as $ticket) {
            $cardsHtml .= renderFaultCard($ticket);
        }
    } else {
        $cardsHtml = '<p style="grid-column: 1/-1; text-align:center; color:#666; padding:40px;">No faults match your search.</p>';
    }

    echo json_encode([
        'html'    => $cardsHtml,
        'tickets' => mapTicketPayload($tickets),
        'count'   => count($tickets),
    ]);

    $conn->close();