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

class Miners extends ApiBase
{

    /**
     * 存储服务器群组
     * Undocumented function
     * $user_id 用户id
     * $miner_info 矿机详情
     * $user_info['remainder_money'] 用户余额
     * $miner_nums 矿机总T数
     * @return void
     */
    public function index()
    {
        $this->user_id = 27760;
        $user_id = $this->user_id;
        $miner_info = Db::name('miner_list')->where(['user_id' => $user_id])->select();
        $user_info = Db::name('member')->field('realname,mobile,remainder_money')->where(['id' => $user_id])->find();
        $currency = Db::name('currency')->field('currency_name,currency_address,currency_address_url')->where(['id' => 1])->find();
        $miner_nums = Db::name('miner_list')->where(['user_id' => $user_id])->sum('T_num');
        $data = ['miner_info' => $miner_info, 'miner_nums' => $miner_nums, 'user_money' => $user_info['remainder_money'], 'currency' => $currency];
        // $user_info = doPhone($user_info);
        // dump($user_info);
        $this->successResult($data);
    }
    /**
     * 明细记录
     * Undocumented function
     *
     * @return void
     */
    public function entrust_log()
    {
        $this->user_id = 27760;
        $user_id = $this->user_id;
        $source_type = trim(input('source_type'));
        // $source_type = 9;
        if (!$source_type) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数错误！']);
        }
        $where = ['source_type' => $source_type, 'user_id' => $user_id];
        // if ($source_type == 9) {
        //     $where = ['source_type' => $source_type, 'user_id' => $user_id];
        // }
        // if ($source_type == 11) {
        //     $where = ['source_type' => $source_type, 'user_id' => $user_id];
        // }
        if ($source_type == 'all') { //全部
            $where = ['source_type' => ['in', '1,2,3,4,5,11'], 'user_id' => $user_id];
        }
        if ($source_type == 'all_share') { //分享
            $where = ['source_type' => ['in', '1,2'], 'user_id' => $user_id];
        }
        if ($source_type == 'all_node') { //节点
            $where = ['source_type' => ['in', '1,2'], 'user_id' => $user_id];
        }
        if ($source_type == 'all_Infinite') { //无限
            $where = ['source_type' => ['in', '5'], 'user_id' => $user_id];
        }
        // if ($source_type == 'carry') { //提币
        //     $where = ['source_type' => ['in', '7'], 'user_id' => $user_id];
        // }
        // if ($source_type == 'release') { //释放
        //     $where = ['source_type' => ['in', '11'], 'user_id' => $user_id];
        // }
        if ($source_type == 'day_release') { //每日释放

            $where = ['source_type' => ['in', '11'], 'user_id' => $user_id];
            $menber_balance_log = Db::name('menber_balance_log')->where($where)->whereTime('create_time', 'today')->paginate(20, false);

            $this->successResult($menber_balance_log);
        }
        $menber_balance_log = Db::name('menber_balance_log')->where($where)->paginate(20, false);
        //->paginate(10,false,$pageParam);
        $this->successResult($menber_balance_log);
    }
    /**
     * 提币详情
     * Undocumented function
     *
     * @return void
     */
    public function carry_list()
    {
        $this->user_id = 27760;
        $user_id = $this->user_id;
        $carry_id = trim(input('carry_id'));

        if (!$carry_id) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数错误！']);
        }

        $where = ['user_id' => $user_id, 'id' => $carry_id];
        $carry_list = Db::name('menber_balance_log')->where($where)->find();

        $this->successResult($carry_list);
    }
    /**
     * 订单列表
     * Undocumented function
     *
     * @return void
     */
    public function order_list_log()
    {

        $this->user_id = 27712;
        $user_id = $this->user_id;
        $order_status = trim(input('order_status'));

        if (!$order_status) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数错误！']);
        }
        $where = ['user_id' => $user_id, 'order_status' => $order_status];
        if ($order_status = 'all') {
            $where = ['user_id' => $user_id, 'order_status' => ['in', '1,2,3,4,5']];
        }
        $carry_list = Db::name('order')->where($where)->paginate(20, false);
        $this->successResult($carry_list);
    }

    /**
     * 订单详情
     * Undocumented function
     *
     * @return void
     */
    public function order_list()
    {

        $this->user_id = 27712;
        $user_id = $this->user_id;
        $order_id = trim(input('order_id'));

        if (!$order_id) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数错误！']);
        }
        $where = ['user_id' => $user_id, 'order_id' => $order_id];
        $order_list = Db::name('order')->where($where)->find();
        $this->successResult($order_list);
    }
    /**
     * 代理明细
     * Undocumented function
     *
     * @return void
     */
    public function agent_users()
    {
        $this->user_id = 27875;
        $user_id = $this->user_id;
        $user = Db::name('member')->where(['id' => $user_id])->field('id,level,first_leader')->find();

        $user_first = Db::name('member')->where(['first_leader' => $user['id']])->field('id,level,realname,first_leader')->select();

        $users_id = subordinate_ids($user_id);
        $level_count_1 = Db::name('member')->where('id', 'in', $users_id)->where(['level' => 1])->field('id,level,realname')->count();
        $level_count_2 = Db::name('member')->where('id', 'in', $users_id)->where(['level' => 2])->field('id,level,realname')->count();
        $level_count_3 = Db::name('member')->where('id', 'in', $users_id)->where(['level' => 3])->field('id,level,realname')->count();
        $level_count_4 = Db::name('member')->where('id', 'in', $users_id)->where(['level' => 4])->field('id,level,realname')->count();
        // $user_first = $user_id;
        $data = ['level_count_1' => $level_count_1, 'level_count_2' => $level_count_2, 'level_count_3' => $level_count_3, 'level_count_4' => $level_count_4, 'user_first' => $user_first];
        $this->successResult($data);
    }
    /**
     * 代理直推下级明细
     * Undocumented function
     *
     * @return void
     */
    public function agent_users_list()
    {
        $id = trim(input('id'));
        if (!$id) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数错误！']);
        }
        $user_first = Db::name('member')->where(['first_leader' => $id])->field('id,level,realname,first_leader')->select();
        $this->successResult($user_first);
    }
    /**
     * 等级人数详情列表
     * Undocumented function
     *
     * @return void
     */
    public function agent_nums_list()
    {
        $this->user_id = 27875;
        $user_id = $this->user_id;
        $level = trim(input('level'));
        if (!$level) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数错误！']);
        }
        $users_id = subordinate_ids($user_id);
        $user_first = Db::name('member')->where('id', 'in', $users_id)->where(['level' => $level])->field('id,level,realname,first_leader')->select();
        $this->successResult($user_first);
    }
}
