<?php


namespace app\admin\validate;


use think\Validate;

class Chargecode extends Validate
{
    protected $rule = [
        'money' => 'integer',
        'num' => 'integer',
    ];


    protected $message = [
        'money' => '面额必须是整数',
        'num' => '生成个数必须是整数'
    ];
}