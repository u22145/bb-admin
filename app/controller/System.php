<?php

/**
 * Created by PhpStorm
 * User: Kobe
 * Date: 2019/8/6
 * Time: 10:59
 */
class System extends Controller
{
    /**
     * @var System_model
     */
    protected $system_model;

    public function __construct()
    {
        parent::__construct();
        $this->load_model("system_model");
        $this->uptime = date('Y-m-d H:i:s');
    }

    /**
     * 添加角色；
     * @author :Kobe
     */
    public function add_role()
    {
        /**
         * $role_name：权限名称，$role_desc：权限描述
         */
        $role_name = htmlspecialchars(trim($_POST['role_name'] ?? ''));
        $desc = htmlspecialchars(trim($_POST['desc'] ?? ''));
        if (empty($role_name) || empty($desc)) {
            ajax_return(ERROR, get_tips(1006));
        }
        $this->check_role_name($role_name);
        $sql = "INSERT INTO admin_role_" . DATABASESUFFIX . " (`role_name`,`desc`,`uptime`) VALUES ('$role_name','$desc','$this->uptime')";
        $insert_id = $this->db->query($sql)->insert_id();
        if ($insert_id) {
            ajax_return(SUCCESS, get_tips(8001));
        }
        ajax_return(ERROR, get_tips(8002));
    }

    /**
     *修改角色
     * @author:Kobe
     */
    public function edit_role()
    {
        $role_name = htmlspecialchars(trim($_POST['role_name'] ?? ''));
        $desc = htmlspecialchars(trim($_POST['desc'] ?? ''));
        $role_id = htmlspecialchars(trim($_POST['role_id'] ?? 0));
        if (empty($role_name) || empty($desc) || intval($role_id) == 0) {
            ajax_return(ERROR, get_tips(1006));
        }
        /**
         *验证觉得名称是否存在
         */
        $this->check_role_name($role_name, $role_id);
        $sql = "UPDATE admin_role_" . DATABASESUFFIX . " SET `role_name` = '$role_name',`desc` = '$desc' WHERE role_id={$role_id}";
        $edit_ok = $this->db->query($sql);
        if ($edit_ok) {
            ajax_return(SUCCESS, get_tips(1005));
        }
        ajax_return(ERROR, get_tips(1004));
    }

    /**
     *展示单个角色
     * @author :Kobe
     */
    public function show_role()
    {
        $role_id = (int)$_POST['role_id'] ?: 0;
        if (!$role_id) {
            ajax_return(ERROR, get_tips(1006));
        }

        $this->init_db();
        $role_info_sql = "select * from admin_role_" . DATABASESUFFIX . " where role_id = {$role_id}";
        $role_info = $this->db->query($role_info_sql)->row_array();
        // 获取权限等级
        // $role_level_sql = "select power.power_id as id, power.power_name from admin_role_power_" . DATABASESUFFIX . " as role ,admin_power_" . DATABASESUFFIX . " as power where role.power_id = power.power_id and role.role_id = {$role_id} order by id asc";
        $role_level_sql = "SELECT power.power_id as id, power.power_name from admin_power_" . DATABASESUFFIX . " as power LEFT JOIN admin_role_power_" . DATABASESUFFIX . " as role ON role.power_id = power.power_id WHERE role.role_id = {$role_id} order by id asc";
        $role_level = $this->db->query($role_level_sql)->result_array();

        $nav = [];
        $i = -1;
        $j = -1;
        foreach ($role_level as $val) {
            if (substr($val['id'], -2) == '00') {
                $nav[++$i] = $val;
                $j = -1;
            } else if (substr($val['id'], -1) == '0') {
                $nav[$i]['children'][++$j] = $val;
            } else {
                $nav[$i]['children'][$j]['children'][] = $val;
            }
        }

        ajax_return(SUCCESS, '', array(
            'role_info' => $role_info,
            'role_level' => $nav
        ));
    }

    /**
     * 角色列表
     * @author :Kobe
     */
    public function role_list()
    {
        $role_name = sql_format($_POST['role_name'] ?? '');
        $starttime = sql_format($_POST['starttime'] ?? '');
        $endtime = sql_format($_POST['endtime'] ?? '');
        $app_id = DATABASESUFFIX;

        $where = " where 1 = 1";
        if ($role_name) {
            $where .= " and role_name = '$role_name'";
        }
        if ($starttime && $endtime) {
            $where .= " and uptime between '$starttime' and '$endtime'";
        }

        $sql = "select role_id,role_name,`desc`,uptime from admin_role_$app_id $where";
        $role = $this->db->query($sql)->result_array();

        ajax_return(SUCCESS, '', $role);
    }

    /**
     * 验证角色名称是否存在 修改和新增
     * @author :Kobe
     * $role_name:角色名称；
     * $role_id  :角色id; 更新时候使用
     */
    protected function check_role_name($role_name, $role_id = '')
    {
        $sql = "select role_id from admin_role_" . DATABASESUFFIX . " where role_name = '{$role_name}'";
        $has_role = $this->db->query($sql)->row_array();
        if (empty($role_id)) {
            if ($has_role) ajax_return(ERROR, get_tips(8003));
        } else {
            if ($has_role && $has_role['role_id'] <> $role_id) ajax_return(ERROR, get_tips(8003));
        }
    }

    /**
     * 删除角色:当没有没有用户选择该角色的情况下可以删除
     */
    public function delete_role()
    {
        $role_id = intval($_POST['role_id']);
        if (!$role_id) ajax_return(ERROR, get_tips(1006));
        /**
         *查询:这个角色是否被用户已经使用过;
         */
        $sql = "select count(*) t from admin_user_role_" . DATABASESUFFIX . " where role_id = $role_id";
        $has_admin_user_role = $this->db->query($sql)->row_array();
        if ($has_admin_user_role['t']) ajax_return(ERROR, get_tips(8004));

        /**
         * 删除 当前角色;
         */
        $sql = "DELETE FROM admin_role_" . DATABASESUFFIX . " WHERE role_id=$role_id";
        $this->db->query($sql)->affected_rows();

        ajax_return(SUCCESS, get_tips(8005));
    }

    /**
     *修改管理员信息:
     * @author :Kobe
     */
    public function edit_admin_user()
    {
        $admin_id = htmlspecialchars($_POST['admin_id']) ?? 0;
        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $password = htmlspecialchars(trim($_POST['password'] ?? ''));
        $mobile = htmlspecialchars(trim($_POST['mobile'] ?? ''));
        $area_code = htmlspecialchars(trim($_POST['area_code'] ?? ''));
        $is_super_admin = htmlspecialchars(trim($_POST['is_super_admin'] ?? 0));
        $status = htmlspecialchars(trim($_POST['status'] ?? 1));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        if (intval($admin_id) == 0) ajax_return(ERROR, get_tips(1006));
        if (!check_user_data('mobile', $mobile)) ajax_return(ERROR, get_tips(8006));
        if (empty($area_code)) ajax_return(ERROR, get_tips(8007));
        if (empty($description)) ajax_return(ERROR, get_tips(8008));
        if (empty($username)) ajax_return(ERROR, get_tips(8009));
        //if(!empty($password) && !check_user_data('password', $password))ajax_return(ERROR, get_tips(8016));

        $this->check_admin_user_name($username, $mobile, $area_code, $admin_id);
        /**
         *
         * 上传头像 查看原来头像在不在 在的话删除图片文件；
         */

        $fields = "`id`,`password`,`avatar`";
        $has_admin_user_avatar = $this->look_admin_user_info($admin_id, $fields);
        if (empty(trim($password))) {
            $password = $has_admin_user_avatar['password'];
        } else {
            if (!check_user_data('password', $password)) ajax_return(ERROR, get_tips(8010));
        }
        $password = password_hash($password, PASSWORD_DEFAULT);
        $avatar = !empty($has_admin_user_avatar['avatar']) ? $has_admin_user_avatar['avatar'] : '';
        if (!empty($_FILES['file'])) {
            $avatar = $this->upload_img($_FILES['file']);
            /**
             * 如果原头像存在 就删除掉原来的头像
             */
            if (!empty($avatar)) {
                if ($has_admin_user_avatar['avatar'] && file_exists(get_pic_url($has_admin_user_avatar['avatar'], 'avatar'))) {
                    unlink(get_pic_url($has_admin_user_avatar['avatar'], 'avatar'));
                }
            }
        }
        $sql = "UPDATE admin_user_" . DATABASESUFFIX . " SET `username` = '$username',`password` = '$password',`mobile` = '$mobile',`area_code` = '$area_code',`is_super_admin`=$is_super_admin,`status`=$status,`description`='$description',`avatar`='$avatar' WHERE id= $admin_id";
        $update_ok = $this->db->query($sql)->affected_rows();
        if ($update_ok) {
            ajax_return(SUCCESS, get_tips(8011));
        }
        ajax_return(ERROR, get_tips(8012));
    }

    /**
     * 管理员列表
     */
    public function admin_user_list()
    {
        $uid = intval($_POST['uid'] ?? '');
        $status = $_POST['status'] ?? 'all';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        //处理条件
        $where = '1';
        $where .= $uid != '' ? " and id={$uid}" : '';
        if ($status != 'all' && $status != '') {
            $where .= $status == 'pass' ? " and status=1" : ' and status=0';
        }
        $where .= $start_time && $end_time ? " and uptime >= '{$start_time}' and uptime <= '{$end_time}'" : '';

        $sql = "select `id`,`username`,`mobile`,`area_code`,`is_super_admin`,`status`,`login_time`,`login_ip`,`description`,`avatar`,`uptime` from admin_user_" . DATABASESUFFIX . " where {$where}";
        $admin_users = $this->db->query($sql)->result_array();

        $admin_id = array_column($admin_users, 'id');
        $admin_id = implode(',', $admin_id);
        $role_arr = [];
        if ($admin_id) {
            $role_sql = "select user.admin_id, role.role_name from admin_user_role_" . DATABASESUFFIX . " user join admin_role_" . DATABASESUFFIX . " role on user.role_id = role.role_id  where user.admin_id in ($admin_id)";
            $role_arr = $this->db->query($role_sql)->result_array();
        }


        $role = [];
        foreach ($role_arr as $role_val) {
            if (!isset($role[$role_val['admin_id']])) {
                $role[$role_val['admin_id']] = [];
                $role[$role_val['admin_id']]['role_name'] = $role_val['role_name'] . '，';
                $role[$role_val['admin_id']]['admin_id'] = $role_val['admin_id'];
                continue;
            }
            $role[$role_val['admin_id']]['role_name'] .= $role_val['role_name'] . '，';
        }

        foreach ($admin_users as &$user) {

            $user['status_msg'] = $user['status'] == 1 ? get_tips(8013) : get_tips(8014);
            // $user['avatar'] = get_pic_url($user['avatar'],'avatar');

            if (isset($role[$user['id']])) {
                $user['role_name'] = trim($role[$user['id']]['role_name'], '，');
            } else {
                $user['role_name'] = get_tips(8015);
            }
        }

        ajax_return(SUCCESS, '', $admin_users);
    }

    /**
     * 添加管理员
     */
    public function add_admin_user()
    {
        $uid = intval($_POST['uid'] ?? 0);
        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $area_code = htmlspecialchars(trim($_POST['area_code'] ?? ''));
        $mobile = htmlspecialchars(trim($_POST['mobile'] ?? ''));
        $password = htmlspecialchars(trim($_POST['password'] ?? ''));
        $commitpass = htmlspecialchars(trim($_POST['commitpass'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $advert_id = htmlspecialchars(trim($_POST['advert_id'] ?? 0));

        if (empty($username)) ajax_return(ERROR, get_tips(8009));
        // if (empty($area_code)) ajax_return(ERROR, get_tips(8007));
        // if (!check_user_data('mobile', $mobile)) ajax_return(ERROR, get_tips(8006));
        if (empty($password) || !check_user_data('password', $password)) ajax_return(ERROR, get_tips(8016));
        if ($password !== $commitpass) {
            ajax_return(SUCCESS, get_tips(8017));
        }
        // if (empty($description)) ajax_return(ERROR, get_tips(8007));

        $password = password_hash($password, PASSWORD_DEFAULT);
        // $this->check_admin_user_name($username, $mobile, $area_code);

        $sql = "INSERT INTO admin_user_" . DATABASESUFFIX . " (`uid`, `username`,`password`,`mobile`,`area_code`,`description`, `advert_id`) VALUES ($uid, '$username','$password','$mobile',$area_code,'$description', '$advert_id')";
        $insert_id = $this->db->query($sql)->insert_id();
        if (!$insert_id) {
            ajax_return(ERROR, get_tips(1004));
        }

        // 将客服ID放入redis
        if ($uid) {
            $this->redis->sAdd("customer:service", $uid);
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 查看后台用户单条
     */
    public function show_admin_user()
    {
        $admin_id = htmlspecialchars(trim($_POST['role_id'] ?? 0));
        if (intval($admin_id) == 0) ajax_return(ERROR, get_tips(1006));
        $admin_user = $this->look_admin_user_info($admin_id);
        if (!$admin_user) ajax_return(ERROR, get_tips(1007));

        if ($admin_user['is_super_admin'] == 1) {
            $admin_user['is_super_admin_msg'] = get_tips(8018);
            $admin_user['desc'] = get_tips(8018);
        } else {
            //查询权限名称
            $sql = "select r.role_name,r.desc from admin_user_role_" . DATABASESUFFIX . " ur join admin_role_" . DATABASESUFFIX . " r on ur.role_id = r.role_id where ur.admin_id = {$admin_user['id']}";
            $res = $this->db->query($sql)->result_array();
            $role_arr = array_column($res, 'role_name');
            $admin_user['is_super_admin_msg'] = implode(',', $role_arr);
            $role_arr = array_column($res, 'desc');
            $admin_user['desc'] = implode(',', $role_arr);
        }

        $admin_user['status_msg'] = $admin_user['status'] == 1 ? get_tips(8013) : get_tips(8014);
        $admin_user['avatar'] = get_pic_url($admin_user['avatar'], 'avatar');

        ajax_return(SUCCESS, '', $admin_user);
    }

    /**
     * 删除后台管理员
     */
    public function delete_admin_user()
    {
        $id = htmlspecialchars(trim($_POST['id'] ?? 0));
        if (intval($id) == 0) ajax_return(ERROR, get_tips(1006));
        $admin_user_info = $this->look_admin_user_info($id);
        if ($admin_user_info && $admin_user_info['is_super_admin'] == 1) {
            ajax_return(ERROR, get_tips(8019));
        }

        //开启事务
        $this->db->trans_begin();
        $sql = "DELETE FROM admin_user_" . DATABASESUFFIX . " WHERE id=$id";
        $delete_admin_user = $this->db->query($sql)->affected_rows(); //删除用户

        $sql = "DELETE FROM admin_user_role_" . DATABASESUFFIX . " WHERE admin_id=$id";
        $delete_admin_user_role = $this->db->query($sql)->affected_rows();  //删除用户分配角色标的记录

        if ($delete_admin_user) {
            $this->db->trans_commit();

            if ($admin_user_info['uid']) {
                $this->redis->sRem("customer:service", $admin_user_info['uid']);
            }

            ajax_return(SUCCESS, get_tips(1005));
        } else {
            $this->db->trans_rollback();
            ajax_return(ERROR, get_tips(1004));
        }
    }

    /**
     * 停用管理员账号
     */
    public function stop_admin()
    {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status']);
        if (empty($id)) {
            ajax_return(ERROR, get_tips(1006));
        }
        $admin_user_info = $this->look_admin_user_info($id);
        if ($admin_user_info['is_super_admin'] == 1) ajax_return(ERROR, get_tips(8020));

        $sql = "select role_id from admin_user_role_1 where admin_id = $id";
        $res = $this->db->query($sql)->row_array();
        if ($res['role_id'] == 31) {
            switch ($status) {
                case '0':
                    $this->redis->srem('customer:service', $admin_user_info['uid']);
                    break;
                case '1':
                    $this->redis->sadd('customer:service', $admin_user_info['uid']);
                    break;
            }
        }
        $sql = "update admin_user_" . DATABASESUFFIX . " set status = $status where id = $id";
        $this->db->query($sql);
        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 公用的方法查看个人信息
     */
    private function look_admin_user_info($id, $fields = "")
    {
        if (empty($fields)) {
            $fields = "`id`, `uid`, `is_super_admin`,`mobile`,`area_code`,`is_super_admin`,`status`,`login_time`,`login_ip`,`description`,`avatar`,`uptime`";
        }
        $sql = "select $fields from admin_user_" . DATABASESUFFIX . " where id = $id";
        return $admin_user_info = $this->db->query($sql)->row_array();
    }

    /**
     * @param $username :用户
     * @param $mobile :手机号
     * @param $area_code ;国际区号
     * 添加时候验证用户信息是否已经存在了
     */
    private function check_admin_user_name($username = '', $mobile = '', $area_code = '', $id = '')
    {
        /**
         * 验证国际区号
         */
        if (!empty($area_code)) {
            $sql = "select count(*) as t from cat_country_" . DATABASESUFFIX . " as t where  `status` = 1 and area_code = " . $area_code;
            $area_code_arr = $this->db->query($sql)->row_array();
            if ($area_code_arr['t'] == 0) ajax_return(ERROR, get_tips(8021));
        }

        /**
         * 验证这个人账号手机号是否已经存在;
         */
        if (!empty($id)) {
            $sql = "select id from admin_user_" . DATABASESUFFIX . " where username = '$username'";
            $has_admin_user = $this->db->query($sql)->row_array();
            if ($has_admin_user['id'] && $has_admin_user['id'] <> $id) ajax_return(ERROR, get_tips(8022));

            $sql = "select id from admin_user_" . DATABASESUFFIX . " where `mobile`=$mobile and `area_code` = $area_code";
            $has_admin_user = $this->db->query($sql)->row_array();
            if ($has_admin_user['id'] && $has_admin_user['id'] <> $id) ajax_return(ERROR, get_tips(8023));
        } else {
            $sql = "select id from admin_user_" . DATABASESUFFIX . " where (mobile = $mobile and area_code = $area_code) or username = '$username'";
            $has_admin_user = $this->db->query($sql)->row_array();
            if ($has_admin_user['id']) ajax_return(ERROR, get_tips(8024));
        }
    }

    /**
     * 上传头像
     * @param $avatar
     * @return string
     * @author :kobe
     */
    protected function upload_img($avatar)
    {
        if (empty($avatar)) {
            ajax_return(ERROR, get_tips(8025));
        }
        $_FILES['file'] = $avatar;
        $upload = new Upload();
        $media_list = [];
        $index = 0;
        foreach ($_FILES['file']['name'] as $key => $file) {
            $tpl_file_key = "tpl_file_" . $index;
            $_FILES[$tpl_file_key] = [
                'name' => $_FILES['file']['name'][$index],
                'type' => $_FILES['file']['type'][$index],
                'tmp_name' => $_FILES['file']['tmp_name'][$index],
                'error' => $_FILES['file']['error'][$index],
                'size' => $_FILES['file']['size'][$index],
            ];

            $ext_arr = explode(".", $_FILES[$tpl_file_key]['name']);
            $ext = array_pop($ext_arr);
            $filename = "/admin/" . date("Ymd") . rand_code(10, 'both') . "." . $ext;
            $upload_result = $upload->run_upload($tpl_file_key, $filename, image);
            if (empty($upload_result['status'])) {
                ajax_return(ERROR, get_tips(8026), $upload_result);
            }
            return "/admin/" . $upload_result['file_name'];
        }
    }

    /**
     * 用户与权限权限
     */
    public function update_admin_user_role()
    {
        $flag = true;
        $this->db->trans_begin();
        $commit_admin_role = array_filter($_POST['commit_admin_role'] ?? []);
        $admin_id = htmlspecialchars(trim($_POST['admin_id'] ?? 0));
        if (intval($admin_id) == 0) ajax_return(ERROR, get_tips(1006));
        $sql = "select `role_id` from admin_user_role_" . DATABASESUFFIX . " where admin_id = $admin_id";
        $user_role = $this->db->query($sql)->result_array();
        $result = array();
        if ($user_role) {
            array_map(function ($value) use (&$result) {
                $result = array_merge($result, array_values($value));
            }, $user_role);
        }
        if (!empty($result) && !empty($commit_admin_role)) {
            $delete = array();
            $insert = array();
            $diff_1 = array_diff($result, $commit_admin_role);
            $diff_2 = array_diff($commit_admin_role, $result);
            foreach ($diff_2 as $value) {
                array_push($diff_1, $value);
            }
            foreach ($diff_1 as $value) {
                /**
                 * 判断差集在不在 提交的数组里 在:新增 不再:删除
                 */
                if (in_array($value, $commit_admin_role)) {
                    $insert[] = $value;
                } else {
                    $delete[] = $value;
                }
            }
            if ($insert) {
                foreach ($insert as $role_id) {
                    $sql = "INSERT INTO admin_user_role_" . DATABASESUFFIX . " (`admin_id`,`role_id`) VALUES ('$admin_id','$role_id')";
                    $add_user_role = $this->db->query($sql)->insert_id();
                    if (!$add_user_role) $flag = false;
                }
            }
            if ($delete) {
                $delete_str = implode(',', $delete);
                $sql = "DELETE FROM admin_user_role_" . DATABASESUFFIX . " WHERE role_id in ($delete_str) and admin_id = $admin_id";
                $delete_admin_user_role = $this->db->query($sql)->affected_rows();
                if (!$delete_admin_user_role) $flag = false;
            }
        }

        /**
         * 删除管理员所有角色
         */
        if (!empty($result) && empty($commit_admin_role)) {
            $delete_str = implode(',', $result);
            $sql = "DELETE FROM admin_user_role_" . DATABASESUFFIX . " WHERE role_id in ($delete_str) and admin_id = $admin_id";
            $delete_admin_user_role = $this->db->query($sql)->affected_rows();
            if (!$delete_admin_user_role) $flag = false;
        }

        /**
         * 新增管理员角色
         */
        if (empty($result) && !empty($commit_admin_role)) {
            foreach ($commit_admin_role as $role_id) {
                $sql = "INSERT INTO admin_user_role_" . DATABASESUFFIX . " (`admin_id`,`role_id`) VALUES ('$admin_id','$role_id')";
                $add_user_role = $this->db->query($sql)->insert_id();
                if (!$add_user_role) $flag = false;
            }
        }
        if ($flag) {
            $this->db->trans_commit();
            ajax_return(SUCCESS, get_tips(1005));
        } else {
            $this->db->trans_rollback();
            ajax_return(ERROR, get_tips(1004));
        }
    }

    /**
     * 用户和角色列表:一对多 查询某个管理员所有的权限
     * @author :kobe
     * $admin_id
     */
    public function show_admin_user_role()
    {
        $admin_id = htmlspecialchars(trim($_POST['admin_id'] ?? 0));
        if (intval($admin_id) == 0) ajax_return(ERROR, get_tips(1006));
        $sql = "select `id`,`admin_id`,a.`role_id`,b.`role_name` from admin_user_role_" . DATABASESUFFIX . " as a INNER JOIN admin_role_" . DATABASESUFFIX . " as b on a.role_id = b.role_id where a.`admin_id` = " . $admin_id;
        $admin_user_role = $this->db->query($sql)->result_array();
        if ($admin_user_role) {
            ajax_return(SUCCESS, '', $admin_user_role);
        }
        ajax_return(ERROR, get_tips(1004));
    }

    /**
     * 角色和权限关系 新增或更新
     */
    public function update_role_power()
    {
        $flag = true;
        $this->db->trans_begin();
        $commit_role_power = array_filter($_POST['commit_role_power'] ?? []);
        $role_id = htmlspecialchars(trim($_POST['role_id'] ?? 0));
        if (intval($role_id) == 0) ajax_return(ERROR, get_tips(1006));
        $sql = "select `power_id` from admin_role_power_" . DATABASESUFFIX . " where role_id = $role_id";
        $role_power = $this->db->query($sql)->result_array();
        $result = array();
        if ($role_power) {
            array_map(function ($value) use (&$result) {
                $result = array_merge($result, array_values($value));
            }, $role_power);
        }
        if (!empty($result) && !empty($commit_role_power)) {
            $delete = array();
            $insert = array();
            $diff_1 = array_diff($result, $commit_role_power);
            $diff_2 = array_diff($commit_role_power, $result);
            foreach ($diff_2 as $value) {
                array_push($diff_1, $value);
            }
            foreach ($diff_1 as $value) {
                /**
                 * 判断差集在不在 提交的数组里 在:新增 不再:删除
                 */
                if (in_array($value, $commit_role_power)) {
                    $insert[] = $value;
                } else {
                    $delete[] = $value;
                }
            }
            if ($insert) {
                foreach ($insert as $power_id) {
                    $sql = "INSERT INTO admin_role_power_" . DATABASESUFFIX . " (`role_id`,`power_id`) VALUES ('$role_id','$power_id')";
                    $add_role_power = $this->db->query($sql)->insert_id();
                    if (!$add_role_power) $flag = false;
                }
            }
            if ($delete) {
                $delete_str = implode(',', $delete);
                $sql = "DELETE FROM admin_role_power_" . DATABASESUFFIX . " WHERE power_id in ($delete_str) and role_id = $role_id";
                $delete_admin_role_power = $this->db->query($sql)->affected_rows();
                if (!$delete_admin_role_power) $flag = false;
            }
        }

        /**
         * 删除角色所有权限
         */
        if (!empty($result) && empty($commit_role_power)) {
            $delete_str = implode(',', $result);
            $sql = "DELETE FROM admin_role_power_" . DATABASESUFFIX . " WHERE role_id in ($delete_str) and role_id = $role_id";
            $delete_admin_role_power = $this->db->query($sql)->affected_rows();
            if (!$delete_admin_role_power) $flag = false;
        }

        /**
         * 新增角色权限
         */
        if (empty($result) && !empty($commit_role_power)) {
            foreach ($commit_role_power as $power_id) {
                $sql = "INSERT INTO admin_role_power_" . DATABASESUFFIX . " (`role_id`,`power_id`) VALUES ('$role_id','$power_id')";
                $add_role_power = $this->db->query($sql)->insert_id();
                if (!$add_role_power) $flag = false;
            }
        }
        if ($flag) {
            $this->db->trans_commit();
            ajax_return(SUCCESS, get_tips(1005));
        } else {
            $this->db->trans_rollback();
            ajax_return(ERROR, get_tips(1004));
        }
    }

    /**
     * 角色和权限关系 ,查询某个权限所有的权限
     */
    public function show_role_power()
    {
        $role_id = htmlspecialchars(trim($_POST['role_id'] ?? 0));
        if (intval($role_id) == 0) ajax_return(ERROR, get_tips(1006));
        $sql = "select `id`,a.`role_id`,a.`power_id`,b.`power_name` from admin_role_power_" . DATABASESUFFIX . " as a INNER JOIN admin_power_" . DATABASESUFFIX . " as b on a.power_id = b.power_id where  `role_id` = $role_id";
        $role_power = $this->db->query($sql)->result_array();
        if ($role_power) {
            ajax_return(SUCCESS, '', $role_power);
        }
        ajax_return(ERROR, get_tips(1004));
    }

    /**
     * 获取所有权限 show_power
     *
     * @return void
     */
    public function show_power()
    {
        $role_id = htmlspecialchars(trim($_POST['role_id'] ?? 0));
        if (intval($role_id) == 0) ajax_return(ERROR, get_tips(1006));
        $power_sql = "select power_id as id,power_name as label from admin_power_" . DATABASESUFFIX;
        $powerData = $this->db->query($power_sql)->result_array();
        $nav = [];
        $i = -1;
        $j = -1;

        /**
         * 需要将指定的数据加到二级导航栏中的power_id
         */
        $expire_arr = [761, 762];
        foreach ($powerData as $key => $val) {
            if (substr($val['id'], -2) == '00') {
                $nav[++$i] = $val;
                $j = -1;
            } else if (substr($val['id'], -1) == '0') {
                if ($val['id'] == 760) {
                    $val['label'] = "提现审核";
                }
                $nav[$i]['children'][++$j] = $val;
            } else {
                if (in_array($val['id'], $expire_arr)) {
                    if ($val['id'] == 761) {
                        $val['label'] = "提款下发";
                    }
                    if ($val['id'] == 762) {
                        $val['label'] = "提款下发记录";
                    }
                    $nav[$i]['children'][++$j] = $val;
                } else {
                    switch ($val['id']) {
                        case 763:
                            $j = $j - 2;
                            $nav[$i]['children'][$j]['children'][] = $val;
                            break;
                        case 766:
                            $j = $j + 1;
                            $val['label'] = "获取下发记录列表";
                            $nav[$i]['children'][$j]['children'][] = $val;
                            break;
                        case 764:
                        case 765:
                        case 767:
                        case 768:
                            $nav[$i]['children'][$j]['children'][] = $val;
                            break;
                        case 769:
                            $j = $j + 1;
                            $val['label'] = "获取下发历史记录列表";
                            $nav[$i]['children'][$j]['children'][] = $val;
                            break;
                        default:
                            $nav[$i]['children'][$j]['children'][] = $val;
                            break;


                    }
                }
            }
        }


        // 获取当前角色已有的权限
        $role_power_sql = "select power_id as id from admin_role_power_" . DATABASESUFFIX . " where role_id = {$role_id}";
        $role_power = $this->db->query($role_power_sql)->result_array();

        ajax_return(SUCCESS, '', array(
            'power_list' => $nav,
            'role_power' => array_column($role_power, 'id')
        ));
    }

    /**
     * 管理员角色分配 role_assignment
     *
     * @return json
     */
    public function role_assignment()
    {
        $admin_id = htmlspecialchars(trim($_POST['admin_id'] ?? 0));
        if (intval($admin_id) == 0) ajax_return(ERROR, get_tips(1006));

        // 获取所有角色
        $role_sql = "select role_id,role_name from admin_role_" . DATABASESUFFIX;
        $role_list = $this->db->query($role_sql)->result_array();

        // 获取已有权限
        $have_role_sql = "select role_id from admin_user_role_" . DATABASESUFFIX . " where admin_id = {$admin_id}";
        $have_role_list = $this->db->query($have_role_sql)->result_array();

        $role_list = array_index($role_list, 'role_id');

        foreach ($have_role_list as &$role) {
            $role_id = $role['role_id'];
            if (isset($role_list[$role_id])) {
                $role['name'] = $role_list[$role_id]['role_name'];
            }
        }

        ajax_return(SUCCESS, '', array(
            'role_list' => array_values($role_list),
            'have_role_list' => array_column($have_role_list, 'role_id')
        ));
    }

    /**
     * 公告列表 message_list
     *
     * @return json
     */
    public function message_list()
    {
        $page = intval($_POST['page'] ?? 1);
        $status = intval($_POST['status'] ?: 0);
        $start_time = $_POST['start_time'] ?: '';
        $end_time = $_POST['end_time'] ?: '';

        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $where = '';
        if ($status > 0) {
            $status = (int)$status - 1;
            $where .= ' and status = ' . $status;
        }
        if ($start_time && $end_time) {
            $where .= " and uptime >= '{$start_time}' and uptime <= '{$end_time}'";
        }
        // 查询消息数据
        $sql = "select * from app_notice_" . DATABASESUFFIX . " where 1 $where";
        $message = $this->db->query($sql)->result_array();

        // 查询总条数
        $count_sql = "select count(id) as num from app_notice_" . DATABASESUFFIX . " where 1 $where";
        $total = $this->db->query($count_sql)->row_array()['num'];
        ajax_return(SUCCESS, '', array(
            'data' => $message,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 消息状态修改 message_pass
     *
     * @return json
     */
    public function message_pass()
    {
        $status = $_POST['status'] ?: 0;
        $id = intval($_POST['id'] ?: 0);
        if (!$status || !$id) ajax_return(ERROR, get_tips(1006));
        $type = $status == 'up' ? 1 : 0;

        $sql = "update app_notice_" . DATABASESUFFIX . " set status = {$type} where id = {$id}";
        $result = $this->db->query($sql)->affected_rows();
        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * 修改消息内容 save_message_info
     *
     * @return void
     */
    public function save_message_info()
    {
        $info = htmlspecialchars($_POST['info'] ?: 0);
        $id = intval($_POST['id'] ?: 0);
        $status = $_POST['status'] ?: 0;
        $title = sql_format($_POST['title'] ?? '');
        $remark = sql_format($_POST['remark'] ?? '');
        if (!$info || !$id) ajax_return(ERROR, get_tips(1006));
        $type = $status == 'up' ? 1 : 0;
        $set = "content = '{$info}'";
        $set .= ",status = {$type}";
        if ($title) {
            $set .= ",title = '{$title}'";
        }
        if ($remark) {
            $set .= ",remark = '{$remark}'";
        }

        $sql = "update app_notice_" . DATABASESUFFIX . " set {$set} WHERE id = {$id}";
        $result = $this->db->query($sql)->affected_rows();
        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * 新增公告消息 add_message
     *
     * @return void
     */
    public function add_message()
    {
        $info = htmlspecialchars($_POST['info'] ?: 0);
        $status = $_POST['status'] ?: 0;
        $title = sql_format($_POST['title'] ?? '');
        $remark = sql_format($_POST['remark'] ?? '');
        if (!$info || !$status) ajax_return(ERROR, get_tips(1006));

        $type = $status == 'up' ? 1 : 0;
        $date = date('Y-m-d H:i:s');
        $sql = "INSERT INTO app_notice_" . DATABASESUFFIX . "(title, content, status, uptime, remark) value('{$title}', '{$info}', {$type}, '{$date}', '{$remark}')";
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }

    public function change_langs()
    {
        $lang = $_POST['lang'];
        $this->redis->set('lang', $lang);
        ajax_return(SUCCESS, $lang);
    }
}
