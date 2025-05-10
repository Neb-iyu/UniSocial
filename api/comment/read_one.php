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

$comment->id = isset($_GET['id']) ? $_GET['id'] : die();

if($comment->readOne()) {
    $comment_arr = array(
        "id" => $comment->id,
        "user_id" => $comment->user_id,
        "username" => $comment->username,
        "post_id" => $comment->post_id,
        "content" => $comment->content,
        "created_at" => $comment->created_at,
        "updated_at" => $comment->updated_at
    );

    http_response_code(200);
    echo json_encode($comment_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Comment does not exist."));
}
?> 