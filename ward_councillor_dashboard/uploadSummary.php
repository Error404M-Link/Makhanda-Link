<?php
/**
 * upload_summary.php
 * -------------------------------------------------------------
 * Lets a logged-in ward councillor, communication officer, or
 * admin submit a ward summary as a file (PDF).
 * -------------------------------------------------------------
 */

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once "Error404Connection.php"; // provides $conn (mysqli)

    /* #### VERIFY SESSION & DATABASE USER EXISTENCE FROM TABLES #### */
    $sessionUsername = $_SESSION['username'] ?? '';
    $sessionRole     = $_SESSION['role'] ?? '';
    
    $userRow = null;
    $isAuthorized = false;

    if (!empty($sessionUsername) && !empty($sessionRole)) {
        if ($sessionRole === 'admin') {
            $stmt = $conn->prepare("SELECT username FROM admin WHERE username = ?");
            $stmt->bind_param("s", $sessionUsername);
            $stmt->execute();
            $userRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($userRow) {
                $isAuthorized = true;
            }
        } elseif ($sessionRole === 'officer') {
            $stmt = $conn->prepare("SELECT username, division FROM municipal_staff WHERE username = ?");
            $stmt->bind_param("s", $sessionUsername);
            $stmt->execute();
            $userRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($userRow) {
                $isAuthorized = true;
            }
        } elseif ($sessionRole === 'councillor') {
            $stmt = $conn->prepare("SELECT username, ward_id FROM ward_councillor WHERE username = ?");
            $stmt->bind_param("s", $sessionUsername);
            $stmt->execute();
            $userRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($userRow) {
                if (isset($userRow['ward_id'])) {
                    $_SESSION['ward_id'] = $userRow['ward_id'];
                }
                $isAuthorized = true;
            }
        }
    }

    /* #### FETCH WARDS DYNAMICALLY FROM THE `ward` TABLE #### */
    $wards = [];
    $wardResult = $conn->query("SELECT ward_id, ward_name, ward_number FROM ward ORDER BY ward_number ASC");
    if ($wardResult) {
        while ($row = $wardResult->fetch_assoc()) {
            $wards[] = $row;
        }
    }

    const UPLOAD_DIR      = __DIR__ . '/uploads/ward_summaries/';
    const UPLOAD_URL_BASE = 'uploads/ward_summaries/';
    const MAX_FILE_BYTES  = 10 * 1024 * 1024; // 10MB

    $errors  = [];
    $success = false;

    // Councillors are locked to their own ward; everyone else picks one.
    $lockedWard = ($sessionRole === 'councillor') ? (int)($_SESSION['ward_id'] ?? 0) : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $title       = trim($_POST['title'] ?? '');
        $periodStart = trim($_POST['period_start'] ?? '');
        $periodEnd   = trim($_POST['period_end'] ?? '');
        $ward        = $lockedWard ?? (int)($_POST['ward_id'] ?? 0);

        // ---- Validate basic fields --------------------------------
        if ($title === '') {
            $errors[] = "Title is required.";
        }
        
        /* #### [CHANGED] VALIDATE WARD USING A LOOP INSTEAD OF array_column/in_array #### */
        $wardExists = false;
        foreach ($wards as $w) {
            if ((int)$w['ward_id'] === $ward) {
                $wardExists = true;
                break;
            }
        }
        if (!$wardExists) {
            $errors[] = "Please select a valid ward.";
        }

        if ($periodStart !== '' && $periodEnd !== '' && strtotime($periodStart) > strtotime($periodEnd)) {
            $errors[] = "Period start date must be before the period end date.";
        }

        // ---- Validate the uploaded file ----------------------------
        if (!isset($_FILES['summary_file']) || $_FILES['summary_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "Please attach a PDF file.";
        } elseif ($_FILES['summary_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "There was a problem uploading the file (error code "
                      . $_FILES['summary_file']['error'] . ").";
        } else {
            $file = $_FILES['summary_file'];

            if ($file['size'] > MAX_FILE_BYTES) {
                $errors[] = "File is too large. Maximum size is 10MB.";
            }

            /* #### SAFE MIME CHECK (AVOIDS FATAL finfo CLASS ERROR) #### */
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
                $errors[] = "Only PDF files are allowed (detected type: " . ($mimeType ?: 'unknown') . ").";
            }
        }

        // ---- Save it -------------------------------------------------
        if (empty($errors)) {

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            $safeName = bin2hex(random_bytes(16)) . '.pdf';
            $destPath = UPLOAD_DIR . $safeName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {

                $filePathForDb = UPLOAD_URL_BASE . $safeName;
                $fileSize      = filesize($destPath);
                $username      = $sessionUsername;

                $stmt = $conn->prepare(
                    "INSERT INTO ward_summaries
                        (ward_id, councillor_username, title, file_path,
                         file_size_bytes, mime_type, period_start, period_end)
                     VALUES (?, ?, ?, ?, ?, 'application/pdf', ?, ?)"
                );

                $periodStartVal = $periodStart !== '' ? $periodStart : null;
                $periodEndVal   = $periodEnd !== '' ? $periodEnd : null;

                $stmt->bind_param(
                    "isssiss",
                    $ward, $username, $title, $filePathForDb,
                    $fileSize, $periodStartVal, $periodEndVal
                );

                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = "Could not save the summary record. Please try again.";
                    unlink($destPath);
                }
                $stmt->close();

            } else {
                $errors[] = "Could not move the uploaded file. Check folder permissions.";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Ward Summary | M-Link</title>
    <link rel="stylesheet" href="ward_summaries.css">
</head>
<body>

<main class="container" style="max-width:600px;">

    <h2>Submit a Ward Summary</h2>
    <p>Logged in as <strong><?php echo htmlspecialchars($sessionUsername, ENT_QUOTES, 'UTF-8'); ?></strong>
        (<?php echo htmlspecialchars($sessionRole, ENT_QUOTES, 'UTF-8'); ?>)
        &middot; <a href="logout.php">Log out</a>
    </p>

    <?php if ($success): ?>
        <p style="color:green; font-weight:bold;">
            Summary uploaded successfully.
        </p>
        <p><a href="ward_summaries.php?ward=<?php echo $ward; ?>">View it on the ward page &rarr;</a></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul style="color:#b00020;">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="uploadSummary.php" enctype="multipart/form-data">

        <label for="title">Title</label><br>
        <input type="text" id="title" name="title" required
               value="<?php echo htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
               style="width:100%;"><br><br>

        <label for="ward_id">Ward</label><br>
        <?php if ($lockedWard !== null && $lockedWard > 0): ?>
            <?>
                $lockedWardName = "Ward " . $lockedWard;
                foreach ($wards as $w) {
                    if ((int)$w['ward_id'] === $lockedWard) {
                        $lockedWardName = $w['ward_name'] ?? ("Ward " . $w['ward_number']);
                        break;
                    }
                }
            ?>
            <input type="text" value="<?php echo htmlspecialchars($lockedWardName, ENT_QUOTES, 'UTF-8'); ?>" disabled style="width:100%;">
            <input type="hidden" name="ward_id" value="<?php echo $lockedWard; ?>">
        <?php else: ?>
            <select id="ward_id" name="ward_id" required>
                <option value="">-- Select a ward --</option>
                <?php foreach ($wards as $w): ?>
                    <option value="<?php echo (int)$w['ward_id']; ?>"
                        <?php echo (isset($_POST['ward_id']) && (int)$_POST['ward_id'] === (int)$w['ward_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($w['ward_name'] ?? ('Ward ' . $w['ward_number']), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <br><br>

        <label for="period_start">Period start (optional)</label><br>
        <input type="date" id="period_start" name="period_start"
               value="<?php echo htmlspecialchars($_POST['period_start'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

        <label for="period_end">Period end (optional)</label><br>
        <input type="date" id="period_end" name="period_end"
               value="<?php echo htmlspecialchars($_POST['period_end'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

        <label for="summary_file">Summary PDF</label><br>
        <input type="file" id="summary_file" name="summary_file" accept="application/pdf" required><br><br>

        <button type="submit">Submit Summary</button>

    </form>

</main>

</body>
</html>