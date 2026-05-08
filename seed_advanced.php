<?php
require_once __DIR__ . '/config/db.php';

try {
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->begin_transaction();

    // Wipe existing users (except admin or default if needed, but let's just wipe non-admin or we can just append)
    // Actually, the user asked to "add some data and users", let's just insert new ones to avoid deleting the admin.
    
    // Create Users
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    
    $usersData = [
        ['Ramesh Kumar', '9876543210', 'ramesh@example.com', 'Sivaganga', 'Madurai', 'Tamil Nadu', 'uploads/profiles/ramesh.png'],
        ['Suresh Patel', '9876543211', 'suresh@example.com', 'Anand', 'Anand', 'Gujarat', 'uploads/profiles/suresh.png'],
        ['Amit Singh', '9876543212', 'amit@example.com', 'Sonipat', 'Sonipat', 'Haryana', 'uploads/profiles/amit.png']
    ];

    $user_ids = [];
    $stmtUser = $conn->prepare("INSERT INTO users (full_name, phone, email, village, district, state, password_hash, profile_photo, trust_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 4.8) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), email=VALUES(email), profile_photo=VALUES(profile_photo)");
    
    foreach ($usersData as $u) {
        $stmtUser->bind_param('ssssssss', $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $password_hash, $u[6]);
        $stmtUser->execute();
        $user_ids[] = $stmtUser->insert_id ?: $conn->query("SELECT id FROM users WHERE phone='{$u[1]}'")->fetch_assoc()['id'];
    }
    $stmtUser->close();

    // Equipment Data mapping to the images we generated
    $tractorImg = '["uploads/equipment/tractor.png"]';
    $harvesterImg = '["uploads/equipment/harvester.png"]';
    $cultivatorImg = '["uploads/equipment/cultivator.png"]';

    $equipment = [
        // Ramesh's Equipment
        [$user_ids[0], 'John Deere 5050D Tractor', 'Reliable 50 HP tractor suitable for heavy-duty plowing and tilling. Includes power steering and high backup torque.', 'tractor', 1200, 1, $usersData[0][2], $usersData[0][3], $tractorImg],
        [$user_ids[0], 'Heavy Duty Cultivator', '9-tine heavy duty cultivator for deep tillage. Excellent for breaking hard soil.', 'cultivator', 400, 0, $usersData[0][2], $usersData[0][3], $cultivatorImg],
        
        // Suresh's Equipment
        [$user_ids[1], 'Kubota Harvester DC-68G', 'High-efficiency combine harvester for paddy and wheat. Ensures minimal grain loss and fast harvesting.', 'harvester', 4500, 1, $usersData[1][2], $usersData[1][3], $harvesterImg],
        [$user_ids[1], 'Swaraj 744 FE Tractor', '48 HP tractor with water-cooled engine. Great for rotavator and cultivator implements.', 'tractor', 1100, 1, $usersData[1][2], $usersData[1][3], $tractorImg],

        // Amit's Equipment
        [$user_ids[2], 'Paddy Transplanter', 'Ride-on type paddy transplanter, covers 4-5 acres per day.', 'other', 2500, 1, $usersData[2][2], $usersData[2][3], $harvesterImg], // Reusing harvester img as placeholder
        [$user_ids[2], 'Rotavator 6 Feet', 'Perfect for seedbed preparation in single pass. Compatible with 40+ HP tractors.', 'rotavator', 600, 0, $usersData[2][2], $usersData[2][3], $cultivatorImg],
    ];

    $stmtEq = $conn->prepare("INSERT INTO equipment (owner_id, title, description, category, price_per_day, includes_operator, location_village, location_district, images, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

    foreach ($equipment as $eq) {
        // Check if already exists to prevent duplication
        $checkStmt = $conn->prepare("SELECT id FROM equipment WHERE owner_id = ? AND title = ?");
        $checkStmt->bind_param('is', $eq[0], $eq[1]);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $stmtEq->bind_param('isssdssss', $eq[0], $eq[1], $eq[2], $eq[3], $eq[4], $eq[5], $eq[6], $eq[7], $eq[8]);
            $stmtEq->execute();
        }
        $checkStmt->close();
    }
    $stmtEq->close();

    $conn->commit();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    // ── 5. Seed Equipment for Suresh Patel (ID 1025) ──────────────────
    $sureshId = 1025;
    $sureshEq = [
        [
            'title' => 'Power Tiller 15HP',
            'category' => 'power_tiller',
            'description' => 'High performance power tiller for efficient field preparation. Includes multiple attachments for versatile use.',
            'price_per_day' => 800.00,
            'safety_deposit' => 2000.00,
            'includes_operator' => 1,
            'location_village' => 'Hoskote',
            'location_district' => 'Bengaluru Rural',
            'condition' => 'excellent'
        ],
        [
            'title' => 'Chaff Cutter (Electric)',
            'category' => 'chaff_cutter',
            'description' => 'Electric chaff cutter for animal feed preparation. Very efficient and easy to operate.',
            'price_per_day' => 300.00,
            'safety_deposit' => 500.00,
            'includes_operator' => 0,
            'location_village' => 'Doddaballapura',
            'location_district' => 'Bengaluru Rural',
            'condition' => 'good'
        ]
    ];

    foreach ($sureshEq as $eq) {
        // Check if already exists
        $checkStmt = $conn->prepare("SELECT id FROM equipment WHERE owner_id = ? AND title = ?");
        $checkStmt->bind_param('is', $sureshId, $eq['title']);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO equipment (owner_id, title, category, description, price_per_day, safety_deposit, includes_operator, location_village, location_district, images, `condition`, created_at, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', ?, NOW(), 1)");
            $stmt->bind_param('isssddisss', $sureshId, $eq['title'], $eq['category'], $eq['description'], $eq['price_per_day'], $eq['safety_deposit'], $eq['includes_operator'], $eq['location_village'], $eq['location_district'], $eq['condition']);
            $stmt->execute();
            $stmt->close();
        }
        $checkStmt->close();
    }

    echo "Advanced seeding completed successfully.\nSuccessfully seeded " . count($usersData) . " users and " . count($equipment) . " equipment records with images.";
} catch (Exception $e) {
    $conn->rollback();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    echo "Error: " . $e->getMessage();
}
