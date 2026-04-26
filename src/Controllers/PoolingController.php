<?php
/**
 * PoolingController.php
 * Handles P2P crowdsourcing supply campaigns and farmer pledges.
 */

/**
 * 1. Create a new pooling campaign
 */
function createCampaign($conn, $data, $userId) {
    $title = trim($data['title'] ?? '');
    $itemName = trim($data['item_name'] ?? '');
    $unit = trim($data['unit'] ?? '');
    $offeringPrice = (float)($data['offering_price'] ?? 0);
    $targetQty = (float)($data['target_quantity'] ?? 0);
    $minContribution = (float)($data['min_contribution'] ?? 1);
    $deadline = $data['deadline'] ?? '';
    $district = trim($data['district'] ?? '');
    $description = trim($data['description'] ?? '');

    if (empty($title) || mb_strlen($title) > 150) return ['success' => false, 'message' => 'Invalid title.'];
    if (empty($itemName)) return ['success' => false, 'message' => 'Item name is required.'];
    if (empty($unit)) return ['success' => false, 'message' => 'Unit is required.'];
    if ($offeringPrice <= 0) return ['success' => false, 'message' => 'Offering price must be greater than zero.'];
    if ($targetQty <= 0) return ['success' => false, 'message' => 'Target quantity must be greater than zero.'];
    if ($minContribution <= 0) return ['success' => false, 'message' => 'Minimum contribution must be at least 1.'];
    if (strtotime($deadline) <= time()) return ['success' => false, 'message' => 'Deadline must be a future date.'];

    $stmt = $conn->prepare("INSERT INTO pooling_campaigns (creator_id, title, item_name, unit, offering_price, target_quantity, min_contribution, deadline, district, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssddisss", $userId, $title, $itemName, $unit, $offeringPrice, $targetQty, $minContribution, $deadline, $district, $description);

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $id];
    }

    $stmt->close();
    return ['success' => false, 'message' => 'Failed to create campaign.'];
}

/**
 * 2. Get all campaigns with optional filters
 */
function getCampaigns($conn, $filters = []) {
    $sql = "SELECT pc.*, u.full_name as creator_name 
            FROM pooling_campaigns pc
            JOIN users u ON pc.creator_id = u.id";
    $where = [];
    $types = "";
    $params = [];

    if (!empty($filters['district'])) {
        $where[] = "pc.district = ?";
        $params[] = $filters['district'];
        $types .= "s";
    }
    if (!empty($filters['status'])) {
        $where[] = "pc.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY pc.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

/**
 * 3. Get a single campaign by ID
 */
function getCampaignById($conn, $id) {
    $stmt = $conn->prepare("SELECT pooling_campaigns.*, users.full_name as creator_name FROM pooling_campaigns JOIN users ON users.id = pooling_campaigns.creator_id WHERE pooling_campaigns.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res;
}

/**
 * 4. Get all pledges for a campaign
 */
function getPledges($conn, $campaignId) {
    $stmt = $conn->prepare("SELECT pooling_pledges.*, users.full_name FROM pooling_pledges JOIN users ON users.id = pooling_pledges.farmer_id WHERE campaign_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $res;
}

/**
 * 5. Get a specific user's pledge for a campaign
 */
function getUserPledge($conn, $campaignId, $userId) {
    $stmt = $conn->prepare("SELECT * FROM pooling_pledges WHERE campaign_id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $campaignId, $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res;
}

/**
 * 6. Add a pledge to a campaign
 */
function addPledge($conn, $campaignId, $userId, $qty) {
    $conn->begin_transaction();

    try {
        // Step 1: Check status and deadline with lock
        $stmt = $conn->prepare("SELECT id, status, deadline, target_quantity, min_contribution, current_quantity FROM pooling_campaigns WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $camp = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$camp || $camp['status'] !== 'open' || strtotime($camp['deadline']) < strtotime(date('Y-m-d'))) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Campaign is no longer accepting pledges.'];
        }

        if ($qty < $camp['min_contribution']) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Minimum contribution is ' . $camp['min_contribution']];
        }

        // Step 2: Insert pledge
        $stmt = $conn->prepare("INSERT INTO pooling_pledges (campaign_id, farmer_id, quantity_pledged) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $campaignId, $userId, $qty);
        if (!$stmt->execute()) {
            if ($conn->errno === 1062) {
                $stmt->close();
                $conn->rollback();
                return ['success' => false, 'message' => 'You have already pledged to this campaign.'];
            }
            throw new Exception("Pledge insert failed");
        }
        $stmt->close();

        // Step 3: Update campaign quantity
        $stmt = $conn->prepare("UPDATE pooling_campaigns SET current_quantity = current_quantity + ? WHERE id = ?");
        $stmt->bind_param("di", $qty, $campaignId);
        $stmt->execute();
        $stmt->close();

        // Step 4: Fetch updated quantity
        $stmt = $conn->prepare("SELECT status, current_quantity, target_quantity FROM pooling_campaigns WHERE id = ?");
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $updatedCamp = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $newStatus = $updatedCamp['status'];

        // Step 5: Check threshold
        if ($updatedCamp['current_quantity'] >= $updatedCamp['target_quantity'] && $updatedCamp['status'] === 'open') {
            $stmt = $conn->prepare("UPDATE pooling_campaigns SET status = 'threshold_met' WHERE id = ?");
            $stmt->bind_param("i", $campaignId);
            $stmt->execute();
            $stmt->close();
            $newStatus = 'threshold_met';

            // Step 6: Notify every pledger
            $stmt = $conn->prepare("SELECT farmer_id FROM pooling_pledges WHERE campaign_id = ?");
            $stmt->bind_param("i", $campaignId);
            $stmt->execute();
            $pledgers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $msg = "Threshold met for supply campaign #" . $campaignId . "! Target quantity achieved.";
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            foreach ($pledgers as $p) {
                $notifStmt->bind_param("is", $p['farmer_id'], $msg);
                $notifStmt->execute();
            }
            $notifStmt->close();
        }

        $conn->commit();
        return ['success' => true, 'new_quantity' => $updatedCamp['current_quantity'], 'status' => $newStatus];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'An error occurred while pledging.'];
    }
}

/**
 * 7. Cancel a user's pledge
 */
function cancelPledge($conn, $campaignId, $userId) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT quantity_pledged FROM pooling_pledges WHERE campaign_id = ? AND farmer_id = ?");
        $stmt->bind_param("ii", $campaignId, $userId);
        $stmt->execute();
        $pledge = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pledge) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Pledge not found.'];
        }

        $qty = $pledge['quantity_pledged'];

        $stmt = $conn->prepare("DELETE FROM pooling_pledges WHERE campaign_id = ? AND farmer_id = ?");
        $stmt->bind_param("ii", $campaignId, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE pooling_campaigns SET current_quantity = GREATEST(0, current_quantity - ?) WHERE id = ?");
        $stmt->bind_param("di", $qty, $campaignId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT current_quantity FROM pooling_campaigns WHERE id = ?");
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $updatedQty = $stmt->get_result()->fetch_assoc()['current_quantity'];
        $stmt->close();

        $conn->commit();
        return ['success' => true, 'new_quantity' => $updatedQty];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Failed to cancel pledge.'];
    }
}

/**
 * 8. Close a campaign (Owner only)
 */
function closeCampaign($conn, $id, $userId) {
    $stmt = $conn->prepare("SELECT creator_id FROM pooling_campaigns WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $camp = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$camp || (int)$camp['creator_id'] !== (int)$userId) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    $stmt = $conn->prepare("UPDATE pooling_campaigns SET status = 'closed' WHERE id = ? AND status = 'open'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    return ['success' => true];
}

/**
 * 9. Expire campaigns past their deadline
 */
function expireDeadlines($conn) {
    $stmt = $conn->prepare("UPDATE pooling_campaigns SET status = 'cancelled' WHERE deadline < CURDATE() AND status = 'open'");
    $stmt->execute();
    $stmt->close();
}
