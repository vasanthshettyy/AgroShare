# Tech Stack Standards

## Core Stack
- **Backend:** PHP 8.1+ (no frameworks — pure PHP).
- **Frontend:** Vanilla HTML5, CSS3, and JavaScript (ES6+).
- **Database:** MySQL 8.0+ with Object-Oriented `mysqli` (no PDO, no procedural `mysqli_` functions).

## Coding Rules
- **PHP:** Always use OO `mysqli` with prepared statements (`$conn->prepare()` + `$stmt->bind_param()`). No raw SQL string concatenation. No variable interpolation into SQL strings.
- **JavaScript:** Use modern JS (`const`/`let`, arrow functions, `async/await`); avoid runtime third-party libraries like jQuery in production bundles, but permit build-time npm tooling (webpack, Babel, Rollup, polyfills, linters) or explicitly list allowed packages for development.
- **CSS:** Use modern Flexbox/Grid. Prefer semantic HTML (`<section>`, `<article>`, `<nav>`) over nested `<div>`s.
- **HTML:** Use proper `<!DOCTYPE html>`, `<meta charset>`, and semantic structure.

## Safety & Security
- **Error Handling:** Call `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` before connection. Implement `try-catch` blocks for all database operations.
- **Security:** Never hardcode credentials. Store them in a configuration file placed outside the web root (e.g., `../config/constants.php`) so credentials cannot be served by the webserver, and load it via `require_once` from application code.
- **Input Sanitization:** Always validate and sanitize user input on both client and server side. Use `htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` for HTML context ONLY. For other contexts, use `json_encode()` for JavaScript/JSON data, and `rawurlencode()` or `urlencode()` for URL parameters. Rely on prepared statements/parameterized queries for SQL.
- **CSRF Protection:** Use tokens for all form submissions that modify data.
- **Password Hashing:** Use `password_hash($pw, PASSWORD_ARGON2ID)` and `password_verify()`. Never store plaintext passwords.