<?php

namespace ProcessFlows\Services;

use ProcessFlows;
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

NotionService::init();

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
            self::$token = ProcessFlows\Plugin::getConfig('notion_api_key');
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

    public static function getClient(): Notion
    {
        return self::$notion;
    }

    /**
     * Send a request to the Notion API.
     *
     * @param string $route
     * @param array $args
     * @return array|\WP_Error
     */
    public static function request($route, $args = [])
    {
        $url = "https://api.notion.com/v1/$route";
        $args = wp_parse_args($args, [
            'headers' => [
                'Authorization' => 'Bearer '.self::$token,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ],
            'body' => $args['body'] ?? [],
            'method' => 'POST',
            'data_format' => 'body',
            'timeout' => 15,
        ]);

        if (empty($args['body'])) {
            unset($args['body']);
        } else {
            if (isset($args['headers']['Content-Type']) && $args['headers']['Content-Type'] === 'application/json') {
                $args['body'] = wp_json_encode($args['body'] ?? []);
            }
        }

        $response = wp_remote_request($url, $args);
        if (! empty($args['raw'])) {
            return $response;
        }

        $data = wp_remote_retrieve_body($response);
        return json_decode($data, true);
    }

    public static function getContent($pageId)
    {
        $page = self::request("blocks/$pageId/children", ['method' => 'GET']);
        
        //get value from array $page from all elements with key = plain_text - recursively
        // collect all 'plain_text' values recursively
        $plain_texts = [];
        $collect_plain_texts = function ($node) use (&$collect_plain_texts, &$plain_texts) {
            if (is_array($node)) {
                foreach ($node as $key => $value) {
                    if ($key === 'plain_text') {
                        $plain_texts[] = (string) $value;
                    }
                    if (is_array($value) || is_object($value)) {
                        $collect_plain_texts($value);
                    }
                }
            } elseif (is_object($node)) {
                foreach (get_object_vars($node) as $key => $value) {
                    if ($key === 'plain_text') {
                        $plain_texts[] = (string) $value;
                    }
                    if (is_array($value) || is_object($value)) {
                        $collect_plain_texts($value);
                    }
                }
            }
        };

        $collect_plain_texts($page);

        // single string with paragraphs separated by blank line
        $contentPlainText = trim(implode("\n\n", array_filter(array_map('trim', $plain_texts))));
        return $contentPlainText;
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


    //getQuery
    public static function getQuery(): Query
    {
        return Query::create();
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
            ->addSort(Sort::property("Status"))
            // ->addSort(Sort::property("LastEditedTime")->descending())
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
            ProcessFlows\Plugin::$settings_slug
        );

        add_settings_field(
            'notion_api_key',
            __('Notion API Key', 'process-flows'),
            function () {
                $value = ProcessFlows\Plugin::getConfig('notion_api_key');
                echo '<input type="text" name="'.ProcessFlows\Plugin::getConfigFieldName('notion_api_key').'" value="'.esc_attr($value).'" />';
            },
            ProcessFlows\Plugin::$settings_slug,
            'notion_integration'
        );
    }
}
