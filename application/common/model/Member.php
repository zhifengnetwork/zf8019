<?php
namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Member extends Model
{
    protected $updateTime = false;

    protected $autoWriteTimestamp = true;

    /***
     * 充值积分and余额
     */
    public static function setBalance($uid = '',$type = '',  $num = 0, $data = array()){
            $balance_info  = get_balance($uid,$type);
            $dephp_11      = $balance_info['balance'] + $num;

            Db::name('member_balance')->where(['user_id' => $uid,'balance_type' => $balance_info['balance_type']])->update(['balance' => $dephp_11]);
           
            $dephp_12 = array('user_id' => $uid, 'balance_type' => $balance_info['balance_type'], 'old_balance' => $balance_info['balance'], 'balance' => $dephp_11,'create_time' => time(), 'account_id' => intval($data[0]), 'note' => $data[1]);
            Db::name('menber_balance_log')->insert($dephp_12);
    }

    public static function getBalance($uid = '',$type = ''){
        $balance  = Db::name('member_balance')->where(['user_id' => $uid ,'balance_type' => $type])->value('balance');
        return $balance;
    }

  
    public static  function getLevels(){
        $Leve = Db::table('member_level')->order('level')->select();
        return $Leve;
    }

  
    public static function getGroups(){
        $Group = Db::table('member_group')->order('id')->select();
        return $Group;
    }
    public static function getGroup($dephp_0){
        if (empty($dephp_0)){
            return false;
        }
        $dephp_7 = self::getMember($dephp_0);
        return $dephp_7['groupid'];
    }
    /**
     * 绑定上下级关系
     */
    public static function doLeader($first_leader){
         // 如果找到他老爸还要找他爷爷他祖父等
         if ($first_leader) {
            $info = Db::name('users')->where(['user_id' => $first_leader])->find();
            $map['first_leader']   = $first_leader;
            $map['second_leader']  = $info['first_leader']; //  第一级推荐人
            $map['third_leader']   = $info['second_leader']; // 第二级推荐人
        } else {
            $map['first_leader'] = 0;
        }
    }


     /**
     * 用户一级下线数
     * @param $value
     * @param $data
     * @return mixed
     */
     public static function getFisrtLeaderNumAttr($user_id){

        $where['first_leader'] = $user_id;
        $fisrt_leader = self::where(['first_leader'=>$user_id,'is_vip' => 1])->count();
        return  $fisrt_leader;
    }

    /**
     * 用户二级下线数
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getSecondLeaderNumAttr($value, $data){
        $second_leader = $this->where(['second_leader'=>$data['user_id']])->count();
        return  $second_leader;
    }

    /**
     * 用户三级下线数
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getThirdLeaderNumAttr($value, $data){
        $third_leader = $this->where(['third_leader'=>$data['user_id']])->count();
        return  $third_leader;
    }
 

    
}
