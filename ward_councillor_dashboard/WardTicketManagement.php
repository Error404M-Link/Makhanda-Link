<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../Error404Connection.php";


/* LOGIN CHECK*/

if (
    !isset($_SESSION["username"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "ward_councillor"
) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}


$username = $_SESSION["username"];


/* ESCAPE FUNCTION*/

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* GET LOGGED-IN COUNCILLOR*/

$councillor_sql = "
    SELECT
        wc.username,
        wc.ward_id,
        u.first_Name,
        u.surname,
        pi.image_path
    FROM ward_councillor wc
    LEFT JOIN user u
        ON wc.username = u.username
    LEFT JOIN profile_images pi
        ON u.profile_id = pi.profile_id
    WHERE wc.username = ?
    LIMIT 1
";

$councillor_stmt = mysqli_prepare(
    $conn,
    $councillor_sql
);

if (!$councillor_stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $councillor_stmt,
    "s",
    $username
);

mysqli_stmt_execute(
    $councillor_stmt
);

$councillor_result =
    mysqli_stmt_get_result(
        $councillor_stmt
);

$councillor =
    mysqli_fetch_assoc(
        $councillor_result
    );

mysqli_stmt_close(
    $councillor_stmt
);


if (!$councillor) {
    die("Councillor information could not be found.");
}


/* COUNCILLOR INFORMATION*/

$first_name =
    $councillor["first_Name"] ?? "";

$surname =
    $councillor["surname"] ?? "";

$user_ward_id =
    (int)($councillor["ward_id"] ?? 0);

$image_path =
    $councillor["image_path"] ?? "";


$display_name =
    trim(
        $first_name . " " . $surname
    );


if ($display_name === "") {
    $display_name = $username;
}


$profileImg =
    !empty($image_path)
        ? $image_path
        : "profile.jpg";


if (
    $user_ward_id < 1 ||
    $user_ward_id > 14
) {
    die("No valid ward has been assigned to this councillor.");
}


/* SELECTED WARD */

$selected_ward =
    isset($_GET["ward"])
        ? trim($_GET["ward"])
        : (string)$user_ward_id;




if ($selected_ward !== "all") {

    if (
        !ctype_digit($selected_ward) ||
        (int)$selected_ward < 1 ||
        (int)$selected_ward > 14
    ) {
        $selected_ward =
            (string)$user_ward_id;
    }
}

$unreadNotificationCount = 0;

$sql = "
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'new_ticket'
    AND is_read = 0
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $unreadNotificationCount =
            (int)$row["unread_count"];
    }

    $stmt->close();
}



/* FILTERS */

$search_query =
    isset($_GET["search"])
        ? trim($_GET["search"])
        : "";

$status_filter =
    isset($_GET["status"])
        ? trim($_GET["status"])
        : "";

$priority_filter =
    isset($_GET["priority"])
        ? trim($_GET["priority"])
        : "";


/* BUILD QUERY */

$where = [];

$params = [];

$types = "";


/* WARD */

if ($selected_ward !== "all") {

    $where[] =
        "it.ward_id = ?";

    $params[] =
        (int)$selected_ward;

    $types .= "i";
}


/* STATUS */

if ($status_filter === "1") {

    $where[] = "
        ts.pending IS NOT NULL
        AND ts.InProgress IS NULL
        AND ts.resolved IS NULL
    ";

}

elseif ($status_filter === "2") {

    $where[] = "
        ts.InProgress IS NOT NULL
        AND ts.resolved IS NULL
    ";

}

elseif ($status_filter === "3") {

    $where[] = "
        ts.resolved IS NOT NULL
    ";

}


/* PRIORITY */

if ($priority_filter !== "") {

    $where[] =
        "LOWER(it.priority) = LOWER(?)";

    $params[] =
        $priority_filter;

    $types .= "s";
}


/* SEARCH */

if ($search_query !== "") {

    $where[] = "
        (
            CAST(it.incident_id AS CHAR) LIKE ?
            OR it.incident_description LIKE ?
            OR it.location_description LIKE ?
            OR s.service_type LIKE ?
        )
    ";

    $search =
        "%" . $search_query . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "ssss";
}


/* WHERE */

$where_sql = "";

if (!empty($where)) {

    $where_sql =
        "WHERE " .
        implode(
            " AND ",
            $where
        );
}


/*GET TICKETS */

$sql = "
    SELECT
        it.incident_id,
        it.ward_id,
        it.service_id,
        s.service_type,
        it.location_description,
        it.incident_description,
        it.priority,
        it.status_id,
        ts.pending,
        ts.InProgress,
        ts.resolved,
        CASE
            WHEN ts.resolved IS NOT NULL
                THEN 'Resolved'
            WHEN ts.InProgress IS NOT NULL
                THEN 'In Progress'
            WHEN ts.pending IS NOT NULL
                THEN 'Pending'
            ELSE 'Unknown'
        END AS current_status,
        it.date_reported,
        it.created_at
    FROM incident_ticket it
    LEFT JOIN municipal_service s
        ON it.service_id = s.service_id
    LEFT JOIN ticket_status ts
        ON it.status_id = ts.status_id
    $where_sql
    ORDER BY
        CASE LOWER(it.priority)
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END,
        it.created_at DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {
    die(
        "Database error: " .
        mysqli_error($conn)
    );
}


if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

    $unread_count = 0;

$unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM internal_messages
    WHERE receiver_username = ?
    AND is_read = 0
");

$unread_stmt->bind_param("s", $username);
$unread_stmt->execute();

$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();

$unread_count = (int)($unread_data['unread_count'] ?? 0);

$unread_stmt->close();


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ward Ticket Management</title>


    <!-- YOUR EXISTING SIDEBAR/DASHBOARD CSS -->

    <link
        rel="stylesheet"href="Wardcouncillo.css"
    >



    <style>

        .page-heading {
            margin-bottom: 25px;
        }


        .filter-bar {
            background: #ffffff;
            padding: 18px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }


        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }


        .filter-group input,
        .filter-group select {
            padding: 9px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }


        .btn {
            padding: 9px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }


        .btn-primary {
            background: #1b442b;
            color: white;
        }


        .btn-outline {
            background: white;
            border: 1px solid #1b442b;
            color: #1b442b;
        }


        .ticket-table-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            overflow-x: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
        }


        th {
            background: #4b561b;
            color: white;
        }


        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }


        tbody tr:hover {
            background: #f5f5f5;
        }


        .badge-ward {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }


        .priority-badge-red {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 800;
        }


        .priority-badge-normal {
            background: #f8fafc;
            color: #475569;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
        }


        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }


        .status-pending {
            background: #fff7ed;
            color: #c2410c;
        }


        .status-progress {
            background: #eff6ff;
            color: #1d4ed8;
        }


        .status-resolved {
            background: #f0fdf4;
            color: #15803d;
        }


        .status-unknown {
            background: #f3f4f6;
            color: #4b5563;
        }


        .view-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #239e56;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
        }


        .no-tickets {
            text-align: center;
            padding: 25px;
        }

        .menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.menu-btn svg {
    display: block;
    pointer-events: none;
}



    </style>

</head>
<body>


<div class="container">


    <!--TOP NAVIGATION -->

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
           <img
    src="<?php echo e($profileImg); ?>"
    alt="<?php echo e($display_name); ?>"
    id="profileImage"
    onerror="this.onerror=null; this.src='../uploads/profile.jpg';"
>
            <div>
                <h4><?php echo e($display_name); ?></h4>
                <small>Ward Councillor</small>
            </div>
        </div>

    </div>

</header>

<?php
$activePage = 'tickets';

// Values the shared sidebar expects
$userData['image_path'] = $profileImg;
$fullName               = $display_name;
$ward_id                = $user_ward_id;

include 'ward_sidebar.php';
?>
    

    <!--MAIN CONTENT-->

    <main
        class="main-content"
        id="mainContent"
    >


        <section class="page-heading">

            <h1>
                Ward Ticket Management
            </h1>

        </section>


        <!-- FILTERS -->

        <form
            method="GET"
            action="WardTicketManagement.php"
            class="filter-bar"
        >


            <div class="filter-group">


                <!-- WARD -->

                <select name="ward">

                    <option
                        value="all"
                        <?php
                        echo $selected_ward === "all"
                            ? "selected"
                            : "";
                        ?>
                    >
                        All Wards
                    </option>


                    <?php for (
                        $ward = 1;
                        $ward <= 14;
                        $ward++
                    ): ?>

                        <option
                            value="<?php echo $ward; ?>"
                            <?php
                            echo $selected_ward === (string)$ward
                                ? "selected"
                                : "";
                            ?>
                        >

                            Ward <?php echo $ward; ?>

                            <?php
                            if (
                                $ward === $user_ward_id
                            ) {
                                echo " - My Ward";
                            }
                            ?>

                        </option>

                    <?php endfor; ?>

                </select>


                <!-- SEARCH -->

                <input
                    type="text"
                    name="search"
                    placeholder="Search ID, description..."
                    value="<?php echo e($search_query); ?>"
                >


                <!-- STATUS -->

                <select name="status">

                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="1"
                        <?php
                        echo $status_filter === "1"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Pending
                    </option>

                    <option
                        value="2"
                        <?php
                        echo $status_filter === "2"
                            ? "selected"
                            : "";
                        ?>
                    >
                        In Progress
                    </option>

                    <option
                        value="3"
                        <?php
                        echo $status_filter === "3"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Resolved
                    </option>

                </select>


                <!-- PRIORITY -->

                <select name="priority">

                    <option value="">
                        All Priorities
                    </option>

                    <option
                        value="Low"
                        <?php
                        echo strtolower($priority_filter) === "low"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Low
                    </option>

                    <option
                        value="Normal"
                        <?php
                        echo strtolower($priority_filter) === "normal"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Normal
                    </option>

                    <option
                        value="High"
                        <?php
                        echo strtolower($priority_filter) === "high"
                            ? "selected"
                            : "";
                        ?>
                    >
                        High
                    </option>

                    <option
                        value="Urgent"
                        <?php
                        echo strtolower($priority_filter) === "urgent"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Urgent
                    </option>

                </select>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-filter"></i>

                    Filter

                </button>


                <a
                    href="WardTicketManagement.php"
                    class="btn btn-outline"
                >

                    Clear

                </a>


            </div>


            <!-- VIEW ALL / MY WARD -->

            <div class="filter-group">


                <?php if (
                    $selected_ward === "all"
                ): ?>


                    <a
                        href="WardTicketManagement.php?ward=<?php echo $user_ward_id; ?>"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-location-dot"></i>

                        View My Ward

                    </a>


                <?php else: ?>


                    <a
                        href="WardTicketManagement.php?ward=all"
                        class="btn btn-outline"
                    >

                        <i class="fa-solid fa-globe"></i>

                        View All Wards

                    </a>


                <?php endif; ?>


            </div>


        </form>


        <!-- TICKETS-->

        <div class="ticket-table-card">


            <table>
                <thead>

                    <tr>

                        <th>
                            Incident ID
                        </th>

                        <th>
                            Ward
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Priority
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Date Reported
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        mysqli_num_rows($result) > 0
                    ): ?>


                        <?php while (
                            $row =
                            mysqli_fetch_assoc($result)
                        ): ?>


                            <?php

                            $priority =
                                strtolower(
                                    $row["priority"] ?? ""
                                );


                            $important =
                                in_array(
                                    $priority,
                                    [
                                        "urgent",
                                        "high",
                                        "normal"
                                    ],
                                    true
                                );


                            $status =
                                $row["current_status"]
                                ?? "Unknown";


                            if (
                                $status === "Pending"
                            ) {

                                $status_class =
                                    "status-pending";

                            } elseif (
                                $status === "In Progress"
                            ) {

                                $status_class =
                                    "status-progress";

                            } elseif (
                                $status === "Resolved"
                            ) {

                                $status_class =
                                    "status-resolved";

                            } else {

                                $status_class =
                                    "status-unknown";
                            }

                            ?>


                            <tr>


                                <td>
                                    #<?php
                                    echo e(
                                        $row["incident_id"]
                                    );
                                    ?>
                                </td>


                                <td>

                                    <span class="badge-ward">

                                        Ward
                                        <?php
                                        echo e(
                                            $row["ward_id"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $row["service_type"]
                                        ??
                                        $row["service_id"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $row[
                                            "location_description"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $row[
                                            "incident_description"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>


                                    <?php if ($important): ?>

                                        <span
                                            class="priority-badge-red"
                                        >

                                            <?php
                                            echo e(
                                                ucfirst(
                                                    $priority
                                                )
                                            );
                                            ?>

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="priority-badge-normal"
                                        >

                                            <?php
                                            echo e(
                                                ucfirst(
                                                    $priority
                                                    ?: "Unknown"
                                                )
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <td>

                                    <span
                                        class="
                                            status-badge
                                            <?php
                                            echo e(
                                                $status_class
                                            );
                                            ?>
                                        "
                                    >

                                        <?php
                                        echo e($status);
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row[
                                                "date_reported"
                                            ]
                                        )
                                    ) {

                                        $date =
                                            strtotime(
                                                $row[
                                                    "date_reported"
                                                ]
                                            );


                                        if (
                                            $date !== false
                                        ) {

                                            echo e(
                                                date(
                                                    "d M Y",
                                                    $date
                                                )
                                            );

                                        } else {

                                            echo e(
                                                $row[
                                                    "date_reported"
                                                ]
                                            );
                                        }

                                    } else {

                                        echo "Not available";
                                    }

                                    ?>

                                </td>


                                <td>

                                    <a
                                        class="view-btn"
                                        href="ticketDetails.php?incident_id=<?php echo urlencode($row["incident_id"]); ?>"
                                    >

                                        <i
                                            class="fa-solid fa-eye"
                                        ></i>

                                        View

                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                class="no-tickets"
                            >

                                No tickets match the selected criteria.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </main>


</div>

    
   <!-- FOOTER -->
        <footer>
            <p>
                © 2026 M-LINK | Empowering Communities
            </p>
        </footer>


</body>
</html>

<?php

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>