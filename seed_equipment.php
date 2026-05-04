<?php
require_once __DIR__ . '/config/db.php';

try {
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->begin_transaction();

    // 1. Wipe existing equipment
    $conn->query("TRUNCATE TABLE equipment");

    // 2. Fetch an admin or first user to be the owner
    $res = $conn->query("SELECT id FROM users LIMIT 1");
    $owner = $res->fetch_assoc();
    $owner_id = $owner ? $owner['id'] : 1; // Fallback to 1

    // 3. Realistic records
    $equipment = [
        ['John Deere 5050D Tractor', 'Reliable 50 HP tractor suitable for heavy-duty plowing and tilling. Includes power steering and high backup torque.', 'Tractor', 1200, 1],
        ['Mahindra Yuvraj 215 NXT', 'Compact 15 HP tractor ideal for inter-culture operations in orchards and vineyards.', 'Tractor', 800, 0],
        ['Kubota Harvester DC-68G', 'High-efficiency combine harvester for paddy and wheat. Ensures minimal grain loss and fast harvesting.', 'Harvester', 4500, 1],
        ['Swaraj 744 FE', '48 HP tractor with water-cooled engine. Great for rotavator and cultivator implements.', 'Tractor', 1100, 0],
        ['Lemken Cultivator', 'Heavy-duty 9-tine cultivator for deep tillage and soil aeration.', 'Implement', 400, 0],
        ['Rotavator 6 Feet', 'Perfect for seedbed preparation in single pass. Compatible with 40+ HP tractors.', 'Implement', 600, 0],
        ['Massey Ferguson 241 DI', '42 HP tractor, highly versatile for haulage and agricultural tasks.', 'Tractor', 1000, 0],
        ['Heavy Duty Ploughs (Reversible)', 'Hydraulic reversible MB plough for deep plowing in hard soils.', 'Implement', 500, 0],
        ['Paddy Transplanter', 'Ride-on type paddy transplanter, covers 4-5 acres per day.', 'Machine', 2500, 1],
        ['Power Tiller 12 HP', 'Walking tractor/power tiller for small holdings and wet land puddling.', 'Machine', 700, 0],
        ['ASPEE Boom Sprayer', 'Tractor mounted boom sprayer with 400L tank for uniform chemical application.', 'Implement', 300, 0],
        ['New Holland 3600-2 TX', '50 HP tractor with advanced styling and features for modern farming.', 'Tractor', 1300, 0],
        ['Sugarcane Harvester', 'Specialized harvester for sugarcane crops, reduces labor dependency.', 'Harvester', 5000, 1],
        ['Laser Land Leveler', 'Precision laser guided land leveler for saving water and improving yield.', 'Implement', 1500, 1],
        ['Seed Drill Machine', 'Tractor drawn seed cum fertilizer drill for accurate sowing of wheat and gram.', 'Implement', 450, 0]
    ];

    $stmt = $conn->prepare("INSERT INTO equipment (owner_id, title, description, category, price_per_day, includes_operator, location_village, location_district, location_state, images, is_available) VALUES (?, ?, ?, ?, ?, ?, 'Sample Village', 'Sample District', 'State', '[]', 1)");

    foreach ($equipment as $eq) {
        $stmt->bind_param('isssdi', $owner_id, $eq[0], $eq[1], $eq[2], $eq[3], $eq[4]);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    echo "Successfully seeded " . count($equipment) . " realistic equipment records.";
} catch (Exception $e) {
    $conn->rollback();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    echo "Error: " . $e->getMessage();
}
