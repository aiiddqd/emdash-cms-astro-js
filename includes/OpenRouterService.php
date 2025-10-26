<?php

namespace ProcessFlows;

use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Enums\Method;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Http\Auth\TokenAuthenticator;

/**
 * OpenRouter API Connector
 *
 * Base URL: https://openrouter.ai/api/v1
 * Auth: Bearer <OPENROUTER_API_KEY>
 * Optional headers:
 *   - HTTP-Referer: Your app/site URL
 *   - X-Title: Your app/site name
 */
class OpenRouterService extends Connector
{
    use AcceptsJson;

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $referer = null,
        protected ?string $appTitle = 'ProcessFlow',
    ) {
        if (empty($this->apiKey)) {
            $value = Plugin::getConfig('openrouter_api_key');
            if ($value) {
                $this->apiKey = $value;
            }
        }
    }

    /**
     * Get JSON response by format and prompt
     *
     * @param string $formatSystemInstruction - describe JSON format or structure, also set role for system
     * @param string $prompt - user prompt
     * @return array
     */
    public function getJsonByFormatAndPrompt(string $formatSystemInstruction, string $prompt): array
    {
        $request = new ChatCompletionsRequest(
            messages: [
                [
                    'role' => 'system',
                    'content' => $formatSystemInstruction
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            options: [
                'model' => 'openrouter/auto',
                'response_format' => [
                    'type' => 'json_object'
                ]
            ]);

        $response = $this->send($request);
        $data = $response->json();
        $data = $data['choices'][0]['message']['content'] ?? null;

        if ($data) {
            $data = json_decode($data, true);
        }

        return $data ?? [];
    }

    /**
     * simple example of prompting by text
     *
     * @param string $text
     */
    public function getTextByPrompt(string $text)
    {
        $request = new ChatCompletionsRequest([
            ['role' => 'user', 'content' => $text]
        ]);

        $response = $this->send($request);
        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function resolveBaseUrl(): string
    {
        return 'https://openrouter.ai/api/v1';
    }

    // Adds: Authorization: Bearer <token>
    public function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiKey);
    }

    public function defaultHeaders(): array
    {
        // AcceptsJson sets: Accept: application/json
        // We'll ensure JSON requests by default.
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->referer)) {
            $headers['HTTP-Referer'] = $this->referer;
        }

        if (! empty($this->appTitle)) {
            $headers['X-Title'] = $this->appTitle;
        }

        return $headers;
    }

    public function defaultConfig(): array
    {
        return [
            'timeout' => 120, // seconds
        ];
    }
}

/**
 * Chat Completions Request
 *
 * POST /chat/completions
 * Body must include: model and messages[]
 */
class ChatCompletionsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    //$model
    protected string $model = 'openrouter/auto';

    public function __construct(
        /** @var array<int, array{role:string, content:string|array}> $messages */
        protected array $messages,
        protected ?array $options = null // any extra OpenAI/OpenRouter-style params (temperature, tools, etc.)
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return '/chat/completions';
    }

    protected function defaultBody(): array
    {
        // Merge options over defaults, allowing 'model' in options to override property
        $defaults = [
            'model' => $this->model,
            'messages' => $this->messages,
        ];

        return array_merge($defaults, $this->options ?? []);
    }

    // protected function defaultConfig(): array
    // {
    //     return [
    //         'timeout' => 120,        // Request timeout in seconds
    //         'connect_timeout' => 10, // Connection timeout in seconds
    //     ];
    // }
}


OpenRouterServiceLegacy::init();

class OpenRouterServiceLegacy
{
    //baseUrl
    public static string $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
    //token
    public static string $token = '';

    //model
    public static string $model = 'openrouter/auto';

    public static function init()
    {

        // add settings for OpenRouterServiceLegacy
        add_action('admin_init', [self::class, 'add_settings']);

    }

    public static function add_settings()
    {
        add_settings_section(
            'openrouter_integration',
            __('OpenRouter Integration', 'process-flows'),
            function () {
                echo '<p>'.__('Configure OpenRouter API integration settings.', 'process-flows').'</p>';
            },
            Plugin::$settings_slug
        );

        add_settings_field(
            'openrouter_api_key',
            __('OpenRouter API Key', 'process-flows'),
            function () {
                $value = Plugin::getConfig('openrouter_api_key');
                echo '<input type="text" name="'.Plugin::getConfigFieldName('openrouter_api_key').'" value="'.esc_attr($value).'" />';
            },
            Plugin::$settings_slug,
            'openrouter_integration'
        );

    }

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
