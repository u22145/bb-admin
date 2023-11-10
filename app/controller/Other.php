<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/8/9
 * Time: 16:37
 */
class Other extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load_model('common_model');
    }

    /**
     * 国家列表
     */
    public function get_country()
    {
        $cache_key = "cat:country";
        $mcache = $this->init_mcache();
        $list = $mcache->get($cache_key);
        if (empty($list)) {
            $this->init_db();
            $sql = "SELECT id, country FROM cat_country_" . DATABASESUFFIX . " WHERE status=1 ORDER BY country ASC";
            $list = $this->db->query($sql)->result_array();
            $mcache->set($cache_key, $list, 30 * 86400);
        }

        ajax_return(SUCCESS, "", $list);
    }

    /**
     *  私聊等级限制
     */
    public function msg_restrict_list()
    {
        
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);

        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        if(isset($_POST['level']) && filter_var($_POST['level'], FILTER_VALIDATE_INT) && $_POST['level'] > 0) {
            $level      = intval($_POST['level']);
            $sql        = "INSERT INTO private_msg_restrict_log (`admin_id`, `admin_name`, `level`) VALUES ({$admin_id}, '{$admin_name}', {$level})";
            $insert_id  = $this->db->query($sql)->insert_id();
            if(!$insert_id) {
                @file_put_contents(ERRLOG_PATH . '/other_pm_log_' . date("Ymd") . '.log',
                            date("Y-m-d H:i:s") . "failed to insert: $sql... \n",
                            FILE_APPEND);
                ajax_return(ERROR, 'FAILED TO CREATE THE NEW RECORD', []);
            }
            // 有效期一个月
            $this->redis->set('pm:level:restrict', $level, 30 * 86400);
        }

        $sql    = "SELECT * FROM private_msg_restrict_log ORDER BY update_at DESC LIMIT {$page_size} OFFSET {$limit}";
        $list   = $this->db->query($sql)->result_array();
        foreach ($list as $key => &$value) {
            $value['update_at'] = time_to_local_string($value['update_at']);
        }

        $sql = "SELECT count(id) as num FROM private_msg_restrict_log";
        $total = $this->db->query($sql)->row_array()['num'];

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int) $total
        ));

    }

    /**
     *  私聊等级限制设置
     */
    // public function set_private_restrict_level()
    // {
    //     $admin_id   = $this->user['id'];
    //     $admin_name = $this->user['username'];

    //     $level      = intval($_POST['level'] ?? 2);

    //     $sql        = "INSERT INTO private_msg_restrict_log (`admin_id`, `admin_name`, `level`) VALUES ({$admin_id}, '{$admin_name}', {$level})";
    //     $insert_id  = $this->db->query($sql)->insert_id();
    //     if(!$insert_id) {
    //         @file_put_contents(ERRLOG_PATH . '/other_pm_log_' . date("Ymd") . '.log',
    //                     date("Y-m-d H:i:s") . "failed to insert: $sql... \n",
    //                     FILE_APPEND);
    //         ajax_return(ERROR, 'FAILED TO CREATE THE NEW RECORD', []);
    //     }
    //     // 有效期一个月
    //     $this->redis->set('pm:level:restrict', $level, 30 * 86400);

    //     ajax_return(SUCCESS, '', []);

    // }

    /**
     * 应用版本列表
     */
    public function version_list()
    {
        // 处理参数
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $type = sql_format($_POST['type'] ?? '');
        $load = sql_format($_POST['load'] ?? 'all');
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        //处理条件
        $where = '1';
        $where .= $type ? " and platform = {$type}" : '';
        if ($load == 'yes') {
            $where .= ' and enforce = 1';
        } else if ($load == 'on') {
            $where .= ' and enforce = 0';
        }
        if ($start_time && $end_time) {
            $where .= " and uptime >= '{$start_time}' and uptime <= '{$end_time}'";
        }
        // 查询数据
        $table = 'app_version_' . DATABASESUFFIX;
        $field = "platform, version, url, uplog, enforce, uptime, status, uptitle, download";
        $sql = "SELECT {$field} FROM {$table} WHERE {$where} ORDER BY uptime DESC LIMIT {$page_size} offset $limit";
        $list = $this->db->query($sql)->result_array();

        // 查询条数
        $count_sql = "SELECT COUNT(*) as num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->row_array()['num'];

        // 返回数据
        $data = array(
            'list' => $list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        );
        ajax_return(SUCCESS, get_tips(1005), $data);
    }

    /**
     * 应用版本编辑
     */
    public function version_edit()
    {
        $platform = intval($_POST['platform'] ?? 0);
        $version = sql_format($_POST['version'] ?? '');
        $url = str_replace("'", "''", $_POST['url'] ?? '');
        $uplog = sql_format($_POST['uplog'] ?? '');
        $enforce = intval($_POST['enforce'] ?? 0);
        $uptitle = sql_format($_POST['uptitle'] ?? '');
        $download = intval($_POST['download'] ?? 1);

        $sql = "UPDATE app_version_" . DATABASESUFFIX . " SET version='$version',url='$url',uplog='$uplog',enforce=$enforce,uptitle='$uptitle',download=$download WHERE platform=$platform";
        $this->db->query($sql);

        //记录日志
        $this->common_model->admin_write_log("编辑platform：{$platform}的信息");
        ajax_return(SUCCESS, get_tips(5011));
    }

    /**
     * 应用版本删除
     */
    public function version_del()
    {
        $platform = intval($_POST['platform'] ?? 0);

        $sql = "UPDATE app_version_" . DATABASESUFFIX . " SET status=1 WHERE platform=$platform";
        $this->db->query($sql);

        //记录日志
        $this->common_model->admin_write_log("删除platform：{$platform}的信息");
        ajax_return(SUCCESS, get_tips(1012));
    }

    /**
     * vip类型列表
     */
    public function vip_list()
    {
        $field = "vip_type,vip_id,vip_name,fee,money,active_life,status";
        $sql = "SELECT $field FROM cat_vip_" . DATABASESUFFIX;
        $list = $this->db->query($sql)->result_array();
        foreach ($list as &$value) {
            $value['fee']   = number_format($value['fee'], MONEY_DECIMAL_DIGITS, ".", ""); 
            $value['money'] = number_format($value['money'], MONEY_DECIMAL_DIGITS, ".", ""); 
        }
        //记录日志
        $this->common_model->admin_write_log(get_tips(1013));
        ajax_return(SUCCESS, "", $list);
    }

    /**
     * vip类型编辑
     */
    public function vip_edit()
    {
        $vip_type = intval($_POST['vip_type'] ?? 0);
        $vip_id = intval($_POST['vip_id'] ?? 0);
        $vip_name = sql_format($_POST['vip_name'] ?? '');
        $fee = floatval($_POST['fee'] ?? 0);
        $money = floatval($_POST['money'] ?? 0);
        $active_life = intval($_POST['active_life'] ?? 0);

        $sql = "UPDATE cat_vip_" . DATABASESUFFIX . " SET vip_name='{$vip_name}', fee = '{$fee}', money = '{$money}', active_life='{$active_life}' WHERE vip_type = $vip_type AND vip_id = $vip_id";
        $this->db->query($sql);

        //记录日志
        $this->common_model->admin_write_log("编辑vip_type：{$vip_type}，vip_id：{$vip_id}的信息");
        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     *  普通用戶充值類型列表
     */
    public function user_list() 
    {
        $sql = "select * from cat_recharge_1 where status = 1";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as &$value) {
            $value['amount'] = number_format($value['amount'], MONEY_DECIMAL_DIGITS, ".", ""); 
            $value['money'] = number_format($value['money'], MONEY_DECIMAL_DIGITS, ".", ""); 
        }
        ajax_return(SUCCESS, "", $list);
    }

    /**
     *  普通用戶充值類型修改
     */
    public function user_edit() 
    {
        $id = intval($_POST['id'] ?? 0);
        $rec_name = $_POST['rec_name'] ?? '';
        $amount = intval($_POST['amount'] ?? 0);
        $money = intval($_POST['money'] ?? 0);
        $id || ajax_return(ERROR, "id不能為空");

        $sql = "UPDATE cat_recharge_1 SET rec_name = '$rec_name', amount = $amount, `money` = $money WHERE id = $id";
        $res = $this->db->query($sql)->affected_rows();
        $res && ajax_return(SUCCESS, "修改成功");
        ajax_return(ERROR, "修改失敗");
    }

    /**
     * 应用授权 appkey_list
     *
     * @return json
     */
    public function appkey_list()
    {
        $sql = "select * from app_key";
        $appkey = $this->db->query($sql)->result_array();
        ajax_return(SUCCESS, '', $appkey);
    }

    /**
     * 新增应用授权密码 create_appkey
     *
     * @return void
     */
    public function create_appkey()
    {
        $comm = htmlspecialchars($_POST['comm'] ?? '');
        if (!$comm) {
            ajax_return(ERROR, get_tips(1006));
        }
        //读取参数
        $appid = DATABASESUFFIX;

        //计算id
        $sql = "select platform from app_key where appid = $appid order by platform desc limit 1";
        $res = $this->db->query($sql)->row_array();
        if ($res) {
            $platform = $res['platform'] + 1;
        } else {
            $platform = 1;
        }

        //生成key
        $appkey = rand_code(16, 'both');
        $appsecret = rand_code(30, 'all');
        //写入数据库
        $date = date('Y-m-d H:i:s');
        $sql = "insert into app_key (appid, platform, appkey, appsecret, comm, uptime) values ($appid, $platform, '$appkey', '$appsecret', '$comm','{$date}')";
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1015);
        if ($result) {
            //同步写入redis
            $this->redis->hmset("app:key:$appkey", ['appid' => $appid, 'platform' => $platform, 'appsecret' => $appsecret]);
            $result_status = SUCCESS;
            $result_msg = get_tips(1016);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * 删除授权 delete_appkey
     *
     * @return void
     */
    public function delete_appkey()
    {
        $admin_user = $this->user;
        if ($admin_user['id'] != 1) {
            ajax_return(ERROR, get_tips(1017));
            exit;
        }
        $appkey = htmlspecialchars($_POST['appkey'] ?? '');
        if (!$appkey)  ajax_return(ERROR, get_tips(1006));

        $sql = "delete from app_key where appkey = '{$appkey}'";
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1012);
        if ($result) {
            $this->redis->del("app:key:$appkey");
            $result_status = SUCCESS;
            $result_msg = get_tips(1018);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * vpn管理 vpn_list
     *
     * @return json
     */
    public function vpn_list()
    {
        $sql = "select * from vpn_list_" . DATABASESUFFIX;
        $vpn = $this->db->query($sql)->result_array();
        ajax_return(SUCCESS, '', $vpn);
    }

    /**
     * 修改vpn状态 update_vpn_status
     *
     * @return json
     */
    public function update_vpn_status()
    {
        $admin_user = $this->user;
        if ($admin_user['id'] != 1) {
            ajax_return(ERROR, get_tips(1017));
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        $status = htmlspecialchars($_POST['status'] ?? '');
        if ($id < 0 || $status == '')  ajax_return(ERROR, get_tips(1006));

        $type = $status == 'pass' ? 1 : 0;

        $sql = "update vpn_list_" . DATABASESUFFIX . " set status = {$type} where id = {$id}";
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
     * 分销提成配置 pate_list
     *
     * @return void
     */
    public function pyra_list()
    {
        $fields = 'id, title, rate, exp_rate,uptime';
        $table = 'cat_pyra_rate_' . DATABASESUFFIX;
        $sql = "SELECT {$fields} FROM {$table} WHERE 1 ORDER BY uptime DESC";
        $result = $this->db->query($sql)->result_array();
        foreach ($result as &$value) {
            $value['rate']     = number_format($value['rate'], MONEY_DECIMAL_DIGITS, ".", "");
            $value['exp_rate'] = number_format($value['exp_rate'], MONEY_DECIMAL_DIGITS, ".", "");
        }
        ajax_return(SUCCESS, '', $result);
    }

    /**
     * 修改比例 pyra_edit
     *
     * @return void
     */
    public function pyra_edit()
    {
        $data = $_POST['data'];
        $role = array(
            'rate' => 'must|int',
            'exp_rate' => 'int',
            'id' => 'must|int'
        );

        $msg = array(
            'rate' => get_tips(5012),
            'rate.int' => get_tips(5013),
            'exp_rate' => get_tips(5014),
            'exp_rate.int' => get_tips(5015),
            'id' => get_tips(1006)
        );
        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }
        $rate = $data['rate'];
        $exp_rate = $data['exp_rate'];
        $id = $data['id'];
        $table = 'cat_pyra_rate_' . DATABASESUFFIX;
        $sql = "UPDATE {$table} SET rate = {$rate}, exp_rate = {$exp_rate} WHERE id = {$id}";
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
     *  敏感词管理
     */
    public function sensitive()
    {
        $table = 'cat_sensitive_' . DATABASESUFFIX;
        $where = '1';

        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $name = input('post.name', 'htmlspecialchars', '');
        $type = input('post.type', 'intval', 0);
        $exp = input('post.exp', 'intval', 1);

        $where .= $name ? " AND `name` LIKE '%$name%'" : '';
        $where .= $type > 0 ? " AND `type` = {$type}" : '';
        // 查询数据
        $limit = (($page - 1) * $page_size);
        $sql = "SELECT `id`, `name`, `type`, `admin_name`, `add_time` FROM {$table} WHERE {$where} ORDER BY `add_time` DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();

        $sql = "SELECT count(id) as num FROM {$table} WHERE {$where}";
        $total = $this->db->query($sql)->row_array()['num'];

        // 处理数据
        foreach ($list as &$text) {
            switch ($text['type']) {
                case 1:
                    $text['type'] = get_tips(10001);
                    break;
                case 2:
                    $text['type'] = get_tips(10002);
                    break;
                case 3:
                    $text['type'] = get_tips(10003);
                    break;
                case 4:
                    $text['type'] = get_tips(10004);
                    break;
                case 5:
                    $text['type'] = get_tips(10005);
                    break;
            }
            unset($text['status']);
            unset($text['admin_id']);
        }
        if ($exp == 2) {
            $header = array(
                'ID',
                get_tips(10006),
                get_tips(10008),
                get_tips(10007),
                get_tips(1001)
            );
            $path = export(get_tips(10009), $header, $list);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int) $total
        ));
    }

    /**
     * 删除敏感词 del_sensitive
     *
     * @return void
     */
    public function del_sensitive()
    {
        $table = 'cat_sensitive_' . DATABASESUFFIX;

        $id = input('post.id', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));

        $sql = "DELETE FROM {$table} WHERE id = {$id}";
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
     * 创建敏感词 create_sensitive
     *
     * @return void
     */
    public function create_sensitive()
    {
        $table = 'cat_sensitive_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'name' => 'must|char',
            'type' => 'must|int',
        );

        $msg = array(
            'name' => get_tips(10010),
            'type' => get_tips(10011),
        );
        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }

        $admin_user = $this->user;
        $name = $data['name'];
        $type = $data['type'];

        $admin_id = $admin_user['id'];
        $admin_name = $admin_user['username'];
        $sql = "INSERT INTO {$table}(`name`, `type`, `admin_id`, `admin_name`) VALUE('{$name}', {$type}, {$admin_id}, '{$admin_name}')";
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
     * 渠道列表 channel_list
     *
     * @return void
     */
    public function channel_list()
    {
        $table = 'channel_' . DATABASESUFFIX;
        $where = '1';

        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', 10);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', 'htmlspecialchars', 0));
        $status = input('post.type', '', 'all');
        if ($status == 'off') {
            $status = 0;
        } else if ($status == 'on') {
            $status = 1;
        }
        // 条件处理
        $where .= $id > 0 ? " AND `id` = {$id}" : '';
        $where .= $name ? " AND `name` LIKE '%{$name}%'" : '';
        $where .= $status !== 'all' ? " AND `status` = {$status}" : '';

        // 数据处理
        $sql = "SELECT id, name, password, type, period, pic_url, qrcode, status, uptime, pid FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as $k => $channel) {
            $list[$k]['pic_url'] = get_pic_url($channel['pic_url']);
            $list[$k]['status_txt'] = $channel['status'] == 1 ? get_tips(10012) : get_tips(10013);
            $list[$k]['url'] = SHARE_SERVER_URL . '/h5/channel/' . $channel['id'];
            $list[$k]['login_url'] = SHARE_SERVER_URL . '/admin/';
            $list[$k]['uptime'] = time_to_local_string($channel['uptime']);
            if (! $channel['qrcode']) {
                //生成二维码
                $qrcode = "qrcode_ch/" . $channel['id'] . ".png";
                $savepath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $qrcode;
                if (! file_exists(dirname($savepath))) {
                    mkdir(dirname($savepath));
                }
                QRcode::png($list[$k]['url'], $savepath);
                $sql = "update $table set qrcode = 1 where id = " . $channel['id'];
                $this->db->query($sql);
            }
            switch ($channel['period']) {
                case 1:
                    $list[$k]['period1'] = '每周';
                    break;
                case 2:
                    $list[$k]['period1'] = '半個月';
                    break;
                case 3:
                    $list[$k]['period1'] = '每月';
                    break;
                default:
                    $list[$k]['period1'] = '';
                    break;
            }
            $list[$k]['qrcode'] = get_pic_url('qrcode_ch/' . $channel['id'] . '.png');
        }
        $count_sql = "SELECT COUNT(id) as num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->affected_rows();

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int) $total
        ));
    }

    /**
     * 修改渠道状态 channel_status
     *
     * @return void
     */
    public function channel_status()
    {
        $table = 'channel_' . DATABASESUFFIX;
        $id = input('post.id', 'intval', 0);
        $status = input('post.status', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));

        $sql = "UPDATE {$table} SET `status` = {$status} WHERE `id` = {$id}";
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
     * 创建渠道 create_channel
     *
     * @return void
     */
    public function create_channel()
    {
        $table = 'channel_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'name' => 'must|char',
            'password' => 'must|char'
        );

        $msg = array(
            'name' => get_tips(10014),
            'url' => get_tips(10015)
        );
        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }

        $name = sql_format($data['name']);
        $password = $data['password'];
        $type = sql_format($data['type']);
        $period = intval($data['period']);
        $pic_url = $data['upload_img'] ?? '';
        $id = $data['id'] ?? '';
        $pid = intval($data['pid'] ?? 0);

        if ($id) {
            $sql = "UPDATE {$table} SET `name` = '{$name}', `password` = '{$password}', `type` = '{$type}', period = $period, `pic_url` = '{$pic_url}', pid = $pid WHERE `id` = {$id}";
        } else {
            $sql = "INSERT INTO {$table}(`name`, `password`, type, period, `pic_url`, `status`, pid) VALUE('{$name}', '{$password}', '$type', $period, '{$pic_url}', 0, $pid)";
        }
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
     * 删除渠道 del_channel
     *
     * @return void
     */
    public function del_channel()
    {
        $table = 'channel_' . DATABASESUFFIX;

        $id = input('post.id', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));

        $sql = "DELETE FROM {$table} WHERE id = {$id}";
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }
}
