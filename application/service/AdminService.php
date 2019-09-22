<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/1/8
 * Time: 17:04
 */

namespace app\service;

use app\model\Admin;

class AdminService
{
    public function GetAll()
    {
        $data = Admin::order('id', 'desc');
        $page = config('page.back_end_page');
        $admins = $data->paginate($page, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);
        return [
            'admins' => $admins,
            'count' => $data->count()
        ];
    }
}