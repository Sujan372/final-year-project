<?php
function getFuelPrices($city = '') {
    // If a city is provided, try a city‑wise API
    if (!empty($city)) {
        $url = "https://api.petroldieselprice.com/v1/fuel-prices?city=" . urlencode($city);
        $json = @file_get_contents($url);
        if ($json !== false) {
            $data = json_decode($json, true);
            if (isset($data['petrol']) && isset($data['diesel']) && isset($data['cng'])) {
                return [
                    'petrol' => floatval($data['petrol']),
                    'diesel' => floatval($data['diesel']),
                    'cng'    => floatval($data['cng']),
                    'city'   => ucfirst($city)
                ];
            }
        }
        // Fallback for that city
        return [
            'petrol' => 104.23,
            'diesel' => 92.15,
            'cng'    => 76.50,
            'city'   => ucfirst($city)
        ];
    }

    // Default (no city) – try a generic API
    $apiUrl = "https://fuelprice-api.herokuapp.com/";
    $json = @file_get_contents($apiUrl);
    if ($json !== false) {
        $data = json_decode($json, true);
        if (isset($data['petrol']) && isset($data['diesel']) && isset($data['cng'])) {
            return [
                'petrol' => floatval($data['petrol']),
                'diesel' => floatval($data['diesel']),
                'cng'    => floatval($data['cng']),
                'city'   => 'Default'
            ];
        }
    }

    // Ultimate fallback
    return [
        'petrol' => 104.23,
        'diesel' => 92.15,
        'cng'    => 76.50,
        'city'   => 'Default'
    ];
}
?>