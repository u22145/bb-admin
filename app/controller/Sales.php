<?php

/**
 * 推廣小組
 */
class Sales extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 小組列表
     */
    public function team_list()
    {
        $table = 'salesman_' . DATABASESUFFIX;
        $where = 'tid = 0';

        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', '', 0));
        if ($id) {
            $where .= " AND `id` = {$id}";
        }
        if ($name) {
            $where .= " AND `team` LIKE '%{$name}%'";
        }

        // 数据处理
        $sql = "SELECT id, uid, nickname, team, mem, uptime FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as $k => $v) {
            $list[$k]['uptime'] = time_to_local_string($v['uptime']);
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
     * 创建小組
     */
    public function team_add()
    {
        $table = 'salesman_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'name' => 'must|char',
            'uid' => 'must|int'
        );
        
        $msg = array(
            'name' => '請填寫名稱',
            'uid' => '請填寫組長ID'
        );
        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }
        
        $name = sql_format($data['name']);
        $uid = intval($data['uid']);
        $id = intval($data['id']);
        if (! $uid) {
            ajax_return(0, '請填寫組長ID');
        }
        // 檢查組長是否存在
        $nickname = $this->redis->hget("user:$uid", 'nickname');
        if (! $nickname) {
            ajax_return(0, '組長ID不存在');
        }
        $sql = "select id from $table where uid = $uid and id <> $id";
        $res = $this->db->query($sql)->row_array();
        if ($res) {
            ajax_return(0, '此組長ID已經加入小組了');
        }
        
        if ($id) {
            $sql = "UPDATE {$table} SET `team` = '{$name}', `uid` = $uid, nickname = '$nickname' WHERE `id` = {$id} and tid = 0";
        } else {
            $sql = "INSERT INTO {$table} (uid, nickname, `team`, `mem`, `status`) VALUE($uid, '$nickname', '$name', 0, 1)";
        }
        $res = $this->db->query($sql)->affected_rows();
        if ($res) {
            ajax_return(1, '操作成功');
        } else {
            ajax_return(0, '操作失敗');
        }
    }
    
    /**
     * 删除小組
     */
    public function team_del()
    {
        $table = 'salesman_' . DATABASESUFFIX;
        
        $id = input('post.id', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        
        $sql = "select id from $table where tid = $id limit 1";
        $res = $this->db->query($sql)->row_array();
        if ($res) {
            ajax_return(0, '小組内已有組員，不能刪除');
        }
        
        $sql = "DELETE FROM {$table} WHERE id = {$id} and tid = 0";
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
     * 組員列表
     */
    public function salesman_list()
    {
        $table = 'salesman_' . DATABASESUFFIX;
        $tid = intval($_POST['tid'] ?? 0);
        if (! $tid) {
            ajax_return(0, '參數錯誤');
        }
        $where = "tid = $tid";
        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', '', 0));
        if ($id) {
            $where .= " AND `uid` = {$id}";
        }
        if ($name) {
            $where .= " AND `nickname` LIKE '%{$name}%'";
        }
        
        // 数据处理
        $sql = "SELECT id, uid, nickname, tid, team, uptime FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as $k => $salesman) {
            $list[$k]['uptime'] = time_to_local_string($salesman['uptime']);
        }
        $count_sql = "SELECT COUNT(id) as num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->affected_rows();
        
        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int) $total,
            'id' => $tid
        ));
    }
    
    /**
     * 添加組員
     */
    public function sm_add()
    {
        $table = 'salesman_' . DATABASESUFFIX;
        
        $id = intval($_POST['data']['tid'] ?? 0);
        $uid = intval($_POST['data']['uid'] ?? 0);
        if (! $id) {
            ajax_return(0, '參數錯誤');
        }
        if (! $uid) {
            ajax_return(0, '請填寫組員ID');
        }
        // 檢查組員是否存在
        $nickname = $this->redis->hget("user:$uid", 'nickname');
        if (! $nickname) {
            ajax_return(0, '組員ID不存在');
        }
        $sql = "select id from $table where uid = $uid";
        $res = $this->db->query($sql)->row_array();
        if ($res) {
            ajax_return(0, '此組員ID已經加入另一小組了');
        }
        $sql = "select team from $table where id = $id and tid = 0";
        $res = $this->db->query($sql)->row_array();
        if (! $res) {
            ajax_return(0, '參數錯誤');
        }
        $team = $res['team'];
        
        $sql = "INSERT INTO {$table} (uid, nickname, tid, `team`, `status`) VALUE($uid, '$nickname', $id, '$team', 1)";
        $res = $this->db->query($sql)->affected_rows();
        if ($res) {
            $sql = "update $table set mem = mem + 1 where id = $id";
            $this->db->query($sql);
        }
        
        ajax_return(1, '操作成功');
    }
    
    /**
     * 删除組員
     */
    public function sm_del()
    {
        $table = 'salesman_' . DATABASESUFFIX;
        
        $id = input('post.id', 'intval', 0);
        $uid = input('post.uid', 'intval', 0);
        if (! $id || ! $uid) ajax_return(ERROR, get_tips(1006));
        
        $sql = "DELETE FROM $table WHERE tid = $id and uid = $uid";
        $res = $this->db->query($sql)->affected_rows();
        if ($res) {
            $sql = "update $table set mem = mem - 1 where id = $id";
            $this->db->query($sql);
        }
        
        ajax_return(1, '操作成功');
    }

    /**
     * 小組数据总计
     */
    public function sales_statis()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $tid = intval($_POST['tid'] ?? 0);   //小組id
        $team = sql_format($_POST['team'] ?? '');
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';

        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, `team`, mem, uptime, 0 dep2, 0 dep1 from salesman_$appid where ";
        if ($tid) {
            $sql .= "id = $tid and ";
        }
        if ($team) {
            $sql .= "team like '$team%' and ";
        }
        $sql .= "tid = 0 order by id desc";
        $res = $this->db->query($sql)->result_array();

        if ($day_start && $day_end) {
            $day_start = date('Ymd', strtotime($day_start));
            $day_end = date('Ymd', strtotime($day_end));
        } else {
            $day_start = '20200301';
            $day_end = date('Ymd');
        }

        $dep1 = 0;
        $dep2 = 0;
        foreach ($res as $k => $v) {
            $res[$k]['uptime'] = time_to_local_string($v['uptime']);
            $id = $v['id'];
            $pipe = $this->redis->pipeline();
            $pipe->zrange("st:dep1:$id", 0, -1, true);
            $res1 = $pipe->exec();
            $res[$k]['uv'] = 0;
            $pipe = $this->redis->pipeline();
            for ($i = $day_start; $i <= $day_end; $i++) {
                if (0 != substr($i, 6, 2) && strtotime(substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2))) {
                    $res[$k]['dep1'] += @$res1[0][$i];
                    $pipe->scard("st:dep2:$id:$i");
                }
            }
            $res1 = $pipe->exec();
            $res[$k]['dep2'] = array_sum($res1);
            $dep2 += $res[$k]['dep2'];
            $dep1 += $res[$k]['dep1'];
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

        $sumary = ['dep1' => $dep1, 'dep2' => $dep2];
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }
    
    /**
     * 組員数据列表
     */
    public function sales_statis_sm()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;
        
        $tid = intval($_POST['tid'] ?? 0);   //小組id
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';
        
        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select uid id, nickname, uptime, 0 reg, 0 active, 0 dep2, 0 dep1 from salesman_$appid where (tid = $tid or id = $tid and tid = 0) order by id desc";
        $res = $this->db->query($sql)->result_array();
        
        if ($day_start && $day_end) {
            $day_start = date('Ymd', strtotime($day_start));
            $day_end = date('Ymd', strtotime($day_end));
        } else {
            $day_start = '20200301';
            $day_end = date('Ymd');
        }
        
        $reg = 0;
        $active = 0;
        $dep2 = 0;
        $dep1 = 0;
        foreach ($res as $k => $v) {
            $res[$k]['uptime'] = time_to_local_string($v['uptime']);
            $id = $v['id'];
            $pipe = $this->redis->pipeline();
            $pipe->zrange("sm:reg:$id", 0, -1, true);
            $pipe->zrange("sm:active:$id", 0, -1, true);
            $pipe->zrange("sm:dep1:$id", 0, -1, true);
            $res1 = $pipe->exec();
            $res[$k]['uv'] = 0;
            $pipe = $this->redis->pipeline();
            for ($i = $day_start; $i <= $day_end; $i++) {
                if (0 != substr($i, 6, 2) && strtotime(substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2))) {
                    $res[$k]['reg'] += @$res1[0][$i];
                    $res[$k]['active'] += @$res1[1][$i];
                    $res[$k]['dep1'] += @$res1[2][$i];
                    $pipe->scard("sm:dep2:$id:$i");
                }
            }
            $res1 = $pipe->exec();
            $res[$k]['dep2'] = array_sum($res1);
            $reg += $res[$k]['reg'];
            $active += $res[$k]['active'];
            $dep2 += $res[$k]['dep2'];
            $dep1 += $res[$k]['dep1'];
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
        
        $sumary = ['reg' => $reg, 'active' => $active, 'dep2' => $dep2, 'dep1' => $dep1];
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }
    
    /**
     * 注冊用戶列表
     */
    public function sales_statis_user()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;
        
        $id = intval($_POST['id'] ?? 0);   //組員id
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';
        if (! $id) {
            ajax_return(0, '參數錯誤');
        }
        
        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, nickname, join_date, active, 0 deposit_total, 0 balance from user_{$appid}_0 where sm_uid = $id";
        if ($day_start && $day_end) {
            $day_start = date('Y-m-d 00:00:00', strtotime($day_start));
            $day_end = date('Y-m-d 23:59:59', strtotime($day_end));
            $sql .= " and join_date >= '$day_start' and join_date <= '$day_end'";
        }
        $sql .= " order by id desc";
        $res = $this->db->query($sql)->result_array();
        
        foreach ($res as $k => $v) {
            $uid = $v['id'];
            $res[$k]['join_date'] = time_to_local_string($v['join_date']);
            if ($v['active']) {
                $res[$k]['active'] = '是';
            } else {
                $res[$k]['active'] = '否';
            }
            $res1 = $this->redis->hmget("user:$uid", ['eurc_balance', 'deposit_total']);
            $res[$k]['deposit_total'] = floatval($res1['deposit_total']);
            $res[$k]['balance'] = floatval($res1['eurc_balance']);
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
        
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => [], 'total' => $total, 'page' => $page_no]);
    }
    
    /**
     * 充值明細
     */
    public function sales_statis_pay()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;
        
        $id = intval($_POST['id'] ?? 0);   //用戶id
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';
        if (! $id) {
            ajax_return(0, '參數錯誤');
        }
        
        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, order_id, money, order_time, status from pay_deposit_$appid where uid = $id";
        if ($day_start && $day_end) {
            $day_start = date('Y-m-d 00:00:00', strtotime($day_start));
            $day_end = date('Y-m-d 23:59:59', strtotime($day_end));
            $sql .= " and order_time >= '$day_start' and order_time <= '$day_end'";
        }
        $sql .= " order by id desc";
        $res = $this->db->query($sql)->result_array();
        
        foreach ($res as $k => $v) {
            $res[$k]['order_time'] = time_to_local_string($v['order_time']);
            if ($v['status'] == 0) {
                $res[$k]['status'] = '未付款';
            } elseif ($v['status'] == 1) {
                $res[$k]['status'] = '支付成功';
            } else {
                $res[$k]['status'] = '支付失敗';
            }
            $res[$k]['money'] = floatval($v['money']);
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
        
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => [], 'total' => $total, 'page' => $page_no]);
    }
    
    /**
     * 消費明細
     */
    public function sales_statis_expend()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;
        
        $id = intval($_POST['id'] ?? 0);   //用戶id
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';
        if (! $id) {
            ajax_return(0, '參數錯誤');
        }
        
        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, cate, fee, call_id, uptime from exp_detail_$appid where uid = $id";
        if ($day_start && $day_end) {
            $day_start = date('Y-m-d 00:00:00', strtotime($day_start));
            $day_end = date('Y-m-d 23:59:59', strtotime($day_end));
            $sql .= " and uptime >= '$day_start' and uptime <= '$day_end'";
        }
        $sql .= " order by id desc";
        $res = $this->db->query($sql)->result_array();
        
        foreach ($res as $k => $v) {
            $res[$k]['uptime'] = time_to_local_string($v['uptime']);
            if ($v['cate'] == 'call') {
                $res[$k]['cate'] = '一對一';
            } elseif ($v['cate'] == 'pm') {
                $res[$k]['cate'] = '私信';
            } elseif ($v['cate'] == 'gift') {
                $res[$k]['cate'] = '打賞';
            }
            $res[$k]['fee'] = abs(floatval($v['fee']));
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
        
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => [], 'total' => $total, 'page' => $page_no]);
    }
}
