<?php
class PayPalService {
    public static function createPaymentUrl($orderId, $amount, $orderInfo = 'Thanh toan PayPal') {
        $clientId = $_ENV['PAYPAL_CLIENT_ID'];
        $returnUrl = $_ENV['PAYPAL_RETURN_URL'];
        $cancelUrl = $_ENV['PAYPAL_CANCEL_URL'];
        
        $orderId = preg_replace('/[^a-zA-Z0-9]/', '', $orderId);
        
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $orderId,
                    'description' => $orderInfo,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount / 23000, 2, '.', '')
                    ]
                ]
            ],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'BookStore',
                'locale' => 'vi-VN',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING'
            ]
        ];
        
        $accessToken = self::getAccessToken();
        
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Không thể kết nối đến PayPal API'
            ];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api-m.sandbox.paypal.com/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($statusCode != 201) {
            self::logError('create_order', $statusCode, $error, $response);
            return [
                'success' => false,
                'message' => 'Có lỗi khi tạo đơn hàng PayPal: ' . $error,
                'status_code' => $statusCode,
                'response' => json_decode($response, true)
            ];
        }
        
        $responseData = json_decode($response, true);
        
        $approveUrl = null;
        if (isset($responseData['links'])) {
            foreach ($responseData['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approveUrl = $link['href'];
                    break;
                }
            }
        }
        
        if ($approveUrl) {
            return [
                'success' => true,
                'payment_url' => $approveUrl,
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không nhận được URL thanh toán từ PayPal',
                'response' => $responseData
            ];
        }
    }
    
    private static function getAccessToken() {
        $clientId = $_ENV['PAYPAL_CLIENT_ID'];
        $clientSecret = $_ENV['PAYPAL_SECRET_KEY'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api-m.sandbox.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($statusCode == 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }
        
        self::logError('get_access_token', $statusCode, curl_error($ch), $response);
        return null;
    }
    
    public static function verifyPayment($orderId, $token, $payerId) {
        try {
            $accessToken = self::getAccessToken();
            
            // Gọi API PayPal để xác minh thanh toán
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/" . $token);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => 'Không thể xác minh thanh toán với PayPal',
                    'response' => json_decode($response, true)
                ];
            }
            
            $result = json_decode($response, true);
            
            // Kiểm tra trạng thái thanh toán
            if ($result['status'] === 'COMPLETED' && $result['payer']['payer_id'] === $payerId) {
                return [
                    'success' => true,
                    'message' => 'Thanh toán thành công',
                    'response' => $result
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Thanh toán chưa hoàn thành',
                'response' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi xác minh thanh toán: ' . $e->getMessage()
            ];
        }
    }
    
    private static function logError($action, $statusCode, $error, $response) {
        $logDir = __DIR__ . '/../../logs';
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/paypal_error_' . date('Y-m-d') . '.log';
        $logData = date('Y-m-d H:i:s') . ' | ' . $action . ' | Status Code: ' . $statusCode . ' | Error: ' . $error . ' | Response: ' . $response . PHP_EOL;
        
        file_put_contents($logFile, $logData, FILE_APPEND);
    }
} 