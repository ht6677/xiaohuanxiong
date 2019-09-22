<?php


namespace app\worker;


use GuzzleHttp\Client;
use think\Exception;
use think\facade\Env;
use think\worker\Server;

class Push extends Server
{
    protected $socket = 'websocket://0.0.0.0:2346';
//    protected $context = [
//        'ssl' => [
//            'local_cert' => '磁盘路径/server.pem',
//            'local_pk' => '磁盘路径/server.key',
//            'verify_peer' => false,
//        ],
//    ];
//    protected $transport = 'ssl';

    public function onConnect($connection)
    {
        try {
            $client = new Client();
            $srcUrl = Env::get('root_path') . "/public/static/html/version.txt";
            $localVersion = (int)str_replace('.', '', file_get_contents($srcUrl));
            $server = "http://update.xhxcms.xyz";
            $serverFileUrl = $server . "/public/static/html/version.txt";
            $res = $client->request('GET', $serverFileUrl); //读取版本号
            $serverVersion = (int)str_replace('.', '', $res->getBody());
            $connection->send('<p></p>');

            if ($serverVersion > $localVersion) {
                file_put_contents($srcUrl, $res, true); //将版本号写入到本地文件
                $connection->send('<p style="padding-left:15px 24px;font-weight: 400;color:#999;">覆盖版本号</p>');
                for ($i = $localVersion + 1; $i <= $serverVersion; $i++) {
                    $res = $client->request('GET', "http://config.xhxcms.xyz/" . $i . ".json");
                    if ((int)($res->getStatusCode()) == 200) {
                        $json = json_decode($res->getBody(), true);

                        foreach ($json['update'] as $value) {
                            $data = $client->request('GET', $server . '/' . $value)->getBody(); //根据配置读取升级文件的内容
                            $saveFileName = Env::get('root_path') . $value;
                            $dir = dirname($saveFileName);
                            if (!file_exists($dir)) {
                                mkdir($dir, 0777, true);
                            }
                            file_put_contents($saveFileName, $data, true); //将内容写入到本地文件
                            $connection->send('<p style="padding-left:15px 24px;font-weight: 400;color:#999;">升级文件' . $value . '</p>');
                        }
                        foreach ($json['delete'] as $value) {
                            $flag = unlink(Env::get('root_path') . '/' . $value);
                            if ($flag) {
                                $connection->send('<p style="padding-left:15px 24px;font-weight: 400;color:#999;">删除文件' . $value . '</p>');
                            } else {
                                $connection->send('<p style="padding-left:15px 24px;font-weight: 400;color:#999;">删除文件失败</p>');
                            }
                        }
                    }
                }
                $connection->send('<p style="padding-left:15px;font-weight: 400;color:#999;">升级完成</p>');
            } else {
                $connection->send('<p style="padding-left:15px;font-weight: 400;color:#999;">已经是最新版本！当前版本是' . $localVersion.'</p>');
            }
        } catch (Exception $exception) {
            $connection->send($exception->getMessage());
        }

    }
}