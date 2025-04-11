<?php
class MomoService {
    public static function createPaymentUrl($payment_id, $amount, $orderInfo = 'Thanh toan MoMo') {
        $endpoint = $_ENV['MOMO_API_URL'];
        $partnerCode = $_ENV['MOMO_PARTNER_CODE'];
        $accessKey = $_ENV['MOMO_ACCESS_KEY'];
        $secretKey = $_ENV['MOMO_SECRET_KEY'];
        $returnUrl = $_ENV['MOMO_RETURN_URL'] . '&payment_id=' . urlencode($payment_id);
        $notifyUrl = $_ENV['MOMO_NOTIFY_URL'];
        $requestType = $_ENV['MOMO_REQUEST_TYPE'];
        
        $payment_id = preg_replace('/[^a-zA-Z0-9]/', '', $payment_id);
        
        $requestId = time() . rand(1000, 9999);
        
        $rawData = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => (string)$amount,
            'orderId' => $payment_id,
            'orderInfo' => $orderInfo,
            'returnUrl' => $returnUrl,
            'notifyUrl' => $notifyUrl,
            'extraData' => '',
            'requestType' => $requestType
        ];
        
        $rawSignature = "partnerCode=" . $partnerCode . 
                       "&accessKey=" . $accessKey . 
                       "&requestId=" . $requestId . 
                       "&amount=" . $amount . 
                       "&orderId=" . $payment_id . 
                       "&orderInfo=" . $orderInfo . 
                       "&returnUrl=" . $returnUrl . 
                       "&notifyUrl=" . $notifyUrl . 
                       "&extraData=";
        
        $signature = hash_hmac('sha256', $rawSignature, $secretKey);
        $rawData['signature'] = $signature;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rawData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($statusCode != 200) {
            return [
                'success' => false,
                'message' => 'Có lỗi khi kết nối đến MoMo: ' . $error,
                'status_code' => $statusCode
            ];
        }
        
        $responseData = json_decode($response, true);

        if (isset($responseData['payUrl'])) {
            return [
                'success' => true,
                'payment_url' => $responseData['payUrl'],
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không nhận được URL thanh toán từ MoMo',
                'response' => $responseData
            ];
        }
    }
} 