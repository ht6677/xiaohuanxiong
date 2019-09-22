<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/3/1
 * Time: 21:19
 */

namespace app\api\controller;

use app\model\Author;
use app\model\Book;
use app\model\Photo;
use think\Controller;
use think\Db;
use think\Request;
use app\model\Chapter;

class Write extends Controller
{
    protected $chapterService;
    protected $photoService;
    protected $conn;

    public function initialize()
    {
        $this->chapterService = new \app\service\ChapterService();
        $this->photoService = new \app\service\PhotoService();
        $this->conn = [
            'type'            => 'mysql',
            'hostname'        => '数据库ip',
            'database'        => '数据库名',
            'username'        => '数据库名',
            'password'        => '数据库密码',
            'hostport'        => '端口号',
            'charset'         => 'utf8',
        ];
    }

    public function save(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->param();
            $key = $data['api_key'];
            if (empty($key) || is_null($key)) {
                return 'api密钥不能为空！';
            }
            if ($key != config('site.api_key')) {
                return 'api密钥错误！';
            }

            $book_id = -1;
            $where[] = ['src_url','=',$data['src_url']];
            $where[] = ['src','=',$data['src']];
            $booklog = Db::connect($this->conn)->name('booklogs')->where($where)->find();
            if (empty($booklog)) { //如果漫画不存在
                $author = Author::where('author_name', '=', trim($data['author']))->find();
                if (is_null($author)) {//如果作者不存在
                    $author = new Author();
                    $author->author_name = $data['author'] ?: '侠名';
                    $author->save();
                }
                $book = new Book();
                $book->author_id = $author->id;
                $book->area_id = trim($data['area_id']);
                $book->book_name = trim($data['book_name']);
                if (!empty($data['nick_name']) || !is_null($data['nick_name'])) {
                    $book->nick_name = trim($data['nick_name']);
                }
                $book->tags = trim($data['tags']);
                $book->end = trim($data['end']);
                $book->cover_url = trim($data['cover_url']);
                $book->summary = trim($data['summary']);
                $book->last_time = time();
                halt($book->save());
                $book_id = $book->id;

                Db::connect($this->conn)->name('booklogs')->insert([
                    'book_id' => $book_id,
                    'src_url' => $data['src_url'],
                    'src' => $data['src']
                ]);
            }
            else {
                $book_id = $booklog['book_id'];
                $book = Book::get($book_id);
                $book->update_time = time();
                $book->save();
            }
            $this->addChapter($book_id, $data);
        }
    }

    public function addChapter($book_id, $data)
    {
        $chapterlog = Db::connect($this->conn)->name('chapterlogs')->where('c_src_url','=',$data["c_src_url"])->find();
        if (empty($chapterlog)) {
            $chapter = new Chapter();
            $chapter->chapter_name = trim($data['chapter_name']);
            $chapter->book_id = $book->id;
            $lastChapterOrder = 0;
            $lastChapter = $this->chapterService->getLastChapter($book->id);
            if ($lastChapter) {
                $lastChapterOrder = $lastChapter->chapter_order;
            }
            $chapter->chapter_order = $lastChapterOrder + 1;
            $chapter->save();

            Db::connect($this->conn)->name('chapterlogs')->insert([
                'book_id' => $book_id,
                'chapter_id' => $chapter->id,
                'c_src_url' => 'c_src_url'
            ]);
            $preg = '/\bsrc\b\s*=\s*[\'\"]?([^\'\"]*)[\'\"]?/i';
            preg_match_all($preg, $data['images'], $img_urls);
            $lastOrder = 0;
            $lastPhoto = $this->photoService->getLastPhoto($chapter->id);
            if ($lastPhoto) {
                $lastOrder = $lastPhoto->pic_order + 1;
            }
            foreach ($img_urls[1] as $img_url) {
                $photo = new Photo();
                $photo->chapter_id = $chapter->id;
                $photo->pic_order = $lastOrder;
                $photo->img_url = $img_url;
                $photo->save();
                $lastOrder++;
            }
        }
    }
}