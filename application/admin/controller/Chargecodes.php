<?php


namespace app\admin\controller;


use app\model\ChargeCode;
use think\facade\Env;

class Chargecodes extends BaseAdmin
{
    public function index()
    {
        $data = ChargeCode::order('id', 'desc');
        $codes = $data->paginate(5, false,
            [
                //'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);

        $this->assign([
            'codes' => $codes,
            'count' => $data->count(),
            'api_key' => config('site.api_key'),
        ]);
        return view();
    }

    public function detail(){
        $this->assign([
            'salt' => config('kami.salt'),
            'chargecode_money' => config('kami.chargecode.money'),
            'chargecode_num' => config('kami.chargecode.num')
        ]);
        return view();
    }

    public function search()
    {
        $where = array();
        $money = input('money');
        if (!empty($days)) {
            $where[] = ['money', '=', $money];
        }
        $used = input('used');
        if (!empty($used)) {
            $where[] = ['used', '=', $used];
        }
        $data = ChargeCode::where($where)->order('id', 'desc');

        $codes = $data->paginate(5, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);

        $this->assign([
            'codes' => $codes,
            'count' => $data->count(),
            'api_key' => config('site.api_key'),
        ]);
        return view('index');
    }

    public function export()
    {
        $data = ChargeCode::where('used', '=', 1)->select()->toArray();
        if (empty($data)) {
            return json(['err=>1', 'msg' => '没有可导出的']);
        }
        $arr = array();
        foreach ($data as $code) {
            ChargeCode::update([
                'id' => $code['id'],
                'used' => 2,
                'update_time' => time()
            ]);
            $arr[] = $code['code'];
        }
        $dir = Env::get('root_path') . '/public/downloads';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = $dir . '/chargecode.txt';
        if (file_exists($file)) {
            delete_dir_file($file);
        }
        file_put_contents($file, implode("\r\n", $arr));
        $site_url = config('site.url');

        return json(['err=>0', 'msg' => '成功导出，<a href="' . $site_url.'/downloads/chargecode.txt">点击下载</a>']);
    }

    public function delete($id)
    {
        $code = ChargeCode::get($id);
        if (empty($code)) {
            return json(['err' => '1', 'msg' => '找不到该项']);
        }
        $code->delete();
        return json(['err' => '0', 'msg' => '删除成功']);
    }

    public function deleteAll($ids)
    {
        ChargeCode::destroy($ids);
    }
}