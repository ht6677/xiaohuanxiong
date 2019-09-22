<?php


namespace app\worker;


use app\model\ChargeCode;
use app\model\VipCode;
use think\worker\Server;

class Gen extends Server
{
    protected $socket = 'websocket://0.0.0.0:2347';

    public function onConnect($connection)
    {
        $connection->onWebSocketConnect = function ($connection, $http_header) {
            $p = $_GET['p'];
            if ($p == 'vip') {
                $num = (int)config('kami.vipcode.num'); //产生多少个
                $day = config('kami.vipcode.day');

                $data = [
                    'num' => $num,
                    'day' => $day,
                ];
                $validate = new \app\admin\validate\Vipcode();
                if (!$validate->check($data)) {
                    $connection->send('后台配置错误');
                }

                $salt = config('site.' . config('kami.salt'));//根据配置，获取盐的方式
                for ($i = 1; $i <= $num; $i++) {
                    $code = substr(md5($salt . time()), 8, 16);
                    VipCode::create([
                        'code' => $code,
                        'add_day' => $day
                    ]);
                    $connection->send('<p style="padding-left:15px 24px;font-weight: 400;color:#999;">' . '生成' . $code . '，天数为' . $day . '</p>');
                    sleep(1);
                }
            } else if ($p == 'charge') {
                $num = (int)config('kami.chargecode.num'); //产生多少个
                $money = config('kami.chargecode.money');

                $data = [
                    'num' => $num,
                    'money' => $money,
                ];
                $validate = new \app\admin\validate\Chargecode();
                if (!$validate->check($data)) {
                    $connection->send('后台配置错误');
                }

                $salt = config('site.' . config('kami.salt'));//根据配置，获取盐的方式
                for ($i = 1; $i <= $num; $i++) {
                    $code = substr(md5($salt . time()), 8, 16);
                    ChargeCode::create([
                        'code' => $code,
                        'money' => $money
                    ]);
                    $connection->send('<p style="padding-left:15px 24px;font-weight: 400;color:#999;">' . '生成' . $code . '，金额为' . $money . '</p>');
                    sleep(1);
                }
            }
        };
    }
}