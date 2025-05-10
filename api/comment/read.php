<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Comment.php';

$database = new Database();
$db = $database->getConnection();

$comment = new Comment($db);

$comment->post_id = isset($_GET['post_id']) ? $_GET['post_id'] : die();

$stmt = $comment->readByPost();
$num = $stmt->rowCount();

if($num > 0) {
    $comments_arr = array();
    $comments_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $comment_item = array(
            "id" => $id,
            "user_id" => $user_id,
            "username" => $username,
            "content" => $content,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        );
        array_push($comments_arr["records"], $comment_item);
    }

    http_response_code(200);
    echo json_encode($comments_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No comments found."));
}
?> 