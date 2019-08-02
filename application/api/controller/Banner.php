<<<<<<< HEAD
<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/5/29
 * Time: 18:43
 */

namespace app\api\controller;

use app\common\controller\ApiAbstract;
use think\Db;

class Banner extends ApiAbstract
{

    public function banner () {
        $banners=Db::name('advertisement')->field('picture,title,url')->where(['type'=>0,'state'=>1])->order('sort','desc')->limit(3)->select();
        foreach($banners as $bk=>$bv){
            $banners[$bk]['picture']=SITE_URL.$bv['picture'];
        }
        if($banners){
            return $this->successResult($banners);
        }else{
            return $this->failResult('暂无数据', 301);
        }
    }
    
    public function announce()
    {
        $announce=Db::name('announce')->field('id,title,urllink as link,desc')->where(['status'=>1])->order('create_time','desc')->limit(3)->select();
        if($announce){
            return $this->successResult($announce);
        }else{
            return $this->failResult("暂无数据",401);
        }    
    }



    public function getAllData ($only_logo,$type=0){
        return Db::table('page_advertisement')->alias('a')->join('advertisement b','a.id = b.page_id','left')
            ->where(['a.only_logo'=>$only_logo,'a.status'=>1,'b.state'=>1,'b.type'=>$type])->field('b.*')->select();
    }


=======
<?php
/**
 * Created by PhpStorm.
 * User: MyPC
 * Date: 2019/5/29
 * Time: 18:43
 */

namespace app\api\controller;

use app\common\controller\ApiAbstract;
use think\Db;

class Banner extends ApiAbstract
{

    public function banner () {
        $banners=Db::name('advertisement')->field('picture,title,url')->where(['type'=>0,'state'=>1])->order('sort','desc')->limit(3)->select();
        foreach($banners as $bk=>$bv){
            $banners[$bk]['picture']=SITE_URL.$bv['picture'];
        }
        if($banners){
            return $this->successResult($banners);
        }else{
            return $this->failResult('暂无数据', 301);
        }
    }
    
    public function announce()
    {
        $announce=Db::name('announce')->field('id,title,urllink as link,desc,picture')->where(['status'=>1])->order('create_time','desc')->limit(3)->select();
        if($announce){
            return $this->successResult($announce);
        }else{
            return $this->failResult("暂无数据",401);
        }    
    }


    //资讯详情
    public function info_detail(){
        $id=input('id');
        if($id){
            $info_detail=Db::name('info')->where(['id'=>$id])->find();  
            $info_detail['create_time']=date('Y-m-d',$info_detail['create_time']);
            $info_detail['picture']=SITE_URL.$info_detail['picture'];
            if($info_detail){
                $this->successResult($info_detail);
            }else{
                $this->failResult("获取资讯数据失败");
            }
        }
    }

    //公告详情
    public function announce_detail()
    {
        $id=input('id');
        if($id){
            $info_detail=Db::name('announce')->where(['id'=>$id])->find();  
            $info_detail['create_time']=date('Y-m-d',$info_detail['create_time']);
            $info_detail['picture']=SITE_URL.$info_detail['picture'];
            if($info_detail){
                $this->successResult($info_detail);
            }else{
                $this->failResult("获取资讯数据失败");
            }
        }

    }



    public function getAllData ($only_logo,$type=0){
        return Db::table('page_advertisement')->alias('a')->join('advertisement b','a.id = b.page_id','left')
            ->where(['a.only_logo'=>$only_logo,'a.status'=>1,'b.state'=>1,'b.type'=>$type])->field('b.*')->select();
    }


>>>>>>> f377d99b55fa752952b0dcb0ec019888e7c51a99
}