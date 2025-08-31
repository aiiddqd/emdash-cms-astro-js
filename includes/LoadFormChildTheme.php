<?php

namespace ProcessFlows;

// load files from child theme

LoadFormChildTheme::init();

class LoadFormChildTheme
{
    public static function init()
    {
        //we should load all flow files from the child theme after plugins loaded
        add_action('plugins_loaded', function () {
            $files = glob(get_stylesheet_directory().'/flows/*.php');
            foreach ($files as $file) {
                require_once $file;
            }
        });

        add_action('current_screen', [self::class, 'load_flows_to_collection']);

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

        foreach (Plugin::$flows as $flow) {
            if ($flow instanceof FlowAbstract) {
                continue;
            }

            // $slug = $flow::$slug;

            //check post with slug $slug
            $post = get_page_by_path($flow::$slug, OBJECT, 'flow');

            if (! $post) {
                $post_id = wp_insert_post([
                    'post_title' => $flow::$title,
                    'post_excerpt' => $flow::$description,
                    'post_name' => $flow::$slug,
                    'post_type' => 'flow',
                    'post_status' => 'publish',
                ]);

                $post = get_post($post_id);
            }

            $flowData = ['class_name' => $flow];
            update_post_meta($post->ID, 'flowData', json_encode($flowData));

        }

    }
}