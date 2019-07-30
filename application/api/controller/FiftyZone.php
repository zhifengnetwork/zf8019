<?php
/**
 * 50元专区
 */
namespace app\api\controller;


use app\common\controller\ApiBase;
use think\Db;
use think\Config;
use app\common\model\Sales;

class FiftyZone extends ApiBase
{

    public function shop_list(){
        $user_id = $this->get_user_id();

        $info = Db::table('config')->where('module',5)->column('value','name');
        if(isset($info['ailicode'])) $info['ailicode'] = Config('c_pub.apiimg') . $info['ailicode'];
        if(isset($info['wechatcode'])) $info['wechatcode'] = Config('c_pub.apiimg') . $info['wechatcode'];
        if(isset($info['mayuncode'])) $info['mayuncode'] = Config('c_pub.apiimg') . $info['mayuncode'];
        if($info['default_type']==1) $info['default_pay_code'] = $info['ailicode'];
        if($info['default_type']==2) $info['default_pay_code'] = $info['wechatcode'];
        if($info['default_type']==3) $info['default_pay_code'] = $info['mayuncode'];
        
        if(isset($info['is_open']) && $info['is_open']){
            $start = strtotime($info['start_open_time']);
            $end = strtotime($info['end_open_time']);
            $time = time();
            if($start > $time || $end < $time){
                $this->ajaxReturn(['status' => 301 , 'msg'=>'不是开放时间！','data'=>'']);
            }
        }elseif(isset($info['is_open']) && !$info['is_open']){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'不是开放时间！','data'=>'']);
        }

        $is_release = Db::table('member')->where('id',$user_id)->value('is_release');
        if(!$is_release){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'需要购买礼品专区的商品才能参与50元专区！','data'=>'']);
        }

        // if($this->fifty_order(1)){
        //     $this->ajaxReturn(['status' => 304 , 'msg'=>'还有未付款的订单！','data'=>'']);
        // }

        $user = Db::table('member')->field('release,residue_release,release_ci,release_time')->find($user_id);

        $time = strtotime(date("Y-m-d"),time());

        // if(!$user['residue_release'] && $user['release'] == $user['release_ci'] && $user['release_time'] == $time ){
        //     $this->ajaxReturn(['status' => 301 , 'msg'=>'今天发布次数已用完！','data'=>'']);
        // }

        // if( (!$user['residue_release'] && $time > $user['release_time']) || !$user['residue_release'] ){
        //     $this->ajaxReturn(['status' => 302 , 'msg'=>'是否缴纳30元服务费！','data'=>'']);
        // }

        $goods_id = 50;

        $where['g.goods_id'] = $goods_id;
        // $where['gi.main'] = 1;
        $where['fzs.stock']   = ['neq',0];
        $where['fzs.is_show'] = 1;
        $where1['fzs.user_id'] = ['neq',$user_id];

        $num = Db::table('fifty_zone_shop')->count();
        // if($num >= 10){
        //    $where['fzs.user_id'] = ['neq',0];
        // }

        $list = Db::table('fifty_zone_shop')->alias('fzs')
                ->join('goods g','g.goods_id=fzs.goods_id','LEFT')
                ->join('fifty_zone_img fzi','fzs.img_id=fzi.id','LEFT')
                // ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->join('member m','m.id=fzs.user_id','LEFT')
                ->field('fzs.*,g.goods_name,fzi.picture img,m.mobile,m.realname')
                ->where($where)
                ->where($where1)
                ->order('fzs.add_time ASC')
                ->limit($info['show_num'])
                ->select();
        if(empty($list) && !$num){
            $img_arr = Db::table('fifty_zone_img')->column('id');
            $arr = [];
            for($i=0;$i<10;$i++){
                $arr[$i]['user_id'] = 0;
                $arr[$i]['goods_id'] = $goods_id;
                $arr[$i]['stock'] = 11;
                $arr[$i]['frozen_stock'] = 0;
                $arr[$i]['add_time'] = time();
                $arr[$i]['img_id'] = $img_arr[array_rand($img_arr)];
                $arr[$i]['is_show'] = 1;
            }
            Db::table('fifty_zone_shop')->insertAll($arr);
            $list = Db::table('fifty_zone_shop')->alias('fzs')
                ->join('goods g','g.goods_id=fzs.goods_id','LEFT')
                ->join('fifty_zone_img fzi','fzs.img_id=fzi.id','LEFT')
                // ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->join('member m','m.id=fzs.user_id','LEFT')
                ->field('fzs.*,g.goods_name,fzi.picture img,m.mobile,m.realname')
                ->where($where)
                ->where($where1)
                ->order('fzs.add_time ASC')
                ->limit($info['show_num'])
                ->select();
        }

        foreach($list as $key=>&$value){
                $value['img']      = Config('c_pub.apiimg') . $value['img'];
            if(!$value['user_id']){
                $value['mobile']   = $info['shop_mobile'];
                $value['realname'] = '自营商家';
                $value['pay_code'] = $info['default_pay_code'];
            }else{
                $payment = Db::table('member_payment')->where('user_id',$value['user_id'])->field('my_pic,wx_pic,zfb_pic,pay_default')->find();
                if($payment == 1){
                    $value['pay_code'] = $payment['zfb_pic'];
                }elseif($payment == 2){
                    $value['pay_code'] = $payment['wx_pic'];
                }elseif($payment == 3){
                    $value['pay_code'] = $payment['my_pic'];
                }else{
                    $value['pay_code'] = '';
                }
            }
        }
        
        $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>$list]);
    }
    
    public function get_release(){
        $user_id = $this->get_user_id();

        $data = Db::table('member')->where('id',$user_id)->field('remainder_money,pwd')->find();
        if(!$data['pwd']){
            $data['pwd'] = 0;
        }else{
            $data['pwd'] = 1;
        }

        $data['release_money'] = Db::table('config')->where('module',5)->where('name','release_money')->value('value');

        $pay = Db::table('sysset')->value('sets');
        $pay = unserialize($pay)['pay'];

        $pay_type = Config('PAY_TYPE');
        $arr = [];
        $i = 0;
        foreach($pay as $key=>$value){
            
            if($value){
                if( $pay_type[$key]['pay_type'] == 4 ) continue;
                $arr[$i]['pay_type'] = $pay_type[$key]['pay_type'];
                $arr[$i]['pay_name'] = $pay_type[$key]['pay_name'];
                $i++;
            }
        }

        $data['pay_type'] = $arr;

        $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>$data]);
    }

    public function fiftySubmitOrder(){
        $user_id = $this->get_user_id();

        $res = Db::table('fifty_zone_shop')->where('user_id',$user_id)->where('stock','gt',0)->where('is_show',1)->find();
        if($res){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'您发布的商品还未卖完！','data'=>'']);
        }

        $info = Db::table('config')->where('module',5)->column('value','name');
        if(isset($info['ailicode'])) $info['ailicode'] = Config('c_pub.apiimg') . $info['ailicode'];
        if(isset($info['wechatcode'])) $info['wechatcode'] = Config('c_pub.apiimg') . $info['wechatcode'];
        if(isset($info['mayuncode'])) $info['mayuncode'] = Config('c_pub.apiimg') . $info['mayuncode'];
        if($info['default_type']==1) $info['default_pay_code'] = $info['ailicode'];
        if($info['default_type']==2) $info['default_pay_code'] = $info['wechatcode'];
        if($info['default_type']==3) $info['default_pay_code'] = $info['mayuncode'];
        
        if(isset($info['is_open']) && $info['is_open']){
            $start = strtotime($info['start_open_time']);
            $end = strtotime($info['end_open_time']);
            $time = time();
            if($start > $time || $end < $time){
                $this->ajaxReturn(['status' => 301 , 'msg'=>'不是开放时间！','data'=>'']);
            }
        }elseif(isset($info['is_open']) && !$info['is_open']){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'不是开放时间！','data'=>'']);
        }

        $user = Db::table('member')->field('release,residue_release,release_ci,release_time')->find($user_id);

        $time = strtotime(date("Y-m-d"),time());

        if($this->fifty_order(1)){
            $this->ajaxReturn(['status' => 304 , 'msg'=>'还有未付款的订单！','data'=>'']);
        }

        if(!$user['residue_release'] && $user['release'] == $user['release_ci'] && $user['release_time'] == $time ){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'今天发布次数已用完！','data'=>'']);
        }
        
        if((!$user['residue_release'] && $time > $user['release_time']) || !$user['residue_release'] ){
            // $this->ajaxReturn(['status' => 302 , 'msg'=>'是否缴纳30元服务费！','data'=>'']);
            Db::table('fifty_zone_shop')->where('user_id',$user_id)->where('is_show',0)->delete();
            $res = Db::table('fifty_zone_order')->where('user_id',$user_id)->where('is_show',0)->column('fz_id');
            foreach($res as $key=>$value){
                Db::table('fifty_zone_shop')->where('fz_id',$value)->setInc('stock');
            }

            Db::table('fifty_zone_order')->where('user_id',$user_id)->where('is_show',0)->delete();
        }else{
            $my_payment = Db::table('member_payment')->where('user_id',$user_id)->find();
            if(!$my_payment['my_pic'] && !$my_payment['wx_pic'] && !$my_payment['zfb_pic']){
                $this->ajaxReturn(['status' => 309 , 'msg'=>'请先上传收款码！','data'=>'']);
            }
        } 
        
        
           

        $num = 10;
        
        $fz_ids = input('fz_id');

        if(!$fz_ids){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'请选择商品！','data'=>'']);
        }
        $fz_id = explode(',',$fz_ids);

        $fz_id = array_unique($fz_id);

        if( count($fz_id) != $num ){
            $this->ajaxReturn(['status' => 301 , 'msg'=>"请选择{$num}商品！",'data'=>'']);
        }

        $res = Db::table('fifty_zone_shop')->where('fz_id','in',$fz_ids)->where('user_id',$user_id)->field('fz_id,img_id,goods_id,user_id,stock')->select();
        if($res){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'不能购买自己的商品！','data'=>'']);
        }

        $list = Db::table('fifty_zone_shop')->where('fz_id','in',$fz_ids)->field('fz_id,img_id,goods_id,user_id,stock')->select();

        if(!$list){
            $this->ajaxReturn(['status' => 301 , 'msg'=>"请选择商品！",'data'=>'']);
        }

        
        $arr = [];
        $time = time();
        // $res2 = Db::table('order')->insertGetId(['order_sn'=>date('YmdHis',$time) . mt_rand(10000000,99999999),'user_id'=>$user_id,'prom_type'=>5,'add_time'=>$time]);
        
        foreach($list as $key=>$value){
            if($value['user_id'] == $user_id){
                $this->ajaxReturn(['status' => 301 , 'msg'=>"不能购买自己的商品！",'data'=>'']);
            }
            if(!$value['stock']){
                $this->ajaxReturn(['status' => 301 , 'msg'=>"个别商家的商品已没库存，请刷新列表！",'data'=>'']);
            }
            $arr[$key]['fz_id']        = $value['fz_id'];
            $arr[$key]['img_id']       = $value['img_id'];
            $arr[$key]['order_sn']     = date('YmdHis',$time) . mt_rand(10000000,99999999);
            $arr[$key]['user_id']      = $user_id;
            $arr[$key]['shop_user_id'] = $value['user_id'];
            $arr[$key]['goods_id']     = $value['goods_id'];
            $arr[$key]['goods_num']    = 1;
            $arr[$key]['add_time']     = $time;
            $arr[$key]['sure_time']    = $time + ($info['auto_cancel'] * 60);
        }

        
        //开启事务
        Db::startTrans();
        
        $res = Db::table('fifty_zone_order')->insertAll($arr);

        if($res){
            foreach($arr as $key=>$value){
                if( !Db::table('fifty_zone_shop')->where('fz_id',$value['fz_id'])->setDec('stock') ){
                    Db::rollback();
                    $this->ajaxReturn(['status' => 301 , 'msg'=>"系统错误！",'data'=>'']);
                }
            }

            $res1 = Db::table('fifty_zone_shop')->insert(['user_id'=>$user_id,'img_id'=>$arr[array_rand($arr)]['img_id'],'goods_id'=>$arr[0]['goods_id'],'stock'=>count($arr)+1,'add_time'=>$time]);

            
            $release = Db::table('member')->where('id',$user_id)->field('release_ci,if_buy_fifty')->find();
            Db::table('member')->where('id',$user_id)->update(['if_buy_fifty'=>1]);

            if($res1){
                Db::commit();
                if((!$user['residue_release'] && $time > $user['release_time']) || !$user['residue_release'] ){
                    $this->ajaxReturn(['status' => 302 , 'msg'=>'是否缴纳30元服务费！','data'=>'']);
                }
                $this->ajaxReturn(['status' => 200 , 'msg'=>"成功！",'data'=>'']);
            }

            Db::rollback();
            $this->ajaxReturn(['status' => 301 , 'msg'=>"系统错误！",'data'=>'']);
        }
    }

    public function fifty_order($status=0){
        $user_id = $this->get_user_id();

        $goods_id = 50;

        $info = Db::table('config')->where('module',5)->column('value','name');
        if(isset($info['ailicode'])) $info['ailicode'] = Config('c_pub.apiimg') . $info['ailicode'];
        if(isset($info['wechatcode'])) $info['wechatcode'] = Config('c_pub.apiimg') . $info['wechatcode'];
        if(isset($info['mayuncode'])) $info['mayuncode'] = Config('c_pub.apiimg') . $info['mayuncode'];
        if($info['default_type']==1) $info['default_pay_code'] = $info['ailicode'];
        if($info['default_type']==2) $info['default_pay_code'] = $info['wechatcode'];
        if($info['default_type']==3) $info['default_pay_code'] = $info['mayuncode'];

        $list = Db::table('fifty_zone_order')->where('user_id',$user_id)->where('is_show',1)->field('fz_order_id,order_id,shop_user_id,goods_id,user_confirm,shop_confirm')->order('add_time DESC')->limit(10)->select();

        if($list){
            $flag = false;
            foreach($list as $key=>&$value){
                if(!$value['user_confirm']){
                    if($status){
                        return $status;
                    }
                    $falg = true;
                }else if(!$value['shop_confirm']){
                    if($status){
                        $this->ajaxReturn(['status' => 301 , 'msg'=>"还有商家未确认完的订单，不能下单！",'data'=>'']);
                    }
                }

                if(!$value['shop_user_id']){
                    $value['my_pic'] = $info['mayuncode'];
                    $value['wx_pic'] = $info['wechatcode'];
                    $value['zfb_pic'] = $info['ailicode'];
                    $value['mobile'] = $info['shop_mobile'];
                    $value['shop_name'] = '自营商家';
                }else{
                    $temp = Db::table('member_payment')->where('user_id',$value['shop_user_id'])->field('my_pic,wx_pic,zfb_pic')->find();
                    $value['my_pic'] = $temp['my_pic'];
                    $value['wx_pic'] = $temp['wx_pic'];
                    $value['zfb_pic'] = $temp['zfb_pic'];
                    $member =  Db::name('member')->where('id',$value['shop_user_id'])->field('mobile,realname')->find();
                    $value['mobile'] = $member['mobile'];
                    $value['shop_name'] = $member['realname'];
                }
                $goods = Db::table('goods')->alias('g')->join('goods_img gi','g.goods_id=gi.goods_id','LEFT')->where('gi.main',1)->where('g.goods_id',$goods_id)->field('g.goods_name,gi.picture img')->find();
                $value['goods_name'] = $goods['goods_name'];
                $value['img'] = Config('c_pub.apiimg') . $goods['img'];
            }

            if($flag){
                $this->ajaxReturn(['status' => 200 , 'msg'=>"成功！",'data'=>[]]);
            }

            // foreach($list as $key=>&$value){
            //     if(!$value['shop_user_id']){
            //         $value['my_pic'] = $info['mayuncode'];
            //         $value['wx_pic'] = $info['wechatcode'];
            //         $value['zfb_pic'] = $info['ailicode'];
            //         $value['mobile'] = $info['shop_mobile'];
            //         $value['shop_name'] = '自营商家';
            //     }else{
            //         $temp = Db::table('member_payment')->where('user_id',$value['shop_user_id'])->field('my_pic,wx_pic,zfb_pic')->find();
            //         $value['my_pic'] = $temp['my_pic'];
            //         $value['wx_pic'] = $temp['wx_pic'];
            //         $value['zfb_pic'] = $temp['zfb_pic'];
            //         $member =  Db::name('member')->where('id',$value['shop_user_id'])->field('mobile,realname')->find();
            //         $value['mobile'] = $member['mobile'];
            //         $value['shop_name'] = $member['realname'];
            //     }
            //     $goods = Db::table('goods')->alias('g')->join('goods_img gi','g.goods_id=gi.goods_id','LEFT')->where('gi.main',1)->where('g.goods_id',$goods_id)->field('g.goods_name,gi.picture img')->find();
            //     $value['goods_name'] = $goods['goods_name'];
            //     $value['img'] = Config('c_pub.apiimg') . $goods['img'];
            // }
        }

        if($status){
            return 0;
        }

        $this->ajaxReturn(['status' => 200 , 'msg'=>"成功！",'data'=>$list]);
    }

    public function upload_proof(){
        $user_id = $this->get_user_id();

        $fz_order_id = input('fz_order_id');
        //$proof = input('proof');

        if(!$fz_order_id){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'参数错误！','data'=>'']);
        }
        $data['fz_order_id'] = $fz_order_id;

        $fifty_zone_order = Db::table('fifty_zone_order')->where('fz_order_id',$fz_order_id)->where('user_id',$user_id)->find();
        if(!$fifty_zone_order) $this->ajaxReturn(['status' => 301 , 'msg'=>'订单不存在！','data'=>'']);

        if($fifty_zone_order['user_confirm']){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'该订单已上传凭证！','data'=>'']);
        }

        $saveName = request()->time().rand(0,99999) . '.png';

        // $proof = explode(',',$proof)[1];
        // $imga=base64_decode($proof);
        // //生成文件夹
        // $names = "fifty_zone" ;
        // $name = "fifty_zone/" .date('Ymd',time()) ;
        // if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
        //     mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
        // }
        // //保存图片到本地
        // file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$imga);

        // $data['proof'] = $name.$saveName;
        $data['user_confirm'] = 1;
        $data['pay_time'] = time();

        // 启动事务
        Db::startTrans();
        $res = Db::table('fifty_zone_order')->update($data);
        if($res){
            // $res = Db::table('fifty_zone_order')->where('user_id',$user_id)->where('is_show',1)->where('user_confirm',0)->find();
            // if(!$res){
            //     Db::table('fifty_zone_shop')->where('user_id',$user_id)->where('is_show',0)->update(['is_show'=>1]);
            // }

            Db::commit();
            // $res = new Transaction();
            // $res->sendOrderSmsCode($fifty_zone_order['user_id'],$fifty_zone_order['shop_user_id']);

            $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>'']);
        }else{
            Db::rollback();
            $this->ajaxReturn(['status' => 301 , 'msg'=>'失败！','data'=>'']);
        }
    }

    public function stay_upload_proof(){
        $user_id = $this->get_user_id();

        $goods_id = 50;

        $where['fzo.user_id'] = $user_id;
        $where['g.goods_id'] = $goods_id;
        $where['gi.main'] = 1;


        $list = Db::table('fifty_zone_order')->alias('fzo')
                ->join('fifty_zone_img fzi','fzo.img_id=fzi.id','LEFT')
                ->join('goods g','g.goods_id=fzo.goods_id','LEFT')
                // ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where($where)
                ->field('fzo.fz_order_id,g.goods_name,fzi.picture img')
                ->order('fzo.fz_order_id DESC')
                ->paginate(10);
        
        if($list){
            $list = $list->all();

            foreach($list as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') . $value['img'];
            }
        }

        $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>$list]);
    }

    //商家待确认列表
    public function shop_que_list(){
        $data['shop_user_id'] = $this->get_user_id();

        $res = Db::table('fifty_zone_shop')->where('user_id',$data['shop_user_id'])->order('add_time DESC')->field('fz_id,stock')->find();
        if(!$res){
            $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>[]]);
        }

        //多加1个小时

        

        if(!$res['stock']){
            $count = Db::table('fifty_zone_order')->where('fz_id',$res['fz_id'])->where('shop_confirm',1)->count();

            $shop_time = Db::table('fifty_zone_order')->where('fz_id',$res['fz_id'])->where('shop_confirm',1)->order('shop_time desc')->find();
            $time = $shop_time['shop_time'] + 3600;
            $info = Db::table('config')->where('module',5)->column('value','name');
            if($count == 11 && $time < time()){
                $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>[]]);
            }
        }

        // $data['fzo.shop_confirm'] = 0;
        $data['fzo.user_confirm'] = 1;
        $data['fzo.fz_id'] = $res['fz_id'];
        
        $list = Db::table('fifty_zone_order')->alias('fzo')
                ->join('goods g','g.goods_id=fzo.goods_id','LEFT')
                ->join('fifty_zone_img fzi','fzi.id=fzo.img_id','LEFT')
                ->field('fzo.*,fzi.picture img,g.goods_name')
                ->where($data)
                ->paginate(1000,false);
        if($list){
            $list = $list->all();
            foreach($list as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') . $value['img'];
                $value['proof'] = Config('c_pub.apiimg') . $value['proof'];
                $member = Db::table('member')->where('id',$value['user_id'])->field('realname,mobile')->find();
                $value['realname'] = $member['realname'];
                $value['mobile'] = $member['mobile'];
            }
        }
        
        $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>$list]);
    }

    //商家确认
    public function shop_que(){
        $data['shop_user_id'] = $this->get_user_id();
        $data['fz_order_id'] = input('fz_order_id');
        $data['shop_confirm'] = 0;

        $info = Db::table('fifty_zone_order')->where($data)->find();
        if(!$info){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'订单不存在！','data'=>'']);
        }

        // Db::startTrans();
        $res = Db::table('fifty_zone_order')->where($data)->update(['shop_confirm'=>1,'shop_time' => time()]);
        if($res){

            $res = Db::table('fifty_zone_order')->where('user_id',$info['user_id'])->where('is_show',1)->where('shop_confirm',0)->find();
            if(!$res){
                Db::table('fifty_zone_shop')->where('user_id',$info['user_id'])->where('is_show',0)->update(['is_show'=>1]);
            }

            // Db::commit();
            $this->ajaxReturn(['status' => 200 , 'msg'=>'确认成功！','data'=>'']);
        }
        // Db::rollback();
        $this->ajaxReturn(['status' => 301 , 'msg'=>'确认失败！','data'=>'']);
    }

}
