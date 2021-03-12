<?php
declare(strict_types=1);

$sports = ['Football', 'Tennis', 'Ping pong', 'Volley ball', 'Rugby', 'Horse riding', 'Swimming', 'Judo', 'Karate'];

function openConnection(): PDO
{
    // No bugs in this function, just use the right credentials.
    /*$dbhost = "DB_HOST";
    $dbuser = "DB_USER";
    $dbpass = "DB_USER_PASSWORD";
    $db = "DB_NAME";*/
    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $db = "dbintro";

    $driverOptions = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO('mysql:host=' . $dbhost . ';dbname=' . $db, $dbuser, $dbpass, $driverOptions);
}

$pdo = openConnection();

if(!empty($_POST['firstname']) && !empty($_POST['lastname'])) {
    //@todo possible bug below?
    if(empty($_POST['id'])) {//switched if statement
        $handle = $pdo->prepare('INSERT INTO user (firstname, lastname, year) VALUES (:firstname, :lastname, :year)');
        $message = 'Your record has been added';
    } else {
        $handle = $pdo->prepare('UPDATE user SET firstname = :firstname, lastname = :lastname, year = :year WHERE id = :id');//@todo change VALUES to SET
        $handle->bindValue(':id', $_POST['id']);
        $message = 'Your record has been updated';
    }

    $handle->bindValue(':firstname', $_POST['firstname']);
    $handle->bindValue(':lastname', $_POST['lastname']);
    $handle->bindValue(':year', date('Y'));
    $handle->execute();

    if(!empty($_POST['id'])) {
        $handle = $pdo->prepare('DELETE FROM sport WHERE user_id = :id');//@todo: change where id to where user_id
        $handle->bindValue(':id', $_POST['id']);
        $handle->execute();
        $userId = $_POST['id'];
    } else {
        //@todo The last insertId needs to be moved from the foreach below to this else. Now it will fetch the ID of the last inserted sport.
        $userId = $pdo->lastInsertId();
        //why did I leave this if empty? There must be no important reason for this. Move on.
    }

    foreach($_POST['sports'] AS $sport) {
        $handle = $pdo->prepare('INSERT INTO sport (user_id, sport) VALUES (:userId, :sport)');
        $handle->bindValue(':userId', $userId);
        $handle->bindValue(':sport', $sport);
        $handle->execute();
    }
}
elseif(isset($_POST['delete'])) {
    $handle = $pdo->prepare('DELETE FROM user WHERE id = :id');//@todo Forgot WHERE id = :id
    $handle->bindValue(':id', $_POST['id']);
    $handle->execute();

    $message = 'Your record has been deleted';
}

//@todo: make id ambiguous -> needs to be select user.id
//@todo switched concat_ws(" ", firstname, lastname) to wrong order concat_ws(irstname, lastname, " ")
$handle = $pdo->prepare('SELECT user.id, concat_ws(" ", firstname, lastname) AS name, sport FROM user LEFT JOIN sport ON user.id = sport.user_id where year = :year order by sport');
$handle->bindValue(':year', date('Y'));
$handle->execute();
$users = $handle->fetchAll();

$saveLabel = 'Save record';
if(!empty($_GET['id'])) {
    $saveLabel = 'Update record';

    $handle = $pdo->prepare('SELECT id, firstname, lastname FROM user where id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    $selectedUser = $handle->fetch();

    //This segment marks all the checkboxes with all the current sports for an existing user when you update him. Currently that is not working however. :-(
    $selectedUser['sports'] = [];
    $handle = $pdo->prepare('SELECT sport FROM sport where user_id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    foreach($handle->fetchAll() AS $sport) {
        $selectedUser['sports'][] = $sport['sport'];//@todo I just want an array of all sports of this, why is it not working?
    }
}

if(empty($selectedUser['id'])) {
    $selectedUser = [
        'id' => '',
        'firstname' => '',
        'lastname' => '',
        'sports' => []
    ];
}


require 'view.php';