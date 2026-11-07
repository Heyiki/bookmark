<?php

/**
 * 简易书签接口
 *
 * 利用notion api + php + vercel + github 制作
 *
 * notion开发文档
 * https://developers.notion.com/reference
 *
 * vercel
 * https://vercel.com/
 *
 */


declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

final class Index
{
    private string $token;
    private string $databaseId;
    private string $method;
    private string $pageId;
    private string $title;
    private string $url;
    private string $tab;
    private int $pageSize = 20;
    private string $page;
    private string $titleFilter;
    private string $urlFilter;
    private string $tabFilter;

    public function __construct()
    {
        $this->token = (string)($_ENV['NOTION_TOKEN'] ?? getenv('NOTION_TOKEN') ?: '');
        $this->databaseId = (string)($_ENV['DATABASE_ID'] ?? getenv('DATABASE_ID') ?: '');

        if ($this->token === '' || $this->databaseId === '') {
            $this->retJson([], 'NOTION_TOKEN and DATABASE_ID cannot be empty', 500);
        }

        $this->method = $this->getParam('m');
        $this->pageId = $this->getParam('pid');
        $this->title = $this->getParam('t');
        $this->url = $this->getParam('u');
        $this->tab = $this->getParam('tb');

        $this->pageSize = max(1, min(100, (int)($this->getParam('s') ?: 20)));

        $this->page = $this->getParam('p');
        $this->titleFilter = $this->getParam('tf');
        $this->urlFilter = $this->getParam('uf');
        $this->tabFilter = $this->getParam('tbf');
    }

    public function handle(): void
    {
        if (!method_exists($this, $this->method)) {
            $this->retJson([], 'Method does not exist', 400);
        }

        // 仅允许公开的接口方法，防止调用内部方法
        $allowedMethods = ['rows', 'detail', 'create', 'edit', 'delete'];

        if (!in_array($this->method, $allowedMethods, true)) {
            $this->retJson([], 'Method not allowed', 400);
        }

        $this->{$this->method}();
    }

    /**
     * 获取请求参数
     */
    private function getParam(string $param): string
    {
        $value = $_REQUEST[$param] ?? '';

        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * 列表
     */
    public function rows(): void
    {
        $params = $this->filterCriteria();

        $res = $this->curlSend(
            'https://api.notion.com/v1/databases/' . rawurlencode($this->databaseId) . '/query',
            $params
        );

        $list = [];

        foreach (($res['results'] ?? []) as $item) {
            $properties = $item['properties'] ?? [];

            $list[] = [
                'id' => $item['id'] ?? '',
                'tab' => $properties['tab']['rich_text'][0]['plain_text'] ?? '',
                'title' => $properties['title']['title'][0]['plain_text'] ?? '',
                'url' => $properties['url']['url'] ?? '',
            ];
        }

        $this->retJson([
            'next_cursor' => $res['next_cursor'] ?? '',
            'list' => $list,
        ]);
    }

    /**
     * 列表筛选条件
     */
    private function filterCriteria(): array
    {
        $params = [
            'page_size' => $this->pageSize,
        ];

        if ($this->page !== '') {
            $params['start_cursor'] = $this->page;
        }

        $filter = [];

        if ($this->titleFilter !== '') {
            $filter[] = [
                'property' => 'title',
                'title' => [
                    'contains' => $this->titleFilter,
                ],
            ];
        }

        if ($this->urlFilter !== '') {
            $filter[] = [
                'property' => 'url',
                'url' => [
                    'contains' => $this->urlFilter,
                ],
            ];
        }

        if ($this->tabFilter !== '') {
            $filter[] = [
                'property' => 'tab',
                'rich_text' => [
                    'contains' => $this->tabFilter,
                ],
            ];
        }

        if ($filter !== []) {
            $params['filter'] = [
                'and' => $filter,
            ];
        }

        return $params;
    }

    /**
     * 详情
     */
    public function detail(): void
    {
        if ($this->pageId === '') {
            $this->retJson([], 'Unknown object', 400);
        }

        $preview = $this->curlSend(
            'https://api.notion.com/v1/pages/' . rawurlencode($this->pageId),
            [],
            'GET'
        );

        $properties = $preview['properties'] ?? [];

        if ($properties === []) {
            $this->retJson([], 'Unknown object', 400);
        }

        $this->retJson([
            'id' => $this->pageId,
            'title' => $properties['title']['title'][0]['plain_text'] ?? '',
            'url' => $properties['url']['url'] ?? '',
            'tab' => $properties['tab']['rich_text'][0]['plain_text'] ?? '',
        ]);
    }

    /**
     * 创建
     */
    public function create(): void
    {
        if ($this->url === '') {
            $this->retJson([], 'Link cannot be empty', 400);
        }

        $this->title = $this->title !== '' ? $this->title : $this->url;
        $this->tab = $this->tab !== '' ? $this->tab : '公共';

        $data = [
            'parent' => [
                'type' => 'database_id',
                'database_id' => $this->databaseId,
            ],
            'properties' => $this->structure(),
        ];

        $res = $this->curlSend(
            'https://api.notion.com/v1/pages',
            $data
        );

        $this->retJson($res, 'Created successfully');
    }

    /**
     * 获取创建的数据结构
     */
    private function structure(): array
    {
        return [
            'tab' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $this->tab,
                        ],
                    ],
                ],
            ],
            'url' => [
                'url' => $this->url,
            ],
            'title' => [
                'title' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $this->title,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 编辑
     */
    public function edit(): void
    {
        if ($this->pageId === '') {
            $this->retJson([], 'Unknown object', 400);
        }

        $preview = $this->curlSend(
            'https://api.notion.com/v1/pages/' . rawurlencode($this->pageId),
            [],
            'GET'
        );

        $properties = $preview['properties'] ?? [];

        if ($properties === []) {
            $this->retJson([], 'Unknown object', 400);
        }

        $updateProperties = [];

        if ($this->tab !== '') {
            $updateProperties['tab'] = [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $this->tab,
                        ],
                    ],
                ],
            ];
        }

        if ($this->title !== '') {
            $updateProperties['title'] = [
                'title' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $this->title,
                        ],
                    ],
                ],
            ];
        }

        if ($this->url !== '') {
            $updateProperties['url'] = [
                'url' => $this->url,
            ];
        }

        if ($updateProperties === []) {
            $this->retJson([], 'No fields to update', 400);
        }

        $res = $this->curlSend(
            'https://api.notion.com/v1/pages/' . rawurlencode($this->pageId),
            ['properties' => $updateProperties],
            'PATCH'
        );

        $this->retJson($res, 'Updated successfully');
    }

    /**
     * 删除
     */
    public function delete(): void
    {
        if ($this->pageId === '') {
            $this->retJson([], 'Unknown object', 400);
        }

        $res = $this->curlSend(
            'https://api.notion.com/v1/blocks/' . rawurlencode($this->pageId),
            [],
            'DELETE'
        );

        $this->retJson($res, 'Deleted successfully');
    }

    /**
     * 请求 Notion API
     */
    private function curlSend(
        string $url,
        array $data = [],
        string $method = 'POST'
    ): array {
        $curl = curl_init();

        if ($curl === false) {
            $this->retJson([], 'Failed to initialize cURL', 500);
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Notion-Version: 2022-06-28',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ];

        if ($data !== []) {
            $json = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );

            $options[CURLOPT_POSTFIELDS] = $json;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($response === false) {
            $this->retJson([], 'cURL error: ' . $error, 502);
        }

        try {
            $result = json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->retJson([], 'Invalid JSON response from Notion', 502);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $result['message'] ?? 'Notion API request failed';

            $this->retJson(
                $result,
                (string)$message,
                $httpCode >= 400 && $httpCode <= 599 ? $httpCode : 502
            );
        }

        return is_array($result) ? $result : [];
    }

    /**
     * 返回 JSON
     */
    private function retJson(
        array $data = [],
        string $msg = 'ok',
        int $code = 200
    ): never {
        http_response_code($code);

        echo json_encode(
            [
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
            ],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        exit;
    }
}

(new Index())->handle();
