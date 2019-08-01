<?php

namespace app\admin\controller;


use app\common\model\Member as MemberModel;
use app\common\model\User as UserModel;
use app\common\model\Withdrawals;
use app\common\model\Users;
use app\common\model\AgentInfo;
use think\Request;
use think\Db;
use think\Loader;

class Member extends Common
{
    /**
     * 会员列表
     */
    public function index()
    {
        $where  =  array();

        $kw              = input('realname', '');
        $level           = input('level', '');

        if (!empty($level) || $level == 0) {
            $where['level'] = $level;
        }

        if (!empty($kw)) {
            is_numeric($kw) ? $where['id|mobile']   = ['like', "%{$kw}%"] : $where['mobile'] = ['like', "%{$kw}%"];
        }


        // 携带参数
        $carryParameter = [
            'kw'               => $kw,
            'level'            => $level,
        ];
        $list  = Db::name('member')
            ->field('id,realname,level,mobile,distribut_money,createtime,remainder_money,first_leader,status,bank_name,bank_card,pay_points,avatar')
            ->where($where)
            ->order('createtime desc')
            ->paginate(15, false, ['query' => $carryParameter]);

        return $this->fetch('', [
            'exportParam'  => $carryParameter,
            'kw'           => $kw,
            'level'        => $level,
            'list'     => $list,
            'levels'   => MemberModel::getLevels(),
        ]);
    }

    public function add_payment()
    {
        return $this->fetch();
    }

    private function &get_where()
    {
        $begin_time      = input('begin_time', '');
        $end_time        = input('end_time', '');
        $id              = input('mid', '');
        $kw              = input('realname', '');
        $followed        = input('followed', '');
        $isblack         = input('isblack', '');
        $level           = input('level', '');
        $groupid         = input('groupid', '');
        $where = [];
        if (!empty($id)) {
            $where['dm.id']    = $id;
        }
        if (!empty($followed)) {
            $where['f.state']   = $followed;
        }
        if (!empty($isblack)) {
            $where['dm.isblack'] = $isblack;
        }
        if (!empty($level)) {
            $where['dm.level'] = $level;
        }
        if (!empty($groupid)) {
            $where['dm.groupid'] = $groupid;
        }

        if (!empty($kw)) {
            is_numeric($kw) ? $where['dm.mobile'] = $kw : $where['dm.realname'] = $kw;
        }
        if ($begin_time && $end_time) {
            $where['dm.createtime'] = [['EGT', $begin_time], ['LT', $end_time]];
        } elseif ($begin_time) {
            $where['dm.createtime'] = ['EGT', $begin_time];
        } elseif ($end_time) {
            $where['dm.createtime'] = ['LT', $end_time];
        }
        $this->assign('kw', $kw);
        $this->assign('id', $id);
        $this->assign('followed', $followed);
        $this->assign('isblack', $isblack);
        $this->assign('level', $level);
        $this->assign('groupid', $groupid);
        $this->assign('begin_time', empty($begin_time) ? date('Y-m-d') : $begin_time);
        $this->assign('end_time', empty($end_time) ? date('Y-m-d') : $end_time);
        return $where;
    }
    /***
     * 会员详情
     */
    public function member_edit()
    {
        $uid = input('id');
        $memberOrder = Db::name('member m')
            ->join('order o', 'o.user_id=m.id', 'LEFT')
            ->field('count(o.order_id) as ordercount,m.realname,m.mobile,m.distribut_money,m.remainder_money,m.createtime,m.gender,m.pay_points,m.status,count(total_amount) as totalamount,m.id,m.avatar,m.id')
            ->where('m.id', $uid)
            ->where('o.order_status', 4)
            ->find();

        if (Request::instance()->isPost()) {
            $data = input('data/a');
            $member = Db::name('member')->find($data['id']);


            $memberRes = Db::name('member')->update($data);
            if ($memberRes !== false) {
                $this->success("编辑成功", "member/index");
            } else {
                $this->error("编辑失败");
            }
        }
        $this->assign('memberOrder', $memberOrder);
        return $this->fetch();
    }

    public function set_free()
    {
        $data['id'] = input('uid');
        $status = Db::name('member')->where('id', $data['id'])->value('status');
        $status == 1 ? $data['status'] = 0 : $data['status'] = 1;
        $freeRes = Db::name('member')->update($data);
        if ($freeRes) {
            echo 1;
        } else {
            echo 2;
        }
    }

    /***
     * 会员详情
     */
    public function member_isblack()
    {
        $uid     = input('id');
        $isblack = input('isblack');
        $member  = MemberModel::get($uid);
        if (empty($member)) {
            $this->error('会员不存在，无法设置黑名单!');
        }
        if ($member['isblack']) {
            $update['isblack'] = 0;
        } else {
            $update['isblack'] = 1;
        }
        $res = MemberModel::where(['id' => $uid])->update($update);
        if ($res !== false) {
            $this->success('设置成功');
        }
        $this->error('设置失败!');
    }


    /***
     * 会员删除
     */
    public function member_delete()
    {
        $uid     = input('id');
        $member  = MemberModel::get($uid);
        if (empty($member)) {
            $this->error('会员不存在，无法删除!');
        }
        $res = MemberModel::where(['id' => $uid])->delete();

        if ($res !== false) {
            $this->success('删除成功');
        }
        $this->error('删除失败!');
    }


    public function group()
    {
        $where       = array();
        $membercount = Db::table('member')->where(['groupid' => 0])->count();
        $list        =  [
            [
                'id'          => 0,
                'groupname'   => '无分组',
                'membercount' => $membercount
            ]
        ];
        $alllist  = Db::table('member_group')
            ->field('*')
            ->where($where)
            ->order('id')
            ->select();
        foreach ($alllist as &$row) {
            $row['membercount'] = Db::table('member')->where(['groupid' => $row['id']])->count();
        }
        unset($row);
        $list = array_merge($list, $alllist);
        $this->assign('list', $list);
        $this->assign('meta_title', '会员分组设置');
        return $this->fetch();
    }

    public function group_add()
    {

        if (Request::instance()->isPost()) {
            $groupname = input('groupname', '');
            if (empty($groupname)) {
                $this->error('分组名称不能为空');
            }
            $add['groupname'] = $groupname;
            $res = Db::table('member_group')->insert($add);
            if ($res !== false) {
                $this->success('新增成功', url('member/group'));
            }
            $this->error('新增失败');
        }
        $this->assign('meta_title', '会员分组新增');
        return $this->fetch();
    }

    public function group_edit()
    {
        $id = input('id');
        if (Request::instance()->isPost()) {
            $groupname = input('groupname', '');
            if (empty($groupname)) {
                $this->error('分组名称不能为空');
            }
            $uodate['groupname'] = $groupname;
            $res = Db::table('member_group')->where(['id' => $id])->update($uodate);
            if ($res !== false) {
                $this->success('编辑成功', url('member/group'));
            }
            $this->error('编辑失败');
        }
        $info = Db::table('member_group')->where(['id' => $id])->find();
        $this->assign('info', $info);
        $this->assign('meta_title', '会员等级设置');
        return $this->fetch();
    }




    /**
     * 会员详细信息查看
     */
    public function detail()
    {

        $uid = I('get.id');
        $user = D('users')->where(array('user_id' => $uid))->find();
        if (!$user)
            exit($this->error('会员不存在'));
        if (IS_POST) {
            //  会员信息编辑
            $password = I('post.password');
            $password2 = I('post.password2');
            if ($password != '' && $password != $password2) {
                exit($this->error('两次输入密码不同'));
            }
            if ($password == '' && $password2 == '') {
                unset($_POST['password']);
            } else {
                $_POST['password'] = encrypt($_POST['password']);
            }

            if (!empty($_POST['email'])) {
                $email = trim($_POST['email']);
                $c = M('users')->where("user_id != $uid and email = '$email'")->count();
                $c && exit($this->error('邮箱不得和已有用户重复'));
            }

            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $c = M('users')->where("user_id != $uid and mobile = '$mobile'")->count();
                $c && exit($this->error('手机号不得和已有用户重复'));
            }
            if (!empty($_POST['level'])) {
                $userLevel = D('user_level')->where('level_id=' . $_POST['level'])->value('level');
                $_POST['agent_user'] = $userLevel;
            }

            $agent = M('agent_info')->where(['uid' => $uid])->find();

            if ($agent) {
                $data = array('level_id' => (int) $userLevel);
                M('agent_info')->where(['uid' => $uid])->save($data);
            } else {
                $this->agent_add($user['user_id'], $user['first_leader'], (int) $userLevel);
                $_POST['is_agent'] = 1;
            }

            $row = M('users')->where(array('user_id' => $uid))->save($_POST);
            if ($row) {
                exit($this->success('修改成功'));
            } else {
                exit($this->error('未作内容修改或修改失败'));
            }
        }

        $user['first_lower'] = M('users')->where("first_leader = {$user['user_id']}")->count();
        $user['second_lower'] = M('users')->where("second_leader = {$user['user_id']}")->count();
        $user['third_lower'] = M('users')->where("third_leader = {$user['user_id']}")->count();

        //一级是分销商数量
        $first_leader_distribut = M('users')->where("first_leader = {$user['user_id']}")->field('user_id')->select();
        $first_leader_distribut_num = 0;
        foreach ($first_leader_distribut as $key => $val) {
            $is_distribut = M('users')->where(["user_id" => $val['user_id']])->value('is_distribut');
            if ($is_distribut == 1) {
                $first_leader_distribut_num += 1;
            }
        }
        $this->assign('first_leader_distribut_num', $first_leader_distribut_num);

        $this->assign('user', $user);
        return $this->fetch();
    }



    /**
     * 用户收货地址查看
     */
    public function address()
    {
        $uid = I('get.id');
        $lists = D('user_address')->where(array('user_id' => $uid))->select();
        $regionList = get_region_list();
        $this->assign('regionList', $regionList);
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 删除会员
     */
    public function delete()
    {
        $uid = I('get.id');

        //先删除ouath_users表的关联数据
        M('OuathUsers')->where(array('user_id' => $uid))->delete();
        $row = M('users')->where(array('user_id' => $uid))->delete();
        if ($row) {
            $this->success('成功删除会员');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 删除会员
     */
    public function ajax_delete()
    {
        $uid = I('id');
        if ($uid) {
            $row = M('users')->where(array('user_id' => $uid))->delete();
            if ($row !== false) {
                //把关联的第三方账号删除
                M('OauthUsers')->where(array('user_id' => $uid))->delete();
                $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'data' => ''));
            } else {
                $this->ajaxReturn(array('status' => 0, 'msg' => '删除失败', 'data' => ''));
            }
        } else {
            $this->ajaxReturn(array('status' => 0, 'msg' => '参数错误', 'data' => ''));
        }
    }

    /**
     * 账户资金记录
     */
    public function account_log()
    {
        $user_id = I('get.id');
        //获取类型
        $type = I('get.type');
        //获取记录总数
        $count = M('account_log')->where(array('user_id' => $user_id))->count();
        $page = new Page($count);
        $lists = M('account_log')->where(array('user_id' => $user_id))->order('change_time desc')->limit($page->firstRow . ',' . $page->listRows)->select();

        $this->assign('user_id', $user_id);
        $this->assign('page', $page->show());
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 账户资金调节
     */
    public function account_edit()
    {
        $user_id = I('user_id');
        if (!$user_id > 0) $this->ajaxReturn(['status' => 0, 'msg' => "参数有误"]);
        $user = M('users')->field('user_id,user_money,frozen_money,pay_points,is_lock')->where('user_id', $user_id)->find();
        if (IS_POST) {
            $desc = I('post.desc');
            if (!$desc)
                $this->ajaxReturn(['status' => 0, 'msg' => "请填写操作说明"]);
            //加减用户资金
            $m_op_type = I('post.money_act_type');
            $user_money = I('post.user_money/f');
            $user_money = $m_op_type ? $user_money : 0 - $user_money;
            if (($user['user_money'] + $user_money) < 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => "用户剩余资金不足！！"]);
            }
            //加减用户积分
            $p_op_type = I('post.point_act_type');
            $pay_points = I('post.pay_points/d');
            $pay_points = $p_op_type ? $pay_points : 0 - $pay_points;
            if (($pay_points + $user['pay_points']) < 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '用户剩余积分不足！！']);
            }
            //加减冻结资金
            $f_op_type = I('post.frozen_act_type');
            $revision_frozen_money = I('post.frozen_money/f');
            if ($revision_frozen_money != 0) {    //有加减冻结资金的时候
                $frozen_money = $f_op_type ? $revision_frozen_money : 0 - $revision_frozen_money;
                $frozen_money = $user['frozen_money'] + $frozen_money;    //计算用户被冻结的资金
                if ($f_op_type == 1 && $revision_frozen_money > $user['user_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "用户剩余资金不足！！"]);
                }
                if ($f_op_type == 0 && $revision_frozen_money > $user['frozen_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => "冻结的资金不足！！"]);
                }
                $user_money = $f_op_type ? 0 - $revision_frozen_money : $revision_frozen_money;    //计算用户剩余资金
                M('users')->where('user_id', $user_id)->update(['frozen_money' => $frozen_money]);
            }
            if (accountLog($user_id, $user_money, $pay_points, $desc, 0)) {
                $this->ajaxReturn(['status' => 1, 'msg' => "操作成功", 'url' => U("Admin/User/account_log", array('id' => $user_id))]);
            } else {
                $this->ajaxReturn(['status' => -1, 'msg' => "操作失败"]);
            }
            exit;
        }
        $this->assign('user_id', $user_id);
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function recharge()
    {
        $timegap = urldecode(I('timegap'));
        $nickname = I('nickname');
        $map = array();
        if ($timegap) {
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $map['ctime'] = array('between', array(strtotime($begin), strtotime($end)));
            $this->assign('begin', $begin);
            $this->assign('end', $end);
        }
        if ($nickname) {
            $map['nickname'] = array('like', "%$nickname%");
            $this->assign('nickname', $nickname);
        }
        $count = M('recharge')->where($map)->count();
        $page = new Page($count);
        $lists = M('recharge')->where($map)->order('ctime desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('page', $page->show());
        $this->assign('pager', $page);
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 搜索用户名
     */
    public function search_user()
    {
        $search_key = trim(I('search_key'));
        if ($search_key == '') $this->ajaxReturn(['status' => -1, 'msg' => '请按要求输入！！']);
        $list = M('users')->where(['nickname' => ['like', "%$search_key%"]])->select();
        if ($list) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $list]);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '未查询到相应数据！！']);
    }

    /**
     * 分销树状关系
     */
    public function ajax_distribut_tree()
    {
        $list = M('users')->where("first_leader = 1")->select();
        return $this->fetch();
    }

    /**
     *
     * @time 2016/08/31
     * @author dyr
     * 发送站内信
     */
    public function sendMessage()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $users = M('users')->field('user_id,nickname')->where(array('user_id' => array('IN', $user_id_array)))->select();
        }
        $this->assign('users', $users);
        return $this->fetch();
    }

    /**
     * 发送系统通知消息
     * @author yhj
     * @time  2018/07/10
     */
    public function doSendMessage()
    {
        $call_back = I('call_back'); //回调方法
        $message_content = I('post.text', ''); //内容
        $message_title = I('post.title', ''); //标题
        $message_type = I('post.type', 0); //个体or全体
        $users = I('post.user/a'); //个体id
        $message_val = ['name' => ''];
        $send_data = array(
            'message_title' => $message_title,
            'message_content' => $message_content,
            'message_type' => $message_type,
            'users' => $users,
            'type' => 0, //0系统消息
            'message_val' => $message_val,
            'category' => 0,
            'mmt_code' => 'message_notice'
        );

        $messageFactory = new MessageFactory();
        $messageLogic = $messageFactory->makeModule($send_data);
        $messageLogic->sendMessage();

        echo "<script>parent.{$call_back}(1);</script>";
        exit();
    }

    /**
     *
     * @time 2016/09/03
     * @author dyr
     * 发送邮件
     */
    public function sendMail()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $user_where = array(
                'user_id' => array('IN', $user_id_array),
                'email' => array('neq', '')
            );
            $users = M('users')->field('user_id,nickname,email')->where($user_where)->select();
        }
        $this->assign('smtp', tpCache('smtp'));
        $this->assign('users', $users);
        return $this->fetch();
    }

    /**
     * 发送邮箱
     * @author dyr
     * @time  2016/09/03
     */
    public function doSendMail()
    {
        $call_back = I('call_back'); //回调方法
        $message = I('post.text'); //内容
        $title = I('post.title'); //标题
        $users = I('post.user/a');
        $email = I('post.email');
        if (!empty($users)) {
            $user_id_array = implode(',', $users);
            $users = M('users')->field('email')->where(array('user_id' => array('IN', $user_id_array)))->select();
            $to = array();
            foreach ($users as $user) {
                if (check_email($user['email'])) {
                    $to[] = $user['email'];
                }
            }
            $res = send_email($to, $title, $message);
            echo "<script>parent.{$call_back}({$res['status']});</script>";
            exit();
        }
        if ($email) {
            $res = send_email($email, $title, $message);
            echo "<script>parent.{$call_back}({$res['status']});</script>";
            exit();
        }
    }

    public function level()
    {
        $list = Db::name('member_level')->select();
        $this->assign('list', $list);
        $this->assign('meta_title', '代理等级');
        return $this->fetch();
    }


    public function level_edit()
    {

        $id   = input('id', 0);

        $info = Db::table('member_level')->where('id', $id)->find();

        if (request()->isPost()) {

            $recommend      = input('recommend/f', 0);
            $pingji_level       = input('pingji_level/f', 0);
            $layer_number       = input('layer_number/f', 0);
            $upgrade       = input('upgrade', '');
            $levelname  = input('levelname', '');
            $desc  = input('desc', '');

            if (empty($levelname)) {
                $this->error('等级名称不能为空！');
            }

            if (empty($levelname)) {
                $this->error('等级名称不能为空！');
            }
            $data['recommend']  = $recommend / 100;
            $data['pingji_level']   = $pingji_level / 100;
            $data['layer_number']   = $layer_number;
            $data['upgrade']   = $upgrade;
            $data['levelname']  = $levelname;
            $data['desc']  = $desc;
            $res = Db::table('member_level')->where(['id' => $id])->update($data);

            if ($res !== false) {
                $this->success('操作成功', url('member/level'));
            } else {
                $this->error('失败！');
            }
        };


        return $this->fetch('', [
            'meta_title'    =>  '编辑等级',
            'info'          =>  $info,
        ]);
    }
}
