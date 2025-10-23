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

## AWS Deployment Notes (EC2 with Nginx & PHP 8.3-FPM)

This section provides a detailed, step-by-step guide for deploying this PHP application on an AWS EC2 instance using Nginx as the web server and PHP 8.3-FPM. These instructions assume you are using an Ubuntu EC2 instance and have SSH access.

**Important:** Replace `your_domain.com`, `[YOUR_GITHUB_REPO_LINK]`, and placeholder values (like database credentials) with your actual information.

### 1. Server Setup: Installing Prerequisites

After connecting to your EC2 instance via SSH:

1.  **Update System Packages:**
    ```bash
    sudo apt update
    sudo apt upgrade -y
    ```
2.  **Install Nginx, PHP 8.3 and PHP-FPM, and essential extensions:**
    ```bash
    sudo apt install nginx php8.3 php8.3-fpm php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-zip php8.3-pdo -y
    ```
3.  **Install Composer:**
    ```bash
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    sudo rm composer-setup.php
    ```
4.  **Verify Installations:**
    ```bash
    nginx -v
    php -v
    composer -V
    ```

### 2. Code Deployment

1.  **Navigate to a suitable directory (e.g., `/var/www/`):**
    ```bash
    cd /var/www/
    ```
2.  **Clone your GitHub repository:**
    ```bash
    sudo git clone [YOUR_GITHUB_REPO_LINK] hng13-stage1
    # Example: sudo git clone https://github.com/Joe-encodes/hng13-stage1-backend.git hng13-stage1
    ```
3.  **Change ownership of the project directory to your user (for Composer/Git operations):**
    ```bash
    sudo chown -R ubuntu:ubuntu /var/www/hng13-stage1
    ```
4.  **Tell Git to trust the repository (important if cloned with sudo or ownership changed):**
    ```bash
    git config --global --add safe.directory /var/www/hng13-stage1
    ```
5.  **Navigate into the project directory:**
    ```bash
    cd /var/www/hng13-stage1
    ```
6.  **Install PHP dependencies for production:**
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
7.  **Change ownership back to `www-data` (Nginx/PHP-FPM user):**
    ```bash
    sudo chown -R www-data:www-data /var/www/hng13-stage1
    ```

### 3. Database Configuration & Migration

**Important**: For production, strongly consider using a managed database service like **AWS RDS** (e.g., PostgreSQL, MySQL) instead of SQLite directly on the EC2 instance.

#### a. For SQLite on EC2 (as per `.env.example`)

1.  **Ensure database directory and file exist with correct permissions:**
    ```bash
    sudo mkdir -p /var/www/hng13-stage1/database
    sudo chown www-data:www-data /var/www/hng13-stage1/database
    sudo chmod 775 /var/www/hng13-stage1/database
    sudo touch /var/www/hng13-stage1/database/database.sqlite
    sudo chown www-data:www-data /var/www/hng13-stage1/database/database.sqlite
    sudo chmod 664 /var/www/hng13-stage1/database/database.sqlite
    ```
2.  **Run the database migration script:**
    ```bash
    cd /var/www/hng13-stage1 # Ensure you are in the project root
    sudo php database/migrate.php
    ```

#### b. For AWS RDS (PostgreSQL/MySQL Example)

1.  **Provision an AWS RDS instance:** (e.g., PostgreSQL or MySQL).
2.  **Manually create the database schema on RDS:** Connect to your RDS instance using a database client (e.g., `psql` for PostgreSQL, `mysql` for MySQL) from your EC2 instance or local machine. Execute SQL commands to create the `strings` table. (You can generate this SQL from your `database/migrate.php` logic).

### 4. Environment Variables on EC2 (for PHP-FPM)

**Do NOT commit your `.env` file to Git.** Instead, configure these directly on the server.

1.  **Edit the PHP-FPM pool configuration file:**
    ```bash
    sudo nano /etc/php/8.3/fpm/pool.d/www.conf
    ```
2.  **Add/uncomment and modify your database environment variables in the `[www]` section:**
    ```ini
    ; Example for SQLite:
    env[DB_CONNECTION] = sqlite
    env[DB_PATH] = /var/www/hng13-stage1/database/database.sqlite

    ; Example for AWS RDS (uncomment and modify if using RDS):
    ; env[DB_CONNECTION] = pgsql
    ; env[DB_HOST] = your-rds-endpoint.aws.com
    ; env[DB_PORT] = 5432
    ; env[DB_DATABASE] = your_db_name
    ; env[DB_USERNAME] = your_username
    ; env[DB_PASSWORD] = your_password
    ```
3.  **Restart PHP-FPM service after changes:**
    ```bash
    sudo systemctl restart php8.3-fpm
    ```

### 5. Nginx Web Server Configuration

1.  **Remove the default Nginx site configuration:**
    ```bash
    sudo rm /etc/nginx/sites-enabled/default
    ```
2.  **Create a new Nginx server block configuration file for your application:**
    ```bash
    sudo nano /etc/nginx/sites-available/hng13-stage1
    ```
3.  **Paste the following configuration into the file** (replace `your_domain.com` with your actual domain or EC2 Public IP/DNS, and ensure `fastcgi_pass` matches your PHP-FPM 8.3 socket):
    ```nginx
    server {
        listen 80;
        server_name your_domain.com; # Replace with your domain or EC2 Public IP/DNS
        root /var/www/hng13-stage1/public; # Path to your project's public directory

        index index.php;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # Ensure this matches your PHP-FPM 8.3 socket
            fastcgi_index index.php;
            fastcgi_buffers 16 16k; # Increase buffer sizes
            fastcgi_buffer_size 32k;
        }

        error_log /var/log/nginx/hng13-error.log;
        access_log /var/log/nginx/hng13-access.log;
    }
    ```
4.  **Enable the Nginx configuration and restart Nginx:**
    ```bash
    sudo ln -s /etc/nginx/sites-available/hng13-stage1 /etc/nginx/sites-enabled/
    sudo systemctl restart nginx
    sudo systemctl enable nginx
    ```

### 6. Security: AWS Security Groups

1.  **In your AWS EC2 Console, navigate to "Security Groups".**
2.  **Locate and modify the Security Group associated with your EC2 instance:**
    *   **Inbound Rules:**
        *   Add a rule for `HTTP (Port 80)` from `0.0.0.0/0` (or your specific IP for testing/restricted access).
        *   Add a rule for `HTTPS (Port 443)` from `0.0.0.0/0` (strongly recommended for production).
        *   Ensure `SSH (Port 22)` is allowed from `My IP` (your specific IP address, or a very restricted CIDR block).

By diligently following these comprehensive steps, your PHP application should be successfully deployed and running on your AWS EC2 instance.
