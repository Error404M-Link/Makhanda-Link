<?php

session_start();

require_once "Error404Connection.php";

//check if administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);

//check administrator role
if ($userRole !== "admin") {

    die("Access denied.");
}

$message = "";
$messageType = "";

//get municipal service categories
$categories = [];

$categoryResult = $conn->query("
    SELECT DISTINCT service_type
    FROM municipal_service
    WHERE service_type IS NOT NULL
    AND TRIM(service_type) <> ''
    ORDER BY service_type ASC
");

if (!$categoryResult) {

    die(
        "Unable to load municipal services: "
        . $conn->error
    );
}

while ($row = $categoryResult->fetch_assoc()) {

    $categories[] = $row["service_type"];
}

//CREATE NOTICE
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "create_notice"
) {

    $title =
        trim($_POST["notice_title"] ?? "");

    $category =
        trim($_POST["notice_category"] ?? "");

    $description =
        trim($_POST["notice_description"] ?? "");

    $publishDate =
        $_POST["publish_date"] ?? "";

    $expiryDate =
        $_POST["expiry_date"] ?? "";

    if (
        $title === ""
        || $category === ""
        || $description === ""
        || $publishDate === ""
        || $expiryDate === ""
    ) {

        $message =
            "Please complete all required fields.";

        $messageType =
            "error";

    } elseif (
        !in_array(
            $category,
            $categories,
            true
        )
    ) {

        $message =
            "Please select a valid municipal service.";

        $messageType =
            "error";

    } elseif (
        strtotime($expiryDate)
        <= strtotime($publishDate)
    ) {

        $message =
            "Expiry date must be after the publish date.";

        $messageType =
            "error";

    } else {

        $stmt = $conn->prepare("
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
                'Active',
                ?,
                NOW(),
                NULL
            )
        ");

        if (!$stmt) {

            $message =
                "Database error: "
                . $conn->error;

            $messageType =
                "error";

        } else {

            $stmt->bind_param(
                "ssssss",
                $adminUsername,
                $title,
                $category,
                $publishDate,
                $expiryDate,
                $description
            );

            if ($stmt->execute()) {

                $message =
                    "Notice created successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Notice could not be created: "
                    . $stmt->error;

                $messageType =
                    "error";
            }

            $stmt->close();
        }
    }
}

//EDIT NOTICE
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "edit_notice"
) {

    $noticeId =
        (int)($_POST["notice_id"] ?? 0);

    $title =
        trim($_POST["notice_title"] ?? "");

    $category =
        trim($_POST["notice_category"] ?? "");

    $publishDate =
        $_POST["publish_date"] ?? "";

    $expiryDate =
        $_POST["expiry_date"] ?? "";

    $description =
        trim($_POST["notice_description"] ?? "");

    if (
        $noticeId <= 0
        || $title === ""
        || $category === ""
        || $description === ""
        || $publishDate === ""
        || $expiryDate === ""
    ) {

        $message =
            "Please complete all required fields.";

        $messageType =
            "error";

    } elseif (
        !in_array(
            $category,
            $categories,
            true
        )
    ) {

        $message =
            "Please select a valid municipal service.";

        $messageType =
            "error";

    } elseif (
        strtotime($expiryDate)
        <= strtotime($publishDate)
    ) {

        $message =
            "Expiry date must be after the publish date.";

        $messageType =
            "error";

    } else {

        $stmt = $conn->prepare("
            UPDATE notices

            SET
                notice_title = ?,
                notice_category = ?,
                publish_date = ?,
                expiry_date = ?,
                notice_description = ?

            WHERE notice_id = ?
            AND deleted_at IS NULL
        ");

        if (!$stmt) {

            $message =
                "Database error: "
                . $conn->error;

            $messageType =
                "error";

        } else {

            $stmt->bind_param(
                "sssssi",
                $title,
                $category,
                $publishDate,
                $expiryDate,
                $description,
                $noticeId
            );

            if ($stmt->execute()) {

                $message =
                    "Notice updated successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Notice could not be updated: "
                    . $stmt->error;

                $messageType =
                    "error";
            }

            $stmt->close();
        }
    }
}

//APPROVE NOTICE
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "approve"
) {

    $noticeId =
        (int)($_POST["notice_id"] ?? 0);

    if ($noticeId > 0) {

        $stmt = $conn->prepare("
            UPDATE notices
            SET status = 'Approved'
            WHERE notice_id = ?
            AND deleted_at IS NULL
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $noticeId
            );

            if ($stmt->execute()) {

                $message =
                    "Notice approved successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Notice could not be approved.";

                $messageType =
                    "error";
            }

            $stmt->close();
        }
    }
}

//ARCHIVE NOTICE
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "archive"
) {

    $noticeId =
        (int)($_POST["notice_id"] ?? 0);

    if ($noticeId > 0) {

        $stmt = $conn->prepare("
            UPDATE notices
            SET status = 'Archived'
            WHERE notice_id = ?
            AND deleted_at IS NULL
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $noticeId
            );

            if ($stmt->execute()) {

                $message =
                    "Notice archived successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Notice could not be archived.";

                $messageType =
                    "error";
            }

            $stmt->close();
        }
    }
}

//RESTORE ARCHIVED NOTICE
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "restore"
) {

    $noticeId =
        (int)($_POST["notice_id"] ?? 0);

    if ($noticeId > 0) {

        $stmt = $conn->prepare("
            UPDATE notices
            SET status = 'Active'
            WHERE notice_id = ?
            AND deleted_at IS NULL
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $noticeId
            );

            if ($stmt->execute()) {

                $message =
                    "Notice restored successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Notice could not be restored.";

                $messageType =
                    "error";
            }

            $stmt->close();
        }
    }
}

//SOFT DELETE NOTICE
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "delete"
) {

    $noticeId =
        (int)($_POST["notice_id"] ?? 0);

    if ($noticeId > 0) {

        $stmt = $conn->prepare("
            UPDATE notices
            SET deleted_at = NOW()
            WHERE notice_id = ?
            AND deleted_at IS NULL
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $noticeId
            );

            if ($stmt->execute()) {

                $message =
                    "Notice removed successfully.";

                $messageType =
                    "success";

            } else {

                $message =
                    "Notice could not be removed.";

                $messageType =
                    "error";
            }

            $stmt->close();
        }
    }
}

//GET FILTER VALUES
$search =
    trim($_GET["search"] ?? "");

$statusFilter =
    $_GET["status_filter"] ?? "all";

$categoryFilter =
    $_GET["category_filter"] ?? "all";

//BUILD NOTICE QUERY
$sql = "
    SELECT
        notice_id,
        username,
        notice_title,
        notice_category,
        publish_date,
        expiry_date,
        status,
        notice_description,
        date_posted

    FROM notices

    WHERE deleted_at IS NULL
";

$params = [];
$types = "";

//SEARCH
if ($search !== "") {

    $sql .= "
        AND
        (
            notice_title LIKE ?
            OR notice_category LIKE ?
            OR notice_description LIKE ?
            OR username LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}

//CATEGORY FILTER
if (
    $categoryFilter !== "all"
    && in_array(
        $categoryFilter,
        $categories,
        true
    )
) {

    $sql .= "
        AND notice_category = ?
    ";

    $params[] =
        $categoryFilter;

    $types .= "s";
}

//STATUS FILTER
$allowedStatuses = [
    "Draft",
    "Pending",
    "Approved",
    "Active",
    "Archived"
];

if (
    $statusFilter !== "all"
    && in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND status = ?
    ";

    $params[] =
        $statusFilter;

    $types .= "s";
}

$sql .= "
    ORDER BY date_posted DESC
";

$stmt =
    $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database error: "
        . $conn->error
    );
}

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

if (!$stmt->execute()) {

    die(
        "Unable to load notices: "
        . $stmt->error
    );
}

$noticeResult =
    $stmt->get_result();

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
    Manage Notices | M-LINK
</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f0e7;
    color: #263238;
    min-height: 100vh;
}

.page-container {
    width: 100%;
    max-width: 1350px;
    margin: 0 auto;
    padding: 35px 40px 60px;
}

.page-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 55px;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #4b561b;
    font-size: 30px;
    text-align: center;
}

.container {
    width: calc(100% - 70px);

    max-width: 1500px;

    margin: 15px auto 40px;
}

.message {
    margin-bottom: 20px;
    padding: 13px 16px;
    border-radius: 7px;
    font-size: 14px;
}

.message.success {
    background: #e6eed5;
    color: #35400f;
    border: 1px solid #a9b77d;
}

.message.error {
    background: #f7dddd;
    color: #7b2424;
    border: 1px solid #d5a2a2;
}

.toolbar {
    background: white;
    border: 1px solid #ddd7ca;
    border-radius: 10px;
    padding: 17px;
    margin-bottom: 22px;

    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.filter-form input,
.filter-form select {
    height: 43px;
    padding: 0 12px;
    border: 1px solid #ccc6bb;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    outline: none;
}

.filter-form input {
    flex: 1;
    min-width: 180px;
}

.filter-form select {
    min-width: 160px;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: #4b561b;
}

button,
.button {
    border: none;
    border-radius: 6px;
    padding: 11px 17px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    font-family: inherit;
}

.primary-button {
    background: #4b561b;
    color: white;
}

.primary-button:hover {
    background: #3e4816;
}

.secondary-button {
    background: #e6e2d9;
    color: #263238;
}

.danger-button {
    background: #963636;
    color: white;
}

.restore-button {
    background: #4b561b;
    color: white;
}

.add-button {
    white-space: nowrap;
}

.table-card {
    background: white;
    border: 1px solid #ddd7ca;
    border-radius: 10px;
    overflow: hidden;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #4b561b;
    color: white;
    padding: 14px;
    text-align: left;
    font-size: 13px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #ebe7de;
    font-size: 13px;
    vertical-align: middle;
}

tbody tr:hover {
    background: #faf9f5;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status.Active {
    background: #e4edcf;
    color: #40500f;
}

.status.Approved {
    background: #dce9d5;
    color: #365227;
}

.status.Pending {
    background: #f5e8bf;
    color: #725b13;
}

.status.Draft {
    background: #e5e5e5;
    color: #555;
}

.status.Archived {
    background: #efdddd;
    color: #813737;
}

.actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.action-button {
    padding: 7px 10px;
    font-size: 12px;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #777;
}

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 20px;
}

.modal-overlay.show {
    display: flex;
}

.modal {
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    background: white;
    border-radius: 10px;
    padding: 26px;
}

.modal h2 {
    color: #4b561b;
    text-align: center;
    margin-bottom: 22px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 7px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc6bb;
    border-radius: 6px;
    font-family: inherit;
    font-size: 14px;
}

.form-group textarea {
    min-height: 110px;
    resize: vertical;
}

.modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

@media (max-width: 900px) {

    .page-container {
        padding: 25px 20px;
    }

    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form {
        flex-wrap: wrap;
    }

    .filter-form input {
        flex-basis: 100%;
    }

    .add-button {
        width: 100%;
    }
}

@media (max-width: 600px) {

    .page-header {
        padding-top: 60px;
    }

    .filter-form {
        flex-direction: column;
    }

    .filter-form input,
    .filter-form select,
    .filter-form button,
    .filter-form .button {
        width: 100%;
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
$adminActivePage = "services";
require __DIR__ . "/adminHeader.php";
?>

<div class="page-container">

<?php if ($message !== ""): ?>

<div
    class="message <?= htmlspecialchars($messageType) ?>"
>
    <?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>

<section class="toolbar">

<form
    method="GET"
    action="manageNotices.php"
    class="filter-form"
>

<input
    type="text"
    name="search"
    placeholder="Search notices..."
    value="<?= htmlspecialchars($search) ?>"
>

<select name="category_filter">

<option value="all">
    All Services
</option>

<?php foreach ($categories as $category): ?>

<option
    value="<?= htmlspecialchars($category) ?>"
    <?= $categoryFilter === $category
        ? "selected"
        : "" ?>
>
    <?= htmlspecialchars($category) ?>
</option>

<?php endforeach; ?>

</select>

<select name="status_filter">

<option
    value="all"
    <?= $statusFilter === "all"
        ? "selected"
        : "" ?>
>
    All Statuses
</option>

<option
    value="Active"
    <?= $statusFilter === "Active"
        ? "selected"
        : "" ?>
>
    Active
</option>

<option
    value="Pending"
    <?= $statusFilter === "Pending"
        ? "selected"
        : "" ?>
>
    Pending
</option>

<option
    value="Approved"
    <?= $statusFilter === "Approved"
        ? "selected"
        : "" ?>
>
    Approved
</option>

<option
    value="Draft"
    <?= $statusFilter === "Draft"
        ? "selected"
        : "" ?>
>
    Draft
</option>

<option
    value="Archived"
    <?= $statusFilter === "Archived"
        ? "selected"
        : "" ?>
>
    Archived
</option>

</select>

<button
    type="submit"
    class="primary-button"
>
    Search
</button>

<?php if (
    $search !== ""
    || $categoryFilter !== "all"
    || $statusFilter !== "all"
): ?>

<a
    href="manageNotices.php"
    class="button secondary-button"
>
    Clear
</a>

<?php endif; ?>

</form>

<button
    type="button"
    class="primary-button add-button"
    onclick="openAddModal()"
>
    + Add Notice
</button>

</section>

<section class="table-card">

<div class="table-wrapper">

<table>

<thead>

<tr>

    <th>Title</th>

    <th>Service</th>

    <th>Publish Date</th>

    <th>Expiry Date</th>

    <th>Status</th>

    <th>Posted By</th>

    <th>Actions</th>

</tr>

</thead>

<tbody>

<?php if (
    $noticeResult
    && $noticeResult->num_rows > 0
): ?>

<?php while (
    $notice =
    $noticeResult->fetch_assoc()
): ?>

<tr>

<td>

<?= htmlspecialchars(
    $notice["notice_title"]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $notice["notice_category"]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $notice["publish_date"]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $notice["expiry_date"]
) ?>

</td>

<td>

<span
    class="status <?= htmlspecialchars($notice["status"]) ?>"
>

<?= htmlspecialchars(
    $notice["status"]
) ?>

</span>

</td>

<td>

<?= htmlspecialchars(
    $notice["username"]
) ?>

</td>

<td>

<div class="actions">

<button
    type="button"
    class="secondary-button action-button"
    onclick='openEditModal(
        <?= json_encode(
            $notice,
            JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_HEX_AMP
        ) ?>
    )'
>
    Edit
</button>

<?php if (
    $notice["status"] === "Pending"
): ?>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="approve"
>

<input
    type="hidden"
    name="notice_id"
    value="<?= (int)$notice["notice_id"] ?>"
>

<button
    type="submit"
    class="primary-button action-button"
>
    Approve
</button>

</form>

<?php endif; ?>

<?php if (
    $notice["status"] !== "Archived"
): ?>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="archive"
>

<input
    type="hidden"
    name="notice_id"
    value="<?= (int)$notice["notice_id"] ?>"
>

<button
    type="submit"
    class="secondary-button action-button"
>
    Archive
</button>

</form>

<?php else: ?>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="restore"
>

<input
    type="hidden"
    name="notice_id"
    value="<?= (int)$notice["notice_id"] ?>"
>

<button
    type="submit"
    class="restore-button action-button"
>
    Restore
</button>

</form>

<?php endif; ?>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="delete"
>

<input
    type="hidden"
    name="notice_id"
    value="<?= (int)$notice["notice_id"] ?>"
>

<button
    type="submit"
    class="danger-button action-button"
>
    Delete
</button>

</form>

</div>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td
    colspan="7"
    class="empty-state"
>
    No notices found.
</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</section>

</div>

<!-- ADD NOTICE -->

<div
    class="modal-overlay"
    id="addModal"
>

<div class="modal">

<h2>
    Add Notice
</h2>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="create_notice"
>

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
    Municipal Service
</label>

<select
    name="notice_category"
    required
>

<option value="">
    Select Municipal Service
</option>

<?php foreach ($categories as $category): ?>

<option
    value="<?= htmlspecialchars($category) ?>"
>
    <?= htmlspecialchars($category) ?>
</option>

<?php endforeach; ?>

</select>

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

<div class="form-group">

<label>
    Description
</label>

<textarea
    name="notice_description"
    required
></textarea>

</div>

<div class="modal-buttons">

<button
    type="button"
    class="secondary-button"
    onclick="closeModal('addModal')"
>
    Cancel
</button>

<button
    type="submit"
    class="primary-button"
>
    Add Notice
</button>

</div>

</form>

</div>

</div>

<!-- EDIT NOTICE -->

<div
    class="modal-overlay"
    id="editModal"
>

<div class="modal">

<h2>
    Edit Notice
</h2>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="edit_notice"
>

<input
    type="hidden"
    name="notice_id"
    id="editNoticeId"
>

<div class="form-group">

<label>
    Notice Title
</label>

<input
    type="text"
    name="notice_title"
    id="editTitle"
    required
>

</div>

<div class="form-group">

<label>
    Municipal Service
</label>

<select
    name="notice_category"
    id="editCategory"
    required
>

<?php foreach ($categories as $category): ?>

<option
    value="<?= htmlspecialchars($category) ?>"
>
    <?= htmlspecialchars($category) ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group">

<label>
    Publish Date
</label>

<input
    type="datetime-local"
    name="publish_date"
    id="editPublishDate"
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
    id="editExpiryDate"
    required
>

</div>

<div class="form-group">

<label>
    Description
</label>

<textarea
    name="notice_description"
    id="editDescription"
    required
></textarea>

</div>

<div class="modal-buttons">

<button
    type="button"
    class="secondary-button"
    onclick="closeModal('editModal')"
>
    Cancel
</button>

<button
    type="submit"
    class="primary-button"
>
    Save Changes
</button>

</div>

</form>

</div>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<script>

function openModal(modalId)
{
    document
        .getElementById(modalId)
        .classList
        .add("show");
}

function closeModal(modalId)
{
    document
        .getElementById(modalId)
        .classList
        .remove("show");
}

function openAddModal()
{
    openModal("addModal");
}

function formatDateForInput(dateValue)
{
    if (!dateValue) {

        return "";
    }

    return dateValue
        .replace(" ", "T")
        .substring(0, 16);
}

function openEditModal(notice)
{

    document.getElementById(
        "editNoticeId"
    ).value =
        notice.notice_id;

    document.getElementById(
        "editTitle"
    ).value =
        notice.notice_title;

    document.getElementById(
        "editCategory"
    ).value =
        notice.notice_category;

    document.getElementById(
        "editPublishDate"
    ).value =
        formatDateForInput(
            notice.publish_date
        );

    document.getElementById(
        "editExpiryDate"
    ).value =
        formatDateForInput(
            notice.expiry_date
        );

    document.getElementById(
        "editDescription"
    ).value =
        notice.notice_description;

    openModal(
        "editModal"
    );
}

document
    .querySelectorAll(
        ".modal-overlay"
    )
    .forEach(
        function(modal)
        {

            modal.addEventListener(
                "click",
                function(event)
                {

                    if (
                        event.target === modal
                    ) {

                        closeModal(
                            modal.id
                        );
                    }
                }
            );
        }
    );

document.addEventListener(
    "keydown",
    function(event)
    {

        if (event.key === "Escape") {

            document
                .querySelectorAll(
                    ".modal-overlay.show"
                )
                .forEach(
                    function(modal)
                    {

                        closeModal(
                            modal.id
                        );
                    }
                );
        }
    }
);

</script>

</body>

</html>

<?php

$stmt->close();

$conn->close();

?>