<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthHelper {
    public static function generateAccessToken($userId, $expiration = null, $secret = null) {
        $expiration = $expiration ?? $_ENV['JWT_EXPIRATION'];
        $secret = $secret ?? $_ENV['JWT_SECRET'];
        
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (int)$expiration
        ];
        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function generateRefreshToken($userId, $expiration = null, $secret = null) {
        $expiration = $expiration ?? $_ENV['JWT_EXPIRATION'];
        $secret = $secret ?? $_ENV['JWT_SECRET'];
        
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (int)$expiration
        ];
        return JWT::encode($payload, $secret, 'HS256');
    }
    
    public static function verifyToken($token, $secret = null) {
        $secret = $secret ?? $_ENV['JWT_SECRET'];
        return JWT::decode($token, new Key($secret, 'HS256'));
    }
}
