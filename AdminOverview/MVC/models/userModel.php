<?php

require_once "../../Error404Connection.php";

class UserModel
{
    private $conn;


    //get database connection
    public function __construct()
    {
        global $conn;

        $this->conn = $conn;
    }


    //get all streets from the database
    public function getStreets()
    {
        $sql = "SELECT address_id, ward_id, street_name
                FROM address_directory
                ORDER BY street_name";

        $result = mysqli_query($this->conn, $sql);

        if (!$result) {

            throw new Exception(
                "Could not load streets: "
                . mysqli_error($this->conn)
            );

        }

        $streets = [];

        while ($row = mysqli_fetch_assoc($result)) {

            $streets[] = $row;

        }

        return $streets;
    }


    //get ward information for a selected street
    public function getStreetWard($addressId)
    {
        $sql = "SELECT ward_id, street_name
                FROM address_directory
                WHERE address_id = ?";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Street SQL Error: "
                . $this->conn->error
            );

        }

        $stmt->bind_param(
            "i",
            $addressId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $addressData = $result->fetch_assoc();

        $stmt->close();

        return $addressData;
    }


  //check if username already exists
public function usernameExists($username)
{
    $sql = "SELECT profile_id
            FROM user
            WHERE username = ?";

    $stmt = $this->conn->prepare($sql);

    if ($stmt === false) {

        throw new Exception(
            "Username SQL Error: "
            . $this->conn->error
        );

    }

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $exists = $result->num_rows > 0;

    $stmt->close();

    return $exists;
}

    //create user in the main user table
    public function createUser(
        $username,
        $firstName,
        $surname,
        $contact,
        $passwordHash,
        $wardId,
        $databaseRole,
        $address,
        $active,
        $verified
    ) {

        $sql = "INSERT INTO user
                (
                    username,
                    first_Name,
                    surname,
                    contact,
                    password_hash,
                    ward_id,
                    User_role,
                    address,
                    active,
                    verified
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "User SQL Error: "
                . $this->conn->error
            );

        }

        $stmt->bind_param(
            "sssssissis",
            $username,
            $firstName,
            $surname,
            $contact,
            $passwordHash,
            $wardId,
            $databaseRole,
            $address,
            $active,
            $verified
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "User registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();

        return $this->conn->insert_id;
    }


    //create community member
    public function createCommunityMember(
        $username,
        $wardId,
        $addressId
    ) {

        $sql = "INSERT INTO community_member
                (
                    username,
                    ward_id,
                    address_id
                )
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Community Member SQL Error: "
                . $this->conn->error
            );

        }

        $stmt->bind_param(
            "sii",
            $username,
            $wardId,
            $addressId
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Community member registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();
    }


    //create ward councillor
    public function createWardCouncillor(
        $username,
        $wardId,
        $dateAppointed,
        $passwordHash,
        $contact
    ) {

        $sql = "INSERT INTO ward_councillor
                (
                    username,
                    ward_id,
                    date_appointed,
                    password_hashed,
                    contact
                )
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Ward Councillor SQL Error: "
                . $this->conn->error
            );

        }

        $stmt->bind_param(
            "sisss",
            $username,
            $wardId,
            $dateAppointed,
            $passwordHash,
            $contact
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Ward councillor registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();
    }


    //create municipal staff
    public function createMunicipalStaff(
        $username,
        $serviceId,
        $division,
        $availabilityStatus,
        $contact,
        $passwordHash
    ) {

        $sql = "INSERT INTO municipal_staff
                (
                    username,
                    service_id,
                    division,
                    availability_status,
                    contact,
                    password_hashed
                )
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Municipal Staff SQL Error: "
                . $this->conn->error
            );

        }

        $stmt->bind_param(
            "sissss",
            $username,
            $serviceId,
            $division,
            $availabilityStatus,
            $contact,
            $passwordHash
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Municipal staff registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();
    }


    //start database transaction
    public function startTransaction()
    {
        $this->conn->begin_transaction();
    }


    //save database changes
    public function commit()
    {
        $this->conn->commit();
    }


    //undo database changes
    public function rollback()
    {
        $this->conn->rollback();
    }
}

?>