<?php

require_once "Error404Connection.php";


//get selected ward
$selectedWard = trim($_GET["ward"] ?? "");


//get wards from database
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


//get approved current notices
$sql = "
    SELECT
        n.notice_id,
        n.username,
        u.first_Name,
        u.surname,
        n.notice_title,
        n.notice_category,
        n.publish_date,
        n.expiry_date,
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

    LEFT JOIN `user` u
        ON n.username = u.username

    LEFT JOIN notice_ward nw
        ON n.notice_id = nw.notice_id

    LEFT JOIN ward w
        ON nw.ward_id = w.ward_id

    WHERE n.status = 'Approved'

    AND n.deleted_at IS NULL

    AND n.publish_date <= NOW()

    AND n.expiry_date >= NOW()
";


//filter by selected ward
if ($selectedWard !== "") {

    $wardId = (int)$selectedWard;

    $sql .= "
        AND (
            EXISTS (
                SELECT 1
                FROM notice_ward selected_nw
                WHERE selected_nw.notice_id = n.notice_id
                AND selected_nw.ward_id = ?
            )

            OR

            NOT EXISTS (
                SELECT 1
                FROM notice_ward community_nw
                WHERE community_nw.notice_id = n.notice_id
            )
        )
    ";
}


//group notices
$sql .= "
    GROUP BY
        n.notice_id,
        n.username,
        u.first_Name,
        u.surname,
        n.notice_title,
        n.notice_category,
        n.publish_date,
        n.expiry_date,
        n.notice_description,
        n.date_posted

    ORDER BY n.date_posted DESC
";


//prepare query
$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database error: "
        . $conn->error
    );
}


//bind selected ward
if ($selectedWard !== "") {

    $stmt->bind_param(
        "i",
        $wardId
    );
}


//execute query
$stmt->execute();

$results = $stmt->get_result();


$homeNotices = [];

$noticeQuery = "
    SELECT
        n.notice_id,
        n.notice_title,
        n.notice_category,
        n.notice_description,
        n.publish_date,
        n.expiry_date,
        n.date_posted
    FROM notices n
    WHERE n.status = 'Approved'
    AND n.deleted_at IS NULL
    AND n.publish_date <= NOW()
    AND n.expiry_date >= NOW()

    AND NOT EXISTS (
        SELECT 1
        FROM notice_ward nw
        WHERE nw.notice_id = n.notice_id
    )

    ORDER BY n.date_posted DESC
    LIMIT 1
";

$noticeResult = $conn->query($noticeQuery);

if ($noticeResult) {

    while ($row = $noticeResult->fetch_assoc()) {

        $homeNotices[] = $row;
    }
}


/* ########## WEATHER ############## */

$apiKey = "YOUR_WEATHER_API_KEY";
$city = "Grahamstown";

// forecast.json returns the current conditions, the city's local time
// and a 3-day forecast in a single request
$url = "https://api.weatherapi.com/v1/forecast.json?key="
    . urlencode($apiKey)
    . "&q="
    . urlencode($city)
    . "&days=3&aqi=no&alerts=no";

$weatherAvailable = false;
$localTime = "--";
$forecastDays = [];

$response = @file_get_contents($url);

if ($response !== false) {

    $data = json_decode($response, true);

    if (
        is_array($data)
        && !isset($data["error"])
        && !empty($data["forecast"]["forecastday"])
    ) {

        $weatherAvailable = true;

        // Time in the city itself, e.g. "11:44 pm"
        $localTime = date(
            "g:i a",
            strtotime($data["location"]["localtime"])
        );

        foreach ($data["forecast"]["forecastday"] as $index => $day) {

            $isToday = ($index === 0);

            // Today uses the live conditions, the next days use the
            // forecast (highest temperature and strongest wind).
            // The API gives wind in km/h, so divide by 3.6 for m/s.
            $forecastDays[] = [
                "label" => $isToday
                    ? "Today"
                    : date("l", strtotime($day["date"])),

                "date" => date("F j, Y", strtotime($day["date"])),

                "icon" => $isToday
                    ? $data["current"]["condition"]["icon"]
                    : $day["day"]["condition"]["icon"],

                "condition" => $isToday
                    ? $data["current"]["condition"]["text"]
                    : $day["day"]["condition"]["text"],

                "temp" => round(
                    $isToday
                        ? $data["current"]["temp_c"]
                        : $day["day"]["maxtemp_c"]
                ),

                "wind" => round(
                    ($isToday
                        ? $data["current"]["wind_kph"]
                        : $day["day"]["maxwind_kph"]) / 3.6
                )
            ];
        }
    }
}



/* ########### FETCH WARD SUMMARIES ############ */

$query = "SELECT
            title,
            file_path,
            created_at,
            ward_id
        FROM ward_summaries
        WHERE deleted = 0
        ORDER BY created_at DESC
        LIMIT ?
";


$result = false;
if ($stmt = mysqli_prepare($conn, $query)) {
    $limit = 3;
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $limit
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}else{
    error_log("Database query failed: ". mysqli_error($conn));
}

// HERO BACKGROUND: new random order on every page load. First image after the shuffle is shown.
$heroBackgrounds = [
    "Makhandaimages/makhandaBackground1.jpg",
    "Makhandaimages/makhandaBackground2.jpg",
    "Makhandaimages/makhandaBackground3.jpg",
    "Makhandaimages/makhandaBackground4.jpg",
    "Makhandaimages/makhandaBackground5.jpg",
    "Makhandaimages/makhandaBackground6.jpg"
];
shuffle($heroBackgrounds);

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
        Public Notice Board
    </title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="publicfaultBoard.css">

    <style>

        /* =========================================
           RESET
        ========================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f0e7;
            color: #000000;
            min-height: 100vh;
        }


        /* =========================================
           NAVIGATION
        ========================================= */

        .mainNavbar {
            width: 100%;
            background-color: #ffffff;
            border-bottom: 1px solid #d5d5d5;
        }


        .navContainer {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }


        .mainNavLinks {
            min-height: 65px;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 40px;

            list-style: none;
        }


        .navItem {
            position: relative;

            display: block;

            padding: 23px 0;

            color: #000000;

            text-decoration: none;
            text-transform: uppercase;

            font-size: 13px;
            font-weight: 700;

            letter-spacing: 0.4px;

            transition: 0.2s ease;
        }


        .navItem:hover {
            color: #4b561b;
        }


        .navItem.active {
            color: #4b561b;
        }


        .navItem.active::after {
            content: "";

            position: absolute;

            left: 0;
            bottom: 13px;

            width: 100%;
            height: 3px;

            background-color: #917e5a;

            border-radius: 3px;
        }


        /* =========================================
           NOTICE PAGE
        ========================================= */

        .notice-page {
            width: 92%;
            max-width: 1300px;

            margin: 0 auto;

            padding: 50px 0 80px;
        }


        /* =========================================
           PAGE HEADING
        ========================================= */

        .notice-heading {
            text-align: center;

            margin-bottom: 35px;
        }


        .notice-heading h1 {
            margin: 0 0 10px;

            color: #334e4a;

            font-size: 32px;
            font-weight: 700;
        }


        .notice-heading p {
            max-width: 600px;

            margin: 0 auto;

            color: #000000;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================================
           FILTER
        ========================================= */

        .filter-container {
            width: 100%;
            max-width: 720px;

            margin: 0 auto 45px;
        }


        .filter-container form {
            width: 100%;

            padding: 22px 25px;

            background-color: #ffffff;

            border: 1px solid #d5d1c8;
            border-radius: 8px;

            display: flex;
            align-items: flex-end;
            justify-content: center;

            gap: 14px;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.07);
        }


        .filter-field {
            display: flex;
            flex-direction: column;

            gap: 7px;
        }


        .filter-field label {
            color: #000000;

            font-size: 13px;
            font-weight: 700;
        }


        .filter-field select {
            width: 320px;
            height: 43px;

            padding: 0 13px;

            background-color: #ffffff;

            color: #000000;

            border: 1px solid #917e5a;
            border-radius: 5px;

            font-size: 14px;

            outline: none;
        }


        .filter-field select:focus {
            border: 2px solid #334e4a;
        }


        /* =========================================
           VIEW NOTICES BUTTON
        ========================================= */

        .filter-container button {
            height: 43px;

            padding: 0 22px;

            background-color: #334e4a;

            color: #ffffff;

            border: none;
            border-radius: 5px;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .filter-container button:hover {
            background-color: #4b561b;
        }


        /* =========================================
           CLEAR BUTTON
        ========================================= */

        .clear-btn {
            height: 43px;

            padding: 0 18px;

            display: flex;
            align-items: center;
            justify-content: center;

            background-color: #ffffff;

            color: #000000;

            border: 1px solid #334e4a;
            border-radius: 5px;

            text-decoration: none;

            font-size: 13px;
            font-weight: 700;

            transition: 0.2s ease;
        }


        .clear-btn:hover {
            background-color: #334e4a;

            color: #ffffff;
        }


        /* =========================================
           NOTICE GRID
        ========================================= */

        .notice-container {
            width: 100%;

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 22px;

            align-items: stretch;
        }


        /* =========================================
           NOTICE CARD
        ========================================= */

        .notice-card {
            width: 100%;

            background-color: #ffffff;

            border: 1px solid #d5d1c8;
            border-top: 4px solid #334e4a;

            border-radius: 8px;

            padding: 24px;

            display: flex;
            flex-direction: column;

            min-height: 300px;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.08);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .notice-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 7px 18px rgba(0, 0, 0, 0.12);
        }


        /* =========================================
           NOTICE TOP
        ========================================= */

        .notice-top {
            width: 100%;

            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 15px;

            margin-bottom: 20px;
        }


        /* =========================================
           NOTICE CATEGORY
        ========================================= */

        .notice-category {
            display: inline-block;

            padding: 6px 11px;

            background-color: #e8e4d9;

            color: #000000;

            border-radius: 15px;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: 0.5px;

            line-height: 1.2;
        }


        /* =========================================
           NOTICE AREA
        ========================================= */

        .notice-area {
            color: #000000;

            font-size: 11px;
            font-weight: 700;

            line-height: 1.4;

            text-align: right;
        }


        /* =========================================
           NOTICE TITLE
        ========================================= */

        .notice-card-title {
            margin: 0 0 12px;

            color: #000000;

            font-size: 20px;
            font-weight: 700;

            line-height: 1.4;

            text-transform: capitalize;
        }


        /* =========================================
           NOTICE DESCRIPTION
        ========================================= */

        .notice-description {
            margin: 0 0 22px;

            color: #000000;

            font-size: 14px;
            font-weight: 400;

            line-height: 1.6;

            flex-grow: 1;
        }


        /* =========================================
           NOTICE INFORMATION
        ========================================= */

        .notice-info {
            margin-top: auto;

            padding-top: 16px;

            border-top: 1px solid #d5d1c8;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px 18px;
        }


        .notice-info div {
            min-width: 0;

            display: flex;
            flex-direction: column;

            gap: 4px;
        }


        .notice-info div:last-child {
            grid-column: 1 / -1;
        }


        .notice-info span {
            color: #000000;

            font-size: 9px;
            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.6px;
        }


        .notice-info strong {
            color: #000000;

            font-size: 12px;
            font-weight: 700;

            line-height: 1.4;

            overflow-wrap: anywhere;
        }


        /* =========================================
           NO NOTICES
        ========================================= */

        .no-notices {
            grid-column: 1 / -1;

            width: 100%;

            padding: 45px 30px;

            background-color: #ffffff;

            border: 1px solid #d5d1c8;
            border-top: 4px solid #334e4a;

            border-radius: 8px;

            text-align: center;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.07);
        }


        .no-notices h2 {
            margin-bottom: 8px;

            color: #000000;

            font-size: 20px;
        }


        .no-notices p {
            color: #000000;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================================
           KEEP ALL NOTICE TEXT VISIBLE
        ========================================= */

        .notice-card,
        .notice-card h2,
        .notice-card p,
        .notice-card span,
        .notice-card strong,
        .notice-card-title,
        .notice-description,
        .notice-category,
        .notice-area,
        .notice-info span,
        .notice-info strong {
            color: #000000;
        }


        /* =========================================
           TABLET
        ========================================= */

        @media (max-width: 1000px) {

            .notice-container {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .mainNavLinks {
                gap: 22px;

                flex-wrap: wrap;
            }


            .filter-container form {
                align-items: stretch;
            }


            .filter-field {
                flex: 1;
            }


            .filter-field select {
                width: 100%;
            }
        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 650px) {

            .mainNavLinks {
                min-height: auto;

                gap: 5px 16px;

                padding: 12px 0;
            }


            .navItem {
                padding: 8px 0;

                font-size: 11px;
            }


            .navItem.active::after {
                bottom: 2px;
            }


            .notice-page {
                width: 92%;

                padding: 40px 0 60px;
            }


            .notice-heading h1 {
                font-size: 26px;
            }


            .filter-container form {
                flex-direction: column;

                padding: 20px;
            }


            .filter-field select {
                width: 100%;
            }


            .filter-container button,
            .clear-btn {
                width: 100%;
            }


            .notice-container {
                grid-template-columns: 1fr;
            }


            .notice-card {
                min-height: auto;

                padding: 20px;
            }


            .notice-top {
                flex-direction: column;

                align-items: flex-start;

                gap: 8px;
            }


            .notice-area {
                text-align: left;
            }


            .notice-info {
                grid-template-columns: 1fr;

                gap: 12px;
            }


            .notice-info div:last-child {
                grid-column: auto;
            }
        }

    </style>

</head>


<body>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makhanda Link</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="publicfaultBoard.css">
    <style>
        
    </style>
</head>
<body>


<!-- MAKHANDA HEADER -->

<header class="heroHeader">

    <div class="heroBackgroundSlideshow">

        <?php foreach ($heroBackgrounds as $index => $heroImage): ?>
            <img
                src="<?= htmlspecialchars($heroImage) ?>"
                alt="Makhanda"
                class="heroBgImage<?= $index === 0 ? ' active' : '' ?>"
            >
        <?php endforeach; ?>

    </div>
    
    <div class="heroOverlay"></div>
    <div class="heroContainer">
        <div class="heroLogo">
            <img src="makanalogo.svg" alt="Makhanda logo">
        </div>

        <div class="heroText">
            <h1>Makhanda-Link</h1>
            <p>
                Welcome. Empowering our community together.
            </p>
        </div>
    </div>

</header>
<!-- 
     UTILITY BAR
 -->
<div class="utilityBar">
    <div class="utilityContainer">
        <div class="utilityLeft">
            <a href="#">
                Documentation
            </a>
            <a href="#">
                Support
            </a>
        </div>


        <div class="utilityRight">
            <a href="login.php" class="utilityBtn btn">
                Login
            </a>
            <a href="register.php" class="utilityBtn btn">
                Sign Up
            </a>
        </div>
    </div>
</div>

<!-- 
     MAIN NAVIGATION
 -->

<nav class="mainNavbar">
    <div class="navContainer">
        <ul class="mainNavLinks">
            <li>
                <a
                    href="index.php"
                    class="navItem"
                >
                    Home
                </a>
            </li>
            <li>
                <a
                    href="PublicFaultBoard.php"
                    class="navItem"
                >
                    Faults
                </a>
            </li>
            <li>
                <a
                    href="ward_summaries.php"
                    class="navItem"
                >
                    Wards
                </a>
            </li>

            <li>
                <a
                    href="ABOUTUS.html"
                    class="navItem"
                >
                    About Us
                </a>
            </li>

            <li>
                <a
                    href="about_us_page.html"
                    class="navItem"
                >
                    About M-Link
                </a>
            </li>

            <li>
                <a
                    href="view_notices.php"
                    class="navItem active"
                >
                    Notices
                </a>
            </li>
            <li>
                <a
                    href="contactUs.html"
                    class="navItem"
                >
                    Contact us
                </a>
            </li>
        </ul>
    </div>
</nav>

  <!--   NOTICE PAGE -->


<main class="notice-page">


    <!-- PAGE HEADING -->

    <div class="notice-heading">

        <h1>
            Public Notice Board
        </h1>

        <p>
            View current municipal notices and
            updates for Makhanda and your ward.
        </p>

    </div>



    <!-- =========================================
         WARD FILTER
    ========================================= -->

    <div class="filter-container">

        <form
            method="GET"
            action="view_notices.php"
        >

            <div class="filter-field">

                <label for="ward">
                    Select Ward
                </label>


                <select
                    name="ward"
                    id="ward"
                >

                    <option value="">
                        All Wards
                    </option>


                    <?php foreach ($wards as $ward): ?>

                        <option
                            value="<?php
                                echo (int)$ward["ward_id"];
                            ?>"

                            <?php

                            if (
                                $selectedWard ===
                                (string)$ward["ward_id"]
                            ) {

                                echo "selected";
                            }

                            ?>
                        >

                            Ward
                            <?php
                                echo (int)$ward["ward_number"];
                            ?>

                            <?php if (
                                !empty($ward["ward_name"])
                            ): ?>

                                -
                                <?php

                                echo htmlspecialchars(
                                    $ward["ward_name"]
                                );

                                ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button type="submit">
                View Notices
            </button>


            <?php if ($selectedWard !== ""): ?>

                <a
                    href="view_notices.php"
                    class="clear-btn"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>



    <!-- =========================================
         NOTICE CARDS
    ========================================= -->

    <div class="notice-container">


        <?php if (
            $results &&
            $results->num_rows > 0
        ): ?>


            <?php while (
                $row = $results->fetch_assoc()
            ): ?>


                <article class="notice-card">


                    <!-- NOTICE TOP -->

                    <div class="notice-top">


                        <span class="notice-category">

                            <?php

                            $category = trim(
                                $row["notice_category"]
                                ?? ""
                            );

                            if ($category === "") {

                                $category = "Municipality";
                            }

                            echo htmlspecialchars(
                                strtoupper($category)
                            );

                            ?>

                        </span>



                        <span class="notice-area">

                            <?php

                            if (
                                (int)$row["ward_count"] === 0
                            ) {

                                echo "Community-wide";

                            } else {

                                echo htmlspecialchars(
                                    $row["ward_list"]
                                );
                            }

                            ?>

                        </span>

                    </div>



                    <!-- NOTICE TITLE -->

                    <h2 class="notice-card-title">

                        <?php

                        echo htmlspecialchars(
                            $row["notice_title"]
                        );

                        ?>

                    </h2>



                    <!-- NOTICE DESCRIPTION -->

                    <p class="notice-description">

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $row["notice_description"]
                            )
                        );

                        ?>

                    </p>



                    <!-- NOTICE INFORMATION -->

                    <div class="notice-info">


                        <!-- PUBLISHED -->

                        <div>

                            <span>
                                Published
                            </span>

                            <strong>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $row["publish_date"]
                                    )
                                );

                                ?>

                            </strong>

                        </div>



                        <!-- EXPIRES -->

                        <div>

                            <span>
                                Expires
                            </span>

                            <strong>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $row["expiry_date"]
                                    )
                                );

                                ?>

                            </strong>

                        </div>



                        <!-- POSTED BY -->

                        <div>

                            <span>
                                Posted By
                            </span>

                            <strong>

                                <?php

                                $postedBy = trim(
                                    ($row["first_Name"] ?? "")
                                    . " "
                                    . ($row["surname"] ?? "")
                                );

                                //use username only if name is missing
                                if ($postedBy === "") {

                                    $postedBy =
                                        $row["username"];
                                }

                                echo htmlspecialchars(
                                    $postedBy
                                );

                                ?>

                            </strong>

                        </div>


                    </div>


                </article>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="no-notices">

                <h2>
                    No Current Notices
                </h2>

                <p>
                    There are currently no approved
                    notices available for the selected
                    area.
                </p>

            </div>


        <?php endif; ?>


    </div>


</main>



<?php

$stmt->close();

$conn->close();

?>

<!-- FOOTER -->

<footer>


    <div class="footerContainer">


        <div class="makanaLogo">


            <div class="logoBrand">

                <img
                    src="MakanaLogo.svg"
                    alt="M-Link logo"
                >

            </div>


            <p>

                Empowering residents to take charge in their
                neighbourhood and work together to build a
                better, well-maintained Makhanda community.

            </p>


            <div class="socials">

                <a
                    href="#"
                    aria-label="X"
                >
                    <img
                        src="x.svg"
                        alt="X"
                    >
                </a>


                <a
                    href="#"
                    aria-label="Instagram"
                >
                    <img
                        src="instagram.svg"
                        alt="Instagram"
                    >
                </a>


                <a
                    href="#"
                    aria-label="Facebook"
                >
                    <img
                        src="facebook.svg"
                        alt="Facebook"
                    >
                </a>

            </div>


            <a
                href="#"
                class="backToTopBtn"
            >
                <span class="arrow">
                    ↑
                </span>

                BACK TO TOP
            </a>

        </div>



        <div class="siteMap">

            <h2>
                Site Map
            </h2>

            <a href="homepage.php">
                Homepage
            </a>

            <a href="about_us_page.html">
                About Us
            </a>

            <a href="PublicFaultBoard.php">
                Public Fault Board
            </a>

            <a href="view_notices.php">
                Notices
            </a>

            <a href="viewWards.html">
                Wards
            </a>

            <a href="contactUs.html">
                Contact Us
            </a>

            <a href="FAQ.html">
                FAQs
            </a>

        </div>



        <div class="damCapacity">
            <h2>
                Dam Capacity
            </h2>
            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Howiesonspoort <strong>92%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 92%;"></div>
                </div>
            </div>


            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Settlers <strong>75%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 75%;"></div>
                </div>
            </div>

            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Jameson <strong>40%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 40%;"></div>
                </div>
            </div>

            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Milner <strong>40%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 40%;"></div>
                </div>
            </div>
        </div>
        
        <div class="contact">
            <h2>
                Contact
            </h2>
            <a href="mailto:zkewuti@makana.gov.za">
                Customer Care
            </a>
            <a href="mailto:info@makana.gov.za">
                Service Delivery
            </a>
            <a href="mailto:enquiries@makana.gov.za">
                Billing Related
            </a>
            <div class="address">
                <p>
                    City Hall, High Str, Grahamstown, 6139
                </p>
                <p>
                    <img
                        src="call.svg"
                        alt="Phone"
                    >
                    +27 (0) 46 603 6134
                </p>
            </div>
        </div>
    </div>

    <div class="copyrightBar">

        <p>
            Copyright © 2026 M-Link. All Rights Reserved.
        </p>

    </div>

</footer>



<!-- 
     JAVASCRIPT
 -->

<script>
document.addEventListener("DOMContentLoaded", function () {

    // ############### INTERESTING FACTS SLIDESHOW

    const factsTrack = document.getElementById("factsTrack");
    const factSlides = document.querySelectorAll(
        "#factsTrack .fact-slide"
    );

    const previousButton = document.getElementById("prevFactBtn");
    const nextButton = document.getElementById("nextFactBtn");
    let currentFactSlide = 0;


    function displayFactSlide() {
        if (!factsTrack || factSlides.length === 0) {
            return;
        }
        factsTrack.style.transform =
            "translateX(-" + (currentFactSlide * 100) + "%)";
    }


    function showNextFact() {
        if (factSlides.length === 0) {
            return;
        }
        currentFactSlide++;
        if (currentFactSlide >= factSlides.length) {
            currentFactSlide = 0;
        }
        displayFactSlide();
    }

    function showPreviousFact() {
        if (factSlides.length === 0) {
            return;
        }
        currentFactSlide--;
        if (currentFactSlide < 0) {
            currentFactSlide = factSlides.length - 1;
        }
        displayFactSlide();
    }

    if (nextButton) {
        nextButton.addEventListener("click", function () {
            showNextFact();
        });
    }

    if (previousButton) {
        previousButton.addEventListener("click", function () {
            showPreviousFact();
        });
    }

    //change fact automatically every 4 seconds
    if (factSlides.length > 1) {
        setInterval(showNextFact, 4000);

    }
});

</script>
</body>

</html>