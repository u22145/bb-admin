<?php

/**
 * 数据统计
 */
class Stat extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 每日用戶数据
     */
    public function stat_user()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';

        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;

        if ($day_start && $day_end) {
            $day_start = date('Ymd', strtotime($day_start));
            $day_end = date('Ymd', strtotime($day_end));
        } else {
            $day_start = date('Ymd', strtotime('-30 days'));
            $day_end = date('Ymd');
        }

        $reg = 0;
        $active = 0;
        $dep1 = 0;
        $dep2 = 0;
        $online = 0;

        $pipe = $this->redis->pipeline();
        $pipe->zrange("stat:user:reg", 0, -1, true);
        $pipe->zrange("stat:user:active", 0, -1, true);
        $pipe->zrange("stat:dep1", 0, -1, true);
        $res1 = $pipe->exec();

        $res = [];
        for ($i = $day_end; $i >= $day_start; $i--) {
            $day = substr($i, 0, 4) . '-' . substr($i, 4, 2) . '-' . substr($i, 6, 2);
            if (0 != substr($i, 6, 2) && strtotime($day)) {
                $res[$i]['day'] = $day;
                $res[$i]['reg'] = $res1[0][$i] ?? 0;
                $res[$i]['active'] = $res1[1][$i] ?? 0;
                $res[$i]['dep1'] = $res1[2][$i] ?? 0;
                $res[$i]['dep2'] = $this->redis->scard("stat:dep2:$i");
                $res[$i]['online'] = $this->redis->hlen("user:online:$i");
                $reg += $res[$i]['reg'];
                $active += $res[$i]['active'];
                $dep1 += $res[$i]['dep1'];
                $dep2 += $res[$i]['dep2'];
                $online += $res[$i]['online'];
                $yes = date('Ymd',strtotime("$i - 1 day"));
                $bf_yes = date('Ymd',strtotime("$i - 2 day"));
                $yes_res = $this->redis->hgetall("user:online:$yes");
                $bf_res = $this->redis->hgetall("user:online:$bf_yes");
                $num = 0;
                $res[$i]['online_rate'] = '0%';
                if ($bf_res) {
                    foreach ($bf_res as $key => $value) {
                        if (array_key_exists($key, $yes_res)) {
                            $num += 1;
                        }
                    }
                    if ($bf_num = count($bf_res)) { 
                        $res[$i]['online_rate'] = number_format(($num / $bf_num) * 100, MONEY_DECIMAL_DIGITS, '.', '') . '%';
                    }
                }
            }
        }

        // 分頁
        $total = count($res);
        $res = array_slice($res, $offset, $page_size);

        $sumary = ['reg' => $reg, 'dep1' => $dep1, 'active' => $active, 'dep2' => $dep2, 'online' => $online];
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }

    /**
     *  用戶統計
     */
    public function user_statistics()
    {   
        $page_no = intval($_POST['page_no'] ?? 1);        // 頁數
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE);   // 每頁的條數
        $start = ($page_no - 1) * $page_size;
        $content = strval($_POST['content'] ?? '');        // 暱稱和id

        // 每日註冊人數
        $dt = date('Ymd');
        $week = date('YW');
        $reg_all = intval($this->redis->zscore('stat:user:reg', $dt));  // 註冊用戶

        // 日活用戶
        $user_active = intval($this->redis->hlen("user:online:$dt")); 

        // 直播間收益
        $room_day = $this->redis->zrevrange("rank:gds:eurc:$dt", 0, -1, true);       // 直播間日榜
        $room_day = array_sum($room_day);
        $room_week = $this->redis->zrevrange("rank:gds:eurc:$week", 0, -1, true);    // 直播間周榜
        $room_week = array_sum($room_week);
        $room_all = $this->redis->zrevrange("rank:gds:eurc:all", 0, -1, true);       // 直播間總榜
        $room_all = array_sum($room_all);

        // 平台收益
        $sql = "select id from sociaty_1";
        $res = $this->db->query($sql)->result_array();
        $boss_day = 0;
        $boss_week = 0;
        $boss_all = 0;  
        $w = $this->get_week();
        $res[] = ['id' => 0];
        foreach ($res as $key => $value) {
            $data= $this->redis->hgetall('soc:eurc:boss:'.$value['id']);
            if (array_key_exists($dt, $data)) {
                $boss_day += $data[$dt];                   // 平台每天的收益
            }
            $boss_all += array_sum($data);                 // 總的平台收益
            // 每週平台收益
            if ($data) {
                $s_week = str_replace('-','',$w['now_start']);
                $e_week = str_replace('-','',$w['now_end']);
                foreach ($data as $k => $val) {
                    if ($k >= $s_week && $k <= $e_week) {
                        $boss_week += $val;
                    }
                }
            }
        }

        // 每日觀看付費內容  個數/金額
        $day = date('Y-m-d');
        $sql = "select count(id) num, sum(fee) fee_total from exp_log_1 where type in(4,5) and uptime >= '$day 00:00:00' and uptime <= '$day 23:59:59'";
        $res = $this->db->query($sql)->result_array();
        $day_exp_num = intval($res[0]['num']);              // 每日觀看付費內容的人數
        $day_exp_fee = intval($res[0]['fee_total']);        // 每日觀看內容的總金額

        // 開通會員人數
        $s_week = $w['now_start'];
        $e_week = $w['now_end'];
        $sql = "select count(id) num from exp_vip_1 where vip_type = 1 and uptime >= '$s_week 00:00:00' and uptime <= '$e_week 23:59:59'";
        $res = $this->db->query($sql)->result_array(); 
        $day_vip = $res[0]['num'];                                             // 當周購買vip的
        $sql = "select count(id) num from exp_vip_1 where vip_type = 1 and uptime >= '$day 00:00:00' and uptime <= '$day 23:59:59'";
        $res = $this->db->query($sql)->result_array(); 
        $week_vip = $res[0]['num'];                                            // 當周所有購買vip的
        $all_vip = $this->redis->zcard('user:vip');                            // 所有購買vip的人

        // 用戶充值
        $user_day_dps = intval($this->redis->scard("stat:dep2:$dt"));          // 每天用戶充值人數
        $sql = "select count(id) num from pay_deposit_1 where status = 1";
        $user_all_dps = $this->db->query($sql)->row_array()['num'];            // 總的充值用戶

        // 各檔位等級人數
        $sql = "select uid from pay_deposit_1 where status = 1";
        $res = $this->db->query($sql)->result_array();
        $five_sum = 0;
        $ten_sum = 0;
        $fif_sum = 0;
        $twen_sum = 0;
        $twen_five_sum = 0;
        $thirty_sum = 0;
        foreach ($res as $key => $value) {
            $level = $this->redis->hget('user:'.$value['uid'], 'level');
            if ($level <= 5) {
                $five_sum++;
            } else if ($level <= 10) {
                $ten_sum++;
            } else if ($level <= 15) {
                $fif_sum++;
            } else if ($level <= 20) {
                $twen_sum++;
            } else if ($level <= 25) {
                $twen_five_sum++;
            } else {
                $thirty_sum++;
            }
        }

        $where = 1;
        // 搜索
        if ($content) {
            $where = is_numeric($content) ? "id = $content" : "nickname like '%$content%'";
        }
        $sql = "select id, nickname from user_1_0 where $where order by id desc limit $start, $page_size";
        $res = $this->db->query($sql)->result_array();
        foreach ($res as &$val) {
            $redis_arr = $this->redis->hMget('user:'.$val['id'], ['money', 'level', 'is_agent', 'eurc_balance']);
            $val['level'] = intval($redis_arr['level']);
            $val['money'] = intval($redis_arr['money']);
            $val['eurc_balance'] = $redis_arr['eurc_balance'];
            if (isset($redis_arr['is_agent']) && $redis_arr['is_agent']) {
                $val['is_vip'] = '是';
            } else {
                $val['is_vip'] = '否';
            }
            $val['exp_eurc_balance'] = $this->redis->zScore('rank:rich:eurc:all', $val['id']);
            $val['exp_eurc_balance'] = $val['exp_eurc_balance'] ? $val['exp_eurc_balance'] / MONEY_DECIMAL_MULTIPLE : 0;
            $val['online_time'] = intval($this->redis->hget("user:online:$dt", $val['id']));
            unset($val['vip_expire']);
        }

        $count_sql = "select count(id) num from user_1_0 order by id desc";
        $total = $this->db->query($count_sql)->row_array()['num'];

        $data = [
            'reg_all'       => $reg_all,                                    // 每日註冊用戶數
            'user_active'   => $user_active,                                // 日活用戶 
            'room_day'      => $room_day / MONEY_DECIMAL_MULTIPLE,          // 直播每天總收益
            'room_week'     => $room_week / MONEY_DECIMAL_MULTIPLE,         // 直播每週總收益
            'room_all'      => $room_all / MONEY_DECIMAL_MULTIPLE,          // 直播間所有總收益
            'boss_day'      => $boss_day ? number_format($boss_day, MONEY_DECIMAL_DIGITS, '.', '') : 0,   // 平台每天總收益
            'boss_week'     => $boss_week ? number_format($boss_week, MONEY_DECIMAL_DIGITS, '.', '') : 0,  // 平台每月總收益
            'boss_all'      => $boss_all ? number_format($boss_all, MONEY_DECIMAL_DIGITS, '.', '') : 0,   // 平台所有總收益
            'day_exp_num'   => $day_exp_num,            // 每日觀看付費內容的人數
            'day_exp_fee'   => $day_exp_fee,            // 每日觀看內容的總金額
            'day_vip'       => intval($day_vip),        // 每日開通vip
            'week_vip'      => $all_vip,                // 每月開通vip
            'all_vip'       => $all_vip,                // 總開通vip的
            'user_day_dps'  => $user_day_dps,           // 用戶每日充值
            'user_all_dps'  => intval($user_all_dps),   // 用戶所有總充值
            'five_sum'      => $five_sum,               // 1-5級別的總個數
            'ten_sum'       => $ten_sum,                // 5-10級別的總個數
            'fif_sum'       => $fif_sum,                // 10-15級別的總個數
            'twen_sum'      => $twen_sum,               // 15-20級別的總個數
            'twen_five_sum' => $twen_five_sum,          // 20-25
            'thirty_sum'    => $thirty_sum              // 25-30
        ];

        ajax_return(SUCCESS, '', [
            'list' => $res,
            'total' => intval($total), 
            'data'  => $data,
            'page'  => $page_no, 
        ]);
    }

    /**
     * 獲取當前周的開始和結束時間
     * @return array
     */
    private function get_week()
    {
        $date = date('Y-m-d');                                                                     // 当前日期
        $first = 1;                                                                                // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $w = date('w',strtotime($date));                                                           // 获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $now_start = date('Y-m-d',strtotime("$date -".($w ? $w - $first : 6).' days'));              // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $now_end = date('Y-m-d',strtotime("$now_start +6 days"));                                    // 本周结束日期
        return ['now_start' => $now_start, 'now_end' => $now_end];
    }

    /**
     * 帐目汇总表
     */
    public function stat_summary()
    {
        // 处理参数
        $page       = intval($_POST['page'] ?? 1);
        $page_size  = ADMIN_PAGE_SIZE;
        $limit      = (($page - 1) * $page_size);
        $appid      = DATABASESUFFIX;

        $uid        = intval($_POST['s_uid'] ?? 0);
        $nickname   = str_format($_POST['s_nickname'] ?? '');
        $mobile     = intval($_POST['s_mobile'] ?? 0);
        $time_start = str_format($_POST['s_time'][0] ?? '');
        $time_end   = str_format($_POST['s_time'][1] ?? '');
        // today = UTC + 8，Asia/Shanghai 时区
        $today      = date('Y-m-d', time());

        $search     = intval($_POST['search'] ?? 0);

        // 处理条件
        $where = '1';

        $where .= !empty($uid) ? " AND user.id={$uid}" : '';
        $where .= !empty($nickname) ? " AND user.nickname={$nickname}" : '';
        $where .= !empty($mobile) ? " AND user.mobile={$mobile}" : '';

        // 用户注册，API项目，属于UTC时间
        $whereUser = (!empty($time_start) && !empty($time_end)) 
                        ? " AND DATE_ADD(user.join_date, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(user.join_date, INTERVAL 8 HOUR) < '{$time_end}'" 
                        : " AND DATE(DATE_ADD(user.join_date, INTERVAL 8 HOUR)) = '{$today}'";
        // 送礼，API项目，属于UTC时间
        $whereGift = (!empty($time_start) && !empty($time_end)) 
                        ? " AND DATE_ADD(uptime, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(uptime, INTERVAL 8 HOUR) < '{$time_end}'" 
                        : " AND DATE(DATE_ADD(uptime, INTERVAL 8 HOUR)) = '{$today}'";

        // 提现 admin中处理，属于北京时间, unixtime跟时区无关
        $onCondWr = (!empty($time_start) && !empty($time_end)) 
                        ? " AND from_unixtime(created_at, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(created_at, '%Y-%m-%d %H:%i:%s') < '{$time_end}'" 
                        : " AND DATE( from_unixtime(created_at, '%Y-%m-%d %H:%i:%s') ) = '{$today}'";
        // 人工上下分 admin中处理，属于北京时间
        $onCondOpamr = (!empty($time_start) && !empty($time_end)) 
                        ? " AND from_unixtime(uptime, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(uptime, '%Y-%m-%d %H:%i:%s') < '{$time_end}'" 
                        : " AND DATE(from_unixtime(uptime, '%Y-%m-%d %H:%i:%s')) = '{$today}'";
        // 充值记录 下单时间为API项目，UTC时间 支付时间可能为SHARE项目的北京时间
        $onCondPd = (!empty($time_start) && !empty($time_end)) 
                        ? " AND DATE_ADD(order_time, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(order_time, INTERVAL 8 HOUR) < '{$time_end}'" 
                        : " AND DATE(DATE_ADD(order_time, INTERVAL 8 HOUR)) = '{$today}'";

        // 获取数据
        $sql = "SELECT user.id, user.nickname, user.mobile, (IF(user.vip_expire>NOW(), 1, 0)) AS is_member, user.eurc_balance,
                        pd.user_recharge_amount, wr.user_withdrawal_amount, wr.user_wtf_amount, wr.user_commission, 
                        opamr.user_manual_amount
                    FROM user_1_0 user

                    LEFT JOIN (
                        SELECT uid, created_at, is_deleted, SUM(IF(status=2, amount, 0)) AS user_withdrawal_amount, 
                        SUM(IF(status=2, service_charge, 0)) AS user_wtf_amount, 
                        SUM(IF(status=2, return_money, 0)) AS user_commission
                        FROM withdrawal_record_$appid
                        WHERE is_deleted=0 $onCondWr
                        GROUP BY uid
                    ) wr ON user.id = wr.uid 

                    LEFT JOIN (
                        SELECT uid, uptime, is_deleted, SUM(amount) AS user_manual_amount
                        FROM other_pay_add_money_record_$appid 
                        WHERE is_deleted=0 $onCondOpamr
                        GROUP BY uid
                    ) opamr ON user.id = opamr.uid
                    
                    LEFT JOIN (
                        SELECT uid, order_time, SUM( money ) AS user_recharge_amount
                        FROM pay_deposit_$appid
                        WHERE status=1 $onCondPd
                        GROUP BY uid
                    ) pd ON user.id = pd.uid

                    WHERE $where 
                    ORDER BY user.id DESC 
                    LIMIT {$page_size} OFFSET $limit";

        // $sql = "select * from pay_deposit_" . DATABASESUFFIX . " where 1 $where ORDER BY id DESC limit {$page_size} offset $limit";
        @file_put_contents(ERRLOG_PATH . '/stat_log_' . date("Ymd") . '.log',
                date("Y-m-d H:i:s") ."SQL excuted: $sql " . " \n",
                FILE_APPEND);
        $summary = $this->db->query($sql)->result_array();

        $sqlTotal = "SELECT COUNT(user.id) AS total 
                        FROM user_1_0 user
                        WHERE $where";
        $total = $this->db->query($sqlTotal)->row_array()['total'];

        $index = 1;
        $page_recharge_total = 0;
        $page_withdrawal_total = 0;
        $page_manual_total = 0;
        foreach ($summary as &$row) {
            $row['index']       = $index;
            $row['uid']         = $row['id'];
            $row['user_recharge_amount'] = number_format($row['user_recharge_amount'] ?? 0, 2, '.', '');
            $row['user_withdrawal_amount'] = number_format($row['user_withdrawal_amount'] ?? 0, 2, '.', '');
            $row['eurc_balance'] = number_format($row['eurc_balance'] ?? 0, 2, '.', '');
            $row['user_profit'] = number_format($row['user_recharge_amount'] - $row['user_withdrawal_amount'] - $row['eurc_balance']/4, 2, '.', '');
            // $row['total_profit'] = $row['total_profit'] >= 0 ? '+ ' . $row['total_profit'] : '- ' .abs($row['total_profit']);
            $row['user_manual_amount'] = number_format($row['user_manual_amount'] ?? 0, 2, '.', '');
            $row['user_wtf_amount'] = number_format($row['user_wtf_amount'] ?? 0, 2, '.', '');
            $row['user_commission'] = number_format($row['user_commission'] ?? 0, 2, '.', '');
            $row['is_member']   = $row['is_member'] ? '是' : '否';
            $page_recharge_total += $row['user_recharge_amount'];
            $page_withdrawal_total += $row['user_withdrawal_amount'];
            $page_manual_total += $row['user_manual_amount'];
            $index++;
        }
        // 充值记录 下单时间为API项目，UTC时间 支付时间可能为SHARE项目的北京时间
        $wherePd = (!empty($time_start) && !empty($time_end)) 
                        ? " AND DATE_ADD(pd.order_time, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(pd.order_time, INTERVAL 8 HOUR) < '{$time_end}'" 
                        : " AND DATE(DATE_ADD(pd.order_time, INTERVAL 8 HOUR)) = '{$today}'";
        // 获取方框里面的数据
        $sqlTotalStat = "SELECT COUNT(ev.id) AS registered_user, SUM(pd.user_recharge_amount) AS total_recharge_amount,
                                    SUM(pd.recharge_users) AS total_recharge_users, SUM(pd.user_recharge_amount) AS total_recharge_amount, 
                                    SUM(pd.user_online_amount) AS total_online_amount, SUM(pd.user_offline_amount) AS total_offline_amount, 
                                    SUM(pd.user_first_recharge) AS total_first_users, SUM(pd.user_first_amount) AS total_first_amount,
                                    SUM(wr.total_withdrawal_users) AS total_withdrawal_users, SUM(wr.total_withdrawal_amount) AS total_withdrawal_amount,
                                    SUM(eg.total_gift_num) AS total_gift_num , SUM(ed.total_platform_income) AS total_platform_income,
                                    COUNT(IF(ev.is_first_purchase=1, ev.pay_uid, NULL)) AS first_purchae
                            FROM user_1_0 user
                            LEFT JOIN (
                                SELECT pd.uid, COUNT(DISTINCT pd.uid) AS recharge_users, SUM(pd.money) AS user_recharge_amount, 
                                        SUM(IF(pd.payment_type=1, pd.money, 0)) AS user_online_amount,
                                        SUM(IF(pd.payment_type=2, pd.money, 0)) AS user_offline_amount,
                                        COUNT(IF(rhl.is_first_order=1, pd.uid, NULL)) AS user_first_recharge,
                                        SUM(IF(rhl.is_first_order=1, pd.money, 0)) AS user_first_amount
                                FROM pay_deposit_$appid pd
                                LEFT JOIN recharge_history_log_$appid  rhl
                                ON pd.order_id=rhl.order_id
                                WHERE pd.status=1 $wherePd
                                GROUP BY pd.uid
                            ) pd ON user.id = pd.uid 

                            LEFT JOIN (
                                SELECT uid, SUM( amount ) AS total_withdrawal_amount, 
                                            COUNT(DISTINCT uid) AS total_withdrawal_users
                                FROM withdrawal_record_$appid wr
                                
                                WHERE is_deleted=0 AND status=2 $onCondWr
                                GROUP BY uid
                            ) wr ON user.id = wr.uid 

                            LEFT JOIN (
                                SELECT from_uid, SUM(num) as total_gift_num
                                FROM exp_gift_$appid
                                WHERE 1 $whereGift
                                GROUP BY from_uid
                            ) eg ON user.id = eg.from_uid

                            LEFT JOIN (
                                SELECT uid, SUM(boss_share) AS total_platform_income
                                FROM exp_detail_$appid
                                WHERE 1 $whereGift
                                GROUP BY uid
                            ) ed ON user.id = ed.uid

                            LEFT JOIN (
                                SELECT user.id, ev.pay_uid, ev.is_first_purchase
                                FROM user_1_0 user
                                LEFT JOIN exp_vip_$appid ev
                                ON user.id=ev.pay_uid
                                WHERE $where $whereUser
                                GROUP BY user.id
                            ) ev ON user.id = ev.id

                            WHERE $where ";
        $totalStatRet = $this->db->query($sqlTotalStat)->row_array();
// @file_put_contents(ERRLOG_PATH . '/stat_log_' . date("Ymd") . '.log',
//                 date("Y-m-d H:i:s") ."SQL excuted: $sqlTotalStat " . " \n",
//                 FILE_APPEND);
        $summary_register_users   = $totalStatRet['registered_user'] ?? 0;
        $summary_new_vips         = $totalStatRet['first_purchae'] ?? 0;

        $summary_recharge_users   = $totalStatRet['total_recharge_users'] ?? 0;
        $summary_recharge_amount  = $totalStatRet['total_recharge_amount'] ?? 0;

        $summary_first_users      = $totalStatRet['total_first_users'] ?? 0;
        $summary_first_amount     = $totalStatRet['total_first_amount'] ?? 0;

        $summary_online_amount    = $totalStatRet['total_online_amount'] ?? 0;
        $summary_offline_amount   = $totalStatRet['total_offline_amount'] ?? 0;

        $summary_withdrawal_users = $totalStatRet['total_withdrawal_users'] ?? 0;
        $summary_withdrawal_amount  = $totalStatRet['total_withdrawal_amount'] ?? 0;

        $summary_gift_num = $totalStatRet['total_gift_num'] ?? 0;
        $summary_platform_income = $totalStatRet['total_platform_income'] ?? 0;

        // 充值总计
        $sqlTotalPd = "SELECT SUM(money) AS total_recharge_amount
                            FROM pay_deposit_$appid WHERE status=1    ";
        $total_recharge_amount = $this->db->query($sqlTotalPd)->row_array()['total_recharge_amount'];

        // 提现总计
        $sqlTotalWr = "SELECT SUM( amount ) AS total_manual_withdrawal
                            FROM withdrawal_record_$appid WHERE status=2 AND is_deleted=0";
        $total_withdrawal_amount = $this->db->query($sqlTotalWr)->row_array()['total_manual_withdrawal'];

        // 人工上下分总计
        $sqlTotalOpamr = "SELECT SUM(IF(is_deleted=0, amount, 0)) AS total_manual_cash
                            FROM other_pay_add_money_record_$appid";
        $total_manual_amount = $this->db->query($sqlTotalOpamr)->row_array()['total_manual_cash'];

        // 直播礼物和盈利
        $totalGiftsql = "SELECT SUM(num) as total_gift FROM exp_gift_$appid";
        $total_gift_num = $this->db->query($totalGiftsql)->row_array()['total_gift'];

        $lottoApi = LOTTO_SERVER . 'lotto/rest/api/report/allGames';
        SimpleCurl::prepare($lottoApi, ['startTime'=> $time_start ?? '', 'endTime' => $time_end ?? ''], 
                                ['Content-Type: application/json', 'SERVER-ID: php']);
        SimpleCurl::exec_get();
        $platformProfit = SimpleCurl::get_response_assoc();
        $platformProfit = $platformProfit['data']['profit'] ?? 0;

        $summary_platform_income = number_format($summary_platform_income + floatval($platformProfit), 2, '.', '');
// @file_put_contents(ERRLOG_PATH . '/stat_log_' . date("Ymd") . '.log',
//                 date("Y-m-d H:i:s") ."Platform Income: internal - $summary_platform_income , external - $platformProfit " . json_encode($totalStatRet, true). " \n",
//                 FILE_APPEND);

        // 处理下载
        if( !empty($search) && 1 === $search ) {
            $header = ['序号', '用户ID', '用户昵称', '注册手机号', '会员', '当前Baby币', '充值渠道', '充值总额', '提款总额', '盈亏总额', '手工加扣款总币额', '提款手续费', '返水总额'];

            // 提现 admin中处理，属于北京时间， unixtime 无关时区
            $onCondWr = (!empty($time_start) && !empty($time_end)) 
                        ? " AND from_unixtime(created_at, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(created_at, '%Y-%m-%d %H:%i:%s') < '{$time_end}'" 
                        : " AND from_unixtime(created_at, '%Y-%m-%d %H:%i:%s') > DATE_ADD(NOW(), INTERVAL -1 MONTH)";
            // 人工上下分 admin中处理，属于北京时间
            $onCondOpamr = (!empty($time_start) && !empty($time_end)) 
                            ? " AND from_unixtime(uptime, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(uptime, '%Y-%m-%d %H:%i:%s') < '{$time_end}'" 
                            : " AND from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s') > DATE_ADD(NOW(), INTERVAL -1 MONTH)";
            // 充值记录 下单时间为API项目，UTC时间 支付时间可能为SHARE项目的北京时间
            $onCondPd = (!empty($time_start) && !empty($time_end)) 
                            ? " AND DATE_ADD(order_time, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(order_time, INTERVAL 8 HOUR) < '{$time_end}'" 
                            : " AND DATE_ADD(order_time, INTERVAL 8 HOUR) > DATE_ADD(NOW(), INTERVAL -1 MONTH)";
            $downOffset = 0;
            $downSize  = 5000;
            while($downOffset < $total) {
                // 获取数据
                $sqlDown = "SELECT user.id, user.nickname, user.mobile, (IF(user.vip_expire>NOW(), 1, 0)) AS is_member, user.eurc_balance,
                                pd.user_recharge_amount, wr.user_withdrawal_amount, wr.user_wtf_amount, wr.user_commission, 
                                opamr.user_manual_amount
                            FROM user_1_0 user

                            LEFT JOIN (
                                SELECT uid, created_at, is_deleted, SUM(IF(status=2, amount, 0)) AS user_withdrawal_amount, 
                                SUM(IF(status=2, service_charge, 0)) AS user_wtf_amount, 
                                SUM(IF(status=2, return_money, 0)) AS user_commission
                                FROM withdrawal_record_$appid
                                WHERE 1 $onCondWr
                                GROUP BY uid
                            ) wr ON user.id = wr.uid AND wr.is_deleted=0 

                            LEFT JOIN (
                                SELECT uid, uptime, is_deleted, SUM(amount) AS user_manual_amount
                                FROM other_pay_add_money_record_$appid 
                                WHERE 1 $onCondOpamr
                                GROUP BY uid
                            ) opamr ON user.id = opamr.uid AND opamr.is_deleted=0 
                            
                            LEFT JOIN (
                                SELECT uid, order_time, SUM(IF(status=1, money, 0)) AS user_recharge_amount
                                FROM pay_deposit_$appid
                                WHERE 1 $onCondPd
                                GROUP BY uid
                            ) pd ON user.id = pd.uid 

                            WHERE $where
                            ORDER BY user.id DESC 
                            LIMIT $downSize OFFSET $downOffset";

                // $sql = "select * from pay_deposit_" . DATABASESUFFIX . " where 1 $where ORDER BY id DESC limit {$page_size} offset $limit";

                $summaryDown = $this->db->query($sqlDown)->result_array();

                $index = 1;
                $new_data = [];
                foreach ($summaryDown as $k => &$row) {
                    $new_data[$k]['index']          = $index;
                    $new_data[$k]['id']             = $row['id'];
                    $new_data[$k]['nickname']       = $row['nickname'];
                    $new_data[$k]['mobile']         = $row['mobile'];
                    $new_data[$k]['is_member']      = $row['is_member'] ? '是' : '否';
                    $new_data[$k]['eurc_balance']   = number_format($row['eurc_balance'] ?? 0, 2, '.', '');
                    $new_data[$k]['user_recharge_amount']   = number_format($row['user_recharge_amount'] ?? 0, 2, '.', '');
                    $new_data[$k]['user_withdrawal_amount'] = number_format($row['user_withdrawal_amount'] ?? 0, 2, '.', '');
                    $new_data[$k]['user_profit']    = number_format($row['user_recharge_amount'] - $row['user_withdrawal_amount'] - $row['eurc_balance']/4, 2, '.', '');
                    $new_data[$k]['user_manual_amount']     = number_format($row['user_manual_amount'] ?? 0, 2, '.', '');
                    $new_data[$k]['user_wtf_amount']        = number_format($row['user_wtf_amount'] ?? 0, 2, '.', '');
                    $new_data[$k]['user_commission']        = number_format($row['user_commission'] ?? 0, 2, '.', '');
                    
                    $index++;
                }

                $path[] = export('帐目汇总_'.$downOffset.'_' . time()  , $header, $new_data);
                $downOffset += $downSize;
            }

            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        // 返回
        ajax_return(SUCCESS, '', array(
            'data' => $summary,
            'summary' => ['page_recharge_total' => number_format($page_recharge_total, 2, '.', ''), 
                            'page_withdrawal_total' => number_format($page_withdrawal_total, 2, '.', ''), 
                            'page_manual_total' => number_format($page_manual_total, 2, '.', ''),

                            'total_recharge_amount' => number_format($total_recharge_amount, 2, '.', ''),
                            'total_withdrawal_amount' => number_format($total_withdrawal_amount, 2, '.', ''),
                            'total_manual_amount' => number_format($total_manual_amount, 2, '.', ''),

                            'summary_recharge_users' => $summary_recharge_users,
                            'summary_recharge_amount' => number_format($summary_recharge_amount, 2, '.', ''),
                            'summary_withdrawal_users' => $summary_withdrawal_users,
                            'summary_withdrawal_amount' => number_format($summary_withdrawal_amount, 2, '.', ''),
                            'summary_first_users' => $summary_first_users,
                            'summary_first_amount' => number_format($summary_first_amount, 2, '.', ''),
                            'summary_online_amount' => number_format($summary_online_amount, 2, '.', ''),
                            'summary_offline_amount' => number_format($summary_offline_amount, 2, '.', ''),

                            'summary_gift_num' => $summary_gift_num ?? 0,
                            'summary_platform_income' => $summary_platform_income,

                            'summary_register_users' => $summary_register_users,
                            'summary_new_vips'  => $summary_new_vips,
                            ],
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 帐目汇总表 - 明细
     */
    public function stat_summary_detail()
    {
        // 处理参数
        $page       = intval($_POST['page'] ?? 1);
        $page_size  = ADMIN_PAGE_SIZE;
        $limit      = (($page - 1) * $page_size);
        $appid      = DATABASESUFFIX;

        $uid        = intval($_POST['s_uid'] ?? 0);
        $time_start = str_format($_POST['time_from'] ?? '');
        $time_end   = str_format($_POST['time_to'] ?? '');
        $today      = date('Y-m-d', time());

        $search     = intval($_POST['search'] ?? 0);

        // 处理条件
        $where = '1';

        $uidRechargeWhere = !empty($uid) ? $where . " AND pd.uid={$uid}" : $where . '';
        // 充值记录 下单时间为API项目，UTC时间 支付时间可能为SHARE项目的北京时间
        $rechargeWhere = (!empty($time_start) && !empty($time_end)) 
                        ? $uidRechargeWhere . " AND DATE_ADD( pd.order_time, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(pd.order_time, INTERVAL 8 HOUR) < '{$time_end}'"
                        : $uidRechargeWhere . " AND DATE(DATE_ADD( pd.order_time, INTERVAL 8 HOUR)) = '{$today}'";

        $uidWithdrawalWhere = !empty($uid) ? $where . " AND wr.uid={$uid}" : $where . '';
        // 提现 admin中处理，属于北京时间, 无关时区
        $withdrawalWhere = (!empty($time_start) && !empty($time_end)) 
                        ? $uidWithdrawalWhere . " AND from_unixtime(wr.created_at, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(wr.created_at, '%Y-%m-%d %H:%i:%s') < '{$time_end}'"
                        : $uidWithdrawalWhere . " AND DATE( from_unixtime(wr.created_at, '%Y-%m-%d %H:%i:%s')) = '{$today}'";

        $uidManualWhere = !empty($uid) ? $where . " AND opamr.uid={$uid}" : $where . '';
        // 人工上下分 admin中处理，属于北京时间
        $manualWhere = (!empty($time_start) && !empty($time_end)) 
                        ? $uidManualWhere . " AND from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s') < '{$time_end}'"
                        : $uidManualWhere . " AND DATE(from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s')) = '{$today}'";

        // 获取充值数据
        $sqlRechargeDetail = "SELECT pd.money, IF(pd.payment_type=1, pc.channel_name, '银行卡充值') AS channel_name, pd.order_id, IF(pd.payment_type=1, pd.status, bpl.status) AS status, pd.order_time, bpl.order_note, IF(pd.payment_type=1, pc.third_party_name, bpl.order_operator) AS related_name
                    FROM pay_deposit_$appid pd
                    LEFT JOIN payment_config pc 
                    ON pd.payment_config_id=pc.id
                    LEFT JOIN bank_payment_log_$appid bpl
                    ON pd.order_id=bpl.order_id
                    WHERE $rechargeWhere
                    ORDER BY pd.order_time DESC ";

        $retRechargeDetail = $this->db->query($sqlRechargeDetail)->result_array();

        $rechargeIndex = 1;
        foreach ($retRechargeDetail as $key => &$value) {
            $value['index']         = $rechargeIndex;
            $value['order_time']    = time_to_local_string($value['order_time']);
            $value['money']         = number_format($value['money'], 2, '.', '');
            
            $value['order_note']    = $value['order_note'] ?? '/';
            $rechargeIndex++;
        }

        // 获取提现数据
        // $sqlWithdrawalDetail = "SELECT wr.amount, wr.order_id, wr.reason, wr.status, wr.created_at, au.username
        //             FROM withdrawal_record_$appid wr
        //             LEFT JOIN admin_user_$appid au
        //             ON wr.op_uid=au.uid AND is_deleted=0

        //             WHERE $withdrawalWhere AND wr.status=2
        //             ORDER BY wr.created_at DESC ";
        $sqlWithdrawalDetail = "SELECT wr.amount, wr.order_id, wr.reason, wr.status, wr.created_at, wr.op_uid as username
                    FROM withdrawal_record_$appid wr

                    WHERE $withdrawalWhere AND wr.is_deleted=0
                    ORDER BY wr.created_at DESC ";
        $retWithdrawalDetail = $this->db->query($sqlWithdrawalDetail)->result_array();

        $withdrawalIndex = 1;
        foreach ($retWithdrawalDetail as $key => &$value) {
            $value['index']         = $withdrawalIndex;
            $value['channel_name']  = '银行卡';
            $value['created_at']    = date('Y-m-d H:i:s', $value['created_at']);
            $value['amount']         = number_format($value['amount'], 2, '.', '');
            switch ($value['status']) {
                case 1:
                    $value['status'] = '等待审核';
                    break;
                case 2:
                    $value['status'] = '审核成功';
                    break;
                case 3:
                    $value['status'] = '审核失败';
                    break;
                default:
                    $value['status'] = '等待审核';
                    break;
            }

            $withdrawalIndex++;
        }
// @file_put_contents(ERRLOG_PATH . '/stat_log_' . date("Ymd") . '.log',
//                 date("Y-m-d H:i:s") ."data:  " . json_encode($retWithdrawalDetail, true) . " \n",
//                 FILE_APPEND);
        // 获取人工上下分数据
        // $sqlManualDetail = "SELECT opamr.amount, opamr.memo, opamr.uptime, au.username
        //             FROM other_pay_add_money_record_$appid opamr
        //             LEFT JOIN admin_user_$appid au
        //             ON opamr.op_uid=au.uid

        //             WHERE $manualWhere AND is_deleted=0
        //             ORDER BY opamr.uptime DESC ";
        $sqlManualDetail = "SELECT opamr.amount, opamr.memo, opamr.uptime, opamr.op_uid as username
                    FROM other_pay_add_money_record_$appid opamr
                    WHERE $manualWhere AND is_deleted=0
                    ORDER BY opamr.uptime DESC ";
        // $sql = "select * from pay_deposit_" . DATABASESUFFIX . " where 1 $where ORDER BY id DESC limit {$page_size} offset $limit";
        // @file_put_contents(ERRLOG_PATH . '/stat_log_' . date("Ymd") . '.log',
        //         date("Y-m-d H:i:s") ."SQL excuted: $sqlManualDetail " . " \n",
        //         FILE_APPEND);
        $retManualDetail = $this->db->query($sqlManualDetail)->result_array();

        $manualIndex = 1;
        foreach ($retManualDetail as $key => &$value) {
            $value['index']     = $manualIndex;
            $value['uptime']    = date('Y-m-d H:i:s', $value['uptime']);
            $value['amount']    = number_format($value['amount'], 2, '.', '');
            // $value['amount']    = $value['amount'] >= 0 ? '+ ' .$value['amount'] : '- ' . abs($value['amount']);
            $manualIndex++;
        }

        // 处理下载
        if( !empty($search) && 1 === $search ) {
            self::exportSummaryDetail($uid, $time_start, $time_end);
        }

        // 返回
        ajax_return(SUCCESS, '', array(
            'rechargeData' => $retRechargeDetail,
            'withdrawalData' => $retWithdrawalDetail,
            'manualData' => $retManualDetail,

            // 'summary' => ['page_recharge_total' => number_format($page_recharge_total, 2, '.', ''), 
            //                 'page_withdrawal_total' => number_format($page_withdrawal_total, 2, '.', ''), 
            //                 'page_manual_total' => number_format($page_manual_total, 2, '.', ''),

            //                 'total_recharge_amount' => number_format($total_recharge_amount, 2, '.', ''),
            //                 'total_manual_withdrawal' => number_format($total_manual_withdrawal, 2, '.', ''),
            //                 'total_manual_cash' => number_format($total_manual_cash, 2, '.', ''),

            //                 'summary_recharge_users' => $total_recharge_users,
            //                 'summary_recharge_amount' => number_format($total_recharge_amount, 2, '.', ''),
            //                 'summary_user_withdrawal' => $total_user_withdrawal,
            //                 'summary_withdrawal_amount' => number_format($total_manual_withdrawal, 2, '.', ''),
            //                 'summary_first_users' => $total_first_users,
            //                 'summary_first_amount' => number_format($total_first_amount, 2, '.', ''),
            //                 'summary_online_amount' => number_format($total_online_amount, 2, '.', ''),
            //                 'summary_offline_amount' => number_format($total_offline_amount, 2, '.', ''),

            //                 ],
            // 'total' => intval($total),
            // 'page' => $page,
            // 'page_size' => $page_size,
            // 'page_count' => intval($total / $page_size)
        ));
    }

    private function exportSummaryDetail(int $uid, string $time_start, string $time_end)
    {

        $header[] = ['序号', '充值金额', '充值渠道', '订单号', '订单状态', '订单时间', '备注', '关联方'];
        $header[] = ['序号', '提款金额', '提款渠道', '订单号', '订单状态', '订单时间', '备注', '关联方'];
        $header[] = ['序号', '加扣款币额', '加扣款原因', '加扣款时间', '关联方'];

        $today      = date('Y-m-d', time()+3600*8);
        $appid      = DATABASESUFFIX;
        $where      = '1';

        $uidRechargeWhere = !empty($uid) ? $where . " AND pd.uid={$uid}" : $where . '';
        // 充值记录 下单时间为API项目，UTC时间 支付时间可能为SHARE项目的北京时间
        $rechargeWhere = (!empty($time_start) && !empty($time_end)) 
                        ? $uidRechargeWhere . " AND DATE_ADD( pd.order_time, INTERVAL 8 HOUR) > '{$time_start}' AND DATE_ADD(pd.order_time, INTERVAL 8 HOUR) < '{$time_end}'"
                        : $uidRechargeWhere . " AND DATE(DATE_ADD( pd.order_time, INTERVAL 8 HOUR)) = '{$today}'";

        $uidWithdrawalWhere = !empty($uid) ? $where . " AND wr.uid={$uid}" : $where . '';
        // 提现 admin中处理，属于北京时间, 无关时区 unixtime
        $withdrawalWhere = (!empty($time_start) && !empty($time_end)) 
                        ? $uidWithdrawalWhere . " AND from_unixtime(wr.created_at, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(wr.created_at, '%Y-%m-%d %H:%i:%s') < '{$time_end}'"
                        : $uidWithdrawalWhere . " AND DATE( from_unixtime(wr.created_at , '%Y-%m-%d %H:%i:%s') ) = '{$today}'";

        $uidManualWhere = !empty($uid) ? $where . " AND opamr.uid={$uid}" : $where . '';
        // 人工上下分 admin中处理，属于北京时间
        $manualWhere = (!empty($time_start) && !empty($time_end)) 
                        ? $uidManualWhere . " AND from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s') > '{$time_start}' AND from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s') < '{$time_end}'"
                        : $uidManualWhere . " AND DATE( from_unixtime(opamr.uptime, '%Y-%m-%d %H:%i:%s') ) = '{$today}'";

        // 获取充值数据
        $sqlRechargeDetail = "SELECT pd.money, pc.channel_name, pd.order_id, pd.status, pd.order_time, bpl.order_note, IF(pd.payment_type=1, pc.third_party_name, bpl.order_operator) AS related_name
                    FROM pay_deposit_$appid pd
                    LEFT JOIN payment_config pc 
                    ON pd.payment_config_id=pc.id
                    LEFT JOIN bank_payment_log_$appid bpl
                    ON pd.order_id=bpl.order_id
                    WHERE $rechargeWhere AND pd.status=1
                    ORDER BY pd.order_time DESC ";

        $retRechargeDetail = $this->db->query($sqlRechargeDetail)->result_array();

        $rechargeIndex = 1;
        $new_data = [];
        foreach ($retRechargeDetail as $k => &$row) {
            $new_data[0][$k]['index']          = $rechargeIndex;
            $new_data[0][$k]['money']          = number_format($row['money'], 2, '.', '');
            $new_data[0][$k]['channel_name']   = $row['channel_name'];
            $new_data[0][$k]['order_id']       = $row['order_id'];
            switch ($row['status']) {
                case 0:
                    $row['status'] = '未支付';
                    break;
                case 1:
                    $row['status'] = '成功';
                    break;
                case 2:
                    $row['status'] = '失败';
                    break;
                default:
                    $row['status'] = '未支付';
                    break;
            }
            $new_data[0][$k]['status']         = $row['status'];
            $new_data[0][$k]['order_time']     = time_to_local_string($row['order_time']);
            $new_data[0][$k]['order_note']     = $row['order_note'] ?? '/';
            $new_data[0][$k]['related_name']   = $row['related_name'] ?? '/';

            $rechargeIndex++;
        }

        // 获取提现数据
        // $sqlWithdrawalDetail = "SELECT wr.amount, wr.order_id, wr.reason, wr.status, wr.created_at, au.username
        //             FROM withdrawal_record_$appid wr
        //             LEFT JOIN admin_user_$appid au
        //             ON wr.op_uid=au.uid AND is_deleted=0

        //             WHERE $withdrawalWhere AND wr.status=2
        //             ORDER BY wr.created_at DESC ";
        $sqlWithdrawalDetail = "SELECT wr.amount, wr.order_id, wr.reason, wr.status, wr.created_at, wr.op_uid as username
                    FROM withdrawal_record_$appid wr

                    WHERE $withdrawalWhere AND wr.status=2 AND wr.is_deleted=0
                    ORDER BY wr.created_at DESC ";
        $retWithdrawalDetail = $this->db->query($sqlWithdrawalDetail)->result_array();

        $withdrawalIndex = 1;
        foreach ($retWithdrawalDetail as $k => &$row) {
            $new_data[1][$k]['index']          = $withdrawalIndex;
            $new_data[1][$k]['amount']         = number_format($row['amount'], 2, '.', '');
            $new_data[1][$k]['channel_name']   = '银行卡';
            $new_data[1][$k]['order_id']       = $row['order_id'];
            switch ($row['status']) {
                case 1:
                    $row['status'] = '等待审核';
                    break;
                case 2:
                    $row['status'] = '审核成功';
                    break;
                case 3:
                    $row['status'] = '审核失败';
                    break;
                default:
                    $row['status'] = '等待审核';
                    break;
            }
            $new_data[1][$k]['status']         = $row['status'];
            $new_data[1][$k]['created_at']     = date('Y-m-d H:i:s', $row['created_at'] );
            $new_data[1][$k]['order_note']     = $row['order_note'] ?? '/';
            $new_data[1][$k]['related_name']   = $row['related_name'] ?? '/';

            $withdrawalIndex++;
        }

        // 获取人工上下分数据
       
        $sqlManualDetail = "SELECT opamr.amount, opamr.memo, opamr.uptime, opamr.op_uid as username
                    FROM other_pay_add_money_record_$appid opamr
                    WHERE $manualWhere AND is_deleted=0
                    ORDER BY opamr.uptime DESC ";
        
        $retManualDetail = $this->db->query($sqlManualDetail)->result_array();

        $manualIndex = 1;
        foreach ($retManualDetail as $k => &$row) {
            $new_data[2][$k]['index']          = $manualIndex;
            $new_data[2][$k]['amount']         = number_format($row['amount'], 2, '.', '');
            $new_data[2][$k]['memo']           = $row['memo'];
            $new_data[2][$k]['uptime']        =  date('Y-m-d H:i:s', $row['uptime']);
            
            $manualIndex++;
        }


        $path = export_multi('帐目汇总明细_' . time()  , $header, $new_data);

        ajax_return(SUCCESS, get_tips(1002), $path);
    }
}
