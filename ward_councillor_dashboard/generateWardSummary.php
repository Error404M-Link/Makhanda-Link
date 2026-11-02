<?php

session_start();

require_once "../Error404Connection.php";


/* ================= LOGIN CHECK ================= */

if (
    !isset($_SESSION["username"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "ward_councillor"
) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}


$username = $_SESSION["username"];

$ward_id = isset($_SESSION["ward_id"])
    ? (int) $_SESSION["ward_id"]
    : 0;


/* ================= COUNCILLOR DETAILS ================= */

$first_name = "";
$surname = "";
$image_path = "";

$sql = "SELECT u.first_Name, u.surname, pi.image_path
        FROM ward_councillor wc
        INNER JOIN user u
            ON wc.username = u.username
        LEFT JOIN profile_images pi
            ON u.profile_id = pi.profile_id
        WHERE wc.username = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "s", $username);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $first_name = $row["first_Name"];
        $surname = $row["surname"];
        $image_path = $row["image_path"];
    }

    mysqli_stmt_close($stmt);
}


$display_name = trim($first_name . " " . $surname);

if ($display_name === "") {
    $display_name = $username;
}


$profileImg = !empty($image_path)
    ? $image_path
    : "profile.jpg";


/* ================= FORM PROCESSING ================= */

$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "Ward Summary Report");

    $summaryPath = null;
    $fileSizeBytes = null;
    $mimeType = null;


    /* ================= FILE UPLOAD ================= */

    if (
        isset($_FILES["file_path"]) &&
        $_FILES["file_path"]["error"] === UPLOAD_ERR_OK
    ) {

        $fileTmpPath = $_FILES["file_path"]["tmp_name"];
        $fileName = $_FILES["file_path"]["name"];
        $fileSizeBytes = $_FILES["file_path"]["size"];

        $fileExtension = strtolower(
            pathinfo($fileName, PATHINFO_EXTENSION)
        );


        $allowedExtensions = [
            "pdf",
            "doc",
            "docx",
            "rtf",
            "jpg",
            "jpeg",
            "png",
            "gif"
        ];


        if (!in_array($fileExtension, $allowedExtensions)) {

            $message = "
                <div class='alert error'>
                    Invalid file type. Please upload a PDF, Word document or image.
                </div>
            ";

        } else {

            $mimeType = mime_content_type($fileTmpPath);

            $uploadDirectory = "uploads/Summaries/";


            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }


            $newFileName =
                bin2hex(random_bytes(16))
                . "."
                . $fileExtension;


            $destination = $uploadDirectory . $newFileName;


            if (move_uploaded_file($fileTmpPath, $destination)) {

                $summaryPath = $destination;

            } else {

                $message = "
                    <div class='alert error'>
                        The file could not be uploaded.
                    </div>
                ";
            }
        }
    }


    /* ================= DATABASE INSERT ================= */

    if ($message === "") {

        if ($ward_id < 1 || $ward_id > 14) {

            $message = "
                <div class='alert error'>
                    Invalid ward assigned to this councillor.
                </div>
            ";

        } else {

            $sql = "INSERT INTO ward_summaries
                    (
                        ward_id,
                        councillor_username,
                        title,
                        file_path,
                        file_size_bytes,
                        mime_type
                    )
                    VALUES (?, ?, ?, ?, ?, ?)";


            $stmt = mysqli_prepare($conn, $sql);


            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "isssis",
                    $ward_id,
                    $username,
                    $title,
                    $summaryPath,
                    $fileSizeBytes,
                    $mimeType
                );


                if (mysqli_stmt_execute($stmt)) {

                    $message = "
                        <div class='alert success'>
                            Ward summary uploaded successfully.
                        </div>
                    ";

                } else {

                    $message = "
                        <div class='alert error'>
                            Error uploading summary:
                            "
                            . htmlspecialchars(mysqli_stmt_error($stmt))
                            .
                            "
                        </div>
                    ";
                }


                mysqli_stmt_close($stmt);

            } else {

                $message = "
                    <div class='alert error'>
                        Database query could not be prepared.
                    </div>
                ";
            }
        }
    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Ward Summary Report</title>


    <!-- SAME DASHBOARD CSS -->

    <link rel="stylesheet"
          href="Wardcouncillo.css">


    <!-- FONT AWESOME -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- PAGE-SPECIFIC CSS -->

    <style>

        h1{
            text-align: center;
        }

        .page-heading {
            margin-bottom: 25px;
        }


        .form-card {

            background: white;

            max-width: 700px;

            margin: 0 auto;

            border-radius: 15px;

            overflow: hidden;

            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);

        }


        .card-header {

            background: #1b442b;

            color: white;

            padding: 25px;

        }


        .card-header h2 {

            margin-bottom: 6px;

        }


        .card-header p {

            opacity: 0.9;

        }


        .card-body {

            padding: 30px;

        }


        .form-group {

            margin-bottom: 20px;

        }


        .form-group label {

            display: block;

            font-weight: 600;

            margin-bottom: 8px;

        }


        .form-group input[type="text"],
        .form-group input[type="file"] {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 6px;

            font-size: 14px;

        }


        .form-group input[type="file"] {

            background: #fafafa;

        }


        .btn-submit-container {

            display: flex;

            justify-content: flex-end;

            margin-top: 25px;

        }


        .btn-submit {

            background: #239e56;

            color: white;

            border: none;

            padding: 11px 25px;

            border-radius: 6px;

            cursor: pointer;

            font-size: 14px;

            font-weight: 600;

        }


        .btn-submit:hover {

            background: #236833;

        }


        .alert {

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;

            font-size: 14px;

        }


        .alert.success {

            background: #def7ec;

            color: #03543f;

        }


        .alert.error {

            background: #fde8e8;

            color: #981b1b;

        }

    </style>

</head>


<body>


<div class="container">


    <!-- ================= TOP NAVIGATION ================= -->

    <header class="topbar">


        <button class="menu-btn" id="menuBtn">

            <i class="fa-solid fa-bars"></i>

        </button>


         <div class="container">
    <header class="topbar">
        <button class="menu-btn" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
          <div class="logoBrand">
    <img
        src="makanaLogo.svg"
        alt="M-Link logo"
        height="100"
        style="display:block; height:80px; width:auto; max-width:100%; margin:0 auto; object-fit:contain;"
    >
</div>



        <div class="top-right">


            <div class="icon">

                <i class="fa-regular fa-bell"></i>

                <span>3</span>

            </div>


            <div class="icon">

                <i class="fa-regular fa-envelope"></i>

                <span>2</span>

            </div>


            <div class="user">

                <img
                    src="<?php echo htmlspecialchars($profileImg); ?>"
                    alt="Profile">

                <div>

                    <h4>
                        <?php echo htmlspecialchars($display_name); ?>
                    </h4>

                    <small>Ward Councillor</small>

                </div>

            </div>


        </div>

    </header>



    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar" id="sidebar">


        <div class="profile">

            <img
                src="<?php echo htmlspecialchars($profileImg); ?>"
                alt="Profile">

            <h3>
                <?php echo htmlspecialchars($display_name); ?>
            </h3>

            <p>Ward Councillor</p>

            <span>
                Ward <?php echo $ward_id; ?>
            </span>

        </div>



        <ul class="menu">


            <!-- DASHBOARD -->

            <li>

                <a href="wardcouncillorDashboard.php">

                    <i class="fa-solid fa-house"></i>

                    <span>Dashboard</span>

                </a>

            </li>


            <!-- TICKETS -->

            <li class="menu-title">

                TICKETS MANAGEMENT

            </li>


            <li>

                <a href="WardTicketManagement.php">

                    <i class="fa-solid fa-ticket"></i>

                    <span>Ward Tickets Management</span>

                </a>

            </li>


            <!-- COMMUNICATION -->

            <li class="menu-title">

                COMMUNICATION

            </li>


            <li class="active">

                <a href="ward_summaries.php">

                    <i class="fa-solid fa-file-lines"></i>

                    <span>Generate Reports / Ward Summaries</span>

                </a>

            </li>


            <!-- ANALYTICS -->

            <li class="menu-title">

                ANALYTICS

            </li>


            <li>

                <a href="Analytics.php">

                    <i class="fa-solid fa-chart-column"></i>

                    <span>Analytics & Reports</span>

                </a>

            </li>


            <!-- ACCOUNT -->

            <li class="menu-title">

                ACCOUNT

            </li>


            <li>

                <i class="fa-regular fa-envelope"></i>

                <span>Messages</span>

                <b class="badge">2</b>

            </li>


            <li>

                <a href="../USER_DASHBOARD/settings.php">

                    <i class="fa-solid fa-user"></i>

                    <span>Profile</span>

                </a>

            </li>


            <li>

                <a href="logout.php">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>Logout</span>

                </a>

            </li>


        </ul>


    </aside>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content" id="mainContent">


        <section class="page-heading">

            <h1>Generate Ward Summary</h1>

            <p>
                
                <?php echo $ward_id; ?>.
            </p>

        </section>



        <!-- ================= FORM ================= -->

        <div class="form-card">


            <div class="card-header">

                <h2>Ward Summary Report</h2>

                <p>
                    Upload and record your ward summary.
                </p>

            </div>


            <div class="card-body">


                <?php echo $message; ?>


                <form
                    action=""
                    method="POST"
                    enctype="multipart/form-data">


                    <!-- REPORT TITLE -->

                    <div class="form-group">

                        <label for="title">
                            Report Title
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="Ward Summary Report"
                            required>

                    </div>


                    <!-- FILE -->

                    <div class="form-group">

                        <label for="file_path">
                            Attachment File
                        </label>

                        <input
                            type="file"
                            id="file_path"
                            name="file_path"
                            accept=".pdf,.doc,.docx,.rtf,.jpg,.jpeg,.png,.gif"
                            required>

                    </div>


                    <!-- SUBMIT -->

                    <div class="btn-submit-container">

                        <button
                            type="submit"
                            class="btn-submit">

                            <i class="fa-solid fa-upload"></i>

                            Submit Report

                        </button>

                    </div>


                </form>


            </div>

        </div>


    </main>


</div>


<!-- SAME JAVASCRIPT -->

<script src="wardcouncillor.js"></script>


</body>

</html>

<?php

mysqli_close($conn);

?>