<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Comment.php';

$database = new Database();
$db = $database->getConnection();

$comment = new Comment($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->id) &&
    !empty($data->content)
) {
    $comment->id = $data->id;
    $comment->content = $data->content;

    if($comment->update()) {
        http_response_code(200);
        echo json_encode(array("message" => "Comment was updated."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to update comment."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to update comment. Data is incomplete."));
}
?> 