<?php
namespace app\admin\controller;

use think\Db;
use think\Loader;
use think\Request;

/*
 * 分销管理
 */
class Distribution extends Common
{


    /*
     * 分销等级列表
     */
    public function distribution_grade(){


        $list = Db::table('distribution')->order('id asc')->where('type',0)->select();
      
        
        return $this->fetch('',[
            'meta_title'    =>  '分销等级列表',
            'list'          =>  $list,
        ]);
    }

    /*
     * 分销商品等级列表
     */
    public function distribution_grade_goods(){


        $list = Db::table('distribution')->order('id asc')->where('type',1)->select();
      
        
        return $this->fetch('',[
            'meta_title'    =>  '分销商品等级列表',
            'list'          =>  $list,
        ]);
    }

    /*
     * 添加新等级
     */
    public function distribution_grade_add(){

        $id   = input('id',0);

        $info = Db::table('distribution')->where('id',$id)->find();
        
        if( request()->isPost() ){

            $start_num  = input('start_num/d',0);
            $start_end  = input('start_end/d',0);
            $levelratio = input('levelratio/d',0);
            $levelname  = input('levelname','');
            if($start_num <= 0 || $start_end <= 0){
                $this->error('分销条件须大于0！');
            }
            if(empty($levelname)){
                $this->error('等级名称不能为空！');
            }
            $data['start_num']  = $start_num;
            $data['start_end']  = $start_end;
            $data['levelratio'] = $levelratio/100;
            $data['levelname']  = $levelname;
            
            if($id){
                //添加操作日志
                // slog($id,'edit');
                $data['update_time']  = time();
                $res = Db::table('distribution')->where(['id' => $id])->update($data);
            }else{
                $data['create_time']  = time();
                $res = Db::table('distribution')->insertGetId($data);
                //添加操作日志
                // slog($res);
            }

            if($res !== false){
                if($info['type']){
                    $this->success('操作成功',url('distribution/distribution_grade_goods'));
                }
                $this->success('操作成功',url('distribution/distribution_grade'));
            }else{
                $this->error('失败！');
            }

        };
        
        
        return $this->fetch('',[
            'meta_title'    =>  $id?'编辑等级':'添加新等级',
            'info'          =>  $info,
        ]);
    }

    /*
     * 删除等级
     */
    public function distribution_grade_del(){
        $id = input('id');

        if(!$id){
            jason([],'参数错误',0);
        }

        $info = Db::table('distribution')->find($id);
        if(!$info){
            jason([],'参数错误',0);
        }

        if( Db::table('distribution')->where('id',$id)->delete() ){
            //添加操作日志
            slog($id);
            jason([],'删除成功！');
        }
    }




}
