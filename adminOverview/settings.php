<?php

session_start();

require_once "Error404Connection.php";

//check if administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$username = $_SESSION["username"];

//check administrator role
$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);

if ($userRole !== "admin") {

    die("Access denied.");

}

//get administrator information
$query = "
    SELECT
        u.username,
        u.first_Name,
        u.surname,
        u.contact,
        u.User_role,
        u.address,
        u.ward_id,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
";

$userData = [];

$stmt = $conn->prepare($query);

if (!$stmt) {

    die("Database Error: " . $conn->error);

}

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {

    $userData = $row;

}

$stmt->close();

//administrator information for sidebar and topbar
$firstName = $userData["first_Name"] ?? "";
$surname = $userData["surname"] ?? "";

$adminName = trim(
    $firstName . " " . $surname
);

if ($adminName === "") {

    $adminName = $username;

}

$adminRole = "System Admin";

$profileImage = trim(
    $userData["image_path"] ?? ""
);

//get wards
$wardQuery = "
    SELECT
        ward_id,
        ward_name,
        ward_number
    FROM ward
    ORDER BY ward_number ASC
";

$wardResult = $conn->query($wardQuery);

$wards = [];

if ($wardResult) {

    while ($ward = $wardResult->fetch_assoc()) {

        $wards[] = $ward;

    }

}

//message passed through URL
$statusMessage = "";
$statusType = "";

if (isset($_GET["status"])) {

    if ($_GET["status"] === "success") {

        $statusMessage = "Profile updated successfully!";
        $statusType = "successNotification";

    } elseif ($_GET["status"] === "pwd_success") {

        $statusMessage = "Password updated successfully!";
        $statusType = "successNotification";

    } elseif (isset($_GET["msg"])) {

        $statusMessage = htmlspecialchars(
            $_GET["msg"],
            ENT_QUOTES,
            "UTF-8"
        );

        $statusType = "errorNotification";

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Profile | M-LINK Admin</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="settings.css"
    >

    <style>

        body {

            min-height: 100vh;

            font-family: "Ubuntu", sans-serif;

            background: var(--cream);

            color: var(--text);

        }

        a {

            text-decoration: none;

        }

        button,
        input,
        select,
        textarea {

            font-family: "Ubuntu", sans-serif;

        }

        /* =========================================
           PROFILE PAGE WRAPPER
        ========================================= */

        .profile-page-content {

            padding: 35px;

        }

        @media (max-width: 750px) {

            .profile-page-content {

                padding: 25px 18px;

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
$adminActivePage = "profile";
require __DIR__ . "/adminHeader.php";
?>

        <!-- =====================================
             EXISTING PROFILE PAGE
        ====================================== -->

        <div class="profile-page-content">

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

                        <img
                            src="<?php echo htmlspecialchars(
                                $userData["image_path"] ?? "profile.jpg"
                            ); ?>"
                            alt="Profile Picture"
                            id="profileImage"
                        >

                        <label
                            for="imageUpload"
                            class="editBadge"
                        >

                            <i class="fa-solid fa-pen"></i>

                        </label>

                    </div>

                    <!-- PROFILE PICTURE FORM -->

                    <form
                        id="profileImageForm"
                        action="updateProfile.php"
                        method="POST"
                        enctype="multipart/form-data"
                        class="profileImageForm"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="update_profile_picture"
                        >

                        <input
                            type="file"
                            id="imageUpload"
                            name="profile_image"
                            accept="image/*"
                            class="fileInputHidden"
                        >

                        <div
                            id="imageUploadActions"
                            class="imageUploadActions hiddenSection"
                        >

                            <button
                                type="button"
                                id="btnCancelImage"
                                class="btnDiscard btnSmall"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                id="btnSaveImage"
                                class="btnSave btnSmall"
                            >
                                Save Picture
                            </button>

                        </div>

                    </form>

                    <h3
                        class="userName"
                        id="displayFullName"
                    >

                        <?php
                            echo htmlspecialchars(
                                ($userData["first_Name"] ?? "")
                                . " "
                                . ($userData["surname"] ?? "")
                            );
                        ?>

                    </h3>

                    <p
                        class="userRole"
                        id="displayRole"
                    >

                        <?php
                            echo htmlspecialchars(
                                $userData["User_role"] ?? "Admin"
                            );
                        ?>

                    </p>

                    <nav class="profileNavMenu">

                        <a
                            href="#personal-info"
                            class="navItem active"
                            id="navPersonalInfo"
                        >

                            <i class="fa-regular fa-user"></i>

                            <span>
                                Personal Information
                            </span>

                        </a>

                        <a
                            href="#login-password"
                            class="navItem"
                            id="navPassword"
                        >

                            <i class="fa-solid fa-lock"></i>

                            <span>
                                Password
                            </span>

                        </a>

                    </nav>

                </aside>

                <!-- RIGHT COLUMN -->

                <main class="profileCardRight">

                    <!-- PERSONAL INFORMATION -->

                    <section
                        id="personalInfoSection"
                        class="tabSection"
                    >

                        <h2>
                            Personal Information
                        </h2>

                        <form
                            id="profileForm"
                            action="updateProfile.php"
                            method="POST"
                            enctype="multipart/form-data"
                            class="settingsForm"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="update_profile"
                            >

                            <div class="formGrid">

                                <div class="inputGroup">

                                    <label for="first_Name">
                                        First Name
                                    </label>

                                    <input
                                        type="text"
                                        id="first_Name"
                                        name="first_Name"
                                        value="<?php echo htmlspecialchars(
                                            $userData["first_Name"] ?? ""
                                        ); ?>"
                                    >

                                </div>

                                <div class="inputGroup">

                                    <label for="surname">
                                        Surname
                                    </label>

                                    <input
                                        type="text"
                                        id="surname"
                                        name="surname"
                                        value="<?php echo htmlspecialchars(
                                            $userData["surname"] ?? ""
                                        ); ?>"
                                    >

                                </div>

                                <div class="inputGroup fullWidth">

                                    <label for="username">
                                        Username
                                    </label>

                                    <div class="icon-input">

                                        <input
                                            type="text"
                                            id="username"
                                            name="username"
                                            value="<?php echo htmlspecialchars(
                                                $userData["username"] ?? ""
                                            ); ?>"
                                            readonly
                                            class="readOnlyInput"
                                        >

                                        <span class="verifiedTag">

                                            <i class="fa-solid fa-circle-check"></i>

                                            Verified

                                        </span>

                                    </div>

                                </div>

                                <div class="inputGroup fullWidth">

                                    <label for="address">
                                        Address
                                    </label>

                                    <input
                                        type="text"
                                        id="address"
                                        name="address"
                                        value="<?php echo htmlspecialchars(
                                            $userData["address"] ?? ""
                                        ); ?>"
                                    >

                                </div>

                                <div class="inputGroup">

                                    <label for="contact">
                                        Phone Number
                                    </label>

                                    <input
                                        type="tel"
                                        id="contact"
                                        name="contact"
                                        value="<?php echo htmlspecialchars(
                                            $userData["contact"] ?? ""
                                        ); ?>"
                                    >

                                </div>

                                <div class="inputGroup">

                                    <label for="ward_id">
                                        Ward / Location
                                    </label>

                                    <select
                                        id="ward_id"
                                        name="ward_id"
                                    >

                                        <option value="">
                                            Select Ward...
                                        </option>

                                        <?php foreach ($wards as $ward): ?>

                                            <option
                                                value="<?php echo $ward["ward_id"]; ?>"
                                                <?php
                                                    echo (
                                                        $userData["ward_id"]
                                                        == $ward["ward_id"]
                                                    )
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >

                                                <?php
                                                    echo htmlspecialchars(
                                                        $ward["ward_name"]
                                                        . " (Ward "
                                                        . $ward["ward_number"]
                                                        . ")"
                                                    );
                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>

                            <input
                                type="hidden"
                                id="confirmPasswordInput"
                                name="confirm_password"
                            >

                            <div class="formActions">

                                <button
                                    type="button"
                                    id="btnDiscardProfile"
                                    class="btnDiscard"
                                >
                                    Discard Changes
                                </button>

                                <button
                                    type="button"
                                    id="btnTriggerModal"
                                    class="btnSave"
                                >
                                    Save Changes
                                </button>

                            </div>

                        </form>

                    </section>

                    <!-- CHANGE PASSWORD -->

                    <section
                        id="passwordSection"
                        class="tabSection hiddenSection"
                    >

                        <h2>
                            Change Password
                        </h2>

                        <form
                            id="passwordForm"
                            action="updateProfile.php"
                            method="POST"
                            class="settingsForm"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="change_password"
                            >

                            <div class="formGrid">

                                <div class="inputGroup fullWidth">

                                    <label for="newPassword">
                                        New Password
                                    </label>

                                    <input
                                        type="password"
                                        id="newPassword"
                                        name="newPassword"
                                        required
                                    >

                                </div>

                                <div class="inputGroup fullWidth">

                                    <label for="confirmNewPassword">
                                        Confirm New Password
                                    </label>

                                    <input
                                        type="password"
                                        id="confirmNewPassword"
                                        name="confirmNewPassword"
                                        required
                                    >

                                </div>

                            </div>

                            <input
                                type="hidden"
                                id="confirmPasswordInputPwd"
                                name="confirm_password"
                            >

                            <div class="formActions">

                                <button
                                    type="button"
                                    id="btnDiscardPassword"
                                    class="btnDiscard"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    id="btnTriggerPasswordModal"
                                    class="btnSave"
                                >
                                    Update Password
                                </button>

                            </div>

                        </form>

                    </section>

                </main>

            </div>

        </div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<!-- =====================================
     PASSWORD CONFIRMATION MODAL
====================================== -->

<div
    id="passwordModal"
    class="modalOverlay hiddenModal"
>

    <div class="modalContent">

        <h3>
            Confirm Changes
        </h3>

        <p>
            Please enter your account password to confirm these changes.
        </p>

        <input
            type="password"
            id="modalConfirmPassword"
            class="modalInput"
            placeholder="Enter password"
        >

        <p
            id="modalError"
            class="modalErrorText hiddenError"
        >
            Please enter your password to continue.
        </p>

        <div class="modalActions">

            <button
                type="button"
                id="btnCancelModal"
                class="btnDiscard"
            >
                Cancel
            </button>

            <button
                type="button"
                id="btnConfirmModal"
                class="btnSave"
            >
                Confirm & Save
            </button>

        </div>

    </div>

</div>

<script src="profile_settings.js"></script>

<script>

document.addEventListener("DOMContentLoaded", () => {

    // =========================================
    // PROFILE PAGE ELEMENTS
    // =========================================

    const personalInfoSection =
        document.getElementById("personalInfoSection");

    const passwordSection =
        document.getElementById("passwordSection");

    const navPersonalInfo =
        document.getElementById("navPersonalInfo");

    const navPassword =
        document.getElementById("navPassword");

    const profileForm =
        document.getElementById("profileForm");

    const passwordForm =
        document.getElementById("passwordForm");

    const imageUploadInput =
        document.getElementById("imageUpload");

    const profileImage =
        document.getElementById("profileImage");

    const btnTriggerModal =
        document.getElementById("btnTriggerModal");

    const btnTriggerPasswordModal =
        document.getElementById("btnTriggerPasswordModal");

    const modal =
        document.getElementById("passwordModal");

    const modalConfirmBtn =
        document.getElementById("btnConfirmModal");

    const modalCancelBtn =
        document.getElementById("btnCancelModal");

    const modalPasswordInput =
        document.getElementById("modalConfirmPassword");

    const modalError =
        document.getElementById("modalError");

    const confirmPasswordInput =
        document.getElementById("confirmPasswordInput");

    const confirmPasswordInputPwd =
        document.getElementById("confirmPasswordInputPwd");

    let pendingAction = null;

    // =========================================
    // SAVE ORIGINAL FORM VALUES
    // =========================================

    const originalImageSrc =
        profileImage
            ? profileImage.src
            : "";

    const initialFormData = {

        firstName:
            document.getElementById("first_Name")
                ? document.getElementById("first_Name").value
                : "",

        surname:
            document.getElementById("surname")
                ? document.getElementById("surname").value
                : "",

        address:
            document.getElementById("address")
                ? document.getElementById("address").value
                : "",

        contact:
            document.getElementById("contact")
                ? document.getElementById("contact").value
                : "",

        ward_id:
            document.getElementById("ward_id")
                ? document.getElementById("ward_id").value
                : ""

    };

    // =========================================
    // PROFILE TABS
    // =========================================

    if (navPersonalInfo && navPassword) {

        navPersonalInfo.addEventListener(
            "click",
            (e) => {

                e.preventDefault();

                navPersonalInfo.classList.add("active");

                navPassword.classList.remove("active");

                personalInfoSection.classList.remove(
                    "hiddenSection"
                );

                passwordSection.classList.add(
                    "hiddenSection"
                );

            }
        );

        navPassword.addEventListener(
            "click",
            (e) => {

                e.preventDefault();

                navPassword.classList.add("active");

                navPersonalInfo.classList.remove("active");

                passwordSection.classList.remove(
                    "hiddenSection"
                );

                personalInfoSection.classList.add(
                    "hiddenSection"
                );

            }
        );

    }

    // =========================================
    // PROFILE PICTURE
    // =========================================

    const profileImageForm =
        document.getElementById("profileImageForm");

    const imageUploadActions =
        document.getElementById("imageUploadActions");

    const btnCancelImage =
        document.getElementById("btnCancelImage");

    if (imageUploadInput && profileImage) {

        imageUploadInput.addEventListener(
            "change",
            (e) => {

                const file = e.target.files[0];

                if (file) {

                    const reader =
                        new FileReader();

                    reader.onload = (event) => {

                        profileImage.src =
                            event.target.result;

                    };

                    reader.readAsDataURL(file);

                    if (imageUploadActions) {

                        imageUploadActions.classList.remove(
                            "hiddenSection"
                        );

                    }

                }

            }
        );

    }

    if (btnCancelImage) {

        btnCancelImage.addEventListener(
            "click",
            () => {

                if (imageUploadInput) {

                    imageUploadInput.value = "";

                }

                if (profileImage) {

                    profileImage.src =
                        originalImageSrc;

                }

                if (imageUploadActions) {

                    imageUploadActions.classList.add(
                        "hiddenSection"
                    );

                }

            }
        );

    }

    // =========================================
    // DISCARD PROFILE CHANGES
    // =========================================

    const btnDiscardProfile =
        document.getElementById("btnDiscardProfile");

    if (btnDiscardProfile) {

        btnDiscardProfile.addEventListener(
            "click",
            () => {

                if (document.getElementById("first_Name")) {

                    document.getElementById("first_Name").value =
                        initialFormData.firstName;

                }

                if (document.getElementById("surname")) {

                    document.getElementById("surname").value =
                        initialFormData.surname;

                }

                if (document.getElementById("address")) {

                    document.getElementById("address").value =
                        initialFormData.address;

                }

                if (document.getElementById("contact")) {

                    document.getElementById("contact").value =
                        initialFormData.contact;

                }

                if (document.getElementById("ward_id")) {

                    document.getElementById("ward_id").value =
                        initialFormData.ward_id;

                }

                if (profileImage) {

                    profileImage.src =
                        originalImageSrc;

                }

                if (imageUploadInput) {

                    imageUploadInput.value = "";

                }

            }
        );

    }

    // =========================================
    // CANCEL PASSWORD CHANGE
    // =========================================

    const btnDiscardPassword =
        document.getElementById("btnDiscardPassword");

    if (
        btnDiscardPassword &&
        passwordForm
    ) {

        btnDiscardPassword.addEventListener(
            "click",
            () => {

                passwordForm.reset();

            }
        );

    }

    // =========================================
    // PROFILE SAVE MODAL
    // =========================================

    if (
        btnTriggerModal &&
        modal
    ) {

        btnTriggerModal.addEventListener(
            "click",
            (e) => {

                e.preventDefault();

                pendingAction = "profile";

                if (modalPasswordInput) {

                    modalPasswordInput.value = "";

                }

                if (modalError) {

                    modalError.classList.add(
                        "hiddenError"
                    );

                }

                modal.classList.remove(
                    "hiddenModal"
                );

                if (modalPasswordInput) {

                    modalPasswordInput.focus();

                }

            }
        );

    }

    // =========================================
    // PASSWORD CHANGE MODAL
    // =========================================

    if (
        btnTriggerPasswordModal &&
        modal
    ) {

        btnTriggerPasswordModal.addEventListener(
            "click",
            (e) => {

                e.preventDefault();

                const newPwd =
                    document.getElementById("newPassword")
                        ? document.getElementById("newPassword").value
                        : "";

                const confirmPwd =
                    document.getElementById("confirmNewPassword")
                        ? document.getElementById("confirmNewPassword").value
                        : "";

                if (newPwd !== confirmPwd) {

                    alert(
                        "New passwords do not match."
                    );

                    return;

                }

                pendingAction = "password";

                if (modalPasswordInput) {

                    modalPasswordInput.value = "";

                }

                if (modalError) {

                    modalError.classList.add(
                        "hiddenError"
                    );

                }

                modal.classList.remove(
                    "hiddenModal"
                );

                if (modalPasswordInput) {

                    modalPasswordInput.focus();

                }

            }
        );

    }

    // =========================================
    // CLOSE PASSWORD MODAL
    // =========================================

    if (
        modalCancelBtn &&
        modal
    ) {

        modalCancelBtn.addEventListener(
            "click",
            () => {

                modal.classList.add(
                    "hiddenModal"
                );

            }
        );

    }

    if (modal) {

        modal.addEventListener(
            "click",
            (e) => {

                if (e.target === modal) {

                    modal.classList.add(
                        "hiddenModal"
                    );

                }

            }
        );

    }

    // =========================================
    // CONFIRM PASSWORD AND SUBMIT
    // =========================================

    if (modalConfirmBtn) {

        modalConfirmBtn.addEventListener(
            "click",
            () => {

                const passwordValue =
                    modalPasswordInput
                        ? modalPasswordInput.value.trim()
                        : "";

                if (!passwordValue) {

                    if (modalError) {

                        modalError.classList.remove(
                            "hiddenError"
                        );

                    }

                    return;

                }

                if (modal) {

                    modal.classList.add(
                        "hiddenModal"
                    );

                }

                if (pendingAction === "profile") {

                    if (confirmPasswordInput) {

                        confirmPasswordInput.value =
                            passwordValue;

                    }

                    if (profileForm) {

                        profileForm.submit();

                    }

                } else if (
                    pendingAction === "password"
                ) {

                    if (confirmPasswordInputPwd) {

                        confirmPasswordInputPwd.value =
                            passwordValue;

                    }

                    if (passwordForm) {

                        passwordForm.submit();

                    }

                }

                pendingAction = null;

            }
        );

    }

    // =========================================
    // ENTER KEY IN PASSWORD MODAL
    // =========================================

    if (modalPasswordInput) {

        modalPasswordInput.addEventListener(
            "keypress",
            (e) => {

                if (e.key === "Enter") {

                    e.preventDefault();

                    if (modalConfirmBtn) {

                        modalConfirmBtn.click();

                    }

                }

            }
        );

    }

    // =========================================
    // PASSWORD VALIDATION
    // =========================================

    if (passwordForm) {

        passwordForm.addEventListener(
            "submit",
            (e) => {

                const newPwd =
                    document.getElementById("newPassword")
                        ? document.getElementById("newPassword").value
                        : "";

                const confirmPwd =
                    document.getElementById("confirmNewPassword")
                        ? document.getElementById("confirmNewPassword").value
                        : "";

                if (newPwd !== confirmPwd) {

                    e.preventDefault();

                    alert(
                        "New passwords do not match."
                    );

                }

            }
        );

    }

});

</script>

</body>

</html>