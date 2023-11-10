<?php

/**
 * 銀商
 */
class Merchant extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 銀商列表
     */
    public function merchant_list()
    {
        $table = 'merchant_' . DATABASESUFFIX;
        $where = 'status = 1';

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
            $where .= " AND `mname` LIKE '%{$name}%'";
        }

        // 数据处理
        $sql = "SELECT id, mname, contact, phone, qq, wechat, balance, uptime FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
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
     * 创建銀商
     */
    public function merchant_add()
    {
        $table = 'merchant_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'mname' => 'must|char',
        );
        
        $msg = array(
            'mname' => '請填寫名稱',
        );
        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }
        
        $name = sql_format($data['mname']);
        $password = sql_format($data['password'] ?? '');
        $contact = sql_format($data['contact'] ?? '');
        $phone = sql_format($data['phone'] ?? '');
        $qq = sql_format($data['qq'] ?? '');
        $wechat = sql_format($data['wechat'] ?? '');
        $id = intval($data['id']);
        
        $sql = "select id from $table where mname = '$name' and id <> $id";
        $res = $this->db->query($sql)->row_array();
        if ($res) {
            ajax_return(0, '此銀商已存在');
        }
        
        if ($id) {
            $sql = "UPDATE {$table} SET mname = '$name', `contact` = '{$contact}', `phone` = '$phone', qq = '$qq', wechat = '$wechat' WHERE `id` = {$id}";
        } else {
            if (! $password) {
                ajax_return(0, '請填寫登錄密碼');
            }
            $sql = "INSERT INTO {$table} (mname, password, `contact`, `phone`, qq, wechat, `status`) VALUE('$name', '$password', '$contact', '$phone', '$qq', '$wechat', 1)";
        }
        $res = $this->db->query($sql)->affected_rows();
        if ($res) {
            ajax_return(1, '操作成功');
        } else {
            ajax_return(0, '操作失敗');
        }
    }
    
    /**
     * 删除銀商
     */
    public function merchant_del()
    {
        $table = 'merchant_' . DATABASESUFFIX;
        
        $id = input('post.id', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        
        $sql = "update {$table} set status = 0 WHERE id = {$id}";
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
     * 充值列表
     */
    public function merchant_wholesale()
    {
        $table = 'merchant_wholesale_' . DATABASESUFFIX;
        $where = "id > 0";
        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', '', 0));
        if ($id) {
            $where .= " AND `mid` = {$id}";
        }
        if ($name) {
            $where .= " AND `mname` LIKE '%{$name}%'";
        }
        
        // 数据处理
        $sql = "SELECT id, mid, mname, money, amount, balance, uptime FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as $k => $merchant) {
            $list[$k]['uptime'] = time_to_local_string($merchant['uptime']);
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
     * 添加充值
     */
    public function merchant_ws_add()
    {
        $table = 'merchant_' . DATABASESUFFIX;
        $table1 = 'merchant_wholesale_' . DATABASESUFFIX;
        
        $mid = intval($_POST['data']['mid'] ?? 0);
        $amount = intval($_POST['data']['amount'] ?? 0);
        $money = floatval($_POST['data']['money'] ?? 0);
        if (! $mid) {
            ajax_return(0, '銀商ID不正確');
        }
        if (! $amount) {
            ajax_return(0, '金幣數量不正確');
        }
 
        $sql = "select mname, balance from $table where id = $mid";
        $res = $this->db->query($sql)->row_array();
        if (! $res) {
            ajax_return(0, '銀商ID不正確');
        }
        $mname = sql_format($res['mname']);
        $balance = $res['balance'] + $amount;
        
        $admin_id = $this->user['id'];
        $admin_name = $this->user['username'];
        
        $sql = "INSERT INTO {$table1} (mid, mname, money, amount, balance, admin_id, admin_name) VALUE($mid, '$mname', $money, $amount, $balance, $admin_id, '$admin_name')";
        $res = $this->db->query($sql)->affected_rows();
        if ($res) {
            $sql = "update $table set balance = balance + $amount where id = $mid";
            $this->db->query($sql);
        } else {
            ajax_return(0, '操作失敗');
        }
        
        ajax_return(1, '操作成功');
    }
    
    /**
     * 充值列表
     */
    public function transfer()
    {
        $table = 'merchant_transfer_' . DATABASESUFFIX;
        $where = "id > 0";
        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', '', 0));
        if ($id) {
            $where .= " AND `mid` = {$id}";
        }
        if ($name) {
            $where .= " AND `mname` LIKE '%{$name}%'";
        }
        
        // 数据处理
        $sql = "SELECT id, mid, mname, uid, nickname, amount, user_balance, mer_balance, uptime FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as $k => $merchant) {
            $list[$k]['uptime'] = time_to_local_string($merchant['uptime']);
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
}
