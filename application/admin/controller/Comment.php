<?php


namespace app\admin\controller;


use app\model\Comments;

class Comment extends BaseAdmin
{
    public function index()
    {
        $map = array();
        $uid = input('uid');
        if ($uid){
            $map[] = ['user_id','=',$uid];
        }

        $book_id = input('book_id');
        if ($book_id){
            $map[] = ['book_id','=',$book_id];
        }
        $data = Comments::where($map)->with('book,user');
        $comments = $data->order('id', 'desc')->paginate(5, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);
//        ->each(function ($item, $key) {
//            $dir = Env::get('root_path') . '/public/static/upload/comments/' . $item->book->id . '/';
//            $item['content'] = file_get_contents($dir . $item->id . '.txt'); //获取用户评论内容
//        });
        $this->assign([
            'comments' => $comments,
            'count' => $data->count()
        ]);
        return view();
    }

    public function delete($id){
        $comment = Comments::get($id);
        if (empty($comment)){
            return ['err' => '1','msg' => '删除失败'];
        }
        $result = $comment->delete();
        if ($result) {
            return ['err' => '0','msg' => '删除成功'];
        } else {
            return ['err' => '1','msg' => '删除失败'];
        }
    }

    public function deleteAll($ids){
        Comments::destroy($ids);
    }
}