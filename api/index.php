<?php

/**
 * 简单浏览器书签
 *
 * 利用notion api + php + vercel + github 制作
 *
 * notion开发文档
 * https://developers.notion.com/reference
 *
 * vercel
 * https://vercel.com/
 *
 * @author Heyiki
 * @github https://github.com/Heyiki
 */

var_dump($_SERVER);

/*class Index
{
    # notion令牌
    private $token;
    # notion数据库id
    private $databaseId;
    # 方法名
    private $method;
    # 列表id
    private $pageId;
    # 参数
    private $value = [
        'tab' => '',//标签
        'title' => '',//标题
        'url' => '',//链接
    ];
    # 分页数
    private $pageSize = 10;
    # 页码
    private $page;

    public function __construct()
    {
        var_dump($_SERVER);die;
        $this->token = !empty($_ENV['NOTION_TOKEN']) ? $_ENV['NOTION_TOKEN'] : '';
        $this->databaseId = !empty($_ENV['DATABASE_ID']) ? $_ENV['DATABASE_ID'] : '';
        if (empty($this->token) && empty($this->databaseId)) {
            return $this->retJson([],"No Access",403);
        }

        $this->method = !empty($_REQUEST['m']) ? $_REQUEST['m'] : '';
        $this->pageId = !empty($_REQUEST['pid']) ? $_REQUEST['pid'] : '';
        $this->value['title'] = !empty($_REQUEST['t']) ? $_REQUEST['t'] : '';
        $this->value['url'] = !empty($_REQUEST['u']) ? $_REQUEST['u'] : '';
        $this->value['tab'] = !empty($_REQUEST['tb']) ? $_REQUEST['tb'] : '';
        $this->pageSize = !empty($_REQUEST['s']) ? (int)$_REQUEST['s'] : 10;
        $this->page = !empty($_REQUEST['p']) ? $_REQUEST['p'] : '';
        }
    }

    public function handle()
    {
        switch($this->method) {
            case 'list':
                $this->getList();
                break;
            case 'create':
                $this->create();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'edition':
                phpinfo();
                break;
            default:
                echo "{$this->method} is not exist";
                break;
        }
    }

    # 列表
    public function getList()
    {
        $params['page_size'] = $this->pageSize;
        if($this->page) {
            $params['start_cursor'] = $this->page;
        }
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
        return $this->retJson(
            [
                'next_cursor'=>!empty($res['next_cursor']) ? $res['next_cursor'] : '',
                'list'=>$list,
            ]
        );
    }

    # 创建
    public function create()
    {
        list($tab,$title,$url) = $this->value;
        if(empty($url)) $this->retJson([],'Link cannot be empty',400);
        $title = !empty($title) ? $title : $url;
        $tab = !empty($tab) ? $tab : '公共';
        $data = [
            'parent'=>[
                'type' => 'database_id',
                'database_id' => $this->databaseId
            ],
            'properties'=>$this->structure($title,$url,$tab),
        ];
        $res = $this->curlSend('https://api.notion.com/v1/pages',$data);
        return $this->retJson($res,'Created successfully');
    }

    # 获取创建的结构
    private function structure($title = '', $url = '', $tab = '')
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
        return json_decode(str_replace(['#title','#url','#tab'],[$title,$url,$tab],json_encode($properties)),true);
    }

    # 编辑
    public function edit()
    {
        if (empty($this->pageId)) $this->retJson([],'Unknown object',400);
        list($tab,$title,$url) = $this->value;
        $preview = $this->curlSend('https://api.notion.com/v1/pages/'.$this->pageId,[],'GET');
        $properties = !empty($preview['properties']) ? $preview['properties'] : [];
        if (empty($properties)) $this->retJson([],'Unknown object',400);
        if (!empty($tab)) {
            $properties['tab']['rich_text'][0]['text']['content'] = $tab;
            $properties['tab']['rich_text'][0]['plain_text'] = $tab;
        }
        if (!empty($title)) {
            $properties['title']['title'][0]['text']['content'] = $title;
            $properties['title']['title'][0]['plain_text'] = $title;
        }
        if (!empty($url)) {
            $properties['url']['url'] = $url;
        }
        $res = $this->curlSend('https://api.notion.com/v1/pages/'.$this->pageId,['properties'=>$properties],'PATCH');
        return $this->retJson($res,'Updated successfully');
    }

    # 删除
    public function delete()
    {
        $res = $this->curlSend('https://api.notion.com/v1/blocks/'.$this->pageId,[],'DELETE');
        return $this->retJson($res,'Deleted successfully');
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
            return $this->retJson([],$err,400);
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

print_r((new Index())->handle());*/
