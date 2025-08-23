<?php

namespace ProcessFlows;

abstract class FlowAbstract
{
    abstract public static function init();

    public function __invoke() {
        static::init();
        add_action(static::getActionNameWithSlug(), [static::class, 'handleAction']);
    }

    abstract public static function handleAction(array $payload);

    public static function scheduleAction($payload = [])
    {
        as_schedule_single_action(time(), static::getActionNameWithSlug(), [$payload], Plugin::$slug, true);
    }

    /**
     * Get the slug for the flow
     */
    abstract public static function getSlug(): string;


    public static function getActionNameWithSlug(){
        return Plugin::$slug . '/' . static::getSlug();
    }

    public static function log($message, $flow_id = null, $context = [])
    {
        if(empty($flow_id)){
            wc_get_logger()->info($message, array_merge(['source' => static::getActionNameWithSlug()], $context));
        } else {
            //todo add log to comment if flow_id exists
            $post = get_post($flow_id);
            if($post){
                $comment = [
                    'comment_post_ID' => $post->ID,
                    'comment_author' => 'Flow Logger',
                    'comment_content' => $message,
                    'comment_type' => 'flow_log',
                    'comment_approved' => 1,
                ];
                wp_insert_comment($comment);
            } else {
                wc_get_logger()->info($message, array_merge(['source' => static::getActionNameWithSlug()], $context));
            }
        }
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