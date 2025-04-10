<?php
function call_api_with_token($url, $method = 'GET', $data = []) {
    $token = $_SESSION['auth_token'] ?? '';
    if (empty($token)) {
        return [
            'success' => false,
            'message' => 'Authentication token is missing.',
        ];
    }

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif (strtoupper($method) === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return [
            'success' => false,
            'message' => 'API request failed with status ' . $http_code,
        ];
    }

    return json_decode($response, true);
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
