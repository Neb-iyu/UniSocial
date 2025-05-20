<?php

// Handle CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('error_log', __DIR__ . '/log');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

file_put_contents(__DIR__ . '/log', "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . " | METHOD: " . $_SERVER['REQUEST_METHOD'] . PHP_EOL, FILE_APPEND);

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use Src\Core\Router;

$router = new Router();

// Auth routes
$router->addRoute('POST', '/register', 'AuthController@register');
$router->addRoute('POST', '/login', 'AuthController@login');
$router->addRoute('GET', '/me', 'AuthController@me');

// Password reset routes
$router->addRoute('POST', '/password-reset/request', 'AuthController@sendResetCode');
$router->addRoute('POST', '/password-reset/verify', 'AuthController@verifyResetCode');
$router->addRoute('POST', '/password-reset/reset', 'AuthController@resetPassword');

// User routes
$router->addRoute('GET', '/users', 'UserController@getAllUsers');
$router->addRoute('GET', '/users/{uuid}', 'UserController@getUserByUuid');
$router->addRoute('PATCH', '/users/{uuid}', 'UserController@updateUser');
$router->addRoute('DELETE', '/users/{uuid}', 'UserController@deleteUser');
$router->addRoute('GET', '/users/{uuid}/followers', 'UserController@getFollowers');
$router->addRoute('GET', '/users/{uuid}/following', 'UserController@getFollowing');
$router->addRoute('POST', '/users/{username}/recover', 'UserController@recoverUser');
$router->addRoute('POST', '/users/{uuid}/profile-picture', 'UserController@uploadProfilePicture');
$router->addRoute('POST', '/users/{uuid}/promote-admin', 'UserController@promoteAdmin');
$router->addRoute('POST', '/users/{uuid}/demote-admin', 'UserController@demoteAdmin');
$router->addRoute('GET', '/admins', 'UserController@getAdminList');
$router->addRoute('GET', '/users/role/{role}', 'UserController@getUsersByRole');

// Post routes
$router->addRoute('GET', '/posts/trash', 'PostController@getSoftDeletedPosts');
$router->addRoute('GET', '/posts/{uuid}', 'PostController@getPostByUuid');
$router->addRoute('POST', '/posts', 'PostController@createPost');
$router->addRoute('PATCH', '/posts/{uuid}', 'PostController@updatePost');
$router->addRoute('PATCH', '/posts/{uuid}/recover', 'PostController@recoverPost');
$router->addRoute('DELETE', '/posts/{uuid}', 'PostController@deletePost');
$router->addRoute('GET', '/feed', 'PostController@getFeed');

// Comment routes
$router->addRoute('GET', '/posts/{uuid}/comments', 'CommentController@getCommentsByPostUuid');
$router->addRoute('GET', '/comments/{uuid}', 'CommentController@getCommentByUuid');
$router->addRoute('POST', '/comments', 'CommentController@createComment');
$router->addRoute('PATCH', '/comments/{uuid}', 'CommentController@updateComment');
$router->addRoute('DELETE', '/comments/{uuid}', 'CommentController@deleteComment');

// Notification routes
$router->addRoute('GET', '/notifications', 'NotificationController@getAllNotifications');
$router->addRoute('GET', '/notifications/{uuid}', 'NotificationController@getNotificationByUuid');
$router->addRoute('POST', '/notifications', 'NotificationController@createNotification');
$router->addRoute('PATCH', '/notifications/{uuid}', 'NotificationController@updateNotification');
$router->addRoute('DELETE', '/notifications/{uuid}', 'NotificationController@deleteNotification');

// Mention routes
$router->addRoute('GET', '/mentions/{uuid}', 'MentionController@getMentionByUuid');
$router->addRoute('PATCH', '/mentions/{uuid}', 'MentionController@updateMention');
$router->addRoute('DELETE', '/mentions/{uuid}', 'MentionController@deleteMention');
$router->addRoute('GET', '/users/{uuid}/mentions', 'MentionController@getMentionsForUser');

// Like routes
$router->addRoute('POST', '/posts/{uuid}/like', 'LikeController@togglePostLike');
$router->addRoute('POST', '/comments/{uuid}/like', 'LikeController@toggleCommentLike');
$router->addRoute('GET', '/posts/{uuid}/likes', 'LikeController@getPostLikes');
$router->addRoute('GET', '/posts/{uuid}/likes/count', 'LikeController@getPostLikeCount');
$router->addRoute('GET', '/comments/{uuid}/likes', 'LikeController@getCommentLikes');
$router->addRoute('GET', '/comments/{uuid}/likes/count', 'LikeController@getCommentLikeCount');

// Role routes
$router->addRoute('POST', '/roles', 'RoleController@createRole');
$router->addRoute('DELETE', '/roles/{role}', 'RoleController@deleteRole');
$router->addRoute('GET', '/roles', 'RoleController@getRoles');
$router->addRoute('PATCH', '/roles/{role}', 'RoleController@updateRole');
$router->addRoute('POST', '/roles/assign', 'RoleController@assignRole');
$router->addRoute('POST', '/roles/remove', 'RoleController@removeRole');

// Follow routes
$router->addRoute('POST', '/users/{uuid}/follow', 'FollowController@follow');


$router->dispatch();
