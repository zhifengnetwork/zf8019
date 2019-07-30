<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\controller\ApiBase;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\GoodsPromFactory;
use app\common\model\GoodsCategory;
use think\AjaxPage;
use think\Page;
use think\Db;

class Goods extends ApiBase
{

    /**
    * 礼品专区商品列表
    */
    public function goods_gift(){
        $list = Db::table('goods')->alias('g')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where('gi.main',1)
                ->where('g.is_show',1)
                ->where('g.is_del',0)
                ->where('g.is_gift',1)
                ->order('g.goods_id DESC')
                ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
                ->paginate(4);

        if($list){
            $list = $list->all();
            foreach($list as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') .$value['img'];
            }
        }

        $this->ajaxReturn(['status' => 200 , 'msg'=>'获取成功','data'=>$list]);
    }

    /**
    * 商品分类接口
    */
    /*public function categoryList(){
        $list = Db::name('category')->where('is_show',1)->field('cat_id,cat_name,pid,img')->order('sort DESC,cat_id DESC')->select();
        $list  = getTree1($list);
        
        if($list){
            foreach($list as $key=>&$value){
                //热销
                $list[$key]['hot'] = Db::table('goods')->alias('g')
                                        ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                                        ->where('cat_id1',$value['cat_id'])
                                        ->where('g.is_show',1)
                                        ->where('gi.main',1)
                                        ->where('FIND_IN_SET(3,g.goods_attr)')
                                        ->field('g.goods_id,goods_name,gi.picture img,price,original_price,g.goods_attr')
                                        ->select();
                if(isset($value['children'])){
                    foreach($value['children'] as $ke=>&$val){

                        $val['goods'] = Db::table('goods')->alias('g')
                                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                                ->where('cat_id2',$val['cat_id'])
                                ->where('g.is_show',1)
                                ->where('gi.main',1)
                                ->field('g.goods_id,goods_name,gi.picture img,price,original_price,g.goods_attr')
                                ->select();
                        if($val['goods']){
                            foreach($val['goods'] as $g=>$v){
                                if(strpos($v['goods_attr'], '3') !== false){
                                    $list[$key]['hot'][] = $v;
                                }
                            }
                        }
                    }
                }
                if( $list[$key]['hot'] ){
                    $list[$key]['hot'] = array_unique($list[$key]['hot'],SORT_REGULAR);
                    foreach($list[$key]['hot'] as $hot=>$hot_val){
                        $list[$key]['hot'][$hot]['attr_name'] = Db::table('goods_attr')->where('attr_id','in',$hot_val['goods_attr'])->field('attr_name')->select();
                    }
                }
            }
        }
        
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$list]);
    }*/


    /**
     * @api {GET} /goods/recommend_goods 推荐商品
     * @apiGroup goods
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "page":"1", 请求页数
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *   "status": 200,
     *   "msg": "获取成功",
     *   "data": [
     *       {
     *       "goods_id": 39,
     *       "goods_name": "本草",
     *       "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *       "price": "200.00",
     *       "original_price": "250.00"
     *       },
     *       {
     *       "goods_id": 18,
     *       "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",
     *       "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *       "price": "2188.00",
     *       "original_price": "2588.00"
     *       }
     *   ]
     *   }
     * //错误返回结果
     * 无
     */
    public function recommend_goods(){

        $list = Db::table('goods')->alias('g')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where('gi.main',1)
                ->where('g.is_show',1)
                ->where('g.is_del',0)
                ->where('FIND_IN_SET(1,g.goods_attr)')
                ->order('g.goods_id DESC')
                ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
                ->paginate(4);

        if($list){
            $list = $list->all();
            foreach($list as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') .$value['img'];
            }
        }

        $this->ajaxReturn(['status' => 200 , 'msg'=>'获取成功','data'=>$list]);
    }



    /**
     * @api {GET} /goods/categoryList 分类
     * @apiGroup goods
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * 无
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *   "status": 200,
     *   "msg": "获取成功",
     *   "data": [
     *       {
     *       "cat_id": 13,
     *       "cat_name": "化妆品",
     *       "pid": 0,
     *       "level": 1,
     *       "img": "category/20190516155797968450728.png",
     *       "is_show": 1,
     *       "desc": "",
     *       "sort": 1,
     *       "goods": [
     *           {
     *           "goods_id": 39,
     *           "goods_name": "本草",
     *           "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *           "price": "200.00",
     *           "original_price": "250.00",
     *           "attr_name": [
     *               "精选",
     *               "限时卖",
     *               "热卖"
     *           ],
     *           "comment": 0
     *           },
     *           {
     *           "goods_id": 18,
     *           "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",
     *           "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *           "price": "2188.00",
     *           "original_price": "2588.00",
     *           "attr_name": [
     *               "热卖",
     *               "新上"
     *           ],
     *           "comment": 18
     *           }
     *       ]
     *       }
     *   ]
     *   }
     * //错误返回结果
     * 无
     */
    public function categoryList()
    {
        
        $list = Db::name('category')->where('is_show',1)->order('sort DESC,cat_id DESC')->select();
        $list  = getTree1($list);
        foreach($list as $key=>$value){
            $list[$key]['goods'] = Db::table('goods')->alias('g')
                                ->join('goods_attr ga','FIND_IN_SET(ga.attr_id,g.goods_attr)','LEFT')
                                ->where('cat_id1',$value['cat_id'])
                                ->where('g.is_show',1)
                                ->where('g.is_del',0)
                                ->where('gi.main',1)
                                ->group('g.goods_id')
                                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                                ->order('g.goods_id DESC')
                                ->field('g.goods_id,goods_name,gi.picture img,price,original_price,GROUP_CONCAT(ga.attr_name) attr_name,g.cat_id1 comment')
                                ->select();
            if($list[$key]['goods']){
                foreach($list[$key]['goods'] as $k=>$v){
                    if($v['attr_name']){
                        $list[$key]['goods'][$k]['attr_name'] = explode(',',$v['attr_name']);
                    }else{
                        $list[$key]['goods'][$k]['attr_name'] = array();
                    }

                    $list[$key]['goods'][$k]['img'] = Config('c_pub.apiimg') .$v['img'];

                    $list[$key]['goods'][$k]['comment'] = Db::table('goods_comment')->where('goods_id',$v['goods_id'])->count();
                }
            }
        }
        
        $this->ajaxReturn(['status' => 200 , 'msg'=>'获取成功','data'=>$list]);
    }

    /*public function category(){
        $cat_id = input('cat_id');
        $cat_id2 = 'cat_id1';
        $sort = input('sort');
        $goods_attr = input('goods_attr');
        $page = input('page',1);

        $where = [];
        $whereRaw = [];
        $pageParam = ['query' => []];
        if($cat_id){
            $cate_list = Db::name('category')->where('is_show',1)->where('cat_id',$cat_id)->value('pid');
            if($cate_list){
                $cate_list = Db::name('category')->where('is_show',1)->where('pid',$cate_list)->select();
                $cat_id2 = 'cat_id2';
            }else{
                $cate_list = Db::name('category')->where('is_show',1)->where('pid',$cat_id)->select();
            }
            $where[$cat_id2] = $cat_id;
            $pageParam['query'][$cat_id2] = $cat_id;
        }else{
            $cate_list = Db::name('category')->where('is_show',1)->order('sort DESC,cat_id ASC')->select();
        }
        $cate_list  = getTree1($cate_list);

        if($goods_attr){
            $whereRaw = "FIND_IN_SET($goods_attr,goods_attr)";
            $pageParam['query']['goods_attr'] = $goods_attr;
        }

        if($sort){
            $order['price'] = $sort;
        }else{
            $order['goods_id'] = 'DESC';
        }
        
        $goods_list = Db::name('goods')->alias('g')
                        ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                        ->where('gi.main',1)
                        ->where('is_show',1)
                        ->where($where)
                        ->where($whereRaw)
                        ->order($order)
                        ->field('g.goods_id,gi.picture img,goods_name,desc,price,original_price,g.goods_attr')
                        ->paginate(10,false,$pageParam)
                        ->toArray();
        if($goods_list['data']){
            foreach($goods_list['data'] as $key=>&$value){
                $value['comment'] = Db::table('goods_comment')->where('goods_id',$value['goods_id'])->count();
                $value['attr_name'] = Db::table('goods_attr')->where('attr_id','in',$value['goods_attr'])->column('attr_name');
            }
        }
        
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>['cate_list'=>$cate_list,'goods_list'=>$goods_list['data']]]);

    }*/

    /**
     * @api {POST} /goods/goodsDetail 商品详情
     * @apiGroup goods
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     "token":"",
     *     "goods_id",商品ID
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *   "status": 200,
     *   "msg": "获取成功",
     *   "data": {
     *   "goods_id": 18,商品ID
     *   "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",商品名称
     *   "type_id": 0,
     *   "desc": "宽幅变温；铂金净味；雷达感温；风冷无霜；",描述
     *   "content": null,内容
     *   "goods_attr": "2,3",
     *   "limited_start": 1559318400,
     *   "limited_end": 1559577600,
     *   "add_time": 1557417600,
     *   "goods_spec": "[{\"key\":\"规格\",\"value\":\"默认;升级版;超级版\"},{\"key\":\"颜色\",\"value\":\"阳光米;星空灰;天空黑\"},{\"key\":\"尺寸\",\"value\":\"中;大;小;加大\"}]",
     *   "price": "2188.00",现价
     *   "original_price": "2588.00",市场价
     *   "cost_price": "10.00",
     *   "cat_id1": 13,
     *   "cat_id2": 14,
     *   "stock": 1190,总库存数量
     *   "stock1": 1193,
     *   "less_stock_type": 2,
     *   "shipping_setting": 1,运费方式 1：统一运费价格 ，2：运费模板
     *   "shipping_price": "0.00",统一运费价格
     *   "delivery_id": 0,
     *   "is_hdfk": 0,是否支持货到付款 0：否，1：是
     *   "is_distribution": 1,
     *   "most_buy_number": 10000,
     *   "gift_points": "10%",
     *   "number_sales": 66,已售数量
     *   "single_number": 10000,单次最多购买量
     *   "distributor_level": 0,
     *   "is_full_return": 0,
     *   "is_arrange_all": 0,
     *   "shopping_all_return": 0,
     *   "is_show": 1,
     *   "dividend_agent_level": 0,
     *   "is_del": 0,
     *   "is_puls": 0,
     *   "province_proportion": 0,
     *   "tow_proportion": 0,
     *   "infinite_proportion": 0,
     *   "puls_discount": 0,
     *   "share_discount": 0,
     *   "attr_name": [
     *       "新上",
     *       "热卖"
     *   ],商品属性
     *   "spec": {
     *       "spec_attr": [
     *       {
     *           "spec_id": 1,
     *           "spec_name": "规格",规格名称
     *           "res": [
     *           {
     *               "attr_id": 34478,
     *               "attr_name": "默认"规格值
     *           },
     *           {
     *               "attr_id": 34480,
     *               "attr_name": "升级版"
     *           },
     *           {
     *               "attr_id": 34494,
     *               "attr_name": "超级版"
     *           }
     *           ]
     *       },
     *       {
     *           "spec_id": 2,
     *           "spec_name": "颜色",
     *           "res": [
     *           {
     *               "attr_id": 34479,
     *               "attr_name": "阳光米"
     *           },
     *           {
     *               "attr_id": 34481,
     *               "attr_name": "星空灰"
     *           },
     *           {
     *               "attr_id": 34495,
     *               "attr_name": "天空黑"
     *           }
     *           ]
     *       },
     *       {
     *           "spec_id": 4,
     *           "spec_name": "尺寸",
     *           "res": [
     *           {
     *               "attr_id": 34484,
     *               "attr_name": "中"
     *           },
     *           {
     *               "attr_id": 34485,
     *               "attr_name": "大"
     *           },
     *           {
     *               "attr_id": 34486,
     *               "attr_name": "小"
     *           },
     *           {
     *               "attr_id": 34496,
     *               "attr_name": "加大"
     *           }
     *           ]
     *       }
     *       ],
     *       "goods_sku": [
     *       {
     *           "sku_id": 1,规格ID
     *           "goods_id": 18,
     *           "sku_attr": "{\"1\":34478,\"2\":34479,\"4\":34484}",
     *           "price": "2199.00",价格
     *           "groupon_price": "1999.00",
     *           "img": "",
     *           "inventory": 0,剩余库存
     *           "frozen_stock": 2,
     *           "sales": 0,销量
     *           "virtual_sales": 559,虚拟销量
     *           "sku_attr1": "34478,34479,34484"
     *       },
     *       {
     *           "sku_id": 2,
     *           "goods_id": 18,
     *           "sku_attr": "{\"1\":34480,\"2\":34481,\"4\":34485}",
     *           "price": "2388.00",
     *           "groupon_price": "2288.00",
     *           "img": "",
     *           "inventory": 493,
     *           "frozen_stock": 0,
     *           "sales": 0,
     *           "virtual_sales": 472,
     *           "sku_attr1": "34480,34481,34485"
     *       },
     *       {
     *           "sku_id": 4,
     *           "goods_id": 18,
     *           "sku_attr": "{\"1\":34480,\"2\":34479,\"4\":34486}",
     *           "price": "1988.00",
     *           "groupon_price": "1799.00",
     *           "img": "",
     *           "inventory": 298,
     *           "frozen_stock": 1,
     *           "sales": 0,
     *           "virtual_sales": 538,
     *           "sku_attr1": "34480,34479,34486"
     *       },
     *       {
     *           "sku_id": 10,
     *           "goods_id": 18,
     *           "sku_attr": "{\"1\":34494,\"2\":34495,\"4\":34496}",
     *           "price": "2500.00",
     *           "groupon_price": "2300.00",
     *           "img": "",
     *           "inventory": 199,
     *           "frozen_stock": 0,
     *           "sales": 0,
     *           "virtual_sales": 443,
     *           "sku_attr1": "34494,34495,34496"
     *       },
     *       {
     *           "sku_id": 11,
     *           "goods_id": 18,
     *           "sku_attr": "{\"1\":34494,\"2\":34481,\"4\":34485}",
     *           "price": "2488.00",
     *           "groupon_price": "2388.00",
     *           "img": "",
     *           "inventory": 200,
     *           "frozen_stock": 0,
     *           "sales": 0,
     *           "virtual_sales": 450,
     *           "sku_attr1": "34494,34481,34485"
     *       }
     *       ]
     *   },商品规格
     *   "groupon_price": "1799.00",
     *   "img": [
     *       {
     *       "picture": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png"
     *       }
     *   ],商品组图
     *   "collection": 0,是否收藏
     *   "comment_count": 18,评论总数
     *   "coupon": [
     *       
     *   ]优惠券
     *   }
     * }
     * //错误返回结果
     * {
     *     "status": 999,
     *     "msg": "用户不存在！",
     *     "data": false
     * }
     * {
     *     "status": 301,
     *     "msg": "商品不存在！",
     *     "data": false
     * }
     */
    public function goodsDetail()
    {   
        $user_id = $this->get_user_id();
        
        $goods_id = input('goods_id');
        
        $goodsRes = Db::table('goods')->alias('g')
                    ->join('goods_attr ga','FIND_IN_SET(ga.attr_id,g.goods_attr)','LEFT')
                    ->field('g.*,GROUP_CONCAT(ga.attr_name) attr_name')
                    ->where('g.is_show',1)
                    ->where('g.is_del',0)
                    ->find($goods_id);
        
        if (empty($goodsRes) || !$goodsRes['goods_id']) {
            $this->ajaxReturn(['status' => 301 , 'msg'=>'商品不存在！']);
        }

        if($goodsRes['attr_name']){
            $goodsRes['attr_name'] = explode(',',$goodsRes['attr_name']);
        }else{
            $goodsRes['attr_name'] = [];
        }

        $goodsRes['spec'] = $this->getGoodsSpec($goods_id);
        $goodsRes['stock'] = $goodsRes['spec']['count_num'];
        $goodsRes['groupon_price'] = $goodsRes['spec']['min_groupon_price'];
        unset($goodsRes['spec']['count_num'],$goodsRes['spec']['min_groupon_price']);
        
        //组图
        $goodsRes['img'] = Db::table('goods_img')->where('goods_id',$goods_id)->field('picture')->order('main DESC')->select();
        if($goodsRes['img']){
            foreach($goodsRes['img'] as $key=>&$value){
                $value['picture'] = Config('c_pub.apiimg') .$value['picture'];
            }
        }
        
        //收藏
        $goodsRes['collection'] = Db::table('collection')->where('user_id',$user_id)->where('goods_id',$goods_id)->find();
        if($goodsRes['collection']){
            $goodsRes['collection'] = 1;
        }else{
            $goodsRes['collection'] = 0;
        }
        
        //评论总数
        $goodsRes['comment_count'] = Db::table('goods_comment')->where('goods_id',$goods_id)->count();

        //优惠券
        $where = [];
        $where['start_time'] = ['<', time()];
        $where['end_time'] = ['>', time()];
        $where['goods_id'] = ['in',$goods_id.',0'];
        $goodsRes['coupon'] = Db::table('coupon')->where($where)->select();
        if($goodsRes['coupon']){
            foreach($goodsRes['coupon'] as $key=>$value){
                $res = Db::table('coupon_get')->where('user_id',$user_id)->where('coupon_id',$value['coupon_id'])->find();
                if($res){
                    $goodsRes['coupon'][$key]['is_lq'] = 1;
                }else{
                    $goodsRes['coupon'][$key]['is_lq'] = 0;
                }
            }
        }

        //属性  add  by zgp
        $arr_attr = null;
        $goodsRes['productAttr'] = null;
        if(isset($goodsRes['spec']['spec_attr']) && !empty($goodsRes['spec']['spec_attr'])){
            foreach($goodsRes['spec']['spec_attr'] as $k=>$vv){
                $goodsRes['productAttr'][$k]['product_id'] = $goodsRes['goods_id'];
                $goodsRes['productAttr'][$k]['attr_name'] = $vv['spec_name'];
                $goodsRes['productAttr'][$k]['spec_id'] = $vv['spec_id'];
                $goodsRes['productAttr'][$k]['res'] = $vv['res'];
                foreach($vv['res'] as $aabb => $item){
                    $arr_attr[$k][$aabb] = $item['attr_id'].','.$item['attr_name'];
                }

            }
        }
        //goods_sku  完善所有组合
        $arr_attr = getArrSet($arr_attr);

        $goodsRes['productSku'] = null;
//        print_r($goodsRes['spec']['goods_sku']);die;
        $check_true_arr = null;
        $sku_all_arr = null;
        if(isset($goodsRes['spec']['goods_sku']) && !empty($goodsRes['spec']['goods_sku'])){
            foreach($arr_attr as $k=>$vv){
                foreach($vv as $aabb=>$item){
                    $arr = explode(",",$item);
                    $check_true_arr[$k][$aabb] = $arr[0];
                }
                $check_true_arr[$k]['arr_str'] = implode(",",$check_true_arr[$k]);
                unset($check_true_arr[$k][0]);
                unset($check_true_arr[$k][1]);
                unset($check_true_arr[$k][2]);
            }
            //全部的sku数组
            foreach($check_true_arr as $k=>$vv){
                $sku_all_arr[$k] = $vv['arr_str'];
            }
            foreach($sku_all_arr as $k=>$vv){
                if(isset($goodsRes['spec']['goods_sku'][$k]) && !empty($goodsRes['spec']['goods_sku'][$k])){
                    if(in_array($goodsRes['spec']['goods_sku'][$k]['sku_attr1'],$sku_all_arr)){
                        $goodsRes['productSku'][$k]['product_id'] = $goodsRes['goods_id'];
                        $goodsRes['productSku'][$k] = $goodsRes['spec']['goods_sku'][$k];
                    }
                }else{
                    $goodsRes['productSku'][$k]['sku_id'] = 0;
                    $goodsRes['productSku'][$k]['product_id'] = $goodsRes['goods_id'];
                    $goodsRes['productSku'][$k]['goods_id'] = $goodsRes['goods_id'];;
                    $goodsRes['productSku'][$k]['inventory'] = 0;
                    $goodsRes['productSku'][$k]['price'] = 0;
                    $goodsRes['productSku'][$k]['sku_attr'] = '';
                    $goodsRes['productSku'][$k]['img'] = 0;
                    $goodsRes['productSku'][$k]['groupon_price'] = 0;
                    $goodsRes['productSku'][$k]['frozen_stock'] = 0;
                    $goodsRes['productSku'][$k]['sales'] = 0;
                    $goodsRes['productSku'][$k]['virtual_sales'] = 0;
                    $goodsRes['productSku'][$k]['sku_attr1'] = $vv;
                }

            }
        }
        
        // 查询地址
        $addr_data['ua.user_id'] = $user_id;
        $addr_data['ua.is_default'] = 1;
        $addressM = Model('UserAddr');
        $addr_res = $addressM->getAddressFind($addr_data);
        $goodsRes['address'] = $addr_res['p_cn'] . $addr_res['c_cn'] . $addr_res['d_cn'] . $addr_res['s_cn'] . $addr_res['address'];

        $goodsRes['content'] = str_replace("/ueditor/",  SITE_URL . "/ueditor/", $goodsRes['content']);
        $goodsRes['content_param'] = str_replace("/ueditor/",  SITE_URL . "/ueditor/", $goodsRes['content_param']);
              
        $this->ajaxReturn(['status' => 200 , 'msg'=>'获取成功','data'=>$goodsRes]);

    }

    /**
     * @api {POST} /goods/comment_list 商品评论
     * @apiGroup goods
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *      "token",
     *      "goods_id",商品ID
     *      "page",请求页数
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
    *      "status": 1,
    *      "msg": "获取成功",
    *      "data": [
    *      {
    *          "mobile": "",手机号
    *          "user_id": 0,
    *          "comment_id": 13,
    *          "content": null,评论内容
    *          "star_rating": 5,星评
    *          "replies": null,商家回复
    *          "praise": 0,点赞
    *          "add_time": 1558087885,
    *          "img": [
    *          
    *          ],评论图片
    *          "sku_id": 4,
    *          "spec": "规格:升级版,颜色:阳光米,尺寸:小",购买规格
    *          "is_praise": 0,是否已点赞改评论 0：否，1：是
    *      }
    *      ]
    * }
     * //错误返回结果
     * 无
     */
    public function comment_list(){

        $user_id = $this->get_user_id();

        $goods_id = input('goods_id');
        $page = input('page');

        $pageParam['query']['goods_id'] = $goods_id;

        $comment = Db::table('goods_comment')->alias('gc')
                ->join('member m','m.id=gc.user_id','LEFT')
                ->field('m.mobile,m.avatar,gc.user_id,gc.id comment_id,gc.content,gc.star_rating,gc.replies,gc.praise,gc.add_time,gc.img,gc.sku_id')
                ->order('gc.id DESC')
                ->where('gc.goods_id',$goods_id)
                ->paginate(1000,false,$pageParam);

        $comment = $comment->all();

        if (empty($comment)) {
            $this->ajaxReturn(['status' => 200 , 'msg'=>'暂无评论！','data'=>[]]);
        }
        
        foreach($comment as $key=>$value ){
            
            $comment[$key]['mobile'] = $value['mobile'] ? substr_cut($value['mobile']) : '';

            if($value['img']){
                $comment[$key]['img'] = explode(',',$value['img']);
                foreach($comment[$key]['img'] as $k=>&$v){
                    $comment[$key]['img'][$k] = Config('c_pub.apiimg') .$v;
                }
            }else{
                $comment[$key]['img'] = [];
            }

            $comment[$key]['spec'] = $this->get_sku_str($value['sku_id']);

            $comment[$key]['is_praise'] = Db::table('goods_comment_praise')->where('comment_id',$value['comment_id'])->where('user_id',$user_id)->count();
        }
        
        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>$comment]);
    }


    public function getGoodsSpec($goods_id){

        //从规格-属性表中查到所有规格id
        $spec = Db::name('goods_spec_attr')->field('spec_id')->where('goods_id',$goods_id)->select();

        $specArray = array();
        foreach ($spec as $spec_k => $spec_v){
            array_push($specArray,$spec_v['spec_id']);
        }

        $specArray = array_unique($specArray);
        $specStr = implode(',',$specArray);

        $specRes = Db::name('goods_spec')->field('spec_id,spec_name')->where('spec_id','in',$specStr)->select();

        $data = array();
        $data['goods_id'] = $goods_id;
        foreach ($specRes as $key=>$value) {
            //商品规格下的属性
            $data['spec_id'] = $value['spec_id'];
            $specRes[$key]['res'] = Db::name('goods_spec_attr')->field('attr_id,attr_name')->where($data)->select();
        }

        //sku信息
        $skuRes = Db::name('goods_sku')->where('goods_id',$goods_id)->select();
        $count_num = 0;
        $min = [];
        foreach ($skuRes as $sku_k=>$sku_v){
            $min[] = $sku_v['groupon_price'];
            $skuRes[$sku_k]['inventory'] = $skuRes[$sku_k]['inventory'] - $skuRes[$sku_k]['frozen_stock'];
            $count_num += $skuRes[$sku_k]['inventory'];
            $skuRes[$sku_k]['sku_attr'] = preg_replace("/(\w):/",  '"$1":' ,  $sku_v['sku_attr']);
            $str = preg_replace("/(\w+):/",  '"$1":' ,  $sku_v['sku_attr']);
            $arr = json_decode($str,true);
            $str = '';
            foreach($arr as $k=>$v){
                $str .= $v . ',';
            }
            $str = rtrim($str,',');
            $skuRes[$sku_k]['sku_attr1'] = $str;

            // $skuRes[$sku_k]['sku_attr'] = json_decode($sku_v['sku_attr'],true);
        }
        $specData = array();
        $specData['spec_attr'] = $specRes;
        $specData['goods_sku'] = $skuRes;
        $specData['count_num'] = $count_num;
        $specData['min_groupon_price'] = $min ? min($min) : 0;
        return $specData;
    }

    //获取商品sku字符串
    public function get_sku_str($sku_id)
    {
        $sku_attr = Db::name('goods_sku')->where('sku_id', $sku_id)->value('sku_attr');
        
        if(!$sku_attr){
            return false;
        }
        $sku_attr = preg_replace("/(\w+):/",  '"$1":' ,  $sku_attr);
        $sku_attr = json_decode($sku_attr, true);
        foreach($sku_attr as $key=>$value){
            $spec_name = Db::table('goods_spec')->where('spec_id',$key)->value('spec_name');
            $attr_name = Db::table('goods_spec_attr')->where('attr_id',$value)->value('attr_name');
            $sku_attr[$spec_name] = $attr_name;
            unset($sku_attr[$key]);
        }

        $sku_attr = json_encode($sku_attr, JSON_UNESCAPED_UNICODE);
        $sku_attr = str_replace(array('{', '"', '}'), array('', '', ''), $sku_attr);

        return $sku_attr;
    }

    /**
     *  商品分类
     */
    public function goods_cate () {
        $where['level'] = 1;
        $where['is_show'] = 1;
        $field = 'cat_id,cat_name,img,is_show';
        $list = Db::table('category')->where($where)->order('sort desc')->field($field)->select();
        return json(['cede'=>1,'msg'=>'','data'=>$list]);
    }

    /**
     *  商品列表
     */
    public function goods_list () {
        $keyword = request()->param('keyword','');
        $cat_id = request()->param('cat_id',0,'intval');
        $page = request()->param('page',0,'intval');
        $goods = new Goods();
        $list = $goods->getGoodsList($keyword,$cat_id,$page);
        if (!empty($list)){
            return json(['code'=>1,'msg'=>'','data'=>$list]);
        }else{
            return json(['code'=>-2,'msg'=>'没有数据哦','data'=>$list]);
        }
    }

    /**
     *  指定属性商品
     */
    public function selling_goods ()
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $page = request()->param('page',0,'intval');
        $attr = request()->param('attr',0,'intval');
        $field = 'goods_id,goods_name,price,stock,number_sales,desc';
        $list = model('Goods')->where($where)->where("find_in_set($attr,`goods_attr`)")->field($field)->paginate(4,'',['page'=>$page]);
        if (!empty($list)){
            foreach ($list as &$v){
                $v['picture'] = Db::table('goods_img')->where(['goods_id'=>$v['goods_id'],'main'=>1])->value('picture');
            }
        }
        return json(['code'=>1,'msg'=>'','data'=>$list]);
    }

    /**
     * 限时购
     */
    public function limited_list(){

        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        //限时购专区图片
        $limited_img = Db::table('category')->where('cat_name','like',"%限时购%")->value('img');

        $page = input('page');
        
        $where['is_show'] =  1;
        $where['main'] =  1;
        $where['is_del'] =  0;

        $pageParam['query']['is_show'] = 1;
        $pageParam['query']['is_del'] = 0;


        $list = Db::table('goods')->alias('g')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where($where)
                ->where("FIND_IN_SET(6,goods_attr)")
                ->field('g.goods_id,goods_name,goods_attr,gi.picture img,desc,limited_start,limited_end,price,original_price,stock,stock1')
                ->paginate(5,false,$pageParam);
        $list = $list->all();
        $arr = [];
        if($list){
            foreach($list as $key=>$value){
                if($value['limited_end'] < time()){
                    $attr = explode(',',$value['goods_attr']);
                    $k =  array_search(6,$attr);
                    unset($attr[$k]);
                    $goods_attr = implode(',',$attr);
                    Db::table('goods')->where('goods_id',$value['goods_id'])->update(['goods_attr'=>$goods_attr]);
                    continue;
                }
                $value['purchased'] = $value['stock1'] - $value['stock'];
                $value['surplus'] = $value['stock1'] - $value['purchased'];      //剩余量
                if($value['surplus']){
                    $value['surplus_percentage'] = $value['surplus'] / $value['stock1'];      //剩余百分比
                }else{
                    $value['surplus_percentage'] = 0;      //剩余百分比
                }
                unset($value['goods_attr'],$value['stock'],$value['stock1']);

                $arr[] = $value;
            }
        }

        $this->ajaxReturn(['status' => 1 , 'msg'=>'获取成功','data'=>['list'=>$arr,'limited_img'=>$limited_img]]);
    }

    function ts(){
        phpinfo();
    }

    public function praise(){

        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $comment_id = input('comment_id');

        $where['comment_id'] = $comment_id;
        $where['user_id'] = $user_id;

        $res = Db::table('goods_comment_praise')->where($where)->find();

        if($res){
            Db::table('goods_comment')->where('id',$comment_id)->setDec('praise',1);
            Db::table('goods_comment_praise')->where($where)->delete();
            
            $this->ajaxReturn(['status' => 1 , 'msg'=>'取消点赞成功！','data'=>'']);
        }else{
            Db::table('goods_comment')->where('id',$comment_id)->setInc('praise',1);
            Db::table('goods_comment_praise')->insert($where);
            $this->ajaxReturn(['status' => 1 , 'msg'=>'点赞成功！','data'=>'']);
        }
    }
}
