<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "Error404Connection.php";


//check if organisation is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$username = $_SESSION["username"];

//check organisation role
$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);

if ($userRole !== "organisation") {

    header(
        "Location: settings.php?status=error&msg="
        . urlencode("Access denied.")
    );

    exit;
}


//guard against direct URL access
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: settings.php");
    exit;
}


//read submitted action
$action = $_POST["action"] ?? "";


// UPDATE PROFILE PICTURE //

if ($action === "update_profile_image") {


    if (
        !isset($_FILES["profile_image"]) ||
        $_FILES["profile_image"]["error"] !== UPLOAD_ERR_OK
    ) {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode("Please select an image to upload.")
        );

        exit;
    }


    $fileTmpPath =
        $_FILES["profile_image"]["tmp_name"];

    $fileSize =
        (int)$_FILES["profile_image"]["size"];


    //maximum 5MB
    if ($fileSize > (5 * 1024 * 1024)) {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode("Profile picture must be smaller than 5MB.")
        );

        exit;
    }


    //check if uploaded file is really an image //
    $imageInfo = @getimagesize($fileTmpPath);


    if ($imageInfo === false) {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode("The selected file is not a valid image.")
        );

        exit;
    }


    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/gif"  => "gif",
        "image/webp" => "webp"
    ];


    $mimeType = $imageInfo["mime"] ?? "";


    if (!isset($allowedMimeTypes[$mimeType])) {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "Only JPG, PNG, GIF and WEBP images are allowed."
            )
        );

        exit;
    }


    $fileExtension =
        $allowedMimeTypes[$mimeType];


    try {


        //get current profile image //
        $stmt = $conn->prepare("
            SELECT
                o.profile_id,
                p.image_path
            FROM `organisation` o
            LEFT JOIN profile_images p
                ON o.profile_id = p.profile_id
            WHERE o.username = ?
            LIMIT 1
        ");


        if (!$stmt) {

            throw new Exception($conn->error);
        }


        $stmt->bind_param(
            "s",
            $username
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $user =
            $result
            ? $result->fetch_assoc()
            : null;


        $stmt->close();


        if (!$user) {

            header(
                "Location: settings.php?status=error&msg="
                . urlencode("Organisation account could not be found.")
            );

            exit;
        }


        $profileId =
            !empty($user["profile_id"])
            ? (int)$user["profile_id"]
            : null;


        $oldImagePath =
            trim(
                $user["image_path"]
                ?? ""
            );


        //upload directory //
        $uploadDir =
            __DIR__
            . "/uploads/profile_pics/";


        if (!is_dir($uploadDir)) {

            if (
                !mkdir(
                    $uploadDir,
                    0755,
                    true
                ) &&
                !is_dir($uploadDir)
            ) {

                throw new Exception(
                    "Upload directory could not be created."
                );
            }
        }


        //generate safe unique filename //
        $newFileName =
            bin2hex(
                random_bytes(16)
            )
            . "."
            . $fileExtension;


        $absoluteDestination =
            $uploadDir
            . $newFileName;


        //this is what gets stored in database //
        $databasePath =
            "uploads/profile_pics/"
            . $newFileName;


        if (
            !move_uploaded_file(
                $fileTmpPath,
                $absoluteDestination
            )
        ) {

            throw new Exception(
                "Uploaded image could not be saved."
            );
        }


        $conn->begin_transaction();


        try {

            // ORGANISATION ALREADY HAS PROFILE IMAGE //

            if ($profileId !== null) {


                $imgStmt =
                    $conn->prepare("
                        UPDATE profile_images
                        SET
                            image_path = ?,
                            date_uploaded = CURRENT_TIMESTAMP
                        WHERE profile_id = ?
                    ");


                if (!$imgStmt) {

                    throw new Exception(
                        $conn->error
                    );
                }


                $imgStmt->bind_param(
                    "si",
                    $databasePath,
                    $profileId
                );


                if (!$imgStmt->execute()) {

                    throw new Exception(
                        $imgStmt->error
                    );
                }


                $imgStmt->close();


            } else {

                // FIRST PROFILE PICTURE //

                $imgStmt =
                    $conn->prepare("
                        INSERT INTO profile_images
                            (
                                image_path
                            )
                        VALUES
                            (?)
                    ");


                if (!$imgStmt) {

                    throw new Exception(
                        $conn->error
                    );
                }


                $imgStmt->bind_param(
                    "s",
                    $databasePath
                );


                if (!$imgStmt->execute()) {

                    throw new Exception(
                        $imgStmt->error
                    );
                }


                $profileId =
                    $conn->insert_id;


                $imgStmt->close();


                //link image to organisation user //
                $linkStmt =
                    $conn->prepare("
                        UPDATE `organisation`
                        SET profile_id = ?
                        WHERE username = ?
                    ");


                if (!$linkStmt) {

                    throw new Exception(
                        $conn->error
                    );
                }


                $linkStmt->bind_param(
                    "is",
                    $profileId,
                    $username
                );


                if (!$linkStmt->execute()) {

                    throw new Exception(
                        $linkStmt->error
                    );
                }


                $linkStmt->close();
            }


            $conn->commit();


            //delete previous image after successful DB update //
            if (
                $oldImagePath !== "" &&
                $oldImagePath !== $databasePath
            ) {


                $oldFile =
                    __DIR__
                    . "/"
                    . $oldImagePath;


                $profileDirectory =
                    realpath(
                        __DIR__
                        . "/uploads/profile_pics"
                    );


                $oldFileReal =
                    realpath($oldFile);


                if (
                    $profileDirectory !== false &&
                    $oldFileReal !== false &&
                    strpos(
                        $oldFileReal,
                        $profileDirectory
                    ) === 0 &&
                    is_file($oldFileReal)
                ) {

                    @unlink($oldFileReal);
                }
            }


            header(
                "Location: settings.php?status=image_success"
            );

            exit;


        } catch (Exception $e) {


            $conn->rollback();


            //remove newly uploaded file
            if (
                isset($absoluteDestination) &&
                is_file($absoluteDestination)
            ) {

                @unlink(
                    $absoluteDestination
                );
            }


            throw $e;
        }


    } catch (Exception $e) {


        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "Profile picture could not be updated."
            )
        );

        exit;
    }

}


//UPDATE ORGANISATION PROFILE //

elseif ($action === "update_profile") {


    $password =
        $_POST["confirm_password"]
        ?? "";


    if ($password === "") {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "Password confirmation is required."
            )
        );

        exit;
    }


    try {


        //get organisation password
        $stmt =
            $conn->prepare("
                SELECT
                    password_hashed
                FROM `organisation` 
                WHERE username = ?
                LIMIT 1
            ");


        if (!$stmt) {

            throw new Exception(
                $conn->error
            );
        }


        $stmt->bind_param(
            "s",
            $username
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $user =
            $result
            ? $result->fetch_assoc()
            : null;


        $stmt->close();


        //confirm current password
        if (
            !$user ||
            !password_verify(
                $password,
                $user["password_hashed"]
            )
        ) {

            header(
                "Location: settings.php?status=error&msg="
                . urlencode(
                    "Incorrect password. Changes were not saved."
                )
            );

            exit;
        }


        //get submitted details
        $organisationName =
            trim(
                $_POST["organisation_name"]
                ?? ""
            );


        $contact =
            trim(
                $_POST["contact"]
                ?? ""
            );


        $address =
            trim(
                $_POST["address"]
                ?? ""
            );


        $wardId =
            !empty($_POST["ward_id"])
            ? (int)$_POST["ward_id"]
            : null;


        //validate contact
        if (
            $contact !== "" &&
            !preg_match(
                "/^[0-9]{10}$/",
                $contact
            )
        ) {

            header(
                "Location: settings.php?status=error&msg="
                . urlencode(
                    "Contact number must contain exactly 10 digits."
                )
            );

            exit;
        }


        //validate ward
        if ($wardId !== null) {


            $wardStmt =
                $conn->prepare("
                    SELECT ward_id
                    FROM ward
                    WHERE ward_id = ?
                    LIMIT 1
                ");


            if (!$wardStmt) {

                throw new Exception(
                    $conn->error
                );
            }


            $wardStmt->bind_param(
                "i",
                $wardId
            );


            $wardStmt->execute();


            $wardResult =
                $wardStmt->get_result();


            if (
                !$wardResult ||
                $wardResult->num_rows === 0
            ) {

                $wardStmt->close();


                header(
                    "Location: settings.php?status=error&msg="
                    . urlencode(
                        "The selected ward is invalid."
                    )
                );

                exit;
            }


            $wardStmt->close();
        }


        $conn->begin_transaction();


        //update user information
        $updateStmt =
            $conn->prepare("
                UPDATE `organisation`
                SET
                    organisation_name = ?,
                    contact = ?,
                    address = ?,
                    ward_id = ?
                WHERE username = ?
            ");


        if (!$updateStmt) {

            throw new Exception(
                $conn->error
            );
        }


        $updateStmt->bind_param(
            "sssis",
            $organisationName,
            $contact,
            $address,
            $wardId,
            $username
        );


        if (!$updateStmt->execute()) {

            throw new Exception(
                $updateStmt->error
            );
        }


        $updateStmt->close();


        $conn->commit();


        header(
            "Location: settings.php?status=success"
        );

        exit;


    } catch (Exception $e) {


        if ($conn->errno === 0) {

            //nothing
        }


        try {

            $conn->rollback();

        } catch (Throwable $rollbackError) {

            //transaction may not have started
        }


        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "Profile could not be updated."
            )
        );

        exit;
    }

}



// CHANGE ORGANISATION PASSWORD //


elseif ($action === "change_password") {


    $currentPassword =
        $_POST["confirm_password"]
        ?? "";


    $newPassword =
        $_POST["newPassword"]
        ?? "";


    $confirmNewPassword =
        $_POST["confirmNewPassword"]
        ?? "";


    if (
        $currentPassword === "" ||
        $newPassword === "" ||
        $confirmNewPassword === ""
    ) {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "Password confirmation and new password fields are required."
            )
        );

        exit;
    }


    if (
        $newPassword !==
        $confirmNewPassword
    ) {

        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "New passwords do not match."
            )
        );

        exit;
    }


    try {


        $stmt =
            $conn->prepare("
                SELECT password_hashed
                FROM `organisation`
                WHERE username = ?
                LIMIT 1
            ");


        if (!$stmt) {

            throw new Exception(
                $conn->error
            );
        }


        $stmt->bind_param(
            "s",
            $username
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $user =
            $result
            ? $result->fetch_assoc()
            : null;


        $stmt->close();


        //confirm current password
        if (
            !$user ||
            !password_verify(
                $currentPassword,
                $user["password_hashed"]
            )
        ) {

            header(
                "Location: settings.php?status=error&msg="
                . urlencode(
                    "Incorrect password. Password was not changed."
                )
            );

            exit;
        }


        $newHash =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        $updateStmt =
            $conn->prepare("
                UPDATE `organisation`
                SET password_hashed = ?
                WHERE username = ?
            ");


        if (!$updateStmt) {

            throw new Exception(
                $conn->error
            );
        }


        $updateStmt->bind_param(
            "ss",
            $newHash,
            $username
        );


        if (!$updateStmt->execute()) {

            throw new Exception(
                $updateStmt->error
            );
        }


        $updateStmt->close();


        header(
            "Location: settings.php?status=pwd_success"
        );

        exit;


    } catch (Exception $e) {


        header(
            "Location: settings.php?status=error&msg="
            . urlencode(
                "Server error while updating password."
            )
        );

        exit;
    }

}


// INVALID ACTION //

else {


    header(
        "Location: settings.php?status=error&msg="
        . urlencode(
            "Invalid request."
        )
    );

    exit;
}

?>