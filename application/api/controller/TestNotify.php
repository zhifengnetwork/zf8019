<?php
namespace app\api\controller;
use Payment\Notify\PayNotifyInterface;
use app\common\model\Sales;
use Payment\Config;
use think\Loader;
use think\Db;

/**
 * @author: helei
 * @createTime: 2016-07-20 18:31
 * @description:
 */

/**
 * 客户端需要继承该接口，并实现这个方法，在其中实现对应的业务逻辑
 * Class TestNotify
 * anthor helei
 */
class TestNotify implements PayNotifyInterface
{
    public function notifyProcess(array $data)
    {
        $channel = $data['channel'];
        if ($channel === Config::ALI_CHARGE){// 支付宝支付
         write_log('alipay.txt',$data);
        if(strpos($data['order_no'],'release')){
            
                $update = [
                    'pay_time'       =>time(), 
                    'order_status'   => 1,
                    'transaction_id' => $data['transaction_id'],
                ];

                $is_vip = Db::table('member')->where('id',$order['user_id'])->value('is_vip');
                if(!$is_vip){
                    Db::table('member')->where('id',$order['user_id'])->setInc('is_vip');

                    $first_leader = Db::table('member')->where('id',$order['user_id'])->value('first_leader');
                    if($first_leader){
                        Db::table('member')->where('id',$first_leader)->setInc('release');
                    }
                }

                Db::startTrans();
           
                $res = Db::name('fifty_order')->where(['order_sn' => $data['order_no']])->update($update);
                
                if($res === false){
                    Db::rollback();
                    return false;
                }
                $order = Db::table('fifty_order')->where(['order_sn' => $data['order_no']])->field('id,user_id,order_sn,amount')->find();
                $order['amount'] = 30;
                // Db::name('member')->where(['id' => $order['user_id']])->setInc('residue_release');
                $Sales = new Sales($order['user_id'],$order['id'],0);
                $rest  = $Sales->rewardtame($order['user_id'],$order['amount'],$order['id'],$order['order_sn']);
                if($rest === false){
                    Db::rollback();
                    return false;
                }
                $rest = $Sales->reward_fifty($order['user_id'],$order['id'],$order['order_sn'],$order['amount']);
                if($rest === false){
                    Db::rollback();
                    return false;
                }

                Db::table('fifty_zone_order')->where('user_id',$order['user_id'])->where('is_show',0)->update(['is_show'=>1]);
                Db::table('member')->where('id',$order['user_id'])->setInc('release_ci');
        }elseif(strpos($data['order_no'],'recharge')){
            //修改订单状态
            $update = [
                'transaction_id'   => $data['transaction_id'],
                'order_status'     => 1,
                'pay_time'         => strtotime($data['pay_time']),
            ];

            Db::startTrans();

            $info    = Db::name('recharge_order')->where(['order_sn' => $data['order_no']])->find();

            $member  = Db::name('member')->where(['id' => $info['user_id']])->find();
           
            $res     = Db::name('recharge_order')->where(['order_sn' => $data['order_no']])->update($update);
            
            if($res === false){
                Db::rollback();
                return false;
            }
             //已回调余额
            if($info['order_status'] == 1){
                Db::rollback();
                return false;
            }
            //充值余额 操作
            $moneyUpdate = [
                'remainder_money'   => Db::raw('remainder_money+' . $info['amount']),
            ];

            $res = Db::name('member')->where(['id' => $info['user_id']])->update($moneyUpdate);
            if($res == false){
                Db::rollback();
                return false;
            }
            //余额记录

            $balance = [
				'user_id'     => $info['user_id'],
				'balance'     => $member['remainder_money'] + $info['amount'],
				'source_type' => 6,
				'log_type'    => 1,
				'source_id'   => $data['order_no'],
				'bonus_from'  => '在线余额充值', 
				'old_balance' => $member['remainder_money'],
				'note'        => '在线余额充值',  
				'create_time' => time(),
			];
			
			$balance_res = Db::name('menber_balance_log')->where(['user_id' => $info['user_id'],'source_id' => $data['order_no']])->find();

			if(!$balance_res){
				$res	= Db::name('menber_balance_log')->insert($balance);
				if($res == false){
                    Db::rollback();
					return false;
				}
			}
            
        }else{
                 //修改订单状态
                 $update = [
                    'seller_id'      => $data['seller_id'],
                    'transaction_id' => $data['transaction_id'],
                    'order_status'   => 1,
                    'pay_status'     => 1,
                    'pay_time'       => strtotime($data['pay_time']),
                    'pay_type'       => 3
                ];
                Db::startTrans();
               
                $res = Db::name('order')->where(['order_sn' => $data['order_no']])->update($update);
                
                if($res === false){
                    Db::rollback();
                    return false;
                }
    
                $order = Db::table('order')->where(['order_sn' => $data['order_no']])->field('order_id,user_id,order_sn,order_amount')->find();
    
                $goods_res = Db::table('order_goods')->field('goods_id,goods_name,goods_num,spec_key_name,goods_price,sku_id')->where('order_id',$order['order_id'])->select();
                $fenyong_money = 0;
                foreach($goods_res as $key=>$value){
                    // $goods = Db::table('goods')->where('goods_id',$value['goods_id'])->field('less_stock_type,gift_points')->find();
                    $goods = Db::table('goods')->where('goods_id',$value['goods_id'])->field('less_stock_type,gift_points,is_distribution')->find();
                    if($goods['is_distribution']){
                        $price = Db::table('goods_sku')->where('sku_id',$value['sku_id'])->value('price');
                        $fenyong_money = sprintf("%.2f",$fenyong_money + ($value['goods_num'] * $price));
                    }
                    //付款减库存
                    if($goods['less_stock_type']==2){
                        Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('inventory',$value['goods_num']);
                        Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('frozen_stock',$value['goods_num']);
                    }
                }
             
                $Sales = new Sales($order['user_id'],$order['order_id'],0);
    
                $rest = $Sales->reward_leve($order['user_id'],$order['order_id'],$order['order_sn'],$fenyong_money,1);
                
                if($rest === false){
                    Db::rollback();
                    return false;
                }
        }

        Db::commit();
        return true;
        } elseif ($channel === Config::WX_CHARGE) {// 微信支付


            
                // array (
                // 'bank_type' => 'CFT',
                // 'cash_fee' => '0.10',
                // 'device_info' => 'WEB',
                // 'fee_type' => 'CNY',
                // 'is_subscribe' => 'N',
                // 'buyer_id' => 'oJLQ86AO_mBjoVPszofZU4VDnv_A',
                // 'order_no' => '20190726212729727623',
                // 'pay_time' => '2019-07-26 21:28:34',
                // 'amount' => '0.10',
                // 'trade_type' => 'MWEB',
                // 'transaction_id' => '4200000385201907269133591095',
                // 'trade_state' => 'success',
                // 'channel' => 'wx_charge',
                // )


            write_log('wxpay.txt',$data);

             if(strpos($data['order_no'],'release')){
                $update = [
                    'pay_time'       => strtotime($data['pay_time']),
                    'order_status'   => 1,
                    'transaction_id' => $data['transaction_id'],
                ];
                Db::startTrans();
           
                $res = Db::name('fifty_order')->where(['order_sn' => $data['order_no']])->update($update);
                
                if($res === false){
                    Db::rollback();
                    return false;
                }
                $order = Db::table('fifty_order')->where(['order_sn' => $data['order_no']])->field('id,user_id,order_sn,amount')->find();
                $order['amount'] = 30;
                // Db::name('member')->where(['id' => $order['user_id']])->setInc('residue_release');
                $Sales = new Sales($order['user_id'],$order['id'],0);
                $rest  = $Sales->rewardtame($order['user_id'],$order['amount'],$order['id'],$order['order_sn']);
                if($rest === false){
                    Db::rollback();
                    return false;
                }
                $rest = $Sales->reward_fifty($order['user_id'],$order['id'],$order['order_sn'],$order['amount']);
                if($rest === false){
                    Db::rollback();
                    return false;
                }

                $is_vip = Db::table('member')->where('id',$order['user_id'])->value('is_vip');
                if(!$is_vip){
                    Db::table('member')->where('id',$order['user_id'])->setInc('is_vip');

                    $first_leader = Db::table('member')->where('id',$order['user_id'])->value('first_leader');
                    if($first_leader){
                        Db::table('member')->where('id',$first_leader)->setInc('release');
                    }
                }

                Db::table('fifty_zone_order')->where('user_id',$order['user_id'])->where('is_show',0)->update(['is_show'=>1]);
                Db::table('member')->where('id',$order['user_id'])->setInc('release_ci');
        }elseif(strpos($data['order_no'],'recharge')){
            //修改订单状态  商品充值
            $update = [
                'transaction_id'   => $data['transaction_id'],
                'order_status'     => 1,
                'pay_time'         => strtotime($data['pay_time']),
            ];

            Db::startTrans();

            $info    = Db::name('recharge_order')->where(['order_sn' => $data['order_no']])->find();

            $member  = Db::name('member')->where(['id' => $info['user_id']])->find();
           
            $res     = Db::name('recharge_order')->where(['order_sn' => $data['order_no']])->update($update);
            
            if($res === false){
                Db::rollback();
                return false;
            }
             //已回调余额
            if($info['order_status'] == 1){
                Db::rollback();
                return false;
            }
            //充值余额 操作
            $moneyUpdate = [
                'remainder_money'   => Db::raw('remainder_money+' . $info['amount']),
            ];

            $res = Db::name('member')->where(['id' => $info['user_id']])->update($moneyUpdate);
            if($res == false){
                Db::rollback();
                return false;
            }
            //余额记录

            $balance = [
				'user_id'     => $info['user_id'],
				'balance'     => $member['remainder_money'] + $info['amount'],
				'source_type' => 6,
				'log_type'    => 1,
				'source_id'   => $data['order_no'],
				'bonus_from'  => '在线余额充值', 
				'old_balance' => $member['remainder_money'],
				'note'        => '在线余额充值',  
				'create_time' => time(),
			];
			
			$balance_res = Db::name('menber_balance_log')->where(['user_id' => $info['user_id'],'source_id' => $data['order_no']])->find();

			if(!$balance_res){
				$res	= Db::name('menber_balance_log')->insert($balance);
				if($res == false){
                    Db::rollback();
					return false;
				}
			}
            
        }else{
                 //修改订单状态 普通商品订单
                 $update = [
                    'transaction_id' => $data['transaction_id'],
                    'order_status'   => 1,
                    'pay_status'     => 1,
                    'pay_time'       => strtotime($data['pay_time']),
                    'pay_type'       => 3
                ];
                Db::startTrans();
               
                $res = Db::name('order')->where(['order_sn' => $data['order_no']])->update($update);
                
                if($res === false){
                    Db::rollback();
                    return false;
                }
    
                $order = Db::table('order')->where(['order_sn' => $data['order_no']])->field('order_id,user_id,order_sn,order_amount')->find();
    
                $goods_res = Db::table('order_goods')->field('goods_id,goods_name,goods_num,spec_key_name,goods_price,sku_id')->where('order_id',$order['order_id'])->select();
                $fenyong_money = 0;
                foreach($goods_res as $key=>$value){
                    // $goods = Db::table('goods')->where('goods_id',$value['goods_id'])->field('less_stock_type,gift_points')->find();
                    $goods = Db::table('goods')->where('goods_id',$value['goods_id'])->field('less_stock_type,gift_points,is_distribution')->find();
                    if($goods['is_distribution']){
                        $price = Db::table('goods_sku')->where('sku_id',$value['sku_id'])->value('price');
                        $fenyong_money = sprintf("%.2f",$fenyong_money + ($value['goods_num'] * $price));
                    }
                    //付款减库存
                    if($goods['less_stock_type']==2){
                        Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('inventory',$value['goods_num']);
                        Db::table('goods_sku')->where('sku_id',$value['sku_id'])->setDec('frozen_stock',$value['goods_num']);
                    }
                }
             
                $Sales = new Sales($order['user_id'],$order['order_id'],0);
    
                $rest = $Sales->reward_leve($order['user_id'],$order['order_id'],$order['order_sn'],$fenyong_money,1);
                
                if($rest === false){
                    Db::rollback();
                    return false;
                }
        }

            Db::commit();
            return true;

        } elseif ($channel === Config::CMB_CHARGE) {// 招商支付
        } elseif ($channel === Config::CMB_BIND) {// 招商签约
        } else {
            // 其它类型的通知
        }
        // 执行业务逻辑，成功后返回true
        return true;
    }
}