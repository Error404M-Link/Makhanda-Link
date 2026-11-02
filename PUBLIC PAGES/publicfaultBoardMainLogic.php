<?php
    require "../Error404Connection.php";
    require "faultDataHelper.php";

    // #################### PAGE BOOTSTRAP FOR PublicFaultBoard.php ####################
    // This file only prepares data for the initial page render. Live search/filter
    // requests from the browser are handled separately by faultFilterAjax.php so the
    // two responsibilities (render a page vs. answer a fetch()) don't get tangled.

    if (!defined('MAP_TICKET_LIMIT')) {
        // Cap the number of pins plotted on the map so it never gets cluttered.
        define('MAP_TICKET_LIMIT', 30);
    }

    // ---- Ticket set for the map & "Recent Faults" cards ----
    // Fields per row: incident_id, incident_description, location_description, priority,
    // date_reported, date_resolved, latitude, longitude, created_at, ward_id, category,
    // ward_name, ward_number, street_name, ticket_status, imagePath
    $ticketArray = fetchFaultTickets($conn, [], false, MAP_TICKET_LIMIT);

    // JSON payload consumed by initGrahamstownMap() in publicFaultBoard.js
    $jsonTickets = json_encode(mapTicketPayload($ticketArray));

    // ---- Category stat counters ----
    $counts = getFaultCategoryCounts($conn);

    // ---- Dropdown data for the search/filter bar ----
    $faultCategories = getFaultCategories($conn);
    $faultWards      = getFaultWards($conn);
?>