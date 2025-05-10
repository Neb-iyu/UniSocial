<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Follow.php';

$database = new Database();
$db = $database->getConnection();

$follow = new Follow($db);

$follow->following_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$stmt = $follow->getFollowers();
$num = $stmt->rowCount();

if($num > 0) {
    $followers_arr = array();
    $followers_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $follower_item = array(
            "id" => $id,
            "username" => $username
        );
        array_push($followers_arr["records"], $follower_item);
    }

    http_response_code(200);
    echo json_encode($followers_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No followers found."));
}
?> 