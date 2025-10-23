# HNG13 Stage 1: String Analysis API

This project implements a RESTful API for analyzing, storing, and retrieving strings with various properties, including natural language filtering capabilities. It's built with PHP, the Slim Framework, and uses Eloquent ORM for database interactions. Automated tests are provided with PHPUnit.

## Table of Contents
1. [Features](#features)
2. [Local Setup](#local-setup)
3. [Dependencies](#dependencies)
4. [Environment Variables](#environment-variables)
5. [Running Tests](#running-tests)
6. [API Endpoints](#api-endpoints)
7. [AWS Deployment Notes](#aws-deployment-notes)

## Features

This API allows you to:
- **Create/Analyze Strings**: Submit a string to be analyzed for properties like length, palindrome status, unique characters, word count, SHA-256 hash, and character frequency. The analyzed string and its properties are stored in a database.
- **Get Specific String**: Retrieve a string and its properties by providing the original string value.
- **Get All Strings with Filtering**: Retrieve a list of all stored strings, with options to filter by properties like `is_palindrome`, `min_length`, `max_length`, `word_count`, and `contains_character`.
- **Natural Language Filtering**: Filter strings using natural language queries (e.g., "all palindromic strings longer than 5 characters"). The API interprets the query and applies the relevant filters.
- **Delete String**: Remove a string from the system by providing its original value.

## Local Setup

Follow these steps to get the project up and running on your local machine.

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/Joe-encodes/hng13-stage1-backend.git
    cd hng13-stage1
    ```

2.  **Install PHP Dependencies:**
    This project uses Composer to manage PHP libraries.
    ```bash
    composer install
    ```

3.  **Environment Variables:**
    Create a `.env` file in the root of the project directory. You can use the provided `.env.example` as a template.
    ```bash
    cp .env.example .env
    ```
    Ensure your `.env` file is configured for your local database. For local development, an SQLite database is recommended.

4.  **Start the PHP Development Server:**
    ```bash
    php -S 0.0.0.0:8000 -t public -d opcache.enable=0 -d opcache.enable_cli=0
    ```
    The API will be accessible at `http://localhost:8000`.

## Dependencies

The project's PHP dependencies are managed via `composer.json`. Key dependencies include:
- `slim/slim`: The PHP micro-framework for routing and HTTP handling.
- `illuminate/database`: Laravel's Eloquent ORM and Query Builder for database interactions.
- `guzzlehttp/guzzle`: HTTP client used in tests for making API requests.
- `vlucas/phpdotenv`: For loading environment variables from `.env` files.
- `nesbot/carbon`: For date and time manipulation.
- `phpunit/phpunit`: The testing framework.

To install them, simply run `composer install`.

## Environment Variables

The application relies on environment variables, typically stored in a `.env` file for local development.

-   **`DB_CONNECTION`**: The database driver (e.g., `sqlite`).
-   **`DB_PATH`**: The path to your database file (e.g., `database/database.sqlite`).

**Example `.env` file:** (See `.env.example`)
```env
DB_CONNECTION=sqlite
DB_PATH=database/database.sqlite
```

## Running Tests

Automated tests are provided using PHPUnit to ensure the API endpoints function correctly.

1.  **Ensure Composer dependencies are installed:**
    ```bash
    composer install
    ```
2.  **Run PHPUnit tests:**
    ```bash
    ./vendor/bin/phpunit tests/ApiUnitTest.php
    ```

## API Endpoints

All endpoints are prefixed with `http://localhost:8000` (for local development).

### 1. Create/Analyze String
- **Endpoint**: `POST /strings`
- **Content-Type**: `application/json`
- **Request Body**:
  ```json
  {
    "value": "string to analyze"
  }
  ```
- **Success Response (201 Created)**:
  ```json
  {
    "id": "sha256_hash_value",
    "value": "string to analyze",
    "properties": {
      "length": 17,
      "is_palindrome": false,
      "unique_characters": 12,
      "word_count": 3,
      "sha256_hash": "abc123...",
      "character_frequency_map": {
        "s": 2,
        "t": 3,
        "r": 2
      }
    },
    "created_at": "2025-08-27T10:00:00Z"
  }
  ```
- **Error Responses**:
    - `409 Conflict`: String already exists.
    - `400 Bad Request`: Missing "value" field.
    - `422 Unprocessable Entity`: "value" must be a string.

### 2. Get Specific String
- **Endpoint**: `GET /strings/{string_value}`
- **Success Response (200 OK)**:
  ```json
  {
    "id": "sha256_hash_value",
    "value": "requested string",
    "properties": { /* same as above */ },
    "created_at": "2025-08-27T10:00:00Z",
    "updated_at": "2025-08-27T10:00:00Z"
  }
  ```
- **Error Responses**:
    - `404 Not Found`: String does not exist.

### 3. Get All Strings with Filtering
- **Endpoint**: `GET /strings?is_palindrome=true&min_length=5&max_length=20&word_count=2&contains_character=a`
- **Query Parameters**:
    - `is_palindrome`: boolean (`true`/`false`)
    - `min_length`: integer (minimum string length)
    - `max_length`: integer (maximum string length)
    - `word_count`: integer (exact word count)
    - `contains_character`: string (single character)
- **Success Response (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": "hash1",
        "value": "string1",
        "properties": { /* ... */ },
        "created_at": "2025-08-27T10:00:00Z"
      }
    ],
    "count": 15,
    "filters_applied": {
      "is_palindrome": true,
      "min_length": 5
    }
  }
  ```
- **Error Response**:
    - `400 Bad Request`: Invalid query parameter values or types.

### 4. Natural Language Filtering
- **Endpoint**: `GET /strings/filter-by-natural-language?query=all%20single%20word%20palindromic%20strings`
- **Example Queries Supported**:
    - "all single word palindromic strings" → `word_count=1`, `is_palindrome=true`
    - "strings longer than 10 characters" → `min_length=11`
    - "palindromic strings that contain the first vowel" → `is_palindrome=true`, `contains_character=a`
    - "strings containing the letter z" → `contains_character=z`
- **Success Response (200 OK)**:
  ```json
  {
    "data": [ /* array of matching strings */ ],
    "count": 3,
    "interpreted_query": {
      "original": "all single word palindromic strings",
      "parsed_filters": {
        "word_count": 1,
        "is_palindrome": true
      }
    }
  }
  ```
- **Error Responses**:
    - `400 Bad Request`: Unable to parse natural language query.
    - `422 Unprocessable Entity`: Query parsed but resulted in conflicting filters (e.g., "longer than 10 and shorter than 5").

### 5. Delete String
- **Endpoint**: `DELETE /strings/{string_value}`
- **Success Response (204 No Content)**: (Empty response body)
- **Error Responses**:
    - `404 Not Found`: String does not exist.

## AWS Deployment Notes

Deploying a PHP application like this to AWS can be done in several ways (e.g., EC2 with Nginx/Apache + PHP-FPM, Elastic Beanstalk, Lambda with Bref). Here are general considerations, focusing on a typical EC2 setup for clarity:

1.  **Server Setup (e.g., EC2 Instance):**
    *   Provision an EC2 instance (e.g., Ubuntu, Amazon Linux).
    *   Install PHP (8.4 recommended), Composer, Nginx (or Apache), and PHP-FPM.
    *   Ensure necessary PHP extensions are installed (e.g., `php-sqlite3` for local testing, `php-pdo_sqlite` if using SQLite, or appropriate drivers for other databases).

2.  **Code Deployment:**
    *   Clone your GitHub repository onto the EC2 instance.
    *   Run `composer install` on the server to install production dependencies.

3.  **Web Server Configuration (Nginx Example):**
    *   Configure Nginx to serve your application from the `public/` directory.
    *   A typical Nginx server block might look like this (adjust `your_domain.com` and `fastcgi_pass` as needed):
        ```nginx
        server {
            listen 80;
            server_name your_domain.com;
            root /var/www/hng13-stage1/public; # Adjust path to your project's public directory

            index index.php;

            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }

            location ~ \.php$ {
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                fastcgi_pass unix:/var/run/php/php8.4-fpm.sock; # Adjust PHP-FPM socket
                fastcgi_index index.php;
            }

            error_log /var/log/nginx/hng13-error.log;
            access_log /var/log/nginx/hng13-access.log;
        }
        ```
    *   Restart Nginx after configuration changes.

4.  **Database Configuration:**
    *   For production, consider a managed database service like **AWS RDS** (e.g., PostgreSQL, MySQL) instead of SQLite.
    *   If using RDS, your `.env` variables will change to:
        ```env
        DB_CONNECTION=pgsql # or mysql
        DB_HOST=your-rds-endpoint.aws.com
        DB_PORT=5432 # or 3306
        DB_DATABASE=your_db_name
        DB_USERNAME=your_username
        DB_PASSWORD=your_password
        ```
    *   **Crucially, ensure the database schema is created on deployment.** For this project, you might need to run the `database/migrate.php` script *once* on the server if using a fresh database, or adapt its schema creation logic.

5.  **Environment Variables on AWS:**
    *   **Do NOT commit your `.env` file to Git.**
    *   Instead, set your environment variables directly in your AWS deployment environment. For EC2, you might put them in a separate config file or directly in your application's startup script. For Elastic Beanstalk, there's a dedicated configuration section for environment properties.

6.  **Security:**
    *   Configure AWS Security Groups to allow inbound HTTP (port 80) and HTTPS (port 443) traffic to your web server.
    *   Restrict SSH access (port 22) to known IP addresses.

By following these steps, you should be able to get your application deployed and running on AWS successfully.
