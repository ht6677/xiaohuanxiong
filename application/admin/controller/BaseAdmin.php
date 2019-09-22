<?php
/**
 * Created by PhpStorm.
 * User: zhangxiang
 * Date: 2018/10/19
 * Time: 下午6:04
 */

namespace app\admin\controller;


use think\App;
use think\Controller;
use think\facade\Session;
use think\facade\View;

class BaseAdmin extends Controller
{
    protected function initialize()
    {
        $this->checkAuth();
    }

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $img_site = config('site.img_site');
        $version = file_get_contents(\think\facade\App::getRootPath().'public/static/html/version.txt');
        View::share([
            'img_site' => $img_site,
            'version' => $version,
            'returnUrl' => $this->request->url(true),
            'host_ip' => $_SERVER['SERVER_ADDR']
        ]);
    }

    protected function checkAuth(){
        if (!Session::has('xwx_admin')) {
            $this->redirect('admin/login/index');
        }
    }
}