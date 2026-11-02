<?php

session_start();

require_once "Error404Connection.php";

//check if administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

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

$adminUsername = $_SESSION["username"];

$message = "";
$messageType = "";

// =====================================================
// GET WARDS
// =====================================================

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

// =====================================================
// GET MUNICIPAL SERVICES
// =====================================================

$services = [];

$serviceResult = $conn->query("
    SELECT DISTINCT service_type
    FROM municipal_service
    WHERE service_type IS NOT NULL
    AND TRIM(service_type) <> ''
    AND active = 1
    ORDER BY service_type ASC
");

if ($serviceResult) {

    while ($row = $serviceResult->fetch_assoc()) {

        $services[] = $row["service_type"];
    }
}

// =====================================================
// VERIFY ADMINISTRATOR PASSWORD
// =====================================================

function verifyAdminPassword(
    $conn,
    $adminUsername,
    $adminPassword
) {

    $sql = "
        SELECT password_hashed
        FROM admin
        WHERE username = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        return false;
    }

    $stmt->bind_param(
        "s",
        $adminUsername
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if (
        !$result ||
        $result->num_rows !== 1
    ) {

        $stmt->close();

        return false;
    }

    $admin = $result->fetch_assoc();

    $stmt->close();

    return password_verify(
        $adminPassword,
        $admin["password_hashed"]
    );
}

// =====================================================
// PASSWORD PROTECTED ACTIONS
// =====================================================

if (isset($_POST["verify_action_password"])) {

    $action =
        trim(
            $_POST["action_type"]
            ?? ""
        );

    $noticeId =
        intval(
            $_POST["notice_id"]
            ?? 0
        );

    $adminPassword =
        $_POST["admin_password"]
        ?? "";

    if (
        $noticeId <= 0 ||
        $adminPassword === ""
    ) {

        $message =
            "Please enter your administrator password.";

        $messageType =
            "error";

    } elseif (
        !in_array(
            $action,
            [
                "edit",
                "approve",
                "delete",
                "restore"
            ],
            true
        )
    ) {

        $message =
            "Invalid action.";

        $messageType =
            "error";

    } elseif (
        !verifyAdminPassword(
            $conn,
            $adminUsername,
            $adminPassword
        )
    ) {

        $message =
            "Incorrect administrator password.";

        $messageType =
            "error";

    } else {

        // =================================================
        // EDIT NOTICE
        // =================================================

        if ($action === "edit") {

            $checkStmt =
                $conn->prepare("
                    SELECT notice_id
                    FROM notices
                    WHERE notice_id = ?
                    AND deleted_at IS NULL
                    LIMIT 1
                ");

            if ($checkStmt) {

                $checkStmt->bind_param(
                    "i",
                    $noticeId
                );

                $checkStmt->execute();

                $checkResult =
                    $checkStmt->get_result();

                if (
                    $checkResult &&
                    $checkResult->num_rows === 1
                ) {

                    $_SESSION[
                        "notice_edit_verified"
                    ] = true;

                    $_SESSION[
                        "notice_edit_id"
                    ] = $noticeId;

                    $_SESSION[
                        "notice_edit_time"
                    ] = time();

                    $checkStmt->close();

                    header(
                        "Location: manageNotices.php?edit="
                        . $noticeId
                    );

                    exit();

                } else {

                    $message =
                        "This notice cannot be edited.";

                    $messageType =
                        "error";

                    $checkStmt->close();
                }

            } else {

                $message =
                    "Unable to prepare edit.";

                $messageType =
                    "error";
            }
        }

        // =================================================
        // APPROVE NOTICE
        // =================================================

        if ($action === "approve") {

            $stmt =
                $conn->prepare("
                    UPDATE notices
                    SET status = 'Approved'
                    WHERE notice_id = ?
                    AND deleted_at IS NULL
                ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $noticeId
                );

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {

                        $message =
                            "Notice approved successfully.";

                        $messageType =
                            "success";

                    } else {

                        $message =
                            "Notice is already approved or could not be found.";

                        $messageType =
                            "error";
                    }

                } else {

                    $message =
                        "Unable to approve notice.";

                    $messageType =
                        "error";
                }

                $stmt->close();

            } else {

                $message =
                    "Unable to prepare approval.";

                $messageType =
                    "error";
            }
        }

        // =================================================
        // SOFT DELETE NOTICE
        // =================================================

        if ($action === "delete") {

            $stmt =
                $conn->prepare("
                    UPDATE notices
                    SET
                        deleted_at = NOW(),
                        status = 'Archived'
                    WHERE notice_id = ?
                    AND deleted_at IS NULL
                ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $noticeId
                );

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {

                        $message =
                            "Notice deleted successfully.";

                        $messageType =
                            "success";

                    } else {

                        $message =
                            "Notice could not be found.";

                        $messageType =
                            "error";
                    }

                } else {

                    $message =
                        "Unable to delete notice.";

                    $messageType =
                        "error";
                }

                $stmt->close();

            } else {

                $message =
                    "Unable to prepare delete.";

                $messageType =
                    "error";
            }
        }

        // =================================================
        // RESTORE DELETED NOTICE
        // =================================================

        if ($action === "restore") {

            $stmt =
                $conn->prepare("
                    UPDATE notices
                    SET
                        deleted_at = NULL,
                        status = 'Approved'
                    WHERE notice_id = ?
                    AND deleted_at IS NOT NULL
                ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $noticeId
                );

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {

                        $message =
                            "Notice restored successfully.";

                        $messageType =
                            "success";

                    } else {

                        $message =
                            "Notice could not be restored.";

                        $messageType =
                            "error";
                    }

                } else {

                    $message =
                        "Unable to restore notice.";

                    $messageType =
                        "error";
                }

                $stmt->close();

            } else {

                $message =
                    "Unable to prepare restore.";

                $messageType =
                    "error";
            }
        }
    }
}

// =====================================================
// CHECK EDIT VERIFICATION
// =====================================================

$editVerified = false;

if (
    isset(
        $_SESSION[
            "notice_edit_verified"
        ]
    ) &&
    isset(
        $_SESSION[
            "notice_edit_id"
        ]
    ) &&
    isset(
        $_SESSION[
            "notice_edit_time"
        ]
    )
) {

    if (
        time() -
        $_SESSION[
            "notice_edit_time"
        ]
        <= 300
    ) {

        $editVerified = true;

    } else {

        unset(
            $_SESSION[
                "notice_edit_verified"
            ],
            $_SESSION[
                "notice_edit_id"
            ],
            $_SESSION[
                "notice_edit_time"
            ]
        );
    }
}

// =====================================================
// ADD NOTICE
// =====================================================

if (isset($_POST["add_notice"])) {

    $noticeTitle =
        trim(
            $_POST[
                "notice_title"
            ]
            ?? ""
        );

    $noticeCategory =
        trim(
            $_POST[
                "notice_category"
            ]
            ?? ""
        );

    $noticeDescription =
        trim(
            $_POST[
                "notice_description"
            ]
            ?? ""
        );

    $publishDate =
        $_POST[
            "publish_date"
        ]
        ?? "";

    $expiryDate =
        $_POST[
            "expiry_date"
        ]
        ?? "";

    $communityWide =
        isset(
            $_POST[
                "community_wide"
            ]
        );

    $selectedWards =
        $_POST[
            "wards"
        ]
        ?? [];

    if (
        $noticeTitle === "" ||
        $noticeCategory === "" ||
        $noticeDescription === "" ||
        $publishDate === "" ||
        $expiryDate === ""
    ) {

        $message =
            "Please complete all required fields.";

        $messageType =
            "error";

    } elseif (
        !in_array(
            $noticeCategory,
            $services,
            true
        )
    ) {

        $message =
            "Please select a valid service.";

        $messageType =
            "error";

    } elseif (
        strtotime($expiryDate)
        <=
        strtotime($publishDate)
    ) {

        $message =
            "The expiry date must be after the publish date.";

        $messageType =
            "error";

    } elseif (
        !$communityWide &&
        empty($selectedWards)
    ) {

        $message =
            "Please select at least one ward.";

        $messageType =
            "error";

    } else {

        $conn->begin_transaction();

        try {

            $stmt =
                $conn->prepare("
                    INSERT INTO notices
                    (
                        username,
                        notice_title,
                        notice_category,
                        publish_date,
                        expiry_date,
                        status,
                        notice_description,
                        date_posted,
                        deleted_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Approved',
                        ?,
                        NOW(),
                        NULL
                    )
                ");

            if (!$stmt) {

                throw new Exception(
                    $conn->error
                );
            }

            $stmt->bind_param(
                "ssssss",
                $adminUsername,
                $noticeTitle,
                $noticeCategory,
                $publishDate,
                $expiryDate,
                $noticeDescription
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    $stmt->error
                );
            }

            $noticeId =
                $conn->insert_id;

            $stmt->close();

            //insert ward links
            if (!$communityWide) {

                $wardStmt =
                    $conn->prepare("
                        INSERT INTO notice_ward
                        (
                            notice_id,
                            ward_id
                        )
                        VALUES (?, ?)
                    ");

                if (!$wardStmt) {

                    throw new Exception(
                        $conn->error
                    );
                }

                foreach (
                    $selectedWards
                    as $wardId
                ) {

                    $wardId =
                        intval($wardId);

                    if ($wardId <= 0) {

                        continue;
                    }

                    $wardStmt->bind_param(
                        "ii",
                        $noticeId,
                        $wardId
                    );

                    if (
                        !$wardStmt->execute()
                    ) {

                        throw new Exception(
                            $wardStmt->error
                        );
                    }
                }

                $wardStmt->close();
            }

            $conn->commit();

            $message =
                "Notice added successfully.";

            $messageType =
                "success";

        } catch (Throwable $error) {

            $conn->rollback();

            $message =
                "Unable to add notice: "
                . $error->getMessage();

            $messageType =
                "error";
        }
    }
}

// =====================================================
// EDIT NOTICE
// =====================================================

if (isset($_POST["edit_notice"])) {

    $noticeId =
        intval(
            $_POST[
                "notice_id"
            ]
            ?? 0
        );

    if (
        !$editVerified ||
        !isset(
            $_SESSION[
                "notice_edit_id"
            ]
        ) ||
        intval(
            $_SESSION[
                "notice_edit_id"
            ]
        ) !== $noticeId
    ) {

        $message =
            "Administrator password verification is required.";

        $messageType =
            "error";

    } else {

        $noticeTitle =
            trim(
                $_POST[
                    "notice_title"
                ]
                ?? ""
            );

        $noticeCategory =
            trim(
                $_POST[
                    "notice_category"
                ]
                ?? ""
            );

        $noticeDescription =
            trim(
                $_POST[
                    "notice_description"
                ]
                ?? ""
            );

        $publishDate =
            $_POST[
                "publish_date"
            ]
            ?? "";

        $expiryDate =
            $_POST[
                "expiry_date"
            ]
            ?? "";

        $communityWide =
            isset(
                $_POST[
                    "community_wide"
                ]
            );

        $selectedWards =
            $_POST[
                "wards"
            ]
            ?? [];

        if (
            $noticeTitle === "" ||
            $noticeCategory === "" ||
            $noticeDescription === "" ||
            $publishDate === "" ||
            $expiryDate === ""
        ) {

            $message =
                "Please complete all required fields.";

            $messageType =
                "error";

        } elseif (
            !in_array(
                $noticeCategory,
                $services,
                true
            )
        ) {

            $message =
                "Please select a valid service.";

            $messageType =
                "error";

        } elseif (
            strtotime($expiryDate)
            <=
            strtotime($publishDate)
        ) {

            $message =
                "The expiry date must be after the publish date.";

            $messageType =
                "error";

        } elseif (
            !$communityWide &&
            empty($selectedWards)
        ) {

            $message =
                "Please select at least one ward.";

            $messageType =
                "error";

        } else {

            $conn->begin_transaction();

            try {

                $stmt =
                    $conn->prepare("
                        UPDATE notices
                        SET
                            notice_title = ?,
                            notice_category = ?,
                            publish_date = ?,
                            expiry_date = ?,
                            notice_description = ?
                        WHERE notice_id = ?
                        AND deleted_at IS NULL
                    ");

                if (!$stmt) {

                    throw new Exception(
                        $conn->error
                    );
                }

                $stmt->bind_param(
                    "sssssi",
                    $noticeTitle,
                    $noticeCategory,
                    $publishDate,
                    $expiryDate,
                    $noticeDescription,
                    $noticeId
                );

                if (!$stmt->execute()) {

                    throw new Exception(
                        $stmt->error
                    );
                }

                $stmt->close();

                //remove old ward links
                $deleteWardStmt =
                    $conn->prepare("
                        DELETE FROM notice_ward
                        WHERE notice_id = ?
                    ");

                if (!$deleteWardStmt) {

                    throw new Exception(
                        $conn->error
                    );
                }

                $deleteWardStmt->bind_param(
                    "i",
                    $noticeId
                );

                if (
                    !$deleteWardStmt->execute()
                ) {

                    throw new Exception(
                        $deleteWardStmt->error
                    );
                }

                $deleteWardStmt->close();

                //insert new ward links
                if (!$communityWide) {

                    $wardStmt =
                        $conn->prepare("
                            INSERT INTO notice_ward
                            (
                                notice_id,
                                ward_id
                            )
                            VALUES (?, ?)
                        ");

                    if (!$wardStmt) {

                        throw new Exception(
                            $conn->error
                        );
                    }

                    foreach (
                        $selectedWards
                        as $wardId
                    ) {

                        $wardId =
                            intval($wardId);

                        if ($wardId <= 0) {

                            continue;
                        }

                        $wardStmt->bind_param(
                            "ii",
                            $noticeId,
                            $wardId
                        );

                        if (
                            !$wardStmt->execute()
                        ) {

                            throw new Exception(
                                $wardStmt->error
                            );
                        }
                    }

                    $wardStmt->close();
                }

                $conn->commit();

                $message =
                    "Notice updated successfully.";

                $messageType =
                    "success";

                unset(
                    $_SESSION[
                        "notice_edit_verified"
                    ],
                    $_SESSION[
                        "notice_edit_id"
                    ],
                    $_SESSION[
                        "notice_edit_time"
                    ]
                );

                $editVerified =
                    false;

            } catch (Throwable $error) {

                $conn->rollback();

                $message =
                    "Unable to update notice: "
                    . $error->getMessage();

                $messageType =
                    "error";
            }
        }
    }
}

// =====================================================
// GET NOTICE FOR EDITING
// =====================================================

$editNotice = null;

$editNoticeWards = [];

if (isset($_GET["edit"])) {

    $editId =
        intval(
            $_GET["edit"]
        );

    if (
        !$editVerified ||
        !isset(
            $_SESSION[
                "notice_edit_id"
            ]
        ) ||
        intval(
            $_SESSION[
                "notice_edit_id"
            ]
        ) !== $editId
    ) {

        $editId = 0;
    }

    if ($editId > 0) {

        $stmt =
            $conn->prepare("
                SELECT
                    notice_id,
                    username,
                    notice_title,
                    notice_category,
                    publish_date,
                    expiry_date,
                    status,
                    notice_description,
                    date_posted
                FROM notices
                WHERE notice_id = ?
                AND deleted_at IS NULL
                LIMIT 1
            ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $editId
            );

            $stmt->execute();

            $editResult =
                $stmt->get_result();

            if (
                $editResult &&
                $editResult->num_rows === 1
            ) {

                $editNotice =
                    $editResult->fetch_assoc();
            }

            $stmt->close();
        }

        if ($editNotice !== null) {

            $wardStmt =
                $conn->prepare("
                    SELECT ward_id
                    FROM notice_ward
                    WHERE notice_id = ?
                    ORDER BY ward_id
                ");

            if ($wardStmt) {

                $wardStmt->bind_param(
                    "i",
                    $editId
                );

                $wardStmt->execute();

                $wardEditResult =
                    $wardStmt->get_result();

                while (
                    $row =
                    $wardEditResult->fetch_assoc()
                ) {

                    $editNoticeWards[] =
                        intval(
                            $row["ward_id"]
                        );
                }

                $wardStmt->close();
            }
        }
    }
}

// =====================================================
// FILTERS
// =====================================================

$search =
    trim(
        $_GET["search"]
        ?? ""
    );

$wardFilter =
    $_GET[
        "ward_filter"
    ]
    ?? "all";

$statusFilter =
    $_GET[
        "status_filter"
    ]
    ?? "all";

$viewFilter =
    $_GET[
        "view_filter"
    ]
    ?? "current";

$allowedViews = [
    "current",
    "old",
    "deleted",
    "all"
];

if (
    !in_array(
        $viewFilter,
        $allowedViews,
        true
    )
) {

    $viewFilter =
        "current";
}

$params = [];

$types = "";

// =====================================================
// MAIN NOTICE QUERY
// =====================================================

$sql = "
    SELECT

        n.notice_id,
        n.username,
        n.notice_title,
        n.notice_category,
        n.publish_date,
        n.expiry_date,
        n.status,
        n.notice_description,
        n.date_posted,
        n.deleted_at,

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

    LEFT JOIN notice_ward nw
        ON n.notice_id = nw.notice_id

    LEFT JOIN ward w
        ON nw.ward_id = w.ward_id

    WHERE 1 = 1
";

// =====================================================
// CURRENT / OLD / DELETED / ALL
// =====================================================

if ($viewFilter === "current") {

    $sql .= "
        AND n.deleted_at IS NULL
        AND n.expiry_date >= NOW()
    ";

} elseif ($viewFilter === "old") {

    $sql .= "
        AND n.deleted_at IS NULL
        AND n.expiry_date < NOW()
    ";

} elseif ($viewFilter === "deleted") {

    $sql .= "
        AND n.deleted_at IS NOT NULL
    ";

}

// =====================================================
// SEARCH
// =====================================================

if ($search !== "") {

    $sql .= "
        AND
        (
            n.notice_title LIKE ?
            OR n.notice_category LIKE ?
            OR n.notice_description LIKE ?
        )
    ";

    $searchValue =
        "%"
        . $search
        . "%";

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $types .=
        "sss";
}

// =====================================================
// WARD FILTER
// =====================================================

if ($wardFilter === "community") {

    $sql .= "
        AND NOT EXISTS
        (
            SELECT 1
            FROM notice_ward communityWard
            WHERE communityWard.notice_id =
                  n.notice_id
        )
    ";

} elseif (
    ctype_digit(
        (string)$wardFilter
    )
) {

    $selectedWardId =
        intval(
            $wardFilter
        );

    $sql .= "
        AND EXISTS
        (
            SELECT 1
            FROM notice_ward selectedWard
            WHERE selectedWard.notice_id =
                  n.notice_id
            AND selectedWard.ward_id = ?
        )
    ";

    $params[] =
        $selectedWardId;

    $types .=
        "i";
}

// =====================================================
// STATUS FILTER
// =====================================================

$allowedStatuses = [
    "Draft",
    "Pending",
    "Approved",
    "Active",
    "Archived"
];

if (
    $statusFilter !== "all" &&
    in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND n.status = ?
    ";

    $params[] =
        $statusFilter;

    $types .=
        "s";
}

// =====================================================
// COMPLETE QUERY
// =====================================================

$sql .= "
    GROUP BY

        n.notice_id,
        n.username,
        n.notice_title,
        n.notice_category,
        n.publish_date,
        n.expiry_date,
        n.status,
        n.notice_description,
        n.date_posted,
        n.deleted_at

    ORDER BY n.date_posted DESC
";

$noticeStmt =
    $conn->prepare($sql);

if (!$noticeStmt) {

    die(
        "<pre>"
        . "DATABASE ERROR\n\n"
        . "RUNNING FILE:\n"
        . __FILE__
        . "\n\nERROR:\n"
        . $conn->error
        . "\n\nSQL:\n"
        . htmlspecialchars($sql)
        . "</pre>"
    );
}

if (!empty($params)) {

    $noticeStmt->bind_param(
        $types,
        ...$params
    );
}

if (!$noticeStmt->execute()) {

    die(
        "Unable to load notices: "
        . $noticeStmt->error
    );
}

$result =
    $noticeStmt->get_result();

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
    M-LINK | Manage Notices
</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f0e7;
    color: #333;
    min-height: 100vh;
}

.page-title {
    width: calc(100% - 70px);
    margin: 30px auto 0;
}

.page-title h1 {
    font-size: 26px;
    color: #263238;
}

.container {
    width: calc(100% - 70px);
    margin: 20px auto 35px;
}

.message {
    padding: 13px 16px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-size: 14px;
}

.message.success {
    background: #edf3df;
    color: #4b561b;
    border: 1px solid #c8d5a8;
}

.message.error {
    background: #f1dfda;
    color: #a22d1d;
    border: 1px solid #e2c1ba;
}

.filter-container {
    background: white;
    padding: 18px 20px;
    border: 1px solid #ddd7ca;
    border-radius: 7px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-form input,
.filter-form select {
    height: 40px;
    padding: 0 11px;
    border: 1px solid #d7dbe0;
    border-radius: 5px;
    background: #f7f8fa;
    font-size: 13px;
}

.filter-form input {
    flex: 1;
    min-width: 200px;
}

.filter-form select {
    min-width: 150px;
}

.form-container {
    background: white;
    padding: 25px;
    border: 1px solid #ddd7ca;
    border-radius: 7px;
    margin-bottom: 25px;
}

.form-container h3 {
    margin-bottom: 22px;
    font-size: 18px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d7dbe0;
    border-radius: 5px;
    background: #f7f8fa;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 13px;
}

.form-group textarea {
    min-height: 130px;
    resize: vertical;
}

.ward-selection {
    border: 1px solid #d7dbe0;
    border-radius: 5px;
    background: #f7f8fa;
    padding: 15px;
}

.community-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid #ddd;
    font-weight: 600;
}

.community-option input {
    width: auto;
}

.ward-checkboxes {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(100px, 1fr));
    gap: 10px;
}

.ward-option {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
}

.ward-option input {
    width: auto;
}

.form-buttons {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.button {
    min-height: 38px;
    padding: 0 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.button-primary,
.button-approve {
    background: #5f7022;
    color: white;
}

.button-secondary {
    background: white;
    color: #555;
    border: 1px solid #d1cbc0;
}

.button-edit {
    background: #6d7780;
    color: white;
}

.button-delete {
    background: #f1dfda;
    color: #a22d1d;
}

.button-restore {
    background: #e1ecd2;
    color: #4b681c;
}

.table-container {
    background: white;
    border: 1px solid #ddd7ca;
    border-radius: 7px;
    overflow-x: auto;
}

.table-container h3 {
    padding: 22px 25px;
    border-bottom: 1px solid #e5e0d7;
    font-size: 18px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
}

thead {
    background: #f7f5ef;
}

th {
    padding: 14px 16px;
    text-align: left;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    border-bottom: 1px solid #ddd7ca;
}

td {
    padding: 14px 16px;
    border-bottom: 1px solid #ebe7df;
    vertical-align: top;
    font-size: 13px;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.status-approved,
.status-active {
    background: #e1ecd2;
    color: #4b681c;
}

.status-draft {
    background: #eeeae1;
    color: #666;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-archived {
    background: #e8e8e8;
    color: #666;
}

.status-deleted {
    background: #f1dfda;
    color: #a22d1d;
}

.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.action-buttons .button {
    min-height: 32px;
    padding: 0 11px;
    font-size: 12px;
}

.notice-area {
    max-width: 220px;
    line-height: 1.5;
}

.description {
    max-width: 250px;
    line-height: 1.5;
}

.no-notices {
    text-align: center;
    padding: 50px;
    color: #777;
}

.password-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(38, 50, 56, 0.65);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.password-modal-content {
    background: #f4f0e7;
    width: 460px;
    max-width: 100%;
    padding: 25px;
    border-radius: 10px;
}

.password-modal-content h3 {
    color: #4b561b;
    margin-bottom: 10px;
}

.password-modal-content p {
    margin-bottom: 18px;
    font-size: 14px;
}

.password-modal-content label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
}

.password-input-container {
    position: relative;
}

.password-input-container input {
    width: 100%;
    height: 44px;
    padding: 0 75px 0 12px;
    border: 1px solid #b9bec1;
    border-radius: 5px;
}

.show-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: none;
    cursor: pointer;
    color: #4b561b;
}

.password-modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

@media (max-width: 800px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .ward-checkboxes {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .container {
        width: calc(100% - 36px);
    }

    .filter-form {
        flex-direction: column;
    }

    .filter-form input,
    .filter-form select,
    .filter-form .button {
        width: 100%;
    }
}

@media (max-width: 550px) {

    .ward-checkboxes {
        grid-template-columns: 1fr;
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
$adminActivePage = "notices";
require __DIR__ . "/adminHeader.php";
?>

<div class="page-title">
<h1>
    Manage Notices
</h1>
</div>

<div class="container">

<?php if ($message !== ""): ?>

<div
    class="message <?= htmlspecialchars(
        $messageType
    ) ?>"
>

<?= htmlspecialchars(
    $message
) ?>

</div>

<?php endif; ?>



<!-- ADD OR EDIT -->

<div class="form-container">

<?php if (
    $editNotice !== null
): ?>

<h3>
    Edit Notice
</h3>

<form
    method="POST"
    action="manageNotices.php"
>

<input
    type="hidden"
    name="notice_id"
    value="<?= (int)$editNotice[
        "notice_id"
    ] ?>"
>

<div class="form-grid">

<div class="form-group">

<label>
    Notice Title
</label>

<input
    type="text"
    name="notice_title"
    required
    value="<?= htmlspecialchars(
        $editNotice[
            "notice_title"
        ]
    ) ?>"
>

</div>

<div class="form-group">

<label>
    Service
</label>

<select
    name="notice_category"
    required
>

<option
    value=""
    disabled
>
    Select Service
</option>

<?php foreach (
    $services
    as $service
): ?>

<option
    value="<?= htmlspecialchars(
        $service
    ) ?>"
    <?= (
        $editNotice[
            "notice_category"
        ]
        === $service
    )
        ? "selected"
        : ""
    ?>
>

    <?= htmlspecialchars(
        $service
    ) ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group full">

<label>
    Notice Area
</label>

<div class="ward-selection">

<label class="community-option">

<input
    type="checkbox"
    name="community_wide"
    id="communityWide"
    value="1"
    <?= empty(
        $editNoticeWards
    )
        ? "checked"
        : ""
    ?>
>

Community-wide

</label>

<div class="ward-checkboxes">

<?php foreach (
    $wards
    as $wardRow
): ?>

<label class="ward-option">

<input
    type="checkbox"
    name="wards[]"
    class="ward-checkbox"
    value="<?= (int)$wardRow[
        "ward_id"
    ] ?>"
    <?= in_array(
        (int)$wardRow[
            "ward_id"
        ],
        $editNoticeWards,
        true
    )
        ? "checked"
        : ""
    ?>
>

Ward <?= (int)$wardRow[
    "ward_number"
] ?>

</label>

<?php endforeach; ?>

</div>

</div>

</div>

<div class="form-group">

<label>
    Publish Date
</label>

<input
    type="datetime-local"
    name="publish_date"
    required
    value="<?= date(
        "Y-m-d\TH:i",
        strtotime(
            $editNotice[
                "publish_date"
            ]
        )
    ) ?>"
>

</div>

<div class="form-group">

<label>
    Expiry Date
</label>

<input
    type="datetime-local"
    name="expiry_date"
    required
    value="<?= date(
        "Y-m-d\TH:i",
        strtotime(
            $editNotice[
                "expiry_date"
            ]
        )
    ) ?>"
>

</div>

<div class="form-group full">

<label>
    Notice Description
</label>

<textarea
    name="notice_description"
    required
><?= htmlspecialchars(
    $editNotice[
        "notice_description"
    ]
) ?></textarea>

</div>

</div>

<div class="form-buttons">

<button
    type="submit"
    name="edit_notice"
    class="button button-primary"
>
    Save Changes
</button>

<a
    href="manageNotices.php"
    class="button button-secondary"
>
    Cancel
</a>

</div>

</form>

<?php else: ?>

<h3>
    Add New Notice
</h3>

<form
    method="POST"
    action="manageNotices.php"
>

<div class="form-grid">

<div class="form-group">

<label>
    Notice Title
</label>

<input
    type="text"
    name="notice_title"
    required
>

</div>

<div class="form-group">

<label>
    Service
</label>

<select
    name="notice_category"
    required
>

<option
    value=""
    disabled
    selected
>
    Select Service
</option>

<?php foreach (
    $services
    as $service
): ?>

<option
    value="<?= htmlspecialchars(
        $service
    ) ?>"
>

    <?= htmlspecialchars(
        $service
    ) ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group full">

<label>
    Notice Area
</label>

<div class="ward-selection">

<label class="community-option">

<input
    type="checkbox"
    name="community_wide"
    id="communityWide"
    value="1"
    checked
>

Community-wide

</label>

<div class="ward-checkboxes">

<?php foreach (
    $wards
    as $wardRow
): ?>

<label class="ward-option">

<input
    type="checkbox"
    name="wards[]"
    class="ward-checkbox"
    value="<?= (int)$wardRow[
        "ward_id"
    ] ?>"
>

Ward <?= (int)$wardRow[
    "ward_number"
] ?>

</label>

<?php endforeach; ?>

</div>

</div>

</div>

<div class="form-group">

<label>
    Publish Date
</label>

<input
    type="datetime-local"
    name="publish_date"
    required
>

</div>

<div class="form-group">

<label>
    Expiry Date
</label>

<input
    type="datetime-local"
    name="expiry_date"
    required
>

</div>

<div class="form-group full">

<label>
    Notice Description
</label>

<textarea
    name="notice_description"
    required
></textarea>

</div>

</div>

<div class="form-buttons">

<button
    type="submit"
    name="add_notice"
    class="button button-primary"
>
    Add Notice
</button>

</div>

</form>

<?php endif; ?>

</div>

<!-- FILTER -->

<div class="filter-container">

<form
    method="GET"
    action="manageNotices.php"
    class="filter-form"
>

<input
    type="text"
    name="search"
    placeholder="Search notices..."
    value="<?= htmlspecialchars(
        $search
    ) ?>"
>

<select name="view_filter">

<option
    value="current"
    <?= $viewFilter === "current"
        ? "selected"
        : ""
    ?>
>
    Current Notices
</option>

<option
    value="old"
    <?= $viewFilter === "old"
        ? "selected"
        : ""
    ?>
>
    Old Notices
</option>

<option
    value="deleted"
    <?= $viewFilter === "deleted"
        ? "selected"
        : ""
    ?>
>
    Deleted Notices
</option>

<option
    value="all"
    <?= $viewFilter === "all"
        ? "selected"
        : ""
    ?>
>
    All Notices
</option>

</select>

<select name="ward_filter">

<option
    value="all"
    <?= $wardFilter === "all"
        ? "selected"
        : ""
    ?>
>
    All Wards
</option>

<option
    value="community"
    <?= $wardFilter === "community"
        ? "selected"
        : ""
    ?>
>
    Community-wide
</option>

<?php foreach (
    $wards
    as $wardRow
): ?>

<option
    value="<?= (int)$wardRow[
        "ward_id"
    ] ?>"
    <?= (
        $wardFilter ===
        (string)$wardRow[
            "ward_id"
        ]
    )
        ? "selected"
        : ""
    ?>
>

    Ward <?= (int)$wardRow[
        "ward_number"
    ] ?>

</option>

<?php endforeach; ?>

</select>

<select name="status_filter">

<option
    value="all"
    <?= $statusFilter === "all"
        ? "selected"
        : ""
    ?>
>
    All Statuses
</option>

<option
    value="Draft"
    <?= $statusFilter === "Draft"
        ? "selected"
        : ""
    ?>
>
    Draft
</option>

<option
    value="Pending"
    <?= $statusFilter === "Pending"
        ? "selected"
        : ""
    ?>
>
    Pending
</option>

<option
    value="Approved"
    <?= $statusFilter === "Approved"
        ? "selected"
        : ""
    ?>
>
    Approved
</option>

<option
    value="Active"
    <?= $statusFilter === "Active"
        ? "selected"
        : ""
    ?>
>
    Active
</option>

<option
    value="Archived"
    <?= $statusFilter === "Archived"
        ? "selected"
        : ""
    ?>
>
    Archived
</option>

</select>

<button
    type="submit"
    class="button button-primary"
>
    Filter
</button>

<a
    href="manageNotices.php"
    class="button button-secondary"
>
    Clear
</a>

</form>

</div>

<!-- NOTICE TABLE -->

<div class="table-container">

<h3>

<?php

if ($viewFilter === "old") {

    echo "Old Notices";

} elseif (
    $viewFilter === "deleted"
) {

    echo "Deleted Notices";

} elseif (
    $viewFilter === "all"
) {

    echo "All Notices";

} else {

    echo "Current Notices";
}

?>

</h3>

<?php if (
    $result &&
    $result->num_rows > 0
): ?>

<table>

<thead>

<tr>

<th>ID</th>

<th>Title</th>

<th>Service</th>

<th>Area</th>

<th>Publish Date</th>

<th>Expiry Date</th>

<th>Status</th>

<th>Description</th>

<th>Posted By</th>

<th>Actions</th>

</tr>

</thead>

<tbody>

<?php while (
    $notice =
    $result->fetch_assoc()
): ?>

<?php

$noticeStatus =
    strtolower(
        trim(
            $notice[
                "status"
            ]
        )
    );

$isDeleted =
    !empty(
        $notice[
            "deleted_at"
        ]
    );

$isExpired =
    strtotime(
        $notice[
            "expiry_date"
        ]
    ) < time();

?>

<tr>

<td>

<?= (int)$notice[
    "notice_id"
] ?>

</td>

<td>

<?= htmlspecialchars(
    $notice[
        "notice_title"
    ]
) ?>

</td>

<td>

<?= htmlspecialchars(
    $notice[
        "notice_category"
    ]
) ?>

</td>

<td>

<div class="notice-area">

<?php

if (
    intval(
        $notice[
            "ward_count"
        ]
    ) === 0
) {

    echo "Community-wide";

} else {

    echo htmlspecialchars(
        $notice[
            "ward_list"
        ]
    );
}

?>

</div>

</td>

<td>

<?= date(
    "d M Y H:i",
    strtotime(
        $notice[
            "publish_date"
        ]
    )
) ?>

</td>

<td>

<?= date(
    "d M Y H:i",
    strtotime(
        $notice[
            "expiry_date"
        ]
    )
) ?>

</td>

<td>

<?php if ($isDeleted): ?>

<span class="status status-deleted">
    Deleted
</span>

<?php elseif ($isExpired): ?>

<span class="status status-archived">
    Old
</span>

<?php elseif (
    $noticeStatus === "approved"
): ?>

<span class="status status-approved">
    Approved
</span>

<?php elseif (
    $noticeStatus === "active"
): ?>

<span class="status status-active">
    Active
</span>

<?php elseif (
    $noticeStatus === "pending"
): ?>

<span class="status status-pending">
    Pending
</span>

<?php elseif (
    $noticeStatus === "archived"
): ?>

<span class="status status-archived">
    Archived
</span>

<?php else: ?>

<span class="status status-draft">

<?= htmlspecialchars(
    ucfirst(
        $noticeStatus
    )
) ?>

</span>

<?php endif; ?>

</td>

<td>

<div class="description">

<?php

$description =
    $notice[
        "notice_description"
    ];

if (
    strlen(
        $description
    ) > 150
) {

    echo htmlspecialchars(
        substr(
            $description,
            0,
            150
        )
    )
    . "...";

} else {

    echo htmlspecialchars(
        $description
    );
}

?>

</div>

</td>

<td>

<?= htmlspecialchars(
    $notice[
        "username"
    ]
) ?>

</td>

<td>

<div class="action-buttons">

<?php if ($isDeleted): ?>

<button
    type="button"
    class="button button-restore"
    onclick="openPasswordPopup(
        'restore',
        <?= (int)$notice[
            "notice_id"
        ] ?>
    )"
>
    Restore
</button>

<?php else: ?>

<button
    type="button"
    class="button button-edit"
    onclick="openPasswordPopup(
        'edit',
        <?= (int)$notice[
            "notice_id"
        ] ?>
    )"
>
    Edit
</button>

<?php if (
    $noticeStatus === "pending" ||
    $noticeStatus === "draft"
): ?>

<button
    type="button"
    class="button button-approve"
    onclick="openPasswordPopup(
        'approve',
        <?= (int)$notice[
            "notice_id"
        ] ?>
    )"
>
    Approve
</button>

<?php endif; ?>

<button
    type="button"
    class="button button-delete"
    onclick="openPasswordPopup(
        'delete',
        <?= (int)$notice[
            "notice_id"
        ] ?>
    )"
>
    Delete
</button>

<?php endif; ?>

</div>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<div class="no-notices">

<?php

if ($viewFilter === "deleted") {

    echo "No deleted notices found.";

} elseif (
    $viewFilter === "old"
) {

    echo "No old notices found.";

} else {

    echo "No notices found.";
}

?>

</div>

<?php endif; ?>

</div>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<!-- PASSWORD POPUP -->

<div
    class="password-modal"
    id="passwordModal"
>

<div class="password-modal-content">

<h3 id="passwordModalTitle">
    Administrator Verification
</h3>

<p id="passwordModalMessage">
    Enter your administrator password to continue.
</p>

<form
    method="POST"
    action="manageNotices.php"
>

<input
    type="hidden"
    name="verify_action_password"
    value="1"
>

<input
    type="hidden"
    name="action_type"
    id="actionType"
>

<input
    type="hidden"
    name="notice_id"
    id="actionNoticeId"
>

<label>
    Administrator Password
</label>

<div class="password-input-container">

<input
    type="password"
    name="admin_password"
    id="adminPassword"
    required
>

<button
    type="button"
    class="show-password"
    onclick="togglePassword()"
>
    Show
</button>

</div>

<div class="password-modal-buttons">

<button
    type="button"
    class="button button-secondary"
    onclick="closePasswordPopup()"
>
    Cancel
</button>

<button
    type="submit"
    class="button button-primary"
>
    Confirm
</button>

</div>

</form>

</div>

</div>

<script>

const communityWide =
    document.getElementById(
        "communityWide"
    );

const wardCheckboxes =
    document.querySelectorAll(
        ".ward-checkbox"
    );

function updateWardSelection()
{

    if (!communityWide) {

        return;
    }

    wardCheckboxes.forEach(
        function(checkbox)
        {

            if (
                communityWide.checked
            ) {

                checkbox.checked =
                    false;

                checkbox.disabled =
                    true;

            } else {

                checkbox.disabled =
                    false;
            }
        }
    );
}

if (communityWide) {

    communityWide.addEventListener(
        "change",
        updateWardSelection
    );

    updateWardSelection();
}

function openPasswordPopup(
    action,
    noticeId
)
{

    const modal =
        document.getElementById(
            "passwordModal"
        );

    const title =
        document.getElementById(
            "passwordModalTitle"
        );

    const message =
        document.getElementById(
            "passwordModalMessage"
        );

    document.getElementById(
        "actionType"
    ).value =
        action;

    document.getElementById(
        "actionNoticeId"
    ).value =
        noticeId;

    const password =
        document.getElementById(
            "adminPassword"
        );

    password.value =
        "";

    password.type =
        "password";

    document.querySelector(
        ".show-password"
    ).textContent =
        "Show";

    if (action === "edit") {

        title.textContent =
            "Edit Notice";

        message.textContent =
            "Enter your administrator password to edit this notice.";
    }

    if (action === "approve") {

        title.textContent =
            "Approve Notice";

        message.textContent =
            "Enter your administrator password to approve this notice.";
    }

    if (action === "delete") {

        title.textContent =
            "Delete Notice";

        message.textContent =
            "Enter your administrator password to delete this notice.";
    }

    if (action === "restore") {

        title.textContent =
            "Restore Notice";

        message.textContent =
            "Enter your administrator password to restore this notice.";
    }

    modal.style.display =
        "flex";

    password.focus();
}

function closePasswordPopup()
{

    document.getElementById(
        "passwordModal"
    ).style.display =
        "none";
}

function togglePassword()
{

    const password =
        document.getElementById(
            "adminPassword"
        );

    const button =
        document.querySelector(
            ".show-password"
        );

    if (
        password.type ===
        "password"
    ) {

        password.type =
            "text";

        button.textContent =
            "Hide";

    } else {

        password.type =
            "password";

        button.textContent =
            "Show";
    }
}

window.addEventListener(
    "click",
    function(event)
    {

        const modal =
            document.getElementById(
                "passwordModal"
            );

        if (
            event.target === modal
        ) {

            closePasswordPopup();
        }
    }
);

document.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key ===
            "Escape"
        ) {

            closePasswordPopup();
        }
    }
);

</script>

</body>

</html>

<?php

$noticeStmt->close();

$conn->close();

?>