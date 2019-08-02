<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/4/24
 * Time: 10:23
 */

namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;
use think\Config;

class Customer extends Common
{
    public function index(){
        $talkList=Db::name('customer_service')->where(['send_id'=>['<>','0']])->select();
        $this->assign(['list'=>$talkList]);
        return $this->fetch();
    }

    public function service(){
        $id=input('get.id');
        $user_info=Db::name('member')->where(['id'=>$id])->find();
        if(!$user_info){
            $this->error("该用户不存在");
        }
        $user_talk=Db::name('customer_service')->where(['user_id'=>$id])->select();
        $data=input();
        if($id){
                if(request()->isPost()){ 
                    if(!$data['content']){
                        $this->error("回复内容不能为空");
                    }
                $data_res['user_id']=$data['user_id'];
                $data_res['send_name']="客服小小熊";
                $data_res['send_id']=0;
                $data_res['send_avatar']='https://dpic.tiankong.com/3w/h7/QJ6961899400.jpg?x-oss-process=style/670ws';
                $data_res['recive_name']=$user_info['realname'];
                $data_res['recive_id']=$data['user_id'];
                $data_res['recive_avatar']=$user_info['avatar'];
                $data_res['content']=$data['content'];
                $data_res['create_time']=time();
                $data_res['send_type']=0;
                $insertTalk=Db::name('customer_service')->insert($data_res);
                if($insertTalk){
                    $this->success('回复成功',url('customer/index'));
                }else{
                    $this->error('回复失败');
                }
            }
        }
        $this->assign([
            'user_id'=>$id,
            'user_talk'=>$user_talk
        ]);
        return $this->fetch();
    }
}