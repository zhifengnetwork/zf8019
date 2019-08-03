<?php

/***
 * 支付api
 */
namespace app\api\controller;
use app\api\controller\TestNotify;
use app\common\controller\ApiBase;
use Payment\Common\PayException;
use Payment\Notify\PayNotifyInterface;
use Payment\Notify\AliNotify;
use Payment\Client\Charge;
use Payment\Client\Notify;
use Payment\Config as PayConfig;
use app\common\model\Member as MemberModel;
use app\common\model\Order;
use app\common\model\Sales;

use \think\Model;
use \think\Config;
use \think\Db;
use \think\Env;
use \think\Request;

class Pay extends ApiBase
{
     /**
     * 析构流函数
     */
    public function  __construct() {           
        require_once ROOT_PATH.'vendor/riverslei/payment/autoload.php';
    }    




    
  
      /**
     * @api {POST} /pay/set_payment 设置收款
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"",           token
     *      "type":''            当前操作的类型 1:码云  2:微信   3:支付宝
     *      "name":""              昵称
     *      "image":""           收款码图片
     *     
     *          
     *     操作对应的类型,填写对应的昵称和图片路径
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,"msg":"success","data":"操作成功"}
     * //错误返回结果
     * {"status":301,"msg":"fail","data":"操作失败"}
     * 无
     */
    public function set_payment()
    {
        $userid=$this->get_user_id();
        // $userid=42;
        if(!Request()->isPost()){
            return $this->failResult("请求方式错误");
        }
        $type=input('type');
        if(!$type){
            return $this->failResult("没有选择收款方式");
        }
        $data=input();
        $image = input('image');
        if(!$image){
            return $this->failResult('缺少图片参数');
        }
        $saveName = request()->time().rand(0,99999) . '.png';
        $imga=file_get_contents($image);
        //生成文件夹
        $names = "pay_picture" ;
        $name = "pay_picture/" .date('Ymd',time()) ;
        if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
            mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
        }
        file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$imga);
        $imgPath = Config('c_pub.apiimg') . $name.$saveName;
    
        if($type==1){
                $data['my_pic']=$imgPath;
                $data['pay_default']=3;
                $data['my_status']=1;
        }
        if($type==2){
                $data['wx_pic']=$imgPath;
               
                $data['pay_default']=2;
                $data['wx_status']=1;
        }
        if($type==3){
                $data['zfb_pic']=$imgPath;
               
                $data['pay_default']=1;
                $data['zfb_status']=1;
        }
        $data['user_id']=$userid;
        $data['create_time']=time();
        unset($data['image']);
        unset($data['type']);
        unset($data['token']);
        $one=Db::name('member_payment')->where('user_id',$userid)->find();
        if($one){
            $res=Db::name("member_payment")->where('user_id',$userid)->update($data);
        }else{
            $res=Db::name("member_payment")->insert($data);
        }
        if($res){
            return $this->successResult("操作成功");
        }else{
            return $this->failResult("操作失败");
        }

    }

   /**
     * @api {POST} /pay/get_user_payment  获取收款信息
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"",           token
     *   
     *     操作对应的类型,填写对应的昵称和图片路径
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,"msg":"success",
     * "data":{"pay_id":1,
     * "user_id":42,        //用户id
     * "my_name":"1111111", 
     * "my_pic":"http://newretail.com/upload/images/pay_picture/20190709156266206410296.png",          //码云闪付二维码
     *  "my_status":1,      是否已经设置   0为未设置   1为已经设置
     * "wx_name":"cxxxxxxhenchenchen11","wx_pic":"\\uploads\\pay_picture\\6f916d3c4c3805e6ea3578718af99a86.png",  //微信收款二维码
     * "wx_status":1,
     * "zfb_account":"chenchenchen11","zfb_pic":"uploads\\pay_picture\\3ca9c2151ec5e02dda500daa293ace47.png",   //支付宝收款二维码
     * "zfb_status":1,
     * "pay_default":3,     //默认收款码：0为未设置,1:支付宝 ,2:微信,3:码云闪付
     * "create_time":1562662065}}
     * //错误返回结果
     * {"status":301,"msg":"fail","data":"操作失败"}
     * 无
     */
    public function get_user_payment()
    {
        $userid=$this->get_user_id();
        // $userid=42;
        if(!Request()->isPost()){
            return $this->failResult("请求方式错误");
        }
        $user_payment=Db::name("member_payment")->where("user_id",'=',$userid)->find();
        return $this->successResult($user_payment);
    }




    /***
     * 支付
     */
    public function payment(){
        $order_id     = input('order_id');
        $pay_type     = input('pay_type',0);//支付方式
        $pwd          = input('pwd','');
        $user_id      = $this->get_user_id();

        $order_info   = Db::name('order')->where(['order_id' => $order_id])->field('order_id,groupon_id,order_sn,order_amount,pay_type,pay_status,user_id,shipping_price')->find();//订单信息
    
        $member       = MemberModel::get($user_id);
        //验证是否本人的
        if(!$order_info){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'订单不存在','data'=>'']);
        }
        if($order_info['user_id'] != $user_id){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'非本人订单','data'=>'']);
        }

    	if($order_info['pay_status'] == 1){
			$this->ajaxReturn(['status' => 301 , 'msg'=>'此订单，已完成支付!','data'=>'']);
        }
        $amount       = $order_info['order_amount'];
        $client_ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

        if($pay_type == 3){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'已关闭','data'=> '']);
            $payData['order_no']        = $order_info['order_sn'];
            $payData['body']            = '';
            $payData['timeout_express'] = time() + 600;
            $payData['amount']          = $amount;
            $payData['subject']         = '支付宝支付';
            $payData['goods_type']      = 1;//虚拟还是实物
            $payData['return_param']    = '';
            $payData['store_id']        = '';
            $payData['quit_url']        = '';
            $pay_config = Config::get('pay_config');
            $web_url    = Db::table('site')->value('web_url');
            $pay_config['return_url'] = $web_url.'/order?type=2';
            $url        = Charge::run(PayConfig::ALI_CHANNEL_WAP, $pay_config, $payData);
            try {
                $this->ajaxReturn(['status' => 200 , 'msg'=>'支付路径','data'=> ['url' => $url]]);
            } catch (PayException $e) {
                $this->ajaxReturn(['status' => 301 , 'msg'=>$e->errorMessage(),'data'=>'']);
                exit;
            }
        }elseif($pay_type == 2){
            $payData = [
                'body'        => '测试',
                'subject'     => '测试',
                'order_no'    =>  $order_info['order_sn'],
                'timeout_express' => time() + 600,// 表示必须 600s 内付款
                'amount'       => $amount,// 金额
                'return_param' => '',
                'client_ip'    => $this->get_client_ip(),// 客户地址
                'scene_info' => [
                    'type'     => 'Wap',// IOS  Android  Wap  腾讯建议 IOS  ANDROID 采用app支付
                    'wap_url'  => 'http://www.puruitingxls.com/',//自己的 wap 地址
                    'wap_name' => '微信支付',
                ],
            ];
            $web_url    = Db::table('site')->value('web_url');
           

            $wxConfig = Config::get('wx_config');
            $wxConfig['redirect_url'] = $web_url.'/Order/OrderDetails?order_id='.$order_id;
            $url      = Charge::run(PayConfig::WX_CHANNEL_WAP, $wxConfig, $payData);
            try {
                $this->ajaxReturn(['status' => 200 , 'msg'=>'支付路径','data'=> ['url' => $url]]);
            } catch (PayException $e) {
                $this->ajaxReturn(['status' => 301 , 'msg'=>$e->errorMessage(),'data'=>'']);
                exit;
            }
        }elseif($pay_type == 1){
            if(empty($pwd)){
                $this->ajaxReturn(['status' => 301 , 'msg'=>'支付密码不能为空！','data'=>'']);
            }

            if(empty($member['pwd'])){
                $this->ajaxReturn(['status' => 888 , 'msg'=>'支付密码未设置','data'=>'']);
            }

            $pwd = md5($member['salt'] . $pwd);
            if ($pwd != $member['pwd']) {
                $this->ajaxReturn(['status' => 301 , 'msg'=>'支付密码错误！','data'=>'']);
            }
            // $balance_info  = get_balance($user_id,0);
            if($member['remainder_money'] < $order_info['order_amount']){
                $this->ajaxReturn(['status' => 308 , 'msg'=>'余额不足','data'=>'']);
            }
            // 启动事务
            Db::startTrans();

            //判断是否能进入50元专区

            $goods_ids = Db::table('order_goods')->where('order_id',$order_id)->column('goods_id');
            $is_gift   = Db::table('goods')->where('goods_id','in',$goods_ids)->where('is_gift',1)->count();

            if($is_gift > 0){
                $remainder_money['is_release']     = 1;
            }
            //扣除用户余额
            $remainder_money['remainder_money'] = Db::raw('remainder_money-'.$amount.'');
           
         
            $res =  Db::table('member')->where(['id' => $user_id])->update($remainder_money);
            if(!$res){
                Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>'余额不足','data'=>'']);
            }
            //余额记录
            $balance_log = [
                'user_id'      => $user_id,
                'balance'      => $member['remainder_money'] - $order_info['order_amount'],
                'source_type'  => 1,
                'log_type'     => 0,
                'source_id'    => $order_info['order_sn'],
                'note'         => '商品订单消费',
                'create_time'  => time(),
                'old_balance'  => $member['remainder_money']
            ];
            $res2 = Db::table('menber_balance_log')->insert($balance_log);
            if(!$res2){
                Db::rollback();
            }
            //修改订单状态
            $update = [
                'order_status' => 1,
                'pay_status'   => 1,
                'pay_type'     => $pay_type,
                'pay_time'     => time(),
            ];
            $reult = Order::where(['order_id' => $order_id])->update($update);

            $goods_res = Db::table('order_goods')->field('goods_id,goods_name,goods_num,spec_key_name,goods_price,sku_id')->where('order_id',$order_id)->select();
           

            $jifen = 0;
            $fenyong_money = 0;
            foreach($goods_res as $key=>$value){

                $goods = Db::table('goods')->where('goods_id',$value['goods_id'])->field('less_stock_type,gift_points,is_distribution')->find();
                
                if($goods['is_distribution']){
                    $price = Db::table('goods_sku')->where('sku_id',$value['sku_id'])->value('price');
                    $fenyong_money = sprintf("%.2f",$fenyong_money + ($value['goods_num'] * $price));
                }
                //付款减库存
                if($goods['less_stock_type']==2){
                    Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('inventory',$value['goods_num']);
                    Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('frozen_stock',$value['goods_num']);
                    // Db::table('goods')->where('goods_id',$value['goods_id'])->setDec('stock',$value['goods_num']);
                }
                $baifenbi = strpos($goods['gift_points'] ,'%');
                if($baifenbi){
                    $goods['gift_points'] = substr($goods['gift_points'],0,strlen($goods['gift_points'])-1); 
                    $goods['gift_points'] = $goods['gift_points'] / 100;
                    $jg    = sprintf("%.2f",$value['goods_price'] * $value['goods_num']);
                    $jifen = sprintf("%.2f",$jifen + ($jg * $goods['gift_points']));
                }else{
                    $goods['gift_points'] = $goods['gift_points'] ? $goods['gift_points'] : 0;
                    $jifen = sprintf("%.2f",$jifen + ($value['goods_num'] * $goods['gift_points']));
                }
            }

            
            $fenyong_money = sprintf("%.2f",$fenyong_money + $order_info['shipping_price']);

            if($fenyong_money > 0){
                $Sales = new Sales($user_id,$order_id,0);
                $res = $Sales->reward_leve($user_id,$order_id,$order_info['order_sn'],$fenyong_money,0);
                if($res === false){
                    Db::rollback();
                    $this->ajaxReturn(['status' => 301 , 'msg'=>'余额支付失败1','data'=>'']);
                }
            }
            // $res = Db::table('member')->update(['id'=>$user_id,'gouwujifen'=>$jifen]);

            if($reult){
                // 提交事务
                Db::commit();
                $this->ajaxReturn(['status' => 200 , 'msg'=>'余额支付成功!','data'=>['order_id' =>$order_info['order_id'],'order_amount' =>$order_info['order_amount'],'goods_name' => getPayBody($order_info['order_id']),'order_sn' => $order_info['order_sn'] ]]);
            }else{
                 Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>'余额支付失败2','data'=>'']);
            }
        }
       
    }

    public function release_wx_pay(){
        $user_id = $this->get_user_id();
        $pay_type     = input('pay_type',1);//支付方式
        $pwd          = input('pwd');

        $ss = Db::table('config')->where('module',5)->where('name','release_money')->value('value');//金额

        $user = MemberModel::where('id',$user_id)->field('is_vip,release,residue_release,release_ci,release_time,remainder_money,salt,pwd,first_leader')->find();
        $time = strtotime(date("Y-m-d"),time());
        
        if(!$user['residue_release'] && $user['release'] == $user['release_ci'] &&  $time == $user['release_time'] ){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'今天发布次数已用完！','data'=>'']);
        }
        
        if( !$user['residue_release'] && $time > $user['release_time'] ){
            MemberModel::where('id',$user_id)->update(['release_time'=>$time,'release_ci'=>0]);
        }


        if($user['residue_release']){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'今天发布次数还未用！','data'=>'']);
        }
        
        $orderNo = time() . 'release' . rand(1000, 9999);
        $web_url    = Db::table('site')->value('web_url');
        if($pay_type==1){
            $pwd = md5($user['salt'] . $pwd);

            if($user['remainder_money'] < $ss){
                $this->ajaxReturn(['status' => 308 , 'msg'=>'余额不足！','data'=>'']);
            }

            if($pwd != $user['pwd']){
                  $this->ajaxReturn(['status' => 301 , 'msg'=>'支付密码错误！','data'=>'']);
            }

            $insert = [
                'order_sn' => $orderNo,
                'user_id'  => $user_id,
                'amount'   => $ss,
                'source'   => $pay_type,
                'order_status' => 1,
                'create_time'  => time(),
                'pay_time'     => time(),
            ];
            $res = Db::name('fifty_order')->insert($insert);
            if($res == false){
                $this->ajaxReturn(['status' => 301 , 'msg'=>'下单失败!','data'=>'']);
            }

            if(!$user['is_vip']){
                Db::table('member')->where('id',$user_id)->setInc('is_vip');

                if($user['first_leader']){
                    Db::table('member')->where('id',$user['first_leader'])->setInc('release');
                }
            }

            // 启动事务
            Db::startTrans();

            $res = Db::table('member')->where('id',$user_id)->setDec('remainder_money',$ss);
            
            // $res1 = Db::table('member')->where('id',$user_id)->setInc('residue_release');
            if($res){
                //记录
                $log['user_id']     = $user_id;
                $log['balance']     = $user['remainder_money'] - $ss;//现有余额
                $log['old_balance'] = $user['remainder_money'];      //原有余额
                $log['source_type'] = 2;
                $log['log_type']    = 0;
                $log['source_id']   = $orderNo;
                $log['note']        = '平台服务费用-余额';
                $log['create_time'] = time();

                Db::table('menber_balance_log')->insert($log);

                $Sales = new Sales($user_id,$orderNo,0);

                $rest  = $Sales->rewardtame($user_id,$ss,0,$orderNo);
                if($rest === false){
                    Db::rollback();
                    $this->ajaxReturn(['status' => 301 , 'msg'=>'分佣失败2!','data'=>'']);
                }

                $rest = $Sales->reward_fifty($user_id,$orderNo,$orderNo,$ss);
                if($rest === false){
                    Db::rollback();
                    $this->ajaxReturn(['status' => 301 , 'msg'=>'分佣失败1!','data'=>'']);
                }
              
                Db::table('member')->where('id',$user_id)->setInc('release_ci');
                Db::table('fifty_zone_order')->where('user_id',$user_id)->where('is_show',0)->update(['is_show'=>1]);
                Db::commit();
                $this->ajaxReturn(['status' => 310 , 'msg'=>'付款成功!','data'=>'']);
            }
            Db::rollback();
            $this->ajaxReturn(['status' => 301 , 'msg'=>'付款失败!','data'=>'']);
        }elseif($pay_type==2){
            Db::startTrans();
            $insert = [
                'order_sn' => $orderNo,
                'user_id'  => $user_id,
                'amount'   => $ss,
                'source'   => $pay_type,
                'order_status' => 0,
                'create_time'  => time(),
            ];
            $res = Db::name('fifty_order')->insert($insert);
            if($res == false){
                 Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>'下单失败!','data'=>'']);
            }
            $payData = [
                'body'        => '测试',
                'subject'     => '微信支付',
                'order_no'    =>  $orderNo,
                'timeout_express' => time() + 600,// 表示必须 600s 内付款
                'amount'       => $ss,// 金额
                'return_param' => '',
                'client_ip'    =>  $this->get_client_ip(),// 客户地址
                'scene_info'   => [
                    'type'     => 'Wap',// IOS  Android  Wap  腾讯建议 IOS  ANDROID 采用app支付
                    'wap_url'  => $web_url.'/Payment',//自己的 wap 地址
                    'wap_name' => '微信支付',
                ],
            ];
           
            
            $wxConfig = Config::get('wx_config');
            // $wxConfig['redirect_url'] = $web_url.'/Payment';
            $url      = Charge::run(PayConfig::WX_CHANNEL_WAP, $wxConfig, $payData);
            try {
                Db::commit();
                $this->ajaxReturn(['status' => 311 , 'msg'=>'支付路径','data'=> ['url' => $url]]);
            } catch (PayException $e) {
                Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>$e->errorMessage(),'data'=>'']);
                exit;
            };

        }elseif($pay_type == 3){//支付宝支付

         $this->ajaxReturn(['status' => 301 , 'msg'=>'已关闭','data'=> '']);
          Db::startTrans();
            $insert = [
                'order_sn' => $orderNo,
                'user_id'  => $user_id,
                'amount'   => $ss,
                'source'   => $pay_type,
                'order_status' => 0,
                'create_time'  => time(),
            ];
            $res = Db::name('fifty_order')->insert($insert);
            if( $res == false){
                 Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>'下单失败!','data'=>'']);
            }
            $payData['order_no']        = $orderNo;
            $payData['body']            = '';
            $payData['timeout_express'] = time() + 600;
            $payData['amount']          = $ss;
            $payData['subject']         = '支付宝支付';
            $payData['goods_type']      = 1;//虚拟还是实物
            $payData['return_param']    = '';
            $payData['store_id']        = '';
            $payData['quit_url']        = '';
            $pay_config = Config::get('pay_config');
        
            $pay_config['return_url'] = $web_url.'/Payment';
            $url        = Charge::run(PayConfig::ALI_CHANNEL_WAP, $pay_config, $payData);
            try {
                Db::commit();
                $this->ajaxReturn(['status' => 200 , 'msg'=>'支付路径','data'=> ['url' => $url]]);
            } catch (PayException $e) {
                Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>$e->errorMessage(),'data'=>'']);
                exit;
            }

        }
        
    }


    
public function get_client_ip() {
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
}



    public function recharge_pay(){
        $user_id = $this->get_user_id();
        $pay_type     = input('pay_type',3);//支付方式
        $money        = input('money/f',0);
       
        if(!preg_match("/^\d+(\.\d+)?$/",$money))$this->failResult('请输入正确的金额！', 301);


        $ss = Db::table('config')->where('module',5)->where('name','release_money')->value('value');//最大充值金额

        if($money > 1000){
            $this->failResult('超过最大金额！', 301);
        }
        
        $orderNo = time() . 'recharge' . rand(1000, 9999);

        if($pay_type==2){
            
            $payData = [
                'body'        => '微信充值',
                'subject'     => '微信充值',
                'order_no'    =>  $orderNo,
                'timeout_express' => time() + 600,// 表示必须 600s 内付款
                'amount'       => $money,// 金额
                'return_param' => '',
                'client_ip'    => $this->get_server_ip(),// 客户地址
                'scene_info' => [
                    'type'     => 'Wap',// IOS  Android  Wap  腾讯建议 IOS  ANDROID 采用app支付
                    'wap_url'  => '',//自己的 wap 地址
                    'wap_name' => '微信支付',
                ],
            ];
            // var_dump($this->getIp());
            // die;
            $wxConfig = Config::get('wx_config');
            $url      = Charge::run(PayConfig::WX_CHANNEL_WAP, $wxConfig, $payData);
            try {
                $this->ajaxReturn(['status' => 200 , 'msg'=>'支付路径','data'=> ['url' => $url]]);
            } catch (PayException $e) {
                $this->ajaxReturn(['status' => 301 , 'msg'=>$e->errorMessage(),'data'=>'']);
                exit;
            };

        }elseif($pay_type == 3){//支付宝支付
          $this->ajaxReturn(['status' => 301 , 'msg'=>'已关闭','data'=> '']);
          Db::startTrans();
            $insert = [
                'order_sn' => $orderNo,
                'user_id'  => $user_id,
                'amount'   => $money,
                'source'   => $pay_type,
                'order_status' => 0,
                'create_time'  => time(),
            ];
            $res = Db::name('recharge_order')->insert($insert);
            if( $res == false){
                Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>'充值失败!','data'=>'']);
            }
            $payData['order_no']        = $orderNo;
            $payData['body']            = '余额充值';
            $payData['timeout_express'] = time() + 600;
            $payData['amount']          = $money;
            $payData['subject']         = '支付宝支付';
            $payData['goods_type']      = 1;//虚拟还是实物
            $payData['return_param']    = '';
            $payData['store_id']        = '';
            $payData['quit_url']        = '';
            $pay_config = Config::get('pay_config');
            $web_url    = Db::table('site')->value('web_url');
            $pay_config['return_url'] = $web_url.'/Sell';
            $url        = Charge::run(PayConfig::ALI_CHANNEL_WAP, $pay_config, $payData);
            try {
                Db::commit();
                $this->ajaxReturn(['status' => 200 , 'msg'=>'支付路径','data'=> ['url' => $url]]);
            } catch (PayException $e) {
                Db::rollback();
                $this->ajaxReturn(['status' => 301 , 'msg'=>$e->errorMessage(),'data'=>'']);
                exit;
            }

        }
        
    }


    /***
     * 微信支付回调
     */
    public function weixin_notify(){
        $callback = new TestNotify();
        $config   = Config::get('wx_config');
        $ret      = Notify::run('wx_charge', $config, $callback);
        echo  $ret;
    }

     /***
     * 支付宝回调
     */
    public function alipay_notify(){
        $callback = new TestNotify();
        $config   = Config::get('pay_config');
        $ret      = Notify::run('ali_charge', $config, $callback);
        echo  $ret;
    }


}
