<?php

/**
 * 消息后台推送设置相关
 * Class message
 */
class Messagesend extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->init_db();
    }

    public function __destruct()
    {
        // 关闭redis
        if (isset($this->redis)) {
            $this->redis->close();
        }
    }


    /**
     * 发送消息设置
     */
    public function send_mess_set()
    {
        $appid = DATABASESUFFIX;

        $info = trim($_POST['info'] ?? '');

        if (!$info) {
            ajax_return(ERROR, '参数错误', []);
        }

        $info = json_decode($info, true);

        $usercode = trim($_POST['usercode'] ?? '');

        if (!$usercode) {
            ajax_return(ERROR, '操作用户信息错误', []);
        }

        if (!$info['sms_title'] || !$info['sms_content']) {
            ajax_return(ERROR, '发送内容错误', []);
        }

        $sql = "select id from admin_user_{$appid} where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();
        $op_uid = 0;
        if ($userinfo) {
            $op_uid = $userinfo['id'];
        }

        $is_single_flag = false; //是否是单用户发送
        //是否是单一发送
        if (in_array('5', $info['sms_obj'])) {
            if (!$info['sms_uid']) {
                ajax_return(ERROR, '参数错误', []);
            }
            $is_single_flag = true;
        }

        $members_id_arr = []; //会员的id集合

        $vip_id_arr = []; //vip的id集合

        $anchor_id_arr = [];//主播的id集合

        $date = date('Y-m-d H:i:s');

        //会员
        if (in_array('2', $info['sms_obj'])) {
            $is_vip_sql = "select id from user_{$appid}_0 where status=0 and (DATE_ADD(vip_expire , INTERVAL 8 HOUR) > '{$date}' or DATE_ADD(svip_expire , INTERVAL 8 HOUR) > '{$date}')";
            $vip_info = $this->db->query($is_vip_sql)->result_array();

            $vip_info = $vip_info && count($vip_info) ? $vip_info : [];

            foreach ($vip_info as $key => $item) {
                array_push($vip_id_arr, $item['id']);
            }
        }

        //vip
        //todo vip目前暂时不错，等产品理清楚概念问题
        /*if (in_array('3', $info['sms_obj'])) {

            $is_vip_sql = "select id from user_{$appid}_0 where status=0 and (DATE_ADD(vip_expire , INTERVAL 8 HOUR) > '{$date}' or DATE_ADD(svip_expire , INTERVAL 8 HOUR) > '{$date}')";
            $vip_info = $this->db->query($is_vip_sql)->result_array();

            $vip_info = $vip_info && count($vip_info) ? $vip_info : [];

            foreach ($vip_info as $key => $item) {
                array_push($vip_id_arr, $item['id']);
            }
        }*/

        //主播
        if (in_array('4', $info['sms_obj'])) {
            $is_anchor_sql = "select id from user_{$appid}_0 where status=0 and is_anchor=1";
            $anchor_info = $this->db->query($is_anchor_sql)->result_array();

            $anchor_info = $anchor_info && count($anchor_info) ? $anchor_info : [];

            foreach ($anchor_info as $key => $item) {
                array_push($anchor_id_arr, $item['id']);
            }
        }


        //全部用户
        if (in_array('1', $info['sms_obj'])) {
            $merge_id_arr = [];
            $is_all_sql = "select id from user_{$appid}_0 where status=0";
            $all_info = $this->db->query($is_all_sql)->result_array();

            $all_info = $all_info && count($all_info) ? $all_info : [];

            foreach ($all_info as $key => $item) {
                array_push($merge_id_arr, $item['id']);
            }

        } else {
            //拿到上面处理的合并集
            $merge_id_arr = array_keys(array_flip($members_id_arr) + array_flip($vip_id_arr) + array_flip($anchor_id_arr));
        }

        if (count($merge_id_arr)) {
            if ($is_single_flag && in_array($info['sms_uid'], $merge_id_arr)) {
                //单一用户包含在合并集中
                $is_single_flag = false;
            }

        } else {
            if (!$is_single_flag) {
                ajax_return(ERROR, '无发送对象', []);
            }
        }

        // 取得所有的客服UID
        $customer_service_uids = $this->redis->sMembers("customer:service");

        //随机取出一个客服
        $index_random = rand(0, count($customer_service_uids) - 1);
        $send_uid = $customer_service_uids[$index_random];

        if ($is_single_flag) {
            $this->send_single_sms_func(1, $send_uid, $info, $op_uid, [$info['sms_uid']]);
        }

        //处理合并集中的发送
        $send_num = count($merge_id_arr);
        if($send_num){
            $this->send_single_sms_func($send_num, $send_uid, $info, $op_uid, $merge_id_arr);
        }

        ajax_return(SUCCESS, '发送成功', []);


    }

    /**
     * 获取发送设置记录
     */
    public function send_mess_list()
    {
        $appid = DATABASESUFFIX;

        //2导出
        $type = intval($_POST['type'] ?? 0);

        $page = intval($_POST['page'] ?? 1);
        $size = intval($_POST['size'] ?? 10);


        $uid = intval($_POST['uid'] ?? 0);
        $time = $_POST['time'] ?? [];
        $op_uid = intval($_POST['op_uid'] ?? 0);
        $sms_type = intval($_POST['sms_type'] ?? 0);
        $sms_timer = intval($_POST['sms_timer'] ?? 0);
        $sms_obj = intval($_POST['sms_obj'] ?? 0);

        $where = "w.is_backend=1 and w.is_send=1";

        if ($uid) {
            $where .= " and l.recive_uid={$uid}";
        }

        if ($time) {
            $time[0] = $time[0] / 1000;
            $time[1] = $time[1] / 1000;
            $where .= " and w.up_time between {$time[0]} and {$time[1]} or w.set_time between {$time[0]} and {$time[1]}";
        }

        if ($sms_timer) {
            $where .= " and w.is_set_time={$sms_timer}";
        }

        if ($op_uid) {
            $where .= " and w.op_uid={$op_uid}";
        }

        if ($sms_type) {
            $where .= " and w.sms_type={$sms_type}";
        }

        if ($sms_obj) {
            $where .= " and find_in_set('{$sms_obj}', w.push_obj)";
        }

        $offset = ($page - 1) * $size;


        $filed = "w.id,w.sms_type,w.push_obj,w.push_uid,w.is_set_time,w.set_time,w.sms_title,w.sms_content,w.send_num,w.watch_num,w.up_time,w.op_time,w.op_uid,w.img_url";

        if ($type == 2) {
            $select_sql = "select {$filed} from live_send_sms_{$appid} as w left join live_send_sms_log_{$appid} as l on l.live_p_id = w.id where {$where} order by w.id desc";
            $info = $this->db->query($select_sql)->result_array();

            $info = $info && count($info) ? $info : [];
            $info = $this->foreach_arr_info($info, $appid);

            foreach ($info as $key => &$item) {
                unset($item['img_url_txt']);
            }
            $info = array_values($info);
            $header = [
                'id编号', '用户ID', '用户名字', '当前baby', '累计盈亏', '累计充值', '累计打码',
                '返水金额', '提现金额', '订单号', '提现时间', '手机号',
                '用户状态', '是否是主播'];
            $path = export('提现审核', $header, $info);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        $select_sql = "select {$filed} from live_send_sms_{$appid} as w left join live_send_sms_log_{$appid} as l on l.live_p_id = w.id where {$where} order by w.id desc";

        $total_info = $this->db->query($select_sql)->result_array();

        $select_sql .= " limit {$size} offset {$offset}";

        $info = $this->db->query($select_sql)->result_array();

        $info = $info && count($info) ? $info : [];

        $info = $this->foreach_arr_info($info, $appid);

        $return_data = [
            'info' => $info,
            'total' => $total_info && count($total_info) ? count($total_info) : 0
        ];

        ajax_return(SUCCESS, '获取成功', $return_data);
    }


    /**
     * 处理返回数据
     * @param $info
     * @param $appid
     * @return mixed
     */
    private function foreach_arr_info($info, $appid)
    {

        foreach ($info as $key => &$item) {
            $push_obj = explode(',', $item['push_obj']);
            $item['push_obj'] = '';
            foreach ($push_obj as $kk => $value) {
                switch (intval($value)) {
                    case 1:
                        $item['push_obj'] .= '·全部用户 ';
                        break;
                    case 2:
                        $item['push_obj'] .= '·会员 ';
                        break;
                    case 3:
                        $item['push_obj'] .= '·VIP ';
                        break;
                    case 4:
                        $item['push_obj'] .= '·主播 ';
                        break;
                    case 5:
                        $item['push_obj'] .= "·用户ID:{$item['push_uid']} ";
                        break;
                    default:
                        $item['push_obj'] .= "/";
                        break;
                }
            }
            $item['push_obj'] = trim($item['push_obj'], ' ');
            $item['push_obj'] = ltrim($item['push_obj'], '·');

            switch (intval($item['is_set_time'])) {
                case 0:
                    $item['is_set_time'] = '否';
                    $item['up_time'] = date("Y-m-d H:i:s", $item['up_time']);
                    break;
                case 1:
                    $item['is_set_time'] = '是';
                    $item['up_time'] = date("Y-m-d H:i:s", $item['set_time']);
                    break;
                default:
                    $item['is_set_time'] = '/';
                    break;
            }

            switch (intval($item['sms_type'])) {
                case 1:
                    $item['sms_type'] = '文本';
                    break;
                case 2:
                    $item['sms_type'] = '图文';
                    break;
                default:
                    $item['sms_type'] = '/';
                    break;
            }

            if ($item['img_url']) {
                $img_url = explode('|', $item['img_url']);
                $item['img_url'] = $img_url;
                $item['img_url_txt'] = "查看图片";
            } else {
                $item['img_url_txt'] = "/";
                $item['img_url'] = [];
            }

            $sql = "select id,username from admin_user_{$appid} where id = {$item['op_uid']}";
            $userinfo = $this->db->query($sql)->row_array();
            $op_name = '/';
            if ($userinfo) {
                $op_name = $userinfo['username'];
            }
            $item['op_uid'] = $op_name;

            $item['op_time'] = date("Y-m-d H:i:s", $item['op_time']);

        }

        return $info;

    }

    /**
     * 发送到单一用户推送消息
     * @param $send_num int 数量
     * @param $uid int 发送uid
     * @param $info array 发送包含内容
     * @param $op_uid int 操作人id
     * @param $merge_id_arr array 接收者的id集合
     */
    private function send_single_sms_func($send_num, $uid, $info, $op_uid, $merge_id_arr)
    {
        $appid = DATABASESUFFIX;
        $curr_time = time();
        $send_obj_str = implode(',', $info['sms_obj']);
        $sms_type = intval($info['sms_type']);
        $push_uid = intval($info['sms_uid']);
        $is_set_time = intval($info['is_set_time']);
        $set_time = intval($info['set_time']) / 1000;

        $filed = "(`uid`,`sms_type`,`push_type`,`push_obj`,`push_uid`,`is_set_time`,`set_time`,`sms_title`,`sms_content`,`is_send`,`send_num`,`no_watch_num`,`up_time`,`is_backend`,`op_time`,`op_uid`)";

        if (!$is_set_time) {
            //不是定时的时候发送
            $insert_value = "({$uid},{$sms_type},10,'{$send_obj_str}',{$push_uid},{$is_set_time},{$set_time},'{$info['sms_title']}','{$info['sms_content']}',1,{$send_num},{$send_num},{$curr_time},1,{$curr_time},$op_uid)";
        } else {
            //定时的时候发送
            $insert_value = "({$uid},{$sms_type},10,'{$send_obj_str}',{$push_uid},{$is_set_time},{$set_time},'{$info['sms_title']}','{$info['sms_content']}',0,{$send_num},{$send_num},0,1,{$curr_time},$op_uid)";
        }

        $sql = "insert into live_send_sms_{$appid} {$filed} values {$insert_value}";

        $insert_id = $this->db->query($sql)->insert_id();

        if ($insert_id) {

            foreach ($merge_id_arr as $key => $item) {
                //$item接收uid
                $revice_uid = $item;
                //发送消息(不是定时的时候发送)
                if (!$is_set_time) {

                    $send_info = $this->redis->hMGet(sprintf(RedisKey::USER, $uid), ["nickname", "avatar"]);
                    $send_info['uid'] = $uid;
                    $send_info['avatar'] = get_pic_url($send_info['avatar'], 'avatar');

                    $recive_arr = $this->redis->hMGet(sprintf(RedisKey::USER, $revice_uid), ["nickname", "avatar"]);
                    $recive_arr['uid'] = $revice_uid;
                    $recive_arr['avatar'] = get_pic_url($recive_arr['avatar'], 'avatar');

                    Util::send_agora_message($appid, $insert_id, $recive_arr, $send_info, $info['sms_content']);

                }
            }


        }
    }


}