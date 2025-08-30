<?php

namespace ProcessFlows;

use Notion\Databases\Properties\StatusOption;
use Notion\Notion;
use Notion\Databases\Query;
use Notion\Databases\Query\CompoundFilter;
use Notion\Databases\Query\DateFilter;
use Notion\Databases\Query\Sort;
use Notion\Databases\Query\StatusFilter;

NotionService::init();
class NotionService
{
    public static function init()
    {
        // add settings to Notion API integration Plugin::$settings_slug

        add_action('admin_init', [self::class, 'add_settings']);
    }

    public static function add_settings()
    {
        add_settings_section(
            'notion_integration',
            __('Notion Integration', 'process-flows'),
            function () {
                echo '<p>'.__('Configure Notion API integration settings.', 'process-flows').'</p>';
            },
            Plugin::$settings_slug
        );

        add_settings_field(
            'notion_api_key',
            __('Notion API Key', 'process-flows'),
            function () {
                $value = Plugin::getConfig('notion_api_key');
                echo '<input type="text" name="'.Plugin::getConfigFieldName('notion_api_key').'" value="'.esc_attr($value).'" />';
            },
            Plugin::$settings_slug,
            'notion_integration'
        );

        register_setting(Plugin::$settings_slug, 'notion_api_key');
    }
}
