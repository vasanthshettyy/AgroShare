<?php
/**
 * GoogleAuthController.php — Logic for Google OAuth 2.0 flow.
 *
 * This controller handles generating the auth URL, exchanging the auth code
 * for an access token, and retrieving user profile information from Google.
 *
 * Uses: Vanilla PHP with cURL (no external libraries required).
 */

/**
 * Generate the Google OAuth 2.0 Authorization URL.
 */
function getGoogleAuthUrl(): string
{
    $endpoint = "https://accounts.google.com/o/oauth2/v2/auth";
    
    // Use a random state for CSRF protection
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['google_oauth_state'])) {
        $_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));
    }

    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $_SESSION['google_oauth_state'],
        'access_type'   => 'offline',
        'prompt'        => 'select_account'
    ];

    return $endpoint . '?' . http_build_query($params);
}

/**
 * Exchange the authorization code for an access token.
 * Returns the full token response array or false on failure.
 */
function exchangeCodeForToken(string $code): array|false
{
    $endpoint = "https://oauth2.googleapis.com/token";

    $params = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    // TEMPORARY: Disable SSL verification for debugging (common issue on Windows/XAMPP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    return json_decode($response, true);
}

/**
 * Retrieve user profile information using the access token.
 * Returns ['google_id', 'email', 'name', 'picture'] or false on failure.
 */
function getGoogleUserInfo(string $accessToken): array|false
{
    $endpoint = "https://www.googleapis.com/oauth2/v3/userinfo";

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    // TEMPORARY: Disable SSL verification for debugging
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $data = json_decode($response, true);
    
    return [
        'google_id' => $data['sub'] ?? null,
        'email'     => $data['email'] ?? null,
        'name'      => $data['name'] ?? null,
        'picture'   => $data['picture'] ?? null
    ];
}

/**
 * Find or create a user based on Google info.
 * This function handles the "Logic Bridge" between Google identity and AgroShare users.
 */
function handleGoogleUserSession(mysqli $conn, array $googleUser): array
{
    $googleId = $googleUser['google_id'];
    $email    = strtolower($googleUser['email']);
    $name     = $googleUser['name'];

    // 1. Try to find by google_id
    $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->bind_param('s', $googleId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        return ['status' => 'success', 'user' => $user];
    }

    // 2. Try to find by email (Link account)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Link this Google account to the existing email
        $stmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $stmt->bind_param('si', $googleId, $user['id']);
        $stmt->execute();
        $stmt->close();
        
        $user['google_id'] = $googleId;
        return ['status' => 'success', 'user' => $user];
    }

    // 3. New User - Require additional info (Phone, Location)
    // We store Google info in session and redirect to a completion page
    $_SESSION['temp_google_user'] = [
        'google_id' => $googleId,
        'email'     => $email,
        'name'      => $name
    ];

    return ['status' => 'needs_profile', 'user' => null];
}
