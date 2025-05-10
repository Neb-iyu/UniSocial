# Social Media Platform REST API

A RESTful API backend for a social media platform built with PHP and MySQL.

## Features

- User authentication (register, login)
- Create, read, update, and delete posts
- Like/unlike posts
- Comment on posts
- Follow/unfollow users
- Secure password hashing
- Input validation and sanitization
- CORS enabled

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO PHP Extension
- JSON PHP Extension

## Installation

1. Clone the repository to your web server directory:
```bash
git clone <repository-url>
cd social-media-api
```

2. Create a MySQL database and import the schema:
```bash
mysql -u root -p < database/schema.sql
```

3. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials (host, db_name, username, password)

4. Set up your web server:
   - For Apache, ensure mod_rewrite is enabled
   - Point your web server's document root to the project directory
   - Ensure the web server has write permissions for the project directory

## API Endpoints

### User Endpoints

- `POST /api/user/create.php` - Register a new user
- `POST /api/user/login.php` - User login
- `GET /api/user/read.php` - Get user profile
- `PUT /api/user/update.php` - Update user profile
- `DELETE /api/user/delete.php` - Delete user account

### Post Endpoints

- `POST /api/post/create.php` - Create a new post
- `GET /api/post/read.php` - Get all posts
- `GET /api/post/read_by_user.php` - Get posts by user
- `PUT /api/post/update.php` - Update a post
- `DELETE /api/post/delete.php` - Delete a post

### Like Endpoints

- `POST /api/like/create.php` - Like a post
- `DELETE /api/like/delete.php` - Unlike a post

### Comment Endpoints

- `POST /api/comment/create.php` - Add a comment
- `GET /api/comment/read.php` - Get comments for a post
- `PUT /api/comment/update.php` - Update a comment
- `DELETE /api/comment/delete.php` - Delete a comment

### Follow Endpoints

- `POST /api/follow/create.php` - Follow a user
- `DELETE /api/follow/delete.php` - Unfollow a user

## Security

- All passwords are hashed using PHP's password_hash() function
- Input validation and sanitization implemented
- CORS headers configured
- Prepared statements used for all database queries
- No sensitive data exposed in responses

## Error Handling

The API uses appropriate HTTP status codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 503: Service Unavailable

## License

This project is licensed under the MIT License - see the LICENSE file for details. 