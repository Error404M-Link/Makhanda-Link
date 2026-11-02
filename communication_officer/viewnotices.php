<?php
session_start();
 
// check if user is logged in
if (!isset($_SESSION["username"])) {
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}
 
require_once "../Error404Connection.php";
 
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
 
// check user role (same rule as manageNotices.php)
$userRole = strtolower(trim($_SESSION["user_role"] ?? $_SESSION["role"] ?? ""));
if ($userRole !== "municipal_staff" && $userRole !== "communications_officer") {
    die("Access denied.");
}
 
// all notices that have not been deleted, newest first
$sql = "
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
            DISTINCT CONCAT('Ward ', w.ward_number)
            ORDER BY w.ward_number ASC
            SEPARATOR ', '
        ) AS ward_list,
        COUNT(DISTINCT nw.ward_id) AS ward_count
    FROM notices n
    LEFT JOIN notice_ward nw ON n.notice_id = nw.notice_id
    LEFT JOIN ward w ON nw.ward_id = w.ward_id
    WHERE n.deleted_at IS NULL
    GROUP BY
        n.notice_id, n.username, n.notice_title, n.notice_category,
        n.publish_date, n.expiry_date, n.status,
        n.notice_description, n.date_posted
    ORDER BY n.date_posted DESC
";
 
$result = $conn->query($sql);
if (!$result) {
    die("Unable to load notices: " . $conn->error);
}
 
$notices  = [];
$statuses = [];
while ($row = $result->fetch_assoc()) {
    $notices[] = $row;
    $s = trim((string)$row['status']);
    if ($s !== '') {
        $statuses[$s] = true;
    }
}
$statuses = array_keys($statuses);
sort($statuses);
 
function fmtDate($d)
{
    return !empty($d) ? date('d M Y', strtotime($d)) : 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-LINK | View Notices</title>
 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
 
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
        body {
            font-family: 'Ubuntu', sans-serif;
            background: #f6f4ee;
            color: #263238;
        }
 
        .page { padding: 35px; max-width: 1200px; margin: 0 auto; }
 
        .sub-heading {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #955b24;
            text-transform: uppercase;
        }
 
        h1 {
            font-family: 'Lora', serif;
            font-size: 32px;
            color: #4b561b;
            margin: 4px 0 22px;
        }
 
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 8px;
        }
 
        .toolbar input {
            flex: 1 1 260px;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
        }
 
        .chips { display: flex; gap: 8px; flex-wrap: wrap; }
 
        .chip {
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 0.825rem;
            font-family: inherit;
            cursor: pointer;
            transition: all .2s ease;
        }
 
        .chip:hover, .chip.active {
            background: #4b561b;
            color: #fff;
            border-color: #4b561b;
        }
 
        #feedback {
            font-size: 0.85rem;
            color: #555;
            font-style: italic;
            min-height: 20px;
            margin-bottom: 14px;
        }
 
        .notice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
 
        .notice-card {
            background: #fff;
            border: 1px solid #e4e0d7;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform .2s ease, box-shadow .2s ease;
        }
 
        .notice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(75, 86, 27, .12);
        }
 
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
 
        .category {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #955b24;
        }
 
        .status-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
            background: #eceadf;
            color: #4b561b;
        }
 
        .status-pending  { background: #fff3cd; color: #7a5b00; }
        .status-approved,
        .status-published { background: #dff0d8; color: #2f5d1f; }
        .status-rejected  { background: #f8d7da; color: #842029; }
        .status-expired   { background: #e2e3e5; color: #41464b; }
 
        .notice-card h3 {
            font-family: 'Lora', serif;
            font-size: 19px;
            color: #263238;
        }
 
        .desc {
            font-size: 14px;
            color: #555;
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
 
        .meta {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #efece4;
            font-size: 12.5px;
            color: #666;
            display: grid;
            gap: 4px;
        }
 
        .meta i { width: 16px; color: #4b561b; }
 
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            border: 1px dashed #cfcabb;
            border-radius: 10px;
            color: #666;
        }
 
        @media (max-width: 750px) {
            .page { padding: 20px 15px; }
            h1 { font-size: 26px; }
        }
    </style>
</head>
<body>
 
<div class="page">
    <span class="sub-heading">Municipal Notices</span>
    <h1>View Notices</h1>
 
    <div class="toolbar">
        <input type="text" id="noticeSearch" placeholder="Search notices..." aria-label="Search notices">
        <div class="chips">
            <button type="button" class="chip active" data-filter="">All</button>
            <?php foreach ($statuses as $s): ?>
                <button type="button" class="chip" data-filter="<?= e(strtolower($s)) ?>"><?= e($s) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="feedback" aria-live="polite"></div>
 
    <div class="notice-grid" id="noticeGrid">
        <?php if (count($notices) > 0): ?>
            <?php foreach ($notices as $n): ?>
                <?php
                    $status = trim((string)$n['status']);
                    $wards  = ((int)$n['ward_count'] > 0) ? $n['ward_list'] : 'Community-wide';
                ?>
                <article class="notice-card" data-status="<?= e(strtolower($status)) ?>">
                    <div class="card-top">
                        <span class="category"><?= e($n['notice_category']) ?></span>
                        <span class="status-badge status-<?= e(strtolower($status)) ?>"><?= e($status) ?></span>
                    </div>
 
                    <h3><?= e($n['notice_title']) ?></h3>
                    <p class="desc"><?= e($n['notice_description']) ?></p>
 
                    <div class="meta">
                        <span><i class="fa-solid fa-location-dot"></i> <?= e($wards) ?></span>
                        <span><i class="fa-regular fa-calendar"></i> <?= e(fmtDate($n['publish_date'])) ?> &ndash; <?= e(fmtDate($n['expiry_date'])) ?></span>
                        <span><i class="fa-regular fa-user"></i> <?= e($n['username']) ?> &middot; posted <?= e(fmtDate($n['date_posted'])) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">No notices found.</div>
        <?php endif; ?>
    </div>
</div>
 
<script>
document.addEventListener("DOMContentLoaded", function () {
    var input    = document.getElementById("noticeSearch");
    var feedback = document.getElementById("feedback");
    var chips    = document.querySelectorAll(".chip");
    var cards    = document.querySelectorAll(".notice-card");
    var active   = "";
 
    function apply() {
        var q = input.value.toLowerCase().trim();
        var shown = 0;
 
        cards.forEach(function (card) {
            var okText   = card.textContent.toLowerCase().indexOf(q) !== -1;
            var okStatus = active === "" || card.getAttribute("data-status") === active;
            var show = okText && okStatus;
            card.style.display = show ? "" : "none";
            if (show) { shown++; }
        });
 
        feedback.textContent = (q || active)
            ? "Showing " + shown + " matching notice(s)."
            : "";
    }
 
    input.addEventListener("input", apply);
 
    chips.forEach(function (chip) {
        chip.addEventListener("click", function () {
            chips.forEach(function (c) { c.classList.remove("active"); });
            chip.classList.add("active");
            active = chip.getAttribute("data-filter");
            apply();
        });
    });
});
</script>
 
</body>
</html>
<script src="Global dashboard layout.js"></script>
 