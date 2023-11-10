<?php

class  Trade extends Controller
{

    const STATUS_DEAL = 2;
    const STATUS_CANCEL = 4;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *  金币充值订单
     */
    public function trade_deposit()
    {
        // 处理参数
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $search_type = $_POST['search_type'] ?? 'all';
        $search_info = $_POST['search_info'] ?? '';
        $order_status = $_POST['order_status'] ?? 'all';
        $start_time = $_POST['start_time'] ?? '';
        $end_time   = $_POST['end_time'] ?? '';
        $channel    = $_POST["channel"]?? '';
        $merchant   = $_POST["merchant"]?? '';
        $type       = $_POST['type'] ?? '';
        $search     = intval($_POST['search'] ?? 0);
        $appid      = DATABASESUFFIX;

        // 处理条件
        $where = '';
        if ($search_type != 'all' && $search_info != '' && $search_type != '') {
            switch ($search_type) {
                case 'uid':
                    $where .= " and pd.uid = '{$search_info}'";
                    break;
                case 'number':
                    $where .= " and pd.username = '{$search_info}'";
                    break;
                case 'order':
                    $where .= " and pd.order_id = '{$search_info}'";
                    break;
            }
        }

        if ($order_status != 'all' && $order_status != '') {
            switch ($order_status) {
                case 'success':
                    $where .= " and pd.status = 1";
                    break;
                case 'error':
                    $where .= " and pd.status = 2";
                    break;
                case 'doing':
                    $where .= " and pd.status = 0";
                    break;
            }
        }

        if ($start_time) {
            $where .= " and DATE_ADD(pd.order_time, INTERVAL 8 HOUR) >= '{$start_time}' ";
        }
        if($end_time){
            $where.=" and DATE_ADD(pd.order_time, INTERVAL 8 HOUR) < '{$end_time}' ";
        }
        $where .= !empty($channel) ? " and pd.payment_config_id = '{$channel}'" : '';
        $where .= empty($merchant)  ?  '' : " and pc.third_party_name = '{$merchant}'";

        $where .= !empty($type) ? " AND pc.type='{$type}' " : '';

        $where .= ' AND pd.payment_type =1 ';

        // 用戶充值
        // 用戶充值
        // $dt = date('Ymd');
        // // $user_day_dps = intval($this->redis->scard("stat:dep2:$dt"));          // 每天用戶充值人數
        // $user_day_sql = "SELECT count(id) AS num, sum(money) AS total FROM pay_deposit_1 WHERE status = 1 and DATE(pay_time) = " . date('Y-m-d', time()+28800);
        // $user_day_res = $this->db->query($user_day_sql)->row_array();            // DAY的充值用戶
        // $user_day_dps = number_format($user_day_res['num'], 0);
        // $user_day_money = number_format($user_day_res['total'], 0);

        // $sql = "SELECT count(id) AS num, sum(money) AS total FROM pay_deposit_1 WHERE status = 1";
        // $res = $this->db->query($sql)->row_array();            // 總的充值用戶
        // $user_all_dps = number_format($res['num'], 0);
        // $user_all_money = number_format($res['total'], 0);

        // 获取数据
        // $sql = "SELECT pd.id, pd.order_id, pd.amount, pd.status, pd.money, pd.order_time, pd.pay_type,
        //                 pd.pay_time, pd.transact_id, pd.uid, user.username
        //             FROM pay_deposit_" . DATABASESUFFIX . " pd
        //             LEFT JOIN user_1_0 user
        //             ON pd.uid = user.id
        //             where 1 $where
        //             ORDER BY pd.id DESC limit {$page_size} offset $limit";
        $sql = "SELECT pd.*, pc.channel_name, pc.type, pc.callback_route, pc.third_party_name
                    FROM pay_deposit_$appid pd
                    LEFT JOIN payment_config pc
                    ON pd.payment_config_id=pc.id
                    WHERE 1 $where 
                    ORDER BY pd.id DESC limit {$page_size} offset $limit";
        $pay_deposit = $this->db->query($sql)->result_array();

        $sqlStats = "SELECT COUNT(pd.id) as total_order, COUNT(IF(pd.status=1, pd.id, NULL)) AS total_paid, SUM(IF(pd.status=1, pd.money, 0)) as total_money
                    FROM pay_deposit_$appid pd
                    LEFT JOIN payment_config pc
                    ON pd.payment_config_id=pc.id
                    WHERE 1 $where";
        $stats_deposit = $this->db->query($sqlStats)->row_array();

        $total_order = $stats_deposit['total_order'];
        $total_paid = $stats_deposit['total_paid'];
        $total_money = $stats_deposit['total_money'] ?? 0;
        $total_rate = $total_order > 0 ? number_format( ($total_paid * 100) / $total_order , 2, '.', '') : 0;

        foreach ($pay_deposit as &$order) {
            $order['idname'] = $order['uid'] .'/' . $order['username'];
            $order['username'] = $order['username'] ?: '未填写';
            $order['money']    = intval($order['money']);
            $order['order_time'] = time_to_local_string($order['order_time']);
            $order['callback_route'] = trim($order['callback_route']);
            // $order['surplus'] = $this->redis->hget('user:'. $order['uid'], 'eurc_balance') ?: 0;
            switch ( strtolower($order['type']) ) {
                case 'alipay':
                    $order['type'] = '支付宝';
                    break;
                case 'wechat':
                    $order['type'] = '微信';
                    break;
                case 'bankcard':
                    $order['type'] = '网银';
                    break;
                case 'credit':
                    $order['type'] = '话费';
                    break;
                default:
                    $order['type'] = '支付宝';
                    break;
            }
        }


        // 统计数量
        // $count_sql = "select count(pd.id) as num from pay_deposit_" . DATABASESUFFIX . " pd where 1 $where";
        // $total = $this->db->query($count_sql)->row_array()['num'];
        $total = $total_order;

        // $sqlTotal="select sum(money) as totalCharge,pay_channel from pay_deposit_".DATABASESUFFIX." where isnull(pay_channel)=0 and status=1 ";
        // if($start_time){
        //     $sqlTotal.=" and order_time >= '{$start_time}' ";
        // }
        // if($end_time){
        //     $sqlTotal.=" and order_time < '{$start_time}' ";
        // }
        // if($channel){
        //     $sqlTotal.=" and  pay_channel = '{$channel}' ";
        // }
        // $sqlTotal.=" group by pay_channel";
        // $totalCharge=$this->db->query($sqlTotal)->result_array();
        // $sqlToday="select sum(money) as todayCharge,pay_channel from pay_deposit_".DATABASESUFFIX." where isnull(pay_channel)=0 and status=1 and DATE_FORMAT(FROM_UNIXTIME(order_time),'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d') group by pay_channel";
        // $todayCharge=$this->db->query($sqlToday)->result_array();

        // foreach ($totalCharge as &$item) {
        //     foreach($todayCharge as $it){
        //         if($item["pay_channel"]==$it["pay_channel"]){
        //             $item["todayCharge"]=$it["todayCharge"];
        //         }
        //     }
        // }

        // foreach ($totalCharge as &$item){
        //     if(!isset($item["todayCharge"])){
        //         $item["todayCharge"]=0;
        //     }
        // }
        // 获取渠道列表
        $channels = $this->db->query("select id, channel_name from payment_config order by seq_id DESC")->result_array();

        // 获取渠道商列表
        $merchants = $this->db->query("select DISTINCT third_party_name from payment_config  order by seq_id DESC")->result_array();

        // if $search = 1, export data to excel
        if (!empty($search) && 1 === $search) {
            $header = ['序号', '用户ID', '充值金额', '渠道名称', '转账类型', '交易ID', '四方订单号', '创建时间', '支付时间', '状态'];

            // set defualt time period to export
            if ( empty($start_time) && empty($end_time) ) {
                // 获取数据
                $where.=" AND pd.order_time > DATE_ADD(NOW(), INTERVAL -1 MONTH) ";
            }

            $sql = "SELECT pd.uid, pd.money, pd.order_id, pd.transact_id, pd.order_time, pd.pay_time, pc.channel_name, pc.type, pd.status, pd.payment_config_id
                    FROM pay_deposit_$appid pd
                    LEFT JOIN payment_config pc
                    ON pd.payment_config_id=pc.id
                    WHERE 1 $where 
                    ORDER BY pd.order_time DESC, pd.id DESC";
// @file_put_contents(ERRLOG_PATH . '/testing_log_' . date("Ymd") . '.log',
//                     date("Y-m-d H:i:s") . "sql excuted: $sql \n",
//                     FILE_APPEND);
            $pay_deposit = $this->db->query($sql)->result_array();

            $new_data = [];
            $index = 1;
            foreach ($pay_deposit as $k => $v) {
                $new_data[$k]['index']      = $index;
                $new_data[$k]['uid']        = $v['uid'];
                $new_data[$k]['money']      = $v['money'];
                $new_data[$k]['channel_name'] = $v['channel_name'];
                $new_data[$k]['type']       = $v['type'];
                $new_data[$k]['order_id']   = $v['order_id'];
                $new_data[$k]['transact_id']   = $v['transact_id'];
                $new_data[$k]['order_time'] = $v['order_time'] ? time_to_local_string($v['order_time']) : '';
                $new_data[$k]['pay_time']   = $v['pay_time'] ? time_to_local_string($v['pay_time']) : '';

                switch (intval($v['status'])) {
                    case 0:
                        $new_data[$k]['status']     = '未支付';
                        break;
                    case 1:
                        $new_data[$k]['status']     = '成功';
                        break;
                    case 2:
                        $new_data[$k]['status']     = '支付失败';
                        break;
                    default:
                        $new_data[$k]['status']     = '未支付';
                        break;
                }

                $index++;
            }

            $path = export('充值订单_' . time()  , $header, $new_data);

            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        // 返回
        ajax_return(SUCCESS, '', array(
            'data' => $pay_deposit,
            // 'sumary' => ['user_day_dps' => $user_day_dps, 'user_all_dps' => $user_all_dps, 'user_day_money' => $user_day_money, 'user_all_money' => $user_all_money, 'total_order' => $total_order, 'total_paid' => $total_paid,'total_money' => $total_money, 'total_rate' => $total_rate],
            'sumary' => ['total_order' => $total_order, 'total_paid' => $total_paid,'total_money' => $total_money, 'total_rate' => $total_rate],
            'total' => intval($total),
            'page' => $page,
            // 'totalCharge'=>$totalCharge,
            'page_size' => $page_size,
            'channels' => $channels,
            'merchants' => $merchants,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     *  金币提币订单
     */
    public function trade_withdraw()
    {
        // 处理参数
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $search_type = $_POST['search_type'] ?? 'all';
        $search_info = $_POST['search_info'] ?? '';
        $order_status = $_POST['order_status'] ?? 'all';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        // 处理条件
        $where = '';
        if ($search_type != 'all' && $search_info != '' && $search_type != '') {
            switch ($search_type) {
                case 'uid':
                    $where .= " and uid = '{$search_info}'";
                    break;
                case 'number':
                    $where .= " and username = '{$search_info}'";
                    break;
            }
        }

        if ($order_status != 'all' && $order_status != '') {
            switch ($order_status) {
                case 'success':
                    $where .= " and status = 1";
                    break;
                case 'error':
                    $where .= " and status = 2";
                    break;
                case 'doing':
                    $where .= " and status = 0";
                    break;
            }
        }

        if ($start_time && $end_time) {
            $where .= " and uptime >= '{$start_time}' and uptime <= '{$end_time}'";
        }

        // 获取数据
        $sql = "select * from trade_withdraw_" . DATABASESUFFIX . " where 1 $where ORDER BY id DESC limit {$page_size} offset $limit";
        $trade_withdraw = $this->db->query($sql)->result_array();

        // 獲取日和總的數據
        $dates = date('Y-m-d');
        $sql = "select count(id) as num, sum(amount) total_amount, sum(fee) total_fee from trade_withdraw_" . DATABASESUFFIX . " where uptime >= '$dates 00:00:00' and uptime <= '$dates 23:59:59'";
        $res = $this->db->query($sql)->row_array();
        $user_day = $res['num'] ?: 0;                    // 每日提現的總人數
        $user_day_amount = $res['total_amount'] ?: 0;    // 每日提現的總數量
        $user_day_fee = $res['total_fee'] ?: 0;  // 每日提現的總金額

        // 统计数量
        $count_sql = "select count(id) as num, sum(amount) total_amount, sum(fee) total_fee from trade_withdraw_" . DATABASESUFFIX . " where 1 $where";
        $res = $this->db->query($count_sql)->row_array();
        $total = $res['num'] ?: 0;
        $user_all_amount = $res['total_amount'] ?: 0;
        $user_all_fee = $res['total_fee'] ?: 0;

        // 返回
        ajax_return(SUCCESS, '', array(
            'data' => $trade_withdraw,
            'total' => intval($total),
            'sumary' => ['user_day' => $user_day, 'user_day_amount' => $user_day_amount, 'user_day_fee' => $user_day_fee, 'user_day_all' => $total, 'user_all_amount' => $user_all_amount, 'user_all_fee' => $user_all_fee],
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     *  金币内部转账订单
     */
    public function internal_transfer_log()
    {
        // 处理参数
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $search_type = $_POST['search_type'] ?? 'all';
        $search_info = $_POST['search_info'] ?? '';
        $order_status = $_POST['order_status'] ?? 'all';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        // 处理条件
        $where = '';
        if ($search_type != 'all' && $search_info != '' && $search_type != '') {
            switch ($search_type) {
                case 'uid':
                    $where .= " and uid = '{$search_info}'";
                    break;
                case 'number':
                    $where .= " and username = '{$search_info}'";
                    break;
            }
        }

        if ($order_status != 'all' && $order_status != '') {
            switch ($order_status) {
                case 'success':
                    $where .= " and status = 1";
                    break;
                case 'error':
                    $where .= " and status = 2";
                    break;
                case 'doing':
                    $where .= " and status = 0";
                    break;
            }
        }

        if ($start_time && $end_time) {
            $where .= " and uptime >= '{$start_time}' and uptime <= '{$end_time}'";
        }

        // 获取数据
        $sql = "select * from trade_withdraw_" . DATABASESUFFIX . " where 1 $where ORDER BY id DESC limit {$page_size} offset $limit";
        $trade_withdraw = $this->db->query($sql)->result_array();

        // 獲取日和總的數據
        $dates = date('Y-m-d');
        $sql = "select count(id) as num, sum(amount) total_amount, sum(fee) total_fee from trade_withdraw_" . DATABASESUFFIX . " where uptime >= '$dates 00:00:00' and uptime <= '$dates 23:59:59'";
        $res = $this->db->query($sql)->row_array();
        $user_day = $res['num'] ?: 0;                    // 每日提現的總人數
        $user_day_amount = $res['total_amount'] ?: 0;    // 每日提現的總數量
        $user_day_fee = $res['total_fee'] ?: 0;  // 每日提現的總金額

        // 统计数量
        $count_sql = "select count(id) as num, sum(amount) total_amount, sum(fee) total_fee from trade_withdraw_" . DATABASESUFFIX . " where 1 $where";
        $res = $this->db->query($count_sql)->row_array();
        $total = $res['num'] ?: 0;
        $user_all_amount = $res['total_amount'] ?: 0;
        $user_all_fee = $res['total_fee'] ?: 0;

        // 返回
        ajax_return(SUCCESS, '', array(
            'data' => $trade_withdraw,
            'total' => intval($total),
            'sumary' => ['user_day' => $user_day, 'user_day_amount' => $user_day_amount, 'user_day_fee' => $user_day_fee, 'user_day_all' => $total, 'user_all_amount' => $user_all_amount, 'user_all_fee' => $user_all_fee],
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     *   獲取購買baby記錄
     */
    public function get_baby_list()
    {
        $content = sql_format($_POST['content'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);

        // 拼接sql 條件
        $where = '1';
        if ($content) {
            $where .= is_numeric($content) ? " and t.uid = $content" : " and u.nickname like '%$content%'";
        }

        $sql = "select t.id, u.nickname, t.uid, t.money, t.amount, t.uptime, t.price, t.status, t.balance from trade_buy_bicc_1 t left join user_1_0 u on t.uid = u.id where $where order by t.id desc limit {$page_size} offset $limit";
        $res = $this->db->query($sql)->result_array();
        @file_put_contents(ERRLOG_PATH . '/recharge_data_' . date("Ymd") . '.log',
            date("Y-m-d H:i:s") . $sql . json_encode($_POST, true) . " \n",
            FILE_APPEND);
        foreach ($res as &$value) {
            // $value['eurc_balance'] = $this->redis->hget('user:'.$value['uid'], 'eurc_balance');
            // $value['eurc_balance'] = $value['eurc_balance'] ? number_format($value['eurc_balance'], MONEY_DECIMAL_DIGITS, ".", "") : 0;
            $value['eurc_balance'] = floatval($value['balance']) ? number_format($value['balance'], MONEY_DECIMAL_DIGITS, ".", "") : 0;
            $value['amount'] = floatval($value['amount']) ? number_format($value['amount'], MONEY_DECIMAL_DIGITS, ".", "") : 0;
            $value['money'] = floatval($value['money']);
            $value['uptime'] = time_to_local_string($value['uptime']);
            switch ($value['status']) {
                case '0':
                    $value['status_txt'] = '待處理';
                    break;
                case '1':
                    $value['status_txt'] = '成功';
                    break;
                case '2':
                    $value['status_txt'] = '失敗';
                    break;
            }
        }
        $count_sql = "select count(id) as num from trade_buy_bicc_1";
        // $total = $this->db->query($count_sql)->row_array()['num'];
        $total = isset($_POST['content']) && !empty($_POST['content']) ? count($res) : $this->db->query($count_sql)->row_array()['num'];

        $dt = date('Y-m-d');
        $sql = "select count(id) as num, sum(amount) as total_amount from trade_buy_bicc_1 where status = 1 and uptime >= '$dt 00:00:00' and uptime <= '$dt 23:59:59'";
        $total_res = $this->db->query($sql)->row_array();
        $user_day_buy = intval($total_res['num']);
        $user_day_amount = floatval($total_res['total_amount']) ? number_format($total_res['total_amount'], MONEY_DECIMAL_DIGITS, ".", "") : 0;

        $sql = "select count(id) as num, sum(amount) as total_amount from trade_buy_bicc_1 where status = 1";
        $total_res = $this->db->query($sql)->row_array();
        $user_all_buy = intval($total_res['num']);
        $user_all_amount = $total_res['total_amount'] ? number_format($total_res['total_amount'], MONEY_DECIMAL_DIGITS, ".", "") : 0;    // 購買幣的總數量


        ajax_return(SUCCESS, '', [
            'list' => $res,
            'data' => ['user_day_buy' => $user_day_buy, 'user_all_buy' => $user_all_buy, 'user_day_amount' => $user_day_amount, 'user_all_amount' => $user_all_amount],
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ]);

    }

    /**
     *   獲取充幣記錄
     */
    public function get_deposit_baby()
    {
        $content = sql_format($_POST['content'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);

        // 拼接sql 條件
        $where = '1';
        if ($content) {
            $where .= is_numeric($content) ? " and t.uid = $content" : " and u.nickname like '%$content%'";
        }

        $sql = "select t.uid, u.nickname, t.amount, t.balance, t.status, t.uptime, t.from_addr from trade_deposit_1 t left join user_1_0 u on u.id = t.uid where $where order by t.id desc limit {$page_size} offset $limit";
        $res = $this->db->query($sql)->result_array();
        foreach ($res as &$value) {
            $value['amount'] = floatval($value['amount']) ? number_format($value['amount'], MONEY_DECIMAL_DIGITS, ".", "") : 0;
            $value['balance'] = floatval($value['balance']);
            $value['uptime'] = time_to_local_string($value['uptime']);
            switch ($value['status']) {
                case '0':
                    $value['status_txt'] = '處理中';
                    break;
                case '1':
                    $value['status_txt'] = '成功';
                    break;
                case '2':
                    $value['status_txt'] = '失敗';
                    break;
            }
        }
        @file_put_contents(ERRLOG_PATH . '/recharge_data_' . date("Ymd") . '.log',
            date("Y-m-d H:i:s") . json_encode($res, true) . " \n",
            FILE_APPEND);
        $count_sql = "select count(id) as num from trade_deposit_1";
        $total = $this->db->query($count_sql)->row_array()['num'];

        // $total = count($res);

        // 充幣的人數 日總
        $dt = date('Y-m-d');
        $sql = "select count(id) as num, sum(amount) as day_amount from trade_deposit_1 where status = 1 and uptime >= '$dt 00:00:00' and uptime <= '$dt 23:59:59'";
        $day_res = $this->db->query($sql)->row_array();
        $user_day_deposit = intval($day_res['num']);                       // 充幣的每日人數
        $user_day_amount = floatval($day_res['day_amount']) ? number_format($day_res['day_amount'], MONEY_DECIMAL_DIGITS, ".", "") : 0;       // 每日充值幣的個數

        // 充幣數量
        $sql = "select count(id) as num, sum(amount) as all_amount from trade_deposit_1 where status = 1";
        $day_res = $this->db->query($sql)->row_array();
        $user_all_deposit = intval($day_res['num']);                       // 充幣的總人數
        $user_all_amount = floatval($day_res['all_amount']) ? number_format($day_res['all_amount'], MONEY_DECIMAL_DIGITS, ".", "") : 0;      // 充幣的總個數

        ajax_return(SUCCESS, '', [
            'list' => $res,
            'data' => ['user_day_deposit' => $user_day_deposit, 'user_all_deposit' => $user_all_deposit, 'user_day_amount' => $user_day_amount, 'user_all_amount' => $user_all_amount],
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ]);
    }

    /**
     *  配置支付通道列表
     *
     *  
     */
    public function payment_channel_config()
    {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);

        $third_party_name = str_format($_POST['third_party_name'] ?? '');
        $type = str_format($_POST['type'] ?? '');
        $switch = str_format($_POST['s_switch'] ?? '');

        // 日志记录操作员的IP地址，未必准确
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $where = '1';
        $where .= ('' === $third_party_name ) ? '' : " AND third_party_name like '%{$third_party_name}%'";
        $where .= ('' === $type ) ? '' : " AND type='{$type}'";
        $where .= ('' === $switch ) ? '' : " AND switch='{$switch}'";
        $where .= ' AND is_deleted=0 ';

        $sql    = "SELECT * FROM payment_config
                        WHERE $where
                        ORDER BY seq_id DESC, id DESC LIMIT {$page_size} OFFSET {$limit}";

        $config = $this->db->query($sql)->result_array();
        foreach ($config as $key => &$value) {
            $value['setting'] = $value['custom_amount_min'] . ' - ' . $value['custom_amount_max'];
            $value['c_switch'] = $value['switch'];
            $value['status_switch'] = $value['switch'];
            $sql = "SELECT `opt_money`, `position` FROM payment_money_config WHERE payment_config_id={$value['id']}";
            $opt_moneys = $this->db->query($sql)->result_array();
            foreach ($opt_moneys as $money) {
                $key = $money['position'];
                $value['opt_money'.$key] = $money['opt_money'];
            }
            switch (strtolower($value['type'])) {
                case 'alipay':
                    $value['label_type'] = '支付宝';
                    break;
                case 'wechat':
                    $value['label_type'] = '微信';
                    break;
                case 'bankcard':
                    $value['label_type'] = '网银';
                    break;
                case 'credit':
                    $value['label_type'] = '话费';
                    break;
                default:
                    $value['label_type'] = '支付宝';
                    break;
            }
        }
        $total  = ($page - 1) * $page_size + count($config);

        ajax_return(SUCCESS, '', [
            'list' => $config,
            'total' => $total,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ]);

    }

    /**
     *  创建支付通道
     *
     *  
     */
    public function create_payment_channel()
    {
        // 日志记录操作员的IP地址，未必准确
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $seq_id     = intval($_POST['seq_id'] ?? 0);          // 顺序号
        $third_party_name = str_format($_POST['third_party_name'] ?? '');      // 渠道商名称
        $route_name     = str_format($_POST['route_name'] ?? '');              // function名称
        $channel_name   = str_format($_POST['channel_name'] ?? '');            // 前端显示名称
        $channel_code   = str_format($_POST['channel_code'] ?? '');            // 前端显示名称的代号，渠道商会提供如果有的话
        $pay_name       = str_format($_POST['pay_name'] ?? '');              // admin后端显示的名称,沿用之前的
        $type           = str_format($_POST['type'] ?? 'ALIPAY');                   // 支付方式: 'WECHAT', 'ALIPAY', 'BANKCARD', 'CREDIT'
        $custom_amount_switch = str_format($_POST['custom_amount_switch'] ?? 'OFF');
        $custom_amount_switch = !empty($custom_amount_switch) ? $custom_amount_switch : 'OFF';
        $custom_amount_min = intval($_POST['custom_amount_min'] ?? 0);                   // 最小值
        $custom_amount_max = intval($_POST['custom_amount_max'] ?? 1);                   // 最大值
        $min            = intval($_POST['min'] ?? 0);                   // 最小值
        $max            = intval($_POST['max'] ?? 0);                   // 最小值
        $limit          = intval($_POST['limit'] ?? 0);                   // 停用金额, 0表示不启用
        $is_recommend   = str_format($_POST['is_recommend'] ?? 'OFF');                   // 前端是否显示推荐
        $switch         = str_format($_POST['status_switch'] ?? 'OFF');                   // 开启/关闭，
        $adapt_ios         = str_format($_POST['adapt_ios'] ?? 'ON');                   // 适用android开启/关闭，
        $adapt_android         = str_format($_POST['adapt_android'] ?? 'ON');                   // 适用ios开启/关闭，
        $adapt_web         = str_format($_POST['adapt_web'] ?? 'ON');                   // 适用小网开启/关闭，
        $pay_type       = intval($_POST['pay_type'] ?? 0);                   // 支付类型，匹配原来的type，
        $history_recharge_limit   = intval($_POST['history_recharge_limit'] ?? 0);                   // 支付类型，匹配原来的type，
        $app_hints       = text_format($_POST['app_hints'] ?? '');                   // 支付类型，匹配原来的type，
        $callback_whitelist       = str_format($_POST['callback_whitelist'] ?? '');                   // 支付类型，匹配原来的type，
        $callback_whitelist       = str_replace(' ', '', $callback_whitelist);
        $callback_whitelist       = str_replace('；', ';', $callback_whitelist);
        $callback_route      = text_format($_POST['callback_route'] ?? '');  

        $moneys         = [];
        $moneysTmp      = [];
        for($i = 0; $i<9; $i++) {
            $money = intval($_POST['opt_money'.$i] ?? 0);
            if( (0 !== $money) && ($money > $max || $money < $min) )
                ajax_return(ERROR, '最小金额或最大金额超出设置范围', []);
            if($money > 0) {
                $moneys[$i] = number_format($money, 2, '.', ''); // 前端显示充值金额
                $moneysTmp[] = number_format($money, 2, '.', '');
            }
        }

        if( 'ON' === $custom_amount_switch && ($custom_amount_min < $min || $custom_amount_max > $max) ) 
            ajax_return(ERROR, '最小金额或最大金额不匹配', []);

        $this->db->trans_begin();

        try {
            $sql    = "INSERT INTO payment_config (`seq_id`, `third_party_name`, `route_name`, `channel_name`, `pay_name`, `type`, `custom_amount_switch`, `custom_amount_min`, `custom_amount_max`, `min`, `max`, `limit`, `is_recommend`, `switch`, `adapt_android`, `adapt_ios`, `adapt_web`, `pay_type`, `channel_code`, `history_recharge_limit`, `app_hints`, `callback_whitelist`, `callback_route`) 
                            VALUES ({$seq_id}, '{$third_party_name}', '{$route_name}', '{$channel_name}', '{$pay_name}', '{$type}', '{$custom_amount_switch}', {$custom_amount_min}, {$custom_amount_max}, {$min}, {$max},  {$limit}, '{$is_recommend}', '{$switch}', '{$adapt_android}', '{$adapt_ios}', '{$adapt_web}', {$pay_type}, '{$channel_code}', {$history_recharge_limit}, '{$app_hints}', '{$callback_whitelist}', '{$callback_route}')";

            $configId = $this->db->query($sql)->insert_id();

            $moneyId = [];
            if(0 < count($moneys) ) {
                foreach($moneys as $key=>$money) {
                    if ($money > 0) {
                        $sql = "INSERT INTO payment_money_config (`payment_config_id`, `position`, `opt_money`) VALUES ({$configId}, {$key}, {$money})";
                        $moneyId[] = $this->db->query($sql)->insert_id();
                    }
                }
            }

            if ( $configId && count($moneyId) === count($moneys)) {
                $this->db->trans_commit();
                @file_put_contents(ERRLOG_PATH . '/payment_channel_crud_' . date("Ymd") . '.log',
                    date("Y-m-d H:i:s") . " $admin_id - $admin_name - $ip had created $configId - $route_name \n",
                    FILE_APPEND);
                $this->redis->set('payment:config:' . $configId, json_encode([ 'payment_config_id' => $configId,
                                        'seq_id' => $seq_id, 'third_party_name' => $third_party_name, 'route_name' => $route_name, 
                                        'channel_name' => $channel_name, 'type' => $type, 'pay_type'=>$pay_type,
                                        'custom_amount_switch' => $custom_amount_switch, 'custom_amount_min' => $custom_amount_min,
                                        'custom_amount_max' => $custom_amount_max, 'adapt_android' => $adapt_android, 'adapt_ios' => $adapt_ios, 'app_hints' => $app_hints, 'adapt_web' => $adapt_web,
                                        'is_recommend' => $is_recommend, 'pay_name' => $pay_name, 'channel_code' => $channel_code,
                                        'history_recharge_limit' => $history_recharge_limit, 'callback_whitelist' => $callback_whitelist,
                                        'payment_switch' => $switch, 'moneys' => $moneysTmp
                                    ], true));

                $msg        = '数据添加成功';
                $rtnStatus  = SUCCESS;
            } else {
                $this->db->trans_rollback();
                $msg        = '数据添加失败';
                $rtnStatus  = ERROR;
                @file_put_contents(ERRLOG_PATH . '/payment_channel_crud_' . date("Ymd") . '.log',
                    date("Y-m-d H:i:s") . " failed to insert $sql to database! \n",
                    FILE_APPEND);
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            @file_put_contents(ERRLOG_PATH . '/payment_channel_crud_' . date("Ymd") . '.log',
                    date("Y-m-d H:i:s") . " failed to create records in database: ".$e->getMessage() ."\n",
                    FILE_APPEND);
            $rtnStatus = ERROR;
            $msg        = '数据添加失败';
        }

        ajax_return($rtnStatus, $msg, []);
    }

    /**
     *  编辑支付通道
     *
     *  
     */
    public function update_payment_channel()
    {
        // 日志记录操作员的IP地址，未必准确
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $id         = intval($_POST['id'] ?? 0); 

        if(0 === $id) ajax_return(ERROR, 'WRONG ID RECEIVED!', []);

        $seq_id     = intval($_POST['seq_id'] ?? 0);          // 顺序号
        $third_party_name = str_format($_POST['third_party_name'] ?? '');      // 渠道商名称
        $route_name     = str_format($_POST['route_name'] ?? '');              // function名称
        $channel_name   = str_format($_POST['channel_name'] ?? '');            // 前端显示名称
        $channel_code   = str_format($_POST['channel_code'] ?? '');            // 前端显示名称的代号，渠道商会提供如果有的话
        $pay_name       = str_format($_POST['pay_name'] ?? '');              // admin后端显示的名称,沿用之前的
        $type           = str_format($_POST['type'] ?? 'ALIPAY');                   // 支付方式: 'WECHAT', 'ALIPAY', 'BANKCARD', 'CREDIT'
        $custom_amount_switch = str_format($_POST['custom_amount_switch'] ?? 'OFF');
        $custom_amount_min = intval($_POST['custom_amount_min'] ?? 0);                   // 最小值
        $custom_amount_max = intval($_POST['custom_amount_max'] ?? 1);                   // 最大值
        $min            = intval($_POST['min'] ?? 0);                   // 最小值
        $max            = intval($_POST['max'] ?? 0);                   // 最小值
        $limit          = intval($_POST['limit'] ?? 0);                   // 停用金额, 0表示不启用
        $is_recommend   = str_format($_POST['is_recommend'] ?? 'OFF');                   // 前端是否显示推荐
        $switch         = str_format($_POST['status_switch'] ?? 'OFF');                   // 开启/关闭，
        $adapt_ios      = str_format($_POST['adapt_ios'] ?? 'ON');                   // 适用android开启/关闭，
        $adapt_android  = str_format($_POST['adapt_android'] ?? 'ON');                   // 适用ios开启/关闭，
        $adapt_web         = str_format($_POST['adapt_web'] ?? 'ON');                   // 适用小网开启/关闭，
        $pay_type       = intval($_POST['pay_type'] ?? 0);                   // 支付类型，匹配原来的type，
        $history_recharge_limit   = intval($_POST['history_recharge_limit'] ?? 0);                   // 支付类型，匹配原来的type，
        $app_hints       = text_format($_POST['app_hints'] ?? '');                   // 支付类型，匹配原来的type，
        $callback_whitelist       = str_format($_POST['callback_whitelist'] ?? '');                   // 支付类型，匹配原来的type，
        $callback_whitelist       = str_replace(' ', '', $callback_whitelist);
        $callback_whitelist       = str_replace('；', ';', $callback_whitelist);
        $callback_route      = text_format($_POST['callback_route'] ?? '');  
        
        $moneys         = [];
        $moneysTmp      = [];
        for($i = 0; $i<9; $i++) {
            $money = intval($_POST['opt_money'.$i] ?? 0);
            if( (0 !== $money) && ($money > $max || $money < $min) )
                ajax_return(ERROR, '最小金额或最大金额超出设置范围', []);
            if($money > 0) {
                $moneys[$i] = number_format($money, 2, '.', ''); // 前端显示充值金额
                $moneysTmp[] = number_format($money, 2, '.', '');
            }
        }

        if( 'ON' === $custom_amount_switch && ($custom_amount_min < $min || $custom_amount_max > $max) ) 
            ajax_return(ERROR, '最小金额或最大金额不匹配', []);

        $this->db->trans_begin();

        try {
            $mainsql    = " UPDATE `payment_config` SET `seq_id`={$seq_id}, `third_party_name`='{$third_party_name}', `route_name`='{$route_name}', `channel_name`='{$channel_name}', `pay_name`='{$pay_name}', `type`='{$type}', `custom_amount_switch`='{$custom_amount_switch}', `custom_amount_min`={$custom_amount_min}, `custom_amount_max`={$custom_amount_max}, `min`={$min}, `max`={$max}, `limit`={$limit}, `is_recommend`='{$is_recommend}', `switch`='{$switch}', `adapt_ios`='{$adapt_ios}', `adapt_android`='{$adapt_android}', `adapt_web`='{$adapt_web}', `pay_type`={$pay_type}, `channel_code`='{$channel_code}', `history_recharge_limit`={$history_recharge_limit}, `app_hints`='{$app_hints}', `callback_whitelist`='{$callback_whitelist}', `callback_route`='{$callback_route}' WHERE id={$id}";
            $this->db->query($mainsql);

            $moneyId = [];
            $remsql = "DELETE FROM `payment_money_config` WHERE `payment_config_id`={$id}";
            $this->db->query($remsql);
            if( 0 !== count($moneys)) {
                foreach($moneys as $key=>$money) {
                    if ($money > 0) {
                        $sql = "INSERT INTO payment_money_config (`payment_config_id`, `position`, `opt_money`) VALUES ({$id}, {$key}, {$money})";
                        $moneyId[] = $this->db->query($sql)->insert_id();
                    }
                }

            }

            $this->db->trans_commit();

            @file_put_contents(ERRLOG_PATH . '/payment_channel_crud_' . date("Ymd") . '.log',
                date("Y-m-d H:i:s") . " $admin_id - $admin_name - $ip had executed $mainsql \n",
                FILE_APPEND);
            $this->redis->set('payment:config:' . $id, json_encode([ 'payment_config_id' => $id,
                                    'seq_id' => $seq_id, 'third_party_name' => $third_party_name, 'route_name' => $route_name, 
                                        'channel_name' => $channel_name, 'type' => $type,  'pay_type'=>$pay_type,
                                        'custom_amount_switch' => $custom_amount_switch, 'custom_amount_min' => $custom_amount_min,
                                        'custom_amount_max' => $custom_amount_max, 'adapt_android' => $adapt_android, 'adapt_ios' => $adapt_ios, 'app_hints' => $app_hints, 'adapt_web' => $adapt_web,
                                        'is_recommend' => $is_recommend, 'pay_name' => $pay_name, 'channel_code' => $channel_code,
                                        'history_recharge_limit' => $history_recharge_limit, 'callback_whitelist' => $callback_whitelist,
                                        'payment_switch' => $switch, 'moneys' => $moneysTmp
                            ], true));

            $msg        = '数据添加成功';
            $rtnStatus  = SUCCESS;
            
        } catch (Exception $e) {
            @file_put_contents(ERRLOG_PATH . '/payment_channel_' . date("Ymd") . '.log',
                    date("Y-m-d H:i:s") . " failed to create records in database: ".$e->getMessage() ."\n",
                    FILE_APPEND);
            $rtnStatus = ERROR;
            $msg        = '数据添加失败';
        }

        ajax_return($rtnStatus, $msg, []);
    }

    /**
     *  开关支付通道
     *
     *  
     */
    public function switch_payment_channel()
    {
        // 日志记录操作员的IP地址，未必准确
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $id         = intval($_POST['id'] ?? 1);                   // 调用方法名称
        $action     = str_format($_POST['action'] ?? 'switch');  
        $switch     = str_format($_POST['value'] ?? 'OFF');                    // 开启/关闭，
        $delete     = boolval($_POST['value'] ?? false);                   // 开启/关闭，

        // if( 0 === $configID ) ajax_return(ERROR, '调用方法名称不能为空', []);

        $cached     = $this->redis->get('payment:config:' . $id);
        $cached     = json_decode($cached, true);

        // $sql = "SELECT id FROM payment_config WHERE route_name='{$route_name}'";
        // $id  = $this->db->query($sql)->row_array()['id'];

        try {
            if($action === 'switch') {
                $sql = "UPDATE payment_config SET switch='{$switch}' WHERE id={$id}";
                $this->db->query($sql);
                $cached['payment_switch'] = $switch;
                $this->redis->set('payment:config:' . $id, json_encode($cached));
                $msg        = '数据更新成功';
            }

            if($action === 'delete') {
                $sql = "UPDATE payment_config SET is_deleted={$delete} WHERE id={$id}";
                $this->db->query($sql);
                $this->redis->del('payment:config:' . $id);
                $msg  = '数据删除成功';
            }

            @file_put_contents(ERRLOG_PATH . '/payment_channel_crud_' . date("Ymd") . '.log',
                date("Y-m-d H:i:s") . "$admin_id - $admin_name - $ip had executed $sql \n",
                FILE_APPEND);
            
            $rtnStatus  = SUCCESS;
        } catch (Exception $e) {
            @file_put_contents(ERRLOG_PATH . '/payment_channel_crud_' . date("Ymd") . '.log',
                date("Y-m-d H:i:s") . " failed to update records in database: ".$e->getMessage() ."\n",
                FILE_APPEND);
            $rtnStatus = ERROR;
            $msg        = '数据更新失败';
        }

        ajax_return($rtnStatus, $msg, []);
    }

    /**
     *  线下银行卡支付通道列表
     *
     *  
     */
    public function bank_payment_channel_config()
    {
        $appid = DATABASESUFFIX;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);

        // 日志记录操作员的IP地址，未必准确
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $where = ' 1 AND is_deleted=0 ';

        $sql    = "SELECT pb.id, pb.channel_name, pb.branch_name, pb.card_number, pb.holder_name, pb.operator, pb.status, pb.updated_at, bt.name as bank_name FROM pay_bankinfo_$appid pb
                        LEFT JOIN bank_type_$appid bt
                        ON pb.bank_id=bt.id
                        WHERE $where
                        ORDER BY pb.id DESC  
                        LIMIT {$page_size} OFFSET {$limit}";

        $config = $this->db->query($sql)->result_array();

        $index = 1;
        foreach ($config as $key => &$value) {
            $value['index'] = $index;
            $value['updated_at'] = time_to_local_string($value['updated_at']);
            $index++;
        }

        $total  = ($page - 1) * $page_size + count($config);

        ajax_return(SUCCESS, '', [
            'list' => $config,
            'total' => $total,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ]);

    }

    /**
     *  创建线下银行支付通道
     *
     *  
     */
    public function create_bank_payment_channel()
    {
        // 日志记录操作员的IP地址，未必准确
        $appid = DATABASESUFFIX;
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $bank_id     = intval($_POST['bank_id'] ?? 0);          
        $branch_name = str_format($_POST['branch_name'] ?? '');      
        $card_number     = str_format($_POST['card_number'] ?? '');             
        $card_number_confirm   = str_format($_POST['card_number_confirm'] ?? '');            
        $holder_name   = str_format($_POST['holder_name'] ?? '');            
        $channel_name  = str_format($_POST['channel_name'] ?? '');   // 前端显示名称的代号，渠道商会提供如果有的话
        

        if ( empty($bank_id) || empty($branch_name) || empty($card_number) || empty($card_number_confirm) 
                || empty($holder_name) || empty($channel_name) )
            ajax_return(ERROR, get_tips(1006));
        if( $card_number !== $card_number_confirm) ajax_return(ERROR, get_tips(1029));

        $nameSql = "SELECT name FROM bank_type_$appid WHERE id={$bank_id}";
        $bank_name = $this->db->query($nameSql)->row_array()['name'];

        if( empty($bank_name) ) ajax_return(ERROR, 'WRONG BANK ID');

        try {
            $sql    = "INSERT INTO pay_bankinfo_$appid (`bank_id`, `channel_name`, `branch_name`, `card_number`, `holder_name`, `operator_id`, `operator`) 
                            VALUES ({$bank_id}, '{$channel_name}', '{$branch_name}', '{$card_number}', '{$holder_name}', {$admin_id}, '{$admin_name}')";
            $configId = $this->db->query($sql)->insert_id();

            if($configId) {
                $this->redis->set('payment:config:bank:' . $configId, json_encode([
                            'payment_config_id'=>$configId, 'bank_id' => $bank_id, 'branch_name'=> $branch_name, 'card_number' => $card_number, 'holder_name' => $holder_name, 'channel_name'=>$channel_name, 'status' => 'OFF', 'bank_name' => $bank_name
                ]));
                $msg        = get_tips(1016);
                $rtnStatus  = SUCCESS;
            } else {
                $msg        = get_tips(1015);
                $rtnStatus  = ERROR;
                @file_put_contents(ERRLOG_PATH . '/payment_bank_' . date("Ymd") . '.log',
                    date("Y-m-d H:i:s") . " failed to insert $sql to database! \n",
                    FILE_APPEND);
            }

        } catch (Exception $e) {
            @file_put_contents(ERRLOG_PATH . '/payment_bank_' . date("Ymd") . '.log',
                    date("Y-m-d H:i:s") . " failed to create records in database: ".$e->getMessage() ."\n",
                    FILE_APPEND);
            $rtnStatus = ERROR;
            $msg       = get_tips(1015);
        }

        ajax_return($rtnStatus, $msg, []);
    }


    /**
     *  开关线下支付通道
     *
     *  
     */
    public function switch_bank_payment_channel()
    {
        // 日志记录操作员的IP地址，未必准确
        $appid = DATABASESUFFIX;
        $ip         = get_client_ip(0);
        // 日志记录操作员的ID
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $id         = intval($_POST['id'] ?? 1);                   // 调用方法名称
        $action     = str_format($_POST['action'] ?? 'switch');  
        $switch     = str_format($_POST['value'] ?? 'OFF');                    // 开启/关闭，
        $delete     = boolval($_POST['value'] ?? false);                   // 开启/关闭，

        // if( 0 === $configID ) ajax_return(ERROR, '调用方法名称不能为空', []);

        $cached     = $this->redis->get('payment:config:bank:' . $id);
        $cached     = json_decode($cached, true);

        // $sql = "SELECT id FROM payment_config WHERE route_name='{$route_name}'";
        // $id  = $this->db->query($sql)->row_array()['id'];

        try {
            if($action === 'switch') {
                $sql = "UPDATE pay_bankinfo_$appid SET status='{$switch}' WHERE id={$id}";
                $this->db->query($sql);
                $cached['status'] = $switch;
                
                $this->redis->set('payment:config:bank:' . $id, json_encode($cached));
                $msg        = '数据更新成功';
            }

            if($action === 'delete') {
                $sql = "UPDATE pay_bankinfo_$appid SET is_deleted={$delete} WHERE id={$id}";
                $this->db->query($sql);
                $this->redis->del('payment:config:bank:' . $id);
                $msg  = '数据删除成功';
            }

            @file_put_contents(ERRLOG_PATH . '/payment_bank_' . date("Ymd") . '.log',
                date("Y-m-d H:i:s") . "$admin_id - $admin_name - $ip had executed $sql \n",
                FILE_APPEND);
            
            $rtnStatus  = SUCCESS;
        } catch (Exception $e) {
            @file_put_contents(ERRLOG_PATH . '/payment_bank_' . date("Ymd") . '.log',
                date("Y-m-d H:i:s") . " failed to update records in database: ".$e->getMessage() ."\n",
                FILE_APPEND);
            $rtnStatus = ERROR;
            $msg        = '数据更新失败';
        }

        ajax_return($rtnStatus, $msg, ['status' => $switch]);
    }

    /**
     *  线下充值订单记录
     *
     */
    public function trade_deposit_bank()
    {
        // 处理参数
        $page       = intval($_POST['page'] ?? 1);
        $page_size  = ADMIN_PAGE_SIZE;
        $limit      = (($page - 1) * $page_size);
        $appid      = DATABASESUFFIX;

        $uid        = intval($_POST['uid'] ?? 0);
        $operator   = str_format($_POST['order_operator'] ?? '');
        $mobile     = intval($_POST['mobile'] ?? 0);
        $payment_username = str_format($_POST['payment_username'] ?? '');
        $payment_bankcard_no = str_format($_POST['payment_bankcard_no'] ?? '');
        $status     = str_format($_POST['status'] ?? '');
        $order_id   = str_format($_POST['order_id'] ?? '');
        $transfer_time_start = str_format($_POST['transfer_time'][0] ?? '');
        $transfer_time_end = str_format($_POST['transfer_time'][1] ?? '');
        $process_time_start = str_format($_POST['process_time'][0] ?? '');
        $process_time_end = str_format($_POST['process_time'][1] ?? '');
        $search     = intval($_POST['search'] ?? 0);

        // 处理条件
        $where = '1';

        $where .= !empty($uid) ? " AND pd.uid={$uid}" : '';
        $where .= !empty($operator) ? " AND bpl.order_operator={$operator}" : '';
        $where .= !empty($mobile) ? " AND user.mobile={$mobile}" : '';
        $where .= !empty($payment_username) ? " AND bpl.payment_username={$payment_username}" : '';
        $where .= !empty($payment_bankcard_no) ? " AND bpl.payment_bankcard_no={$payment_bankcard_no}" : '';
        $where .= !empty($status) ? " AND bpl.status='{$status}'" : '';
        $where .= !empty($order_id) ? " AND bpl.order_id={$order_id}" : '';
        $where .= (!empty($transfer_time_start) && !empty($transfer_time_end)) 
                        ? " AND DATE_ADD(bpl.transfered_at, INTERVAL 8 HOUR) > '{$transfer_time_start}' AND DATE_ADD(bpl.transfered_at, INTERVAL 8 HOUR) < '{$transfer_time_end}'" : '';
        $where .= (!empty($process_time_start) && !empty($process_time_end)) 
                        ? " AND DATE_ADD(bpl.updated_at, INTERVAL 8 HOUR) > '{$process_time_start}' AND DATE_ADD(bpl.updated_at, INTERVAL 8 HOUR) < '{$process_time_end}'" : '';

        $where .= ' AND pd.payment_type =2 ';

        // 获取数据
        $sql = "SELECT bpl.id, bpl.payment_username, bpl.baby_amount, bpl.payment_bankcard_no, bpl.transfered_at, bpl.order_id, bpl.status, bpl.order_operator, bpl.order_note, bpl.updated_at, bpl.upload_image, pd.uid, pd.money, pb.holder_name, pb.branch_name, pb.card_number, bt.name AS bank_name, user.nickname, user.mobile
                    FROM bank_payment_log_$appid bpl
                    LEFT JOIN pay_deposit_$appid pd
                    ON bpl.order_id=pd.order_id
                    LEFT JOIN pay_bankinfo_$appid pb
                    ON bpl.bankinfo_id=pb.id
                    LEFT JOIN bank_type_$appid bt
                    ON pb.bank_id=bt.id
                    LEFT JOIN user_1_0 user
                    ON pd.uid = user.id
                    where $where
                    GROUP BY bpl.id
                    ORDER BY pd.id DESC 
                    LIMIT {$page_size} OFFSET $limit";

        // $sql = "select * from pay_deposit_" . DATABASESUFFIX . " where 1 $where ORDER BY id DESC limit {$page_size} offset $limit";
        // @file_put_contents(ERRLOG_PATH . '/bank_recharge_log_' . date("Ymd") . '.log',
        //         date("Y-m-d H:i:s") ."SQL excuted: $sql " . " \n",
        //         FILE_APPEND);        
        $pay_deposit = $this->db->query($sql)->result_array();

        $index = 1;
        $page_count_solved = 0;
        $page_total_solved = 0;
        foreach ($pay_deposit as &$order) {
            $order['index'] = $index;
            $order['updated_at'] = time_to_local_string($order['updated_at']);
            $order['payee_account_info'] = $order['holder_name'] . '/' . $order['bank_name'] . '/' . $order['branch_name'] . '/' . $order['card_number'];
            if('PROCESSING' === $order['status']) {
                $order['operator'] = '/';
                $order['order_note'] = '/';
                $order['updated_at'] = '/';
            } else {
                $order['operator'] = $order['order_operator'];
            }
            if('SOLVED' === $order['status']) {
                $page_count_solved++;
                $page_total_solved += $order['money'];
            }
            switch ( strtolower($order['status']) ) {
                case 'processing':
                    $order['status'] = '未处理';
                    break;
                case 'solved':
                    $order['status'] = '已上分';
                    break;
                case 'denied':
                    $order['status'] = '拒绝';
                    break;
                default:
                    $order['status'] = '未处理';
                    break;
            }
            $order['upload_image'] = empty($order['upload_image']) ? '' : ADMIN_SERVER_URL . $order['upload_image'];
            $order['baby_amount'] = number_format($order['money'] * 4, 2, '.', '');
            $index++;
            // $order['surplus'] = $this->redis->hget('user:'. $order['uid'], 'eurc_balance') ?: 0;
        }

        $page_total_solved = number_format($page_total_solved, 2, '.', '');

        $sqlTotal = "SELECT count(bpl.id) as total, COUNT(IF (bpl.status='SOLVED', bpl.status, NULL) ) AS count_solved, SUM(IF (bpl.status='SOLVED', pd.money, 0) ) AS total_solved 
                        FROM bank_payment_log_$appid bpl
                        LEFT JOIN pay_deposit_$appid pd
                        ON bpl.order_id=pd.order_id
                        WHERE pd.payment_type=2"; 
        $totalRes = $this->db->query($sqlTotal)->row_array();
        $total = $totalRes['total'];
        $count_solved = $totalRes['count_solved'];
        $total_solved = number_format($totalRes['total_solved'], 2, '.', '');

        // if $search = 1, export data to excel
        if (!empty($search) && 1 === $search) {
            $header = ['序号', '用户ID', '用户昵称', '注册手机号', '转账人', '转账金额', '转账卡号', '转账时间', '收款卡信息', '订单号', '状态', '操作人', '操作备注', '操作时间'];

            // set defualt time period to export
            if ( empty($transfer_time_start) && empty($transfer_time_end) && empty($process_time_start) && empty($process_time_end) ) {
                // 获取数据
                $where .= " AND bpl.updated_at > DATE_ADD(NOW(), INTERVAL -2 MONTH)";
            }

            $sql = "SELECT bpl.payment_username, bpl.payment_bankcard_no, bpl.transfered_at, bpl.order_id, bpl.status, bpl.order_operator, bpl.order_note, bpl.updated_at, pd.uid, pd.money, pb.holder_name, pb.branch_name, pb.card_number, bt.name AS bank_name, user.nickname, user.mobile
                            FROM bank_payment_log_$appid bpl
                            LEFT JOIN pay_deposit_$appid pd
                            ON bpl.order_id=pd.order_id
                            LEFT JOIN pay_bankinfo_$appid pb
                            ON bpl.bankinfo_id=pb.id
                            LEFT JOIN bank_type_$appid bt
                            ON pb.bank_id=bt.id
                            LEFT JOIN user_1_0 user
                            ON pd.uid = user.id
                            where $where
                            GROUP BY bpl.id
                            ORDER BY bpl.created_at DESC, pd.id DESC ";

            $pay_deposit = $this->db->query($sql)->result_array();

            $new_data = [];
            $index = 1;
            foreach ($pay_deposit as $k => $v) {
                $new_data[$k]['index']      = $index;
                $new_data[$k]['uid']        = $v['uid'];
                $new_data[$k]['nickname']   = $v['nickname'];
                $new_data[$k]['mobile']     = $v['mobile'];
                $new_data[$k]['payment_username'] = $v['payment_username'];
                $new_data[$k]['money']      = $v['money'];
                $new_data[$k]['payment_bankcard_no']   = $v['payment_bankcard_no'];
                $new_data[$k]['transfered_at'] = $v['transfered_at'];
                $new_data[$k]['payee_account_info'] = $v['holder_name'] . '/' . $v['bank_name'] . '/' . $v['branch_name'] . '/' . $v['card_number'];
                $new_data[$k]['order_id']   = $v['order_id'];
                $new_data[$k]['status']     = $v['status'];
                switch (strtolower($v['status'])) {
                    case 'processing':
                        $new_data[$k]['status']     = '未处理';
                        break;
                    case 'solved':
                        $new_data[$k]['status']     = '已上分';
                        break;
                    case 'denied':
                        $new_data[$k]['status']     = '拒绝';
                        break;
                    default:
                        $new_data[$k]['status']     = '未处理';
                        break;
                }
                $new_data[$k]['order_operator']     = $v['order_operator'];
                $new_data[$k]['order_note']     = $v['order_note'];
                $new_data[$k]['updated_at']     = time_to_local_string($v['updated_at']);

                $index++;
            }

            $path = export('线下充值订单_' . time()  , $header, $new_data);

            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        // 返回
        ajax_return(SUCCESS, '', array(
            'data' => $pay_deposit,
            'summary' => ['page_count_solved' => $page_count_solved, 'page_total_solved' => $page_total_solved, 
                            'count_solved' => $count_solved, 'total_solved' => $total_solved ],
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     *  处理线下银行打款申请
     *
     */
    public function process_bank_payment_form()
    {
        $id    = intval($_POST['id'] ?? 0);
        $uid    = intval($_POST['uid'] ?? 0);
        $money         = floatval($_POST['money'] ?? 0.00);
        $order_id      = intval($_POST['order_id'] ?? 0);
        // $baby_amount   = floatval($_POST['baby_amount'] ?? 0.0000);
        // $baby_amount   = number_format($baby_amount, 4, '.', '');
        $baby_amount = number_format($money * 4, 4, '.', '');
        $order_note    = text_format($_POST['order_note'] ?? '');
        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $appid = DATABASESUFFIX;

        $time = time();
        $img_arr = [];
        $file = null;

        if ( empty($id) || empty($order_note) || !isset($_FILES['file']) ) 
            ajax_return(ERROR, get_tips(18001));

        $file = $_FILES['file'];

        if ($file) {
            // 取扩展名
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $path = '/bank_payment_process/credentials_' . date("Ymd") .'/' .time(). rand_code(3, 'both');
            $filename = $path . "." . $ext;

            $sql_save_path = '/upload' . $filename;
            $file_name = '/upload' . $filename;


            $_file = $_FILES['file'];
            //解析文件路径
            if (!$file_name) {
                $file_info = pathinfo($_file['name']);//解析文件路径
                $ext = '.' . strtolower($file_info['extension']);//文件后缀
                $file_name = time().rand_code(3, 'both') .'.'. $ext;//保存文件名
                $file_path = '/upload' . '/bank_payment_process/credentials_' . date("Ymd") .'/' ;//存储路径
            } else {
                $file_info = pathinfo($file_name);//解析文件路径
                $ext = '.' . strtolower($file_info['extension']);//文件后缀
                $file_name = strtolower($file_info['basename']);//保存文件名
                $file_path = strtolower($file_info['dirname']) . '/';//存储路径
            }

            //判断目录是否存在，如果不存在则自动创建
            if ( !is_dir(BASEPATH . $file_path) ) {
                if (!mkdir( BASEPATH . $file_path, 0775, true)) {
                    $result['msg'] = get_tips(15009) . BASEPATH . $file_path;
                    ajax_return(ERROR, get_tips(7015) . ':' . $result['msg'] );
                }
            }

            //执行上传
            $dist_path = BASEPATH . $file_path . iconv('UTF-8', 'GB2312//IGNORE', $file_name);
            if (!@copy($_file['tmp_name'], $dist_path)) {
                if (!@move_uploaded_file($_file['tmp_name'], BASEPATH . $file_path . $file_name)) {
                    $result['msg'] = get_tips(15010);
                    ajax_return(ERROR, get_tips(7015) . ':' . $result['msg'] );
                }
            }

            array_push($img_arr, $sql_save_path);
        }

        $img_arr_str = implode('|', $img_arr);

        $sql = "UPDATE bank_payment_log_$appid SET STATUS='SOLVED', baby_amount='{$baby_amount}', order_operator_id={$admin_id}, order_operator='{$admin_name}', order_note='{$order_note}', upload_image='{$img_arr_str}' WHERE id={$id}";

        $affected_rows = $this->db->query($sql)->affected_rows();

        if ($affected_rows)  {
            // 更新redis里的eurc_balance 增加 ----上分
            // $pipe = $this->redis->pipeline();
            // $pipe->hIncrByFloat("user:$uid", STABLE_CURRENCY_NAME . '_balance', $baby_amount);
            // $pipe->sAdd("user:balance:update", $uid);
            // $pipe->exec();

            self::postProcessAfterPaid( $money, $order_id, '');
            // self::add_or_reduce_calc_total_baby_coin(1, $uid, 0, $baby_amount);

            ajax_return(SUCCESS, get_tips(1005));
        }
        else ajax_return(ERROR, get_tips(1004));
    }

    /**
     *  处理线下银行打款 拒绝表单
     *
     */
    public function deny_bank_payment_form()
    {
        $id    = intval($_POST['id'] ?? 0);
        $order_note    = text_format($_POST['order_note'] ?? '');

        $admin_id   = $this->user['id'];
        $admin_name = $this->user['username'];

        $appid = DATABASESUFFIX;

        if ( empty($id) || empty($order_note) ) 
            ajax_return(ERROR, get_tips(18001));

        $sql = "UPDATE bank_payment_log_$appid SET status='DENIED', order_operator_id={$admin_id}, order_operator='{$admin_name}', order_note='{$order_note}' WHERE id={$id}";

        $affected_rows = $this->db->query($sql)->affected_rows();

        if ($affected_rows)  ajax_return(SUCCESS, get_tips(1005));
        else ajax_return(ERROR, get_tips(1004));
    }

    /**
     *  提现审核
     */
    public function trade_review()
    {
        $id   = intval($_POST['id']);
        $type = intval($_POST['type']);
        $admin_id   = $this->user['id'];
        $admin_time = date('Y-m-d H:i:s', time());
        (!$id || !$type) && ajax_return(ERROR, get_tips(18001));

        // 修改审核状态
        $this->init_db();
        switch ($type) {
            case '1':
                $sql = "UPDATE trade_withdraw_1 SET audit = {$type}, admin_id = $admin_id, admin_time = '$admin_time'  WHERE id = {$id}";
                break;
            case '2':
                $sql = "UPDATE trade_withdraw_1 SET audit = {$type}, admin_id = $admin_id, admin_time = '$admin_time', status = {$type}  WHERE id = {$id}";
                break;
        }
        $this->db->query($sql)->affected_rows();
        $sql = "select uid, to_addr, amount, net_amount from trade_withdraw_1 where id = {$id}";
        $data = $this->db->query($sql)->row_array();
        $uid = $data['uid'];
        $amount = $data['amount'];
        $net_amount = $data['net_amount'];
        $to_addr = $data['to_addr'];
        $sql = "select uid from trade_wallet_1 where addr = '$to_addr'";
        $res = $this->db->query($sql)->row_array();
        if ($type == 2) {
            $this->redis->hIncrByFloat("user:$uid", 'eurc_balance', $amount);
            if (isset($res['uid']) && $res['uid']) {
                $this->redis->hIncrByFloat('user:'.$res['uid'], 'eurc_balance', - $net_amount);
            }
        } else {
            if (isset($res['uid']) && $res['uid']) {
                $this->redis->hIncrByFloat('user:'.$res['uid'], 'eurc_balance',  $net_amount);
            }
        }
        ajax_return(1, get_tips(18002));
    }

    /**
     * 虚拟币卖单交易
     */
    public function trade_sell()
    {
        //接收数据
        $last_id = intval($this->req_data['last_id'] ?? 0);
        $appid = $this->req_data['appid'];
        $size = 3;
        //sql
        $fields = 'id, uid, coin, to_addr, amount, fee, net_amount, gas,uptime,status,txn_hash,admin_id,admin_time';
        $sql = "select $fields from trade_withdraw_{$appid} where id > {$last_id} limit {$size}";
        $this->init_db(1);
        $res = $this->db->query($sql)->result_array();
        ajax_return(SUCCESS, '', $res);
    }

    /**
     * C2C交易 sell_id > 0
     */
    public function trade_c2c()
    {
        $this->trade_common($_POST, 'c2c');
    }

    /**
     * B2C交易 sell_id = -1 or sell_id = -2
     */
    public function trade_b2c()
    {
        $this->trade_common($_POST, 'b2c');
    }

    /**
     * 获取交易数据 trade_common
     *
     * @param array $post
     * @param string $type
     * @return void
     */
    public function trade_common(array $post, string $type = 'c2c')
    {
        // 处理参数
        $page = intval($post['page'] ?? 1);
        $page_size = 10;
        $limit = (($page - 1) * $page_size);
        $search_type = $post['search_type'] ?? '';
        $search_info = $post['search_info'] ?? '';
        $search_coin = $post['search_coin'] ?? '';
        $search_appeal = $post['search_appeal'] ?? '';
        $order_status = $post['order_status'] ?? '';
        $start_time = $post['start_time'] ?? '';
        $end_time = $post['end_time'] ?? '';

        // 处理条件
        $where  = '1';
        if ($search_type != '' && $search_info != '') {
            switch ($search_type) {
                case 'order':
                    $where .= " and buy.order_id = '{$search_info}'";
                    break;
                case 'buy':
                    $where .= " and buy.nuy_uid = '{$search_info}'";
                    break;
                // case 'sell':
                //     $where .= " and buy.sell_uid = '{$search_info}'";
                //     break;
                // case 'have':
                //     $where .= " and buy.buy_name = '{$search_info}'";
                //     break;
            }
        }
        $where .= $search_coin != '' ? " and buy.coin = '{$search_coin}'" : "";
        $where .= $type == 'c2c' ? ' and buy.sell_id > 0' : ' and buy.sell_id in(-1,-2)';
        if (!empty($search_appeal)) {
            $where .= $search_appeal == 'yes' ? ' and buy.appeal = 1' : ' and buy.appeal = 0';
        }
        $where .= $order_status === '' ? '' : " and buy.status = {$order_status}";
        $where .= $start_time && $end_time ? " and buy.order_time >= '{$start_time}' and buy.order_time <= '{$end_time}'" : '';

        // 处理sql
        $fields = "buy.id, buy.order_id, buy.sell_id, buy.sell_uid, buy.sell_name, buy.buy_uid, buy.buy_name, buy.coin, FORMAT(buy.price,2) as price, FORMAT(buy.amount,2) as amount, FORMAT(buy.money,2) as money, buy.ba_id, buy.pay_type, buy.appeal, buy.status, buy.order_time, buy.postscript";
        $appid = DATABASESUFFIX;
        $sql  = "SELECT {$fields} FROM trade_buy_$appid AS buy WHERE {$where} ORDER BY buy.id DESC LIMIT {$page_size} OFFSET {$limit}";
        $total_sql = "SELECT count(id) as num FROM trade_buy_" . DATABASESUFFIX . " AS buy WHERE {$where}";

        // 查询数据
        $trade_c2c = $this->db->query($sql)->result_array();
        $total = $this->db->query($total_sql)->row_array()['num'];

        $sell_uid_arr = [];
        foreach ($trade_c2c as $sell) {
            $sell_uid_arr[] = $sell['sell_uid'];
        }
        $sell_uids = implode(',', array_unique($sell_uid_arr));
        // 查询收款方式表
        if ($sell_uids) {
            $bank_sql = "SELECT `uid`, pay_title, account, payee FROM trade_bank_account_$appid WHERE `uid` in ($sell_uids)";
            $bank_data = $this->db->query($bank_sql)->result_array();
            if ($bank_data) {
                $bank_data = array_index($bank_data, 'uid');
            }
            foreach ($trade_c2c as &$item) {
                $item['account'] = $bank_data[$item['sell_uid']]['account'] ?? '';
                $item['payee'] = $bank_data[$item['sell_uid']]['payee'] ?? '';
            }
        }

        //收款人和卖方账户搜索
        if ($search_type != '' && $search_info != '') {
            foreach ($trade_c2c as $key => $item) {
                // 搜索收款人
                if ($search_info && strpos($item['account'], $search_info) === false && $search_type == 'sell') {
                    unset($trade_c2c[$key]);
                }
                // 搜索卖方账户
                if ($search_info && strpos($item['payee'], $search_info) === false && $search_type == 'have') {
                    unset($trade_c2c[$key]);
                }
            }
        }
        // 返回
        ajax_return(SUCCESS, '', array(
            'data' => $trade_c2c,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 交易确认 confirm_order
     *
     * @return json
     */
    public function confirm_order()
    {
        $id = intval($_POST['id'] ?? '');
        $status = intval($_POST['status'] ?? '');
        $memo = sql_format($_POST['memo'] ?? '');
        if (!$id || !$status) {
            ajax_return(ERROR, get_tips(1006));
        }

        // 查询订单数据
        $sql = "select buy_uid, coin, amount from trade_buy_" . DATABASESUFFIX . " where id = $id";
        $result = $this->db->query($sql)->row_array();
        if (!$result) {
            ajax_return(ERROR, get_tips(1006));
        }
        $uid = $result['buy_uid'];
        $coin = strtolower($result['coin']);
        $amount = $result['amount'];

        // 更新状态
        $sql = "update trade_buy_" . DATABASESUFFIX . " set status = {$status}, memo = '{$memo}' where id = {$id}";
        $result = $this->db->query($sql)->affected_rows();

        if ($result) {
            // 增加买家相应的余额
            $pipe = $this->redis->pipeline();
            $pipe->hIncrByFloat("user:$uid", $coin . "_balance", $amount);
            // 触发更新买家的余额数据
            $pipe->sAdd(RedisKey::USER_BALANCE_UPDATE, $uid);
            $pipe->exec();
        }

        ajax_return(1, get_tips(1005));
    }

    /**
     * 订单详情 order_info
     *
     * @return json
     */
    public function order_info()
    {
        $id = intval($_POST['id'] ?? '');
        if (!$id) {
            ajax_return(ERROR, get_tips(1006));
        }
        $sell_info = [];
        $sql = "select * from trade_buy_" . DATABASESUFFIX . " where id = {$id}";
        $info = $this->db->query($sql)->row_array();
        $uid = $info['sell_uid'];
        if ($uid) {
            $sell_info = $this->db->query("SELECT pay_title, payee, account, bank, branch FROM trade_bank_account_" . DATABASESUFFIX . " WHERE uid = {$uid}")->row_array();
            $info['account'] = $sell_info['account'] ?? '';
            $info['pay_title'] = $sell_info['pay_title'] ?? '';
            $info['payee'] = $sell_info['payee'] ?? '';
            $info['bank'] = $sell_info['bank'] ?? '';
            $info['branch'] = $sell_info['branch'] ?? '';
        }
        if (!empty($info['pic'])) {
            $pic_arr = explode('||', $info['pic']);
            foreach ($pic_arr as $item) {
                $info['pic_url'][] = get_pic_url($item);
            }
        }
        ajax_return(SUCCESS, '', $info);
    }

    /**
     * 法币卖单列表 sell_list
     *
     * @return void
     */
    public function sell_list()
    {
        // 表、字段
        $table = 'trade_sell_' . DATABASESUFFIX;
        $join_table = 'trade_bank_account_' . DATABASESUFFIX;
        $fields = 'id, sell_uid, sell_name, coin, FORMAT(price,2) as price, FORMAT(min_buy,2) as min_buy, FORMAT(max_buy,2) as max_buy, pay_type, status, FORMAT(amount,2) as amount';

        // 处理参数
        $page = intval($_POST['page'] ?: 1);
        $page_size = 10;
        $limit = (($page - 1) * $page_size);
        $selectType = $_POST['selectType'] ?: '';
        $selectText = intval($_POST['selectText'] ?: '');
        $coin = $_POST['coin'] ?: '';
        $status = $_POST['status'] ?? 99;
        $start_time = input('post.start_time');
        $end_time = input('post.end_time');

        //处理条件
        $where = '1';
        if ($coin) {
            $where .= " and coin = '{$coin}'";
        }
        if ($selectType == 'uid') {
            $where .= " and sell_uid = $selectText";
        } else if ($selectType == 'id') {
            $where .= " and sell_id = $selectText";
        }
        if ($status != 99) {
            $where .= " and status = $status";
        }
        if ($start_time && $end_time) {
            $where .= " and uptime >= '{$start_time}' and uptime <= '{$end_time}'";
        }
        // 查询数据
        $sql = "SELECT {$fields}  FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";

        $data = $this->db->query($sql)->result_array();

        $sell_arr = [];
        foreach ($data as $sell) {
            $sell_arr[] = $sell['sell_uid'];
        }
        $sell_uids = implode(',', array_unique($sell_arr));
        if ($sell_uids) {
            $bank_sql = "SELECT `uid`, pay_title, account, payee FROM $join_table WHERE `uid` in ($sell_uids)";
            $bank_data = $this->db->query($bank_sql)->result_array();
            if ($bank_data) {
                $bank_data = array_index($bank_data, 'uid');
            }
            foreach ($data as &$item) {
                switch ($item['status']) {
                    case 0:
                        $item['status_txt'] = get_tips(10017);
                        break;
                    case 1:
                        $item['status_txt'] = get_tips(10018);
                        break;
                    case 2:
                        $item['status_txt'] = get_tips(10019);
                        break;
                    case 3:
                        $item['status_txt'] = get_tips(10020);
                        break;
                    case 4:
                        $item['status_txt'] = get_tips(10021);
                        break;
                    case 5:
                        $item['status_txt'] = get_tips(10022);
                        break;
                }
                $item['account'] = $bank_data[$item['sell_uid']]['account'] ?? '';
                $item['payee'] = $bank_data[$item['sell_uid']]['payee'] ?? '';
                $item['pay_title'] = $bank_data[$item['sell_uid']]['pay_title'] ?? '';
            }
        }
        //统计条数
        $count_sql = "SELECT COUNT(id) AS num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->row_array()['num'];

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'data' => $data,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 收款方式列表 payment_method
     *
     * @return void
     */
    public function payment_method()
    {
        // 查询配置
        $table = 'trade_bank_account_' . DATABASESUFFIX;
        $fields = 'id, pay_title, account, uid, currency, status, payee';
        //处理参数
        $page = intval($_POST['page'] ?: 1);
        $page_size = 10;
        $limit = (($page - 1) * $page_size);
        $type = $_POST['paymentMethod'] ?? 'all';
        $status = $_POST['status'] ?? 'all';
        $currency = $_POST['currency'] ?? 'all';
        //处理条件
        $where = 'uid = -1';
        if ($status == 'on') {
            $status = 1;
        } else if ($status == 'off') {
            $status = 0;
        } else {
            $status = 'all';
        }
        $where .= ($type !== 'all' && $type !== '') ? " and pay_type = {$type}" : '';
        $where .= ($status !== 'all') ? " and status = {$status}" : '';
        $where .= ($currency !== 'all' && $currency !== '') ? " and currency = '{$currency}'" : '';
        //查询数据
        $sql = "SELECT {$fields} FROM {$table} WHERE {$where} ORDER BY uptime DESC LIMIT {$page_size} OFFSET {$limit}";
        $pay_list = $this->db->query($sql)->result_array();
        foreach ($pay_list as &$user) {
            $user['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $user['uid']), 'nickname') ?: '暂无昵称';
        }
        // 统计数据
        $count_sql = "SELECT COUNT(id) num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->row_array()['num'];
        // 返回数据
        ajax_return(SUCCESS, '', array(
            'data' => $pay_list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 收款方式详情 payment_method_info
     *
     * @return void
     */
    public function payment_method_info()
    {
        // 查询配置
        $table = 'trade_bank_account_' . DATABASESUFFIX;
        // 条件处理
        $id = intval($_POST['id'] ?? 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        // 查询数据
        $sql = "SELECT * FROM {$table} WHERE id = {$id}";
        $info = $this->db->query($sql)->row_array();
        $info['qrcode'] = get_pic_url($info['qrcode']);
        $info['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $info['uid']), 'nickname') ?: '暂无昵称';
        $info['status'] = $info['status'];
        ajax_return(SUCCESS, '', $info);
    }

    /**
     * 修改状态 save_payment_method
     *
     * @return void
     */
    public function save_payment_method_status()
    {
        $table = 'trade_bank_account_' . DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        $sql = "UPDATE {$table} SET status = {$status}  WHERE id = {$id}";
        $result = $this->db->query($sql)->affected_rows();
        $msg = $status == 1 ? get_tips(8013) : get_tips(8014);
        $return_msg = $msg . get_tips(1023);
        $return_status = ERROR;
        if ($result) {
            $return_msg = $msg . get_tips(1024);
            $return_status = SUCCESS;
        }
        ajax_return($return_status, $return_msg);
    }

    /**
     * 修改或添加收款方式 save_payment_method
     *
     * @return void
     */
    public function save_payment_method()
    {
        $table = 'trade_bank_account_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'type' => 'must|int',
            // 'bank' => 'must|char',
            // 'branch' => 'must|char',
            'account' => 'must|char',
            // 'uid' => 'must|int',
            'status' => 'must|int',
            'currency' => 'must|char',
            'payee' => 'must|char'
        );

        $msg = array(
            'type' => get_tips(8027),
            'type.int' => get_tips(8028),
            'currency' => get_tips(8029),
            // 'bank' => '请填写收款银行',
            // 'branch' => '请填写银行支行',
            'account' => get_tips(8030),
            // 'uid' => '请填写收款人UID',
            // 'uid.int' => 'UID收款人ID错误',
            'status' => get_tips(8031),
        );

        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }
        //参数处理
        $type = $data['type'];
        $bank = $data['bank'] ?? '';
        $currency = $data['currency'];
        $branch = $data['branch'] ?? '';
        $account = $data['account'];
        $uid = -1;
        $status = $data['status'];
        $qrcode = $data['qrcode'] ?? '';
        $remark = $data['remarks'] ?? '';
        $payee = $data['payee'];
        $pay_title = '';
        $title_arr = array(
            1 => get_tips(8032),
            3 => get_tips(8033),
            4 => get_tips(8034),
            5 => 'PayPal'
        );
        $pay_title = $title_arr[$type];
        // 添加或修改
        if (isset($data['id']) && $data['id']) {
            $msg_text = get_tips(8035);
            $id = $data['id'];
            $set = "pay_type = {$type}, bank = '{$bank}', currency = '{$currency}', branch = '{$branch}', account = '{$account}', uid = {$uid}, status = {$status}, pay_title = '{$pay_title}', payee = '{$payee}'";
            if ($qrcode) {
                $set .= ",qrcode = '{$qrcode}'";
            }
            $set .= ",remark = '{$remark}'";
            $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";
        } else {
            $uptime = date('Y-m-d H:i:s');
            $msg_text = get_tips(8036);
            $sql = "INSERT INTO {$table}(`uid`, `pay_type`, `pay_title`, `payee`, `account`, `bank`, `branch`, `qrcode`, `currency`, `uptime`, `status`, `remark`) VALUE({$uid}, {$type}, '{$pay_title}', '{$payee}' , '{$account}', '{$bank}', '{$branch}', '{$qrcode}', '{$currency}', '{$uptime}', {$status}, '{$remark}') ";
        }
        $result = $this->db->query($sql)->affected_rows();
        $return_msg = $msg_text . get_tips(1023);
        $return_status = ERROR;
        if ($result) {
            $return_msg = $msg_text . get_tips(1024);
            $return_status = SUCCESS;
        }
        ajax_return($return_status, $return_msg);
    }

    /**
     * 汇率列表 exchange_rate_list
     *
     * @return void
     */
    public function exchange_rate_list()
    {
        $table = 'cat_currency_' . DATABASESUFFIX;
        $fields = '*';
        // 处理参数
        $page = intval($_POST['page'] ?: 1);
        $page_size = 10;
        $limit = (($page - 1) * $page_size);
        $foreigne_exchange = sql_format($_POST['foreigne_exchange'] ?? 'all');
        $currency = sql_format($_POST['currency'] ?? 'all');
        //处理条件
        $where = '1';
        $where .= $foreigne_exchange !== '' && $foreigne_exchange !== 'all' ? " and currency = '{$foreigne_exchange}'" : '';
        $where .= $currency && $currency !== 'all' ? " and currency = '{$currency}'" : '';
        // 查询数据
        $sql = "SELECT {$fields} FROM {$table} WHERE {$where} ORDER BY uptime DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        $count_sql = "SELECT COUNT(id) AS num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->row_array()['num'];
        foreach ($list as &$val) {
            $val['eurc_exchange_rate'] = round($val['eurc_exchange_rate']);
        }
        // 返回数据
        ajax_return(SUCCESS, '', array(
            'data' => $list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }


    /**
     * 汇率详情 exchange_rate_info
     *
     * @return void
     */
    public function exchange_rate_info()
    {
        $table = 'cat_currency_' . DATABASESUFFIX;
        $fields = '*';
        // 处理参数
        $id = intval($_POST['id'] ?? 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        $sql = "SELECT {$fields} FROM {$table} WHERE id = {$id}";
        $info = $this->db->query($sql)->row_array();
        $info['eurc_exchange_rate'] = round($info['eurc_exchange_rate']);
        ajax_return(SUCCESS, '', $info);
    }

    /**
     * 修改汇率 save_exchange_rate
     *
     * @return void
     */
    public function save_exchange_rate()
    {
        $table = 'cat_currency_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'currency' => 'must|char@len:5',
            'eurc_exchange_rate' => 'must|decimal',
        );

        $msg = array(
            'currency' => get_tips(8038),
            'currency.len' => get_tips(8039),
            'eurc_exchange_rate' => get_tips(8040),
            'eurc_exchange_rate.decimal' => get_tips(8041),
            'msq_exchange_rate' => get_tips(8042),
            'msq_exchange_rate.decimal' => get_tips(8043),
        );

        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }

        //处理数据
        $currency = $data['currency'];
        $eurc_exchange_rate = $data['eurc_exchange_rate'];
        $msq_exchange_rate = $data['msq_exchange_rate'];
        $uptime = date('Y-m-d H:i:s');
        $remark = $data['remark'] ?? '';
        $status = $data['status'];

        if (isset($data['id']) && $data['id']) {
            $msg_text = get_tips(8037);
            $id = $data['id'];
            $set = "currency = '{$currency}', eurc_exchange_rate = '{$eurc_exchange_rate}', msq_exchange_rate = '{$msq_exchange_rate}'";
            if ($remark) {
                $set .= ",remark = '{$remark}'";
            }
            $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";
        } else {
            $msg_text = get_tips(8036);
            $sql = "INSERT INTO {$table}(`currency`, `eurc_exchange_rate`, `msq_exchange_rate`, `uptime`, `status`) VALUE('{$currency}', '{$eurc_exchange_rate}', '{$msq_exchange_rate}', '{$uptime}', {$status})";
        }

        $result = $this->db->query($sql)->affected_rows();
        if ($result) {
            $return_msg = $msg_text . get_tips(1024);
            $return_status = SUCCESS;
        }
        ajax_return($return_status, $return_msg);
    }

    /**
     * 修改法币状态 save_legal_currency_status
     *
     * @return void
     */
    public function save_legal_currency_status()
    {
        $table = 'cat_currency_' . DATABASESUFFIX;
        // 参数处理
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        $sql = "UPDATE {$table} SET `status` = {$status} WHERE id = {$id}";
        $result = $this->db->query($sql)->affected_rows();
        $return_msg = get_tips(1004);
        $return_status = ERROR;
        if ($result) {
            $return_msg = get_tips(1005);
            $return_status = SUCCESS;
        }
        ajax_return($return_status, $return_msg);
    }

    /**
     * 修改法币配置 save_legal_currency
     *
     * @return void
     */
    public function save_legal_currency()
    {
        $table = 'cat_currency_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'title' => 'must|char@len:10',
            'currency' => 'must|char@len:5',
            'type' => 'must|char@len:10',
            'symbol' => 'must|char@len:4',
        );

        $msg = array(
            'currency' => get_tips(8044),
            'currency.len' => get_tips(8045),
            'title' => get_tips(8046),
            'type' => get_tips(8047),
            'type.len' => get_tips(8048),
            'title.len' => get_tips(8049),
            'symbol' => get_tips(8050),
            'symbol.len' => get_tips(8051),
        );
        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
        }
        //处理参数
        $title = $data['title'];
        $currency = $data['currency'];
        $type = $data['type'];
        $symbol = $data['symbol'];
        if (isset($data['id']) && $data['id']) {
            $msg_text = get_tips(8037);
            $id = $data['id'];
            $set = "title = '{$title}', currency = '{$currency}', type = '{$type}', symbol = '{$symbol}'";
            $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";
        } else {
            $msg_text = get_tips(8036);
            $sql = "INSERT INTO {$table}(`title`, `currency`, `type`, `symbol`) VALUE('{$title}', '{$currency}', '{$type}', '{$symbol}')";
        }
        $result = $this->db->query($sql)->affected_rows();
        $return_msg = get_tips(1004);
        $return_status = ERROR;
        if ($result) {
            $return_msg = $msg_text . get_tips(1024);
            $return_status = SUCCESS;
        }
        ajax_return($return_status, $return_msg);
    }

    /**
     * 上传凭证 upload_voucher
     *
     * @return void
     */
    public function upload_voucher()
    {
        $table = 'trade_buy_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $role = array(
            'fileList' => 'must|array',
            'remarks' => 'must|char',
            'id' => 'must|int'
        );

        $msg = array(
            'fileList' => get_tips(8052),
            'remarks' => get_tips(8053),
            'id' => get_tips(8054),
        );

        // 验证器验证
        $validate_msg = validate($data, $role, $msg);
        if ($validate_msg) {
            ajax_return(ERROR, $validate_msg);
            exit;
        }

        $id = $data['id'];

        // 查询订单数据
        $sql = "SELECT a.id, a.order_id, a.sell_id, a.sell_uid, a.buy_uid,a.coin, a.amount AS buy_amount,a.status FROM trade_buy_" . DATABASESUFFIX . " a WHERE a.id = $id";
        $order_data = $this->db->query($sql)->row_array();
        if (!$order_data) {
            ajax_return(ERROR, get_tips(9010));
        }

        $pic = implode('||', $data['fileList']);
        $remark = $data['remarks'];
        $id = $data['id'];

        $sql = "UPDATE {$table} SET `pic` = '{$pic}', `memo` = '{$remark}', `status` = {$data['status']}, appeal = 2 WHERE id = {$id}";
        $result = $this->db->query($sql)->affected_rows();

        $return_msg = get_tips(1004);
        $return_status = ERROR;
        if ($result) {
            $sell_id = $order_data['sell_id'];
            if ($sell_id == -1) {
                // 系统订单
                if ($data['status'] == self::STATUS_DEAL) {
                    $buy_uid = $order_data['buy_uid'];
                    $coin = $order_data['coin'];
                    $amount = $order_data['buy_amount'];
                    // 买家增加
                    $this->changeAmount($buy_uid, $coin, $amount);
                }
            } else {
                $sql = "SELECT price, closed_amount, remain FROM trade_sell_" . DATABASESUFFIX . " WHERE id = $sell_id";
                $row = $this->db->query($sql)->row_array();
                $order_data = array_merge($order_data, $row);
                // 普通订单
                if ($data['status'] == self::STATUS_DEAL) {
                    $buy_uid = $order_data['buy_uid'];
                    $sell_uid = $order_data['sell_uid'];
                    $coin = $order_data['coin'];
                    $amount = $order_data['buy_amount'];
                    // 买家增加
                    $this->changeAmount($buy_uid, $coin, $amount);
                    // 卖家减少冻结余额
                    $this->changeFreezeAmount($sell_uid, $coin, $amount * -1);

                    // 更新卖单状态
                    $remain = $order_data['remain'] - $order_data['buy_amount'];
                    $max_buy = number_format($remain * $order_data['price'], MONEY_DECIMAL_DIGITS, ".", "");
                    $sql = "UPDATE trade_sell_" . DATABASESUFFIX . " SET closed_amount = closed_amount + $amount, status = 3, max_buy = $max_buy, remain = $remain, trade_num = trade_num + 1,last_uptime = CURRENT_TIMESTAMP WHERE id = $sell_id";
                    $this->db->query($sql);
                } else {
                    // 更新卖单状态
                    $sql = "UPDATE trade_sell_" . DATABASESUFFIX . " SET status = CASE WHEN remain = amount THEN 0 ELSE 3 END WHERE id = $sell_id";
                    $this->db->query($sql);
                }
            }

            $return_msg = get_tips(1005);
            $return_status = SUCCESS;
        }
        ajax_return($return_status, $return_msg);
    }

    private function changeAmount($uid, $coin, $amount)
    {
        $coin = strtolower($coin);
        // 增加买家相应的余额
        $pipe = $this->redis->pipeline();
        $pipe->hIncrByFloat("user:$uid", $coin . "_balance", $amount);
        // 触发更新买家的余额数据
        $pipe->sAdd(RedisKey::USER_BALANCE_UPDATE, $uid);
        $pipe->exec();
    }

    private function changeFreezeAmount($uid, $coin, $amount)
    {
        $coin = strtolower($coin);
        // 增加买家相应的余额
        $pipe = $this->redis->pipeline();
        $pipe->hIncrByFloat("user:$uid", $coin . "_freeze", $amount);
        // 触发更新买家的余额数据
        $pipe->sAdd(RedisKey::USER_BALANCE_UPDATE, $uid);
        $pipe->exec();
    }

    /**
     *  成功付款后处理上分
     *  @param $money int paid money
     *  @param $order_id string order id
     *  @param $transact_id 四方订单号
     *  @param $chkIp boolean if needs to check ip whitelist
     *  @return array
     *  @author 孙悟空
     */
    private function postProcessAfterPaid(float $money = 0.00, string $order_id = '', string $transact_id = '', bool $chkIp = FALSE)
    {
        $ip = get_client_ip(0);

        $this->init_db();
        $sql = "SELECT id, uid, amount, money, payment_config_id, sm_uid, channel_id, partner_id, vip_type, vip_id from pay_deposit_1 WHERE order_id = '{$order_id}' AND `money` = {$money}";
        $result = $this->db->query($sql)->row_array();

        if ($result) {
            if ($money != $result['money']) {
                // 金额错误
                @file_put_contents(ERRLOG_PATH . '/postProcessAfterPaid_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s', time()) . "incorrect money compared the data in database:$money - " . $result['money'] . " \n",
                    FILE_APPEND);
                ajax_return(ERROR, '金额错误');
            }

            $this->redis->set("user:charge:$order_id", 1);

            $id = $result['id'];
            $uid = $result['uid'];
            $sm_uid = $result['sm_uid'];
            $channel_id = $result['channel_id'];
            $partner_id = $result['partner_id'];
            $bonus = 0;
            $total = $result['amount'];
            $pay_time = date('Y-m-d H:i:s', time());
            // $channel_name = self::getPaymentMerchantName( $result['payment_config_id'] );

            // 更新订单表的支付状态  支付时间   四方平台的订单号
            $sql = '' === $transact_id 
                    ? "UPDATE pay_deposit_1 SET bonus = $bonus, total = $total, status = 1, pay_time = '{$pay_time}', transact_id='{$transact_id}' WHERE order_id = '{$order_id}'"
                    : "UPDATE pay_deposit_1 SET bonus = $bonus, total = $total, status = 1, pay_time = '{$pay_time}' WHERE order_id = '{$order_id}'";
            $this->db->query($sql);

            // 更新用户的金币
            $pipe = $this->redis->pipeline();
            $pipe->hincrbyfloat("user:$uid",'money', $money);
            $pipe->hincrbyfloat("user:$uid",'deposit_total', $money);
            $pipe->sadd("user:balance:update", $uid);

            // 渠道统计
            $day = date('Ymd');
            if ($channel_id) {
                $pipe->zIncrBy("ch:dep1:$channel_id", $money, $day);
                $pipe->sadd("ch:dep2:$channel_id:$day", $uid);
            }
            if ($partner_id) {
                $pipe->zIncrBy("pn:dep1:$partner_id", $money, $day);
                $pipe->sadd("pn:dep2:$partner_id:$day", $uid);
            }
            if ($sm_uid) {
                $pipe->zIncrBy("sm:dep1:$sm_uid", $money, $day);
                $pipe->sadd("sm:dep2:$sm_uid:$day", $uid);
                if ($tid) {
                    $pipe->zIncrBy("st:dep1:$tid", $money, $day);
                    $pipe->sadd("st:dep2:$tid:$day", $uid);
                }
            }

            $pipe->zIncrBy("stat:dep1", $money, $day);
            $pipe->sadd("stat:dep2:$day", $uid);

            $pipe->exec();

            // 幫用戶直接買幣
            $orderResult = $this->switchOrderMethod($uid, $money, $order_id);
            if ($orderResult) {
                ajax_return(SUCCESS, '成功上分');
            } else {
                @file_put_contents(ERRLOG_PATH . '/postProcessAfterPaid_' . date("Ymd") . '.log',
                    date('Y-m-d H:i:s', time()) . " please check logs in pay_ ! \n",
                    FILE_APPEND);
                $this->redis->del("user:charge:$order_id");
                ajax_return(ERROR, '上分失败' );
            }
            
        }
        @file_put_contents(ERRLOG_PATH . '/postProcessAfterPaid_' . date("Ymd") . '.log',
            date('Y-m-d H:i:s', time()) . " empty result :" . json_encode($result, true) ." \n",
            FILE_APPEND);
        ajax_return(ERROR, '数据库中没有找到相关记录');
    }

    private function switchOrderMethod($uid, $money, $orderId)
    {
        $biccStatus     = false;
        if( !$this->redis->hExists('backend:bicc:switch', 'bicc') )
                $this->redis->hSet('backend:bicc:switch', 'bicc', 0);
        $switch     = $this->redis->hget('backend:bicc:switch', 'bicc');

        if( $switch ) {
            try {
                @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        'Starting Bicc Order Process... ' . "\n", 
                        FILE_APPEND);
                $biccStatus = $this->order($uid, $money, $orderId);
            } catch (Exception $e) {
                @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        'Error Caught... '. $e->getMessage() . "\n", 
                        FILE_APPEND);
                return false;
            }
        }

        if ( !$biccStatus ) {
            try {
                $biccStatus  = $this->orderWithoutBicc(['uidTo' => $uid, 'money'=>$money, 'orderId'=> $orderId], 1001);
                @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        'Starting Manual Order Process... ' . "\n", 
                        FILE_APPEND);
            } catch (Exception $e) {
                @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        'Error Caught... '. $e->getMessage() . "\n", 
                        FILE_APPEND);
                return false;
            }
            
        }

        return $biccStatus;
    }

    /**
     * 购买入库
     * @param string $uid 用户id
     * @param int $money 用户金额
     * @param string $channel 充值渠道
     * @return bool
     */
    private function order($uid = '', $money = 0,$channel="")
    {

        $appid = $this->appid;

        $this->init_db();


        if (!$uid || !$money) {
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log',
                "money $money or uid error: $uid  ". "\n", 
                FILE_APPEND);
            return false;
        }

        // SimpleCurl::prepare($this->market_host . '/openapi/quote/v1/ticker/price', ['symbol' => $this->market_symbol]);
        // SimpleCurl::exec_get();
        // $priceQuery = SimpleCurl::get_response_assoc();
        // $price = $priceQuery['price'] ?? 0.075;

        $amount= round(($money / $this->usdt_cny), 2);
        $headers    = ['X-BH-APIKEY:' . $this->market_api_key];

        $data       = [
            'symbol'        => $this->market_symbol,
            // quantity * price = amount 此处传美元
            'quantity'      => $amount,
            // 订单方向, BUY/SELL
            'side'          => 'BUY',
            // 订单类型, LIMIT/MARKET/LIMIT_MAKER
            'type'          => 'MARKET',
            // 订单时间指令（Time in Force）。可能出现的值为：GTC（Good Till Canceled，一直有效），FOK（Fill or Kill，全部成交或者取消），IOC（Immediate or Cancel，立即成交或者取消）
            'timeInForce'   => 'GTC',
            // 当前时间戳（毫秒）
            'timestamp'     => self::getMilliSecond(),
            // 时效 5000毫秒
            'recvWindow'    => 5000,
        ];



        $requestBody = self::getMarketQuerySignature('sha256', $data, $this->market_secret_key, true);

        SimpleCurl::prepare($this->market_host . '/openapi/v1/order?'.$requestBody, [], $headers);
        SimpleCurl::exec_post();
        $resp = SimpleCurl::get_response();


        if (false === $resp) {
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log',
                'ERROR IN CURL: FALSE RESPONSE FROM SERVER' . "\n",
                FILE_APPEND);
            return false;
        } else {
            $resp = json_decode($resp, true);


            if ( isset($resp['code']) ) {

                @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    'ERROR IN CURL WHEN CHECKING ORDER: '. $resp['msg'] .  "ERROR CODE: ". $resp['code'] ."\n", 
                    FILE_APPEND);
                return false;
            } else {

                if('NEW' == $resp['status'] ) {
                    $order_id   = $resp['orderId'];
                    $bbamount   = coin_format($resp['executedQty'], 4);

                    $this->db->trans_begin();

                    $sql        = "insert into trade_buy_bicc_$appid (uid, money, amount, price, order_id) values ($uid, $money, 0, 0, '$order_id')";
                    $tradeID = $this->db->query($sql);

                    $balance = floatval($this->redis->hget("user:$uid", "eurc_balance"));
                    $balance += $amount;
                    $pipe = $this->redis->pipeline();
                    $pipe->hincrbyfloat("user:$uid",'money', - $money);
                    // add balance when check_order()
                    // $pipe->hset("user:$uid", "eurc_balance", $balance);
                    $pipe->sadd("user:balance:update", $uid);
                    $pipe->exec();

                    // $sql = "insert into trade_deposit_$appid (uid, coin, amount, balance, addr_balance, from_addr, txn_hash, confirm, status) values ($uid, '', $amount, $balance, 0, '', 0, 0, 1)";
                    // $this->db->query($sql);
                    // 检查是否为用户首次充值
                    $chkFirstSql = "SELECT COUNT(id) as numOfFirstOrder FROM pay_deposit_$appid WHERE uid={$uidTo} and status=1";
                    $chkFirstRet = $this->db->query($chkFirstSql)->row_array()['numOfFirstOrder'];

                    $isFirstOrder = $chkFirstRet > 0 ? 0 : 1;

                    // 添加历史记录
                    $sqllog = "INSERT INTO recharge_history_log_$appid (`uid`, `order_id`, `paid_money`, `baby_amount`, `balance`, `is_first_order`) 
                                                    VALUES ({$uidTo}, '{$orderId}', '{$money}', '{$bbamount}', '{$balance}', {$isFirstOrder})";

                    // $sql = "INSERT INTO recharge_history_log_$appid (`uid`, `order_id`, `paid_money`, `baby_amount`, `balance`) 
                    //                             VALUES ({$uid}, '{$order_id}', '{$money}', '{$bbamount}', '{$balance}')";
                    $historyID = $this->db->query($sqllog);

                    // 加入打码量的统计
                    self::add_or_reduce_calc_total_baby_coin(1, $uid, 0, $bbamount);
                    
                    if( $tradeID && $historyID ) {
                        $this->db->trans_commit();
                        return true;
                    } else {
                        $this->db->trans_rollback();
                        @file_put_contents(ERRLOG_PATH . '/recharge_history_log_' . date("Ymd") . '.log',
                            'failed to insert into recharge_history_log_: ' . $sql . "\n",
                            FILE_APPEND);
                        return false;
                    }
                } else {
                    @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        ' wrong response status ....' . $resp['status'] . "\n", 
                        FILE_APPEND);
                    return false;
                }
            }
        }
    }

    /**
     *  当交易所购买失败的时候，执行内部交易
     *  @param $input       array  ['uidTo', 'money', 'orderId' ]
     *  @param $userFromId      int    平台币的资金池地址的用户id, 1001为admin用户的id
     *
     *  @return bool
     *  @author 孙悟空
     */
    private function orderWithoutBicc(array $input, $userFromId = 1001)
    {

        $appid      = DATABASESUFFIX;

        $this->init_db();
        $uidTo  = sql_format($input['uidTo'] ?? '');
        $money  = intval($input['money'] ?? 0);
        $orderId = $input['orderId'] ?? 0;
        $status = 0;
        $sql = "SELECT addr FROM trade_wallet_$appid WHERE uid = $uidTo";
        $userTo = $this->db->query($sql)->row_array();

        if( ! $userTo ) {
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s') . "userTo not found in trade_wallet_$appid" . "\n", 
                    FILE_APPEND);
            return false;
        }
        $addrTo = $userTo['addr'];

        // 查询最近一次记录使用的汇率，并按此汇率转成baby币数量
        // 为了防止取到的最近一条成交价格是错误价格，取前15条平均

        // $sql    = "SELECT AVG(price) as avgPrice
        //                 FROM(
        //                     SELECT money, amount, AVG(money/amount) as price
        //                     FROM trade_buy_bicc_$appid 
        //                     WHERE amount > 0 AND status =1
        //                     GROUP BY id
        //                     HAVING price > 0 AND price < 1
        //                     ORDER BY uptime DESC LIMIT 5
        //                 ) as avgs";
        // $lastTradeRecord = $this->db->query($sql)->row_array();
        // $lastRate   = number_format($lastTradeRecord['avgPrice'] , 2, '.', '');
        // $lastRate   = $lastRate != 0 ? $lastRate : '0.26';

        // 暫時将交易的汇率定为0.25
        $lastRate   = $this->redis->get('payment:baby_price') ?? 1;
        $amount     = number_format($money/$lastRate, 2, '.', '');

        if (! $addrTo || ! $amount )  {
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s') . "addrTo not exists: $addrTo, or amount: $amount " . "\n", 
                    FILE_APPEND);
            return false;
        }
        if (strlen($addrTo) != 42 || substr($addrTo, 0, 2) != '0x')  {
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s') . "input wrong format addrTo: $addrTo \n", 
                    FILE_APPEND);
            return false;
        }

        // 查询$userFrom账户余额
        $balance = $this->redis->hget("user:$userFromId", 'eurc_balance');

        if ($balance < $amount)  {
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s') . "Balance ERROR: $balance - $amount" . "\n", 
                    FILE_APPEND);
            return false;
        }
        $balance -= (int)$amount;

        // 获取资金池用户地址并查询自己是否有钱包地址
        $sql = "SELECT addr, qrcode FROM trade_wallet_$appid WHERE uid = $userFromId";
        $walletFrom = $this->db->query($sql)->row_array();

        if (!$walletFrom || ($walletFrom['addr'] == $addrTo) ) {

            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s') . "input same addrTo and addrFrom: $addrTo " . "\n", 
                    FILE_APPEND);
            return false;
        }

        // 判斷是不是內部轉幣
        if ($userTo) {
            $pipe = $this->redis->pipeline();
            $pipe->hIncrByFloat("user:$userFromId",  'eurc_balance', - $amount);
            $pipe->hIncrByFloat('user:'.$uidTo, 'eurc_balance', $amount);
            $pipe->hincrbyfloat("user:$uidTo",'money', - $money);
            $pipe->sAdd("user:balance:update", $userFromId);
            $pipe->sAdd("user:balance:update", $uidTo);
            $pipe->exec();
            $status = 1;
        } else {

            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                    date('Y-m-d H:i:s') . "User Not Found: userTo:$addrTo - userId:$uidTo" . "\n", 
                    FILE_APPEND);
            return false;
        }

        // 检查是否为用户首次充值
        $chkFirstSql = "SELECT COUNT(id) as numOfFirstOrder FROM pay_deposit_$appid WHERE uid={$uidTo} and order_id<>'{$orderId}' and status=1";
        $chkFirstRet = $this->db->query($chkFirstSql)->row_array()['numOfFirstOrder'];
        $isFirstOrder = $chkFirstRet > 0 ? 0 : 1;

        $this->db->trans_begin();
        try {
            // 记录提币记录, fee = -1，意味是这里去的
            $admin_time = date('Y-m-d H:i:s', time());
            $sql = "INSERT INTO trade_withdraw_$appid (`uid`, `coin`, `to_addr`, `amount`, `fee`, `net_amount`, `gas`, `audit`, balance, status, `admin_time`) VALUES ($userFromId, '', '$addrTo', $amount, -1, $amount, 0, 1, $balance, $status, '{$admin_time}')";
            $insert_id = $this->db->query($sql)->insert_id();

            
            // 添加历史记录
            $sqllog = "INSERT INTO recharge_history_log_$appid (`uid`, `order_id`, `paid_money`, `baby_amount`, `balance`, `is_first_order`) 
                                                    VALUES ({$uidTo}, '{$orderId}', '{$money}', '{$amount}', '{$balance}', {$isFirstOrder})";
            $historyID = $this->db->query($sqllog)->insert_id();

            // 加入打码量的统计
            self::add_or_reduce_calc_total_baby_coin(1, $uidTo, 0, $amount);
 
            if ( empty($insert_id) || empty($historyID) ) {
                $this->db->trans_rollback();
                @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        date('Y-m-d H:i:s') . "empty result returned insert_id $insert_id historyID $historyID - \n $sql \n $sqllog" . "\n", 
                        FILE_APPEND);
                return false;
            } else {
                $this->db->trans_commit();
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            @file_put_contents(ERRLOG_PATH . '/pay_' . date("Ymd") . '.log', 
                        date('Y-m-d H:i:s') . "Exception caught $sql $sqllog ". $e->getMessage() . "\n", 
                        FILE_APPEND);
            return false;
        }      

        return true;
    }

    /**
     * 统计累计打码量和累计充值量
     * @param $appid :appId
     * @param $uid :用户id
     * @param $code_amount :需要加的打码消费额
     * @param $recharge_amount :需要加的充值额
     */
    private function add_or_reduce_calc_total_baby_coin($appid = 1, $uid = 0, $code_amount = 0, $recharge_amount = 0)
    {
        if ($code_amount) {
            $code_amount = floatval($code_amount);
        }

        if ($recharge_amount) {
            $recharge_amount = floatval($recharge_amount);
        }

        if ($uid) {
            $check_sql = "select id,code_amount,recharge_amount from count_code_recharge_amount_{$appid} where uid={$uid}";

            $check_info = $GLOBALS['db']->query($check_sql)->row_array();


            if ($check_info) {
                //存在记录，新增
                $update_filed = "";
                if ($code_amount) {
                    $update_filed .= "code_amount=code_amount+$code_amount,";
                }

                if ($recharge_amount) {
                    $update_filed .= "recharge_amount=recharge_amount+$recharge_amount,";
                }

                $update_filed .= "uid={$uid}";
                $sql = "update count_code_recharge_amount_{$appid} set {$update_filed} where uid={$uid}";

            } else {
                //不存在记录，插入
                $insert_values='';
                $insert_field = "`code_amount`,`recharge_amount`,`uid`";
                if ($code_amount && !$recharge_amount) {
                    $insert_values = "{$code_amount},0,{$uid}";
                }

                if (!$code_amount && $recharge_amount) {
                    $insert_values = "0,{$recharge_amount},{$uid}";
                }

                if ($code_amount && $recharge_amount) {
                    $insert_values = "{$code_amount},{$recharge_amount},{$uid}";
                }

                $sql = "INSERT INTO count_code_recharge_amount_{$appid} ({$insert_field}) VALUES ({$insert_values})";
            }

            $GLOBALS['db']->query($sql)->affected_rows();
        }
    }
}
