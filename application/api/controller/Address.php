<?php
/**
 * Created by PhpStorm.
 * User: zgp
 * Date: 2019/7/2 0002
 * Time: 17:51
 */

namespace app\api\controller;

use app\common\controller\ApiBase;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\util\jwt\JWT;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;
use app\api\model\UserAddr;
use think\Cache;

class Address extends ApiBase
{

    
      /**
     * @api {POST} /address/addressList 地址列表
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParam {string}    token              token*（必填）
     * @apiParamExample {json} 请求数据:
     * {
     *      "token":"xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,
     * "msg":"success",
     * "data":[{
     *      "address_id":1055,
     *      "consignee":"等奖",
     *      "mobile":"15181112455",
     *      "address":"啊实打实的",
     *      "is_default":1,
     *      "p_cn":"北京市",
     *       "c_cn":"北京市",
     *      "d_cn":"东城区",
     *      "s_cn":null
     * }]}
     * * //错误返回结果
     * {
     * "status": 301,
     * "msg": "暂无地址信息",
     * "data": false
     * }
     */
    public function addressList()
    {
//        if(!Request::instance()->isPost()){
//            return $this->getResult(301, 'error', '请求方式有误');
//        }

        $user_id = $this->get_user_id();
        if(!$user_id||is_array($user_id)){
            return $this->failResult("用户不存在");
        }
        // $user_id = 42;  //userid为42 测试用
        $useraddr=new UserAddr();
        
        $addlist=$useraddr->getAddressList(['user_id'=>$user_id]);
        if($addlist){
            return $this->successResult($addlist);
        }else{
            return $this->failResult("暂无地址信息");
        }
    }



    /**
     * @api {POST} /address/addAddress 地址添加和编辑
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParam {string}    token                  token*（必填）
     * @apiParam {string}    address_id             address_id（选填,没有为添加模式）
     * @apiParam {string}    consignee              客户名称（必填）
     * @apiParam {string}    district               区id（必填）
     * @apiParam {string}    address               地址（必填）
     * @apiParam {string}    mobile               地址（必填）
     * @apiParam {string}    is_default              默认地址（必填）
     * @apiParamExample {json} 请求数据:
     * {
     *      "token":"xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      "address_id":"xxxxxxxx",
     *      "consignee"："xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      "district"："xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      "address"："xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      "mobile"："xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      "is_default"："xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,
     * "msg":"添加成功",
     * "data":[]
     * }
     * * //错误返回结果
     * {
     * "status": 301,
     * "msg": "操作失败",
     * "data": false
     * }
     */
    public function addAddress()
    {
        if(!Request::instance()->isPost()){
            return $this->failResult("请求方式错误");
        }
        $data=input('post.');
        // $user_id=42;//测试
        $user_id=$this->get_user_id();
       
        if(!$user_id||is_array($user_id)){
            return $this->failResult("用户不存在");
        }
        $address_id=$data['address_id']?$data['address_id']:0;
        $useraddr=new UserAddr();
        return $useraddr->add_address($user_id,$address_id,$data);
    }

    
    /**
     * @api {POST} /address/delAddress 地址删除
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParam {string}    token                  token*（必填）
     * @apiParam {string}    id                     地址id（必填）
   
     * @apiParamExample {json} 请求数据:
     * {
     *      "token":"xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *      "id":"xxxxxxxx",
     *  
     *      
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,
     * "msg":"删除成功",
     * "data":[]
     * }
     * * //错误返回结果
     * {
     * "status": 301,
     * "msg": "操作失败",
     * "data": false
     * }
     */
    public function delAddress()
    {
        if(!Request::instance()->isPost()){
            return $this->failResult("请求方式错误");
        }        
        $id=input('post.id');
        $one=Db::name("user_address")->where("address_id",$id)->find();
        if(!$id||is_null($one)){
            return $this->failResult("传入参数无效");
        }
        $res=Db::name("user_address")->where("address_id",$id)->delete();
        if($res){
           return $this->successResult("删除成功");
        }else{
            return $this->failResult("操作失败");
        }
    }

    public function my_address()
    {
        $address_id=input("post.address_id");
        $my_address=Db::name('user_address')->where("address_id",$address_id)->find();
       if(!empty($my_address)){
            return $this->successResult($my_address);
       }else{
           return $this->failResult("操作失败");
       }
    }

       /**
     * @api {GET} /address/get_region 获取地区下级
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParam {string}    code                     地址code（选填  如果没有参数会返回所有的省份）
   
     * @apiParamExample {json} 请求数据:
     * {
     *      "code":"xxxxxxxx",
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,
     * "msg":"success",
     * "data":[{"area_id":3,"code":"1101","parent_id":"11","area_name":"北京市","area_type":2}]
     * }
     * * //错误返回结果
     * {
     * "status": 301,
     * "msg": "没有数据",
     * "data": false
     * }
     */
    public function get_region(){
        $codeid=input('get.code');
        if(is_null($codeid)||empty($codeid)){
            $res=Db::name('region')->where('parent_id',100000)->select();
        }else{
            $res=Db::name('region')->where('parent_id',$codeid)->select();
        }
        if($res){
            return $this->successResult($res);
        }else{
            return $this->failResult('没有数据');
        }
    }



        /**
     * @api {POST} /address/get_default_city 获取默认地区
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParam {string}    token                    token 必填
   
     * @apiParamExample {json} 请求数据:
     * {
     *      "token":"xxxxxxxx",
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,"msg":"success","data":{"address_id":1100,"user_id":84,"consignee":"chenchen1222","email":"","country":0,"province":2,"city":3,"district":4,"twon":0,"address":"嘉禾望岗","zipcode":"","mobile":"13202029884","is_default":1,"longitude":"0.0000000","latitude":"0.0000000","poiaddress":null,"poiname":null,
     * "districtname":"东城区","cityname":"北京市","provincename":"北京市"  //省市区
     * }}
     * * //错误返回结果
     * {
     * "status": 301,
     * "msg": "没有数据",
     * "data": false
     * }
     */
    public function get_default_city()
    {
        $user_id = $this->get_user_id();
        // $user_id=84;
        if(!Request::instance()->isPost()){
            return $this->failResult("请求方式错误");
        }
        $defaultCity=Db::name("user_address")->alias("ua")
        ->join("region re","re.area_id=ua.district","LEFT")
        ->join("region re1","re1.area_id=ua.city","LEFT")
        ->join("region re2","re2.area_id=ua.province","LEFT")
        ->where(["ua.user_id"=>$user_id,'ua.is_default'=>1])
        ->field("ua.*,re.area_name as districtname,re1.area_name as cityname,re2.area_name as provincename")->find();
        if($defaultCity){
            return $this->successResult($defaultCity);
        }else{
            return $this->failResult("没有设置地址");
        }
    }

      /**
     * @api {POST} /address/set_default_address 设置默认地区
     * @apiGroup user
     * @apiVersion 1.0.0
     *
     * @apiParam {string}    token                    token 必填
     * @apiParam {string}    address_id                    address_id 必填
   
     * @apiParamExample {json} 请求数据:
     * {
     *      "token":"xxxxxxxx",
     * }
     * @apiSuccessExample {json} 返回数据：
     * //正确返回结果
     * {"status":200,"msg":"success","data":操作成功
     * }}
     * * //错误返回结果
     * {
     * "status": 301,
     * "msg": "操作失败",
     * "data": false
     * }
     */
    public function set_default_address()
    {
        $user_id=$this->get_user_id();
        if(!Request::instance()->isPost()){
            return $this->failResult("请求方式错误");
        }
        $address_id=input("address_id");
        if(!$address_id){
            return $this->failResult("地址id不能为空");
        }
        $user_address=Db::name("user_address")->where('user_id',$user_id)->select();
        foreach($user_address as $k=>$v){
           Db::name('user_address')->where("address_id",$v['address_id'])->update(['is_default'=>0]);
        }
        $setDefault=Db::name("user_address")->where("address_id",$address_id)->update(['is_default'=>1]);
        if($setDefault){
            return $this->successResult("操作成功");
        }else{
            return $this->failResult("操作失败");
        }
    } 


}