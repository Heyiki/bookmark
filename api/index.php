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

header("Access-Control-Allow-Origin: *");

class Index
{
    # notion令牌
    private string $token;
    # notion数据库id
    private string $databaseId;
    # 方法名
    private string $method;
    # 列表id
    private string $pageId;
    # 标题
    private string $title;
    # 链接
    private string $url;
    # 标签
    private string $tab;
    # 分页数
    private int $pageSize = 20;
    # 页码
    private string $page;
    # 筛选标题
    private string $titleFilter;
    # 筛选链接
    private string $urlFilter;
    # 筛选标签
    private string $tabFilter;

    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $this->token = $_ENV['NOTION_TOKEN'] ?? '';
        $this->databaseId = $_ENV['DATABASE_ID'] ?? '';
        if (empty($this->token) && empty($this->databaseId)) {
            $this->retJson([],"NOTION_TOKEN and DATABASE_ID cannot be empty",400);
        }
        $this->method = $this->getParam('m');
        $this->pageId = $this->getParam('pid');
        $this->title = $this->getParam('t');
        $this->url = $this->getParam('u');
        $this->tab = $this->getParam('tb');
        $this->pageSize = (int)$this->getParam('s') ?? 20;
        $this->page = $this->getParam('p');
        $this->titleFilter = $this->getParam('tf');
        $this->urlFilter = $this->getParam('uf');
        $this->tabFilter = $this->getParam('tbf');
    }

    public function handle()
    {
        # 调用指定的方法
        return method_exists($this, $this->method) ? call_user_func([$this, $this->method]) : $this->retJson([],"{$this->method} is not exist",400);
    }

    # 防sql注入
    private function getParam($param)
    {
        return !empty($_REQUEST[$param]) ? htmlentities(urldecode($_REQUEST[$param]), ENT_QUOTES, 'UTF-8') : '';
    }

    # 列表
    public function rows()
    {
        $params = $this->filterCriteria();
        $res = $this->curlSend('https://api.notion.com/v1/databases/'.$this->databaseId.'/query',$params);
        $list = [];
        if (!empty($res['results'])) {
            $data = $res['results'];
            foreach ($data as $v) {
                $list[] = [
                    'id'=>$v['id'],
                    'tab'=>$v['properties']['tab']['rich_text'][0]['text']['content'],
                    'title'=>$v['properties']['title']['title'][0]['text']['content'],
                    'url'=>$v['properties']['url']['url'],
                ];
            }
        }
        $this->retJson(
            [
                'next_cursor'=>$res['next_cursor'] ?? '',
                'list'=>$list,
            ]
        );
    }

    # 列表筛选条件
    private function filterCriteria()
    {
        $params['page_size'] = $this->pageSize;
        if($this->page) {
            $params['start_cursor'] = $this->page;
        }
        if($this->titleFilter || $this->urlFilter || $this->tabFilter) {
            $filter = [];
            if($this->titleFilter){
                $filter[] = [
                    "property"=> "title",
                    "title"=> [
                        "contains"=> $this->titleFilter
                    ]
                ];
            }
            if($this->urlFilter){
                $filter[] = [
                    "property"=> "url",
                    "url"=> [
                        "contains"=> $this->urlFilter
                    ]
                ];
            }
            if($this->tabFilter){
                $filter[] = [
                    "property"=> "tab",
                    "rich_text"=> [
                        "contains"=> $this->tabFilter
                    ]
                ];
            }
            $params['filter'] = ["and" => $filter];
        }
        return $params;
    }

    # 详情
    public function detail()
    {
        if (empty($this->pageId)) $this->retJson([],'Unknown object',400);
        $preview = $this->curlSend('https://api.notion.com/v1/pages/'.$this->pageId,[],'GET');
        $properties = $preview['properties'] ?? [];
        if (empty($properties)) $this->retJson([],'Unknown object',400);
        $this->retJson([
            'id'=>$this->pageId,
            'title'=>$properties['title']['title'][0]['text']['content'],
            'url'=>$properties['url']['url'],
            'tab'=>$properties['tab']['rich_text'][0]['text']['content'],
        ]);
    }

    # 创建
    public function create()
    {
        if(empty($this->url)) $this->retJson([],'Link cannot be empty',400);
        $this->title = $this->title ?? $this->url;
        $this->tab = $this->tab ?? '公共';
        $data = [
            'parent'=>[
                'type' => 'database_id',
                'database_id' => $this->databaseId
            ],
            'properties'=>$this->structure(),
        ];
        $res = $this->curlSend('https://api.notion.com/v1/pages',$data);
        $this->retJson($res,'Created successfully');
    }

    # 获取创建的结构
    private function structure()
    {
        $properties = [
            'tab'=>[
                'id'=>'Tn%60H',
                'type'=>'rich_text',
                'rich_text'=>[
                    [
                        'type'=>'text',
                        'text'=>[
                            'content'=>'#tab',
                            'link'=>null
                        ],
                        'annotations' => [
                            'bold' => false,
                            'italic' => false,
                            'strikethrough' => false,
                            'underline' => false,
                            'code' => false,
                            'color' => 'default'
                        ],
                        'plain_text' => '#tab',
                        'href'=>null,
                    ]
                ]
            ],
            'url' => [
                'id' => 'ceaO',
                'type' => 'url',
                'url' => '#url',
            ],
            'title' => [
                'id' => 'title',
                'type' => 'title',
                'title' => [
                    [
                        'type'=>'text',
                        'text'=>[
                            'content'=>'#title',
                            'link'=>null
                        ],
                        'annotations' => [
                            'bold' => false,
                            'italic' => false,
                            'strikethrough' => false,
                            'underline' => false,
                            'code' => false,
                            'color' => 'default'
                        ],
                        'plain_text' => '#title',
                        'href'=>null,
                    ]
                ],
            ]
        ];
        return json_decode(str_replace(['#title','#url','#tab'],[$this->title,$this->url,$this->tab],json_encode($properties)),true);
    }

    # 编辑
    public function edit()
    {
        if (empty($this->pageId)) $this->retJson([],'Unknown object',400);
        $preview = $this->curlSend('https://api.notion.com/v1/pages/'.$this->pageId,[],'GET');
        $properties = $preview['properties'] ?? [];
        if (empty($properties)) $this->retJson([],'Unknown object',400);
        if (!empty($this->tab)) {
            $properties['tab']['rich_text'][0]['text']['content'] = $this->tab;
            $properties['tab']['rich_text'][0]['plain_text'] = $this->tab;
        }
        if (!empty($this->title)) {
            $properties['title']['title'][0]['text']['content'] = $this->title;
            $properties['title']['title'][0]['plain_text'] = $this->title;
        }
        if (!empty($this->url)) {
            $properties['url']['url'] = $this->url;
        }
        $res = $this->curlSend('https://api.notion.com/v1/pages/'.$this->pageId,['properties'=>$properties],'PATCH');
        $this->retJson($res,'Updated successfully');
    }

    # 删除
    public function delete()
    {
        $res = $this->curlSend('https://api.notion.com/v1/blocks/'.$this->pageId,[],'DELETE');
        $this->retJson($res,'Deleted successfully');
    }

    private function curlSend($url = '', $data = [], $method = 'POST')
    {
        $curl = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Notion-Version: 2022-06-28',
                'accept: application/json',
                'authorization: Bearer ' . $this->token,
                'content-type: application/json'
            ],
        ];
        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->retJson([],$err,400);
        } else {
            return json_decode(htmlspecialchars_decode($response),true);
        }
    }

    # 返回json
    private function retJson($data = [], $msg = 'ok', $code = 200)
    {
        exit(json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data]));
    }
}

print_r((new Index())->handle());
