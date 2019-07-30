<?php

namespace app\index\controller;

use think\Db;

class FiftyZone{

    //多少分钟内未付款冻结账号
    public function index(){
        $info = Db::table('config')->where('module',5)->column('value','name');

        $list = Db::table('fifty_zone_order')->where('user_confirm',0)->group('add_time')->field('user_id,GROUP_CONCAT(add_time) add_time')->select();
        if($list){
            foreach($list as $key=>&$value){
                $add_time = explode(',',$value['add_time']);
                $count = count( $add_time );
                if($count == 10){
                    $m = $info['auto_cancel'] * 60;
                    $add = $add_time[0] + $m;
                    if(time() > $add){
                        Db::startTrans();

                        Db::table('member')->where('id',$value['user_id'])->update(['status'=>0]);
                        Db::table('fifty_zone_shop')->where('user_id',$value['user_id'])->order('fz_id DESC')->limit(1)->delete();

                        $res = Db::table('fifty_zone_order')->where('add_time',$add_time[0])->column('fz_id');
                        
                        foreach($res as $k=>$v){
                            Db::name('fifty_zone_shop')->where('fz_id',$v)->setInc('stock');
                        }
                        Db::commit();
                    }
                }
            }
        }
    }

    //多少分钟内为确认自动确认
    public function auto_order(){
        $info = Db::table('config')->where('module',5)->column('value','name');

        $list = Db::table('fifty_zone_order')->where('user_confirm',1)->where('shop_confirm',0)->select();
        if($list){
            foreach($list as $key=>&$value){
                $pay_time = $value['pay_time'] + ($info['auto_order']*60);
                if($pay_time < time()){
                    Db::table('fifty_zone_order')->where('fz_order_id',$value['fz_order_id'])->update(['shop_confirm'=>1]);
                }
            }
        }
    }
}