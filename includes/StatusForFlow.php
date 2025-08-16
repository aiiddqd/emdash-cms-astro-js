<?php

namespace ProcessFlows;

use ActionScheduler_Store;

StatusForFlow::init();
class StatusForFlow
{
    public static function init()
    {
        add_action('process_flows_meta_box_config', [self::class, 'getLatestStatusActions']);
        // add_action('process_flows_meta_box_config', [self::class, 'getLogs']);
    }


    //getLogs
    public static function getLogs($post_id)
    {
        echo '<h3>'.__('Logs', 'process-flows').'</h3>';

        $flow_slug = get_post_field('post_name', $post_id);
        // get link to log woocommerce
    }
    
    public static function getLatestStatusActions($post_id)
    {
        echo '<h3>'.__('Scheduled Actions', 'process-flows').'</h3>';

        $flow_slug = get_post_field('post_name', $post_id);

        // get Actions Scheduler by hook
        $actions = as_get_scheduled_actions([
            'hook' => Plugin::$slug.'/'.$flow_slug,
            'order' => 'DESC',
            'orderby' => 'date', // Sort by scheduled date
            'status' => ['pending', 'complete', 'failed', 'canceled'],
        ], 'ARRAY_A');

        if (empty($actions)) {
            echo '<p>'.__('No scheduled actions for this flow.', 'process-flows').'</p>';
            return;
        }

        echo '<ul>';
        foreach ($actions as $action_id => $action) {
            $store = ActionScheduler_Store::instance();
            $action = $store->fetch_action($action_id);
            $readable_date = $action->get_schedule()->get_date()->format('Y-m-d H:i:s');
            $status = $store->get_status($action_id);
            ?>
            <li>
                <details>
                    <summary>
                        <strong><?= sprintf("%s, %s, %s", $action_id, $readable_date, $status) ?></strong>
                    </summary>
                    <?= $action->get_hook() ?>
                    <pre><?= var_dump($action->get_args()); ?></pre>
                </details>
            </li>
            <?php
        }
        echo '</ul>';

        //get link to action scheduler log
        $action_scheduler_url = admin_url('admin.php?page=action-scheduler');
        $action_scheduler_url = add_query_arg([
            's' => Plugin::$slug.'/'.$flow_slug,
            'orderby' => 'schedule',
            'order' => 'desc',
        ], $action_scheduler_url);

        echo '<p><a href="'.esc_url($action_scheduler_url).'">'.__('View all scheduled actions', 'process-flows').'</a></p>';

        $log_url = admin_url('admin.php?page=wc-status&tab=logs');
        echo '<p><a href="'.esc_url($log_url).'">'.__('View logs', 'process-flows').'</a></p>';

    }
}