<?php
/**
 * 购物车API
 */
namespace app\api\controller;
use app\common\controller\ApiBase;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\CartLogic;
use think\Request;
use think\Db;
use think\Config;

class Safe extends ApiBase
{

    //安全中心首页 
    public function index(){
        // $this->user_id=27875;
        $user_id=$this->user_id;
        $user_info=Db::name('member')->field('realname,mobile')->where(['id'=>$user_id])->find();
        $user_info=doPhone($user_info);
        $this->successResult($user_info);
    }

    //换绑手机验证
    public function check_new_phone()
    {
        $phone=input('phone');
        // $this->user_id=27875;
        $user_id=$this->user_id;
        $user_info=Db::name('member')->where(['id'=>$user_id])->find();
        if($user_info['mobile']==$phone){
            $this->failResult("新手机号跟老手机号一致");
        }
        $all_info=Db::name('member')->where(['mobile'=>$phone])->select();
        if($all_info){
            $this->failResult("此手机号码绑定的账号已达到上限，请更换手机号码");
        }
        if(isMobile($phone)){
            $this->successResult("手机验证成功");
        }else{
            $this->failResult("手机号码格式错误");
        }
    }

    //换绑手机
    public function change_phone()
    {
        $phone = input('phone/s', '');
        $verify_code = input('verify_code/s', '');

        $this->user_id=27875;

        $user_id=$this->user_id;
        //验证码判断
        $res = $this->phoneAuth($phone, $verify_code);
        if ($res === -1) {
            return $this->failResult('验证码已过期！', 301);
        } else if (!$res) {
            return $this->failResult('验证码错误！', 301);
        }
        $change_phone=Db::name('member')->where(['id'=>$user_id])->update(['mobile'=>$phone]);
        if($change_phone){
            $this->successResult("更换成功");
        }else{
            $this->failResult("更换失败");
        }
    }




    
    /**
     * @api {POST} /cart/cartlist 购物车列表
     * @apiGroup cart
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"", 
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *     "status": 200,
     *     "msg": "成功",
     *     "data": [
     *     {
     *         "cart_id": 1737,购物车ID
     *         "selected": 1,是否选中状态 0：否，1：是
     *         "user_id": 76,
     *         "groupon_id": 0,
     *         "goods_id": 18,商品ID
     *         "goods_sn": "",
     *         "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",商品名称
     *         "market_price": "2588.00",市场价
     *         "goods_price": "2388.00",现价
     *         "member_goods_price": "2388.00",
     *         "subtotal_price": "2388.00",该商品总价
     *         "sku_id": 2,规格ID
     *         "goods_num": 1,购买数量
     *         "spec_key_name": "规格:升级版,颜色:星空灰,尺寸:大",购买规格
     *         "img": "http://api.retail.com/upload/images/goods/20190514155782540787289.png",商品图片
     *     },
     *     {
     *         "cart_id": 1735,
     *         "selected": 1,
     *         "user_id": 76,
     *         "groupon_id": 0,
     *         "goods_id": 39,
     *         "goods_sn": "",
     *         "goods_name": "本草",
     *         "market_price": "250.00",
     *         "goods_price": "199.90",
     *         "member_goods_price": "199.90",
     *         "subtotal_price": "199.90",
     *         "sku_id": 22,
     *         "goods_num": 1,
     *         "spec_key_name": "规格:颜色",
     *         "img": "http://api.retail.com/upload/images/goods/20190514155782540787289.png"
     *     },
     *     {
     *         "cart_id": 1653,
     *         "selected": 1,
     *         "user_id": 76,
     *         "groupon_id": 0,
     *         "goods_id": 18,
     *         "goods_sn": "",
     *         "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",
     *         "market_price": "2588.00",
     *         "goods_price": "2388.00",
     *         "member_goods_price": "2388.00",
     *         "subtotal_price": "2388.00",
     *         "sku_id": 2,
     *         "goods_num": 1,
     *         "spec_key_name": "规格:升级版,颜色:星空灰,尺寸:大",
     *         "img": "http://api.retail.com/upload/images/goods/20190514155782540787289.png"
     *     }
     *     ]
     * }
     * //错误返回结果
     * {
     *   "status": 301,
     *   "msg": "token不存在！",
     *   "data": [
     *       
     *   ]
     *   }
     */
    public function cartlist()
    {
        $user_id = $this->get_user_id();

        $cart_where['user_id'] = $user_id;
        $cartM = model('Cart');
        $cart_res = $cartM->cartList1($cart_where);
        if($cart_res){
            Db::table('cart')->where('session_id','neq','')->delete();//删除立即购买
            foreach($cart_res as $key=>&$value){
                if($value['session_id']){
                    unset($cart_res[$key]);
                }
                $value['img'] = Config('c_pub.apiimg') . $value['img'];
                $value['inventory'] = Db::name('goods_sku')->where('sku_id',$value['sku_id'])->value('(inventory-frozen_stock) inventory');
            }
        }

        $cart_res = array_values( $cart_res );
        
        $this->ajaxReturn(['status' => 200 , 'msg'=>'成功','data'=>$cart_res]);
    }

    /**
     * @api {POST} /cart/cart_sum 购物车总数
     * @apiGroup cart
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"", 
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *     "status": 200,
     *     "msg": "成功",
     *     "data": 3
     * }
     * //错误返回结果
     * {
     *   "status": 301,
     *   "msg": "token不存在！",
     *   "data": [
     *       
     *   ]
     *   }
     */
    public function cart_sum(){
        $user_id = $this->get_user_id();

        $cart_where['user_id'] = $user_id;
        $cart_where['groupon_id'] = 0;
        $num = Db::table('cart')->where($cart_where)->sum('goods_num');

        $this->ajaxReturn(['status' => 200 , 'msg'=>'成功','data'=>$num]);
    }

    /**
     * @api {POST} /cart/addCart 加入|修改购物车
     * @apiGroup cart
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"", 
     *     "sku_id":"14",规格ID
     *     "cart_number":"1",购买数量
     *     "edit":"1",修改购物车数量，参数传1，cart_number参数传实际要修改成的数量
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *     "status": 200,
     *     "msg": "成功",
     *     "data": 3,购物车ID
     * }
     * //错误返回结果
     * status:301
     */
    public function addCart()
    {   

        $user_id = $this->get_user_id();
        

        // input('sku_id/d',0)

        $sku_id       = Request::instance()->param("sku_id", 0, 'intval');
        $groupon_id   = Request::instance()->param("groupon_id", 0, 'intval');
        $cart_number  = Request::instance()->param("cart_number", 1, 'intval');
        $session_id  = Request::instance()->param("session_id", '');
        $act = Request::instance()->param('edit');

        if( !$sku_id || !$cart_number ){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'请选择规格！','data'=>'']);
        }

        $sku_res = Db::name('goods_sku')->where('sku_id', $sku_id)->field('price,groupon_price,inventory,frozen_stock,goods_id')->find();

        if (empty($sku_res)) {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'请选择规格！','data'=>'']);
        }

        if ($cart_number > ($sku_res['inventory']-$sku_res['frozen_stock'])) {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'该商品库存不足！','data'=>'']);
        }

        // $goods = Db::table('goods')->where('goods_id',$sku_res['goods_id'])->field('single_number,most_buy_number')->find();

        // if( $cart_number > $goods['single_number'] ){
        //     $this->ajaxReturn(['status' => 301 , 'msg'=>"超过单次购买数量！同类商品单次只能购买{$goods['single_number']}个",'data'=>'']);
        // }

        // $order_goods_num = Db::table('order_goods')->alias('og')
        //                     ->join('order o','o.order_id=og.order_id')
        //                     ->where('o.order_status','neq',3)
        //                     ->where('og.goods_id',$sku_res['goods_id'])
        //                     ->where('og.user_id',$user_id)
        //                     ->sum('og.goods_num');
        
        // $num =  $cart_number + $order_goods_num;
        // if( $num > $goods['most_buy_number'] ){
        //     $this->ajaxReturn(['status' => 301 , 'msg'=>'超过最多购买量！','data'=>'']);
        // }

        $cart_where = array();
        $cart_where['user_id'] = $user_id;
        $cart_where['goods_id'] = $sku_res['goods_id'];
        
        // $act_where = [];
        // if($act){
        //     $act_where['sku_id'] = ['not in',$sku_id];
        // }

        // $cart_goods_num = Db::table('cart')->where($cart_where)->where($act_where)->sum('goods_num');

        // $num = $cart_number + $cart_goods_num;
        // if( $num > $goods['single_number'] ){
        //     $this->ajaxReturn(['status' => 301 , 'msg'=>"超过单次购买数量！同类商品单次只能购买{$goods['single_number']}个",'data'=>'']);
        // }
        // if( $num > $goods['most_buy_number'] ){
        //     $this->ajaxReturn(['status' => 301 , 'msg'=>'超过最多购买量！','data'=>'']);
        // }

        $cart_where['groupon_id'] = $groupon_id;
        $cart_where['sku_id'] = $sku_id;
        $cart_res = Db::table('cart')->where($cart_where)->field('id,goods_num')->find();

        if ($cart_res && !$session_id) {

            $new_number = $cart_res['goods_num'] + $cart_number;
            if($act){
                $new_number = $cart_number;
            }

            if ($new_number <= 0) {
                $result = Db::table('cart')->where('id',$cart_res['id'])->delete();
                $this->ajaxReturn(['status' => 301 , 'msg'=>'该购物车商品已删除！','data'=>'']);
            }

            if ($sku_res['inventory'] >= $new_number) {
                $update_data = array();
                $update_data['id'] = $cart_res['id'];
                $update_data['goods_num'] = $new_number;
                if($groupon_id){
                    $update_data['subtotal_price'] = $new_number * $sku_res['groupon_price'];
                }else{
                    $update_data['subtotal_price'] = $new_number * $sku_res['price'];
                }
                $result = Db::table('cart')->update($update_data);
                $cart_id = $cart_res['id'];
            } else {
                $this->ajaxReturn(['status' => 301 , 'msg'=>'该商品库存不足！','data'=>'']);
            }
        } else {
            $cartData = array();
            $goods_res = Db::name('goods')->where('goods_id',$sku_res['goods_id'])->field('goods_name,price,original_price')->find();
            $cartData['groupon_id'] = $groupon_id;
            $cartData['goods_id'] = $sku_res['goods_id'];
            $cartData['selected'] = 0;
            $cartData['goods_name'] = $goods_res['goods_name'];
            $cartData['sku_id'] = $sku_id;
            $cartData['user_id'] = $user_id;
            $cartData['market_price'] = $goods_res['original_price'];
            if($groupon_id){
                $cartData['goods_price'] = $sku_res['groupon_price'];
                $cartData['member_goods_price'] = $sku_res['groupon_price'];
                $cartData['subtotal_price'] = $cart_number * $sku_res['groupon_price'];
            }else{
                $cartData['goods_price'] = $sku_res['price'];
                $cartData['member_goods_price'] = $sku_res['price'];
                $cartData['subtotal_price'] = $cart_number * $sku_res['price'];
            }
            $cartData['goods_num'] = $cart_number;
            $cartData['add_time'] = time();
            $sku_attr = action('Goods/get_sku_str', $sku_id);
            $cartData['spec_key_name'] = $sku_attr;
            $cartData['session_id'] = $session_id;
            $cart_id = Db::table('cart')->insertGetId($cartData);
            $cart_id = intval($cart_id);
        }

        if($cart_id) {
            $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>['cart_id'=>$cart_id,'inventory'=>$sku_res['inventory']-$sku_res['frozen_stock']]]);
        } else {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'系统异常！','data'=>'']);
        }
    }

    /**
     * @api {POST} /cart/delCart 删除购物车
     * @apiGroup cart
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"", 
     *     "cart_id":"14",购物车ID，多个逗号分开
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *     "status": 200,
     *     "msg": "成功",
     *     "data": ""
     * }
     * //错误返回结果
     * status:301
     */
    public function delCart()
    {   
        $user_id = $this->get_user_id();

        $idStr = Request::instance()->param("cart_id", '', 'htmlspecialchars');
        
        $where['id'] = array('in', $idStr);
        $where['user_id'] = $user_id;
        $cart_res = Db::table('cart')->where($where)->column('id');
        if (empty($cart_res)) {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'购物车不存在！','data'=>'']);
        }
        
        $res = Db::table('cart')->delete($cart_res);
        if ($res) {
            $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>'']);
        } else {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'系统异常！','data'=>'']);
        }
    }

    /**
     * @api {POST} /cart/selected 选中状态
     * @apiGroup cart
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"", 
     *     "cart_id":"14",购物车ID
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *     "status": 200,
     *     "msg": "成功",
     *     "data": ""
     * }
     * //错误返回结果
     * status:301
     */
    public function selected(){
        $user_id = $this->get_user_id();

        $cart_id = Request::instance()->param("cart_id", '', 'htmlspecialchars');

        $selected = Db::table('cart')->where('id',$cart_id)->value('selected');
        if(!$selected && $selected != 0){
            $this->ajaxReturn(['status' => 301 , 'msg'=>'购物车不存在！','data'=>'']);
        }

        if($selected){
            $res = Db::table('cart')->where('id',$cart_id)->update(['selected'=>0]);
        }else{
            $res = Db::table('cart')->where('id',$cart_id)->update(['selected'=>1]);
        }

        if ($res) {
            $this->ajaxReturn(['status' => 200 , 'msg'=>'成功！','data'=>'']);
        } else {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'系统异常！','data'=>'']);
        }

    }

}
