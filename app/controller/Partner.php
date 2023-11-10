<?php

/**
 * 合作商数据统计
 */
class Partner extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 合作商列表
     */
    public function partner_list()
    {
        $table = 'partner_' . DATABASESUFFIX;
        $where = '';

        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', '', 0));
        $status = input('post.status', '', 'all');

        $search = trim($_POST['search'] ?? '');

        $collect_type = input('post.collect_type', 'intval', 0);

        $sql = "SELECT * FROM {$table} WHERE is_deleted=0";
        $count_sql = "SELECT COUNT(id) as num FROM {$table} WHERE is_deleted=0";
        if ($search || $search === '0' || $search === 0) {
            if (is_numeric($search)) {
                $sql .= " AND `pid` ={$search}";
                $count_sql .= " AND `pid` ={$search}";
            } else {
                //名称
                $parent_sql = "SELECT id FROM {$table} WHERE is_deleted=0 AND `name` LIKE '%{$search}%'";
                $parent_info = $this->db->query($parent_sql)->result_array();
                if ($parent_info && count($parent_info)) {
                    $p_ids = implode(',', array_column($parent_info, 'id'));
                    $sql .= " AND `pid` in ({$p_ids})";
                    $count_sql .= " AND `pid` in ({$p_ids})";
                } else {
                    ajax_return(SUCCESS, '', array(
                        'list' => [],
                        'page' => $page,
                        'total' => 0
                    ));
                }

            }
        }

        if ($status == 'off') {
            $sql .= " AND status = 0";
            $count_sql .= " AND status = 0";
        } else if ($status == 'on') {
            $sql .= " AND status = 1";
            $count_sql .= " AND status = 1";
        }
        if ($id) {
            $sql .= " AND `id` = {$id}";
            $count_sql .= " AND `id` = {$id}";
        }
        if ($name) {
            $sql .= " AND `name` LIKE '%{$name}%'";
            $count_sql .= " AND `name` LIKE '%{$name}%'";
        }

        if ($collect_type) {
            $sql .= " AND `collect_money_type` = {$collect_type}";
            $count_sql .= " AND `collect_money_type` = {$collect_type}";
        }


        // 数据处理
        $sql .= " ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";


        $list = $this->db->query($sql)->result_array();

        foreach ($list as $k => $partner) {
            $list[$k]['status_txt'] = $partner['status'] == 1 ? "启用" : '停用';
            $list[$k]['url'] = SHARE_SERVER_URL . '/h5/partner/' . $partner['id'];
            $list[$k]['login_url'] = PARTNER_SERVER_URL . '/admin/';

            $list[$k]['uptime'] = time_to_local_string($partner['uptime']);
            if (!$partner['qrcode']) {
                //生成二维码
                $qrcode = "qrcode_pn / " . $partner['id'] . " . png";
                $savepath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $qrcode;
                if (!file_exists(dirname($savepath))) {
                    mkdir(dirname($savepath));
                }
                QRcode::png($list[$k]['url'], $savepath);
                $sql = "update {$table} set qrcode = 1 where id = " . $partner['id'];
                $this->db->query($sql);
            }

            switch ($partner['collect_money_type']) {
                case 1:
                    $list[$k]['collect_money_type_info'] = 'BABY钱包地址';
                    $list[$k]['collect_type_address'] = "BABY钱包地址:\n\r" . $partner['collect_money_addr'];
                    break;
                case 2:
                    $list[$k]['collect_money_type_info'] = '支付宝';
                    $list[$k]['collect_type_address'] = "支付宝转账:\n\r" . $partner['collect_money_addr'];
                    break;
                case 3:
                    $list[$k]['collect_money_type_info'] = '微信';
                    $list[$k]['collect_type_address'] = "微信转账:\n\r" . $partner['collect_money_addr'];
                    break;
                case 4:
                    $list[$k]['collect_money_type_info'] = '银行卡';
                    $list[$k]['collect_type_address'] = "银行卡转账:\n\r" . $partner['collect_money_addr'];
                    break;
                default:
                    $list[$k]['collect_money_type_info'] = '无';
                    $list[$k]['collect_type_address'] = '无';
                    break;
            }
            $list[$k]['share_rate_txt'] = $partner['share_rate'] . '%';
            $list[$k]['qrcode'] = get_pic_url('qrcode_pn/' . $partner['id'] . '.png');
        }
        $total = $this->db->query($count_sql)->affected_rows();

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int)$total
        ));
    }

    /**
     * 修改合作商状态 partner_status
     *
     * @return void
     */
    public function partner_status()
    {
        $table = 'partner_' . DATABASESUFFIX;
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
     * 创建|修改合作商 create_partner
     *
     * @return void
     */
    public function create_partner()
    {
        $table = 'partner_' . DATABASESUFFIX;
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

        $share_rate = intval($data['share_rate']);

        $collect_money_type = intval($data['collect_money_type']);

        $collect_money_addr = trim($data['collect_money_addr']);

        $pid = intval($data['pid'] ?? 0);

        $time = time();

        $id = $data['id'] ?? '';
        if ($id) {
            $sql = "UPDATE {$table} SET `name` = '{$name}', `password` = '{$password}',`share_rate` ={$share_rate},`collect_money_type` ={$collect_money_type},`collect_money_addr` = '{$collect_money_addr}' WHERE `id` = {$id}";
        } else {
            $sql = "INSERT INTO {$table}(`name`, `password`,`share_rate`,`collect_money_type`,`collect_money_addr`,`uptime`,`pid`) VALUE('{$name}', '{$password}',{$share_rate},{$collect_money_type},'{$collect_money_addr}',{$time},{$pid})";
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
     * 分成比例与父级检测的分成比例检测
     */
    public function check_rate()
    {
        $table = 'partner_' . DATABASESUFFIX;

        $pid = input('post.pid', 'intval', 0);
        $rate = input('post.rate', 'intval', 0);

        if ($pid) {
            $check_sql = "select share_rate from {$table} where id={$pid}";
            $check_info = $this->db->query($check_sql)->row_array();
            $check_info = $check_info && count($check_info) ? $check_info : [];
            if ($check_info) {
                if ($rate > $check_info['share_rate']) {
                    ajax_return(ERROR, "分成比例不能超过上级ID的分成比例值：{$check_info['share_rate']}%");
                }
            } else {
                ajax_return(SUCCESS, "ok");
            }
        }
    }

    /**
     * 删除合作商 del_partner
     *
     * @return void
     */
    public function del_partner()
    {
        $table = 'partner_' . DATABASESUFFIX;

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
     * 合作商数据总计
     */
    public function partner_total()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $partner_id = intval($_POST['partner_id'] ?? 0);   //合作商id
        $partner_name = sql_format($_POST['partner_name'] ?? '');
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';

        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, `name`, uptime, 0 uv, 0 reg, 0 down, 0 active, 0 dep2, 0 dep1, 0 rate1, 0 rate2 from partner_$appid where ";
        if ($partner_id) {
            $sql .= "id = $partner_id and ";
        }
        if ($partner_name) {
            $sql .= "name like '$partner_name%' and ";
        }
        $sql .= "status = 1 order by id desc";
        $res = $this->db->query($sql)->result_array();

        if ($day_start && $day_end) {
            $day_start = date('Ymd', strtotime($day_start));
            $day_end = date('Ymd', strtotime($day_end));
        } else {
            $day_start = '20191216';
            $day_end = date('Ymd');
        }

        $uv = 0;
        $reg = 0;
        $down = 0;
        $active = 0;
        $dep2 = 0;
        foreach ($res as $k => $v) {
            $res[$k]['uptime'] = time_to_local_string($v['uptime']);
            $id = $v['id'];
            $pipe = $this->redis->pipeline();
            $pipe->zrange("pn:uv:$id", 0, -1, true);
            $pipe->zrange("pn:down:$id", 0, -1, true);
            $pipe->zrange("pn:reg:$id", 0, -1, true);
            $pipe->zrange("pn:active:$id", 0, -1, true);
            $pipe->zrange("pn:dep1:$id", 0, -1, true);
            $res1 = $pipe->exec();
            $res[$k]['uv'] = 0;
            $pipe = $this->redis->pipeline();
            for ($i = $day_start; $i <= $day_end; $i++) {
                if (0 != substr($i, 6, 2) && strtotime(substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2))) {
                    $res[$k]['uv'] += @$res1[0][$i];
                    $res[$k]['down'] += @$res1[1][$i];
                    $res[$k]['reg'] += @$res1[2][$i];
                    $res[$k]['active'] += @$res1[3][$i];
                    $res[$k]['dep1'] += @$res1[4][$i];
                    $pipe->scard("pn:dep2:$id:$i");
                }
            }
            $res1 = $pipe->exec();
            $res[$k]['dep2'] = array_sum($res1);
            if ($res[$k]['uv']) {
                $res[$k]['rate1'] = intval($res[$k]['reg'] / $res[$k]['uv'] * 100) . '%';
            } else {
                $res[$k]['rate1'] = 0;
            }
            if ($res[$k]['reg']) {
                $res[$k]['rate2'] = intval($res[$k]['dep2'] / $res[$k]['reg'] * 100) . '%';
            } else {
                $res[$k]['rate2'] = 0;
            }
            $uv += $res[$k]['uv'];
            $reg += $res[$k]['reg'];
            $down += $res[$k]['down'];
            $active += $res[$k]['active'];
            $dep2 += $res[$k]['dep2'];
        }

        // 排序
        if ($order) {
            if ($order_asc) {
                $order_asc = SORT_ASC;
            } else {
                $order_asc = SORT_DESC;
            }
            array_multisort(array_column($res, $order), $order_asc, SORT_NUMERIC, $res);
        }

        // 分頁
        $total = count($res);
        $res = array_slice($res, $offset, $page_size);

        //导出
        if ($exp == 2) {
            $header = [
                '合作商ID', '合作商名稱', '加入時間', 'UV', '註冊', '下載', '激活', '充值人數', '充值金額', 'UV-註冊', '註冊-充值', '結算方式'
            ];
            $path = export('合作商數據總覽', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }

        $sumary = ['uv' => $uv, 'reg' => $reg, 'down' => $down, 'active' => $active, 'dep2' => $dep2];
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }

    /**
     * 合作商每日統計
     */
    public function partner_day()
    {
        //接收参数
        $page_size = 10; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $id = intval($_POST['partner_id'] ?? 1);   //合作商id

        $exp = intval($_POST['exp'] ?? 1);
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select `name`, uptime from partner_$appid where id = $id";
        $res = $this->db->query($sql)->row_array();
        if (!$res) ajax_return(0, '參數錯誤');
        $day_start = date('Ymd', strtotime($res['uptime']));
        $day_end = date('Ymd');

        $lists = [];
        $pipe = $this->redis->pipeline();
        $pipe->zrange("pn:uv:$id", 0, -1, true);
        $pipe->zrange("pn:down:$id", 0, -1, true);
        $pipe->zrange("pn:reg:$id", 0, -1, true);
        $pipe->zrange("pn:active:$id", 0, -1, true);
        $pipe->zrange("pn:dep1:$id", 0, -1, true);
        $res1 = $pipe->exec();

        $total_active = 0;
        $total_dep = 0;
        for ($i = $day_start; $i <= $day_end; $i++) {
            if (0 != substr($i, 6, 2) && strtotime(substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2))) {
                $lists[$i]['date'] = substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2);
                $lists[$i]['id'] = $id;
                $lists[$i]['uv'] = intval(@$res1[0][$i]);
                $lists[$i]['reg'] = intval(@$res1[2][$i]);
                $lists[$i]['down'] = intval(@$res1[1][$i]);
                $lists[$i]['active'] = intval(@$res1[3][$i]);
                $total_active += @$res1[3][$i];
                $lists[$i]['dep1'] = intval(@$res1[4][$i]);
                $total_dep += @$res1[4][$i];
                $lists[$i]['dep2'] = $this->redis->scard("pn:dep2:$id:$i");
                $lists[$i]['dau'] = $this->redis->scard("pn:dau:$id:$i");
                if ($lists[$i]['uv']) {
                    $lists[$i]['rate1'] = intval($lists[$i]['reg'] / $lists[$i]['uv'] * 100) . '%';
                } else {
                    $lists[$i]['rate1'] = 0;
                }
                if ($lists[$i]['reg']) {
                    $lists[$i]['rate2'] = intval($lists[$i]['dep2'] / $lists[$i]['reg'] * 100) . '%';
                } else {
                    $lists[$i]['rate2'] = 0;
                }
            }
        }

        // 日活
        $dau = @$lists[$day_end]['dau'];
        $wau = 0;
        $mau = 0;
        if ($lists) {
            for ($i = $day_end; $i >= $day_start; $i--) {
                if (0 != substr($i, 6, 2) && strtotime(substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2))) {
                    $t = strtotime($lists[$i]['date'] . ' 00:00:00');
                    if (date('YW') == date('YW', $t)) {
                        $wau += $lists[$i]['dau'];
                    }
                    if (date('Ym') == date('Ym', $t)) {
                        $mau += $lists[$i]['dau'];
                    } else {
                        break;
                    }
                }
            }

        }

        // 分頁
        krsort($lists);
        $total = count($lists);
        $lists = array_slice($lists, $offset, $page_size);

        //导出
        /*
        if ($exp == 2) {
            $header = [
                '合作商ID', '合作商名稱', '加入時間', 'UV', '註冊', '下載', '激活', '充值人數', '充值金額', 'UV-註冊', '註冊-充值', '結算方式'
            ];
            $path = export('合作商數據總覽', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }*/
        $sumary = ['dau' => $dau, 'wau' => $wau, 'mau' => $mau, 'total_active' => $total_active, 'total_dep' => $total_dep];

        ajax_return(SUCCESS, '', ['list' => $lists, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }

    /**
     * 合作商用戶明細
     */
    public function partner_user()
    {
        //接收参数
        $page_size = 10; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $query_uid = intval($_POST['uid'] ?? 0);   //篩選用戶id
        $mobile = sql_format($_POST['mobile'] ?? '');
        $nickname = sql_format($_POST['nickname'] ?? '');
        $agent = intval($_POST['agent'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        $upper_uid = intval($_POST['upper'] ?? 0);
        $sql1 = '';
        if ($query_uid) {
            $sql1 .= " and id = $query_uid";
        }
        if ($mobile) {
            $sql1 .= " and mobile = $mobile";
        }
        if ($mobile) {
            $sql1 .= " and nickname = '$nickname'";
        }
        if (1 == $agent) {
            $sql1 .= " and is_agent = 1";
        } elseif (2 == $agent) {
            $sql1 .= " and is_agent = 0";
        }
        if (1 == $active) {
            $sql1 .= " and active = 1";
        } elseif (2 == $active) {
            $sql1 .= " and active = 0";
        }
        if ($upper_uid) {
            $sql1 .= " and upper_uid = $upper_uid";
        } else {
            $date = $_POST['date'] ?? date('Y-m-d');
            if (!strtotime($date . ' 00:00:00')) {
                ajax_return(0, '參數錯誤');
            }
            $sql1 .= " and join_date >= '$date 00:00:00' and join_date <= '$date 23:59:59'";
        }

        $id = intval($_POST['partner_id'] ?? 1);   //合作商id

        $exp = intval($_POST['exp'] ?? 1);
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, nickname, area_code, mobile, active, platform, join_date, active_time, '' last_visit, is_agent, join_agent, upper_uid, partner_id, deposit_total from user_{
                    $appid}_0 where partner_id = $id $sql1 order by id desc limit $offset, $page_size";
        $res = $this->db->query($sql)->result_array();
        $sql = "select count(id) c from user_{
                    $appid}_0 where partner_id = $id";
        $res1 = $this->db->query($sql)->row_array();
        $total = intval($res1['c']);

        foreach ($res as $k => $v) {
            $res[$k]['join_date'] = time_to_local_string($v['join_date']);
            $res[$k]['active_time'] = $v['active_time'] ? time_to_local_string($v['active_time']) : '';
            if ($v['active']) {
                $res[$k]['active'] = '已激活';
            } else {
                $res[$k]['active'] = '未激活';
            }
            if ($v['is_agent']) {
                $res[$k]['agent'] = '是';
            } else {
                $res[$k]['agent'] = '否';
            }
            if (4 == $v['platform'] || 1 == $v['platform']) {
                $res[$k]['platform'] = 'iOS';
            } elseif (5 == $v['platform'] || 2 == $v['platform']) {
                $res[$k]['platform'] = 'Android';
            } else {
                $res[$k]['platform'] = 'H5';
            }
            $last_visit = $this->redis->hget("user:" . $v['id'], 'heartbeat');
            $res[$k]['last_visit'] = $last_visit ? time_to_local_string($last_visit) : '';
        }

        //导出
        /*
        if ($exp == 2) {
            $header = [
                '合作商ID', '合作商名稱', '加入時間', 'UV', '註冊', '下載', '激活', '充值人數', '充值金額', 'UV-註冊', '註冊-充值', '結算方式'
            ];
            $path = export('合作商數據總覽', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }*/

        ajax_return(SUCCESS, '', ['list' => $res, 'total' => $total, 'page' => $page_no]);
    }

    /**
     * 結算記錄
     */
    public function partner_settle()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $partner_id = intval($_POST['partner_id'] ?? 0);   //合作商id
        $partner_name = sql_format($_POST['partner_name'] ?? '');

        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select s . id, s . partner_id, s . month, s . uv, s . reg, s . down, s . active, s . dep1, s . dep2, s . share_rate, s . share_money, s . uptime, s . is_pay, p . name from partner_settle_$appid s inner join partner_$appid p on s . partner_id = p . id where ";
        if ($partner_id) {
            $sql .= "s . partner_id = $partner_id and ";
        }
        if ($partner_name) {
            $sql .= "p . name like '$partner_name%' and ";
        }
        $sql .= "p . status = 1 order by s . id desc limit $offset, $page_size";
        $res = $this->db->query($sql)->result_array();

        foreach ($res as $k => $v) {
            $res[$k]['share_rate'] .= '%';
            $res[$k]['month'] = substr($v['month'], 0, 4) . '-' . substr($v['month'], 4, 2);
        }

        $sql = "select count(id) c from partner_settle_$appid";
        $res1 = $this->db->query($sql)->row_array();
        $total = $res1['c'];

        ajax_return(SUCCESS, '', ['list' => $res, 'total' => $total, 'page' => $page_no]);
    }
}
