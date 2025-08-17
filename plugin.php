<?php
/**
 * Plugin Name: @ Process Flows
 * Plugin URI:  https://github.com/aiiddqd/process-flows
 * Description: Workflow automation for WordPress.
 * Author:      AI
 * Author URI:  https://github.com/aiiddqd/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins:  woocommerce
 * Text Domain: process-flows
 * Version:     0.1.250808
 */

namespace ProcessFlows;

// Prevent direct access to the file
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

Plugin::init();

class Plugin
{

    public static $slug = 'process-flows';
    public static $flows = [];

    public static function init()
    {
        //load php files from subfolder includes
        $files = glob(plugin_dir_path(__FILE__).'includes/*.php');
        foreach ($files as $file) {
            require_once $file;
        }

        self::$flows = apply_filters('processflow_flows', []);
        foreach (self::$flows as $flowClass) {
            $flow = new $flowClass();
            if (is_callable($flow)) {
                $flow();
            }
        }

        add_action('admin_init', function () {
            if (isset($_GET['test_processflow_recurring_starters'])) {
                do_action('processflow_recurring_starters');
                exit;
            }
        });


        add_action('admin_init', function () {
            if (! as_has_scheduled_action('processflow_recurring_starters')) {
                as_schedule_recurring_action(
                    time(),
                    HOUR_IN_SECONDS, // every 24 hours
                    'processflow_recurring_starters',
                    [],
                    self::$slug,
                    true
                );
            }
        });

        add_action('processflow_recurring_starters', [self::class, 'starters']);

        register_activation_hook(__FILE__, [self::class, 'plugin_activation']);

        register_deactivation_hook(__FILE__, [self::class, 'plugin_deactivation']);

        add_action('current_screen', [self::class, 'load_flows_to_collection']);

        // add_action('init', [self::class, 'load_text_domain']);

    }


    /**
     * Load flows to collection in console
     *
     * @param mixed $screen
     * @return void
     */
    public static function load_flows_to_collection($screen)
    {
        global $pagenow;

        if ($pagenow != 'edit.php') {
            return;
        }

        $post_type = $screen->post_type ?? null;

        if ($post_type != 'flow') {
            return;
        }

        foreach (self::$flows as $flow) {
            if ($flow instanceof FlowInterface) {
                continue;
            }

            $slug = $flow::getSlug();

            //check post with slug $slug
            $post = get_page_by_path($slug, OBJECT, 'flow');

            if (! $post) {
                $post_id = wp_insert_post([
                    'post_title' => $flow::getTitle(),
                    'post_excerpt' => $flow::getDescription(),
                    'post_name' => $slug,
                    'post_type' => 'flow',
                    'post_status' => 'publish',
                ]);

                $post = get_post($post_id);
            }

            $flowData = ['class_name' => $flow];
            update_post_meta($post->ID, 'flowData', json_encode($flowData));

        }

    }

    public static function starters()
    {
        try {
            foreach (self::$flows as $starter) {
                $starter::prepareActions();
            }
        } catch (\Exception $e) {
            wc_get_logger()->error($e->getMessage(), [
                'flows' => self::$flows,
                'source' => self::$slug,
            ]);
        }
    }

    public static function load_text_domain()
    {
        load_plugin_textdomain(
            'process-flows', // Text domain
            false, // No deprecated folder
            plugin_basename(dirname(__FILE__).'/languages') // Path to languages folder
        );
    }

    // Your plugin code goes here
    public static function plugin_activation()
    {
        // Code to run on activation
    }


    public static function plugin_deactivation()
    {
        // Code to run on deactivation
    }
}
