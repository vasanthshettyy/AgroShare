# Changelog - AgroShare

## [2026-05-15] - Booking Rescheduling Feature

### Added
- **Rescheduling Pivot Logic**: Renters can now reschedule their pending or confirmed bookings. 
  - The process creates a new booking request and marks the old one as `rescheduled` for auditability.
  - Automatic price recalculation and conflict detection (allowing self-overlap during shifts).
- **Payment Gating**: Rescheduling is automatically blocked once the owner confirms the payment status.
- **Database Schema Updates**:
  - `bookings.status`: Added `rescheduled` state.
  - `bookings.payment_status`: Added `pending`, `confirmed` enum.
  - `idx_bookings_conflict_reschedule`: Optimized index for availability lookups.
- **API Endpoint**: `POST /public/api/reschedule-booking.php`
  - Requires `booking_id`, `new_start_datetime`, `new_end_datetime`, and `csrf_token`.
- **UI Enhancements**:
  - Injected "Reschedule" action in the `my-bookings.php` 3-dot menu for renters.
  - Integrated `reschedule-modal.php` for date selection and AJAX submission.

- **Payment Evidence Capture**: Renters can now provide a Transaction Reference (UTR) when confirming payment.
- **Owner Verification**: Owners can now verify the receipt of payment, adding a second layer of trust to the transaction.
- **Race Condition Guards**: Implemented stricter transactional checks in the backend to prevent rescheduling once a payment reference is submitted.

### Security & Hardening
- **CSRF Enforcement**: All rescheduling and status mutation calls are protected by token validation.
- **Prepared Statements**: All new database queries use `mysqli` prepared statements to prevent SQL injection.
- **Transactional Integrity**: Uses `FOR UPDATE` locking and `begin_transaction` to ensure atomic consistency during the "Clone-and-Pivot" operation.
- **API Standardisation**: Standardized JSON response envelopes for all booking mutation endpoints.
