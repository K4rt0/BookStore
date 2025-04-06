<?php
class ImgurService {
    private $clientId;

    public function __construct() {
        $this->clientId = $_ENV['IMGUR_CLIENT_ID'];
    }

    public function upload($imageBase64) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.imgur.com/3/image',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Client-ID {$this->clientId}"
            ],
            CURLOPT_POSTFIELDS => [
                'image' => $imageBase64
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function delete($deleteHash) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.imgur.com/3/image/{$deleteHash}",
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Client-ID {$this->clientId}"
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
