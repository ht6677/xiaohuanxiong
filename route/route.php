<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;


Route::rule('/tag/[:name]', 'index/tags/index');
Route::rule('/book/:id', 'index/books/index');
Route::rule('/booklist', 'index/books/booklist');
Route::rule('/ajaxlist', 'index/books/ajaxlist');
Route::rule('/chapter/:id-[:salt]', 'index/chapters/index');
Route::rule('/search/[:keyword]', 'index/search');
Route::rule('/rank', 'index/rank/index');
Route::rule('/update', 'index/books/update');
Route::rule('/author/:id', 'index/authors/index');

Route::rule('/ucenter', 'ucenter/users/ucenter');
Route::rule('/bookshelf', 'ucenter/users/bookshelf');
Route::rule('/history', 'ucenter/users/history');
Route::rule('/userinfo', 'ucenter/users/userinfo');
Route::rule('/delfavors', 'ucenter/users/delfavors');
Route::rule('/delhistory', 'ucenter/users/delhistory');
Route::rule('/updateUserinfo', 'ucenter/users/update');
Route::rule('/bindphone', 'ucenter/users/bindphone');
Route::rule('/userphone', 'ucenter/users/userphone');
Route::rule('/sendcms', 'ucenter/users/sendcms');
Route::rule('/verifyphone', 'ucenter/users/verifyphone');
Route::rule('/recovery', 'ucenter/account/recovery');
Route::rule('/resetpwd', 'ucenter/users/resetpwd');
Route::rule('/commentadd', 'ucenter/users/commentadd');
Route::rule('/leavemsg', 'ucenter/users/leavemsg');
Route::rule('/message', 'ucenter/users/message');
Route::rule('/promotion', 'ucenter/users/promotion');

Route::rule('/wallet', 'ucenter/finance/wallet');
Route::rule('/chargehistory', 'ucenter/finance/chargehistory');
Route::rule('/spendinghistory', 'ucenter/finance/spendinghistory');
Route::rule('/buyhistory', 'ucenter/finance/buyhistory');
Route::rule('/charge', 'ucenter/finance/charge');
Route::rule('/feedback', 'ucenter/finance/feedback');
Route::rule('/buychapter', 'ucenter/finance/buychapter');
Route::rule('/vip', 'ucenter/finance/vip');
Route::rule('/kami', 'ucenter/finance/kami');
Route::rule('/vipexchange', 'ucenter/finance/vipexchange');

Route::rule('/zhapaynotify', 'api/zhapaynotify/index');
Route::rule('/vkzfnotify', 'api/vkzfnotify/index');
Route::rule('/xunhunotify', 'api/xunhunotify/index');
Route::rule('/kakapaynotify','api/kakapaynotify/index');

Route::rule('/login', 'ucenter/account/login');
Route::rule('/register', 'ucenter/account/register');
Route::rule('/logout', 'ucenter/account/logout');
Route::rule('/addfavor', 'index/books/addfavor');
