-- ============================================================
-- Seed Data for Module 12: Escrow Demo Scenarios
-- Target: transactions and bookings tables
-- ============================================================

-- Ensure users 1 (Owner) and 2 (Renter) exist for these seeds
-- Adjust IDs if your local DB differs.

SET FOREIGN_KEY_CHECKS = 0;

-- SCENARIO 1: Escrow transaction in FUNDS_LOCKED state
-- Handover PIN: 1234
INSERT INTO transactions (transaction_id, equipment_id, renter_id, owner_id, booking_type, amount, status, handover_otp)
VALUES ('TXN-DEMO-LOCKED-001', 1, 2, 1, 'ESCROW', 1500.00, 'FUNDS_LOCKED', 1234);

INSERT INTO bookings (transaction_id, equipment_id, renter_id, owner_id, start_datetime, end_datetime, pricing_mode, total_price, status)
VALUES ('TXN-DEMO-LOCKED-001', 1, 2, 1, '2026-05-01 09:00:00', '2026-05-02 18:00:00', 'daily', 1500.00, 'confirmed');


-- SCENARIO 2: Escrow transaction in ACTIVE_RENTAL state
-- Return PIN: 5678
INSERT INTO transactions (transaction_id, equipment_id, renter_id, owner_id, booking_type, amount, status, return_otp)
VALUES ('TXN-DEMO-ACTIVE-002', 2, 2, 1, 'ESCROW', 3000.00, 'ACTIVE_RENTAL', 5678);

INSERT INTO bookings (transaction_id, equipment_id, renter_id, owner_id, start_datetime, end_datetime, pricing_mode, total_price, status)
VALUES ('TXN-DEMO-ACTIVE-002', 2, 2, 1, '2026-04-10 09:00:00', '2026-04-12 18:00:00', 'daily', 3000.00, 'active');


-- SCENARIO 3: Completed escrow transaction
INSERT INTO transactions (transaction_id, equipment_id, renter_id, owner_id, booking_type, amount, status)
VALUES ('TXN-DEMO-COMPLETED-003', 1, 2, 1, 'ESCROW', 750.00, 'COMPLETED');

INSERT INTO bookings (transaction_id, equipment_id, renter_id, owner_id, start_datetime, end_datetime, pricing_mode, total_price, status)
VALUES ('TXN-DEMO-COMPLETED-003', 1, 2, 1, '2026-03-01 09:00:00', '2026-03-01 18:00:00', 'daily', 750.00, 'completed');


-- SCENARIO 4: Manual deal initiated
INSERT INTO transactions (transaction_id, equipment_id, renter_id, owner_id, booking_type, amount, status)
VALUES ('TXN-DEMO-MANUAL-004', 3, 2, 1, 'MANUAL', 2000.00, 'MANUAL_DEAL_INITIATED');

INSERT INTO bookings (transaction_id, equipment_id, renter_id, owner_id, start_datetime, end_datetime, pricing_mode, total_price, status)
VALUES ('TXN-DEMO-MANUAL-004', 3, 2, 1, '2026-06-15 09:00:00', '2026-06-16 18:00:00', 'daily', 2000.00, 'confirmed');

SET FOREIGN_KEY_CHECKS = 1;
