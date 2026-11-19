<?php

namespace App\Nextcloud;

use Exception;



class WorkerBot {
    private $baseUrl;
    private $authHeader;

    public function __construct($baseUrl, $user, $password) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->authHeader = 'Basic ' . base64_encode("$user:$password");
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = [
            'OCS-APIRequest: true', 
            'Authorization: ' . $this->authHeader,
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Отключаем SSL для твоего DDNS домена
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Ошибка cURL: " . $curlError);
        }

        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception("Ошибка API (HTTP $httpCode): " . $response);
        }

        return $decoded;
    }

    /**
     * Отправляет сообщение в конкретную комнату по её токену
     */
    public function sendMessage($roomToken, $message) {
        // Используем v1 для отправки сообщений в чат
        $endpoint = '/ocs/v2.php/apps/spreed/api/v1/chat/' . $roomToken;
        $data = ['message' => $message];
        
        $response = $this->request('POST', $endpoint, $data);
        
        if (isset($response['ocs']['meta']['status']) && $response['ocs']['meta']['status'] === 'ok') {
            return true;
        }
        
        throw new Exception("Не удалось отправить сообщение. Ответ: " . json_encode($response));
    }
}

// ==========================================
// ИСПОЛЬЗОВАНИЕ
// ==========================================

// try {
//     $bot = new NextcloudTalkBot(
//         env('NEXTCLOUD_DOMAIN'), 
//         env('NEXTCLOUD_WORKERBOT_NAME'), 
//         env('NEXTCLOUD_WORKERBOT_TOKEN')
//     );

//     // Твой готовый токен чата из URL
//     $roomToken = env('NEXTCLOUD_ROOM_ID'); 
    
//     $text = "🚀 *Привет!* Это тест из $roomToken .\nВремя: " . date('H:i:s');

//     echo "Отправляем сообщение в чат '$roomToken'...\n";
    
//     // Отправляем напрямую, без создания комнаты
//     $bot->sendMessage($roomToken, $text);
    
//     echo "✅ Успешно отправлено!\n";

// } catch (Exception $e) {
//     echo "❌ Ошибка: " . $e->getMessage() . "\n";
// }