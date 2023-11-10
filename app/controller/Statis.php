<?php

/**
 * 渠道数据统计
 */
class Statis extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 渠道数据统计（停用）
     */
    public function channel_statis_old()
    {
        //接收参数
        $page_size = 10; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;
        $channel_id = intval($_POST['channel_id'] ?? 0);   //管理员id
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';
        $exp = intval($_POST['exp'] ?? 1);
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select date, channel_id, channel_name, new_reg_num, online_today, online_yesterday, online_seven_days_ago, pay_num, stable_currency_recharge_amount, virtual_currency_recharge_amount from user_channel_statistics_$appid";
        if ($channel_id) {
            $sql .= " where channel_id = $channel_id";
        } else {
            $sql .= " where 1=1";
        }
        if ($day_start && $day_end) {
            $day_start = date('Ymd', strtotime($day_start));
            $day_end = date('Ymd', strtotime($day_end));
            $sql .= " and `date` >= $day_start and `date` <= $day_end";
        }
        $sql .= " limit $offset, $page_size";
        $res = $this->db->query($sql)->result_array();
        foreach ($res as $k => $v) {
            $res[$k]['date'] = substr($v['date'], 0, 4) . '-' . substr($v['date'], 4, 2) . '-' . substr($v['date'], 6, 2);
            $res[$k]['stable_currency_recharge_amount'] = number_format($v['stable_currency_recharge_amount'], MONEY_DECIMAL_DIGITS, '.', '');
            $total = $res[$k]['stable_currency_recharge_amount'];
            $res[$k]['arpu']  = number_format($total / ($v['new_reg_num'] ?: 1), MONEY_DECIMAL_DIGITS, '.', '');
            $res[$k]['arppu'] = number_format($total / ($v['pay_num'] ?: 1), MONEY_DECIMAL_DIGITS, '.', '');
        }

        //导出
        if ($exp == 2) {
            $header = [
                get_tips(17001),
                get_tips(17002),
                get_tips(17003),
                get_tips(17004),
                get_tips(17005),
                get_tips(17006),
                get_tips(17007),
                get_tips(17008),
                get_tips(17009),
                get_tips(17010),
                'ARPU', 'ARPPU',
            ];
            $path = export(get_tips(17011), $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }

        ajax_return(SUCCESS, '', ['list' => $res, 'page' => $page_no]);
    }

    /**
     * 渠道列表
     */
    public function channel_list()
    {
        $sql = "select id, `name` from channel_" . DATABASESUFFIX . " where status = 1";
        $res = $this->db->query($sql)->result_array();
        ajax_return(SUCCESS, '', $res);
    }

    /**
     * 渠道数据总计
     */
    public function channel_total()
    {
        //接收参数
        $page_size = 10; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $channel_id = intval($_POST['channel_id'] ?? 0);   //渠道id
        $channel_name = sql_format($_POST['channel_name'] ?? '');
        $type = sql_format($_POST['type'] ?? '');
        $day_start = $_POST['day_start'] ?? '';
        $day_end = $_POST['day_end'] ?? '';

        $exp = intval($_POST['exp'] ?? 1);
        $order = $_POST['order'] ?? '';
        $order_asc = $_POST['order_asc'] ?? 0;
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, `name`, uptime, 0 uv, 0 reg, 0 down, 0 active, 0 dep2, 0 dep1, 0 rate1, 0 rate2, `type` from channel_$appid where ";
        if ($channel_id) {
            $sql .= "id = $channel_id and ";
        }
        if ($channel_name) {
            $sql .= "name like '$channel_name%' and ";
        }
        if ($type) {
            $sql .= "`type` = '$type' and ";
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
            $pipe->zrange("ch:uv:$id", 0, -1, true);
            $pipe->zrange("ch:down:$id", 0, -1, true);
            $pipe->zrange("ch:reg:$id", 0, -1, true);
            $pipe->zrange("ch:active:$id", 0, -1, true);
            $pipe->zrange("ch:dep1:$id", 0, -1, true);
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
                    $pipe->scard("ch:dep2:$id:$i");
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
                '通路ID', '通路名稱', '加入時間', 'UV', '註冊', '下載', '激活', '充值人數', '充值金額', 'UV-註冊', '註冊-充值', '結算方式'
            ];
            $path = export('通路數據總覽', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }

        $sumary = ['uv' => $uv, 'reg' => $reg, 'down' => $down, 'active' => $active, 'dep2' => $dep2];
        ajax_return(SUCCESS, '', ['list' => $res, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }

    /**
     * 渠道每日統計
     */
    public function channel_day()
    {
        //接收参数
        $page_size = 10; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $id = intval($_POST['channel_id'] ?? 1);   //渠道id

        $exp = intval($_POST['exp'] ?? 1);
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select `name`, uptime from channel_$appid where id = $id";
        $res = $this->db->query($sql)->row_array();
        if (! $res) ajax_return(0, '參數錯誤');
        $day_start = date('Ymd', strtotime($res['uptime']));
        $day_end = date('Ymd');

        $lists = [];
        $pipe = $this->redis->pipeline();
        $pipe->zrange("ch:uv:$id", 0, -1, true);
        $pipe->zrange("ch:down:$id", 0, -1, true);
        $pipe->zrange("ch:reg:$id", 0, -1, true);
        $pipe->zrange("ch:active:$id", 0, -1, true);
        $pipe->zrange("ch:dep1:$id", 0, -1, true);
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
                $lists[$i]['dep2'] = $this->redis->scard("ch:dep2:$id:$i");
                $lists[$i]['dau'] = $this->redis->scard("ch:dau:$id:$i");
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
                '通路ID', '通路名稱', '加入時間', 'UV', '註冊', '下載', '激活', '充值人數', '充值金額', 'UV-註冊', '註冊-充值', '結算方式'
            ];
            $path = export('通路數據總覽', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }*/
        $sumary = ['dau' => $dau, 'wau' => $wau, 'mau' => $mau, 'total_active' => $total_active, 'total_dep' => $total_dep];

        ajax_return(SUCCESS, '', ['list' => $lists, 'sumary' => $sumary, 'total' => $total, 'page' => $page_no]);
    }

    /**
     * 渠道用戶明細
     */
    public function channel_user()
    {
        //接收参数
        $page_size = 10; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;

        $query_uid = intval($_POST['uid'] ?? 0);   //篩選用戶id
        $mobile = sql_format($_POST['mobile'] ?? '');
        $type = sql_format($_POST['type'] ?? 0);
        $vip = $_POST['vip'] ?? 0;
        $deposit = $_POST['deposit'] ?? 0;
        $sql1 = '';
        if ($query_uid) {
            $sql1 .= " and id = $query_uid";
        }
        if ($mobile) {
            $sql1 .= " and mobile = $mobile";
        }
        if (1 == $type) {
            $sql1 .= " and active = 1";
        } elseif (2 == $type) {
            $sql1 .= " and active = 0";
        }
        if (3 == $vip) {
            $sql1 .= " and svip_expire > now()";
        } elseif (2 == $vip) {
            $sql1 .= " and vip_expire > now()";
        } elseif (1 == $vip) {
            $sql1 .= " and svip_expire < now() and vip_expire < now()";
        }
        if (1 == $deposit) {
            $sql1 .= " and deposit_total > 0";
        } elseif (2 == $deposit) {
            $sql1 .= " and deposit_total = 0";
        }

        $id = intval($_POST['channel_id'] ?? 1);   //渠道id
        $date = $_POST['date'] ?? date('Y-m-d');   //渠道id
        if (! strtotime($date . ' 00:00:00')) {
            ajax_return(0, '參數錯誤');
        }

        $exp = intval($_POST['exp'] ?? 1);
        $offset = ($page_no - 1) * $page_size;
        $this->init_db();
        $sql = "select id, nickname, mobile, active, platform, join_date, active_time, '' last_visit, vip_expire, svip_expire, '' vip, deposit_total from user_{$appid}_0 where channel_id = $id $sql1 and join_date >= '$date 00:00:00' and join_date <= '$date 23:59:59' order by id desc limit $offset, $page_size";
        $res = $this->db->query($sql)->result_array();
        $sql = "select count(id) c from user_{$appid}_0 where channel_id = $id";
        $res1 = $this->db->query($sql)->row_array();
        $total = intval($res1['c']);

        foreach ($res as $k => $v) {
            $res[$k]['join_date']   = time_to_local_string($v['join_date']);
            $res[$k]['active_time'] = $v['active_time'] ? time_to_local_string($v['active_time']) : '';
            if ($v['active']) {
                $res[$k]['active'] = '已激活';
            } else {
                $res[$k]['active'] = '未激活';
            }
            if (4 == $v['platform'] || 1 == $v['platform']) {
                $res[$k]['platform'] = 'iOS';
            } elseif (5 == $v['platform'] || 2 == $v['platform']) {
                $res[$k]['platform'] = 'Android';
            } else {
                $res[$k]['platform'] = 'H5';
            }
            $last_visit = $this->redis->hget("user:" . $v['id'], 'heartbeat');
            $res[$k]['last_visit'] = $last_visit ? date('Y-m-d H:i:s',$last_visit) : '';
            if (strtotime($v['svip_expire']) > time()) {
                $res[$k]['vip'] = 'SVIP';
            } elseif (strtotime($v['vip_expire']) > time()) {
                $res[$k]['vip'] = 'VIP';
            } else {
                $res[$k]['vip'] = '普通用户';
            }
            unset($res[$k]['svip_expire'], $res[$k]['vip_expire']);
        }

        //导出
        /*
        if ($exp == 2) {
            $header = [
                '通路ID', '通路名稱', '加入時間', 'UV', '註冊', '下載', '激活', '充值人數', '充值金額', 'UV-註冊', '註冊-充值', '結算方式'
            ];
            $path = export('通路數據總覽', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
        }*/

        ajax_return(SUCCESS, '', ['list' => $res, 'total' => $total, 'page' => $page_no]);
    }
}
