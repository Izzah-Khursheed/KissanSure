<?php
/*
 * analyze_ai.php  (user side)
 * Accepts 6 crop images + crop_type, runs classification on each.
 * Claim is AUTO-APPROVED if >=3 images are damaged, AUTO-REJECTED otherwise.
 * Segmentation is disabled.
 */

header('Content-Type: application/json');

if (!isset($_POST['crop_type']) || !isset($_FILES['images'])) {
    echo json_encode(['error' => 'Missing crop_type or images']);
    exit();
}

$cropType = trim($_POST['crop_type']);
$images   = $_FILES['images'];
$count    = count($images['name']);

if ($count !== 6) {
    echo json_encode(['error' => 'Exactly 6 images are required. Got: ' . $count]);
    exit();
}

// ---------- helper: POST one image to FastAPI ----------
function callFastAPI(string $url, string $tmpPath, string $origName, string $mimeType, string $cropType): array
{
    $postFields = [
        'crop_type' => $cropType,
        'file'      => new CURLFile($tmpPath, $mimeType, $origName)
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $postFields);
    curl_setopt($ch, CURLOPT_TIMEOUT,        120);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'cURL error: ' . $curlError, 'http_code' => 0];
    }
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return ['error' => 'Invalid JSON from AI server', 'http_code' => $httpCode];
    }
    $decoded['http_code'] = $httpCode;
    return $decoded;
}

// ---------- Step 1: classify all 6 images ----------
$classifyUrl  = getenv('AI_API_URL') ?: "http://127.0.0.1:8000/analyze";
$imageResults = [];
$damagedCount = 0;
$firstDamagedIdx = -1;

for ($i = 0; $i < 6; $i++) {
    $res = callFastAPI(
        $classifyUrl,
        $images['tmp_name'][$i],
        $images['name'][$i],
        $images['type'][$i],
        $cropType
    );

    if (isset($res['error'])) {
        echo json_encode(['error' => 'Classification failed on image ' . ($i + 1) . ': ' . $res['error']]);
        exit();
    }

    $imageResults[] = [
        'image'      => $i + 1,
        'class'      => $res['class'],
        'confidence' => $res['confidence']
    ];

    if ($res['class'] === 'damaged') {
        $damagedCount++;
        if ($firstDamagedIdx === -1) {
            $firstDamagedIdx = $i;
        }
    }
}

// ---------- Step 2: decide eligibility & build verdict ----------
$isEligible    = ($damagedCount >= 3);
$damagePercent = round(($damagedCount / 6) * 100, 1);  // based on classifier count
$aiResult      = $isEligible ? 'damaged' : 'healthy';

if ($isEligible) {
    $verdict = "Crop Damage Confirmed. {$damagedCount} of 6 images show damage "
             . "({$damagePercent}%). This claim has been AUTO-APPROVED.";
} else {
    $verdict = "Insufficient Damage Detected. Only {$damagedCount} of 6 images show damage "
             . "({$damagePercent}%). Minimum 3 images (50%) required. "
             . "This claim has been AUTO-REJECTED.";
}

echo json_encode([
    'ai_result'         => $aiResult,
    'ai_confidence'     => $damagePercent,
    'damaged_count'     => $damagedCount,
    'total_images'      => 6,
    'is_eligible'       => $isEligible,
    'damage_percent'    => $damagePercent,
    'segmentation_used' => false,
    'verdict'           => $verdict,
    'image_results'     => $imageResults
]);
