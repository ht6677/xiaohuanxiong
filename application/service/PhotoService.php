<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/1/29
 * Time: 12:33
 */

namespace app\service;

use app\model\Photo;

class PhotoService
{
    public function getLastPhoto($chapter_id)
    {
        return Photo::where('chapter_id', '=', $chapter_id)->order('id', 'desc')->limit(1)->find();
    }

    public function getAdminPaged($chapter_id, $num)
    {
        $data = Photo::where('chapter_id', '=', $chapter_id);
        $photos = $data->order('pic_order', 'desc')
            ->paginate($num, false,
                [
                    'query' => request()->param(),
                    'type' => 'util\AdminPage',
                    'var_page' => 'page',
                ]);
        return ['photos' => $photos, 'count' => $data->count()];
    }
}