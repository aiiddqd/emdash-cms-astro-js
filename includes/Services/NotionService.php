<?php

namespace ProcessFlows\Services;

use Notion\Notion;
use Notion\Databases\Query;
use Notion\Databases\Query\CompoundFilter;
use Notion\Databases\Query\DateFilter;
use Notion\Databases\Query\Sort;
use Notion\Databases\Query\StatusFilter;
use Notion\Databases\Properties\StatusOption;
use Notion\Pages\Properties\Status;
use Notion\Comments\Comment;
use Notion\Common\RichText;
use Notion\Pages\Page;
use Notion\Configuration;

// NotionService::init();

/**
 * Notion API integration service
 * 
 * @link https://mariosimao.github.io/notion-sdk-php/getting-started.html
 */
class NotionService
{
    public static string $token = '';
    public static Notion $notion;

    public static function init()
    {
        add_action('admin_init', [self::class, 'add_settings']);
    }

    public function __construct($token = null)
    {
        if ($token) {
            self::$token = $token;
        } else {
            self::$token = Plugin::getConfig('notion_api_key');
        }

        // if empty self::$token
        if (empty(self::$token)) {
            throw new \Exception('Notion API token is not set.');
        }

        self::$notion = Notion::create(self::$token);

        // $config = Configuration::create(self::$token)
            // ->enableRetryOnConflict(2);

        // $notion = Notion::createFromConfig($config);
    }

    /**
     * Retrieve a Notion page by its ID.
     * 
     * @param string $pageId
     * @return Page|null
     */
    public static function getPageById($pageId): Page|null
    {
        return self::$notion->pages()->find($pageId);
    }

    /**
     * Get the first page from a database by status.
     */
    public static function getFirstPageByStatusFromDatabase($databaseId, $status): Page|null
    {
        $database = self::$notion->databases()->find($databaseId);
        $query = Query::create()
            ->changeFilter(
                CompoundFilter::and(
                    StatusFilter::property("Status")->equals($status)
                )
            )
            // ->addSort(Sort::property("Status"))
            ->changePageSize(1);
        $result = self::$notion->databases()->query($database, $query);

        return $result->pages[0] ?? null;
    }

    public static function addCommentToPage($pageId, $comment)
    {
        // Create the comment content as rich text
        $richText = RichText::fromString($comment);

        // Create a Comment object
        $comment = Comment::create($pageId, $richText);

        // Add the comment to the page
        return self::$notion->comments()->create($comment);
    }

    public static function changeNotionPageStatus($pageId, $newStatus, $statusField = 'Status')
    {
        $page = self::$notion->pages()->find($pageId);
        if (! $page) {
            return false;
        }

        /**
         * @var Status $status
         */
        $status = $page->getProperty('Status');
        $page = $page->addProperty('Status', $status->changeOption(StatusOption::fromName($newStatus)));
        return self::$notion->pages()->update($page);
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
    }
}
