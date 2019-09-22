<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/2/25
 * Time: 15:55
 */

namespace app\ucenter\controller;


use app\model\User;
use app\service\PromotionService;
use think\App;
use think\Controller;
use think\facade\Env;
use think\Request;

class Account extends Controller
{
    protected $tpl;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $tpl_root = Env::get('root_path') . '/public/template/default/ucenter/';
        $controller = strtolower($this->request->controller());
        $action = strtolower($this->request->action());
        if ($this->request->isMobile()) {
            $this->tpl = $tpl_root . $controller . '/' . $action . '.html';
        } else {
            $this->tpl = $tpl_root . $controller . '/' . 'pc_' . $action . '.html';
        }
    }

    public function register(Request $request)
    {
        if ($request->isPost()) {
            $captcha = $request->param('captcha');
            if( !captcha_check($captcha ))
            {
                return ['err' => 1, 'msg' => '验证码错误'];
            }
            $ip = $request->ip();
            $redis = new_redis();
            if ($redis->exists('user_reg:' . $ip)) {
                return ['err' => 1, 'msg' => '操作太频繁'];
            } else {
                $redis->set('user_reg:'.$ip,1,60); //写入锁
                $data = $request->param();
                $validate = new \app\ucenter\validate\User();
                if ($validate->check($data)) {
                    $user = User::where('username', '=', trim($request->param('username')))->find();
                    if (!is_null($user)) {
                        return ['err' => 1, 'msg' => '用户名已经存在'];
                    }
                    $user = new User();
                    $user->username = trim($request->param('username'));
                    $user->password = trim($request->param('password'));
                    $pid = cookie('xwx_promotion');
                    if (!$pid) {
                        $pid = 0;
                    }
                    $user->pid = $pid; //设置用户上线id
                    $result = $user->save();
                    if ($result) {
                        $promotionService = new PromotionService();
                        $promotionService->rewards($user->id, (float)config('payment.reg_rewards'), 2); //调用推广处理函数
                        return ['err' => 0, 'msg' => '注册成功，请登录'];
                    } else {
                        return ['err' => 1, 'msg' => '注册失败，请尝试重新注册'];
                    }
                } else {
                    return ['err' => 1, 'msg' => $validate->getError()];
                }

            }

        } else {
            $this->assign([
                'site_name' => config('site.site_name'),
                'url' => config('site.url'),
                'header_title' => '注册'
            ]);
            return view($this->tpl);
        }
    }

    public function login(Request $request)
    {
        if ($request->isPost()) {
            $captcha = $request->param('captcha');
            if( !captcha_check($captcha ))
            {
                return ['err' => 1, 'msg' => '验证码错误'];
            }
            $map = array();
            $map[] = ['username', '=', trim($request->param('username'))];
            $map[] = ['password', '=', md5(strtolower(trim($request->param('password'))) . config('site.salt'))];
            $user = User::withTrashed()->where($map)->find();
            if (is_null($user)) {
                return ['err' => 1, 'msg' => '用户名或密码错误'];
            } else {
                if ($user->delete_time > 0) {
                    return ['err' => 1, 'msg' => '用户被锁定'];
                } else {
                    $user->last_login_time = time();
                    $user->isUpdate(true)->save();
                    session('xwx_user', $user->username);
                    session('xwx_user_id', $user->id);
                    session('xwx_nick_name', $user->nick_name);
                    session('xwx_user_mobile', $user->mobile);
                    session('xwx_vip_expire_time', $user->vip_expire_time);
                    return ['err' => 0, 'msg' => '登录成功'];
                }

            }
        } else {
            $this->assign([
                'site_name' => config('site.site_name'),
                'url' => config('site.url'),
                'header_title' => '登录'
            ]);
            return view($this->tpl);
        }
    }

    public function logout()
    {
        session('xwx_user', null);
        session('xwx_user_id', null);
        session('xwx_nick_name', null);
                    session('xwx_user_mobile',null);
                    session('xwx_vip_expire_time', null);
        $this->success('成功登出', '/login');
    }

    public function recovery()
    {
        if ($this->request->isPost()) {
            $code = trim(input('txt_phonecode'));
            $phone = trim(input('txt_phone'));
            if (verifycode($code, $phone) == 0) {
                return ['err' => 1, 'msg' => '验证码不正确'];
            }
            $pwd = input('txt_password');
            $user = User::where('mobile', '=', $phone)->find();
            if (is_null($user)) {
                return ['err' => 1, 'msg' => '该手机号不存在'];
            }
            $user->password = $pwd;
            $user->isUpdate(true)->save();
            return ['err' => 0, 'msg' => '修改成功'];
        }
        $phone = input('phone');
        $this->assign([
            'phone' => $phone,
            'header_title' => '找回密码',
            'site_name' => config('site.site_name'),
            'url' => config('site.url')
        ]);
        return view($this->tpl);
    }
}