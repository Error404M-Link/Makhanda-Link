<?php
/**
 * ################################################################
 *  SHARED FAULT-BOARD HELPERS
 * ################################################################
 *  Included by: publicfaultBoardMainLogic.php, faultFilterAjax.php,
 *               allFaultTickets.php, viewTicket.php
 *
 *  Keeping this logic in one place means the map, the search/filter
 *  results, the "Recent Faults" cards and the All Faults grid can
 *  never drift out of sync with each other.
 * ################################################################
 */

// ----------------------------------------------------------------
// Turns the 3 datetime columns on ticket_status into one human label.
// ----------------------------------------------------------------
function faultStatusCase(): string
{
    return "CASE
                WHEN ts.resolved IS NOT NULL THEN 'Resolved'
                WHEN ts.InProgress IS NOT NULL THEN 'In Progress'
                WHEN ts.pending IS NOT NULL THEN 'Pending'
                ELSE 'Unknown'
            END";
}

// ----------------------------------------------------------------
// Normalises a raw municipal_service.service_type string into one
// of the 4 dashboard buckets. Case/whitespace tolerant so the stat
// counters don't silently fall to 0 over a casing mismatch.
// ----------------------------------------------------------------
function bucketCategory(?string $rawCategory): string
{
    $clean = strtolower(trim((string) $rawCategory));

    if ($clean === '') {
        return 'Other';
    }
    if (strpos($clean, 'pothole') !== false || strpos($clean, 'road') !== false || strpos($clean, 'street') !== false) {
        return 'Roads & Streets';
    }
    if (strpos($clean, 'water') !== false || strpos($clean, 'sewer') !== false || strpos($clean, 'leak') !== false) {
        return 'Water & Sewer';
    }
    if (strpos($clean, 'electric') !== false || strpos($clean, 'light') !== false || strpos($clean, 'power') !== false) {
        return 'Electricity & Lighting';
    }
    return 'Other';
}

// ----------------------------------------------------------------
// Builds & runs the main ticket query used everywhere a list/grid/map
// of tickets is needed. All filters are optional and parameterised.
//
// $filters keys: search, category, ward, status (all strings)
// $onlyUnresolved: true to exclude tickets with ts.resolved set
// $limit: null for no cap, or an int row cap
// ----------------------------------------------------------------
function fetchFaultTickets(mysqli $conn, array $filters = [], bool $onlyUnresolved = false, ?int $limit = null): array
{
    $statusExpr = faultStatusCase();

    $whereConditions = ['1=1'];
    $havingConditions = [];
    $params = [];
    $types = '';

    if ($onlyUnresolved) {
        $whereConditions[] = 'ts.resolved IS NULL';
    }

    $search = trim($filters['search'] ?? '');
    if ($search !== '') {
        $whereConditions[] = '(i.incident_description LIKE ? OR i.location_description LIKE ? OR ad.street_name LIKE ? OR ms.service_type LIKE ?)';
        $wild = "%{$search}%";
        array_push($params, $wild, $wild, $wild, $wild);
        $types .= 'ssss';
    }

    $category = trim($filters['category'] ?? '');
    if ($category !== '') {
        $whereConditions[] = 'ms.service_type = ?';
        $params[] = $category;
        $types .= 's';
    }

    $ward = trim($filters['ward'] ?? '');
    if ($ward !== '' && ctype_digit($ward)) {
        $whereConditions[] = 'i.ward_id = ?';
        $params[] = (int) $ward;
        $types .= 'i';
    }

    // status is a computed column, so it has to be filtered with HAVING, not WHERE
    $status = trim($filters['status'] ?? '');
    if ($status !== '') {
        $havingConditions[] = 'ticket_status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $sql = "SELECT
                i.incident_id,
                i.incident_description,
                i.location_description,
                i.priority,
                i.date_reported,
                i.date_resolved,
                i.latitude,
                i.longitude,
                i.created_at,
                i.ward_id,
                ms.service_type AS category,
                w.ward_name,
                w.ward_number,
                ad.street_name,
                ({$statusExpr}) AS ticket_status,
                (SELECT ti.image_path FROM ticket_image ti WHERE ti.incident_id = i.incident_id ORDER BY ti.image_id ASC LIMIT 1) AS imagePath
            FROM incident_ticket i
            LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
            LEFT JOIN ward w ON i.ward_id = w.ward_id
            LEFT JOIN address_directory ad ON i.address_id = ad.address_id
            LEFT JOIN ticket_status ts ON i.status_id = ts.status_id
            WHERE " . implode(' AND ', $whereConditions);

    if (!empty($havingConditions)) {
        $sql .= ' HAVING ' . implode(' AND ', $havingConditions);
    }

    $sql .= ' ORDER BY i.created_at DESC';

    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit; // cast, never string-concatenated from user input
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return [];
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

// ----------------------------------------------------------------
// The 4 dashboard stat counters.
// ----------------------------------------------------------------
function getFaultCategoryCounts(mysqli $conn): array
{
    $counts = [
        'Roads & Streets'        => 0,
        'Water & Sewer'          => 0,
        'Electricity & Lighting' => 0,
        'Other'                  => 0,
    ];

    $sql = "SELECT ms.service_type AS category, COUNT(*) AS total
            FROM incident_ticket i
            LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
            GROUP BY ms.service_type";

    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bucket = bucketCategory($row['category']);
            $counts[$bucket] += (int) $row['total'];
        }
    }

    return $counts;
}

// ----------------------------------------------------------------
// Dropdown data sources.
// ----------------------------------------------------------------
function getFaultCategories(mysqli $conn): array
{
    $categories = [];
    $result = $conn->query("SELECT DISTINCT service_type FROM municipal_service WHERE service_type IS NOT NULL AND service_type <> '' ORDER BY service_type ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['service_type'];
        }
    }
    return $categories;
}

function getFaultWards(mysqli $conn): array
{
    $wards = [];
    $result = $conn->query("SELECT ward_id, ward_name, ward_number FROM ward ORDER BY ward_number ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $wards[] = $row;
        }
    }
    return $wards;
}

// ----------------------------------------------------------------
// Renders one fault card. Shared by the "Recent Faults" section,
// the All Faults grid, and every AJAX filter response, so they can
// never visually drift apart.
// ----------------------------------------------------------------
function renderFaultCard(array $ticket): string
{
    $statusText   = $ticket['ticket_status'] ?? 'Unknown';
    $statusClass  = strtolower(str_replace(' ', '-', $statusText));
    $categoryText = $ticket['category'] ?? 'General Incident';
    $imageUrl     = !empty($ticket['imagePath']) ? htmlspecialchars($ticket['imagePath']) : 'MakanaLogo.svg';
    $locationText = $ticket['street_name'] ?? ($ticket['location_description'] ?? 'N/A');

    ob_start();
    ?>
    <div class="faultCard" data-id="<?php echo (int) $ticket['incident_id']; ?>">
        <div class="cardImageWrapper" style="background-image: url('<?php echo $imageUrl; ?>');">
            <span class="statusBadge <?php echo htmlspecialchars($statusClass); ?>">
                <?php echo htmlspecialchars($statusText); ?>
            </span>
        </div>

        <div class="cardContent">
            <h3><?php echo htmlspecialchars($categoryText); ?></h3>

            <p class="description" style="margin: 5px 0; font-weight: 500;">
                <?php echo htmlspecialchars($ticket['incident_description'] ?? ''); ?>
            </p>

            <div class="location" style="color: #666; font-size: 0.9rem;">
                Location: <?php echo htmlspecialchars($locationText); ?>
                <?php if (!empty($ticket['ward_number'])): ?>
                    (Ward <?php echo (int) $ticket['ward_number']; ?>)
                <?php endif; ?>
            </div>

            <button type="button" class="viewBtn viewDetailsBtn" data-id="<?php echo (int) $ticket['incident_id']; ?>">
                View Details
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ----------------------------------------------------------------
// Slims a ticket row down to just what the Leaflet map needs.
// ----------------------------------------------------------------
function mapTicketPayload(array $tickets): array
{
    return array_map(function ($t) {
        return [
            'ticketId'   => $t['incident_id'],
            'category'   => $t['category'] ?? 'General Incident',
            'streetName' => $t['street_name'] ?? ($t['location_description'] ?? 'N/A'),
            'status'     => $t['ticket_status'] ?? 'Unknown',
            'latitude'   => $t['latitude'],
            'longitude'  => $t['longitude'],
        ];
    }, $tickets);
}