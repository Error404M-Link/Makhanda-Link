<?php 
    session_start();

    require_once "Error404Connection.php";
    require "detailsOfTheLoggedInUser.php";
    require "updateProfile.php";

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile settings</title>
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="dashboardSideBar.css">
    <style>

    </style>
</head>
<body>

    <input type="checkbox" id="sidebarToggle">
    <label for="sidebarToggle" class="hamburger">
        <span></span><span></span><span></span>
    </label>
    <aside class="sidebar">
        <nav class="navigation">
            <img src="MakanaLogo.svg" height="150" width="150" alt="M-link logo">
            <ul>
                <li><a href="#home-section">Home</a></li>
                <li><a href="#appointments-section">Appointments</a></li>
                <li><a href="#patient-details-section">Patients</a></li>
                <li><a href="#schedule-section">Schedule</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <button class="logout-btn"><a href="DoctorLoginPage.html">Logout</a></button>
        </div>
    </aside>

    <div class="settingsHeader">
        <h1>Profile</h1>
    </div>

    <?php if ($statusMessage): ?>
        <div class="notificationBanner <?php echo $statusType; ?>">
            <?php echo $statusMessage; ?>
        </div>
    <?php endif; ?>

    <div class="profileContainer">
        <!-- LEFT COLUMN -->
        <aside class="profileCardLeft">
            <div class="profileAvatarWrapper">
                <img src="<?php echo htmlspecialchars($userData['image_path'] ?? 'profile.jpg'); ?>" alt="Profile Picture" id="profileImage">
                <label for="imageUpload" class="editBadge">
                    <i class="fa-solid fa-pen"></i>
                </label>
            </div>
            
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
        <main class="profileCardRight">
            
            <!-- PERSONAL INFORMATION FORM -->
            <section id="personalInfoSection" class="tabSection">
                <h2>Personal Information</h2>
                <form id="profileForm" action="update_profile.php" method="POST" enctype="multipart/form-data" class="settingsForm">

                    <input type="file" id="imageUpload" name="profile_image" accept="image/*" class="fileInputHidden">

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

            <!-- PASSWORD CHANGE -->
            <section id="passwordSection" class="tabSection hiddenSection">
                <h2>Change Password</h2>
                <form id="passwordForm" action="change_password.php" method="POST" class="settingsForm">
                    <div class="formGrid">
                        <div class="inputGroup fullWidth">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="currentPassword" required>
                        </div>

                        <div class="inputGroup fullWidth">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="newPassword" required>
                        </div>

                        <div class="inputGroup fullWidth">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                        </div>
                    </div>

                    <div class="formActions">
                        <button type="button" id="btnDiscardPassword" class="btnDiscard">Cancel</button>
                        <button type="submit" class="btnSave">Update Password</button>
                    </div>
                </form>
            </section>

        </main>
    </div>

    <!-- PASSWORD VERIFICATION POPUP -->
    <div id="passwordModal" class="modalOverlay hiddenModal">
        <div class="modalContent">
            <h3>Confirm Changes</h3>
            <p>Please enter your account password to confirm these changes.</p>
            <input type="password" id="modalConfirmPassword" placeholder="Enter password" class="modalInput">
            <p id="modalError" class="modalErrorText hiddenError">Password is required.</p>
            <div class="modalActions">
                <button type="button" id="btnCancelModal" class="btnDiscard">Cancel</button>
                <button type="button" id="btnConfirmModal" class="btnSave">Confirm & Save</button>
            </div>
        </div>
    </div>

    <script src="profile_settings.js"></script>
    <script>
        
    </script>
</body>
</html>