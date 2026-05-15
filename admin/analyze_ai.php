<?php
/*
 * analyze_ai.php  (admin side)
 * Runs classification on 6 images. Segmentation is disabled.
 * Identical logic to user/analyze_ai.php
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
    curl_setopt($ch, CURLOPT_TIMEOUT,        60);
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

$classifyUrl     = "http://127.0.0.1:8000/analyze";
$imageResults    = [];
$damagedCount    = 0;
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

$isEligible    = ($damagedCount >= 3);
$damagePercent = round(($damagedCount / 6) * 100, 1);
$aiResult      = $isEligible ? 'damaged' : 'healthy';

// ── Segmentation DISABLED ────────────────────────────────────────────────────
// Uncomment and re-enable /segment in main.py + predict.py to use segmentation.
// $segUsed = false;
// if ($isEligible) {
//     $segUrl      = "http://127.0.0.1:8000/segment";
//     $segPercents = [];
//     $segAllOk    = true;
//     for ($i = 0; $i < 6; $i++) {
//         $segRes = callFastAPI($segUrl, $images['tmp_name'][$i], $images['name'][$i], $images['type'][$i], $cropType);
//         if (($segRes['http_code'] ?? 0) !== 200 || isset($segRes['error']) || !isset($segRes['damage_percent'])) {
//             $segAllOk = false; break;
//         }
//         $segPercents[] = (float)$segRes['damage_percent'];
//     }
//     if ($segAllOk && count($segPercents) === 6) {
//         $damagePercent = round(array_sum($segPercents) / 6, 1);
//         $segUsed = true;
//     }
// }

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
