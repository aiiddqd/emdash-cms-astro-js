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

        add_action('init', function () {
            self::$flows = apply_filters('processflow_flows', []);
            // dd(self::$flows);
            foreach (self::$flows as $flowClass) {
                $flow = new $flowClass();
                if (is_callable($flow)) {
                    $flow();
                }
            }
        }, 5);

        register_activation_hook(__FILE__, [self::class, 'plugin_activation']);

        register_deactivation_hook(__FILE__, [self::class, 'plugin_deactivation']);


        // add_action('init', [self::class, 'load_text_domain']);

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
