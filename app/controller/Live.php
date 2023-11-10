<?php

/**
 * 直播管理后台
 */
class Live extends Controller
{
    const FORBIDDEN_FOREVER = '2099-12-31 23:59:59';

    protected $user_model;

    public function __construct()
    {
        parent::__construct();

        $this->load_model("user_model");
    }

    /**
     * 直播列表
     */
    public function list()
    {
        $page = intval($_POST['page']) ?? 1;
        $nickname = sql_format(($_POST['nickname']));
        $uid = intval($_POST['uid'] ?? 0);
        $join_date_start = sql_format($_POST['begin_time_left'] ?? '');
        $join_date_end = sql_format($_POST['begin_time_right'] ?? '');
        $exp = intval($_POST['exp'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);

        // 主播信息字段
        $info_field = ['title', 'nickname', 'pic', 'push_url',
            'pull_url', 'begin_time', 'viewer', 'viewer_num',
            'gift', 'gift_num'
        ];
        $live_list = $this->redis->zrevRange('live:anchor:hot', 0, -1);
        $star = $this->redis->sMembers("live:anchor:star");

        // 获取主播信息
        $new_live = [];
        array_walk($live_list, function ($uid, $key) use (&$new_live, $info_field, $star) {
            // 查询出主播信息

            $live_info = $this->redis->hMGet('live:room:' . $uid, $info_field);
            if ($live_info['title']) {
                $new_live[$key]['uid'] = $uid;
                $new_live[$key]['title'] = $live_info['title'] ?: '';
                $new_live[$key]['nickname'] = $live_info['nickname'] ?: '';
                $new_live[$key]['live_cover'] = get_pic_url($live_info['pic']);
                $new_live[$key]['push_url'] = $live_info['push_url'];
                $new_live[$key]['pull_url'] = $live_info['pull_url'];
                $new_live[$key]['begin_time'] = $live_info['begin_time'] ? date('Y-m-d H:i:s', $live_info['begin_time']) : '';
                // $new_live[$key]['end_time'] = $live_info['end_time'] ? date('Y-m-d H:i:s', $live_info['end_time']) : '';
                $new_live[$key]['gift'] = $live_info['gift'];
                $new_live[$key]['gift_num'] = $live_info['gift_num'];

                $new_live[$key]['viewer_num'] = $live_info['viewer'] ?: 0;
                $new_live[$key]['viewer'] = intval($this->redis->sCard("live:room:$uid:user"));
                if (in_array($uid, $star)) {
                    $new_live[$key]['star'] = 1;
                } else {
                    $new_live[$key]['star'] = 0;
                }
            }
        });

        // 处理搜索
        foreach ($new_live as $key => $live_info) {
            // 搜索昵称
            if ($nickname && strpos($live_info['nickname'], $nickname) === false) {
                unset($new_live[$key]);
            }
            // 搜索uid
            if ($live_info['uid'] != $uid && $uid > 0) {
                unset($new_live[$key]);
            }
            // 搜索时间
            $begin_time = strtotime($live_info['begin_time']);

            if (!$join_date_start && !$join_date_end) {
                continue;
            }

            if (!($begin_time > strtotime($join_date_start) && $begin_time < strtotime($join_date_end))) {
                unset($new_live[$key]);
            }
        }

        // 数量
        $total = count($new_live);
        // 每页要显示的主播
        $page_live = array_splice($new_live, $limit, $page_size);
        if ($exp == 2) {
            $header = array(
                get_tips(4001),
                get_tips(4002),
                get_tips(4003),
                get_tips(4004),
                get_tips(4005),
                get_tips(4006),
                get_tips(4007),
                get_tips(4008),

            );
            $path = export(get_tips(4009), $header, $page_live);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        ajax_return(SUCCESS, get_tips(1007), array(
            'data' => $page_live,
            'total' => $total,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 主播列表
     */
    public function anchor_list()
    {
        // 接受参数
        $uid = intval($_POST['uid'] ?? 0);
        $nickname = sql_format(($_POST['nickname']));
        $page = intval($_POST['page']) ?? 1;
        $join_date_start = sql_format($_POST['upload_time_left'] ?? '');
        $join_date_end = sql_format($_POST['upload_time_right'] ?? '');
        $page_size = ADMIN_PAGE_SIZE;
        $status = input('post.audit_status', 'intval', 99);
        $appid = DATABASESUFFIX;
        $exp = (int)$_POST['exp'] ?: 1;
        // 条件处理
        $where = "where 1 ";
        if ($uid) {
            $where .= " and u.id = $uid";
        }
        if ($nickname) {
            $where .= " and u.nickname like '%$nickname%'";
        }
        if ($status !== 99) {
            $where .= " and an.status = $status";
        }
        if ($join_date_start && $join_date_end) {
            $where .= " and an.uptime > '{$join_date_start}' and an.uptime <= '{$join_date_end}'";
        }
        // 查询数据
        $now_id = (($page - 1) * $page_size);
        $this->init_db();

        $sql = "select an.id, u.id uid, u.nickname, an.status,max( an.uptime) as uptime from user_{$appid}_0 as u join user_cert_anchor_{$appid} as an on u.id = an.uid {$where} group by an.uid order by uptime desc limit {$page_size} offset {$now_id}";


        $res = $this->db->query($sql)->result_array();
        foreach ($res as &$item) {

            $user_info = $this->user_model->get_user($item['uid'], ['nickname', 'gender']);

            $item['nickname'] = $user_info['nickname'];

            $item['uptime'] = time_to_local_string($item['uptime']);
            $item['is_play'] = intval($this->redis->hget('user:' . $item['uid'], 'is_play'));
            switch ($item['status']) {
                case 1:
                    $item['status_txt'] = get_tips(2015);
                    break;
                case 2:
                    $item['status_txt'] = get_tips(2016);
                    break;
                case 3:
                    $item['status_txt'] = get_tips(2025);
                    break;
            }
        }

        //导出
        if ($exp == 2) {
            $header = array(
                get_tips(4010),
                get_tips(4011),
                get_tips(4012),
                get_tips(4013),
                get_tips(4014),
            );
            $path = export(get_tips(4015), $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        // 统计条数
        $sql = "select max( an.uptime) as uptime from user_{$appid}_0 as u join user_cert_anchor_{$appid} as an on u.id = an.uid {$where} group by an.uid order by uptime desc";
        $total_count_res = $this->db->query($sql)->result_array();
        $page_count = count($total_count_res) / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }

        // 返回参数
        $data = array(
            'list' => $res,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => intval(count($total_count_res))
        );
        ajax_return(SUCCESS, get_tips(1003), $data);
    }

    // 禁播
    public function set_play()
    {
        $uid = intval($_POST['uid'] ?? 0);
        $is_play = intval($_POST['is_play']);
        $uid || ajax_return(0, '主播id不能為空！');
        $this->redis->hset("user:$uid", 'is_play', $is_play);
        ajax_return(SUCCESS, "操作成功");
    }

    /**
     * 获取主播申请认证信息
     */
    public function anchor_detail()
    {
        $appid = DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            ajax_return(ERROR, get_tips(4016));
        }
        $sql = "SELECT a.id, a.uid, a.sociaty_id, a.uptime, a.status, a.comm, a.admin_id, a.admin_time,b.name sociaty_name FROM user_cert_anchor_$appid a LEFT JOIN sociaty_$appid b ON a.sociaty_id = b.id WHERE a.id = $id";
        $row = $this->db->query($sql)->row_array();
        // 查询主播认证信息
        $uid = $row['uid'];
        $row['take_photo'] = '';
        $row['pic_url'] = '';
        $row['video_url'] = '';

        if ($uid) {
            $anchor_detail = $this->db->query("select take_photo,pic_url,video_url from user_cert_pic_{$appid} where uid = {$uid} order by id desc")->row_array();
            $row['take_photo'] = get_pic_url($anchor_detail['take_photo']);
            $row['pic_url'] = get_pic_url($anchor_detail['pic_url']);
            $row['video_url'] = get_pic_url($anchor_detail['video_url']);
        }
        if (!empty($row)) {
            $row['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $row['uid']), "nickname");
            //1审核通过，2审核失败，3待审核
            $status_types = [1 => get_tips(1008), 2 => get_tips(1009), 3 => get_tips(1010)];
            $row['status_text'] = $status_types[$row['status']] ?? get_tips(1011);
        }

        ajax_return(SUCCESS, get_tips(1005), $row);
    }

    /**
     * 用户实名认证
     */
    public function certify()
    {
        //接受参数
        $uid = intval($_POST['uid'] ?? 0);
        $nickname = sql_format(($_POST['nickname']));
        $page = intval($_POST['page']) ?? 1;
        $join_date_start = isset($_POST['upload_time_left']) ? htmlspecialchars($_POST['upload_time_left']) : 0; //传时间戳，秒
        $join_date_end = isset($_POST['upload_time_right']) ? htmlspecialchars($_POST['upload_time_right']) : 0; //传时间戳
        $page_size = ADMIN_PAGE_SIZE;
        $status = (int)$_POST['audit_status'];
        $exp = (int)$_POST['exp'] ?? 1;
        $appid = DATABASESUFFIX;

        // 条件处理
        $where = "where 1 = 1 ";
        if ($uid) {
            $where .= " and u.id = $uid";
        }
        if ($nickname) {
            $where .= " and u.nickname like '%$nickname%'";
        }
        if ($status === 1 || $status === 2 || $status === 3) {
            $where .= " and cer.status = $status";
        }
        if ($join_date_start && $join_date_end) {
            $where .= " and cer.uptime > '{$join_date_start}' and cer.uptime <= '{$join_date_end}'";
        }

        // 查询数据
        $now_id = (($page - 1) * $page_size);
        $this->init_db();
        $sql = "select cer.id, u.id uid, u.nickname, cer.realname, cer.status, cer.uptime from user_{$appid}_0 as u join user_certify_$appid cer on u.id = cer.uid $where order by cer.uptime desc limit $page_size offset $now_id";
        $res = $this->db->query($sql)->result_array();
        foreach ($res as &$item) {
            $item['uptime'] = time_to_local_string($item['uptime']);
            switch ($item['status']) {
                case 0:
                    $item['status_txt'] = get_tips(4017);
                    break;
                case 1:
                    $item['status_txt'] = get_tips(2015);
                    break;
                case 2:
                    $item['status_txt'] = get_tips(4018);
                    break;
                case 3:
                    $item['status_txt'] = get_tips(1010);
                    break;
            }
        }

        // 导出
        if ($exp == 2) {
            $header = array(
                get_tips(4010),
                get_tips(4011),
                get_tips(4012),
                get_tips(4019),
                get_tips(2009),
                get_tips(4014),
            );
            $path = export(get_tips(4020), $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        // 查询条数
        $sql = "select  count(*) num from user_{$appid}_0 as u join user_certify_$appid cer on u.id = cer.uid $where ";
        $total_count_res = $this->db->query($sql)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }

        // 返回参数
        $data = array(
            'list' => $res,
            'page' => $page, //第几页
            'page_size' => $page_size, //每页显示几条
            'page_count' => $page_count, //有几页
            'total' => intval($total_count_res['num']) //总条数
        );
        ajax_return(SUCCESS, "", $data);
    }

    /**
     * 实名认证详情
     */
    public function certify_detail()
    {
        $appid = DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            ajax_return(ERROR, get_tips(4016));
        }
        $sql = "SELECT a.id, a.uid, a.realname, a.id_type, a.id_no, a.address, a.idcard1, a.idcard2, a.idcard3, a.photo, a.uptime, a.status, a.comm, a.admin_id, a.admin_time,b.type id_type_name FROM user_certify_$appid a LEFT JOIN cat_id_type_$appid b ON a.id_type = b.id WHERE a.id = $id";
        $row = $this->db->query($sql)->row_array();

        if (!empty($row)) {
            $row['idcard1'] = get_pic_url($row['idcard1']);
            $row['idcard2'] = get_pic_url($row['idcard2']);
            $row['idcard3'] = get_pic_url($row['idcard3']);
            $row['photo'] = get_pic_url($row['photo']);
            //1审核通过，2审核失败，3待审核
            $status_types = [1 => get_tips(1008), 2 => get_tips(1009), 3 => get_tips(1010)];
            $row['status_text'] = $status_types[$row['status']] ?? get_tips(1011);
        }

        ajax_return(SUCCESS, "", $row);
    }

    // 实名/主播认证审核
    public function do_certify()
    {
        $appid = DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);
        // certify 实名认证，anchor 主播认证
        $type = sql_format($_POST['type'] ?? "");
        $status = intval($_POST['status'] ?? 0);
        $comm = sql_format($_POST['comm'] ?? "");
        if ($id <= 0) {
            ajax_return(ERROR, get_tips(4016));
        }
        if (!in_array($type, ['certify', 'anchor'])) {
            ajax_return(ERROR, get_tips(4021));
        }
        if (!in_array($status, [1, 2])) {
            ajax_return(ERROR, get_tips(4022));
        }
        if ($status == 2 && empty($comm)) {
            ajax_return(ERROR, get_tips(4023));
        }
        if ($type == 'anchor') {

            $msg = get_tips(4024);
            $title = get_tips(4025);
            if ($status == 2) {
                $msg = get_tips(4026);
                $title = get_tips(4018);
            }

            $table_name = "user_cert_anchor_$appid";
            $sql = "UPDATE $table_name SET status = {$status},comm = '{$comm}' WHERE id = {$id}";
            $num = $this->db->query($sql)->affected_rows();
            if ($num) {
                // 查询用户id
                $sql = "select uid, sociaty_id from $table_name where id = {$id}";
                $res = $this->db->query($sql)->row_array();
                $uid = $res['uid'];
                $sociaty_id = $res['sociaty_id'];
                if ($status == 1) {
                    //後置審核，默認已通過
                    // 更新用户表
                    $sql = "update user_{$appid}_0 set is_anchor = 1, sociaty_id = {$sociaty_id} where id = {$uid}";
                    $this->db->query($sql);
                    // 更新redis键
                    $this->redis->hmset("user:$uid", ['is_anchor' => 1, 'sociaty_id' => $sociaty_id]);
                    $this->redis->zadd("live:anchor:all", time(), $uid);

                    // 更新主播封面图
                    $sql = "SELECT pic_url FROM user_cert_pic_" . DATABASESUFFIX . " WHERE uid=$uid ORDER BY id DESC";
                    $row = $this->db->query($sql)->row_array();
                    $this->redis->hset("live:anchor:$uid", "pic", $row['pic_url'] ?? "");
                } elseif ($status == 2) {
                    // 更新用户表
                    $sql = "update user_{$appid}_0 set is_anchor = 0, sociaty_id = 0 where id = {$uid}";
                    $this->db->query($sql);
                    // 更新redis键
                    $this->redis->hmset("user:$uid", ['is_anchor' => 0, 'sociaty_id' => 0]);
                    $this->redis->zrem("live:anchor:all", $uid);
                    $this->redis->zrem("live:anchor:hot", $uid);
                    // 更新主播封面图
                    $this->redis->del("live:anchor:$uid");
                }
            } else {
                ajax_return(ERROR, "更新状态失败");
            }
        } else {
            $table_name = "user_certify_$appid";
            $sql = "UPDATE $table_name SET status = {$status}, comm = '$comm' WHERE id = {$id}";
            $this->db->query($sql);

            $msg = get_tips(4027);
            $title = get_tips(4028);
            if ($status == 2) {
                $msg = get_tips(4029);
                $title = get_tips(4018);
            }
        }

        // TODO 发送系统消息给用户知晓审核结果
        $data = [
            'uid' => $uid,
            'status' => $status,
            'msg' => $msg,
            'type' => $type
        ];
        $this->load_model('common_model');
        $this->common_model->send_msg($uid, $title);
        $this->common_model->push_notification($uid, $title, $msg, $data);

        ajax_return(SUCCESS, "操作成功");
    }


    /**
     * 获取rtc token
     * 一对一音视频聊天 和 直播时 使用
     * 直播时需要确认调用方是主播还是观众
     */
    public function get_rtc_token()
    {
        $room_id = $_POST['room_id'] ?? 0;
        if (empty($room_id)) {
            ajax_return(ERROR, get_tips(4030));
        }

        ajax_return(SUCCESS, "success", [
            'rtc_token' => ((new Agora())->get_rtc_token(0, $room_id, 'audience'))
        ]);
    }

    /**
     * 封面列表 live_pic_list
     *
     * @return void
     */
    public function live_pic_list()
    {
        // 接受参数
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $room_id = intval($_POST['room_id']);
        $exp = intval($_POST['exp'] ?? 1);
        $nickname = htmlspecialchars($_POST['nickname']);
        $status = intval($_POST['status'] ?? 99);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        // 条件处理
        $where = '';
        if ($nickname) {
            $where .= " and user.nickname like '{$nickname}'";
        }
        if ($room_id) {
            $where .= " and live_pic.room_id = {$room_id}";
        }
        if ($status != 99) {
            $where .= " and live_pic.status = {$status}";
        }
        if ($start_date && $end_date) {
            $where .= " and live_pic.uptime > '{$start_date}' and live_pic.uptime <= '{$end_date}'";
        }

        // 查询数据
        $data_sql = "select live_pic.id,live_pic.room_id, user.nickname, live_pic.status, live_pic.uptime from live_pic_" . DATABASESUFFIX . " as live_pic, user_" . DATABASESUFFIX . "_0 as user where live_pic.room_id = user.id {$where} order by live_pic.uptime desc limit {$page_size} offset $limit";
        $live_list = $this->db->query($data_sql)->result_array();
        foreach ($live_list as &$list) {
            $list['uptime'] = time_to_local_string($list['uptime']);
            switch ($list['status']) {
                case 0:
                    $list['status_txt'] = get_tips(2025);
                    break;
                case 1:
                    $list['status_txt'] = get_tips(2026);
                    break;
                case 2:
                    $list['status_txt'] = get_tips(2027);
                    break;
                case 3:
                    $list['status_txt'] = get_tips(2028);
                    break;
            }
        }

        // 导出
        if ($exp == 2) {
            $header = [
                get_tips(4031),
                get_tips(2002),
                get_tips(2003),
                get_tips(2009),
                get_tips(4014)
            ];
            $path = export(get_tips(4032), $header, $live_list);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        // 统计条数
        $count_sql = "select count(live_pic.id) as num from live_pic_" . DATABASESUFFIX . " as live_pic, user_" . DATABASESUFFIX . "_0 as user where live_pic.room_id = user.id" . $where;
        $live_count = $this->db->query($count_sql)->row_array();
        $total_num = $live_count['num'];

        // 返回数据
        ajax_return(SUCCESS, "", array(
            'data' => $live_list,
            'total' => intval($total_num),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total_num / $page_size)
        ));
    }

    /**
     * 封面审核详情 live_info
     *
     * @return void
     */
    public function live_info()
    {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            ajax_return(ERROR, get_tips(1006));
        }

        $sql = "select id, room_id, pic, status, uptime from live_pic_" . DATABASESUFFIX . " where id = {$id}";
        $live_info = $this->db->query($sql)->row_array();

        // 获取昵称和封面图
        $uid = $live_info['room_id'];
        $live_info['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $uid), "nickname");
        $live_info['top_pic'] = get_pic_url($this->redis->hGet("live:anchor:$uid", "pic"));
        $live_info['new_pic'] = get_pic_url($live_info['pic']);

        ajax_return(SUCCESS, '', [$live_info]);
    }

    /**
     * 审核封面 check_live
     *
     * @return void
     */
    public function check_live()
    {
        $id = intval($_POST['id'] ?? 0);
        $room_id = intval($_POST['room_id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $new_pic = $_POST['new_pic'] ?? '';
        if (!$room_id || !$status || !$id) {
            ajax_return(ERROR, get_tips(1006));
        }

        $sql = "update live_pic_" . DATABASESUFFIX . " set status = {$status} where id = {$id}";
        $num = $this->db->query($sql)->affected_rows();

        //替换旧图
        if ($num) {
            //  修改缓存
            if ($new_pic && $status == 1) {
                $this->redis->hSet("live:anchor:$room_id", "pic", $new_pic);
            } elseif ($status == 2) {
                $this->redis->zDelete("live:anchor:hot", $room_id);
            }
            ajax_return(SUCCESS, '');
        }
        ajax_return(ERROR, '');
    }


    /**
     * 主播收益
     */
    public function profit()
    {
        $uid = $_POST['uid'] ?? 0;
        $nickname = sql_format($_POST['nickname'] ?? '');
        $sociaty_id = $_POST['sociaty_id'] ?? 0;
        $sociaty_name = sql_format($_POST['sociaty_name'] ?? '');
    }


    /**
     * 主播收益列表 anchor_profit
     *
     * @return void
     */
    public function anchor_profit()
    {
        // 接受参数
        $anchor_id = intval($_POST['anchor_id'] ?: 0);
        $nickname = $_POST['nickname'] ?: '';
        $labour_union = intval($_POST['labour_union'] ?: 0);
        $labour_min = intval($_POST['labour_min'] ?: 0);
        $begin_time_left = strval($_POST['begin_time_left'] ?? 0);
        $begin_time_right = strval($_POST['begin_time_right'] ?? 0);
        $exp = intval($_POST['exp'] ?: 1);
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);

        //处理条件
        $where = '1=1';
        if ($anchor_id) {
            $where .= ' and uid = ' . $anchor_id;
        }
        if ($nickname) {
            $where .= " and nickname like '%{$nickname}%'";
        }
        if ($labour_min) {
            switch ($labour_min) {
                case '1':
                    $where .= " and (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(begin_time)) < 3600";
                    break;
                case '2':
                    $where .= " and (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(begin_time)) >= 3600";
                    break;
                case '3':
                    $where .= " and (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(begin_time)) >= 7200";
                    break;
                case '4':
                    $where .= " and (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(begin_time)) >= 10800";
                    break;
            }
        }

        if ($begin_time_left && $begin_time_right) {
            $where .= " and DATE_ADD(begin_time,INTERVAL +8 HOUR) between '$begin_time_left' and '$begin_time_right'";
        }

        if ($labour_union) {
            $where .= ' and soc_id = ' . $labour_union;
        }

        //查询主播收益
        $anchor_profit_sql = "SELECT id, uid, soc_id, nickname, DATE_ADD(end_time,INTERVAL +8 HOUR) as end_time, gift, DATE_ADD(begin_time,INTERVAL +8 HOUR) as begin_time 
                                FROM live_log_" . DATABASESUFFIX . " 
                                WHERE $where 
                                ORDER BY begin_time desc limit {$page_size} offset $limit";
        // $anchor_profit_sql = "SELECT ll.id, ll.uid, ll.soc_id, ll.nickname, ll.gift, DATE_ADD(ll.begin_time,INTERVAL +8 HOUR) as ll_begin_time, DATE_ADD(ll.end_time,INTERVAL +8 HOUR) as ll_end_time, DATE_ADD(ed.uptime,INTERVAL +8 HOUR) as edUptime, ed.uid as edUid, ed.fee as edFee
        //                         -- SUM(CASE 
        //                         --     WHEN date(DATE_ADD(ed.uptime, INTERVAL +8 HOUR)) = date(DATE_ADD(ll.begin_time, INTERVAL +8 HOUR)) AND ll.uid <> ed.uid THEN ed.fee
        //                         --     ELSE 0 END) AS total
        //                 FROM live_log_" . DATABASESUFFIX . " ll
        //                 LEFT JOIN exp_detail_" . DATABASESUFFIX . " ed
        //                 ON ed.call_id = ll.uid 
        //                 WHERE $where
        //                 GROUP BY ll.id, ed.uid, ed.uptime, ed.fee
        //                 ORDER BY ll.begin_time desc LIMIT {$page_size} OFFSET $limit";

        $anchor_profit = $this->db->query($anchor_profit_sql)->result_array();

        // 获取公会 & 计算播出时长
        $new_data = [];
        foreach ($anchor_profit as $k => $anchor) {
            $new_data[$k]['begin_time'] = $anchor['begin_time'];
            $new_data[$k]['id'] = $anchor['id'];
            $new_data[$k]['uid'] = $anchor['uid'];
            $new_data[$k]['nickname'] = $anchor['nickname'];
            $new_data[$k]['gift'] = number_format($anchor['gift'], MONEY_DECIMAL_DIGITS, '.', '');

            $new_data[$k]['sociaty'] = $this->redis->hGet('sociaty:' . $anchor['soc_id'], "name") ?: '暂无公会';
            $new_data[$k]['times'] = 0;
            if ($anchor['begin_time'] && $anchor['end_time']) {
                $new_data[$k]['times'] = time_difference($anchor['begin_time'], $anchor['end_time']);
            }
            $new_data[$k]['total'] = 0;
            $sql = "SELECT uid, fee, DATE_ADD(uptime,INTERVAL +8 HOUR) as uptime FROM exp_detail_" . DATABASESUFFIX . " WHERE call_id=" . $anchor['uid'] . ' AND call_id <> uid';
            $res = $this->db->query($sql)->result_array();

            foreach ($res as $key => $value) {
                if ((date('Y-m-d', strtotime($value['uptime'])) == date('Y-m-d', strtotime($anchor['begin_time'])))) {
                    $new_data[$k]['total'] += abs($value['fee']);
                }
            }
            $new_data[$k]['total'] = number_format($new_data[$k]['total'], 2, '.', '');
        }
        //导出
        if ($exp == 2) {
            $header = [
                get_tips(4033),
                get_tips(4001),
                get_tips(4012),
                get_tips(4034),
                get_tips(4035),
                get_tips(4036),
                get_tips(4007)
            ];
            $path = export(get_tips(4037), $header, $new_data);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        // 统计条数
        $anchor_profit_count_sql = "select count(id) as num from live_log_" . DATABASESUFFIX . " where $where";
        $total_count = $this->db->query($anchor_profit_count_sql)->row_array();
        $total_num = $total_count['num'];

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'data' => $new_data,
            'total' => intval($total_num),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total_num / $page_size)
        ));
    }




    /**
     * 主播收益列表 anchor_profit
     *
     * @return void
     */
//     public function anchor_profit()
//     {
//         // 接受参数
//         $anchor_id = intval($_POST['anchor_id'] ?: 0);
//         $nickname = $_POST['nickname'] ?: '';
//         $labour_union = intval($_POST['labour_union'] ?: 0);
//         $labour_min = intval($_POST['labour_min'] ?: 0);
//         $begin_time_left = strval($_POST['begin_time_left'] ?? 0);
//         $begin_time_right = strval($_POST['begin_time_right'] ?? 0);
//         $exp = intval($_POST['exp'] ?: 1);
//         $page = intval($_POST['page'] ?? 1);
//         $page_size = 10;
//         $limit = (($page - 1) * $page_size);

//         //处理条件
//         $where = '1=1';
//         if ($anchor_id) {
//             $where .= ' and ll.uid = ' . $anchor_id;
//         }
//         if ($nickname) {
//             $where .= " and ll.nickname like '%{$nickname}%'";
//         }
//         if ($labour_min) {
//             switch ($labour_min) {
//                 case '1':
//                     $where .= " and (UNIX_TIMESTAMP(ll.end_time) - UNIX_TIMESTAMP(ll.begin_time)) < 3600";
//                     break;
//                 case '2': 
//                     $where .= " and (UNIX_TIMESTAMP(ll.end_time) - UNIX_TIMESTAMP(ll.begin_time)) >= 3600";
//                     break;
//                 case '3':
//                     $where .= " and (UNIX_TIMESTAMP(ll.end_time) - UNIX_TIMESTAMP(ll.begin_time)) >= 7200";
//                     break;
//                 case '4':
//                     $where .= " and (UNIX_TIMESTAMP(ll.end_time) - UNIX_TIMESTAMP(ll.egin_time)) >= 10800";
//                     break;
//             }
//         }

//         if ($begin_time_left && $begin_time_right) {
//             $where .= " and ll.begin_time between '$begin_time_left' and '$begin_time_right'";
//         }

//         if ($labour_union) {
//             $where .= ' and ll.soc_id = ' . $labour_union;
//         }

//         //查询主播收益
//         // $anchor_profit_sql = "select id, uid, soc_id, nickname, end_time, gift, begin_time from live_log_" . DATABASESUFFIX . " where $where order by begin_time desc limit {$page_size} offset $limit";
    // $anchor_profit_sql = "SELECT ll.id, ll.uid, ll.soc_id, ll.nickname, ll.gift, ll.begin_time, ll.end_time,
    //                                 SUM(CASE WHEN ed.uptime >= ll.begin_time AND ed.uptime <=ll.end_time THEN ed.soc_share END) AS guildLeaderShareTotal,
    //                                 SUM(CASE WHEN ed.uptime >= ll.begin_time AND ed.uptime <=ll.end_time THEN ed.boss_share END) AS plateformShareTotal,
    //                                 SUM(CASE WHEN ed.uptime >= ll.begin_time AND ed.uptime <=ll.end_time THEN ed.net_fee END) AS streamerShareTotal,
    //                                 SUM(CASE WHEN ed.uptime >= ll.begin_time AND ed.uptime <=ll.end_time THEN ed.fee END) AS total
    //                         FROM live_log_" . DATABASESUFFIX . " ll
    //                         INNER JOIN exp_detail_" . DATABASESUFFIX . " ed
    //                         ON ed.call_id = ll.room_id
    //                         WHERE $where AND ll.end_time IS NOT NULL AND ll.uid <> ed.uid
    //                         GROUP BY ll.id
    //                         ORDER BY ll.begin_time desc LIMIT {$page_size} OFFSET $limit";

//         $anchor_profit = $this->db->query($anchor_profit_sql)->result_array();
// @file_put_contents(ERRLOG_PATH . '/anchor_profit_data_' . date("Ymd") . '.log',
//                             date("Y-m-d H:i:s") . $anchor_profit_sql . " \n" . json_encode($anchor_profit, true) . " \n",
//                             FILE_APPEND);
//         // 获取公会 & 计算播出时长
//         $new_data = [];
//         foreach ($anchor_profit as $k => $anchor) {
//             $new_data[$k]['id'] = $anchor['id'];
//             $new_data[$k]['uid'] = $anchor['uid'];
//             $new_data[$k]['nickname'] = $anchor['nickname'];
//             $new_data[$k]['times'] = 0;
//             if ($anchor['begin_time'] && $anchor['end_time']) {
//                 $new_data[$k]['times'] = time_difference($anchor['begin_time'], $anchor['end_time']);
//             }
//             $new_data[$k]['gift'] = number_format(abs($anchor['streamerShareTotal']), MONEY_DECIMAL_DIGITS, '.', '') ?? 0;

//             $new_data[$k]['sociaty'] = $this->redis->hGet('sociaty:'.$anchor['soc_id'], "name") ?: '暂无公会';
//             $new_data[$k]['gl_income'] = number_format($anchor['guildLeaderShareTotal'], MONEY_DECIMAL_DIGITS, '.', '') ?? 0;
//             $new_data[$k]['pltf_income'] = number_format($anchor['plateformShareTotal'], MONEY_DECIMAL_DIGITS, '.', '') ?? 0;
//             $new_data[$k]['begin_time'] = time_to_local_string($anchor['begin_time']);
//             $new_data[$k]['end_time'] = time_to_local_string($anchor['end_time']);
//         }
// // @file_put_contents(ERRLOG_PATH . '/anchor_profit_data_' . date("Ymd") . '.log',
// //                             date("Y-m-d H:i:s") . json_encode($new_data, true) . " \n",
// //                             FILE_APPEND);

//         //导出
//         if ($exp == 2) {
//             $header = [
//                 '直播场次', '主播ID', '昵称', '播出时长', '打赏收益', '工会', '工会长收入', '平台收入', '开播时间', '下播时间' 
//             ];
//             $path = export( 'income_list_' . time(), $header, $new_data);
//             ajax_return(SUCCESS, get_tips(1002), $path);
//             exit;
//         }
//         // 统计条数
//         $anchor_profit_count_sql = "select count(ll.id) as num from live_log_" . DATABASESUFFIX . " ll where $where";
//         $total_count = $this->db->query($anchor_profit_count_sql)->row_array();
//         $total_num = $total_count['num'];

//         // 返回数据
//         ajax_return(SUCCESS, '', array(
//             'data' => $new_data,
//             'total' => intval($total_num),
//             'page' => $page,
//             'page_size' => $page_size,
//             'page_count' => intval($total_num / $page_size)
//         ));
//     }

    /**
     * 公会列表 labour_union_list
     *
     * @return void
     */
    public function labour_union_list()
    {
        $labour_union_sql = "select id,name from sociaty_" . DATABASESUFFIX . " where status = 1";
        $labour_union = $this->db->query($labour_union_sql)->result_array();
        ajax_return(SUCCESS, '', $labour_union);
    }

    /**
     * 主播每日收益 daily_earnings
     *
     * @return void
     */
    public function daily_earnings()
    {
        $page = intval($_POST['page'] ?? 1);
        $page_size = 10;
        $uid = intval($_POST['uid'] ?: 0);
        $year = intval($_POST['year'] ?: date('Y'));
        $month = intval($_POST['month'] ?: date('m'));
        $limit = (($page - 1) * $page_size);
        $exp = $_POST['exp'] ?? 0;
        if (!$uid) {
            ajax_return(ERROR, get_tips(1006));
        }
        if ($month < 10) {
            $month = '0' . $month;
        }
        $daily_earnings = $this->redis->hGetAll('money:inc:gift:' . $uid);
        // 处理收益里的日期
        $new_earnings = [];
        foreach ($daily_earnings as $date => $price) {
            if (substr($date, 0, 4) == $year && substr($date, 4, 2) == $month) {
                $new_earnings[] = [
                    'date' => date('Y-m-d', strtotime($date)),
                    'price' => number_format($price / MONEY_DECIMAL_MULTIPLE, 2),
                ];
            }
        }
        if ($exp == 2) {
            $header = [
                '日期', '日打赏收益',
            ];
            $path = export(date('Y-m-d') . '_' . $uid . '_income_list_' . time(), $header, $new_earnings);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        $total = count($new_earnings);
        ajax_return(SUCCESS, '', array(
            'data' => array_splice($new_earnings, $limit, $page_size),
            'total' => $total,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval(floor($total / $page_size))
        ));
    }

    /**
     * 主播年收益 year_earnings
     *
     * @return void
     */
    public function year_earnings()
    {
        $uid = intval($_POST['uid'] ?: 0);
        $year = intval($_POST['year'] ?: date('Y'));
        if (!$uid) {
            ajax_return(ERROR, get_tips(1006));
        }
        $year = intval($_POST['year'] ?: date('Y'));

        $year_earnings = $this->redis->hGetAll('money:inc:gift:' . $uid);

        // 计算收益
        $year_gift = 0;
        foreach ($year_earnings as $date => $price) {
            if (substr($date, 0, 4) == $year) {
                $year_gift += $price;
            }
        }

        $start_date = $year . '-01-01 00:00:00';
        $end_date = $year . '-12-31 23:59:59';
        //计算直播时长
        $time_len_sql = "select sum(TIMESTAMPDIFF(MINUTE, begin_time,end_time)) as time_len from live_log_" . DATABASESUFFIX . " where uid = {$uid} and begin_time > '{$start_date}' and begin_time <= '{$end_date}'";

        $time_len = $this->db->query($time_len_sql)->row_array();

        ajax_return(SUCCESS, '', array(
            'year_earnings' => number_format($year_gift / MONEY_DECIMAL_MULTIPLE, 2),
            'time' => $time_len['time_len'] ? number_format($time_len['time_len'] / 60, 2) . '小时' : 0
        ));
    }

    /**
     * 主播月收益 month_earnings
     *
     * @return void
     */
    public function month_earnings()
    {
        $uid = intval($_POST['uid'] ?: 0);
        $year = intval($_POST['year'] ?: date('Y'));
        $month = intval($_POST['month'] ?: date('m'));
        if (!$uid) {
            ajax_return(ERROR, get_tips(1006));
        }

        $year_earnings = $this->redis->hGetAll('money:inc:gift:' . $uid);

        // 计算收益
        $year_gift = 0;
        foreach ($year_earnings as $date => $price) {
            if (substr($date, 0, 4) == $year && substr($date, 4, 2) == $month) {
                $year_gift += $price;
            }
        }
        if ($month < 10) {
            $month = '0' . $month;
        }
        // $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $days = date('t', strtotime($year . '-' . $month));
        $start_date = $year . '-' . $month . '-01 00:00:00';
        $end_date = $year . '-' . $month . '-' . $days . ' 23:59:59';
        //计算直播时长
        $time_len_sql = "select sum(TIMESTAMPDIFF(MINUTE, begin_time,end_time)) as time_len from live_log_" . DATABASESUFFIX . " where uid = {$uid} and begin_time > '{$start_date}' and begin_time <= '{$end_date}'";

        $time_len = $this->db->query($time_len_sql)->row_array();

        ajax_return(SUCCESS, '', array(
            'year_earnings' => number_format($year_gift / MONEY_DECIMAL_MULTIPLE, 2),
            'time' => $time_len['time_len'] ? number_format($time_len['time_len'] / 60, 2) . '小时' : 0
        ));
    }

    /**
     *  获取一场直播的具体收益，含工会长分成以及平台分成
     *
     *
     */
    public function get_liveshow_income_detail()
    {
        $showId = $_POST['showId'] ?? NULL;
        $roomId = $_POST['roomId'] ?? NULL;
        $startTime = $_POST['startTime'] ?? NULL;
        $endTime = $_POST['endTime'] ?? NULL;
        $exp = $_POST['exp'] ?? 0;
        $page = intval($_POST['page'] ?? 1);
        $page_size = 10;
        $limit = (($page - 1) * $page_size);
        $where = ' 1=1 ';

        if ($showId && $startTime && $endTime && $roomId) {
            $where .= "AND call_id = $roomId AND (uptime between '{$startTime}' and '{$endTime}')";
        }
        $exp_detail_sql = "SELECT uid, fee, cate, soc_share, boss_share, net_fee, uptime
                                FROM exp_detail_" . DATABASESUFFIX . "
                                WHERE $where AND uid <> call_id
                                ORDER BY uptime DESC LIMIT {$page_size} OFFSET $limit";

        $results = $this->db->query($exp_detail_sql)->result_array();
        $showIncomeList = [];
        foreach ($results as $value) {
            array_push($showIncomeList, [
                'showId' => $showId,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'userId' => $value['uid'],
                'payTime' => time_to_local_string($value['uptime']),
                'category' => $value['cate'],
                'amount' => abs($value['fee']),
                'income' => abs($value['net_fee']),
                'GLIncome' => abs($value['soc_share']),
                'platformIncome' => abs($value['boss_share']),
            ]);
        }
        $total = count($showIncomeList);

        //导出
        if ($exp == 2) {
            $header = [
                '直播场次', '开播时间', '下播时间', '打赏用户ID', '支付时间', '种类', '打赏金额', '打赏收益', '工会长收入', '平台收入'
            ];
            $path = export($showId . "_show_income_list_" . time(), $header, $showIncomeList);

            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        ajax_return(SUCCESS, '', [
            'incomeList' => $showIncomeList,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size),
            'total' => $total,
        ]);
    }

    /**
     * 断开直播 breakOff
     *
     * @return void
     */
    public function break_off()
    {
        $uid = intval($_POST['uid'] ?: 0);
        if (!$uid) ajax_return(ERROR, get_tips(1006));

        // 把断开直播的消息写入队列
        $this->load_model('common_model');
        $this->common_model->push_notification($uid, get_tips(4041), get_tips(4041), array('uid' => $uid, 'msg' => get_tips(4041), 'status' => '-1'));
        ajax_return(SUCCESS, get_tips(4042));
    }

    /**
     *  禁播 live_prohibit
     *
     * @return void
     */
    public function live_prohibit()
    {
        $uid = intval($_POST['uid'] ?: 0);
        $info = sql_format($_POST['info']);
        if (!$uid || !$info) ajax_return(ERROR, get_tips(1006));

        // 写入队列
        $this->load_model('common_model');
        $this->common_model->push_notification($uid, get_tips(4043), $info, array('uid' => $uid, 'msg' => get_tips(4043), 'status' => '-2'));
        ajax_return(SUCCESS, get_tips(4044));
    }

    /**
     *  警告 live_warning
     *
     * @return void
     */
    public function live_warning()
    {
        $uid = intval($_POST['uid'] ?: 0);
        $info = sql_format($_POST['info']);
        if (!$uid || !$info) ajax_return(ERROR, get_tips(1006));

        // 写入队列
        $this->load_model('common_model');
        $this->common_model->push_notification($uid, get_tips(4045), $info, array('uid' => $uid, 'msg' => get_tips(4045), 'status' => '-3'));
        ajax_return(SUCCESS, get_tips(4046));
    }

    /**
     * 置頂主播
     */
    public function star()
    {
        $uid = intval($_POST['uid'] ?? 0);
        if (!$uid) ajax_return(ERROR, get_tips(1006));
        if (!$this->redis->sIsMember("live:anchor:star", $uid)) {
            $this->redis->sadd("live:anchor:star", $uid);
        } else {
            $this->redis->srem("live:anchor:star", $uid);
        }
        ajax_return(1, '操作成功');
    }
}
