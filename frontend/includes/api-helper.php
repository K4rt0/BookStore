<?php
function call_api_with_token($url, $method = 'GET', $data = null, $retry = true) {
    $access_token = $_SESSION['access_token'] ?? null;
    if (!$access_token) {
        throw new Exception("Missing access token.");
    }

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = json_decode($response, true);

    if ($http_code === 401 && $retry && isset($result['message']) && stripos($result['message'], 'expired') !== false) {
        if (refresh_token()) {
            return call_api_with_token($url, $method, $data, false);
        }
    }

    return $result;
}

function refresh_token(): bool {
    if (!isset($_SESSION['refresh_token'])) return false;

    $refresh_url = $_ENV['API_BASE_URL'] . 'users?action=refresh-token';
    $data = ['refresh_token' => $_SESSION['refresh_token']];

    $ch = curl_init($refresh_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($result && $result['success']) {
        $_SESSION['access_token'] = $result['data']['access_token'];
        $_SESSION['refresh_token'] = $result['data']['refresh_token'] ?? $_SESSION['refresh_token'];
        return true;
    }
    return false;
}
