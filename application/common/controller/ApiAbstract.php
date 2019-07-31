<?php
/**
 * 继承
 */
namespace app\common\controller;
use app\common\util\jwt\JWT;
use think\Db;
use think\Controller;
use app\common\model\Config;
use think\Request;
use app\common\util\Redis;

class ApiAbstract extends Controller
{
    protected $uid;
    protected $user_name;
    protected $is_bing_mobile;

    public $param;
    public $userInfo;
    private static $redis = null;
    const PAGE_SIZE = 20;   // 每页条数

    public function _initialize () {
        parent::_initialize();
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:*");
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header('Content-Type:application/json; charset=utf-8');

        $param = Request::instance()->param();
        $this->param = isset($param) && !empty($param) ? $param : '';

    }

    /*获取redis对象*/
    protected function getRedis(){
        if(!self::$redis instanceof Redis){
            self::$redis = new Redis(Config('cache.redis'));
        }
        return self::$redis;
    }

    public function ajaxReturn($data){
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:*');
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header('Content-Type:application/json; charset=utf-8');
        exit(str_replace("\\/", "/",json_encode($data,JSON_UNESCAPED_UNICODE)));
    }

    /**
     * 发送手机验证码
     * @param $mobile   手机号
     * @param $auth     md5($mobile)
     * @param $type     默认为1
     * @return array    'status' => 1 , 'msg'=>'发送成功！'   status 1:表示发送成功；-2 表示发送失败；
     */
    public function sendPhoneCode($mobile,$auth,$type)
    {

        if( !$mobile || !$auth ){
            return ['status'=>-1,'msg'=>'参数错误'];
        }
        $Md5 = md5($mobile);
        if( $Md5 != $auth ){
            return ['status' => -2 , 'msg'=>'非法请求！'];
        }
        $phone_number = checkMobile($mobile);
        if ($phone_number == false) {
            return ['status' => -2 , 'msg'=>'手机号码格式不对！'];
        }

        $res = Db::name('phone_auth')->field('exprie_time')->where('mobile','=',$mobile)->order('id DESC')->find();

        if( $res['exprie_time'] > time() ){
            return ['status' => -2 , 'msg'=>'请求频繁请稍后重试！'];
        }

        $code = mt_rand(111111,999999);

        $data['mobile'] = $mobile;
        $data['auth_code'] = $code;
        $data['start_time'] = time();
        $data['exprie_time'] = time() + 60;

        $res = Db::table('phone_auth')->insert($data);
        if(!$res){
            return ['status' => -2 , 'msg'=>'发送失败，请重试！'];
        }
//      测试默认通过  zgp
//        $ret['message'] = 'ok';
//        正式启动验证码 zgp
        // $ret = send_zhangjun($mobile, $code);   //正式启用这个

        $ret['message'] = 'ok';   //测试用

        if($ret['message'] == 'ok'){
            return ['status' => 1 , 'msg'=>'发送成功！'];
        }
        return ['status' => -2 , 'msg'=>'发送失败，请重试！'];
    }

    /**
     * 发送撮合交易短信
     * @param $mobile   手机号
     * @param $temp     sms_transaction
     * @param $auth     md5($mobile . $temp)
     * @param $type     0 发送给卖方；1 发送给买方
     * @return array    'status' => 1 , 'msg'=>'发送成功！'   status 1:表示发送成功；-2 表示发送失败；
     */
    public function sendTransactionPhoneCode($mobile,$type){
        if( !$mobile ){
            return ['status'=>-1,'msg'=>'参数错误'];
        }

        $res = Db::name('phone_auth')->field('exprie_time')->where('mobile','=',$mobile)->order('id DESC')->find();

//        if( $res['exprie_time'] > time() ){
//            return ['status' => -2 , 'msg'=>'请求频繁请稍后重试！'];
//        }

        $code = mt_rand(111111,999999);

        $data['mobile'] = $mobile;
        $data['auth_code'] = $code;
        $data['start_time'] = time();
        $data['exprie_time'] = time() + 60;

        $res = Db::table('phone_auth')->insert($data);
        if(!$res){
            return ['status' => -2 , 'msg'=>'发送失败，请重试！'];
        }
//      测试默认通过  zgp
//        $ret['message'] = 'ok';
        if($type==1){//发送给买方
            $ret = send_zhangjun_buyer($mobile);
        }else{//发送给卖方
            $ret = send_zhangjun_seller($mobile);
        }
        if($ret['message'] == 'ok'){
            return ['status' => 1 , 'msg'=>'发送成功！'];
        }
        return ['status' => -2 , 'msg'=>'发送失败，请重试！'];
    }

    /**
     * 校验验证码是否过期
     * @param $mobile
     * @param $auth_code
     * @return bool|int
     */
    public function phoneAuth($mobile, $auth_code)
    {
        $res = Db::name('phone_auth')->field('exprie_time')->where('mobile','=',$mobile)->where('auth_code',$auth_code)->order('id DESC')->find();

        if ($res) {
            if ($res['exprie_time'] >= time()) { // 还在有效期就可以验证
                return true;
            } else {
                return -1;
            }
        }
        return false;
    }

    /**
     * 生成token
     */
    protected function create_token($user_id)
    {
        $time = time();
        $payload = array(
            "iss" => "DC",
            "iat" => $time,
            "exp" => $time + 36000,
            "user_id" => $user_id
        );
        $key = 'zhelishimiyao';
        $token = JWT::encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null);
        return $token;
    }


    /**
     * 空
     */
    public function _empty(){
        $this->ajaxReturn(['status' => 301 , 'msg'=>'接口不存在','data'=>null]);
    }

    public function successResult($data = [])
    {
        return $this->getResult(200, 'success', $data);
    }

    public function failResult($message, $status = 301)
    {
        return $this->getResult($status, $message, false);
    }

    public function getResult($status, $message, $data)
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:*');
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header('Content-Type:application/json; charset=utf-8');
        $return = [
            'status' => $status,
            'msg' => $message,
            'data' => $data
        ];
        exit(str_replace("\\/", "/",json_encode($return,JSON_UNESCAPED_UNICODE)));

    }

}
