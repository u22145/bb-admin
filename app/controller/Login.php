<?php

/**
 * 管理员登录
 */
class Login extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load_model("system_model");
    }

    /**
     * 管理员登陆;手机登陆必须带上国际区号;
     * $username:string :账号
     * $mobile:int:手机号
     * $area_code:国际区号
     */
    public function login()
    {
        $username = sql_format(@$_POST['username']);
        $password = trim(@$_POST['password']);
        if (!$username || !$password) {
            ajax_return(ERROR, get_tips(1006));
        }
        $appid = DATABASESUFFIX;

        //查询字段
        $this->init_db();
        $sql = "select `id`, `uid`, `username`, `password`, usercode, `status`, `avatar` from admin_user_$appid where `username` = '$username'";
        $res = $this->db->query($sql)->row_array();
        if (!$res) {
            ajax_return(ERROR, get_tips(5001));
        }
        if (!password_verify($password, $res['password'])) {
            ajax_return(ERROR, get_tips(5002));
        }
        if ($res['status'] == 0) {
            ajax_return(ERROR, get_tips(5003));
        }
        $admin_id = $res['id'];
        $usercode = $res['usercode'];

        // 更新登录信息
        if (!$usercode) {
            $usercode = rand_code(20, 'all');
        }
        $ip = get_client_ip();
        $sql = "update admin_user_$appid set usercode = '$usercode', login_time = current_timestamp, login_ip = $ip where id = $admin_id";
        $this->db->query($sql);

        // 登录记录
        $sql = "insert into admin_login_$appid (admin_id, ip) values ($admin_id, $ip)";
        $this->db->query($sql);

        //返回数据
        $return = [
            'usercode' => $usercode,
            'avatar' => get_pic_url($res['avatar'], 'avatar'),
            'username' => $res['username'],
            'admin_id' => $admin_id
        ];

        $uid = $res['uid'];
        $user = $this->redis->hMGet(sprintf(RedisKey::USER, $uid), ['usercode', 'nickname', 'avatar']);
        if (!empty($user)) {
            $return['user'] = [
                'id' => (string)$uid,
                'usercode' => (string)$user['usercode'],
                'nickname' => $user['nickname'],
                'avatar' => get_pic_url($user['avatar'], 'avatar')
            ];
        }

        ajax_return(SUCCESS, get_tips(5004), $return);
    }

    /**
     * 获取显示目录
     */
    public function getmenu()
    {
        $usercode = sql_format(@$_POST['usercode']);
        $appid = DATABASESUFFIX;
        if (!$usercode) {
            ajax_return(ERROR, get_tips(5005));
        }

        // 查询用户信息
        $sql = "select status, is_super_admin, id from admin_user_$appid where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();
        if (!$userinfo) {
            ajax_return(ERROR, get_tips(5005));
        }
        if ($userinfo['status'] != 1) {
            ajax_return(ERROR, get_tips(5006));
        }
        $lang = $this->redis->get('lang') ?: 'zh';
        //查询所有nav
        $sql = "select power_id, power_name, vue_router from admin_power_$appid where power_id like '%0' or power_id in (761, 762) order by power_id";
        $res = $this->db->query($sql)->result_array();
        //处理语言
        foreach ($res as &$navs) {
            $power_name = get_tips($navs['power_id'], 'menu');
            $navs['power_name'] = $power_name != 'Unknown Error' ? $power_name : $navs['power_name'];
        }
        //导航栏
        $nav = [];
        $i = -1;
        //外层节点
        foreach ($res as $key => $val) {
            if (in_array(intval($val['power_id']), [1700, 1710, 1720, 1730])) {
                continue;
            }
            if (substr($val['power_id'], -2) == '00') {
                $nav[++$i] = $val;
            } elseif (substr($val['power_id'], -1) == '0') {
                // 管理员不显示我的任务模块
                if ($userinfo['is_super_admin'] && $val['power_id'] == "1010") continue;

                $nav[$i]['son'][] = $val;
            }

            if (in_array(intval($val['power_id']), [761, 762])) {
                $nav[$i]['son'][] = $val;
            }
        }
        $nav = array_values($nav);

        // 查询非管理用户被分配的角色
        if (!$userinfo['is_super_admin']) {
            $sql = "select c.power_id, c.power_name, c.vue_router from admin_user_role_$appid r inner join admin_role_power_$appid p on r.role_id = p.role_id inner join admin_power_$appid c on p.power_id = c.power_id where r.admin_id = {$userinfo['id']}";
            $res = $this->db->query($sql)->result_array();
            $arr_power = array_column($res, 'power_id');
            foreach ($arr_power as $val) {
                $arr_power[] = intval($val / 10) * 10;
                $arr_power[] = intval($val / 100) * 100;
            }

            if (in_array(760, $arr_power)) {
                $arr_power[] = 761;
                $arr_power[] = 762;
            }
            array_unique($arr_power);
            foreach ($nav as $key => $val) {
                if (in_array($val['power_id'], $arr_power)) {
                    if ($val['son'] && $val['son'] != null) {
                        foreach ($val['son'] as $k => $v) {
                            if (!in_array($v['power_id'], $arr_power)) {
                                unset($nav[$key]['son'][$k]);
                            }
                        }
                        $nav[$key]['son'] = array_values($nav[$key]['son']);
                    }

                } else {
                    unset($nav[$key]);
                }
            }
            $nav = array_values($nav);
        }
        ajax_return(SUCCESS, get_tips(1005), $nav);
    }

    /**
     * 修改密码
     */
    public function edit_password()
    {
        $old_password = htmlspecialchars(trim($_POST['old_password'] ?? ''));
        $new_password = htmlspecialchars(trim($_POST['new_password'] ?? ''));
        $new_confirm_password = htmlspecialchars(trim($_POST['new_confirm_password'] ?? ''));

        $sql = "select  password from admin_user_" . DATABASESUFFIX . " where `usercode` = '{$_POST['usercode']}'";
        $check_admin_info = $this->db->query($sql)->row_array();
        if (!(password_verify($old_password, $check_admin_info['password']))) {
            ajax_return(ERROR, get_tips(5007));
        }
        if ($old_password == $new_password) {
            ajax_return(ERROR, get_tips(5008));
        }
        if ($new_password != $new_confirm_password || empty($new_password)) {
            ajax_return(ERROR, get_tips(5009));
        }
        $new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin_user_" . DATABASESUFFIX . " SET `password`='$new_password' WHERE `usercode` = '{$_POST['usercode']}'";
        $this->db->query($sql)->affected_rows();

        ajax_return(SUCCESS, get_tips(5010));
    }
}
