<?php
    session_start();
    require_once "../Error404Connection.php";

    $loggedInUser = $_SESSION['username'] ?? null;
    $userRole     = $_SESSION['User_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;

    // Roles allowed to create/manage notices (admins + municipal comms staff)
    $allowedCreationRoles = ['System Administrator',
                            'Admin',
                            'Communications Officer',
                            'Municipal Communications Officer',
                            'Ward Councillor'];

    // Must both be logged in AND hold an allowed role — no default fallback role.
    $canCreateNotice = !empty($loggedInUser) && in_array($userRole, $allowedCreationRoles, true);

    // FETCH THE CATEGORIES IN THE DATABASE
    $categories = [];
    $catResult = $conn->query("SELECT DISTINCT service_type 
                                FROM municipal_service 
                                ORDER BY service_type ASC");
    if ($catResult) {
        while ($row = $catResult->fetch_assoc()) {
            $categories[] = $row['service_type'];
        }
    }

    // FETCH THE WARDS FROM THE DATABASE
    $wards = [];
    $wardResult = $conn->query("SELECT ward_id, ward_name, ward_number 
                                FROM ward 
                                ORDER BY ward_number ASC");
    if ($wardResult) {
        while ($row = $wardResult->fetch_assoc()) {
            $wards[] = $row;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'create_notice') {
        
        // VERIFY THE ROLE PERMISSIONS
        if (!$canCreateNotice) {
            die("Access Denied");
        }

        $title       = trim($_POST['notice_title'] ?? '');
        $category    = trim($_POST['notice_category'] ?? '');
        $ward        = trim($_POST['ward'] ?? 'Global');
        $publishDate = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
        $expiryDate  = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $description = trim($_POST['notice_description'] ?? '');

        // AUTO APPROVE IF CREATED BY ADMIN, OTHERWISE PENDING FOR COMMUNICATIONS OFFICER
        if (in_array($userRole, ['System Administrator', 'Admin'])) {
            $status = 'Active';
        } else {
            $status = 'Pending';
        }

        // GUARD AGAINST FK CONTRAINTS
        if (empty($loggedInUser)) {
            die("Error: Session expired or unauthenticated user. Please log in first.");
        }

        if (!empty($title) && !empty($description)) {
            $stmt = $conn->prepare("INSERT INTO notices (username, ward, notice_title, notice_category, publish_date, expiry_date, status, notice_description, date_posted) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("ssssssss", $loggedInUser, $ward, $title, $category, $publishDate, $expiryDate, $status, $description);
            
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: manageContent.php?msg=created");
                exit();
            } else {
                die("Database Error: " . $stmt->error);
            }
        }
    }

    // UPDATES THE STATUS OF THE NOTICE. 
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $noticeId  = (int)$_GET['id'];
        $newStatus = '';

        if ($_GET['action'] === 'approve') $newStatus = 'Active';
        if ($_GET['action'] === 'archive') $newStatus = 'Archived';
        if ($_GET['action'] === 'restore') $newStatus = 'Active';

        if ($newStatus && $noticeId > 0) {
            $stmt = $conn->prepare("UPDATE notices SET status = ? 
                                    WHERE notice_id = ?");

            $stmt->bind_param("si", $newStatus, $noticeId);
            $stmt->execute();
            $stmt->close();

            header("Location: manageContent.php?msg=updated");
            exit();
        }
    }

    // FETCH ALL THE NOTICES CREATED BY THE USER LOGGED IN
    $notices = [];
    $sql = "SELECT n.*, u.first_Name, u.surname 
            FROM notices n 
            LEFT JOIN user u ON n.username = u.username 
            ORDER BY n.date_posted DESC";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row;
        }
    }

    // SUMMARY COUNTERS

    $activeCount   = 0;
    $pendingCount  = 0;
    $archivedCount = 0;

    foreach ($notices as $n) {
        if ($n['status'] === 'Active') $activeCount++;
        elseif ($n['status'] === 'Pending' || $n['status'] === 'Draft') $pendingCount++;
        elseif ($n['status'] === 'Archived') $archivedCount++;
    }

    // Selected Notice Inspector
    $selectedNotice = null;
    if (isset($_GET['inspect_id'])) {
        $inspectId = (int)$_GET['inspect_id'];
        foreach ($notices as $n) {
            if ((int)$n['notice_id'] === $inspectId) {
                $selectedNotice = $n;
                break;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Management</title>
    <link rel="stylesheet" href="manageContent.css">
    <link rel="stylesheet" href="dashboardSideBar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="appContainer dashboardContainer">

        <input type="checkbox" id="sidebarToggle">
        <label for="sidebarToggle" class="hamburger">
            <span></span><span></span><span></span>
        </label>

        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav class="navigation">
                <img src="MakanaLogo.svg" height="150" width="150" alt="M-Link logo">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manageContent.php" class="active">Notices</a></li>
                    <li><a href="manageTickets.php">Fault Tickets</a></li>
                    <li><a href="manageUsers.php">User Management</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <button class="logout-btn"><a href="logout.php">Logout</a></button>
            </div>
        </aside>

        <!-- ################## MAIN CONTENT ###################### -->

        <main class="mainContent">
            <header class="topHeader">
                <h1>Notice Management</h1>
                <!-- <div class="userProfile">
                    <i class="fa-solid fa-bell"></i>
                    <div class="avatar"><i class="fa-solid fa-user-shield"></i></div>
                    <span><?= htmlspecialchars($loggedInUser ?? 'Guest') ?></span>
                </div> -->
            </header>

            <section class="metricsGrid">
                <div class="metricCard">
                    <div class="metricIcon blue"><i class="fa-solid fa-paper-plane"></i></div>
                    <div>
                        <h3><?= $activeCount ?></h3>
                        <p>Active Notices</p>
                    </div>
                </div>
                <div class="metricCard">
                    <div class="metricIcon orange"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <h3><?= $pendingCount ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                </div>
                <div class="metricCard">
                    <div class="metricIcon red"><i class="fa-solid fa-box-archive"></i></div>
                    <div>
                        <h3><?= $archivedCount ?></h3>
                        <p>Archived</p>
                    </div>
                </div>
                <?php if ($canCreateNotice): ?>
                    <button class="btn btnPrimary btnLg" id="btnOpenModal">
                        <i class="fa-solid fa-plus"></i> Create New Notice
                    </button>
                <?php endif; ?>
            </section>

            <!-- Content Workspace -->
            <div class="contentWorkspace">
                <!-- Table View -->
                <section class="tableContainer">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Scope</th>
                                <th>Publish Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notices)): ?>
                                <tr><td colspan="7" style="text-align:center;">No notices found in database.</td></tr>
                            <?php else: ?>
                                <?php foreach ($notices as $n): ?>
                                    <tr onclick="window.location.href='manageContent.php?inspect_id=<?= $n['notice_id'] ?>'" style="cursor: pointer;">
                                        <td><strong><?= htmlspecialchars($n['notice_title']) ?></strong></td>
                                        <td><?= htmlspecialchars($n['notice_category']) ?></td>
                                        <td><?= htmlspecialchars($n['ward'] ?? 'Global') ?></td>
                                        <td><?= $n['publish_date'] ? date('Y-m-d H:i', strtotime($n['publish_date'])) : '-' ?></td>
                                        <td><?= $n['expiry_date'] ? date('Y-m-d H:i', strtotime($n['expiry_date'])) : '-' ?></td>
                                        <td><span class="badge badge-<?= strtolower($n['status']) ?>"><?= htmlspecialchars($n['status']) ?></span></td>
                                        <td>
                                            <a href="manageContent.php?inspect_id=<?= $n['notice_id'] ?>" class="btn btnSecondary" onclick="event.stopPropagation();">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>

                <!-- INSPECTION PANEL  -->
                <aside class="detailsPanel">
                    <h3><i class="fa-solid fa-circle-info"></i> Notice Details</h3>
                    <?php if ($selectedNotice): ?>
                        <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top: 1rem;">
                            <h4><?= htmlspecialchars($selectedNotice['notice_title']) ?></h4>
                            <p><strong>Author:</strong> 
                                <?= htmlspecialchars(($selectedNotice['first_Name'] ?? '') . ' ' . ($selectedNotice['surname'] ?? '')) ?> 
                                (<?= htmlspecialchars($selectedNotice['username'] ?? 'System') ?>)
                            </p>
                            <p><strong>Category:</strong> <?= htmlspecialchars($selectedNotice['notice_category']) ?></p>
                            <p><strong>Target Scope:</strong> <?= htmlspecialchars($selectedNotice['ward'] ?? 'Global') ?></p>
                            <p><strong>Published:</strong> <?= date('Y-m-d H:i', strtotime($selectedNotice['publish_date'])) ?></p>
                            <p><strong>Expires:</strong> <?= date('Y-m-d H:i', strtotime($selectedNotice['expiry_date'])) ?></p>
                            <p><strong>Body:</strong> <?= nl2br(htmlspecialchars($selectedNotice['notice_description'])) ?></p>
                            <hr style="border-top:1px solid #ccc; margin: 0.5rem 0;">

                            <!-- ADMIN WORKFLOW BUTTONS -->
                            <?php if (in_array($userRole, ['System Administrator', 'Admin'])): ?>
                                <?php if ($selectedNotice['status'] === 'Pending' || $selectedNotice['status'] === 'Draft'): ?>
                                    <a href="manageContent.php?action=approve&id=<?= $selectedNotice['notice_id'] ?>" class="btn btnSuccess">
                                        <i class="fa-solid fa-check"></i> Approve & Publish
                                    </a>
                                <?php endif; ?>

                                <?php if ($selectedNotice['status'] !== 'Archived'): ?>
                                    <a href="manageContent.php?action=archive&id=<?= $selectedNotice['notice_id'] ?>" class="btn btnDanger">
                                        <i class="fa-solid fa-box-archive"></i> Archive Notice
                                    </a>
                                <?php else: ?>
                                    <a href="manageContent.php?action=restore&id=<?= $selectedNotice['notice_id'] ?>" class="btn btnSecondary">
                                        <i class="fa-solid fa-rotate-left"></i> Restore Notice
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="inspectorEmpty">
                            <p>Select a notice from the table to view details.</p>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </main>
    </div>

    <?php if ($canCreateNotice): ?>
        <!-- FORM FOR CREATING A NEW NOTICE -->
        <div class="modalBackdrop" id="noticeModal" style="display: none;">
            <div class="modal">
                <div class="modalHeader">
                    <h3>Create New Municipal Notice</h3>
                    <button class="closeBtn" id="btnCloseModal">&times;</button>
                </div>
                <form method="POST" action="manageContent.php">
                    <input type="hidden" name="action" value="create_notice">
                    <div class="formGroup">
                        <label for="noticeTitle">Notice Title</label>
                        <input type="text" name="notice_title" id="noticeTitle" required placeholder="e.g., Scheduled Water Interruption">
                    </div>
                    <div class="formRow">
                        <div class="formGroup">
                            <label for="noticeCategory">Category</label>
                            <select id="noticeCategory" name="notice_category" required>
                                <option value="" disabled selected>Select Category</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="formGroup">
                            <label for="noticeScope">Target Scope</label>
                            <select id="noticeScope" name="ward" required>
                                <option value="Global">Community-Wide (Global)</option>
                                <?php foreach ($wards as $w): ?>
                                    <?php 
                                        $wardValue = !empty($w['ward_name']) ? $w['ward_name'] : 'Ward ' . $w['ward_number'];
                                        $wardLabel = 'Ward ' . $w['ward_number'] . (!empty($w['ward_name']) ? ' (' . $w['ward_name'] . ')' : '');
                                    ?>
                                    <option value="<?= htmlspecialchars($wardValue) ?>">
                                        <?= htmlspecialchars($wardLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="formRow">
                        <div class="formGroup">
                            <label for="noticePublish">Publish Date/Time</label>
                            <input type="datetime-local" id="noticePublish" name="publish_date" required>
                        </div>
                        <div class="formGroup">
                            <label for="noticeExpiry">Expiration Date/Time</label>
                            <input type="datetime-local" id="noticeExpiry" name="expiry_date" required>
                        </div>
                    </div>
                    <div class="formGroup">
                        <label for="noticeBody">Notice Body</label>
                        <textarea id="noticeBody" name="notice_description" rows="4" required placeholder="Enter full announcement details"></textarea>
                    </div>
                    <div class="modalFooter">
                        <button type="button" class="btn btnSecondary" id="btnCancelModal">Cancel</button>
                        <button type="submit" class="btn btnPrimary">Save & Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CREATE NOTICE MODAL DISPLAY -->
        <script>
            const modal = document.getElementById("noticeModal");
            const btnOpen = document.getElementById("btnOpenModal");
            if (btnOpen) {
                btnOpen.onclick = () => modal.style.display = "flex";
            }
            document.getElementById("btnCloseModal").onclick = () => modal.style.display = "none";
            document.getElementById("btnCancelModal").onclick = () => modal.style.display = "none";
        </script>
    <?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>