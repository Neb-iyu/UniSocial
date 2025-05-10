<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Post.php';

$database = new Database();
$db = $database->getConnection();

$post = new Post($db);

$post->id = isset($_GET['post_id']) ? $_GET['post_id'] : die();

if($post->exists()) {
    $like_count = $post->getLikes();
    
    http_response_code(200);
    echo json_encode(array(
        "post_id" => $post->id,
        "likes" => $like_count
    ));
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Post not found."));
}
?> 