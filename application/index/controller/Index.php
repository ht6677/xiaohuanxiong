<?php

namespace app\index\controller;

use app\model\Author;
use think\Db;

class Index extends Base
{
    protected $bookService;

    protected function initialize()
    {
        $this->bookService = new \app\service\BookService();
    }

    public function index()
    {
        $pid = input('pid');
        if ($pid) { //如果有推广pid
            cookie('xwx_promotion', $pid); //将pid写入cookie
        }
        $banners = cache('banners_homepage');
        if (!$banners) {
            $banners = Db::query('SELECT * FROM xwx_banner WHERE id >= 
((SELECT MAX(id) FROM xwx_banner)-(SELECT MIN(id) FROM xwx_banner)) * RAND() + (SELECT MIN(id) FROM xwx_banner) LIMIT 5');
            cache('banners_homepage', $banners, null, 'redis');
        }

        $hot_books = cache('hot_books');
        if (!$hot_books) {
            $hot_books = $this->bookService->getHotBooks();
            cache('hot_books', $hot_books, null, 'redis');
        }

        $newest = cache('newest_homepage');
        if (!$newest) {
            $newest = $this->bookService->getBooks('last_time', '1=1', 14);
            cache('newest_homepage', $newest, null, 'redis');
        }

        $ends = cache('ends_homepage');
        if (!$ends) {
            $ends = $this->bookService->getBooks('create_time', [['end', '=', '1']], 14);
            cache('ends_homepage', $ends, null, 'redis');
        }

        $most_charged = cache('most_charged');
        if (!$most_charged) {
            $arr = $this->bookService->getMostChargedBook();
            if (count($arr) > 0) {
                foreach ($arr as $item) {
                    $most_charged[] = $item['book'];
                }
            } else {
                $arr = [];
            }
            cache('most_charged', $most_charged, null, 'redis');
        }

        $tags = cache('tags');
        if (!$tags) {
            $tags = \app\model\Tags::all();
            cache('tags', $tags, null, 'redis');
        }

        $catelist = cache('catelist');
        if (!$catelist) {
            $catelist = array(); //分类漫画数组
            $cateItem = array();
            foreach ($tags as $tag) {
                $books = $this->bookService->getByTag($tag->tag_name);
                $cateItem['books'] = $books->toArray();
                $cateItem['tag'] = ['id' => $tag->id, 'tag_name' => $tag->tag_name];
                $catelist[] = $cateItem;
            }
            cache('catelist', $catelist, null, 'redis');
        }

        $this->assign([
            'banners' => $banners,
            'banners_count' => count($banners),
            'newest' => $newest,
            'hot' => $hot_books,
            'ends' => $ends,
            'most_charged' => $most_charged,
            'tags' => $tags,
            'catelist' => $catelist
        ]);

        return view($this->tpl);
    }

    public function search()
    {
        $keyword = input('keyword');
        $redis = new_redis();
        $redis->zIncrBy($this->redis_prefix . 'hot_search', 1, $keyword); //搜索词写入redis热搜
        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search', 0, 4, true);
        $hot_search = array();
        foreach ($hot_search_json as $k => $v) {
            $hot_search[] = $k;
        }
        $books = cache('searchresult:' . $keyword);
        if (!$books) {
            $num = config('page.search_result_pc');
            if ($this->request->isMobile()) {
                $num = config('page.search_result_mobile');
            }
            $books = $this->bookService->search($keyword, $num);
            cache('searchresult:' . $keyword, $books, null, 'redis');
        }
        foreach ($books as &$book) {
            $author = Author::get($book['author_id']);
            $book['author'] = $author;
        }
        $this->assign([
            'books' => $books,
            'count' => count($books),
            'hot_search' => $hot_search,
            'keyword' => $keyword
        ]);
        return view($this->tpl);
    }

    public function bookshelf()
    {
        $this->assign('header_title', '书架');
        return view($this->tpl);
    }
}

