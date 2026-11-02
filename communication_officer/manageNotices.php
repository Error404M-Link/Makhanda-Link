<?php

session_start();
//check if user is logged in
if(!isset($_SESSION["username"])){
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}

require_once "../Error404Connection.php";


//check if user is logged in
if (!isset($_SESSION["username"])) {

    die("Communications Officer is not logged in.");
}


$username = $_SESSION["username"];


//check user role
$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);


if (
    $userRole !== "municipal_staff" &&
    $userRole !== "communications_officer"
) {

    die("Access denied.");
}


$message = "";
$messageType = "";
$showApprovalModal = false;


// =====================================================
// GET WARDS
// =====================================================

$wards = [];

$wardResult = $conn->query("
    SELECT
        ward_id,
        ward_number,
        ward_name
    FROM ward
    ORDER BY ward_number ASC
");


if ($wardResult) {

    while ($row = $wardResult->fetch_assoc()) {

        $wards[] = $row;
    }
}


// =====================================================
// GET MUNICIPAL SERVICES
// =====================================================

$services = [];

$serviceResult = $conn->query("
    SELECT DISTINCT service_type
    FROM municipal_service
    WHERE service_type IS NOT NULL
    AND TRIM(service_type) <> ''
    ORDER BY service_type ASC
");


if (!$serviceResult) {

    die(
        "Unable to load municipal services: "
        . $conn->error
    );
}


while ($row = $serviceResult->fetch_assoc()) {

    $services[] = $row["service_type"];
}


// =====================================================
// ADD NOTICE
// =====================================================

if (isset($_POST["add_notice"])) {

    $noticeTitle =
        trim(
            $_POST["notice_title"]
            ?? ""
        );


    $noticeCategory =
        trim(
            $_POST["notice_category"]
            ?? ""
        );


    $noticeDescription =
        trim(
            $_POST["notice_description"]
            ?? ""
        );


    $publishDate =
        $_POST["publish_date"]
        ?? "";


    $expiryDate =
        $_POST["expiry_date"]
        ?? "";


    $communityWide =
        isset(
            $_POST["community_wide"]
        );


    $selectedWards =
        $_POST["wards"]
        ?? [];


    //validate fields
    if (
        $noticeTitle === "" ||
        $noticeCategory === "" ||
        $noticeDescription === "" ||
        $publishDate === "" ||
        $expiryDate === ""
    ) {

        $message =
            "Please complete all required fields.";

        $messageType =
            "error";

    } elseif (
        !in_array(
            $noticeCategory,
            $services,
            true
        )
    ) {

        $message =
            "Please select a valid service.";

        $messageType =
            "error";

    } elseif (
        strtotime($expiryDate)
        <=
        strtotime($publishDate)
    ) {

        $message =
            "The expiry date must be after the publish date.";

        $messageType =
            "error";

    } elseif (
        !$communityWide &&
        empty($selectedWards)
    ) {

        $message =
            "Please select at least one ward.";

        $messageType =
            "error";

    } else {

        $conn->begin_transaction();


        try {

            //communications officer notices start as Pending
            $stmt =
                $conn->prepare("
                    INSERT INTO notices
                    (
                        username,
                        notice_title,
                        notice_category,
                        publish_date,
                        expiry_date,
                        status,
                        notice_description,
                        date_posted,
                        deleted_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending',
                        ?,
                        NOW(),
                        NULL
                    )
                ");


            if (!$stmt) {

                throw new Exception(
                    $conn->error
                );
            }


            $stmt->bind_param(
                "ssssss",
                $username,
                $noticeTitle,
                $noticeCategory,
                $publishDate,
                $expiryDate,
                $noticeDescription
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    $stmt->error
                );
            }


            $noticeId =
                $conn->insert_id;


            $stmt->close();


            //save ward links
            //no ward links means Community-wide
            if (!$communityWide) {

                $wardStmt =
                    $conn->prepare("
                        INSERT INTO notice_ward
                        (
                            notice_id,
                            ward_id
                        )
                        VALUES (?, ?)
                    ");


                if (!$wardStmt) {

                    throw new Exception(
                        $conn->error
                    );
                }


                foreach (
                    $selectedWards
                    as $wardId
                ) {

                    $wardId =
                        intval($wardId);


                    if ($wardId <= 0) {

                        continue;
                    }


                    $wardStmt->bind_param(
                        "ii",
                        $noticeId,
                        $wardId
                    );


                    if (!$wardStmt->execute()) {

                        throw new Exception(
                            $wardStmt->error
                        );
                    }
                }


                $wardStmt->close();
            }


            $conn->commit();


            $message =
                "Notice submitted for administrator approval.";

            $messageType =
                "success";
                
            $showApprovalModal = true;


        } catch (Throwable $error) {

            $conn->rollback();


            $message =
                "Unable to submit notice: "
                . $error->getMessage();

            $messageType =
                "error";
        }
    }
}


// =====================================================
// GET COMMUNICATIONS OFFICER NOTICES
// =====================================================

$stmt =$conn->prepare("
        SELECT

            n.notice_id,
            n.username,
            n.notice_title,
            n.notice_category,
            n.publish_date,
            n.expiry_date,
            n.status,
            n.notice_description,
            n.date_posted,

            GROUP_CONCAT(
                DISTINCT CONCAT(
                    'Ward ',
                    w.ward_number
                )
                ORDER BY w.ward_number ASC
                SEPARATOR ', '
            ) AS ward_list,

            COUNT(
                DISTINCT nw.ward_id
            ) AS ward_count

        FROM notices n

        LEFT JOIN notice_ward nw
            ON n.notice_id = nw.notice_id

        LEFT JOIN ward w
            ON nw.ward_id = w.ward_id

        WHERE n.username = ?
        AND n.deleted_at IS NULL

        GROUP BY

            n.notice_id,
            n.username,
            n.notice_title,
            n.notice_category,
            n.publish_date,
            n.expiry_date,
            n.status,
            n.notice_description,
            n.date_posted

        ORDER BY n.date_posted DESC
    ");


if (!$stmt) {

    die(
        "Unable to load notices: "
        . $conn->error
    );
}


$stmt->bind_param(
    "s",
    $username
);


$stmt->execute();

$result =$stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    M-LINK | Municipal Notices
</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


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


.page {
    width: 100%;
    min-height: 100vh;
}


.topbar {
    position: relative;
    min-height: 76px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
}

.backBtn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background-color: #ffffff;
    color: #1b442b;
    border: 1px solid #e2e0d8;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

.backBtn:hover {
    background-color: #1b442b;
    color: #ffffff;
    border-color: #1b442b;
}


.topbar-left {
    display: flex;
    align-items: center;
    z-index: 2;
}


.back-dashboard {
    min-height: 40px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #5f7022;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}


.back-dashboard:hover {
    background: #4b591a;
}


.topbar-title {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}


.topbar-title h1 {
    font-size: 26px;
    color: #263238;
}


.user {
    font-size: 14px;
    font-weight: 600;
    color: #4b561b;
    z-index: 2;
}


.container {
    width: calc(100% - 70px);
    margin: 20px auto 35px;
}


.message {
    padding: 13px 16px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-size: 14px;
}


.message.success {
    background: #edf3df;
    color: #4b561b;
    border: 1px solid #c8d5a8;
}


.message.error {
    background: #f1dfda;
    color: #a22d1d;
    border: 1px solid #e2c1ba;
}


.form-container {
    background: white;
    padding: 25px;
    border: 1px solid #ddd7ca;
    border-radius: 7px;
    margin-bottom: 25px;
}


.form-container h3 {
    margin-bottom: 22px;
    font-size: 18px;
}


.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}


.form-group {
    display: flex;
    flex-direction: column;
}


.form-group.full {
    grid-column: 1 / -1;
}


.form-group label {
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
}


.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d7dbe0;
    border-radius: 5px;
    background: #f7f8fa;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 13px;
}


.form-group textarea {
    min-height: 130px;
    resize: vertical;
}


.ward-selection {
    border: 1px solid #d7dbe0;
    border-radius: 5px;
    background: #f7f8fa;
    padding: 15px;
}


.community-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid #ddd;
    font-weight: 600;
}


.community-option input {
    width: auto;
}


.ward-checkboxes {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(100px, 1fr));
    gap: 10px;
}


.ward-option {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
}


.ward-option input {
    width: auto;
}


.form-buttons {
    margin-top: 20px;
}


.button {
    min-height: 38px;
    padding: 0 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}


.button-primary {
    background: #5f7022;
    color: white;
}


.button-primary:hover {
    background: #4b591a;
}


.table-container {
    background: white;
    border: 1px solid #ddd7ca;
    border-radius: 7px;
    overflow-x: auto;
}


.table-container h3 {
    padding: 22px 25px;
    border-bottom: 1px solid #e5e0d7;
    font-size: 18px;
}


/* Search and Usability Styles */
.search-controls {
    padding: 18px 25px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #faf8f5;
    border-bottom: 1px solid #e5e0d7;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input-wrapper input {
    width: 100%;
    padding: 10px 80px 10px 14px;
    border: 1px solid #d7dbe0;
    border-radius: 5px;
    font-size: 13px;
    background: #fff;
}

.search-shortcut-badge {
    position: absolute;
    right: 42px;
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
    background: #eef0f2;
    border: 1px solid #d0d5dd;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chip:hover, .chip.active {
    background: #5f7022;
    color: #fff;
    border-color: #5f7022;
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
    background-color: #eee9dd;
}

.sort-icon {
    font-size: 0.75rem;
    margin-left: 4px;
    opacity: 0.5;
}


table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
}


thead {
    background: #f7f5ef;
}


th {
    padding: 14px 16px;
    text-align: left;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    border-bottom: 1px solid #ddd7ca;
}


td {
    padding: 14px 16px;
    border-bottom: 1px solid #ebe7df;
    vertical-align: top;
    font-size: 13px;
}


.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}


.status-pending {
    background: #fff3cd;
    color: #856404;
}


.status-approved {
    background: #e1ecd2;
    color: #4b681c;
}


.status-archived {
    background: #e8e8e8;
    color: #666;
}


.notice-area {
    max-width: 220px;
    line-height: 1.5;
}


.description {
    max-width: 300px;
    line-height: 1.5;
}


.no-notices {
    text-align: center;
    padding: 50px;
    color: #777;
}

/* Modal Popup Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: #ffffff;
    padding: 30px;
    border-radius: 8px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transform: translateY(-20px);
    transition: transform 0.3s ease;
}

.modal-overlay.active .modal-content {
    transform: translateY(0);
}

.modal-icon {
    font-size: 40px;
    color: #5f7022;
    margin-bottom: 15px;
}

.modal-title {
    font-size: 20px;
    color: #263238;
    margin-bottom: 10px;
    font-weight: 700;
}

.modal-body {
    font-size: 14px;
    color: #555;
    margin-bottom: 25px;
    line-height: 1.5;
}


@media (max-width: 800px) {

    .form-grid {
        grid-template-columns: 1fr;
    }


    .form-group.full {
        grid-column: auto;
    }


    .ward-checkboxes {
        grid-template-columns:
            repeat(2, 1fr);
    }


    .container {
        width: calc(100% - 36px);
    }
}


@media (max-width: 550px) {

    .ward-checkboxes {
        grid-template-columns: 1fr;
    }


    .topbar {
        min-height: 130px;
        padding: 15px 18px;
        align-items: flex-end;
    }


    .topbar-title {
        top: 20px;
    }
}

</style>

</head>


<body>

<!-- Submission Pop-up Alert Modal -->
<div class="modal-overlay" id="approvalModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-labelledby="modalTitle" aria-describedby="modalBody">
        <div class="modal-icon">
        </div>
        <div class="modal-title" id="modalTitle">Notice Sent</div>
        <div class="modal-body" id="modalBody">
            Notice sent, waiting for admin approval.
        </div>
        <button type="button" class="button button-primary" id="closeModalBtn">OK</button>
    </div>
</div>

<div class="page">


<header class="topbar">


<div class="topbar-left">

<div class="header-navigation">
    <a href="javascript:history.back()" class="backBtn">Back</a>
</div>

</div>


<div class="topbar-title">

<h1>
    Municipal Notices
</h1>

</div>


<div class="user">

<?= htmlspecialchars(
    $username,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>


</header>


<div class="container">


<?php if ($message !== ""): ?>

<div
    class="message <?= htmlspecialchars(
        $messageType
    ) ?>"
>

<?= htmlspecialchars(
    $message
) ?>

</div>

<?php endif; ?>


<!-- CREATE NOTICE -->

<div class="form-container">

<h3>
    Create Notice
</h3>


<form
    method="POST"
    action=""
>


<div class="form-grid">


<div class="form-group">

<label>
    Notice Title
</label>

<input
    type="text"
    name="notice_title"
    required
>

</div>


<div class="form-group">

<label>
    Service
</label>

<select
    name="notice_category"
    required
>

<option
    value=""
    selected
    disabled
>
    Select Service
</option>


<?php foreach (
    $services
    as $service
): ?>

<option
    value="<?= htmlspecialchars(
        $service
    ) ?>"
>
    <?= htmlspecialchars(
        $service
    ) ?>
</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group full">

<label>
    Notice Area
</label>


<div class="ward-selection">


<label class="community-option">

<input
    type="checkbox"
    name="community_wide"
    id="communityWide"
    value="1"
    checked
>

Community-wide

</label>


<div class="ward-checkboxes">


<?php foreach (
    $wards
    as $wardRow
): ?>

<label class="ward-option">

<input
    type="checkbox"
    name="wards[]"
    class="ward-checkbox"
    value="<?= (int)$wardRow[
        "ward_id"
    ] ?>"
>

Ward <?= (int)$wardRow[
    "ward_number"
] ?>

</label>

<?php endforeach; ?>


</div>

</div>

</div>


<div class="form-group">

<label>
    Publish Date
</label>

<input
    type="datetime-local"
    name="publish_date"
    required
>

</div>


<div class="form-group">

<label>
    Expiry Date
</label>

<input
    type="datetime-local"
    name="expiry_date"
    required
>

</div>


<div class="form-group full">

<label>
    Notice Description
</label>

<textarea
    name="notice_description"
    required
></textarea>

</div>


</div>


<div class="form-buttons">

<button
    type="submit"
    name="add_notice"
    class="button button-primary"
>
    Submit Notice
</button>

</div>


</form>

</div>


<!-- MY NOTICES -->

<div class="table-container">

<h3>
    My Notices
</h3>

<!-- Search, Quick Filters & Feedback Controls -->
<div class="search-controls">
    <div class="search-input-wrapper">
        <input type="text" id="noticeSearch" placeholder="Search notices..." aria-label="Search notices">
        <span class="search-shortcut-badge" id="shortcutBadge">/</span>
        <button type="button" class="clear-search-btn" id="clearSearchBtn" title="Clear Search (Esc)" aria-label="Clear Search">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Quick Status Filters -->
    <div class="quick-filter-chips">
        <span style="font-size:0.8rem; color:#666;">Status Filters:</span>
        <button type="button" class="chip active" data-filter="">All</button>
        <button type="button" class="chip" data-filter="pending">Pending</button>
        <button type="button" class="chip" data-filter="approved">Approved</button>
        <button type="button" class="chip" data-filter="archived">Archived</button>
    </div>

    <div id="searchFeedback" class="search-status-feedback" aria-live="polite"></div>
</div>


<?php if (
    $result &&$result->num_rows > 0
): ?>

<table id="noticesTable">

<thead>

<tr>

<th class="sortable-header" data-sort="0">ID <i class="fa-solid fa-sort sort-icon"></i></th>

<th class="sortable-header" data-sort="1">Title <i class="fa-solid fa-sort sort-icon"></i></th>

<th class="sortable-header" data-sort="2">Service <i class="fa-solid fa-sort sort-icon"></i></th>

<th>Area</th>

<th class="sortable-header" data-sort="4">Publish Date <i class="fa-solid fa-sort sort-icon"></i></th>

<th class="sortable-header" data-sort="5">Expiry Date <i class="fa-solid fa-sort sort-icon"></i></th>

<th class="sortable-header" data-sort="6">Status <i class="fa-solid fa-sort sort-icon"></i></th>

<th>Description</th>

</tr>

</thead>


<tbody>


<?php while (
    $notice =
        $result->fetch_assoc()
): ?>


<?php

$noticeStatus =
    strtolower(
        trim(
            $notice["status"]
        )
    );

?>


<tr class="notice-row">


<td>
<?= (int)$notice[
    "notice_id"
] ?>
</td>


<td>
<?= htmlspecialchars(
    $notice[
        "notice_title"
    ]
) ?>
</td>


<td>
<?= htmlspecialchars(
    $notice[
        "notice_category"
    ]
) ?>
</td>


<td>

<div class="notice-area">

<?php

if (
    intval(
        $notice["ward_count"]
    ) === 0
) {

    echo "Community-wide";

} else {

    echo htmlspecialchars(
        $notice["ward_list"]
    );
}

?>

</div>

</td>


<td>

<?= date(
    "d M Y H:i",
    strtotime(
        $notice[
            "publish_date"
        ]
    )
) ?>

</td>


<td>

<?= date(
    "d M Y H:i",
    strtotime(
        $notice[
            "expiry_date"
        ]
    )
) ?>

</td>


<td class="status-cell">

<?php if (
    $noticeStatus === "pending"
): ?>

<span class="status status-pending">
    Pending Approval
</span>

<?php elseif (
    $noticeStatus === "approved"
): ?>

<span class="status status-approved">
    Approved
</span>

<?php else: ?>

<span class="status status-archived">
    <?= htmlspecialchars(
        ucfirst(
            $noticeStatus
        )
    ) ?>
</span>

<?php endif; ?>

</td>


<td>

<div class="description">

<?= htmlspecialchars(
    $notice[
        "notice_description"
    ]
) ?>

</div>

</td>


</tr>


<?php endwhile; ?>


</tbody>

</table>


<?php else: ?>

<div class="no-notices">
    You have not submitted any notices.
</div>

<?php endif; ?>


</div>


</div>

</div>

<script>

const communityWide = document.getElementById("communityWide");


const wardCheckboxes =
    document.querySelectorAll(
        ".ward-checkbox"
    );


function updateWardSelection()
{

    if (!communityWide) {

        return;
    }


    wardCheckboxes.forEach(
        function(checkbox)
        {

            if (
                communityWide.checked
            ) {

                checkbox.checked =
                    false;

                checkbox.disabled =
                    true;

            } else {

                checkbox.disabled =
                    false;
            }
        }
    );
}


communityWide.addEventListener(
    "change",
    updateWardSelection
);


updateWardSelection();

// Pop-Up Modal Alert logic
const approvalModal = document.getElementById("approvalModal");
const closeModalBtn = document.getElementById("closeModalBtn");

function openApprovalModal() {
    if (approvalModal) {
        approvalModal.classList.add("active");
        approvalModal.setAttribute("aria-hidden", "false");
    }
}

function closeApprovalModal() {
    if (approvalModal) {
        approvalModal.classList.remove("active");
        approvalModal.setAttribute("aria-hidden", "true");
    }
}

if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeApprovalModal);
}

if (approvalModal) {
    approvalModal.addEventListener("click", function(e) {
        if (e.target === approvalModal) {
            closeApprovalModal();
        }
    });
}

<?php if ($showApprovalModal): ?>
openApprovalModal();
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("noticeSearch");
    const searchFeedback = document.getElementById("searchFeedback");
    const clearSearchBtn = document.getElementById("clearSearchBtn");
    const shortcutBadge = document.getElementById("shortcutBadge");
    const filterChips = document.querySelectorAll(".quick-filter-chips .chip");
    const rows = document.querySelectorAll("#noticesTable tbody tr.notice-row");

    let activeChipFilter = "";

    function filterNotices() {
        if (!input) return;

        const query = input.value.toLowerCase().trim();
        let matchCount = 0;

        if (query.length > 0) {
            clearSearchBtn.style.display = "block";
            shortcutBadge.style.display = "none";
        } else {
            clearSearchBtn.style.display = "none";
            shortcutBadge.style.display = "block";
        }

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesQuery = text.includes(query);

            let matchesChip = true;
            if (activeChipFilter !== "") {
                const statusText = row.querySelector(".status-cell")?.textContent.toLowerCase() || "";
                matchesChip = statusText.includes(activeChipFilter);
            }

            if (matchesQuery && matchesChip) {
                row.style.display = "";
                matchCount++;
            } else {
                row.style.display = "none";
            }
        });

        if (query.length > 0 || activeChipFilter !== "") {
            const chipContext = activeChipFilter ? ` [Status: ${activeChipFilter.toUpperCase()}]` : "";
            searchFeedback.textContent = `Showing ${matchCount} matching notice(s)${chipContext}.`;
        } else {
            searchFeedback.textContent = "";
        }
    }

    if (input) {
        input.addEventListener("input", filterNotices);
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener("click", function() {
            input.value = "";
            filterNotices();
            input.focus();
        });
    }

    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            if (approvalModal && approvalModal.classList.contains("active")) {
                closeApprovalModal();
                return;
            }
        }

        if (!input) return;

        if (e.key === "/" && document.activeElement !== input) {
            e.preventDefault();
            input.focus();
        } else if (e.key === "Escape") {
            if (input.value !== "") {
                input.value = "";
                filterNotices();
            } else {
                input.blur();
            }
        }
    });

    filterChips.forEach(chip => {
        chip.addEventListener("click", function() {
            filterChips.forEach(c => c.classList.remove("active"));
            this.classList.add("active");
            activeChipFilter = this.getAttribute("data-filter");
            filterNotices();
        });
    });

    const headers = document.querySelectorAll(".sortable-header");
    let sortAsc = true;

    headers.forEach(header => {
        header.addEventListener("click", function() {
            const colIndex = parseInt(this.getAttribute("data-sort"));
            const tbody = document.querySelector("#noticesTable tbody");
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


<?php

$stmt->close();

$conn->close();

?>
<script src="Global dashboard layout.js"></script>