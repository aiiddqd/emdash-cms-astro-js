<?php

namespace ProcessFlows;

use WP_REST_Request, WP_Error, WP_CLI, Exception;

abstract class FlowAbstract
{
    /**
     * Interval in seconds for the flow
     * 
     * Used for scheduling and timing purposes.
     * You have to set this value to enable scheduling.
     */
    protected static ?int $intervalInSeconds = null;

    /**
     * The slug for the flow
     * 
     * This is used to uniquely identify the flow within the system.
     * Used for save to related post in the database.
     * Also used for actions, routing and webhook purposes.
     */
    public static string $slug;

    /**
     * The title for the flow
     * 
     * This is used as a human-readable identifier for the flow.
     * Used for save to related post in the database.
     */
    public static string $title;


    /**
     * The description for the flow
     * 
     * This provides a brief overview of the flow's purpose and functionality.
     * Used for save to related post in the database.
     */
    public static string $description;

    public function __invoke()
    {
        static::init();
        add_action('init', [static::class, 'starter']);

        // add_filter('testeroid_tests', [self::class, 'tests']);

    }

    // public static function tests($tests)
    // {
    //     $tests['FlowAbstract'] = function () {
    //         // Example test logic for the flow
    //         return true;
    //     };

    //     return $tests;
    // }

    abstract public static function init();

    abstract public static function starterHandle($payload = []);

    /**
     * Starter contains recurring actions or another starter logic
     * 
     * @return void
     */
    final public static function starter()
    {
        if (empty(static::$intervalInSeconds)) {
            return;
        }

        if (! as_next_scheduled_action(static::getActionNameWithSlug())) {
            as_schedule_recurring_action(
                time(),
                static::$intervalInSeconds,
                static::getActionNameWithSlug(),
                [],
                Plugin::$slug,
                true
            );
        }

        add_action(static::getActionNameWithSlug(), [static::class, 'starterHandle']);

        if (method_exists(static::class, 'getConfig')) {

            /**
             * @var array $config = [
             *     'schedule' => int, // in seconds
             *     'ability' => string, // ability slug
             *     'slug' => string, // flow slug
             *     'input' => [],
             *  ]
             */
            $config = static::getConfig();

            $schedule = $config['schedule'] ?? 0;

            if (intval($schedule) > 0) {
                if (! as_next_scheduled_action(static::getActionNameWithSlug('trigger'))) {
                    as_schedule_recurring_action(
                        time(),
                        $schedule,
                        static::getActionNameWithSlug('trigger'),
                        $config,
                        Plugin::$slug,
                        true
                    );
                }
            }

        }
        add_action(static::getActionNameWithSlug('trigger'), [self::class, 'trigger']);
        add_action(static::getActionNameWithSlug('handle'), [self::class, 'handle']);
    }


    /**
     * trigger runs actions according config of Flow
     * 
     * @return void
     */
    public static function trigger($payload = [])
    {
        try {
            if (empty($payload)) {
                $payload = [];
            }

            if (isset($ability)) {
                $payload['ability'] = $ability;
            }

            if (isset($input)) {
                $payload['input'] = $input;
            }

            static::scheduleSingleAction('handle', $payload);

        } catch (\Throwable $e) {
            $context = [
                'payload' => $payload ?? null,
                'backtrace' => $e->getTraceAsString(),
            ];
            static::log('Error: '.$e->getMessage(), $context);
        }
    }

    public static function getConfig()
    {
        return [];
    }

    final static public function handle($payload = [])
    {
        try {
            $ability = wp_get_ability($payload['ability'] ?? null);
            if (empty($ability) || is_wp_error($ability)) {
                throw new Exception("Ability not found: $ability");
            }

            $input = $payload['input'] ?? null;

            $output = $ability->execute($input);

            if (is_wp_error($output)) {
                throw new Exception('Error executing ability: '.$output->get_error_message());
            }

            if (isset($output['ability']) && is_string($output['ability'])) {
                $newActionPayload = ['ability' => $output['ability']];
                if (isset($output['input'])) {
                    $newActionPayload['input'] = $output['input'];
                }
                self::scheduleSingleAction('handle', $newActionPayload);
            }

            self::log('Content agent flow completed.', [
                'payload' => $payload,
                'output' => $output]
            );

        } catch (\Throwable $e) {
            $context = [
                'payload' => $payload ?? null,
                'backtrace' => $e->getTraceAsString(),
            ];
            self::log("Error: {$e->getMessage()}", $context);
        }
    }


    public static function requestWebhook($webhook, $payload, $headers = [])
    {
        $response = wp_remote_post($webhook, [
            'method' => 'POST',
            'body' => json_encode($payload),
            'headers' => array_merge([
                'Content-Type' => 'application/json',
            ], $headers),
        ]);

        return $response;
    }

    public static function addWebhookForREST($endpointKey, $callback, $bearerToken)
    {
        //example rest api url /wp-json/FlowProcess/Webhook
        add_action('rest_api_init', function () use ($endpointKey, $callback, $bearerToken) {
            register_rest_route('FlowProcess', '/'.$endpointKey, [
                'methods' => 'POST',
                'callback' => function (WP_REST_Request $request) use ($callback, $bearerToken) {
                    //check $bearerToken
                    if ($request->get_header('Authorization') !== 'Bearer '.$bearerToken) {
                        return new WP_Error('rest_forbidden', __('You do not have permission to access this resource.'), ['status' => 403]);
                    }
                    $callback($request, $bearerToken);
                },
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public static function getUrlWebhookForREST($endpointKey)
    {
        return rest_url('FlowProcess/'.$endpointKey);
    }

    public static function scheduleSingleAction($key, $payload = [])
    {
        as_schedule_single_action(
            time(),
            self::getActionNameWithSlug($key),
            [$payload],
            Plugin::$slug,
            false
        );
    }

    // todo refactoring or remove
    public static function scheduleAction($payload = [])
    {
        as_schedule_single_action(time(), static::getActionNameWithSlug(), [$payload], Plugin::$slug, true);
    }

    public static function getActionNameWithSlug($key = '')
    {
        if (empty($key)) {
            return Plugin::$slug.'/'.static::$slug;
        }

        return Plugin::$slug.'/'.static::$slug.'/'.$key;
    }

    public static function log($message, $context = [], $parent_flow_log_id = null): int
    {
        $flow_id = self::getRelatedFlowId();
        if (empty($flow_id)) {
            wc_get_logger()->info($message, array_merge(['source' => static::getActionNameWithSlug()], $context));
            return 0;
        } else {
            $content = $message.PHP_EOL.print_r($context, true);
            $comment = [
                'comment_post_ID' => $flow_id,
                'comment_author' => 'Flow Logger',
                'comment_content' => $content,
                'comment_type' => 'flow_log',
                'comment_approved' => 1,
            ];
            if (! empty($parent_flow_log_id)) {
                $comment['comment_parent'] = $parent_flow_log_id;
            }
            return wp_insert_comment($comment);
        }
    }

    private static function getRelatedFlowId()
    {
        $slug = static::$slug;
        $post = get_page_by_path($slug, OBJECT, 'flow');
        if ($post) {
            return $post->ID;
        }

        return null;
    }

}