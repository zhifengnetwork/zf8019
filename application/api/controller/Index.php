<<<<<<< HEAD
<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\controller\ApiBase;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Index extends ApiBase
{


    /**
     * @api {GET} /index/index 首页
     * @apiGroup index
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *       "status": 200,
     *       "msg": "获取成功",
     *       "data": {
     *           "banners": [
     *           {
     *               "picture": "http://api.retail.com\\uploads\\fixed_picture\\20190529\\bce5780d314bb3bfd3921ffefc77fcdd.jpeg",
     *               "title": "个人中心个人资料和设置",
     *               "url": "www.cctvhong.com"
     *           },
     *           {
     *               "picture": "http://api.retail.com\\uploads\\fixed_picture\\20190529\\94cbe33d1e15a5ebdd92cd0e3a4f4f19.jpeg",
     *               "title": "13.2 我的钱包-提现记录",
     *               "url": "www.ceshi.com"
     *           },
     *           {
     *               "picture": "http://api.retail.com\\uploads\\fixed_picture\\20190529\\414eac4f30c011288ae42e822cb637cc.jpeg",
     *               "title": "钱包转换",
     *               "url": "www.ceshi.com"
     *           }
     *           ],banner轮播图
     *           "announce": [
     *           
     *           ],公告
     *           "hot_goods": [
     *           {
     *               "goods_id": 39,
     *               "goods_name": "本草",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "200.00",
     *               "original_price": "250.00"
     *           },
     *           {
     *               "goods_id": 18,
     *               "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "2188.00",
     *               "original_price": "2588.00"
     *           }
     *           ],热门商品
     *           "recommend_goods": {
     *           "total": 2,
     *           "per_page": 4,
     *           "current_page": 1,
     *           "last_page": 1,
     *           "data": [
     *               {
     *               "goods_id": 39,
     *               "goods_name": "本草",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "200.00",
     *               "original_price": "250.00"
     *               },
     *               {
     *               "goods_id": 36,
     *               "goods_name": "美的（Midea） 三门冰箱 ",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "50.00",
     *               "original_price": "40.00"
     *               }
     *           ]
     *           }推荐商品
     *       }
     *       }
     * //错误返回结果
     * 无
     */
    public function index()
    {

        $banners=Db::name('advertisement')->field('picture,title,url')->where(['type'=>0,'state'=>1])->order('sort','desc')->limit(3)->select();
        if($banners){
            foreach($banners as $bk=>$bv){
                $banners[$bk]['picture']=SITE_URL.$bv['picture'];
            }
        }
        
        $announce=Db::name('announce')->field('id,title,urllink as link,desc')->where(['status'=>1])->order('create_time','desc')->limit(3)->select();

        $hot_goods = Db::table('goods')->alias('g')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where('gi.main',1)
                ->where('g.is_show',1)
                ->where('g.is_del',0)
                ->where('FIND_IN_SET(3,g.goods_attr)')
                ->order('g.goods_id DESC')
                ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
                ->limit(4)
                ->select();
        
        if($hot_goods){
            foreach($hot_goods as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') .$value['img'];
            }
        }
        
        $recommend_goods = Db::table('goods')->alias('g')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where('gi.main',1)
                ->where('g.is_show',1)
                ->where('g.is_del',0)
                ->where('FIND_IN_SET(1,g.goods_attr)')
                ->order('g.goods_id DESC')
                ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
                ->paginate(4);

        if($recommend_goods){
            $recommend_goods = $recommend_goods->all();
            foreach($recommend_goods as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') .$value['img'];
            }
        }

        $goods_gift = Db::table('goods')->alias('g')
                ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
                ->where('gi.main',1)
                ->where('g.is_show',1)
                ->where('g.is_del',0)
                ->where('g.is_gift',1)
                ->order('g.goods_id DESC')
                ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
                ->paginate(4);

        if($goods_gift){
            $goods_gift = $goods_gift->all();
            foreach($goods_gift as $key=>&$value){
                $value['img'] = Config('c_pub.apiimg') .$value['img'];
            }
        }
        
        $this->ajaxReturn(['status' => 200 , 'msg'=>'获取成功','data'=>['banners'=>$banners,'announce'=>$announce,'hot_goods'=>$hot_goods,'recommend_goods'=>$recommend_goods,'goods_gift'=>$goods_gift]]);
    }

    
}
=======
<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\controller\ApiBase;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Index extends ApiBase
{


    /**
     * @api {GET} /index/index 首页
     * @apiGroup index
     * @apiVersion 1.0.0
     *
     * @apiParamExample {json} 请求数据:
     * {
     *     
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {
     *       "status": 200,
     *       "msg": "获取成功",
     *       "data": {
     *           "banners": [
     *           {
     *               "picture": "http://api.retail.com\\uploads\\fixed_picture\\20190529\\bce5780d314bb3bfd3921ffefc77fcdd.jpeg",
     *               "title": "个人中心个人资料和设置",
     *               "url": "www.cctvhong.com"
     *           },
     *           {
     *               "picture": "http://api.retail.com\\uploads\\fixed_picture\\20190529\\94cbe33d1e15a5ebdd92cd0e3a4f4f19.jpeg",
     *               "title": "13.2 我的钱包-提现记录",
     *               "url": "www.ceshi.com"
     *           },
     *           {
     *               "picture": "http://api.retail.com\\uploads\\fixed_picture\\20190529\\414eac4f30c011288ae42e822cb637cc.jpeg",
     *               "title": "钱包转换",
     *               "url": "www.ceshi.com"
     *           }
     *           ],banner轮播图
     *           "announce": [
     *           
     *           ],公告
     *           "hot_goods": [
     *           {
     *               "goods_id": 39,
     *               "goods_name": "本草",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "200.00",
     *               "original_price": "250.00"
     *           },
     *           {
     *               "goods_id": 18,
     *               "goods_name": "美的（Midea） 三门冰箱 风冷无霜家",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "2188.00",
     *               "original_price": "2588.00"
     *           }
     *           ],热门商品
     *           "recommend_goods": {
     *           "total": 2,
     *           "per_page": 4,
     *           "current_page": 1,
     *           "last_page": 1,
     *           "data": [
     *               {
     *               "goods_id": 39,
     *               "goods_name": "本草",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "200.00",
     *               "original_price": "250.00"
     *               },
     *               {
     *               "goods_id": 36,
     *               "goods_name": "美的（Midea） 三门冰箱 ",
     *               "img": "http://zfwl.zhifengwangluo.c3w.cc/upload/images/goods/20190514155782540787289.png",
     *               "price": "50.00",
     *               "original_price": "40.00"
     *               }
     *           ]
     *           }推荐商品
     *       }
     *       }
     * //错误返回结果
     * 无
     */
    public function index()
    {

        $banners=Db::name('advertisement')->field('picture,title,url')->where(['type'=>0,'state'=>1])->order('sort','desc')->limit(3)->select();
        if($banners){
            foreach($banners as $bk=>$bv){
                $banners[$bk]['picture']=SITE_URL.$bv['picture'];
            }
        }
        
        $announce=Db::name('announce')->field('id,title,urllink as link,desc,picture')->where(['status'=>1])->order('create_time','desc')->limit(3)->select();
        if($announce){
            foreach($announce as $ak=>$av){
                $announce[$ak]['picture']=SITE_URL.$av['picture'];
            }
        }

        // $hot_goods = Db::table('goods')->alias('g')
        //         ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
        //         ->where('gi.main',1)
        //         ->where('g.is_show',1)
        //         ->where('g.is_del',0)
        //         ->where('FIND_IN_SET(3,g.goods_attr)')
        //         ->order('g.goods_id DESC')
        //         ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
        //         ->limit(4)
        //         ->select();
        
        // if($hot_goods){
        //     foreach($hot_goods as $key=>&$value){
        //         $value['img'] = Config('c_pub.apiimg') .$value['img'];
        //     }
        // }
        
        // $recommend_goods = Db::table('goods')->alias('g')
        //         ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
        //         ->where('gi.main',1)
        //         ->where('g.is_show',1)
        //         ->where('g.is_del',0)
        //         ->where('FIND_IN_SET(1,g.goods_attr)')
        //         ->order('g.goods_id DESC')
        //         ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
        //         ->paginate(4);

        // if($recommend_goods){
        //     $recommend_goods = $recommend_goods->all();
        //     foreach($recommend_goods as $key=>&$value){
        //         $value['img'] = Config('c_pub.apiimg') .$value['img'];
        //     }
        // }

        // $goods_gift = Db::table('goods')->alias('g')
        //         ->join('goods_img gi','gi.goods_id=g.goods_id','LEFT')
        //         ->where('gi.main',1)
        //         ->where('g.is_show',1)
        //         ->where('g.is_del',0)
        //         ->where('g.is_gift',1)
        //         ->order('g.goods_id DESC')
        //         ->field('g.goods_id,goods_name,gi.picture img,price,original_price')
        //         ->paginate(4);

        // if($goods_gift){
        //     $goods_gift = $goods_gift->all();
        //     foreach($goods_gift as $key=>&$value){
        //         $value['img'] = Config('c_pub.apiimg') .$value['img'];
        //     }
        // }

        $info=Db::name('info')->field('id,title,urllink as link,desc,picture')->where(['status'=>1])->order('create_time','desc')->limit(3)->select();
        if($info){
            foreach($info as $ik=>$iv){
                $info[$ik]['picture']=SITE_URL.$iv['picture'];
            }
        }
        
        $this->ajaxReturn(['status' => 200 , 'msg'=>'获取成功','data'=>['banners'=>$banners,'announce'=>$announce,'info'=>$info]]);
    }

    
}
>>>>>>> f377d99b55fa752952b0dcb0ec019888e7c51a99
