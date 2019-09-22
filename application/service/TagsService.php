<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2018/10/4
 * Time: 0:03
 */

namespace app\service;

use app\model\Tags;

class TagsService
{
    public function getByName($tagname){
        return Tags::where('tag_name','=',$tagname)->find();
    }

    public function getPagedAdmin($where = '1=1'){
        $data = Tags::where($where);
        $page = config('page.back_end_page');
        $tags = $data->order('id','desc')
            ->paginate($page,false,
                [
                    'query' => request()->param(),
                    'type'     => 'util\AdminPage',
                    'var_page' => 'page',
                ]);
        return [
            'tags' => $tags,
            'count' => $data->count()
        ];
    }
}