<?php

namespace app\common\model;

use think\helper\Time;
use think\Model;
use think\Db;

class Upgrade extends Model
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
        $users_id = first_leader_ids($data['user_id']);
        $user = Db::name('member')->where('id', 'in', $users_id)->field('id,level,first_leader,if_buy_fifty')->select();

        foreach ($user as $key => $vale) {
            $this->upgrade_where($vale['id'], $vale['level']);
        }
    }

    private function upgrade_where($user_id, $level)
    {
        $users = get_all_lower($user_id);
        $users = Db::name('member')->where('id', 'in', $users)->field('id,level,first_leader,if_buy_fifty')->select();
        $proportion = $this->proportion(); //获取比例
        $T_nums = 0; //用户T空间数
        $copper_nums = 0; //铜牌个数
        $silver_nums = 0; //银牌个数
        $level_new = 0; //升级等级
        foreach ($users as $key => $vale) {
            $T_nums = $this->miner_list($vale['id']);
            if ($vale['level'] == 1) {
                $copper_nums += 1;
            }
            if ($vale['level'] == 2) {
                $silver_nums += 1;
            }
        }
        if ($level == 0) {
            //用户总T空间数
            $T_nums = $this->miner_list($user_id);
            $level_0 = $level + 1;
            if ($T_nums >= $proportion['upgrade_' . $level][$level_0]) {
                $level_new = $level + 1;
            }
        } else if ($level == 1) {
            //获取用户下级铜牌个数
            $level_1 = $level + 1;
            if ($copper_nums >= $proportion['upgrade_' . $level][$level_1]) {
                $level_new = $level + 1;
            } else {
                return false;
            }
        } else if ($level == 2) {
            //获取用户下级银牌个数
            $level_2 = $level + 1;
            if ($silver_nums >= $proportion['upgrade_' . $level][$level_2]) {
                $level_new = $level + 1;
            } else {
                return false;
            }
        } else {
            return false;
        }
        $data = ['level' => $level_new];
        $r = DB::name('member')->where(['id' => $user_id])->update($data);
        return $r;
    }

    /**
     * Undocumented function
     * 获取用户T空间总数
     * @param [type] $user_id
     * @return void
     */
    private function miner_list($user_id)
    {
        $miner_list = Db::name('miner_list')->where(['user_id' => $user_id])->sum('T_num');
        if ($miner_list) {
            return $miner_list;
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
        $distribution = Db::name('member_level')->select();
        $Direct_push = [];
        $Push_between = [];
        foreach ($distribution as $key => $veal) {
            if ($veal['type'] == 0) {
                $Direct_push[$veal['level']] = $distribution[$key]['upgrade'];
            }
            if ($veal['type'] == 1) {
                $Push_between[$veal['level']] = $distribution[$key]['upgrade'];
            }
        }
        $proportion = ['upgrade_0' => $Direct_push, 'upgrade_1' => $Push_between];
        return $proportion;
    }
}
