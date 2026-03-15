---
name: php-mysqli-expert
description: Use this skill when writing PHP database logic or handling user authentication.
---

# PHP mysqli (OO) & Security Standards

## When to use
- Creating or updating `db.php` or any database connection file.
- Writing SQL queries to fetch or store farmer resource data.
- Handling login/register logic.
- Building CRUD operations for any module.

## Instructions
1. **Always use mysqli (OO style):** No exceptions. Never use PDO or procedural `mysqli_` functions.
2. **Strict Error Reporting:** Always call `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` before creating the connection so DB errors throw catchable exceptions.
3. **Prepared Statements:** Use `$stmt = $conn->prepare()` + `$stmt->bind_param()`. Never interpolate variables directly into SQL strings.
4. **Fetching Results:** Use `$stmt->get_result()->fetch_assoc()` or `fetch_all(MYSQLI_ASSOC)` for clean associative arrays.
5. **Closing Connections:** Call `$conn->close()` when done, especially in long-running scripts.
6. **Password Hashing:** Always use `password_hash()` with `defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT` and `password_verify()` — never store plaintext passwords. Using `defined()` prevents fatal errors if the Argon2 extension is missing from the current PHP runtime.
7. **Transactions:** Use `$conn->begin_transaction()`, `$conn->commit()`, `$conn->rollback()` for multi-query operations.

## Connection Example
```php
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    die('Database connection failed.');
}
```

## Query Example
```php
$stmt = $conn->prepare("SELECT * FROM resources WHERE category = ?");
$stmt->bind_param('s', $category);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
```