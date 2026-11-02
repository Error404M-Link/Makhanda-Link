<?php


    require_once "Error404Connection.php";
    require_once "detailsOfTheLoggedInUser.php";

    $query = " SELECT 
                u.username,
                u.first_Name,
                u.surname,
                u.contact,
                u.User_role,
                u.address,
                u.ward_id,
                p.image_path
            FROM user u
            LEFT JOIN profile_images p ON u.profile_id = p.profile_id
            WHERE u.username = ?";

    $userData = [];

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $userData = $row;
        }
        $stmt->close();
    } else {
        die("Database Error: " . $conn->error);
    }

    /* ########### FETCH THE WARDS ############ */
    $wardQuery = "SELECT ward_id, ward_name, ward_number FROM ward ORDER BY ward_number ASC";
    $wardResult = $conn->query($wardQuery);
    $wards = [];

    if ($wardResult) {
        while ($ward = $wardResult->fetch_assoc()) {
            $wards[] = $ward;
        }
    }

    /* ##### MESSAGE PASSED VIA THE URL ######## */
    $statusMessage = '';
    $statusType = '';
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'success') {
            $statusMessage = 'Profile updated successfully!';
            $statusType = 'successNotification';
        } elseif ($_GET['status'] === 'pwd_success') {
            $statusMessage = 'Password updated successfully!';
            $statusType = 'successNotification';
        } elseif (isset($_GET['msg'])) {
            $statusMessage = htmlspecialchars($_GET['msg']);
            $statusType = 'errorNotification';
        }
    }

    // Highlights "Profile" in the sidebar
    $activePage = 'profile';

    // Name shown in the top bar and sidebar
    if (empty($fullName)) {
        $fullName = trim(($userData['first_Name'] ?? '') . ' ' . ($userData['surname'] ?? ''));
    }

    // Councillor's ward (same lookup as the dashboard)
    $ward_id = 0;
    if ($stmt = $conn->prepare("SELECT ward_id FROM ward_councillor WHERE username = ?")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $wardRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $ward_id = (int) ($wardRow['ward_id'] ?? 0);
    }

    // Top bar notification badge: tickets reported today in this ward
    $unreadNotificationCount = 0;
    if ($ward_id > 0) {
        $stmtNewReports = $conn->prepare("
            SELECT COUNT(*) AS new_report_count
            FROM incident_ticket
            WHERE ward_id = ?
            AND DATE(created_at) = CURDATE()
        ");

        if ($stmtNewReports) {
            $stmtNewReports->bind_param("i", $ward_id);
            $stmtNewReports->execute();
            $rowNewReports = $stmtNewReports->get_result()->fetch_assoc();
            $unreadNotificationCount = (int) ($rowNewReports['new_report_count'] ?? 0);
            $stmtNewReports->close();
        }
    }

    // Sidebar messages badge
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
        $unread_data  = $unread_stmt->get_result()->fetch_assoc();
        $unread_count = (int) ($unread_data['unread_count'] ?? 0);
        $unread_stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-LINK Profile Settings</title>

    <!-- Restores the sidebar state before the page draws (no flash) -->
    <script>
    try { if (localStorage.getItem("sidebarState") === "closed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}
    </script>

    <!-- Wardcouncillo.css first (layout, top bar, sidebar), settings.css second (this page's cards) -->
    <link rel="stylesheet" href="Wardcouncillo.css?v=<?php echo filemtime(__DIR__ . '/Wardcouncillo.css'); ?>">
    <link rel="stylesheet" href="settings.css?v=<?php echo filemtime(__DIR__ . '/settings.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="container">

    <!-- ================= TOP BAR ================= -->

    <header class="topbar">

        <!-- HAMBURGER -->
        <button class="menu-btn" id="menuBtn" type="button"
                aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="true">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- M-LINK LOGO -->
        <a href="logout.php" class="logo-link" title="M-LINK Homepage">
            <img src="makanaLogo.svg" alt="M-Link logo" class="topbar-logo">
        </a>

        <div class="top-right">

            <!-- NOTIFICATIONS -->
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

            <!-- MESSAGES -->
            <a href="messages.php" class="topbar-icon-btn" title="Messages">
                <img src="../icons/message.svg" alt="" class="topbar-icon-img">
                <span class="topbar-icon-label">Messages</span>
            </a>

            <!-- USER -->
            <div class="user">
                <img src="<?php echo htmlspecialchars($userData['image_path'] ?? 'profile.jpg'); ?>"
                     alt="Profile Picture" id="profileImage">
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($fullName); ?></h4>
                    <small>Ward Councillor</small>
                </div>
            </div>

        </div>
    </header>


    <!-- ================= SIDEBAR ================= -->

    <?php include 'ward_sidebar.php'; ?>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content" id="mainContent">

        <section class="page-heading">
            <div>
                <h1>Profile</h1>
                <p>Manage your personal information and password.</p>
            </div>
        </section>

        <?php if ($statusMessage): ?>
            <div class="notificationBanner <?php echo $statusType; ?>">
                <?php echo $statusMessage; ?>
            </div>
        <?php endif; ?>

        <div class="profileContainer">
            <!-- LEFT COLUMN -->
            <aside class="profileCardLeft">
                <div class="profileAvatarWrapper">
                    <img src="<?php echo htmlspecialchars($userData['image_path'] ?? 'profile.jpg'); ?>" alt="Profile Picture" id="settingsAvatar">
                    <label for="imageUpload" class="editBadge">
                        <i class="fa-solid fa-pen"></i>
                    </label>
                </div>

                <!--    STANDALONE FORM TO UPDATE THE PICTURE WITHOUT
                        NEEDING TO CONFIRM WITH THE PASSWORD 
                -->
                <form id="profileImageForm" action="updateProfile.php" method="POST" enctype="multipart/form-data" class="profileImageForm">
                    <input type="hidden" name="action" value="update_profile_picture">
                    <input type="file" id="imageUpload" name="profile_image" accept="image/*" class="fileInputHidden">

                    <div id="imageUploadActions" class="imageUploadActions hiddenSection">
                        <button type="button" id="btnCancelImage" class="btnDiscard btnSmall">Cancel</button>
                        <button type="submit" id="btnSaveImage" class="btnSave btnSmall">Save Picture</button>
                    </div>
                </form>
                
                <h3 class="userName" id="displayFullName">
                    <?php echo htmlspecialchars(($userData['first_Name'] ?? '') . ' ' . ($userData['surname'] ?? '')); ?>
                </h3>
                <p class="userRole" id="displayRole">
                    <?php echo htmlspecialchars($userData['User_role'] ?? 'User'); ?>
                </p>

                <nav class="profileNavMenu">
                    <a href="#personal-info" class="navItem active" id="navPersonalInfo">
                        <i class="fa-regular fa-user"></i>
                        <span>Personal Information</span>
                    </a>
                    <a href="#login-password" class="navItem" id="navPassword">
                        <i class="fa-solid fa-lock"></i>
                        <span>Password</span>
                    </a>
                </nav>
            </aside>

            <!-- ######## RIGHT COLUMN ######## -->
            <div class="profileCardRight">
                
                <!-- PERSONAL INFORMATION FORM -->
                <section id="personalInfoSection" class="tabSection">
                    <h2>Personal Information</h2>
                    <form id="profileForm" action="updateProfile.php" method="POST" enctype="multipart/form-data" class="settingsForm">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="formGrid">
                            <div class="inputGroup">
                                <label for="first_Name">First Name</label>
                                <input type="text" id="first_Name" name="first_Name" value="<?php echo htmlspecialchars($userData['first_Name'] ?? ''); ?>">
                            </div>

                            <div class="inputGroup">
                                <label for="surname">Surname</label>
                                <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($userData['surname'] ?? ''); ?>">
                            </div>

                            <div class="inputGroup fullWidth">
                                <label for="username">Username</label>
                                <div class="icon-input">
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" readonly class="readOnlyInput">
                                    <span class="verifiedTag"><i class="fa-solid fa-circle-check"></i> Verified</span>
                                </div>
                            </div>

                            <div class="inputGroup fullWidth">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
                            </div>

                            <div class="inputGroup">
                                <label for="contact">Phone Number</label>
                                <input type="tel" id="contact" name="contact" value="<?php echo htmlspecialchars($userData['contact'] ?? ''); ?>">
                            </div>

                            <div class="inputGroup">
                                <label for="ward_id">Ward / Location</label>
                                <select id="ward_id" name="ward_id">
                                    <option value="">Select Ward...</option>
                                    <?php foreach ($wards as $ward): ?>
                                        <option value="<?php echo $ward['ward_id']; ?>" <?php echo ($userData['ward_id'] == $ward['ward_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ward['ward_name'] . ' (Ward ' . $ward['ward_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- HIDDEN CONFIRM PASSWORD WINDOW -->
                        <input type="hidden" id="confirmPasswordInput" name="confirm_password">

                        <div class="formActions">
                            <button type="button" id="btnDiscardProfile" class="btnDiscard">Discard Changes</button>
                            <button type="button" id="btnTriggerModal" class="btnSave">Save Changes</button>
                        </div>
                    </form>
                </section>

                <!-- CHANGE PASSWORD -->
                <section id="passwordSection" class="tabSection hiddenSection">
                    <h2>Change Password</h2>
                    <form id="passwordForm" action="updateProfile.php" method="POST" class="settingsForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="formGrid">
                            <div class="inputGroup fullWidth">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="newPassword" required>
                            </div>

                            <div class="inputGroup fullWidth">
                                <label for="confirmNewPassword">Confirm New Password</label>
                                <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                            </div>
                        </div>

                        <!-- HIDDEN CONFIRM PASSWORD FIELD (used by the popup, mirrors the profile form) -->
                        <input type="hidden" id="confirmPasswordInputPwd" name="confirm_password">

                        <div class="formActions">
                            <button type="button" id="btnDiscardPassword" class="btnDiscard">Cancel</button>
                            <button type="button" id="btnTriggerPasswordModal" class="btnSave">Update Password</button>
                        </div>
                    </form>
                </section>

            </div>
        </div>


        <!-- FOOTER -->
        <footer>
            <p>
                © 2026 M-LINK | Empowering Communities
            </p>
        </footer>
    </main>
</div>

<!-- PASSWORD VERIFICATION POPUP -->
<div id="passwordModal" class="modalOverlay hiddenModal">
    <div class="modalContent">
        <h3>Confirm Changes</h3>
        <p>Please enter your account password to confirm these changes.</p>
        <input type="password" id="modalConfirmPassword" class="modalInput" placeholder="Enter password">
        <p id="modalError" class="modalErrorText hiddenError">Please enter your password to continue.</p>
        <div class="modalActions">
            <button type="button" id="btnCancelModal" class="btnDiscard">Cancel</button>
            <button type="button" id="btnConfirmModal" class="btnSave">Confirm & Save</button>
        </div>
    </div>
</div>

<script src="profile_settings.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // DOM Elements
    const personalInfoSection = document.getElementById("personalInfoSection");
    const passwordSection = document.getElementById("passwordSection");
    const navPersonalInfo = document.getElementById("navPersonalInfo");
    const navPassword = document.getElementById("navPassword");

    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const imageUploadInput = document.getElementById("imageUpload");
    // (renamed from "profileImage" so it no longer clashes with the top bar avatar)
    const profileImage = document.getElementById("settingsAvatar");
    
    const btnTriggerModal = document.getElementById("btnTriggerModal");
    const btnTriggerPasswordModal = document.getElementById("btnTriggerPasswordModal");
    const modal = document.getElementById("passwordModal");
    const modalConfirmBtn = document.getElementById("btnConfirmModal");
    const modalCancelBtn = document.getElementById("btnCancelModal");
    const modalPasswordInput = document.getElementById("modalConfirmPassword");
    const modalError = document.getElementById("modalError");
    const confirmPasswordInput = document.getElementById("confirmPasswordInput");
    const confirmPasswordInputPwd = document.getElementById("confirmPasswordInputPwd");

    // KEEP TRACK OF WHICH POPUP TO USE ONCE CONFIRMED => PROFILE OR PASSWORD
    let pendingAction = null;

    // CACHE INITIAL FORM VALUES BEFORE DISCARDING THE CHANGES
    const originalImageSrc = profileImage ? profileImage.src : "";
    const initialFormData = {
        firstName: document.getElementById("first_Name") ? document.getElementById("first_Name").value : "",
        surname: document.getElementById("surname") ? document.getElementById("surname").value : "",
        address: document.getElementById("address") ? document.getElementById("address").value : "",
        contact: document.getElementById("contact") ? document.getElementById("contact").value : "",
        ward_id: document.getElementById("ward_id") ? document.getElementById("ward_id").value : ""
    };

    // TAB NAVIGATION SWITCHING
    if (navPersonalInfo && navPassword) {
        navPersonalInfo.addEventListener("click", (e) => {
            e.preventDefault();
            navPersonalInfo.classList.add("active");
            navPassword.classList.remove("active");
            personalInfoSection.classList.remove("hiddenSection");
            passwordSection.classList.add("hiddenSection");
        });

        navPassword.addEventListener("click", (e) => {
            e.preventDefault();
            navPassword.classList.add("active");
            navPersonalInfo.classList.remove("active");
            passwordSection.classList.remove("hiddenSection");
            personalInfoSection.classList.add("hiddenSection");
        });
    }

    // REVIEW PROFILE PICTURE AND SAVE WITHOUT PASSWORD CONFIRMATION
    const profileImageForm = document.getElementById("profileImageForm");
    const imageUploadActions = document.getElementById("imageUploadActions");
    const btnCancelImage = document.getElementById("btnCancelImage");

    if (imageUploadInput && profileImage) {
        // UPLOAD AND READ THE IMAGE.
        imageUploadInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => { 
                    profileImage.src = event.target.result;
                };
                reader.readAsDataURL(file);

                if (imageUploadActions) imageUploadActions.classList.remove("hiddenSection");
            }
        });
    }

    if (btnCancelImage) {
        // CANCEL THE UPDATE
        btnCancelImage.addEventListener("click", () => {
            if (imageUploadInput) imageUploadInput.value = "";
            if (profileImage) profileImage.src = originalImageSrc;
            if (imageUploadActions) imageUploadActions.classList.add("hiddenSection");
        });
    }

    // DISCARD CHANGES
    const btnDiscardProfile = document.getElementById("btnDiscardProfile");
    if (btnDiscardProfile) {
        btnDiscardProfile.addEventListener("click", () => {
            if (document.getElementById("first_Name")) document.getElementById("first_Name").value = initialFormData.firstName;
            if (document.getElementById("surname")) document.getElementById("surname").value = initialFormData.surname;
            if (document.getElementById("address")) document.getElementById("address").value = initialFormData.address;
            if (document.getElementById("contact")) document.getElementById("contact").value = initialFormData.contact;
            if (document.getElementById("ward_id")) document.getElementById("ward_id").value = initialFormData.ward_id;
            
            if (profileImage) profileImage.src = originalImageSrc;
            if (imageUploadInput) imageUploadInput.value = "";
        });
    }

    const btnDiscardPassword = document.getElementById("btnDiscardPassword");
    if (btnDiscardPassword && passwordForm) {
        btnDiscardPassword.addEventListener("click", () => {
            passwordForm.reset();
        });
    }

    // SHOW CONFIRMATION => PROFILE SAVE
    if (btnTriggerModal && modal) {
        btnTriggerModal.addEventListener("click", (e) => {
            e.preventDefault();
            pendingAction = "profile";
            if (modalPasswordInput) modalPasswordInput.value = "";
            if (modalError) modalError.classList.add("hiddenError");
            modal.classList.remove("hiddenModal");
            if (modalPasswordInput) modalPasswordInput.focus();
        });
    }

    // SHOW CONFIRMATION MODAL (PASSWORD UPDATE)
    if (btnTriggerPasswordModal && modal) {
        btnTriggerPasswordModal.addEventListener("click", (e) => {
            e.preventDefault();

            // CHECK IF THE NEW PASSWORDS MATCH
            const newPwd = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
            const confirmPwd = document.getElementById("confirmNewPassword") ? document.getElementById("confirmNewPassword").value : "";
            if (newPwd !== confirmPwd) {
                alert("New passwords do not match.");
                return;
            }

            pendingAction = "password";
            if (modalPasswordInput) modalPasswordInput.value = "";
            if (modalError) modalError.classList.add("hiddenError");
            modal.classList.remove("hiddenModal");
            if (modalPasswordInput) modalPasswordInput.focus();
        });
    }

    // CLOSE MODAL (CANCEL BUTTON)
    if (modalCancelBtn && modal) {
        modalCancelBtn.addEventListener("click", () => {
            modal.classList.add("hiddenModal");
        });
    }

    // CLOSE MODAL (CLICK OUTSIDE OVERLAY)
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hiddenModal");
            }
        });
    }

    // CONFIRM PASSWORD & SUBMIT WHICHEVER FORM TRIGGERED THE MODAL
    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener("click", () => {
            const passwordValue = modalPasswordInput ? modalPasswordInput.value.trim() : "";
            
            if (!passwordValue) {
                if (modalError) modalError.classList.remove("hiddenError");
                return;
            }

            if (modal) {
                modal.classList.add("hiddenModal");
            }

            if (pendingAction === "profile") {
                if (confirmPasswordInput) confirmPasswordInput.value = passwordValue;
                if (profileForm) profileForm.submit();
            } else if (pendingAction === "password") {
                if (confirmPasswordInputPwd) confirmPasswordInputPwd.value = passwordValue;
                if (passwordForm) passwordForm.submit();
            }

            pendingAction = null;
        });
    }

    // ALLOW SUBMITTING MODAL WITH ENTER KEY
    if (modalPasswordInput) {
        modalPasswordInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                if (modalConfirmBtn) modalConfirmBtn.click();
            }
        });
    }

    // CHANGE PASSWORD TAB FORM VALIDATION
    if (passwordForm) {
        passwordForm.addEventListener("submit", (e) => {
            const newPwd = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
            const confirmPwd = document.getElementById("confirmNewPassword") ? document.getElementById("confirmNewPassword").value : "";

            if (newPwd !== confirmPwd) {
                e.preventDefault();
                alert("New passwords do not match.");
            }
        });
    }
});
</script>
 <!-- FOOTER -->
        <footer>
            <p>
                © 2026 M-LINK | Empowering Communities
            </p>
        </footer>
</body>
</html>