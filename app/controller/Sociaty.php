<?php

/**
 * 公会管理
 * User: user
 * Date: 2019/8/14
 * Time: 9:48
 */
class Sociaty extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load_model('common_model');
    }

    /**
     * 公会列表
     */
    public function sociaty_list()
    {
        $owner_uid = intval($_POST['owner_uid'] ?? 0);
        $mobile = intval($_POST['mobile'] ?? 0);
        $id = intval($_POST['id'] ?? 0);
        $realname = sql_format($_POST['realname'] ?? '');
        $uptime_start = $_POST['uptime_start'] ?? '';
        $uptime_end = $_POST['uptime_end'] ?? '';
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE);
        $offset = ($page_no - 1) * $page_size;

        $where = '1';
        if ($owner_uid) {
            $where .= ' AND owner_uid=' . $owner_uid;
        }
        if ($mobile) {
            $where .= ' AND mobile=' . $mobile;
        }
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($realname) {
            $where .= ' AND realname LIKE "%' . $realname . '%"';
        }
        if ($uptime_start) {
            if ($uptime_end) {
                $where .= ' AND uptime BETWEEN "' . $uptime_start . '" AND "' . $uptime_end . '"';
            } else {
                $where .= ' AND uptime > "' . $uptime_start . '"';
            }
        } else {
            if ($uptime_end) {
                $where .= ' AND uptime < "' . $uptime_end . '"';
            }
        }

        $field = "id,name,uptime,owner_uid,realname,mobile,area_code,share_rate,anchor_rate,anchor_num,status";
        $sql = "SELECT $field FROM sociaty_" . DATABASESUFFIX . " WHERE $where ORDER BY id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();

        $sql = "SELECT COUNT(id) AS total FROM sociaty_" . DATABASESUFFIX . " WHERE $where";
        $res = $this->db->query($sql)->row_array();

        $ret = [
            'data' => $list,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($res['total'] / $page_size) + 1,
            'total' => (int) $res['total'],
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    /**
     * 公会添加
     */
    public function sociaty_add()
    {
        $name = sql_format($_POST['name'] ?? '');
        $owner_uid = intval($_POST['owner_uid'] ?? 0);
        $realname = sql_format($_POST['realname'] ?? '');
        $mobile = intval($_POST['mobile'] ?? 0);
        $share_rate = intval($_POST['share_rate'] ?? 0);
        $uptime = date('Y-m-d H:i:s');

        if (!$name) {
            ajax_return(ERROR, get_tips(7001));
        }

        if (!$share_rate) {
            ajax_return(ERROR, get_tips(7002));
        }

        //判断会长id是否在user表中存在
        $res = $this->redis->hgetall(sprintf(RedisKey::USER, $owner_uid));
        if (!$owner_uid || !$res) {
            ajax_return(ERROR, get_tips(7003));
        }
        if (isset($res['sociaty_id']) && $res['sociaty_id']) {
            ajax_return(ERROR, get_tips(7004));
        }

        $sql = "INSERT INTO sociaty_" . DATABASESUFFIX . " (name, owner_uid, realname,mobile, share_rate, uptime, anchor_num, status) VALUES ('$name', $owner_uid, '$realname', $mobile, $share_rate, '$uptime', 1, 1)";
        $id = $this->db->query($sql)->insert_id();
        $this->redis->hmset(sprintf(RedisKey::SOCIATY, $id), ['owner_uid' => $owner_uid, 'share_rate' => $share_rate, 'anchor_num' => 1, 'name' => $name, 'realname' => $realname, 'mobile' => $mobile]);

        //更新会长在user表及redis中相关信息
        $this->load_model("sociaty_model");
        $this->sociaty_model->update_user_info(['sociaty_id' => $id, 'join_sociaty' => $uptime, 'is_sociaty' => 1], $owner_uid);

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 公会编辑
     */
    public function sociaty_edit()
    {
        $id = intval($_POST['id'] ?? 0);
        $name = sql_format($_POST['name'] ?? '');
        $owner_uid = intval($_POST['owner_uid'] ?? 0);
        $realname = sql_format($_POST['realname'] ?? '');
        $mobile = intval($_POST['mobile'] ?? 0);
        $share_rate = intval($_POST['share_rate'] ?? 0);

        if (!$name) {
            ajax_return(ERROR, get_tips(7001));
        }
        if (!$share_rate) {
            ajax_return(ERROR, get_tips(7002));
        }

        //判断会长id是否在user表中存在
        $res = $this->redis->hgetall(sprintf(RedisKey::USER, $owner_uid));
        if (!$owner_uid || !$res) {
            ajax_return(ERROR, get_tips(7003));
        }

        //更新前先保留会长id
        $owner_uid_old = $this->redis->hget(sprintf(RedisKey::SOCIATY, $id), 'owner_uid');

        if ($owner_uid_old) {
            //先判断下会长id有没有创建过公会
            if ($owner_uid_old != $owner_uid && isset($res['sociaty_id']) && $res['sociaty_id']) {
                ajax_return(ERROR, get_tips(7004));
            }
            $sql = "UPDATE sociaty_" . DATABASESUFFIX . " SET name='$name',owner_uid=$owner_uid,realname='$realname',mobile=$mobile,share_rate=$share_rate WHERE id=$id";
            $this->db->query($sql);
            $this->redis->hmset(sprintf(RedisKey::SOCIATY, $id), ['owner_uid' => $owner_uid, 'share_rate' => $share_rate, 'anchor_num' => 1, 'name' => $name, 'realname' => $realname, 'mobile' => $mobile]);

            //如果更改会长id更新新旧会长在user表中信息
            if ($owner_uid_old != $owner_uid) {
                $time = date('Y-m-d H:i:s');
                $this->load_model("sociaty_model");
                //新会长信息
                $this->sociaty_model->update_user_info(['sociaty_id' => $id, 'join_sociaty' => $time, 'is_sociaty' => 1], $owner_uid);
                //旧会长信息
                $this->sociaty_model->update_user_info(['sociaty_id' => 0, 'is_sociaty' => 0], $owner_uid_old);
            }
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 公会状态更新
     */
    public function sociaty_update_status()
    {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        $sql = "UPDATE sociaty_" . DATABASESUFFIX . " SET status=$status WHERE id=$id";
        $this->db->query($sql);

        $owner_uid = $this->redis->hget(sprintf(RedisKey::SOCIATY, $id), 'owner_uid');
        if ($status == 3 && $owner_uid) {
            $this->load_model("sociaty_model");
            $this->sociaty_model->update_user_info(['sociaty_id' => 0, 'is_sociaty' => 0], $owner_uid);
            $this->redis->del(sprintf(RedisKey::SOCIATY, $id));
        }

        switch ($status) {
            case 0:
                $desc = get_tips(1019);
                break;
            case 1:
                $desc = get_tips(1020);
                break;
            case 2:
                $desc = get_tips(1021);
                break;
            case 3:
                $desc = get_tips(1022);
                break;
            default:
                $desc = get_tips(7005);
                break;
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 公会主播列表
     */
    public function sociaty_anchor_list()
    {
        $page = intval($_POST['page'] ?: 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $uid = intval($_POST['uid'] ?: 0);
        $mobile = sql_format($_POST['mobile'] ?: '');
        $sociaty_owner_id = intval($_POST['owner_uid'] ?: 0);
        $sociaty_id = intval($_POST['sociaty_id'] ?: 0);
        $realname = sql_format($_POST['realname'] ?: '');
        $start_time = $_POST['join_sociaty_start'] ?? '';
        $end_time = $_POST['join_sociaty_end'] ?? '';

        // 处理条件
        $where = '1';
        if ($uid > 0) {
            $where .= " and id = {$uid}";
        }
        if ($start_time && $end_time) {
            $where .= " and join_sociaty >= '{$start_time}' and join_sociaty <= '{$end_time}'";
        }
        // 查询数据
        $table = 'user_1_0';
        $fields = 'id uid, nickname, realname, mobile, sociaty_id, join_sociaty';
        $sql = "SELECT {$fields} FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();

        // 查询公会和主播信息
        foreach ($list as &$join_info) {
            // 公会信息
            $sociaty_info = $this->redis->hMGet(sprintf(RedisKey::SOCIATY, $join_info['sociaty_id']), array('name', 'owner_uid', 'share_rate', 'mobile'));
            $user_rate = $this->redis->hget('user:'.$join_info['uid'], 'share_rate');
            $join_info['sociaty_name'] = $sociaty_info['name'];
            $join_info['sociaty_owner_id'] = $sociaty_info['owner_uid'];
            $join_info['share_rate'] = $user_rate ? $user_rate : $sociaty_info['share_rate'];
            $join_info['uptime'] = $join_info['join_sociaty'] != '2000-01-01 00:00:00' ? $join_info['join_sociaty'] : '';        
        }

        // 处理搜索
        foreach ($list as $key => $info) {
            if ($mobile && strpos($info['mobile'], $mobile) === false) {
                unset($list[$key]);
            }
            if ($info['sociaty_owner_id'] != $sociaty_owner_id && $sociaty_owner_id !== 0) {
                unset($list[$key]);
            }
            if ($sociaty_id && $info['sociaty_id'] != $sociaty_id) {
                unset($list[$key]);
            }
            if ($realname && strpos($info['nickname'], $realname) === false) {
                unset($list[$key]);
            }
        }
        
        // 统计数据
        $count_sql = "SELECT COUNT(id) as num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->row_array()['num'];

        // 返回数据
        ajax_return(SUCCESS, get_tips(1005), array(
            'data' => array_values($list),
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 公会主播添加
     */
    public function sociaty_anchor_add()
    {
        $uid = intval($_POST['uid'] ?? 0);
        $sociaty_id = intval($_POST['sociaty_id'] ?? 0);
        $share_rate = intval($_POST['share_rate'] ?? 0);

        if (!$share_rate) {
            ajax_return(ERROR, get_tips(7006));
        }

        $res = $this->redis->hgetall(sprintf(RedisKey::USER, $uid));
        if (!$uid || !$res) {
            ajax_return(ERROR, get_tips(7010));
        }
        if (isset($res['sociaty_id']) && $res['sociaty_id']) {
            $res = $this->redis->hgetall(sprintf(RedisKey::SOCIATY, $res['sociaty_id']));
            if ($res) {
                ajax_return(ERROR, get_tips(7011));
            }
        }

        $res = $this->redis->hgetall(sprintf(RedisKey::SOCIATY, $sociaty_id));
        if (!$sociaty_id || !$res) {
            ajax_return(ERROR, get_tips(7012));
        }
        $time = date('Y-m-d H:i:s');
        $this->load_model("sociaty_model");
        $this->sociaty_model->update_user_info(['sociaty_id' => $sociaty_id, 'join_sociaty' => $time, 'share_rate' => $share_rate], $uid);
        $this->sociaty_model->update_anchor_num($sociaty_id, 1);

        //记录日志
        $log_data = [
            'uid' => $uid,
            'sociaty_id' => $sociaty_id,
            'type' => 1,
            'admin_id' => $this->user['id'],
            'share_rate' => $share_rate
        ];
        $this->sociaty_model->sociaty_anchor_log($log_data);

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 公会主播编辑
     */
    public function sociaty_anchor_edit()
    {
        $uid = intval($_POST['uid'] ?? 0);
        $sociaty_id = intval($_POST['sociaty_id'] ?? 0);
        $share_rate = intval($_POST['share_rate'] ?? 0);

        if (!$share_rate) {
            ajax_return(ERROR, get_tips(7006));
        }

        $sociaty_id_old = $this->redis->hget(sprintf(RedisKey::USER, $uid), 'sociaty_id');
        if (!$uid || !$sociaty_id_old) {
            ajax_return(ERROR, get_tips(7007));
        }
        $res = $this->redis->hgetall(sprintf(RedisKey::SOCIATY, $sociaty_id_old));
        if (!$res) {
            ajax_return(ERROR, get_tips(7008));
        }
        if ($res['owner_uid'] == $uid) {
            ajax_return(ERROR, get_tips(7009));
        }

        $res = $this->redis->hgetall(sprintf(RedisKey::SOCIATY, $sociaty_id));
        if (!$sociaty_id || !$res) {
            ajax_return(ERROR, get_tips(7012));
        }
        $this->load_model("sociaty_model");
        $this->sociaty_model->update_user_info(['share_rate' => $share_rate, 'sociaty_id' => $sociaty_id], $uid);
        if ($sociaty_id_old != $sociaty_id) {

            $this->sociaty_model->update_anchor_num($sociaty_id, 1);
            $this->sociaty_model->update_anchor_num($sociaty_id_old, -1);

            //记录日志
            $log_data = [
                'uid' => $uid,
                'sociaty_id' => $sociaty_id_old,
                'type' => 0,
                'admin_id' => $this->user['id'],
                'share_rate' => $share_rate
            ];
            $this->sociaty_model->sociaty_anchor_log($log_data);
            $log_data['sociaty_id'] = $sociaty_id;
            $log_data['type'] = 1;
            $this->sociaty_model->sociaty_anchor_log($log_data);
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 踢出公会
     */
    public function sociaty_anchor_kick_out()
    {
        $uid = intval($_POST['uid'] ?? 0);
        $sociaty_id = $this->redis->hget(sprintf(RedisKey::USER, $uid), 'sociaty_id');
        if (!$uid || !$sociaty_id) {
            ajax_return(ERROR, get_tips(7007));
        }
        $this->load_model("sociaty_model");
        $this->sociaty_model->update_user_info(['sociaty' => 0], $uid);
        $this->sociaty_model->update_anchor_num($sociaty_id, -1);

        //记录日志
        $log_data = [
            'uid' => $uid,
            'sociaty_id' => $sociaty_id,
            'type' => 0,
            'admin_id' => $this->user['id'],
        ];
        $this->sociaty_model->sociaty_anchor_log($log_data);

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 公会流水列表
     */
    public function sociaty_statement_list()
    {
        $sociaty_id = intval($_POST['sociaty_id'] ?? 0);
        $sociaty_name = sql_format($_POST['sociaty_name'] ?? '');
        $owner_uid = intval($_POST['owner_uid'] ?? 0);
        $page_no = intval($_POST['page_no'] ?? 0) ?: 1;
        $page_size = intval($_POST['page_size'] ?? 0) ?: 5;
        $offset = ($page_no - 1) * $page_size;
        $date = isset($_POST['date_start']) && isset($_POST['date_start']) ? '' : date('Ymd');
        // $dateFrom = isset($_POST['date_start']) ? htmlspecialchars($_POST['date_start']) : 0;
        // $dateTo = isset($_POST['date_end']) ? htmlspecialchars($_POST['date_end']) : 0;
        $dateStart = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : date('Ymd');
        $dateEnd = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : date('Ymd');
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));

        $where = '1';
        if ($sociaty_id) {
            $where .= " AND id=$sociaty_id";
        }
        if ($sociaty_name) {
            $where .= " AND name LIKE '%$sociaty_name%'";
        }
        if ($owner_uid) {
            $where .= " AND owner_uid=$owner_uid";
        }

        $sql = "SELECT id,`name` FROM sociaty_" . DATABASESUFFIX . " WHERE $where AND status=1";
        $sociaty_list = $this->db->query($sql)->result_array();

        $list = [];
        $day = 0;

        if ($sociaty_list) {
            if ($year == date('Y') && $month == date('n')) {
                $day = date('j');
            } else {
                $day = date('t', mktime(0, 0, 0, $month, 1, $year));
            }

            for ($i = $day - $offset; $i > $day - $offset - $page_size; $i--) {
                $date = sprintf("%s-%02d-%02d", $year, $month, $i);

                foreach ($sociaty_list as $v) {
                    $list[] = [
                        'sociaty_id'      => $v['id'],
                        'sociaty_name'    => $v['name'],
                        'date'            => $date,
                        // 'soc_msq_gross' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_GROSS, $date), $v['id']) ?: '',
                        'soc_eurc_gross'  => number_format($this->redis->hget(sprintf(RedisKey::SOC_EURC_GROSS, $date), $v['id']), MONEY_DECIMAL_DIGITS, '.', '') ?: '',
                        // 'soc_msq_sum' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_SUM, $date), $v['id']) ?: '',
                        'soc_eurc_sum'    => number_format($this->redis->hget(sprintf(RedisKey::SOC_EURC_SUM, $date), $v['id']), MONEY_DECIMAL_DIGITS, '.', '') ?: '',
                        // 'soc_msq_anchor' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_ANCHOR, $date), $v['id']) ?: '',
                        'soc_eurc_anchor' => number_format($this->redis->hget(sprintf(RedisKey::SOC_EURC_ANCHOR, $date), $v['id']), MONEY_DECIMAL_DIGITS, '.', '') ?: '',
                        // 'soc_msq_net' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_NET, $date), $v['id']) ?: '',
                        'soc_eurc_net'    => number_format($this->redis->hget(sprintf(RedisKey::SOC_EURC_NET, $date), $v['id']), MONEY_DECIMAL_DIGITS, '.', '') ?: '',
                    ];
                }
            }
        }
        $ret = [
            'data' => array_values($list),
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($day / $page_size) + 1,
            'total' => $day * count($sociaty_list),
        ];

        ajax_return(SUCCESS, "", $ret);

    }

    /**
     * 导出公会流水列表
     */
    public function export_sociaty_statement_list()
    {
        $sociaty_id = intval($_POST['sociaty_id'] ?? 0);
        $sociaty_name = sql_format($_POST['sociaty_name'] ?? '');
        $owner_uid = intval($_POST['owner_uid'] ?? 0);
        $page_no = intval($_POST['page_no'] ?? 0) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;
        $date = $_POST['date'] ?? date('Ymd') ?: date('Ymd');
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));

        $where = '1';
        if ($sociaty_id) {
            $where .= " AND id=$sociaty_id";
        }
        if ($sociaty_name) {
            $where .= " AND name LIKE '%$sociaty_name%'";
        }
        if ($owner_uid) {
            $where .= " AND owner_uid=$owner_uid";
        }

        $sql = "SELECT id,`name` FROM sociaty_" . DATABASESUFFIX . " WHERE $where AND status=0";
        $sociaty_list = $this->db->query($sql)->result_array();
        $list = [];
        if ($sociaty_list) {
            if ($year == date('Y') && $month == date('n')) {
                $day = date('j');
            } else {
                $day = date('t', strtotime($year . '-' . $month));
                // $day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            }
            for ($i = $day - $offset; $i > $day - $offset - $page_size; $i--) {
                $date = sprintf("%s-%02d-%02d", $year, $month, $i);
                foreach ($sociaty_list as $v) {
                    $list[] = [
                        'date' => $date,
                        'sociaty_id' => $v['id'],
                        'sociaty_name' => $v['name'],
                        'soc_msq_gross' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_GROSS, $date), $v['id']) ?: '',
                        'soc_eurc_gross' => $this->redis->hget(sprintf(RedisKey::SOC_EURC_GROSS, $date), $v['id']) ?: '',
                        'soc_msq_sum' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_SUM, $date), $v['id']) ?: '',
                        'soc_eurc_sum' => $this->redis->hget(sprintf(RedisKey::SOC_EURC_SUM, $date), $v['id']) ?: '',
                        'soc_msq_anchor' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_ANCHOR, $date), $v['id']) ?: '',
                        'soc_eurc_anchor' => $this->redis->hget(sprintf(RedisKey::SOC_EURC_ANCHOR, $date), $v['id']) ?: '',
                        'soc_msq_net' => $this->redis->hget(sprintf(RedisKey::SOC_MSQ_NET, $date), $v['id']) ?: '',
                        'soc_eurc_net' => $this->redis->hget(sprintf(RedisKey::SOC_EURC_NET, $date), $v['id']) ?: '',
                    ];
                }
            }
        }

        $title = [
            get_tips(7013),
            get_tips(7014),
            get_tips(7015),
            get_tips(7016) . '(MSQ)',
            get_tips(7016) . '(EURC)',
            get_tips(7017) . '(MSQ)',
            get_tips(7017) . '(EURC)',
            get_tips(7018) . '(MSQ)',
            get_tips(7018) . '(EURC)',
            get_tips(7019) . '(MSQ)',
            get_tips(7019) . '(EURC)',
        ];
        $this->load_model('common_model');

        $path = $this->common_model->export_excel('sociaty_statement_list', $title, $list);

        ajax_return(SUCCESS, get_tips(1002), ['path' => '/' . $path]);
    }

    /**
     * 公会主播收益流水列表
     */
    public function sociaty_anchor_statement_list()
    {
        $uid = intval($_POST['uid'] ?? 0);
        $sociaty_id = intval($_POST['sociaty_id'] ?? 0);
        $nickname = sql_format($_POST['nickname'] ?? '');
        $date = sql_format($_POST['date'] ?? '') ?: date('Y-m-d');
        $page_no = intval($_POST['page_no'] ?? 0) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;

        $where = '1';
        if ($uid) {
            $where .= " AND u.id=$uid";
        }
        if ($nickname) {
            $where .= " AND u.nickname LIKE '%$nickname%'";
        }

        $sql = "SELECT u.id,u.nickname,u.share_rate AS anchor_rate,s.share_rate FROM user_" . DATABASESUFFIX . "_0 AS u INNER JOIN sociaty_" . DATABASESUFFIX . " AS s ON u.sociaty_id=s.id WHERE $where AND u.sociaty_id=$sociaty_id";
        $anchor_list = $this->db->query($sql)->result_array();
        $list = [];
        if ($anchor_list) {
            foreach ($anchor_list as $v) {
                // $soc_msq_pa = $this->redis->hget(sprintf(RedisKey::SOC_MSQ_PA, $sociaty_id, $date), $v['id']) ?: 0;
                $soc_eurc_pa = $this->redis->hget(sprintf(RedisKey::SOC_EURC_PA, $sociaty_id, $date), $v['id']) ?: 0;
                $soc_eurc_pa = number_format($soc_eurc_pa, MONEY_DECIMAL_DIGITS, ".", ""); 
                $list[] = [
                    'uid' => $v['id'],
                    'nickname' => $v['nickname'],
                    // 'soc_msq_pa' => $soc_msq_pa,
                    'soc_eurc_pa' => $soc_eurc_pa,
                    // 'soc_msq_pa_sum' => ceil($soc_msq_pa * (100 - $v['share_rate']) * (100 - $v['anchor_rate']) / (100 * 100)),
                    'soc_eurc_pa_sum' => ceil($soc_eurc_pa * (100 - $v['share_rate']) * (100 - $v['anchor_rate']) / (100 * 100)),
                ];
            }

            $list = array_slice($list, $offset, $page_size);
        }

        $ret = [
            'data' => $list,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor(count($anchor_list) / $page_size) + 1,
            'total' => count($anchor_list),
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    
}
