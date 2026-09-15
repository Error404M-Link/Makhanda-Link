<?php 
require_once "Error404Connection.php";
 
$message = ""; 
$messageType = ""; 
 
 
//fixed ward list 
$wards = array( 
    "Ward 1", 
    "Ward 2", 
    "Ward 3", 
    "Ward 4", 
    "Ward 5", 
    "Ward 6", 
    "Ward 7", 
    "Ward 8", 
    "Ward 9", 
    "Ward 10", 
    "Ward 11", 
    "Ward 12", 
    "Ward 13", 
    "Ward 14" 
); 
 
 
//check if a notice is being added 
if (isset($_POST["add_notice"])) { 
 
    $noticeTitle = trim($_POST["notice_title"] ?? ""); 
    $noticeCategory = trim($_POST["notice_category"] ?? ""); 
    $noticeDescription = trim($_POST["notice_description"] ?? ""); 
    $selectedWards = $_POST["ward"] ?? []; 
    $publishDate = $_POST["publish_date"] ?? ""; 
    $expiryDate = $_POST["expiry_date"] ?? ""; 
 
    //make sure selected wards is an array 
    if (!is_array($selectedWards)) { 
        $selectedWards = []; 
    } 
 
    //remove invalid wards 
    $selectedWards = array_values(array_intersect($selectedWards, $wards)); 
 
    if ( 
        $noticeTitle === "" || 
        $noticeCategory === "" || 
        $noticeDescription === "" || 
        $publishDate === "" || 
        $expiryDate === "" 
    ) { 
 
        $message = "Please complete all required fields."; 
        $messageType = "error"; 
 
    } elseif (count($selectedWards) === 0) { 
 
        $message = "Please select at least one ward or Community-wide."; 
        $messageType = "error"; 
 
    } elseif (strtotime($expiryDate) <= strtotime($publishDate)) { 
 
        $message = "The expiry date must be after the publish date."; 
        $messageType = "error"; 
 
    } else { 
 
        //store selected wards as comma-separated values 
        $wardValue = implode(", ", $selectedWards); 
 
        $status = "Draft"; 
 
        $sql = "INSERT INTO notices 
                (username, ward, notice_title, notice_category, publish_date, expiry_date, status, notice_description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; 
 
        $stmt = $conn->prepare($sql); 
 
        if ($stmt) { 
 
            $stmt->bind_param( 
                "ssssssss", 
                $adminUsername, 
                $wardValue, 
                $noticeTitle, 
                $noticeCategory, 
                $publishDate, 
                $expiryDate, 
                $status, 
                $noticeDescription 
            ); 
 
            if ($stmt->execute()) { 
 
                $message = "Notice added successfully."; 
                $messageType = "success"; 
 
            } else { 
 
                $message = "Unable to add the notice."; 
                $messageType = "error"; 
 
            } 
 
            $stmt->close(); 
 
        } else { 
 
            $message = "Unable to prepare the notice."; 
            $messageType = "error"; 
 
        } 
    } 
} 
 
 
//check if a notice is being updated 
if (isset($_POST["edit_notice"])) { 
 
    $noticeId = intval($_POST["notice_id"] ?? 0); 
    $noticeTitle = trim($_POST["notice_title"] ?? ""); 
    $noticeCategory = trim($_POST["notice_category"] ?? ""); 
    $noticeDescription = trim($_POST["notice_description"] ?? ""); 
    $selectedWards = $_POST["ward"] ?? []; 
    $publishDate = $_POST["publish_date"] ?? ""; 
    $expiryDate = $_POST["expiry_date"] ?? ""; 
 
    //make sure selected wards is an array 
    if (!is_array($selectedWards)) { 
        $selectedWards = []; 
    } 
 
    //remove invalid wards 
    $selectedWards = array_values(array_intersect($selectedWards, $wards)); 
 
    if ( 
        $noticeId <= 0 || 
        $noticeTitle === "" || 
        $noticeCategory === "" || 
        $noticeDescription === "" || 
        $publishDate === "" || 
        $expiryDate === "" 
    ) { 
 
        $message = "Please complete all required fields."; 
        $messageType = "error"; 
 
    } elseif (count($selectedWards) === 0) { 
 
        $message = "Please select at least one ward or Community-wide."; 
        $messageType = "error"; 
 
    } elseif (strtotime($expiryDate) <= strtotime($publishDate)) { 
 
        $message = "The expiry date must be after the publish date."; 
        $messageType = "error"; 
 
    } else { 
 
        //store selected wards as comma-separated values 
        $wardValue = implode(", ", $selectedWards); 
 
        $sql = "UPDATE notices 
                SET ward = ?, 
                    notice_title = ?, 
                    notice_category = ?, 
                    publish_date = ?, 
                    expiry_date = ?, 
                    notice_description = ? 
                WHERE notice_id = ?"; 
 
        $stmt = $conn->prepare($sql); 
 
        if ($stmt) { 
 
            $stmt->bind_param( 
                "ssssssi", 
                $wardValue, 
                $noticeTitle, 
                $noticeCategory, 
                $publishDate, 
                $expiryDate, 
                $noticeDescription, 
                $noticeId 
            ); 
 
            if ($stmt->execute()) { 
 
                $message = "Notice updated successfully."; 
                $messageType = "success"; 
 
            } else { 
 
                $message = "Unable to update the notice."; 
                $messageType = "error"; 
 
            } 
 
            $stmt->close(); 
 
        } else { 
 
            $message = "Unable to prepare the update."; 
            $messageType = "error"; 
 
        } 
    } 
} 
 
 
//check if a notice is being approved 
if (isset($_POST["approve_notice"])) { 
 
    $noticeId = intval($_POST["notice_id"] ?? 0); 
 
    if ($noticeId > 0) { 
 
        $sql = "UPDATE notices 
                SET status = 'Approved' 
                WHERE notice_id = ?"; 
 
        $stmt = $conn->prepare($sql); 
 
        if ($stmt) { 
 
            $stmt->bind_param("i", $noticeId); 
 
            if ($stmt->execute()) { 
 
                $message = "Notice approved successfully."; 
                $messageType = "success"; 
 
            } else { 
 
                $message = "Unable to approve the notice."; 
                $messageType = "error"; 
 
            } 
 
            $stmt->close(); 
 
        } else { 
 
            $message = "Unable to prepare the approval."; 
            $messageType = "error"; 
 
        } 
    } 
} 
 
 
//check if a notice is being deleted 
if (isset($_POST["delete_notice"])) { 
 
    $noticeId = intval($_POST["notice_id"] ?? 0); 
 
    if ($noticeId > 0) { 
 
        $sql = "DELETE FROM notices 
                WHERE notice_id = ?"; 
 
        $stmt = $conn->prepare($sql); 
 
        if ($stmt) { 
 
            $stmt->bind_param("i", $noticeId); 
 
            if ($stmt->execute()) { 
 
                $message = "Notice deleted successfully."; 
                $messageType = "success"; 
 
            } else { 
 
                $message = "Unable to delete the notice."; 
                $messageType = "error"; 
 
            } 
 
            $stmt->close(); 
 
        } else { 
 
            $message = "Unable to prepare the delete."; 
            $messageType = "error"; 
 
        } 
    } 
} 
 
 
//get notice to edit 
$editNotice = null; 
 
if (isset($_GET["edit"])) { 
 
    $editId = intval($_GET["edit"]); 
 
    if ($editId > 0) { 
 
        $sql = "SELECT notice_id, 
                       username, 
                       ward, 
                       notice_title, 
                       notice_category, 
                       publish_date, 
                       expiry_date, 
                       status, 
                       notice_description, 
                       date_posted 
                FROM notices 
                WHERE notice_id = ?"; 
 
        $stmt = $conn->prepare($sql); 
 
        if ($stmt) { 
 
            $stmt->bind_param("i", $editId); 
            $stmt->execute(); 
 
            $result = $stmt->get_result(); 
 
            if ($result->num_rows > 0) { 
 
                $editNotice = $result->fetch_assoc(); 
 
            } 
 
            $stmt->close(); 
 
        } 
    } 
} 
 
 
//get all notices 
$sql = "SELECT notice_id, 
               username, 
               ward, 
               notice_title, 
               notice_category, 
               publish_date, 
               expiry_date, 
               status, 
               notice_description, 
               date_posted 
        FROM notices 
        ORDER BY date_posted DESC"; 
 
$result = $conn->query($sql); 
 
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>M-LINK | Manage Notices</title> 
<link rel="stylesheet" href="createnotices.css">
</head> 

<body> 

<div class="navbar"> 

    <h1>M-LINK | Manage Notices</h1> 

    <a href="admin.php">Back to Dashboard</a> 

</div> 


<div class="container"> 

    <div class="page-header"> 

        <h2>Manage Notices</h2> 

        <p>Add, edit, approve and delete community-wide or ward-specific notices.</p> 

    </div> 


    <?php if ($message !== ""): ?> 

        <div class="message <?php echo $messageType; ?>"> 

            <?php echo htmlspecialchars($message); ?> 

        </div> 

    <?php endif; ?> 


    <div class="form-container"> 

        <?php if ($editNotice !== null): ?> 

            <h3>Edit Notice</h3> 

            <form method="POST" action="manageNotices.php"> 

                <input 
                    type="hidden" 
                    name="notice_id" 
                    value="<?php echo $editNotice["notice_id"]; ?>" 
                > 

                <div class="form-grid"> 

                    <div class="form-group"> 

                        <label for="notice_title">Notice Title</label> 

                        <input 
                            type="text" 
                            id="notice_title" 
                            name="notice_title" 
                            maxlength="255" 
                            required 
                            value="<?php echo htmlspecialchars($editNotice["notice_title"]); ?>" 
                        > 

                    </div> 


                    <div class="form-group"> 

                        <label for="notice_category">Notice Category</label> 

                        <input 
                            type="text" 
                            id="notice_category" 
                            name="notice_category" 
                            maxlength="50" 
                            required 
                            value="<?php echo htmlspecialchars($editNotice["notice_category"]); ?>" 
                        > 

                    </div> 


                    <div class="form-group"> 

                        <label>Notice Area</label> 

                        <div class="ward-selection">

                            <?php
                            $currentWardValue = $editNotice["ward"] ?? "";
                            $currentWards = array_map("trim", explode(",", $currentWardValue));
                            ?>

                            <label class="ward-option community-option">

                                <input
                                    type="checkbox"
                                    name="ward[]"
                                    value="Community-wide"
                                    <?php
                                    echo in_array("Community-wide", $currentWards)
                                        ? "checked"
                                        : "";
                                    ?>
                                >

                                Community-wide

                            </label>

                            <?php foreach ($wards as $wardOption): ?>

                                <label class="ward-option">

                                    <input
                                        type="checkbox"
                                        name="ward[]"
                                        value="<?php echo htmlspecialchars($wardOption); ?>"
                                        <?php
                                        echo in_array($wardOption, $currentWards)
                                            ? "checked"
                                            : "";
                                        ?>
                                    >

                                    <?php echo htmlspecialchars($wardOption); ?>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div> 


                    <div class="form-group"> 

                        <label for="publish_date">Publish Date</label> 

                        <input 
                            type="datetime-local" 
                            id="publish_date" 
                            name="publish_date" 
                            required 
                            value="<?php echo date("Y-m-d\TH:i", strtotime($editNotice["publish_date"])); ?>" 
                        > 

                    </div> 


                    <div class="form-group"> 

                        <label for="expiry_date">Expiry Date</label> 

                        <input 
                            type="datetime-local" 
                            id="expiry_date" 
                            name="expiry_date" 
                            required 
                            value="<?php echo date("Y-m-d\TH:i", strtotime($editNotice["expiry_date"])); ?>" 
                        > 

                    </div> 


                    <div class="form-group full"> 

                        <label for="notice_description">Notice Description</label> 

                        <textarea 
                            id="notice_description" 
                            name="notice_description" 
                            required 
                        ><?php echo htmlspecialchars($editNotice["notice_description"]); ?></textarea> 

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

            <h3>Add New Notice</h3> 

            <form method="POST" action="manageNotices.php"> 

                <div class="form-grid"> 

                    <div class="form-group"> 

                        <label for="notice_title">Notice Title</label> 

                        <input 
                            type="text" 
                            id="notice_title" 
                            name="notice_title" 
                            maxlength="255" 
                            required 
                        > 

                    </div> 


                    <div class="form-group"> 

                        <label for="notice_category">Notice Category</label> 

                        <input 
                            type="text" 
                            id="notice_category" 
                            name="notice_category" 
                            maxlength="50" 
                            placeholder="Example: Water" 
                            required 
                        > 

                    </div> 


                    <div class="form-group"> 

                        <label>Notice Area</label> 

                        <div class="ward-selection">

                            <label class="ward-option community-option">

                                <input
                                    type="checkbox"
                                    name="ward[]"
                                    value="Community-wide"
                                >

                                Community-wide

                            </label>

                            <?php foreach ($wards as $wardOption): ?>

                                <label class="ward-option">

                                    <input
                                        type="checkbox"
                                        name="ward[]"
                                        value="<?php echo htmlspecialchars($wardOption); ?>"
                                    >

                                    <?php echo htmlspecialchars($wardOption); ?>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div> 


                    <div class="form-group"> 

                        <label for="publish_date">Publish Date</label> 

                        <input 
                            type="datetime-local" 
                            id="publish_date" 
                            name="publish_date" 
                            required 
                        > 

                    </div> 


                    <div class="form-group"> 

                        <label for="expiry_date">Expiry Date</label> 

                        <input 
                            type="datetime-local" 
                            id="expiry_date" 
                            name="expiry_date" 
                            required 
                        > 

                    </div> 


                    <div class="form-group full"> 

                        <label for="notice_description">Notice Description</label> 

                        <textarea 
                            id="notice_description" 
                            name="notice_description" 
                            placeholder="Enter the full notice information..." 
                            required 
                        ></textarea> 

                    </div> 

                </div> 


                <div class="form-buttons"> 

                    <button type="submit" name="add_notice" class="button button-primary"> 
                        Add Notice 
                    </button> 

                </div> 

            </form> 

        <?php endif; ?> 

    </div> 


    <div class="table-container"> 

        <h3>Existing Notices</h3> 

        <?php if ($result && $result->num_rows > 0): ?> 

            <table> 

                <thead> 

                    <tr> 

                        <th>ID</th> 
                        <th>Title</th> 
                        <th>Category</th> 
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

                    <?php while ($notice = $result->fetch_assoc()): ?> 

                        <tr> 

                            <td> 
                                <?php echo htmlspecialchars($notice["notice_id"]); ?> 
                            </td> 


                            <td> 
                                <?php echo htmlspecialchars($notice["notice_title"]); ?> 
                            </td> 


                            <td> 
                                <?php echo htmlspecialchars($notice["notice_category"]); ?> 
                            </td> 


                            <td> 

                                <?php 

                                if ( 
                                    $notice["ward"] === null || 
                                    $notice["ward"] === "" 
                                ) { 

                                    echo "Community-wide"; 

                                } else { 

                                    echo htmlspecialchars($notice["ward"]); 

                                } 

                                ?> 

                            </td> 


                            <td> 
                                <?php echo date("d M Y H:i", strtotime($notice["publish_date"])); ?> 
                            </td> 


                            <td> 
                                <?php echo date("d M Y H:i", strtotime($notice["expiry_date"])); ?> 
                            </td> 


                            <td> 

                                <?php if (strtolower($notice["status"]) === "approved"): ?> 

                                    <span class="status status-approved"> 
                                        Approved 
                                    </span> 

                                <?php else: ?> 

                                    <span class="status status-draft"> 
                                        <?php echo htmlspecialchars($notice["status"]); ?> 
                                    </span> 

                                <?php endif; ?> 

                            </td> 


                            <td> 

                                <div class="description"> 

                                    <?php 

                                    $description = $notice["notice_description"]; 

                                    if (strlen($description) > 150) { 

                                        echo htmlspecialchars(substr($description, 0, 150)) . "..."; 

                                    } else { 

                                        echo htmlspecialchars($description); 

                                    } 

                                    ?> 

                                </div> 

                            </td> 


                            <td> 
                                <?php echo htmlspecialchars($notice["username"]); ?> 
                            </td> 


                            <td> 

                                <div class="action-buttons"> 

                                    <a 
                                        href="manageNotices.php?edit=<?php echo $notice["notice_id"]; ?>" 
                                        class="button button-edit" 
                                    > 
                                        Edit 
                                    </a> 


                                    <?php if (strtolower($notice["status"]) !== "approved"): ?> 

                                        <form method="POST" action="manageNotices.php"> 

                                            <input 
                                                type="hidden" 
                                                name="notice_id" 
                                                value="<?php echo $notice["notice_id"]; ?>" 
                                            > 

                                            <button 
                                                type="submit" 
                                                name="approve_notice" 
                                                class="button button-approve" 
                                            > 
                                                Approve 
                                            </button> 

                                        </form> 

                                    <?php endif; ?> 


                                    <form method="POST" action="manageNotices.php"> 

                                        <input 
                                            type="hidden" 
                                            name="notice_id" 
                                            value="<?php echo $notice["notice_id"]; ?>" 
                                        > 

                                        <button 
                                            type="submit" 
                                            name="delete_notice" 
                                            class="button button-delete" 
                                        > 
                                            Delete 
                                        </button> 

                                    </form> 

                                </div> 

                            </td> 

                        </tr> 

                    <?php endwhile; ?> 

                </tbody> 

            </table> 

        <?php else: ?> 

            <div class="no-notices"> 

                <p>No notices have been added yet.</p> 

            </div> 

        <?php endif; ?> 

    </div> 

</div> 
```

</body> 

</html> 

<?php 
 
$conn->close(); 
 
?>
