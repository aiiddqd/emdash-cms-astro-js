<?php

namespace ProcessFlows;

AddContentTypes::init();

class AddContentTypes
{

    public static function init()
    {
        add_action('init', [self::class, 'register_post_type']);
        add_action('init', [self::class, 'register_taxonomy']);

        add_filter('acf/settings/remove_wp_meta_box', [self::class, 're_enable_custom_fields_for_flow']);

        add_action('add_meta_boxes', [self::class, 'add_meta_box']);

        add_action('process_flows_meta_box_config', [self::class, 'show_slug']);

        add_filter('get_comment_author', [self::class, 'append_comment_date_to_author'], 10, 3);

    }


    /**
     * Append the comment date to the author name for flow_log comments
     */
    public static function append_comment_date_to_author($author, $comment_ID, $comment)
    {
        if ($comment->comment_type !== 'flow_log') {
            return $author;
        }

        // Check if we're in the comments meta box AJAX call
        if (doing_action('wp_ajax_get-comments')) {
            $comment_date = get_comment_date('Y-m-d', $comment_ID);
            $comment_time = get_comment_time('H:i:s');
            $author = esc_html($comment_date).' at '.esc_html($comment_time);
        }

        return $author;
    }

    public static function show_slug($post_id)
    {
        echo '<p>'.__('Slug: ', 'process-flows').get_post_field('post_name', $post_id).'</p>';
    }

    //add meta box
    public static function add_meta_box()
    {
        add_meta_box(
            'flow_meta_box',
            __('Flow Config', 'process-flows'),
            function () {
                do_action('process_flows_meta_box_config', get_the_ID());
            },
            'flow',
            'normal',
            'high'
        );
    }

    public static function re_enable_custom_fields_for_flow($remove)
    {
        global $post_type;

        // Only return false (re-enable metabox) for 'flow' post type
        if ('flow' === $post_type) {
            return false;
        }

        // Otherwise, keep ACF's default behavior (hide metabox)
        return $remove;
    }

    public static function register_post_type()
    {
        $labels = [
            'name' => __('Flows', 'process-flows'),
            'singular_name' => __('Flow', 'process-flows'),
            'menu_name' => __('Flows', 'process-flows'),
            'name_admin_bar' => __('Flow', 'process-flows'),
            'add_new' => __('Add New', 'process-flows'),
            'add_new_item' => __('Add New Flow', 'process-flows'),
            'new_item' => __('New Flow', 'process-flows'),
            'edit_item' => __('Edit Flow', 'process-flows'),
            'view_item' => __('View Flow', 'process-flows'),
            'all_items' => __('All Flows', 'process-flows'),
            'search_items' => __('Search Flows', 'process-flows'),
            'parent_item_colon' => __('Parent Flow:', 'process-flows'),
            'not_found' => __('No flows found.', 'process-flows'),
            'not_found_in_trash' => __('No flows found in Trash.', 'process-flows'),
            'archives' => __('Flow Archives', 'process-flows'),
            'filter_items_list' => __('Filter flows list', 'process-flows'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,  // Set to false if you want it hidden from frontend
            'show_ui' => true,
            // 'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'flow'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => true,  // Like pages, if flows can have parents
            'menu_position' => 20,    // Position in admin menu
            'menu_icon' => 'dashicons-controls-repeat',  // Custom icon
            'supports' => ['title', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'page-attributes'],
        ];

        register_post_type('flow', $args);
    }
    public static function register_taxonomy()
    {
        $labels = [
            'name' => __('Flow Categories', 'process-flows'),
            'singular_name' => __('Flow Category', 'process-flows'),
            'menu_name' => __('Flow Categories', 'process-flows'),
            'all_items' => __('All Flow Categories', 'process-flows'),
            'parent_item' => __('Parent Flow Category', 'process-flows'),
            'parent_item_colon' => __('Parent Flow Category:', 'process-flows'),
            'new_item_name' => __('New Flow Category Name', 'process-flows'),
            'add_new_item' => __('Add New Flow Category', 'process-flows'),
            'edit_item' => __('Edit Flow Category', 'process-flows'),
            'update_item' => __('Update Flow Category', 'process-flows'),
            'view_item' => __('View Flow Category', 'process-flows'),
            'separate_items_with_commas' => __('Separate flow categories with commas', 'process-flows'),
            'popular_items' => __('Popular Flow Categories', 'process-flows'),
            'search_items' => __('Search Flow Categories', 'process-flows'),
            'not_found' => __('No flow categories found.', 'process-flows'),
            'no_terms' => __('No flow categories', 'process-flows'),
            'items_list' => __('Flow Categories list', 'process-flows'),
            'items_list_navigation' => __('Flow Categories list navigation', 'process-flows'),
            'back_to_items' => __('&larr; Back to Flow Categories', 'process-flows'),
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'flow-category'],
        ];

        register_taxonomy('flow_category', ['flow'], $args);
    }
}
