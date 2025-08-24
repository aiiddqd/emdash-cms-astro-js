<?php

namespace ProcessFlows;

use WP_REST_Request, WP_Error;

abstract class FlowAbstract
{
    protected static ?int $intervalInSeconds = null;

    abstract public static function init();

    public function __invoke()
    {
        static::init();
        add_action('init', [static::class, 'starter']);
    }

    /**
     * Starter contains recurring actions or another starter logic
     * 
     * @return void
     */
    public static function starter()
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


    }


    public static function requestWebhook($webhook, $payload, $headers = []){
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
            register_rest_route('FlowProcess', '/' . $endpointKey, [
                'methods' => 'POST',
                'callback' => function (WP_REST_Request $request) use ($callback, $bearerToken) {
                    //check $bearerToken
                    if ($request->get_header('Authorization') !== 'Bearer ' . $bearerToken) {
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
        return rest_url('FlowProcess/' . $endpointKey);
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

    /**
     * Get the slug for the flow
     */
    abstract public static function getSlug(): string;


    //used in includes/StatusForFlow.php#L26
    public static function getActionNameWithSlug($key = '')
    {
        if (empty($key)) {
            return Plugin::$slug.'/'.static::getSlug();
        }

        return Plugin::$slug.'/'.static::getSlug().'/'.$key;
    }

    public static function log($message, $parent_flow_log_id = null, $context = []): int
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
        $slug = static::getSlug();
        $post = get_page_by_path($slug, OBJECT, 'flow');
        if ($post) {
            return $post->ID;
        }

        return null;
    }

    public static function dd($data, $cli = true)
    {
        if ($cli && class_exists('WP_CLI')) {
            \WP_CLI::line(print_r($data, true));
        } else {
            var_dump($data);
        }
    }
}