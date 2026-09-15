<?php

session_start();

require_once "Error404Connection.php";


$search = trim($_GET["search"] ?? "");
$ward = trim($_GET["ward"] ?? "");
$status = trim($_GET["status"] ?? "");

$sql ="SELECT notice_id ,username ,ward ,notice_title ,publish_date ,expiry_date ,status ,notice_description ,date_posted
    FROM notices
    WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= "AND (notice_title LIKE ?
            OR notice_description LIKE ?
            OR ward LIKE ?)";

    $searchValue = "%" . $search . "%";                     // guys please dont remove this part, this is what is used if they want to search records
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $types .= "sss";
}

if ($ward !== "") {
    if ($ward === "Community-wide") {
        $sql .= "AND (ward = 'Community-wide'
                OR ward IS NULL
                OR ward = '')";
    }else {
        $sql .= " AND ward LIKE ?";
        $params[] = "%" . $ward . "%";
        $types .= "s";
    }
}

if ($status !== "") {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY date_posted DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Officer | Notices</title>
    <link rel="stylesheet" href="viewNotices.css">
</head>
<body>
<div class="container">
    <header class="header">
        <div>
            <span class="sub-header">MAKANA MUNICIPALITY</span>
            <h1>Communication Officer</h1>
        </div>
        <a href="communication_officer.php" class="btn-top"> Back to Dashboard</a>
    </header>

    <div class="page-header">
        <span class="section-tag">SYSTEM OVERVIEW</span>
        <h2>Makana System Overview</h2>
        <p>
            System-wide information from municipal notice records.
        </p>
    </div>
    <div class="card filter-card">
        <form method="GET" action="viewNotices.php">
            <div class="filter-group">
                <label for="search">Search Notices</label>
                <input type="text" id="search" name="search" placeholder="Search by title, description or ward... "value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-group">
                <label for="ward">Area</label>

                <select id="ward"
                    name="ward"
                >

                    <option value="">

                        All Areas

                    </option>

                    <option
                        value="Community-wide"
                        <?php
                        if ($ward === "Community-wide") {
                            echo "selected";
                        }
                        ?>
                    >

                        Community-wide

                    </option>

                    <?php for ($i = 1; $i <= 14; $i++): ?>

                        <option
                            value="Ward <?php echo $i; ?>"
                            <?php
                            if ($ward === "Ward " . $i) {
                                echo "selected";
                            }
                            ?>
                        >

                            Ward <?php echo $i; ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <div class="filter-group">

                <label for="status">

                    Status

                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option value="">

                        All Statuses

                    </option>

                    <option
                        value="Draft"
                        <?php
                        if ($status === "Draft") {
                            echo "selected";
                        }
                        ?>
                    >

                        Draft

                    </option>

                    <option
                        value="Approved"
                        <?php
                        if ($status === "Approved") {
                            echo "selected";
                        }
                        ?>
                    >

                        Approved

                    </option>

                </select>

            </div>


            <div class="filter-buttons">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Filter Notices

                </button>

                <a
                    href="viewNotices.php"
                    class="btn btn-secondary"
                >

                    Clear

                </a>

            </div>

        </form>

    </div>


    <!-- =================================================
         NOTICE COUNT SUMMARY
    ================================================== -->

    <div class="card count-card">

        <span class="sub-header">TOTAL MATCHES</span>

        <div class="count-value">

            <?php echo $result->num_rows; ?>

        </div>

        <span class="count-label">Recorded notice(s)</span>

    </div>


    <!-- =================================================
         NOTICES LIST
    ================================================== -->

    <div class="notices-list">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($notice = $result->fetch_assoc()): ?>

                <div class="card notice-card">

                    <div class="notice-top">

                        <div>

                            <span class="section-tag">NOTICE DETAILS</span>

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $notice["notice_title"]
                                );
                                ?>

                            </h3>

                        </div>


                        <?php if (
                            strtolower($notice["status"]) === "approved"
                        ): ?>

                            <span class="status status-approved">

                                Approved

                            </span>

                        <?php else: ?>

                            <span class="status status-draft">

                                <?php
                                echo htmlspecialchars(
                                    $notice["status"]
                                );
                                ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="notice-area">

                        <strong>Area:</strong>

                        <?php

                        if (
                            empty(trim($notice["ward"] ?? ""))
                        ) {

                            echo "Community-wide";

                        } else {

                            echo htmlspecialchars(
                                $notice["ward"]
                            );

                        }

                        ?>
                    </div>


                    <div class="notice-description">

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $notice["notice_description"]
                            )
                        );

                        ?>

                    </div>


                    <div class="notice-info">

                        <div>

                            <span class="info-label">Publish Date</span>

                            <strong>

                                <?php

                                echo date(
                                    "d M Y H:i",
                                    strtotime(
                                        $notice["publish_date"]
                                    )
                                );

                                ?>

                            </strong>

                        </div>


                        <div>

                            <span class="info-label">Expires</span>

                            <strong>

                                <?php

                                echo date(
                                    "d M Y H:i",
                                    strtotime(
                                        $notice["expiry_date"]
                                    )
                                );

                                ?>

                            </strong>

                        </div>


                        <div>

                            <span class="info-label">Posted By</span>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $notice["username"]
                                );

                                ?>

                            </strong>

                        </div>


                        <div>

                            <span class="info-label">Date Posted</span>

                            <strong>

                                <?php

                                echo date(
                                    "d M Y H:i",
                                    strtotime(
                                        $notice["date_posted"]
                                    )
                                );

                                ?>

                            </strong>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="card no-notices">

                <h3>No Notices Found</h3>

                <p>There are no notices matching your current filter criteria.</p>

            </div>

        <?php endif; ?>

    </div>

</div>


</body>

</html>


<?php

$stmt->close();

$conn->close();

?>