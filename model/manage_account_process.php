<?php
session_start();
error_reporting(0);

include('../config/connect_db.php');
include('../config/lang.php');
include('../util/reorder_record.php');


if ($_POST["action"] === 'GET_DATA') {

    $id = $_POST["id"];

    $return_arr = array();

    $sql_get = "SELECT * FROM ims_user WHERE id = " . $id;
    $statement = $conn->query($sql_get);
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {

        $return_arr[] = array("id" => $result['id'],
            "email" => $result['email'],
            "first_name" => $result['first_name'],
            "last_name" => $result['last_name'],
            "account_type" => $result['account_type'],
            "status" => $result['status']);
    }

    echo json_encode($return_arr);

}

if ($_POST["action"] === 'ADD') {

    if ($_POST["email"] !== '') {

        $email = $_POST["email"];
        $user_id = $_POST["email"];
        //$password = password_hash($password, PASSWORD_DEFAULT);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $first_name = $_POST["first_name"];
        $last_name = $_POST["last_name"];
        $account_type = $_POST["account_type"];

        $picture = $account_type == 'admin' ? "img/icon/admin-001.png" : "img/icon/user-001.png";

        $status = "Active";

        $sql_find = "SELECT * FROM ims_user WHERE email = '" . $email . "'";

        $nRows = $conn->query($sql_find)->fetchColumn();
        if ($nRows > 0) {
            echo 2;
        } else {
            $sql = "INSERT INTO ims_user(user_id,email,password,first_name,last_name,account_type,picture,status)
            VALUES (:user_id,:email,:password,:first_name,:last_name,:account_type,:picture,:status)";
            $query = $conn->prepare($sql);
            $query->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->bindParam(':password', $password, PDO::PARAM_STR);
            $query->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $query->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $query->bindParam(':account_type', $account_type, PDO::PARAM_STR);
            $query->bindParam(':picture', $picture, PDO::PARAM_STR);
            $query->bindParam(':status', $status, PDO::PARAM_STR);
            $query->execute();

            $lastInsertId = $conn->lastInsertId();
            if ($lastInsertId) {
                Reorder_Record($conn, "ims_user");
                echo 1;
            } else {
                echo 3;
            }
        }
    }
}


if ($_POST["action"] === 'UPDATE') {

    if ($_POST["email"] != '') {

        $id = $_POST["id"];
        $email = $_POST["email"];
        $first_name = $_POST["first_name"];
        $last_name = $_POST["last_name"];
        $status = $_POST["status"];
        $account_type = $_POST["account_type"];
        $picture = $account_type === 'admin' ? "img/icon/admin-001.png" : "img/icon/user-001.png";
        $sql_find = "SELECT * FROM ims_user WHERE email = '" . $email . "'";
        $nRows = $conn->query($sql_find)->fetchColumn();
        if ($nRows > 0) {
            $sql_update = "UPDATE ims_user SET first_name=:first_name,last_name=:last_name,status=:status,account_type=:account_type
            ,picture=:picture
            WHERE id = :id";
            $query = $conn->prepare($sql_update);
            $query->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $query->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $query->bindParam(':account_type', $account_type, PDO::PARAM_STR);
            $query->bindParam(':picture', $picture, PDO::PARAM_STR);
            $query->bindParam(':status', $status, PDO::PARAM_STR);
            $query->bindParam(':id', $id, PDO::PARAM_STR);
            $query->execute();
            echo $save_success;
        }
    } else {
        echo $error;
    }

}

if ($_POST["action"] === 'DELETE') {

    $id = $_POST["id"];

    $sql_find = "SELECT * FROM ims_user WHERE id = " . $id;
    $nRows = $conn->query($sql_find)->fetchColumn();
    if ($nRows > 0) {
        try {
            $sql = "DELETE FROM ims_user WHERE id = " . $id;
            $query = $conn->prepare($sql);
            $query->execute();
            Reorder_Record($conn, "ims_user");
            echo $del_success;
        } catch (Exception $e) {
            echo 'Message: ' . $e->getMessage();
        }
    }
}


if ($_POST["action"] === 'CHG') {
    try {
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $email = $_POST["email"];

        //$myfile = fopen("pw-param.txt", "w") or die("Unable to open file!");
        //fwrite($myfile,  $_POST['new_password'] . " | " . $password . " | " . $id);
        //fclose($myfile);

        $sql_update = "UPDATE ims_user SET password=:password WHERE email = :email";
        $query = $conn->prepare($sql_update);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        echo 1;
    } catch (Exception $e) {
        echo 3;
    }
}

if ($_POST["action"] === 'CHL') {
    try {
        $lang = $_POST['lang'];
        $id = $_POST["login_id"];
        $sql_update = "UPDATE ims_user SET lang=:lang WHERE id = :id";
        $query = $conn->prepare($sql_update);
        $query->bindParam(':lang', $lang, PDO::PARAM_STR);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();
        echo 1;
    } catch (Exception $e) {
        echo 3;
    }
}

if ($_POST["action"] === 'GET_ACCOUNT') {
## Read value
    $draw = $_POST['draw'];
    $row = $_POST['start'];
    $rowperpage = $_POST['length']; // Rows display per page
    $columnIndex = $_POST['order'][0]['column']; // Column index
    $columnName = $_POST['columns'][$columnIndex]['data']; // Column name
    $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
    $searchValue = $_POST['search']['value']; // Search value
    $searchArray = array();

## Search
    $searchQuery = " ";
    if ($searchValue != '') {
        $searchQuery = " AND (email LIKE :email or 
        first_name LIKE :first_name OR
        last_name LIKE :last_name OR         
        status LIKE :status ) ";
        $searchArray = array(
            'email' => "%$searchValue%",
            'first_name' => "%$searchValue%",
            'last_name' => "%$searchValue%",
            'status' => "%$searchValue%"
        );
    }


## Total number of records without filtering
    $stmt = $conn->prepare("SELECT COUNT(*) AS allcount FROM ims_user ");
    $stmt->execute();
    $records = $stmt->fetch();
    $totalRecords = $records['allcount'];

## Total number of records with filtering
    $stmt = $conn->prepare("SELECT COUNT(*) AS allcount FROM ims_user WHERE 1 " . $searchQuery);
    $stmt->execute($searchArray);
    $records = $stmt->fetch();
    $totalRecordwithFilter = $records['allcount'];

## Fetch records
    $stmt = $conn->prepare("SELECT * FROM ims_user WHERE 1 " . $searchQuery
        . " ORDER BY " . $columnName . " " . $columnSortOrder . " LIMIT :limit,:offset");

// Bind values
    foreach ($searchArray as $key => $search) {
        $stmt->bindValue(':' . $key, $search, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', (int)$row, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$rowperpage, PDO::PARAM_INT);
    $stmt->execute();
    $empRecords = $stmt->fetchAll();
    $data = array();

    foreach ($empRecords as $row) {

        $data[] = array(
            "line_no" => $row['line_no'],
            "email" => $row['email'],
            "first_name" => $row['first_name'],
            "last_name" => $row['last_name'],
            "update" => "<button type='button' name='update' id='" . $row['id'] . "' class='btn btn-info btn-xs update' data-toggle='tooltip' title='Update'>Update</button>",
            "delete" => "<button type='button' name='delete' id='" . $row['id'] . "' class='btn btn-danger btn-xs delete' data-toggle='tooltip' title='Delete'>Delete</button>",
            "picture" => "<img src = '" . $row['picture'] . "'  width='32' height='32' title='" . $row['account_type'] . "'>",
            "status" => $row['status'] === 'Active' ? "<div class='text-success'>" . $row['status'] . "</div>" : "<div class='text-muted'> " . $row['status'] . "</div>"
        );

    }

## Response Return Value
    $response = array(
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => $totalRecordwithFilter,
        "aaData" => $data
    );

    echo json_encode($response);
}




