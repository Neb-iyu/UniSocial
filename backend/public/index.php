<?php

// Ensure error.log is created in the root directory
ini_set('error_log', __DIR__ . '/log');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use Src\Core\Router;

$router = new Router();

// Auth routes
$router->addRoute('POST', '/register', 'AuthController@register');
$router->addRoute('POST', '/login', 'AuthController@login');
$router->addRoute('GET', '/me', 'AuthController@me');

// User routes
$router->addRoute('GET', '/users', 'UserController@getAllUsers');
$router->addRoute('GET', '/users/{id}', 'UserController@getUserById');
$router->addRoute('PATCH', '/users/{id}', 'UserController@updateUser');
$router->addRoute('DELETE', '/users/{id}', 'UserController@deleteUser');
$router->addRoute('GET', '/users/{id}/followers', 'UserController@getFollowers');
$router->addRoute('GET', '/users/{id}/following', 'UserController@getFollowing');
$router->addRoute('POST', '/users/{username}/recover', 'UserController@recoverUser');
$router->addRoute('POST', '/users/{id}/profile-picture', 'UserController@uploadProfilePicture');
$router->addRoute('POST', '/users/{id}/promote-admin', 'UserController@promoteAdmin');
$router->addRoute('POST', '/users/{id}/demote-admin', 'UserController@demoteAdmin');
$router->addRoute('GET', '/admins', 'UserController@getAdminList');
$router->addRoute('GET', '/users/role/{role}', 'UserController@getUsersByRole');

// Post routes
$router->addRoute('GET', '/posts/{id}', 'PostController@getPostById');
$router->addRoute('POST', '/posts', 'PostController@createPost');
$router->addRoute('PATCH', '/posts/{id}', 'PostController@updatePost');
$router->addRoute('DELETE', '/posts/{id}', 'PostController@deletePost');
$router->addRoute('GET', '/feed', 'PostController@getFeed');
$router->addRoute('GET', '/posts/{id}/likes', 'PostController@getLikes');
$router->addRoute('GET', '/posts/{id}/likes/count', 'PostController@getLikeCount');

// Comment routes
$router->addRoute('GET', '/comments', 'CommentController@getAllComments');
$router->addRoute('GET', '/comments/{id}', 'CommentController@getCommentById');
$router->addRoute('POST', '/comments', 'CommentController@createComment');
$router->addRoute('PATCH', '/comments/{id}', 'CommentController@updateComment');
$router->addRoute('DELETE', '/comments/{id}', 'CommentController@deleteComment');
$router->addRoute('GET', '/comments/{id}/likes', 'CommentController@getCommentLikes');
$router->addRoute('GET', '/comments/{id}/likes/count', 'CommentController@getCommentLikeCount');

// Notification routes
$router->addRoute('GET', '/notifications', 'NotificationController@getAllNotifications');
$router->addRoute('GET', '/notifications/{id}', 'NotificationController@getNotificationById');
$router->addRoute('POST', '/notifications', 'NotificationController@createNotification');
$router->addRoute('PATCH', '/notifications/{id}', 'NotificationController@updateNotification');
$router->addRoute('DELETE', '/notifications/{id}', 'NotificationController@deleteNotification');

// Mention routes
$router->addRoute('GET', '/mentions', 'MentionController@getAllMentions');
$router->addRoute('GET', '/mentions/{id}', 'MentionController@getMentionById');
$router->addRoute('POST', '/mentions', 'MentionController@createMention');
$router->addRoute('PATCH', '/mentions/{id}', 'MentionController@updateMention');
$router->addRoute('DELETE', '/mentions/{id}', 'MentionController@deleteMention');

// Like routes
$router->addRoute('POST', '/posts/{id}/like', 'LikeController@like');
$router->addRoute('POST', '/comments/{id}/like', 'LikeController@likeComment');

// Role routes
$router->addRoute('POST', '/roles', 'RoleController@createRole');
$router->addRoute('DELETE', '/roles/{role}', 'RoleController@deleteRole');
$router->addRoute('GET', '/roles', 'RoleController@getRoles');
$router->addRoute('PATCH', '/roles/{role}', 'RoleController@updateRole');
$router->addRoute('POST', '/roles/assign', 'RoleController@assignRole');
$router->addRoute('POST', '/roles/remove', 'RoleController@removeRole');

$router->dispatch();
