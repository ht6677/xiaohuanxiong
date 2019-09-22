<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2018/9/30
 * Time: 15:01
 */

namespace app\model;

use think\facade\Cache;
use think\Model;

class Banner extends Model
{
    protected $pk='id';

    public function book(){
        return $this->hasOne('book','id','book_id');
    }

    public function setTitleAttr($value){
        return trim($value);
    }
}

