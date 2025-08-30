<?php

namespace ProcessFlows;

class OpenRouterService
{
    //baseUrl
    public static string $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
    //token
    public static string $token = '';

    //model
    public static string $model = 'openrouter/auto';

    public static function init()
    {

        //add settings for OpenRouterService
    }

    //__invoke method to handle requests
    public function __construct($token)
    {
        self::$token = $token;

        // return $this;
    }

    //prompt
    public static function prompt($message)
    {

        $body = wp_json_encode([
            'model' => self::$model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant providing concise and accurate answers.'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ]);

        if (is_array($message)) {
            $body = wp_json_encode($message);
        }

        // Send a prompt message to the OpenRouter API - use wp_remote_request
        $response = wp_remote_request(self::$baseUrl, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer '.self::$token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 50,
            'body' => $body,
        ]);
        // var_dump($response);

        // return $response;
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // return $response_body;

        // Check if the response contains the expected data
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        } else {
            return null;
        }
    }

}
