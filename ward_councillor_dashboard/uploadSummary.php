<?php
/* ALLOWS WARD COUNCILLORS TO UPLOAD & EDIT WARD SUMMARIES */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "Error404Connection.php";
require_once "detailsOfTheLoggedInUser.php";

// login check (same as the other ward councillor pages)
if (
    empty($_SESSION['username']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'ward_councillor'
) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit();
}



function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


$username = $_SESSION['username'];

$loggedInCouncillor = null;

$stmt = $conn->prepare("
    SELECT
        wc.username,
        wc.ward_id,
        u.first_Name,
        u.surname,
        u.profile_id,
        pi.image_path
    FROM ward_councillor wc
    LEFT JOIN user u
        ON wc.username = u.username
    LEFT JOIN profile_images pi
        ON u.profile_id = pi.profile_id
    WHERE wc.username = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $loggedInCouncillor = $result->fetch_assoc();
    $stmt->close();
}

if (!$loggedInCouncillor) {
    die("No councillor record was found for this account.");
}

$loggedInWardNum = (int)($loggedInCouncillor['ward_id'] ?? 0);

if ($loggedInWardNum < 1 || $loggedInWardNum > 14) {
    die("No valid ward has been assigned to this councillor.");
}

$loggedInFullName = trim(
    ($loggedInCouncillor['first_Name'] ?? '') . ' ' .
    ($loggedInCouncillor['surname'] ?? '')
);

if (empty($loggedInFullName)) {
    $loggedInFullName = $username;
}

$loggedInProfileImage = 'profile.jpg';

if (!empty($loggedInCouncillor['image_path'])) {
    $loggedInProfileImage = $loggedInCouncillor['image_path'];
}

// A councillor can only submit and edit summaries for their own ward
$ward = $loggedInWardNum;
$_SESSION['ward_id'] = $ward;

// Name of the ward, shown in the locked Ward field
$wardName = "Ward " . $ward;

$stmt = $conn->prepare("SELECT ward_name, ward_number FROM ward WHERE ward_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $ward);
    $stmt->execute();
    $wardRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($wardRow) {
        $wardName = !empty($wardRow['ward_name'])
            ? $wardRow['ward_name']
            : "Ward " . $wardRow['ward_number'];
    }
}


/* =========================================================
   UNREAD COUNTS (sidebar + top bar)
========================================================= */

$unread_count = 0;

$unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM internal_messages
    WHERE receiver_username = ?
    AND is_read = 0
");

if ($unread_stmt) {
    $unread_stmt->bind_param("s", $username);
    $unread_stmt->execute();
    $unread_data = $unread_stmt->get_result()->fetch_assoc();
    $unread_count = (int)($unread_data['unread_count'] ?? 0);
    $unread_stmt->close();
}

$unreadNotificationCount = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'new_ticket'
    AND is_read = 0
");

if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $r = $res->fetch_assoc()) {
        $unreadNotificationCount = (int)$r['unread_count'];
    }
    $stmt->close();
}


/* =========================================================
   UPLOAD SETTINGS
========================================================= */

const UPLOAD_DIR      = __DIR__ . '/uploads/ward_summaries/';
const UPLOAD_URL_BASE = 'uploads/ward_summaries/';
const MAX_FILE_BYTES  = 10 * 1024 * 1024; // 10MB

$errors    = [];
$statusMsg = $_SESSION['statusMsg'] ?? null;
unset($_SESSION['statusMsg']); // flash message reset


/* =========================================================
   EDIT STATE (only summaries from the councillor's own ward)
========================================================= */

$editId      = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editSummary = null;

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM ward_summaries WHERE summary_id = ? AND ward_id = ?");
    $stmt->bind_param("ii", $editId, $ward);
    $stmt->execute();
    $editSummary = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editSummary) {
        header("Location: ward_summaries.php");
        exit;
    }
}

$isDeleted = $editSummary && (int)$editSummary['deleted'] === 1;


/* =========================================================
   SOFT DELETE / RESTORE
========================================================= */

if ($editSummary && isset($_GET['action'])) {

    $action = $_GET['action'];

    if ($action === 'delete') {
        $stmt = $conn->prepare("UPDATE ward_summaries SET deleted = 1 WHERE summary_id = ? AND ward_id = ?");
        $stmt->bind_param("ii", $editId, $ward);
        $stmt->execute();
        $stmt->close();
        $_SESSION['statusMsg'] = "Summary deleted successfully.";
    } elseif ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE ward_summaries SET deleted = 0 WHERE summary_id = ? AND ward_id = ?");
        $stmt->bind_param("ii", $editId, $ward);
        $stmt->execute();
        $stmt->close();
        $_SESSION['statusMsg'] = "Summary restored successfully.";
    }

    header("Location: uploadSummary.php?edit_id=" . $editId);
    exit;
}


/* FORM SUBMISSION (INSERT / UPDATE) */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Deleted summaries cannot be edited
    if ($isDeleted) {
        $_SESSION['statusMsg'] = "Deleted summaries cannot be updated.";
        header("Location: uploadSummary.php?edit_id=" . $editId);
        exit;
    }

    $title       = trim($_POST['title'] ?? '');
    $periodStart = trim($_POST['period_start'] ?? '');
    $periodEnd   = trim($_POST['period_end'] ?? '');

    if ($title === '') {
        $errors[] = "Title is required.";
    }

    if ($periodStart !== '' && $periodEnd !== '' && strtotime($periodStart) > strtotime($periodEnd)) {
        $errors[] = "Period start date must be before the period end date.";
    }

    $fileError  = $_FILES['summary_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $hasNewFile = $fileError === UPLOAD_ERR_OK;

    if ($fileError !== UPLOAD_ERR_OK && $fileError !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "The file could not be uploaded. Check that it is a PDF under 10MB.";
    } elseif (!$editSummary && !$hasNewFile) {
        $errors[] = "Please attach a PDF file.";
    }

    $filePathForDb = $editSummary['file_path'] ?? '';
    $fileSize      = (int)($editSummary['file_size_bytes'] ?? 0);

    if ($hasNewFile) {

        $file = $_FILES['summary_file'];

        if ($file['size'] > MAX_FILE_BYTES) {
            $errors[] = "File is too large. Maximum size is 10MB.";
        }

        $mimeType = '';
        if (class_exists('finfo')) {
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']);
        } else {
            $mimeType = $file['type'] ?? '';
        }

        if ($mimeType !== 'application/pdf') {
            $errors[] = "Only PDF files are allowed.";
        }

        if (empty($errors)) {
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            $safeName = bin2hex(random_bytes(16)) . '.pdf';
            $destPath = UPLOAD_DIR . $safeName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $filePathForDb = UPLOAD_URL_BASE . $safeName;
                $fileSize      = filesize($destPath);
            } else {
                $errors[] = "Could not move uploaded file.";
            }
        }
    }

    if (empty($errors)) {

        $periodStartVal = $periodStart !== '' ? $periodStart : null;
        $periodEndVal   = $periodEnd !== '' ? $periodEnd : null;

        if ($editSummary) {

            // UPDATE EXISTING SUMMARY
            $stmt = $conn->prepare(
                "UPDATE ward_summaries
                 SET title = ?, file_path = ?, file_size_bytes = ?, period_start = ?, period_end = ?
                 WHERE summary_id = ? AND ward_id = ?"
            );
            $stmt->bind_param("ssissii", $title, $filePathForDb, $fileSize, $periodStartVal, $periodEndVal, $editId, $ward);

            if ($stmt->execute()) {
                $_SESSION['statusMsg'] = "Summary updated successfully!";
                header("Location: uploadSummary.php?edit_id=" . $editId);
                exit;
            } else {
                $errors[] = "Could not update record.";
            }
            $stmt->close();

        } else {

            // INSERT NEW SUMMARY
            $stmt = $conn->prepare(
                "INSERT INTO ward_summaries
                    (ward_id, councillor_username, title, file_path, file_size_bytes, mime_type, period_start, period_end, deleted)
                 VALUES (?, ?, ?, ?, ?, 'application/pdf', ?, ?, 0)"
            );
            $stmt->bind_param("isssiss", $ward, $username, $title, $filePathForDb, $fileSize, $periodStartVal, $periodEndVal);

            if ($stmt->execute()) {
                $_SESSION['statusMsg'] = "Summary submitted successfully!";
                header("Location: uploadSummary.php?edit_id=" . $stmt->insert_id);
                exit;
            } else {
                $errors[] = "Could not create record.";
            }
            $stmt->close();
        }
    }
}


/* VARIABLES USED BY THE SHARED SIDEBAR*/

$ward_id      = $loggedInWardNum;
$fullName     = $loggedInFullName;
$userData['image_path'] = $loggedInProfileImage;
$activePage   = 'summaries';
$dashboardUrl = 'wardcouncilorDashboard.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $editSummary ? 'Edit' : 'Submit'; ?> Ward Summary | M-Link</title>

    <!-- Restores the sidebar state before the page draws (no flash) -->
    <script>
    try { if (localStorage.getItem("sidebarState") === "closed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}
    </script>

    <link rel="stylesheet" href="Wardcouncillo.css?v=<?php echo filemtime(__DIR__ . '/Wardcouncillo.css'); ?>">
    <link rel="stylesheet" href="uploadSummary.css?v=<?php echo filemtime(__DIR__ . '/uploadSummary.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>

<?php if ($statusMsg): ?>
    <div class="top-banner" id="popupBanner">
        <?php echo e($statusMsg); ?>
    </div>
    <script>
        setTimeout(function () {
            var banner = document.getElementById('popupBanner');
            if (banner) banner.style.display = 'none';
        }, 4000);
    </script>
<?php endif; ?>



<header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="true">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none"
             stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <a href="logout.php" class="logo-link" title="M-LINK Homepage">
        <img src="makanaLogo.svg" alt="M-Link logo" class="topbar-logo">
    </a>

    <div class="top-right">
        <a href="notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="notification-icon-wrapper">
                <img src="../icons/add_alert.svg" alt="" class="topbar-icon-img">
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="notification-badge">
                        <?php echo $unreadNotificationCount > 99 ? "99+" : $unreadNotificationCount; ?>
                    </span>
                <?php endif; ?>
            </span>
            <span class="topbar-icon-label">Notifications</span>
        </a>

        <a href="messages.php" class="topbar-icon-btn" title="Messages">
            <img src="../icons/message.svg" alt="" class="topbar-icon-img">
            <span class="topbar-icon-label">Messages</span>
        </a>

        <div class="user">
            <img src="<?php echo e($loggedInProfileImage); ?>" alt="Profile Picture" id="profileImage">
            <div class="user-details">
                <h4><?php echo e($loggedInFullName); ?></h4>
                <small>Ward Councillor</small>
            </div>
        </div>
    </div>
</header>

<?php include 'ward_sidebar.php'; ?>


<main class="main-content" id="mainContent">

    <div class="upload-wrap">

        <a href="ward_summaries.php" class="upload-back">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 5l-7 7 7 7"/>
            </svg>
            <h2>Back to ward summaries</h2>
        </a>

        <section class="upload-card">

            <!-- CARD HEADER -->
            <div class="upload-card-header">

                <div>
                    <h2><?php echo $editSummary ? 'Edit ward summary' : 'Submit ward summary'; ?></h2>
                    <p><?php echo e($wardName); ?></p>
                </div>


                

                <div class="upload-actions">

                    <a href="ward_summaries.php?show_deleted=1" class="link-deleted">
                        View deleted summaries
                    </a>

                    <?php if ($editSummary): ?>
                        <?php if (!$isDeleted): ?>
                            <a href="uploadSummary.php?edit_id=<?php echo $editId; ?>&amp;action=delete"
                               class="btn-delete"
                               onclick="return confirm('Are you sure you want to delete this summary?');">Delete</a>
                        <?php else: ?>
                            <a href="uploadSummary.php?edit_id=<?php echo $editId; ?>&amp;action=restore"
                               class="btn-restore">Restore</a>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>

            </div>


            <!-- ERRORS -->
            <?php if (!empty($errors)): ?>
                <ul class="upload-errors">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>


            <!-- DELETED WARNING -->
            <?php if ($isDeleted): ?>
                <div class="upload-warning">
                    This summary is deleted. Restore it to edit.
                </div>
            <?php endif; ?>


            <!-- WARD NOTICE -->
            <?php if (!$editSummary): ?>
                <div class="upload-notice">
                    <img src="notice.svg" alt="">
                    <span>You can submit ward summaries for <strong><?php echo e($wardName); ?></strong> only.</span>
                </div>
            <?php endif; ?>

            


            <!-- FORM -->
            <form method="POST"
                  action="uploadSummary.php<?php echo $editId ? '?edit_id=' . $editId : ''; ?>"
                  enctype="multipart/form-data">

                <!-- TITLE -->
                <div class="form-field">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required
                           value="<?php echo e($_POST['title'] ?? $editSummary['title'] ?? ''); ?>"
                           <?php echo $isDeleted ? 'disabled' : ''; ?>>
                </div>

                <!-- WARD (locked to the councillor's ward) -->
                <div class="form-field">
                    <label for="ward_display">Ward</label>
                    <input type="text" id="ward_display" value="<?php echo e($wardName); ?>" disabled>
                </div>

                <!-- PERIOD DATES -->
                <div class="form-row">

                    <div class="form-field">
                        <label for="period_start">Period start <span class="optional">(optional)</span></label>
                        <input type="date" id="period_start" name="period_start"
                               value="<?php echo e($_POST['period_start'] ?? $editSummary['period_start'] ?? ''); ?>"
                               <?php echo $isDeleted ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-field">
                        <label for="period_end">Period end <span class="optional">(optional)</span></label>
                        <input type="date" id="period_end" name="period_end"
                               value="<?php echo e($_POST['period_end'] ?? $editSummary['period_end'] ?? ''); ?>"
                               <?php echo $isDeleted ? 'disabled' : ''; ?>>
                    </div>

                </div>

                <!-- CURRENT FILE (edit mode) -->
                <?php if ($editSummary && !empty($editSummary['file_path'])): ?>
                    <div class="form-field">
                        <label>Current PDF</label>
                        <a href="<?php echo e($editSummary['file_path']); ?>"
                           target="_blank" rel="noopener" class="current-file">
                            <i class="fa-regular fa-file-pdf"></i> View attached PDF
                        </a>
                    </div>
                <?php endif; ?>

                <!-- FILE UPLOAD -->
                <?php if (!$isDeleted): ?>
                    <div class="form-field">
                        <label for="summary_file">
                            <?php echo $editSummary ? 'Replace PDF' : 'Summary PDF'; ?>
                            <?php if ($editSummary): ?>
                                <span class="optional">(leave empty to keep the current file)</span>
                            <?php endif; ?>
                        </label>
                        <input type="file" id="summary_file" name="summary_file"
                               accept="application/pdf"
                               <?php echo $editSummary ? '' : 'required'; ?>>
                    </div>

                    <button type="submit" class="btn-primary">
                        <?php echo $editSummary ? 'Save changes' : 'Submit summary'; ?>
                    </button>
                <?php endif; ?>

            </form>

        </section>

    </div>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2026 M-LINK | Empowering Communities</p>
    </footer>

</main>

</body>
</html>