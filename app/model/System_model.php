<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 用户模型
 */
class System_model
{
    /**
     * @param $area_code:验证国际区号
     */
   public function check_area_code($area_code)
   {
       $sql = "select `status` from cat_country_".DATABASESUFFIX." where area_code = $area_code and status = 1";
       $has_area_code = $this->db->query($sql)->row_array();
       if(!$has_area_code)ajax_return(ERROR,'无此国际区号');
   }

    /**
     * @param $usercode:string:用户免登陆标识;
     */
   public function check_usercode($usercode,$type=1)
   {
       $sql = "select `usercode`,`status`,`is_super_admin`,`id` from admin_user_".DATABASESUFFIX." where usercode = '{$usercode}'";
       $admin_user = $this->db->query($sql)->row_array();
       if(!$admin_user || $admin_user['status']==0){
           ajax_return(ERROR,'账号不存在或已被禁用');
       }
       $res = $this->admin_user_menus($admin_user['id'],$admin_user['is_super_admin'],$type);
       return $res;
   }

    /**
     * @param $token 管理员id || 管理员usercode
     * @param string $type 1:usercode 2:admin_id
     */
//   public function admin_user_nav_list($token,$type='',$is_super_admin)
//   {
//        if(empty($type))ajax_return(ERROR,'参数错误');
//        if($type==1){
//            $sql = "select `id`,`is_super_admin` from admin_user_".DATABASESUFFIX." where `usercode` = '$token'";
//            $admin_user_info = $this->db->query($sql)->row_array($sql);
//            if($admin_user_info){
//                $menus = $this->admin_user_menus($admin_user_info['id'],$admin_user_info['is_super_admin']);
//            }else{
//                return false;
//            }
//
//        }else{
//            $menus = $this->admin_user_menus($token,$is_super_admin);
//        }
//        return $menus;
//   }

    /**
     * @param $admin_id:管理员id;
     * $is_super_admin:1:超级管理员 查看所有的权限(power) 0:所属角色的权限;
     */
   public function admin_user_menus($admin_id,$is_super_admin,$type)
   {
       if($is_super_admin==1){
           $sql = "SELECT
	                 c.`vue_router`,c.`status`,c.pid,c.power_id as id,c.controller,c.method,c.power_name,CONCAT_WS('/',c.controller,c.method) as path_name
                    FROM
	                  admin_power_".DATABASESUFFIX." as c";
       }else{
           $sql = "SELECT
	                c.`vue_router`,c.`status`,c.pid,c.power_id as id,a.admin_id,b.role_id,b.power_id,c.controller,c.method,c.pid,c.power_name,CONCAT_WS('/',c.controller,c.method) as path_name
                  FROM
	                admin_user_role_".DATABASESUFFIX." AS a
                  INNER JOIN admin_role_power_".DATABASESUFFIX." AS b ON a.role_id = b.role_id
                  INNER JOIN admin_power_".DATABASESUFFIX." AS c ON b.power_id = c.power_id WHERE a.admin_id=".$admin_id;
       }
       $menus = $this->db->query($sql)->result_array();
//       ajax_return($menus);
       if($menus){
           if($type==2){
                return $menus;
           }
           // ajax_return($this->admin_nav_level($menus));
           return $this->admin_nav_level($menus);
       }
       return false;
   }


    /**
     * @param $array
     * @param int $pid 父类id
     * @param int $level 层级
     * @return array
     */
//    protected function admin_nav_level($array,$pid=0,$level=0)
//    {
//        $arr = array();
//        foreach ($array as $v) {
//            if($v['pid'] == $pid){
//                $v['level'] = $level;
//                $arr[] = $v;
//                $arr = array_merge($arr,$this->admin_nav_level($array,$v['id'],$level+1));
//            }
//        }
//        return $arr;
//    }

    public function admin_nav_level($data, $pid=0)
    {
        $tree = array();
        $num = 0;
        foreach($data as $k => $v){
            if($v['pid'] == $pid && $v['status']==1){
                if($v['pid']==0){
                    $v['index'] = ++$num;
                }
                $v['sons'] = $this->admin_nav_level($data, $v['id']);
                $tree[] = $v;
                //unset($data[$k]);
            }
        }
//        ajax_return($tree);
        return $tree;
    }

    public function jiaoben()
    {
        $file = file_get_contents(__DIR__ . "/Blog.php");
//        preg_match_all('/public\sfunction\s+(.*)\(.*\)/i', $file, $arr);
        preg_match_all('/public\sfunction\s+(.*)\(.*\)/im', $file, $arr);
        var_dump($arr['1']);
        die;
        foreach ($arr as $method) {

        }
    }


}
