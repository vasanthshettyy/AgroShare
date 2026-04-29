# AgroShare: Core System Technical Documentation

This document provides a detailed explanation of the core technical systems implemented in the AgroShare platform, specifically focusing on Security, Authentication, and Financial protections.

---

## 1. CAPTCHA Security System
The CAPTCHA (Completely Automated Public Turing test to tell Computers and Humans Apart) system in AgroShare is a custom-built, lightweight solution designed to prevent automated "brute-force" or "credential stuffing" attacks.

### How it Works:
1.  **Code Generation**: When the login page loads, a PHP script selects 6 random characters from a visually distinct character set (`ABCDEFGHJKLMNPQRSTUVWXYZ23456789`). We exclude characters like '0', 'O', '1', and 'I' to prevent user confusion.
2.  **Session Storage**: This code is stored in the user's server-side session (`$_SESSION['captcha_code']`). This is secure because the user cannot see or modify the session data directly.
3.  **Visual Obfuscation**:
    *   **Rotation**: Each character in the CAPTCHA display is randomly rotated using CSS transforms (e.g., `-5deg` to `5deg`).
    *   **Background Noise**: The container uses a complex linear gradient background that simulates "lines" crossing through the text.
    *   **User Selection**: The text is made `user-select: none` to prevent simple copy-pasting.
4.  **Validation**: When the user submits the form, the server compares the `captcha_answer` (converted to uppercase) with the value stored in the session. If they don't match, the login attempt is rejected before even checking the password, saving database resources.

---

## 2. Google OAuth 2.0 (Gmail) Login
AgroShare integrates with Google's Identity Platform to provide a "One-Tap" login experience. This uses the OAuth 2.0 protocol.

### The Flow:
1.  **Authorization Request**: When a user clicks "Continue with Google", they are redirected to Google's secure servers.
2.  **User Consent**: Google asks the user to share their name and email address with AgroShare.
3.  **Callback**: After consent, Google redirects the user back to our `GoogleAuthController.php` with a "code".
4.  **Token Exchange**: Our server sends this code back to Google (behind the scenes) to get an "Access Token" and an "ID Token".
5.  **Account Linking**:
    *   **Existing User**: If the email provided by Google matches an existing user in our database, we log them in immediately.
    *   **New User**: If the email is new, we automatically create a profile for them, using their Google name and profile picture, and link it to their Google ID.
6.  **Security**: We never see the user's Google password. The connection is handled entirely via secure cryptographic tokens.

---

## 3. Security Deposit (Safety Deposit) System
The Security Deposit system is a critical financial protection layer for equipment owners, ensuring that their valuable assets are treated with care.

### Operational Detail:
*   **Owner Configuration**: When listing equipment (e.g., a Tractor), the owner can set a "Safety Deposit" amount (e.g., ₹5,000) in addition to the daily rental rate.
*   **Total Calculation**: When a renter initiates a booking, the system calculates the total cost as:
    `Total = (Daily Rate × Number of Days) + Safety Deposit`
*   **Atomic Booking**: The deposit amount is frozen and recorded in the `bookings` table under the `deposit_amount` column at the moment the request is made. This ensures the price is "locked in" even if the owner changes the equipment price later.
*   **Refundable Nature**: By default, the deposit is marked as "Refundable".
*   **Lifecycle**:
    1.  **Pending/Confirmed**: The deposit is part of the "Pending" transaction.
    2.  **Active**: The equipment is in use; the owner holds the assurance of the deposit.
    3.  **Completion**: Once the equipment is returned and the booking is marked "Completed", the owner can release the deposit.
*   **Dispute Resolution**: If the equipment is damaged, the owner can "Raise a Dispute". This triggers an **Admin Review**. An AgroShare administrator will then investigate the claim and can decide to:
    *   Refund the deposit to the renter (if the claim is invalid).
    *   Forfeit the deposit to the owner (to cover repair costs).
    *   Split the deposit (in case of partial fault).

---

## 4. Real-Time Maintenance Mode
As a bonus, the platform features a "Maintenace Heartbeat" system.

*   **Admin Toggle**: Admins can turn on Maintenance Mode from the settings panel.
*   **Live Check**: Every 60 seconds, the frontend sends a small "heartbeat" request to `api/maintenance-check.php`.
*   **Auto-Redirect**: If the platform enters maintenance, users are instantly redirected to the "Emerald Harvest" maintenance page without needing to refresh. Once maintenance is over, the page automatically detects it and returns the user to their dashboard.
