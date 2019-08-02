<?php
/**
 * Created by PhpStorm.
 * User: zgp
 * Date: 2019/7/2 0002
 * Time: 17:51
 */

namespace app\api\controller;

use app\common\controller\ApiBase;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\util\jwt\JWT;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;
use app\api\model\UserAddr;
use think\Cache;

class Customer extends ApiBase
{

    public function index()
    {
        // $this->user_id=27875;
        $talkList=Db::name('customer_service')->where(['user_id'=>$this->user_id])->select();
        return $this->successResult($talkList);
    }

    public function service()
    {
        if(request()->post()){
            // $this->user_id=27875;
            $member_info=Db::name('member')->where(['id'=>$this->user_id])->field('realname,avatar')->find();
            $data['user_id']=$this->user_id;
            $data['send_name']=$member_info['realname'];
            $data['send_id']=$this->user_id;
            $data['send_avatar']=$member_info['avatar'];
            $data['recive_name']="客服小小熊";
            $data['recive_id']=0;
            $data['recive_avatar']='https://dpic.tiankong.com/3w/h7/QJ6961899400.jpg?x-oss-process=style/670ws';
            $data['content']=htmlspecialchars(input('content/s'));
            $data['create_time']=time();
            $data['send_type']=1;
            $insertTalk=Db::name('customer_service')->insert($data);
            if($insertTalk){
                return $this->successResult("信息发出成功");
            }else{
                return $this->failResult("信息发出失败");
            }
        }

    }
    
 
}