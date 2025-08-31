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
 * Version:     0.2.250831
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
    public static $settings_slug = 'process-flows-settings';

    public static $flows = [];

    public static function init()
    {
        //add vendor/autoload.php
        require_once plugin_dir_path(__FILE__).'vendor/autoload.php';

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


        add_filter('plugin_action_links_'.plugin_basename(__FILE__), function ($links) {
            $settings_link = admin_url('edit.php?post_type=flow&page=process-flows');
            $links[] = '<a href="'.esc_url($settings_link).'">'.__('Settings', 'process-flows').'</a>';
            return $links;
        });

        register_activation_hook(__FILE__, [self::class, 'plugin_activation']);

        register_deactivation_hook(__FILE__, [self::class, 'plugin_deactivation']);

        add_action('admin_menu', [self::class, 'add_settings_page'], 20);
        add_action('admin_init', [self::class, 'add_config_option']);
        // add_action('init', [self::class, 'load_text_domain']);

    }

    public static function add_config_option()
    {
        register_setting(Plugin::$settings_slug, 'process_flows');
    }

    public static function getConfig($key = null)
    {
        $config = get_option('process_flows', []);
        return $key ? ($config[$key] ?? null) : $config;
    }

    public static function getConfigFieldName($key = null)
    {
        return 'process_flows'.($key ? "[$key]" : '');
    }

    public static function add_settings_page()
    {
        add_submenu_page(
            'edit.php?post_type=flow',
            __('Settings', 'process-flows'),
            __('Settings', 'process-flows'),
            'manage_options',
            'process-flows',
            function () {

                // Render the settings page content
                ?>
            <div class="wrap">
                <h1><?php _e('Process Flows Settings', 'process-flows'); ?></h1>
                <form method="post" action="options.php">
                    <?php
                        settings_fields(self::$settings_slug);
                        do_settings_sections(self::$settings_slug);
                        submit_button();
                        ?>
                </form>
            </div>
            <?php
            }
        );
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
