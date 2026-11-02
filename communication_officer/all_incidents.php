<?php
session_start();
//check if user is logged in
if(!isset($_SESSION["username"])){
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}
require_once "Error404Connection.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$sql = "SELECT i.incident_id, ms.service_type, w.ward_name, i.incident_description, i.priority, i.date_reported
    FROM incident_ticket i
    LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
    LEFT JOIN ward w ON i.ward_id = w.ward_id
    ORDER BY i.date_reported DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Incidents - System-Wide Ticket Tracking</title>
    <link rel="stylesheet" href="all_incidents.css">

    <!-- GOOGLE FONTS & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,700;1,700&family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    body, input, select, button, textarea, table, th, td {
        font-family: 'Ubuntu', sans-serif;
        font-weight: 400;
    }

    h1, h2, h3, h4, h5, h6, .main-heading {
        font-family: 'Lora', serif;
        font-weight: 700;
    }

    .sub-heading, 
    .backBtn, 
    .btn-view, 
    .priority-cell, 
    th {
        font-family: 'Ubuntu', sans-serif;
        font-weight: 700;
    }

    /* Usability Enhancements Styles */
    .search-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 1.25rem;
    }

    .search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-input-wrapper input {
        width: 100%;
        padding: 10px 80px 10px 14px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 0.95rem;
    }

    .search-shortcut-badge {
        position: absolute;
        right: 45px;
        background: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 0.75rem;
        color: #6c757d;
        pointer-events: none;
    }

    .clear-search-btn {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        color: #888;
        cursor: pointer;
        font-size: 1.1rem;
        display: none;
        padding: 4px;
    }

    .clear-search-btn:hover {
        color: #333;
    }

    .quick-filter-chips {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .chip {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        padding: 5px 14px;
        border-radius: 16px;
        font-size: 0.825rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .chip:hover, .chip.active {
        background: #0056b3;
        color: #fff;
        border-color: #0056b3;
    }

    .search-status-feedback {
        font-size: 0.85rem;
        color: #555;
        font-style: italic;
    }

    .sortable-header {
        cursor: pointer;
        user-select: none;
    }

    .sortable-header:hover {
        background-color: #f1f3f5;
    }

    .sort-icon {
        font-size: 0.8rem;
        margin-left: 4px;
        opacity: 0.5;
    }
</style>
</head>
<body>

    <div class="header-navigation">
        <a href="javascript:history.back()" class="backBtn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="tracker-container">
        <div class="tracker-header">
            <div>
                <span class="sub-heading">SYSTEM-WIDE TICKET TRACKING</span>
                <h1 class="main-heading">All Incidents Tickets</h1>
            </div>
        </div>

        <!-- Flexibility, Freedom & System Status Search Control -->
        <div class="search-container">
            <div class="search-input-wrapper">
                <input type="text" id="ticketSearch" placeholder="Search incident tickets..." aria-label="Search incident tickets">
                <span class="search-shortcut-badge" id="shortcutBadge">/</span>
                <button type="button" class="clear-search-btn" id="clearSearchBtn" title="Clear Search (Esc)" aria-label="Clear Search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Priority Quick Filters: Urgent, Normal, Low -->
            <div class="quick-filter-chips">
                <span style="font-size:0.825rem; color:#666;">Quick Filters:</span>
                <button type="button" class="chip active" data-filter="">All</button>
                <button type="button" class="chip" data-filter="urgent">Urgent</button>
                <button type="button" class="chip" data-filter="normal">Normal</button>
                <button type="button" class="chip" data-filter="low">Low</button>
            </div>

            <!-- Live Feedback Status -->
            <div id="searchFeedback" class="search-status-feedback" aria-live="polite"></div>
        </div>

        <div class="table-wrapper">
            <table class="tickets-table" id="ticketsTable">
                <thead>
                    <tr>
                        <th class="sortable-header" data-sort="0">Incident ID <i class="fa-solid fa-sort sort-icon"></i></th>
                        <th class="sortable-header" data-sort="1">Service <i class="fa-solid fa-sort sort-icon"></i></th>
                        <th class="sortable-header" data-sort="2">Ward <i class="fa-solid fa-sort sort-icon"></i></th>
                        <th>Incident Description</th>
                        <th class="sortable-header" data-sort="4">Priority <i class="fa-solid fa-sort sort-icon"></i></th>
                        <th class="sortable-header" data-sort="5">Date Reported <i class="fa-solid fa-sort sort-icon"></i></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result &&$result->num_rows > 0): ?>
                        <?php while ($row =$result->fetch_assoc()): ?>
                            <tr class="incident-row">
                                <td class="ticket-id"><?= e($row['incident_id']) ?></td>
                                <td><?= e($row['service_type'] ?? 'N/A') ?></td>
                                <td><?= e($row['ward_name'] ?? 'N/A') ?></td>
                                <td class="description-cell"><?= e($row['incident_description']) ?></td>
                                <td class="priority-cell">
                                    <span class="priority-badge priority-<?= strtolower(e($row['priority'] ?? 'normal')) ?>">
                                        <?= e($row['priority'] ?? 'Normal') ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <?= !empty($row['date_reported']) 
                                        ? e((new DateTime($row['date_reported']))->format('d M Y, H:i')) 
                                        : 'N/A' ?>
                                </td>
                                <td>
                                    <a href="view_incident.php?id=<?= e($row['incident_id']) ?>" class="btn-view">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr id="noDataRow">
                            <td colspan="7" class="no-records">No incident tickets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const input = document.getElementById('ticketSearch');
            const searchFeedback = document.getElementById('searchFeedback');
            const clearSearchBtn = document.getElementById('clearSearchBtn');
            const shortcutBadge = document.getElementById('shortcutBadge');
            const filterChips = document.querySelectorAll('.quick-filter-chips .chip');
            const rows = document.querySelectorAll('#ticketsTable tbody tr.incident-row');

            let activeChipFilter = "";

            function filterTickets() {
                const query = input.value.toLowerCase().trim();
                let matchCount = 0;

                // Toggle Clear Button & Shortcut Badge Visibility
                if (query.length > 0) {
                    clearSearchBtn.style.display = "block";
                    shortcutBadge.style.display = "none";
                } else {
                    clearSearchBtn.style.display = "none";
                    shortcutBadge.style.display = "block";
                }

                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const matchesQuery = rowText.includes(query);

                    let matchesChip = true;
                    if (activeChipFilter !== "") {
                        const priorityText = row.querySelector('.priority-cell')?.textContent.toLowerCase() || "";
                        matchesChip = priorityText.includes(activeChipFilter);
                    }

                    if (matchesQuery && matchesChip) {
                        row.style.display = "";
                        matchCount++;
                    } else {
                        row.style.display = "none";
                    }
                });

                // Visibility of System Status Feedback
                if (query.length > 0 || activeChipFilter !== "") {
                    const chipContext = activeChipFilter ? ` [Priority: ${activeChipFilter.toUpperCase()}]` : "";
                    searchFeedback.textContent = `Showing ${matchCount} matching ticket(s)${chipContext}.`;
                } else {
                    searchFeedback.textContent = "";
                }
            }

            // Search Event
            if (input) {
                input.addEventListener('input', filterTickets);
            }

            // User Freedom: One-click clear button
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    input.value = "";
                    filterTickets();
                    input.focus();
                });
            }

            // Flexibility: Keyboard Shortcuts ('/' to focus, 'Esc' to clear)
            document.addEventListener('keydown', function(e) {
                if (e.key === '/' && document.activeElement !== input) {
                    e.preventDefault();
                    input.focus();
                } else if (e.key === 'Escape') {
                    if (input.value !== "") {
                        input.value = "";
                        filterTickets();
                    } else {
                        input.blur();
                    }
                }
            });

            // Flexibility: Quick Filter Chips
            filterChips.forEach(chip => {
                chip.addEventListener('click', function() {
                    filterChips.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    activeChipFilter = this.getAttribute('data-filter');
                    filterTickets();
                });
            });

            // Flexibility: Interactive Column Sorting
            const headers = document.querySelectorAll('.sortable-header');
            let sortAsc = true;

            headers.forEach(header => {
                header.addEventListener('click', function() {
                    const colIndex = parseInt(this.getAttribute('data-sort'));
                    const tbody = document.querySelector('#ticketsTable tbody');
                    const rowArray = Array.from(rows);

                    rowArray.sort((a, b) => {
                        const cellA = a.children[colIndex].textContent.trim().toLowerCase();
                        const cellB = b.children[colIndex].textContent.trim().toLowerCase();

                        return sortAsc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                    });

                    sortAsc = !sortAsc;
                    rowArray.forEach(row => tbody.appendChild(row));
                });
            });
        });
    </script>
</body>
</html>
<script src="Global dashboard layout.js"></script>