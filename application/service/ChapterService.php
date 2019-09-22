<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2018/10/5
 * Time: 10:42
 */

namespace app\service;


use app\model\Chapter;

class ChapterService
{
    public function getChapters($where = '1=1')
    {
        $page = config('page.back_end_page');
        $chapters = Chapter::where($where);
        $pages = $chapters->with(['photos' => function ($query) {
            $query->order('pic_order');
        }])->order('id', 'desc')->paginate($page, false, [
            'query' => request()->param(),
            'type' => 'util\AdminPage',
            'var_page' => 'page',
        ]);
        foreach ($pages as &$chapter) {
            $chapter['photo_count'] = count($chapter->photos);
        }
        return [
            'chapters' => $pages,
            'count' => $chapters->count(),
        ];
    }

    public function findByName($chapter_name)
    {
        return Chapter::where('chapter_name', '=', $chapter_name)->find();
    }

    public function getLastChapter($book_id)
    {
        return Chapter::where('book_id', '=', $book_id)->order('chapter_order', 'desc')->limit(1)->find();
    }
}