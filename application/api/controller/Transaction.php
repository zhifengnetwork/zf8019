<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/5 0005
 * Time: 15:23
 */
namespace app\api\controller;

use app\common\controller\ApiAbstract;
use app\common\model\Member;
use app\common\util\jwt\JWT;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;

/**
 * 撮合交易类
 * Class Transaction
 * @package app\api\controller
 */
class Transaction extends ApiAbstract
{

    //支付状态 0未支付 1已支付 2部分支付 3已退款 4拒绝退款
    public $pay_status_already = 1;
    public $pay_status_cancel = 3;
    public $pay_status_un = 0;
    /**
     * 撮合交易处理
     * @param $order_id
     * @return array        'status' => 1 , 'msg'=>'发送成功！'   status 1:表示发送成功；-2 表示发送失败；
     */
    public function deal($order_id = 0){
        //根据时间；订单状态
        if(!empty($order_id)){
            $order = Db::name('order')->where(['order_id'=>$order_id,'pay_status'=>$this->pay_status_un])->select();
        }else{
            $order = Db::name('order')->where(['pay_status'=>$this->pay_status_un])->where('sure_time','<',time())->select();
        }
        if(!empty($order)){
            return ['status'=>0,'msg'=>'订单不存在'];
        }
        $result = 0;
        //系统自动确认完成支付
        Db::startTrans();
        foreach($order as $k=>$v){
            //更新订单状态
            $data['pay_status'] = $this->pay_status_already;
            $result = Db::name('order')->where(['order_id'=>$v['order_id']])->update($data);
            echo $result;die;
            if(!$result){
                Db::rollback();
                return ['status'=>0,'msg'=>'更新状态失败'];
                break;
            }
            //新增支付流水记录
            //***********  ************//
            //***********  ************//
            //***********  ************//

        }
        if($result){
            Db::commit();
            return ['status'=>1,'msg'=>'交易成功'];
        }else{
            Db::commit();
            return ['status'=>0,'msg'=>'无订单需要更新'];
        }

    }

    /**
     * 发送短信给买卖方
     * @param $buy_user_id 买方用户ID
     * @param $to_user_id 卖方用户ID
     * @return array 'status' => 1 , 'msg'=>'发送成功！'   status 1:表示发送成功；0 表示发送失败；
     */
    public function sendOrderSmsCode($buy_user_id = 0,$to_user_id = 0){
        if(empty($buy_user_id) || empty($to_user_id)){
            return ['status'=>0,'msg'=>'参数有误'];
        }
        $buyUser = Db::name('member')->where(['id'=>$buy_user_id])->field('mobile')->find();
        if($to_user_id){
            $sellerUser = Db::name('member')->where(['id'=>$to_user_id])->field('mobile')->find();
        }else{
            $info = Db::table('config')->where('module',5)->column('value','name');
            $sellerUser['mobile'] = $info['shop_mobile'];
        }
        if(empty($buyUser) || empty($sellerUser)){
            return ['status'=>0,'msg'=>'用户不存在'];
        }
        //买方发送短信
        $result = $this->sendTransactionPhoneCode($buyUser['mobile'], 1);
        if ($result['status'] != 1) {
            return ['status'=>0,'msg'=>'买方用户短信发送失败'];
        }
        //卖方发送短信
        $result = $this->sendTransactionPhoneCode($sellerUser['mobile'], 0);
        if ($result['status'] != 1) {
            return ['status'=>0,'msg'=>'卖方用户短信发送失败'];
        }

        return ['status'=>1,'msg'=>'短信发送成功'];
    }
}