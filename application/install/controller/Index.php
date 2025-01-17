<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/9 0009
 * Time: 上午 10:58
 */

namespace app\install\controller;

use think\Controller;
use think\Db;
use think\facade\App;

class Index extends Controller
{

    protected function initialize()
    {
        if (is_file(App::getRootPath() . 'application/install/install.lock')) {
            header("Location: /");
            exit;
        }
    }

    public function index($step = 0)
    {
        switch ($step) {
            case 2:
                session('install_error', false);
                return self::step2();
                break;
            case 3:
                if (session('install_error')) {
                    return $this->error('环境检测未通过，不能进行下一步操作！');
                }
                return self::step3();
                break;
            case 4:
                if (session('install_error')) {
                    return $this->error('环境检测未通过，不能进行下一步操作！');
                }
                return self::step4();
                break;
            case 5:
                if (session('install_error')) {
                    return $this->error('初始失败！');
                }
                return self::step5();
                break;

            default:
                session('install_error', false);
                return $this->fetch();
                break;
        }
    }

    /**
     * 第二步：环境检测
     * @return mixed
     */
    private function step2()
    {
        $data = [];
        $data['env'] = self::checkNnv();
        $data['dir'] = self::checkDir();
        $data['func'] = self::checkFunc();
        $this->assign('data', $data);
        return $this->fetch('step2');
    }

    /**
     * 第三步：初始化配置
     * @return mixed
     */
    private function step3()
    {
        $install_dir = $_SERVER["SCRIPT_NAME"];
        $install_dir = xwxcms_substring($install_dir, strripos($install_dir, "/") + 1);
        $this->assign('install_dir', $install_dir);
        return $this->fetch('step3');
    }

    /**
     * 第四步：执行安装
     * @return mixed
     * @throws
     */
    private function step4()
    {
        if ($this->request->isPost()) {
            if (!is_writable(App::getRootPath() . 'config/database.php')) {
                return json(['code' => 0, 'msg' => '[app/config/database.php]无读写权限！']);
            }
            $data = $this->request->only(['hostname', 'hostport', 'database', 'username', 'prefix', 'password']);
            $data['type'] = 'mysql';

            $rule = [
                'hostname|服务器地址' => 'require',
                'hostport|数据库端口' => 'require|number',
                'database|数据库名称' => 'require',
                'username|数据库账号' => 'require',
                'password|数据库密码' => 'require',
                'prefix|数据库前缀' => 'require|alphaNum'
            ];
            $validate = $this->validate($data, $rule);
            if (true !== $validate) {
                return json(['code' => 0, 'msg' => $validate]);
            }

            $config = include App::getRootPath() . 'config/database.php';
            foreach ($data as $k => $v) {
                if (array_key_exists($k, $config) === false) {
                    return json(['code' => 0, 'msg' => '参数' . $k . '不存在！']);
                }
            }
            // 不存在的数据库会导致连接失败
            $database = $data['database'];

            unset($data['database']);
            // 创建数据库连接
            $db_connect = Db::connect($data);
            // 检测数据库连接
            try {
                $version = $db_connect->query('select version()');
                $num = str_replace('.', '', $version);
                if ($num < '570') {
                    return json(['code' => 0, 'msg' => 'MySQL版本不能低于5.7']);
                }
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '数据库连接失败，请检查数据库配置！']);
            }


            // 生成数据库配置文件
            $data['database'] = $database;
            self::make_database($data);

            $check = $db_connect->execute('SELECT * FROM information_schema.schemata WHERE schema_name="' . $database . '"');
            if (!$check) {
                // 创建数据库
                if (!$db_connect->execute("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8")) {
                    return json(['code' => 0, 'msg' => $db_connect->getError()]);
                }
            } 
            // else {
            //     return json(['code' => 0, 'msg' => '数据库已存在']);
            // }

            // 导入系统初始数据库结构
            // 导入SQL
            $sql_file = App::getRootPath() . 'application/install/sql/install.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $sql_list = xwxcms_parse_sql($sql, 0, ['xwx_' => $config['prefix']]);
                if ($sql_list) {
                    $sql_list = array_filter($sql_list);
                    foreach ($sql_list as $v) {
                        try {
                            Db::execute($v);
                        } catch (\Exception $e) {
                            return json(['code' => 0, 'msg' => '导入SQL失败，请检查install.sql的语句是否正确。' . $e]);
                        }
                    }
                }
            }

            return json(['code' => 1, 'msg' => '数据库连接成功', '']);
        } else {
            return json(['code' => 1, 'msg' => '非法访问']);
        }
    }

    /**
     * 第五步：数据库安装
     * @return mixed
     */
    private function step5()
    {
        $param = $this->request->only(['username', 'password', 'salt', 'redis_prefix']);

        $config = include App::getRootPath() . 'config/database.php';
        if (empty($config['hostname']) || empty($config['database']) || empty($config['username'])) {
            return $this->error('请先点击测试数据库连接！');
        }

        $rule = [
            'username|管理员账号' => 'require|alphaNum',
            'password|管理员密码' => 'require|length:6,20',
            'salt|密码盐' => 'require|alphaNum',
            'id盐' => 'alphaNum',
            'redis_prefix|缓存前缀' => 'require|alphaNum'
        ];

        $validate = $this->validate($param, $rule);
        if (true !== $validate) {
            return $this->error($validate);
        }
        $param['redis_prefix'] = $param['redis_prefix'] . ':';

        $this->setSiteConfig(trim($param['salt'])); //写入网站配置文件
        $this->setCacheConfig(trim($param['redis_prefix'])); //写入cache配置文件
        // 注册管理员账号
        $data = [
            'username' => $param['username'],
            'password' => md5(strtolower(trim($param['password'])) . trim($param['salt'])),
            'last_login_time' => time(),
            'last_login_ip' => $this->request->ip(),
        ];
        $admin = new \app\model\Admin();
        $res = $admin->save($data);
        if (!$res) {
            return $this->error('管理员账号设置失败:' . $res['msg']);
        }
        $install = App::getRootPath() . 'application/install/install.lock';
        if (!is_dir(dirname($install))) {
            @mkdir(dirname($install), 0777, true);
        }

        file_put_contents($install, date('Y-m-d H:i:s'));

        $this->success('系统安装成功,欢迎您使用小涴熊CMS建站.');
    }

    private function setSiteConfig($salt)
    {
        $site_name = config('site.site_name');
        $url = config('site.url');
        $img_site = config('site.img_site');
        $api_key = config('site.api_key');
        $code = <<<INFO
        <?php
        return [
            'url' => '{$url}',
            'img_site' => '{$img_site}',
            'site_name' => '{$site_name}',
            'salt' => '{$salt}',
            'api_key' => '{$api_key}',   
            'tpl' => 'default',
            'payment' => 'Vkzf'            
            ];
INFO;
        file_put_contents(App::getRootPath() . 'config/site.php', $code);
    }

    private function setCacheConfig($redis_prefix)
    {
        $code = <<<INFO
        <?php
        return [
            // 驱动方式
            'type'   => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'password'   => '',
            // 缓存保存目录
            'path'   => '../runtime/cache/',
            // 缓存前缀
            'prefix' => '{$redis_prefix}',
            // 缓存有效期 0表示永久缓存
            'expire' => 600,
        ];
INFO;
        file_put_contents(App::getRootPath() . 'config/cache.php', $code);
    }

    /**
     * 环境检测
     * @return array
     */
    private function checkNnv()
    {
        $items = [
            'os' => ['操作系统', '不限制', 'Windows/Unix', PHP_OS, 'ok'],
            'php' => ['PHP版本', '7.0', '7.0及以上', PHP_VERSION, 'ok'],
            'gd' => ['GD库', '2.0', '2.0及以上', '未知', 'ok'],

        ];
        if ($items['php'][3] < $items['php'][1]) {
            $items['php'][4] = 'no';
            session('install_error', true);
        }
        $tmp = function_exists('gd_info') ? gd_info() : [];
        if (empty($tmp['GD Version'])) {
            $items['gd'][3] = '未安装';
            $items['gd'][4] = 'no';
            session('install_error', true);
        } else {
            $items['gd'][3] = $tmp['GD Version'];
        }

        return $items;
    }

    /**
     * 目录权限检查
     * @return array
     */
    private function checkDir()
    {
        $items = [
            ['dir', '../application', '读写', '读写', 'ok'],
            ['dir', '../config', '读写', '读写', 'ok'],
            ['file', '../config/database.php', '读写', '读写', 'ok'],
            ['dir', '../runtime', '读写', '读写', 'ok'],
            ['dir', '../public/static/upload', '读写', '读写', 'ok'],
            ['file', '../config/cache.php', '读写', '读写', 'ok'],
        ];
        foreach ($items as &$v) {
            if ($v[0] == 'dir') {// 文件夹
                if (!is_writable($v[1])) {
                    if (is_dir($v[1])) {
                        $v[3] = '不可写';
                        $v[4] = 'no';
                    } else {
                        $v[3] = '不存在';
                        $v[4] = 'no';
                    }
                    session('install_error', true);
                }
            } else {// 文件
                if (!is_writable($v[1])) {
                    $v[3] = '不可写';
                    $v[4] = 'no';
                    session('install_error', true);
                }
            }
        }
        return $items;
    }

    /**
     * 函数及扩展检查
     * @return array
     */
    private function checkFunc()
    {
        $items = [
            ['pdo', '支持', 'yes', '类'],
            ['pdo_mysql', '支持', 'yes', '模块'],
            ['zip', '支持', 'yes', '模块'],
            ['xml', '支持', 'yes', '函数'],
            ['file_get_contents', '支持', 'yes', '函数'],
            ['mb_strlen', '支持', 'yes', '函数'],
            ['gzopen', '支持', 'yes', '函数'],
        ];

        if (version_compare(PHP_VERSION, '7.0.0', 'lt')) {
            $items[] = ['always_populate_raw_post_data', '支持', 'yes', '配置'];
        }

        foreach ($items as &$v) {
            if (('类' == $v[3] && !class_exists($v[0])) || ('模块' == $v[3] && !extension_loaded($v[0])) || ('函数' == $v[3] && !function_exists($v[0])) || ('配置' == $v[3] && ini_get('always_populate_raw_post_data') != -1)) {
                $v[1] = '不支持';
                $v[2] = 'no';
                session('install_error', true);
            }
        }

        return $items;
    }

    /**
     * 生成数据库配置文件
     * @return array
     */
    private function make_database(array $data)
    {
        $code = <<<INFO
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
    // 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'hostname'        => '{$data['hostname']}',
    // 数据库名
    'database'        => '{$data['database']}',
    // 用户名
    'username'        => '{$data['username']}',
    // 密码
    'password'        => '{$data['password']}',
    // 端口
    'hostport'        => '{$data['hostport']}',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => '{$data['prefix']}_',
    // 数据库调试模式
    'debug'           => false,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格检查字段是否存在
    'fields_strict'   => false,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => false,
    // 是否需要进行SQL性能分析
    'sql_explain'     => false
];
INFO;
        file_put_contents(App::getRootPath() . 'config/database.php', $code);
        // 判断写入是否成功
        $config = include App::getRootPath() . 'config/database.php';
        if (empty($config['database']) || $config['database'] != $data['database']) {
            return $this->error('[application/config/database.php]数据库配置写入失败！');
            exit;
        }
    }

}