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
$stmt = $post->read();
$num = $stmt->rowCount();

if($num > 0) {
    $posts_arr = array();
    $posts_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $post_item = array(
            "id" => $id,
            "user_id" => $user_id,
            "username" => $username,
            "content" => $content,
            "image_url" => $image_url,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        );

        array_push($posts_arr["records"], $post_item);
    }

    http_response_code(200);
    echo json_encode($posts_arr);
} else {
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "message" => "No posts found.",
        "records" => array()
    ));
}
?> 