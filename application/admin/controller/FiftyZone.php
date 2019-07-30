<?php
namespace app\admin\controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Config;
use think\Image;
/*
 * 50元专区
 */
class FiftyZone extends Common
{
    /*
     * 设置
     */
    public function set(){

        $info = Db::table('config')->where('module',5)->column('value','name');
        
        if( request()->isPost() ){
            $data = input('post.');
            
            if($info){
                if(isset($data['ailicode'])) $data['ailicode'] = $this->base_img($data['ailicode'],'fifty_zone','ailicode');
                if(isset($data['wechatcode'])) $data['wechatcode'] = $this->base_img($data['wechatcode'],'fifty_zone','wechatcode');
                if(isset($data['mayuncode'])) $data['mayuncode'] = $this->base_img($data['mayuncode'],'fifty_zone','mayuncode');
                
                $arr_key = array_keys($info);

                foreach($data as $key=>$value){
                    if(in_array($key,$arr_key)){
                        Db::name('config')->where('module',5)->where('name',$key)->update(['value'=>$value,'update_time'=>time()]);
                    }else{
                        Db::name('config')->insert(['name'=>$key,'module'=>5,'value'=>$value,'create_time'=>time()]);
                    }
                }
            }else{
                if(isset($data['ailicode'])) $data['ailicode'] = $this->base_img($data['ailicode'],'fifty_zone','ailicode');
                if(isset($data['wechatcode'])) $data['wechatcode'] = $this->base_img($data['wechatcode'],'fifty_zone','wechatcode');
                if(isset($data['mayuncode'])) $data['mayuncode'] = $this->base_img($data['mayuncode'],'fifty_zone','mayuncode');
                
                foreach($data as $key=>$value){
                    Db::name('config')->insert(['name'=>$key,'module'=>5,'value'=>$value,'create_time'=>time()]);
                }
            }

            $this->success('修改成功！');
        }

        return $this->fetch('',[
            'info'          =>  $info,
            'meta_title'    =>  '50元专区设置',
        ]);
    }

    /*
     * 商品列表
     */
    public function index()
    {   

        $where = [];
        $pageParam = ['query' => []];

        $where['g.is_del'] = 0;
        $where['g.goods_id'] = 50;
        // $pageParam['query']['is_del'] = 0;

        $is_show = input('is_show');
        if( $is_show ){
            $where['g.is_show'] = $is_show;
            $pageParam['query']['is_show'] = $is_show;
        }else if($is_show === '0'){
            $is_show = 0;
            $where['g.is_show'] = $is_show;
            $pageParam['query']['is_show'] = $is_show;
        }

        $goods_name = input('goods_name');
        if( $goods_name ){
            $where["g.goods_name"] = ['like', "%{$goods_name}%"];
            $pageParam['query']['goods_name'] = ['like', "%{$goods_name}%"];
        }

        $cat_id1 = input('cat_id1');
        if( $cat_id1 ){
            $where['g.cat_id1'] = $cat_id1;
            $pageParam['query']['cat_id1'] = $cat_id1;
        }
        
        $cat_id2 = input('cat_id2');
        if( $cat_id2 ){
            $where['g.cat_id2'] = $cat_id2;
            $pageParam['query']['cat_id2'] = $cat_id2;
        }

        $list  = Db::table('goods')->alias('g')
                ->join('category c1','c1.cat_id=g.cat_id1','LEFT')
                ->join('category c2','c2.cat_id=g.cat_id2','LEFT')
                ->order('goods_id DESC')
                ->field('g.*,c1.cat_name c1_name,c2.cat_name c2_name')
                ->where($where)
                ->paginate(1,false,$pageParam);

        //商品一级分类
        $cat_id11 = Db::table('category')->where('level',1)->select();
        //商品二级分类
        $cat_id22 = Db::table('category')->where('level',2)->select();

        return $this->fetch('',[
            'list'          =>  $list,
            'is_show'       =>  $is_show,
            'goods_name'    =>  $goods_name,
            'cat_id1'       =>  $cat_id1,
            'cat_id2'       =>  $cat_id2,
            'cat_id11'      =>  $cat_id11,
            'cat_id22'      =>  $cat_id22,
            
            'meta_title'    =>  '商品列表',
        ]);
    }

    /*
     * 修改商品
     */
    public function edit(){
        $goods_id = input('goods_id');

        if(!$goods_id || $goods_id!=50){
            $this->error('参数错误！');
        }
        $info = Db::table('goods')->find($goods_id);

        if( Request::instance()->isPost() ){
            $data = input('post.');
            
            //验证
            $validate = Loader::validate('Goods');
            if(!$validate->scene('edit')->check($data)){
                $this->error( $validate->getError() );
            }

            //图片处理
            if( isset($data['img']) && !empty($data['img'][0])){
                foreach ($data['img'] as $key => $value) {

                    $saveName = request()->time().rand(0,99999) . '.png';

                    $img=base64_decode($value);
                    //生成文件夹
                    $names = "goods" ;
                    $name = "goods/" .date('Ymd',time()) ;
                    if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
                        mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
                    }
                    //保存图片到本地
                    file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$img);

                    unset($data['img'][$key]);
                    $data['img'][] = $name.$saveName;
                }
                $data['img'] = array_values($data['img']);
                $img = Db::table('goods_img')->where('goods_id',$goods_id)->find();
                foreach ($data['img'] as $key => $value) {
                    if(!$img){
                        if(!$key){
                            $datas[$key]['main'] = 1;
                        }else{
                            $datas[$key]['main'] = 0;
                        }
                    }
                    
                    $datas[$key]['picture'] = $value;
                    $datas[$key]['goods_id'] = $data['goods_id'];
                }

                Db::table('goods_img')->insertAll($datas);
            }
            
            if ( Db::table('goods')->strict(false)->update($data) !== false ) {
                //添加操作日志
                slog($goods_id);
                $this->success('修改成功', url('FiftyZone/index'));
            } else {
                $this->error('修改失败');
            }
        }
        
        //商品属性
        $goods_attr = Db::table('goods_attr')->select();
        //商品一级分类
        $cat_id1 = Db::table('category')->where('level',1)->select();
        //商品组图
        $img = Db::table('goods_img')->where('goods_id','=',$goods_id)->select();
        //配送方式
        $delivery = Db::table('goods_delivery')->field('delivery_id,name')->where('is_show',1)->select();

        return $this->fetch('',[
            'meta_title'  =>  '编辑商品',
            'info'        =>  $info,
            'goods_attr'  =>  $goods_attr,
            'cat_id1'     =>  $cat_id1,
            'delivery'    =>  $delivery,
            'img'         =>  $img,
        ]);
    }

    /*
     * 已审核列表
     */
    public function audited_vouchers(){
        $where['fzo.shop_confirm'] = 1;
        $where['fzo.shop_user_id'] = 0;
        $pageParam['query']['shop_confirm'] = 1;
        $pageParam['query']['shop_user_id'] = 0;

        $mobile = input('mobile');
        if($mobile){
            $where['u.mobile'] = ['like',"%{$mobile}%"];
            $pageParam['query']['mobile'] = $mobile;
        }

        $list = Db::table('fifty_zone_order')->alias('fzo')
                ->join('member u','u.id=fzo.user_id')
                ->where($where)
                ->field('fzo.*,u.mobile')
                ->order('fzo.fz_order_id DESC')
                ->paginate(10,false,$pageParam);
        
        return $this->fetch('',[
            'list'          =>  $list,
            'mobile'        =>  $mobile,
            
            'meta_title'    =>  '已审核列表',
        ]);

    }

    /*
     * 未审核列表
     */
    public function audit_proof(){
        $where['fzo.user_confirm'] = 1;
        $where['fzo.shop_confirm'] = 0;
        $where['fzo.shop_user_id'] = 0;
        $pageParam['query']['user_confirm'] = 1;
        $pageParam['query']['shop_confirm'] = 0;
        $pageParam['query']['shop_user_id'] = 0;

        $mobile = input('mobile');
        if($mobile){
            $where['u.mobile'] = ['like',"%{$mobile}%"];
            $pageParam['query']['mobile'] = $mobile;
        }

        $list = Db::table('fifty_zone_order')->alias('fzo')
                ->join('member u','u.id=fzo.user_id')
                ->where($where)
                ->field('fzo.*,u.mobile')
                ->order('fzo.fz_order_id DESC')
                ->paginate(10,false,$pageParam);
        
        return $this->fetch('',[
            'list'          =>  $list,
            'mobile'        =>  $mobile,
            
            'meta_title'    =>  '未审核列表',
        ]);

    }

    /*
     * 通过审核
     */
    public function pass_proof(){
        $data['fz_order_id'] = input('fz_order_id');
        $data['shop_confirm'] = 1;

        $info = Db::table('fifty_zone_order')->where('fz_order_id',$data['fz_order_id'])->find();

        $res = Db::table('fifty_zone_order')->update($data);
        if($res){

            $res = Db::table('fifty_zone_order')->where('user_id',$info['user_id'])->where('is_show',1)->where('shop_user_id',0)->find();
            if(!$res){
                Db::table('fifty_zone_shop')->where('user_id',$info['user_id'])->where('is_show',0)->update(['is_show'=>1]);
            }

            jason([],'审核通过成功！');
        }
        jason([],'审核通过失败！',0);
    }

    /*
     * 图片
     */
    public function img(){

        if(request()->isPost()){
            $data = input('post.');
            $datas = [];
            //图片处理
            if( isset($data['picture']) ){
                $time = time();
                foreach ($data['picture'] as $key => $value) {

                    $saveName = $time . rand(0,99999) . '.png';

                    $img=base64_decode($value);
                    //生成文件夹
                    $names = "fifty_zone_img" ;
                    $name = "fifty_zone_img/" .date('Ymd',$time) ;
                    if (!file_exists(ROOT_PATH .Config('c_pub.img').$names)){ 
                        mkdir(ROOT_PATH .Config('c_pub.img').$names,0777,true);
                    }
                    //保存图片到本地
                    file_put_contents(ROOT_PATH .Config('c_pub.img').$name.$saveName,$img);

                    // unset($data['picture'][$key]);
                    $image = Image::open(ROOT_PATH .Config('c_pub.img').$name.$saveName);
                    $image->thumb(500,310,Image::THUMB_CENTER)->save(ROOT_PATH .Config('c_pub.img').$name.$saveName);
                    $datas[]['picture'] = $name.$saveName;
                }
                Db::table('fifty_zone_img')->insertAll($datas);
            }

            $this->success('成功');
        }

        $list = Db::table('fifty_zone_img')->select();
        return $this->fetch('',[
            'list'          =>  $list,
            
            'meta_title'    =>  '图片',
        ]);

    }

    /**
     * ajax删除图片
     */
    public function del_img(){
        if( request()->isAjax() ){
            $data['id'] = input('id');
            
            $info = Db::table('fifty_zone_img')->find($data['id']);
            if( !$info ){
                return 0;
            }

            @unlink(ROOT_PATH .Config('c_pub.img') . $info['picture']);
            //添加操作日志
            slog($data['id']);
            return Db::table('fifty_zone_img')->where('id','=',$data['id'])->delete();
        }
    }
    
}
