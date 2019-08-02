<?php

namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Range_same extends Model
{
    public function index($data)
    {
        // $data = [
        //     'user_id' => 27759, //用户id
        //     'order_id' => 1,
        //     'Profit_id' => 2, //收益id
        //     'money' => 200,
        // ];
        if (!$data) {
            return false;
        }
        $this->bouns($data);
    }

    public function bouns($data)
    {
        $users_id = first_leader_ids($data['user_id']);
        $user_data = $this->users($data['user_id']);
        $user = Db::name('member')->where('id', 'in', $users_id)->field('id,level,first_leader,if_buy_fifty')->select();

        // $logName  = '级差奖';
        //获取分红比例
        $rateArr  = $this->proportion();
        $useRate = 0;

        $userLevel = 0;
        $l = 0;
        foreach ($user as $key => $user_veal) {
            if ($l != $user_veal['level']) {
                $level_nums_1_2 = 0;
                $level_nums_3 = 0;
            }
            $logName  = '级差奖';
            $sourceType = 3;
            if (!$user_veal['level']) continue;
            $grade  = $user_veal['level'];
            if ($grade < $userLevel) continue;
            $jsRate = $rateArr[$grade]['recommend'] - $useRate;
            if ($jsRate < 0) continue;
            $money = $data['money'] * $jsRate;

            if ($jsRate == '0') {
                if ($user_veal['level'] == 1 || $user_veal['level'] == 2) {
                    $level_nums_1_2 += 1;
                }
                if ($user_veal['level'] == 3) {
                    $level_nums_3 += 1;
                }
                $jsRate  = $rateArr[$grade]['pingji_level'];
                $logName = '平级奖';
                $sourceType = 4;
                $money = $data['money'] * $useRate * $jsRate;
                //平级脱离
                $l = $user_veal['level'];
                if ($level_nums_1_2 > 1) {
                    continue;
                }
                if ($level_nums_3 > 3) {
                    continue;
                }
            }

            $useRate = $rateArr[$grade]['recommend'];
            $userLevel = $grade;
            accountLog($user_veal['id'], $sourceType, $money, $logName, $money, $data['order_id']);
            dump($money . '-------' . $level_nums_3 . '-------' . $logName . '-------' . $jsRate . '-------' . $user_veal['level'] . '-------' . $user_veal['id']);
        }
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

    /**
     * Undocumented function
     * 获取升级条件
     * $Direct_push 直推
     * $Push_between 间推
     * @return void
     */
    private function proportion()
    {
        $distribution = Db::name('member_level')->field('level,recommend,layer_number,pingji_level')->select();
        if ($distribution) {
            $miner_list = [];
            foreach ($distribution as $key => $veal) {
                if ($veal['level'] >= 4) continue;
                $miner_list[$veal['level']] = $veal;
            }
            return $miner_list;
        }
    }
}
