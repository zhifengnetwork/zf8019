<?php
namespace app\index\controller;
use think\Verify;
use think\Db;
use app\common\logic\UsersLogic;

class Index
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
    }
    
     /**
     * 前端发送短信方法: APP/WAP/PC 共用发送方法
     * type:mobile
     * scene:1
     * mobile:手机号
     * send：手机号
     *  
     */
    public function send_validate_code()
    {
        $this->send_scene = Config('SEND_SCENE');

        $type = input('type');
        $scene = input('scene');    //发送短信验证码使用场景 
        $mobile = input('mobile');
        $sender = input('send');

        $verify_code = input('verify_code');
        $mobile = !empty($mobile) ? $mobile : $sender;
        $session_id = input('unique_id', session_id());
        session("scene", $scene);

        //注册
        if ($scene == 1 && !empty($verify_code)) {
            $verify = new Verify();
            if (!$verify->check($verify_code, 'user_reg')) {
                ajaxReturn(array('status' => -1, 'msg' => '图像验证码错误'));
            }
        }
        if ($type == 'email') {
            //发送邮件验证码
            $logic = new UsersLogic();
            $res = $logic->send_email_code($sender);
            ajaxReturn($res);
        } else {
            //发送短信验证码
            $res = $this->checkEnableSendSms($scene);
            if ($res['status'] != 1) {
                ajaxReturn($res);
            }
            //判断是否存在验证码
            $data = Db::name('sms_log')->where(array('mobile' => $mobile, 'session_id' => $session_id, 'status' => 1))->order('id DESC')->find();
            //获取时间配置
            $sms_time_out = Cache('sms_time_out');
            $sms_time_out = $sms_time_out ? $sms_time_out : 120;
            //120秒以内不可重复发送
            if ($data && (time() - $data['add_time']) < $sms_time_out) {
                $return_arr = array('status' => -1, 'msg' => $sms_time_out . '秒内不允许重复发送');
                ajaxReturn($return_arr);
            }
            //随机一个验证码
            $code = rand(1000, 9999);
            $params['code'] = $code;

            //发送短信
            $resp = sendSms($scene, $mobile, $params, $session_id);

            if ($resp['status'] == 1) {
                //发送成功, 修改发送状态位成功
                Db::name('sms_log')->where(array('mobile' => $mobile, 'code' => $code, 'session_id' => $session_id, 'status' => 0))->save(array('status' => 1));
                $return_arr = array('status' => 1, 'msg' => '发送成功,请注意查收');
            } else {
                $return_arr = array('status' => -1, 'msg' => '发送失败' . $resp['msg']);
            }
            ajaxReturn($return_arr);
        }
    }

    /**
 * 检测是否能够发送短信
 * @param unknown $scene
 * @return multitype:number string
 */
function checkEnableSendSms($scene)
{
    $scenes = Config('SEND_SCENE');
    $sceneItem = $scenes[$scene];
    if (!$sceneItem) {
        return array("status" => -1, "msg" => "场景参数'scene'错误!");
    }
    $key = $sceneItem[2];
    $sceneName = $sceneItem[0];
    // $config = Cache('sms');
    // $smsEnable = $config[$key];
    // var_dump($config);die;

    // $isCheckRegCode = Cache('regis_sms_enable');
    // $isCheckRegCode =1;
    // if (!$isCheckRegCode || $isCheckRegCode === 0) {
    //     return array("status" => 0, "msg" => "短信验证码功能关闭, 无需校验验证码");
    // }

    // if (!$smsEnable) {
    //     return array("status" => -1, "msg" => "['$sceneName']发送短信被关闭'");
    // }
    //判断是否添加"注册模板"
    $size = Db::name('sms_template')->where("send_scene", $scene)->count('tpl_id');
    if (!$size) {
        return array("status" => -1, "msg" => "请先添加['$sceneName']短信模板");
    }

    return array("status" => 1, "msg" => "可以发送短信");
}











}
