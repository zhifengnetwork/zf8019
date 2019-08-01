<?php

namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Recommend extends Model
{
    public function index($data)
    {
        if (!$data) {
            return false;
        }
        $this->bouns($data);
    }

    public function bouns($data)
    {
        $users = $this->users($data['user_id']);
        $first_leader = $users['first_leader']; //第一个上级
        $second_leader = $users['second_leader']; //第二个上级
        $proportion = $this->proportion(); //获取比例

        //事务处理
        Db::startTrans();
        //直推
        $first_money = $data['money'] * $proportion['Direct_push'];
        $first_money = bcadd($first_money, '0.00', 8);
        $first_note = "用户ID为:" . $data['user_id'] . "的直推上级【ID:" . $first_leader . "】获得收益" . $first_money;
        accountLog($first_leader, 1, $first_money, $first_note, $first_money, $data['order_id']);
        //间推
        $second_money = $first_money * $proportion['Push_between'];
        $second_money = bcadd($second_money, '0.00', 8);
        $second_note = "用户ID为:" . $first_leader . "的间推上级【ID:" . $second_leader . "】获得收益" . $second_money;
        accountLog($second_leader, 2, $second_money, $second_note, $second_money, $data['order_id']);
        Db::commit();
    }



    /**
     * Undocumented function
     *获取直推间推比例
     *$Direct_push 直推
     *$Push_between 间推
     * @return void
     */
    private function proportion()
    {
        $distribution = Db::name('distribution')->select();
        $Direct_push = 0;
        $Push_between = 0;
        foreach ($distribution as $key => $veal) {
            if ($veal['type'] == 0) {
                $Direct_push = $distribution[$key]['levelratio'];
            }
            if ($veal['type'] == 1) {
                $Push_between = $distribution[$key]['levelratio'];
            }
        }
        $proportion = ['Direct_push' => $Direct_push, 'Push_between' => $Push_between];
        return $proportion;
    }

    /**
     * 获取用户信息
     * @param [type] $user_id
     * @return void
     */
    private function users($id)
    {
        $users = Db::name('member')->where(['id' => $id])->find();
        if ($users) {
            return $users;
        }
    }
}
