<?php


namespace app\unittest\controller;


use Elasticsearch\ClientBuilder;
use think\Controller;

class Es extends Controller
{
    public function create()
    {
        $client = ClientBuilder::create()->setHosts(['localhost:9200'])->build();
        $params = [
            'index' => 'xiaohuanxiong',
            'type' => 'xwx_book',
            'id' => '2',
            'body' => [
                'book_name' => '老师,好久不見',
                'nick_name' => '老师真棒',
                'create_time' => 1566020562,
                'last_time' => 1566020562,
                'tags' => '校园',
                'summary' => '「老師久久沒見，一開始就來這樣不會太刺激嗎?」已有未婚妻的我，正和單戀許久的老師，展開一場又一場的禁忌遊戲.',
                'end' => 1,
                'author_id' => 1,
                'author_name' => '李周元&头枕',
                'cover_url' => ''
            ]
        ];

        $response = $client->index($params);
        dump($response);
    }

    public function search()
    {
        $client = ClientBuilder::create()->setHosts(['localhost:9200'])->build();
        $params = [
            'index' => 'xiaohuanxiong',
            'type' => 'xwx_book',
            'body' => [
                'query' => [
                    'match' => [
                        'book_name' => '老师好棒'
                    ]
                ]
            ]
        ];
        $response = $client->search($params);
        dump($response);
    }
}