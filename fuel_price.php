<?php
function getFuelPrices($city = '') {
    // API that supports city‑wise fuel prices (no key required)
    $baseUrl = "https://api.petroldieselprice.com/v1/fuel-prices";
    
    if (!empty($city)) {
        $url = $baseUrl . "?city=" . urlencode($city);
    } else {
        // If no city is given, return generic fallback
        return [
            'petrol' => 104.23,
            'diesel' => 92.15,
            'cng'    => 76.50,
            'city'   => 'Default'
        ];
    }

    $json = @file_get_contents($url);
    if ($json === false) {
        // API down – use fallback
        return [
            'petrol' => 104.23,
            'diesel' => 92.15,
            'cng'    => 76.50,
            'city'   => ucfirst($city)
        ];
    }

    $data = json_decode($json, true);
    if (
        isset($data['petrol']) &&
        isset($data['diesel']) &&
        isset($data['cng'])
    ) {
        return [
            'petrol' => floatval($data['petrol']),
            'diesel' => floatval($data['diesel']),
            'cng'    => floatval($data['cng']),
            'city'   => ucfirst($city)
        ];
    }

    // API response invalid – fallback
    return [
        'petrol' => 104.23,
        'diesel' => 92.15,
        'cng'    => 76.50,
        'city'   => ucfirst($city)
    ];
}
?>