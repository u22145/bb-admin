<?php

/**
 * Created by PhpStorm.
 * Date: 2019/8/6
 * Time: 7:53 PM
 */

class User extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @package  用户列表
     * @author   helloworld
     */
    public function index()
    {
        $page_size = ADMIN_PAGE_SIZE;
        $page = intval(isset($_POST['page']) ? htmlspecialchars($_POST['page']) : 1);
        $nickname = trim(isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : '');
        $mobile = sql_format($_POST['mobile'] ?? 0);
        $join_date_start = isset($_POST['join_date_start']) ? htmlspecialchars($_POST['join_date_start']) : 0; //传时间戳，秒
        $join_date_end = isset($_POST['join_date_end']) ? htmlspecialchars($_POST['join_date_end']) : 0; //传时间戳
        $uid = intval($_POST['uid'] ?? 0) ? htmlspecialchars($_POST['uid']) : 0;
        $is_recommend = intval($_POST['is_remcommand'] ?? 0);
        $gender = intval($_POST['gender'] ?? 0);
        $status = intval($_POST['ustatus'] ?? 0);
        $country = intval($_POST['country'] ?? 0);
        $type = intval($_POST['type'] ?? 1);
        $isvip = intval($_POST['isvip'] ?? 0);
        $advert_id = intval($_POST['advert_id'] ?? 0);

        // $order = trim($_POST['order'] ?? '');
        $sort = trim($_POST['sort'] ?? '');
        $attrib = trim($_POST['attrib'] ?? '');

        // 处理条件
        $date = date('Y-m-d H:i:s');
        $where = 'where 1=1';
        $where .= $uid > 0 ? " and user.id= {$uid}" : '';
        $where .= $nickname == true ? " and user.nickname like '%{$nickname}%'" : '';
        $where .= $is_recommend == true ? " and user.is_recommend = {$is_recommend}" : '';
        $where .= $gender == true ? " and user.gender = {$gender}" : '';
        $where .= $country == true ? " and user.country = {$country}" : '';
        // $where .= $advert_name ? " and advert.name = '{$advert_name}'" : '';
        $where .= $advert_id == true ? " and user.advert_id = {$advert_id}" : '';
        $where .= $join_date_start && $join_date_end ? " and DATE_ADD(user.join_date, INTERVAL 8 HOUR) >= '{$join_date_start}' and DATE_ADD(user.join_date, INTERVAL 8 HOUR) <= '{$join_date_end}'" : '';

        if ($mobile) {
            $where .= " and user.mobile like '$mobile%'";
        }

        // $order_sql = "user.id DESC";
        // if ($order) {
        //     $order = json_decode($order, true);
        //     switch ($order['prop']) {
        //         case "eurc_balance";
        //         case "advert_id";
        //         case "upper_uid";
        //         case "sm_uid";
        //         case "status";
        //         case "join_date";
        //             //按照user表字段排序
        //             if ($order['order'] == 'descending') {
        //                 $order_sql = "user." . $order['prop'] . " DESC";
        //             } else {
        //                 $order_sql = "user." . $order['prop'] . " ASC";
        //             }
        //             break;
        //         case "pay_total":
        //         case "withdrawal_total":
        //             //不按任何表字段排序
        //             if ($order['prop'] == 'descending') {
        //                 $order_sql = $order['prop'] . " DESC";
        //             } else {
        //                 $order_sql = $order['prop'] . " ASC";
        //             }
        //             break;
        //     }
        // }
        $order_sql = ' user.id DESC ';
        if (!empty($sort) && !empty($attrib)) {
            switch ($sort) {
                case 'ascending':
                    $sort_type = 'ASC';
                    break;
                case 'descending':
                    $sort_type = 'DESC';
                    break;
                default:
                    $sort_type = 'DESC ';
                    break;
            }
            switch ($attrib) {
                case 'pay_total':
                    $order_sql = ' pay_total ' . $sort_type . ', user.id DESC ';
                    break;
                case 'eurc_balance':
                    $order_sql = ' eurc_balance ' . $sort_type . ', user.id DESC ';
                    break;
                case 'advert_id':
                    $order_sql = ' advert_id ' . $sort_type . ', user.id DESC ';
                    break;
                case 'withdrawal_total':
                    $order_sql = ' withdrawal_total ' . $sort_type . ', user.id DESC ';
                    break;
                case 'upper_uid':
                    $order_sql = ' upper_uid ' . $sort_type . ', user.id DESC ';
                    break;
                case 'sm_uid':
                    $order_sql = ' sm_uid ' . $sort_type . ', user.id DESC ';
                    break;
                case 'join_date':
                    $order_sql = ' join_date ' . $sort_type . ', user.id DESC ';
                    break;

                default:
                    $order_sql = ' user.id DESC ';
                    break;
            }

            if ('null' == $sort) $order_sql = ' user.id DESC ';
        }

        if ($status > 0) {
            $where .= $status == 1 ? " and user.status = 0" : " and user.status = 1";
        }
        if ($isvip > 0) {
            $where .= $isvip == 1 ? " and (DATE_ADD(user.vip_expire , INTERVAL 8 HOUR) > '{$date}' or DATE_ADD(user.svip_expire , INTERVAL 8 HOUR) > '{$date}')" : " and (DATE_ADD(user.vip_expire, INTERVAL 8 HOUR) < '{$date}' or DATE_ADD(ser.svip_expire, INTERVAL 8 HOUR) < '{$date}')";
        }

        $this->init_db();
        // $sql = 'select id, nickname, mobile, avatar, gender, age, country, vip_expire, svip_expire, msq_balance, eurc_balance, is_recommend, status, level, upper_uid, sm_uid, is_black, is_fake, join_date, advert_id from user_' . DATABASESUFFIX . '_0 ' . $where . ' ORDER BY id DESC limit ' . $page_size . ' offset ' . ($page - 1) * $page_size;
        $sql = 'SELECT user.id, user.nickname, user.mobile, user.avatar, user.gender, user.age, user.country, user.vip_expire, user.svip_expire, user.msq_balance, user.eurc_balance, user.is_recommend, user.status, user.level, user.upper_uid, user.sm_uid, user.is_black, user.is_fake, user.join_date, SUM(pay.money) as pay_total, wr.withdrawal_total, user.advert_id
                FROM user_' . DATABASESUFFIX . '_0 user 
                LEFT JOIN pay_deposit_' . DATABASESUFFIX . ' as pay 
                ON user.id = pay.uid AND pay.status=1 
                left join (select uid,sum(amount) as withdrawal_total from withdrawal_record_1 where pay_status=1 GROUP by uid) as wr
                on user.id = wr.uid
                ' . $where . ' 
                GROUP BY user.id 
                ORDER BY ' . $order_sql . ' limit ' . $page_size . ' offset ' . ($page - 1) * $page_size;

        $res = $this->db->query($sql)->result_array();
        @file_put_contents(ERRLOG_PATH . '/testing_log_' . date("Ymd") . '.log',
            date("Y-m-d H:i:s") . "sql excuted: $sql \n",
            FILE_APPEND);
        $country = $this->db->query("select id, country from cat_country_" . DATABASESUFFIX)->result_array();
        $country = array_index($country, 'id');

        $new_data = [];
        foreach ($res as $key => $val) {
            $new_data[$key]['id'] = $val['id'];
            $new_data[$key]['nickname'] = $val['nickname'];
            $new_data[$key]['mobile'] = $val['mobile'];
            $new_data[$key]['avatar'] = get_pic_url($val['avatar'], 'avatar');
            if ($val['gender'] == 2) {
                $new_data[$key]['gender'] = get_tips(1025);
            } elseif ($val['gender'] == 1) {
                $new_data[$key]['gender'] = get_tips(1026);
            } else {
                $new_data[$key]['gender'] = get_tips(1027);
            }
            $new_data[$key]['age'] = $val['age'];
            $new_data[$key]['country'] = get_tips(1028);
            if (isset($country[$val['country']])) {
                $new_data[$key]['country'] = $country[$val['country']]['country'];
            }
            if ($val['vip_expire'] > date('Y-m-d h:i:s') || $val['svip_expire'] > date('Y-m-d h:i:s')) {
                $new_data[$key]['vip'] = "YES";
            } else {
                $new_data[$key]['vip'] = "NO";
            }
            $new_data[$key]['level'] = $val['level'];
            // $new_data[$key]['msq_balance'] = $val['msq_balance'];
            $new_data[$key]['eurc_balance'] = number_format($this->redis->hget('user:' . $val['id'], 'eurc_balance'), MONEY_DECIMAL_DIGITS, ".", "");
            $new_data[$key]['upper_uid'] = $val['upper_uid'];
            $new_data[$key]['sm_uid'] = $val['sm_uid'];
            switch ($val['status']) {
                case 0:
                    $new_data[$key]['status'] = "ON";
                    break;
                case 1:
                    $new_data[$key]['status'] = "OFF";
                    break;
            }
            $new_data[$key]['join_date'] = time_to_local_string($val['join_date']);
            if ($type == 1) {
                $new_data[$key]['is_black'] = $val['is_black'];
            }
            $new_data[$key]['is_fake'] = $val['is_fake'];

            $new_data[$key]['pay_total'] = number_format($val['pay_total'] ?? 0, 2, '.', '');

            $new_data[$key]['advert_id'] = $val['advert_id'] ?? '-';

            // $sql = "SELECT SUM(money) as total FROM pay_deposit_" . DATABASESUFFIX . " WHERE uid=" . $val['id'] . ' AND status=1';
            // $new_data[$key]['pay_total'] = number_format($this->db->query($sql)->row_array()['total'] ?? 0, 2);

            // $sql = "SELECT name FROM advert_" . DATABASESUFFIX . " WHERE id=" . $val['advert_id'];
            // $res = $this->db->query($sql)->row_array();
            // $new_data[$key]['advert_name'] = $res ?? '-';


            //累计提现金额
            // $withdrawal_sql = "select SUM(amount) as total from withdrawal_record_" . DATABASESUFFIX . " where uid={$val['id']} and status=2";

            // $withdrawal_info = $this->db->query($withdrawal_sql)->row_array();
            $new_data[$key]['withdrawal_total'] = number_format($val['withdrawal_total'] ?? 0, 2);
        }

        if ($type == 2) {
            foreach ($new_data as &$item) {
                unset($item['is_fake']);
            }
            $header = [
                '用户ID', '昵称', '手机号', '头像',
                '性别', '年龄', '国籍', '会员', '等级',
                '余额', '上线用户', '推广员', '状态',
                '注册时间', '累计充值', '渠道来源', '累计提现'];
            $path = export(get_tips(8064), $header, $new_data);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        $sql1 = 'select count(user.id) num from user_' . DATABASESUFFIX . '_0 user ' . $where;
        $total_count_res = $this->db->query($sql1)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }
        $data = array(
            'list' => $new_data,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => intval($total_count_res['num'])
        );

        ajax_return(SUCCESS, '', $data);
    }

    public function export_users()
    {
        $downSize   = 3000;
        $downOffset = intval($_POST['offset'] ?? 0);
        $filename   = sql_format($_POST['filename'] ?? '');
        $filename   = empty($filename) ? '用户列表_' . time() : $filename;
        $appid      = DATABASESUFFIX;

        $nickname = trim(isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : '');
        $mobile = sql_format($_POST['mobile'] ?? 0);
        $join_date_start = isset($_POST['join_date_start']) ? htmlspecialchars($_POST['join_date_start']) : 0; //传时间戳，秒
        $join_date_end = isset($_POST['join_date_end']) ? htmlspecialchars($_POST['join_date_end']) : 0; //传时间戳
        $uid = intval($_POST['uid'] ?? 0) ? htmlspecialchars($_POST['uid']) : 0;
        $is_recommend = intval($_POST['is_remcommand'] ?? 0);
        $gender = intval($_POST['gender'] ?? 0);
        $status = intval($_POST['ustatus'] ?? 0);
        $country = intval($_POST['country'] ?? 0);
        $type = intval($_POST['type'] ?? 1);
        $isvip = intval($_POST['isvip'] ?? 0);
        $advert_id = intval($_POST['advert_id'] ?? 0);


        // 处理条件
        $date = date('Y-m-d H:i:s');
        $where = ' 1 ';
        $where .= $uid > 0 ? " and user.id= {$uid}" : '';
        $where .= $nickname == true ? " and user.nickname like '%{$nickname}%'" : '';
        $where .= $is_recommend == true ? " and user.is_recommend = {$is_recommend}" : '';
        $where .= $gender == true ? " and user.gender = {$gender}" : '';
        $where .= $country == true ? " and user.country = {$country}" : '';
        // $where .= $advert_name ? " and advert.name = '{$advert_name}'" : '';
        $where .= $advert_id == true ? " and user.advert_id = {$advert_id}" : '';
        $where .= $join_date_start && $join_date_end ? " and DATE_ADD(user.join_date, INTERVAL 8 HOUR) BETWEEN '{$join_date_start}' AND '{$join_date_end}'" : '';

        $where .= !empty($mobile) ? " and user.mobile like '$mobile%'" : '';

        if ($status > 0) {
            $where .= $status == 1 ? " and user.status = 0" : " and user.status = 1";
        }
        if ($isvip > 0) {
            $where .= $isvip == 1 ? " and (DATE_ADD(user.vip_expire , INTERVAL 8 HOUR) > '{$date}' or DATE_ADD(user.svip_expire , INTERVAL 8 HOUR) > '{$date}')" : " and (DATE_ADD(user.vip_expire, INTERVAL 8 HOUR) < '{$date}' or DATE_ADD(ser.svip_expire, INTERVAL 8 HOUR) < '{$date}')";
        }

        //输出文档
        $path       = get_load_path($filename . '.csv');
        $absPath    = $path['absolutely_path'];
        header('Content-type:application/vnd.ms-excel;charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        header('Content-type: text/x-csv');
        // header('Content-type: application/vnd.ms-excel;charset=UTF-8');
        // 打开PHP标准输出流以写入追加的方式打开
        $fp     = fopen($absPath, 'a');
        
        // 设置标题
        $header = [
                '用户ID', '昵称', '手机号', 
                '性别', '年龄', '国籍', '会员', '等级',
                '余额', '直属上级', '推广员', '状态',
                '注册时间', '累计充值', '累计提现', '渠道来源'];
        if( 0 === $downOffset) {
            $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
            fputcsv($fp, [$bom] );
            fputcsv($fp, $header );
        }

        $sqlTotal   = 'SELECT COUNT(user.id) AS total FROM user_' . DATABASESUFFIX . '_0 user WHERE ' . $where;
        $retTotal   = $this->db->query($sqlTotal)->row_array()['total'];

        $country = $this->db->query("select id, country from cat_country_" . DATABASESUFFIX)->result_array();
        $country = array_index($country, 'id');
        
        $sql = "SELECT user.id, user.nickname, user.mobile, user.gender, user.age, user.country, IF(user.vip_expire>NOW() OR user.svip_expire > NOW(), 'YES', 'NO') AS vip_expire, user.level, user.eurc_balance, user.upper_uid, user.sm_uid, IF(user.status=1, 'OFF', 'ON') as status, user.join_date, pay.money as recharge_total, wr.amount, user.advert_id
            FROM user_1_0 user 
            LEFT JOIN (
                SELECT uid, SUM(money) AS money FROM pay_deposit_$appid
                WHERE status=1
                GROUP BY uid
            ) pay ON user.id = pay.uid
            LEFT JOIN (
                SELECT uid, SUM(amount) AS amount FROM withdrawal_record_$appid
                WHERE status=2 AND pay_status=1
                GROUP BY uid
            ) wr ON user.id = wr.uid
            WHERE $where
            ORDER BY user.id DESC
            LIMIT $downSize OFFSET $downOffset";
// @file_put_contents(ERRLOG_PATH . '/testing_log_' . date("Ymd") . '.log',
//             date("Y-m-d H:i:s") . "sql excuted: $sql \n",
//             FILE_APPEND);
        $rows = $this->db->query($sql)->result_array();

        $new_data = [];
        foreach ($rows as $key => &$row) {
            
            if ($row['gender'] == 2) {
                $row['gender'] = get_tips(1025);
            } elseif ($row['gender'] == 1) {
                $row['gender'] = get_tips(1026);
            } else {
                $row['gender'] = get_tips(1027);
            }
            $row['country']  = get_tips(1028);
            if (isset($country[$row['country']])) {
                $row['country'] = $country[$row['country']]['country'];
            }

            $row['join_date'] = time_to_local_string($row['join_date']);
            $row['recharge_total'] = number_format($row['recharge_total'] ?? 0, 2);
            $row['amount'] = number_format($row['amount'] ?? 0, 2);
            $row['advert_id'] = $row['advert_id'] ?? '-';

            fputcsv($fp, $row );
            
        }

        fclose($fp);

        $data = [ 'offset' => $downOffset + $downSize,
                    'end' => ($downOffset + $downSize > $retTotal) ? true : false,
                    'filename' => ($downOffset + $downSize > $retTotal) ? $path['relative_path'] : $filename];

        ajax_return(SUCCESS, get_tips(1002), $data );
        exit;
    }


    /**
     * 手动加扣款（上下分）
     */
    public function trans_money_deal_info()
    {
        $uid = intval(isset($_POST['uid']) ? $_POST['uid'] : 0);
        if (!$uid) {
            ajax_return(ERROR, '参数错误');
        }

        $info = isset($_POST['info']) ? $_POST['info'] : '';

        $usercode = trim($_POST['usercode'] ?? '');

        if (!$info) {
            ajax_return(ERROR, "输入参数错误");
        }

        $info = json_decode($info, true);

        if (!isset($info['type']) || !isset($info['amount'])) {
            ajax_return(ERROR, "输入参数错误");
        }

        $appid = DATABASESUFFIX;

        $this->db->trans_begin();

        $coin = 'eurc';

        $memo = trim($info['memo'] ?? '');

        $user_sql = "select id from admin_user_{$appid} where usercode = '$usercode'";
        $userinfo = $this->db->query($user_sql)->row_array();

        $op_uid = 0;
        if ($userinfo) {
            $op_uid = $userinfo['id'];
        }

        try {
            //type 1：扣钱，2：加钱

            if ($info['type'] == 1) {
                //扣钱
                $pay_type = 2;
                $amount = -(floatval($info['amount']));
            } else {
                //加钱派发
                $amount = floatval($info['amount']);
                $pay_type = 1;
            }
            $time = time();
            // 查询余额
            $balance = $this->redis->hget("user:$uid", $coin . '_balance');
            if ($amount < 0 && ($balance <= 0 || $balance < abs($amount))) {
                $this->db->trans_rollback();
                ajax_return(ERROR, "余额不足");
            }

//            if ($info['type'] == 1) {
//                $check_buy_sql = "select code_amount,recharge_amount from count_code_recharge_amount_{$appid} where uid={$uid}";
//
//                $check_buy = $this->db->query($check_buy_sql)->row_array();
//
//                $check_buy = $check_buy && count($check_buy) ? $check_buy : [];
//
//                if (intval($check_buy['recharge_amount']) <= 0) {
//                    $this->db->trans_rollback();
//                    ajax_return(ERROR, "累计充值额为0，不能进行扣钱操作");
//                }
//                if ((floatval($check_buy['recharge_amount']) - abs(floatval($amount))) < 0) {
//                    $this->db->trans_rollback();
//                    ajax_return(ERROR, "累计充值额不够，不足以进行扣钱");
//                }
//            }


            $insert_field = "(`uid`,`amount`,`type`,`memo`,`uptime`,`op_uid`)";
            $insert_value = "({$uid},{$amount},{$pay_type},'{$memo}',{$time},{$op_uid})";
            $sql = "insert into other_pay_add_money_record_{$appid} {$insert_field} values {$insert_value}";
            $insert_id = $this->db->query($sql)->insert_id();


            if ($insert_id) {
                if ($info['type'] == 1) {
                    //扣钱

                    //累计充值
//                    Util::add_or_reduce_calc_total_baby_coin($appid, $uid, 0, -abs(floatval($amount)));

                    $pipe = $this->redis->pipeline();
                    $pipe->hIncrByFloat("user:$uid", STABLE_CURRENCY_NAME . '_balance', -abs(floatval($amount)));
                    $pipe->sAdd("user:balance:update", $uid);
                    $pipe->exec();

                } else {
                    //加钱派发

                    //累计充值
//                    Util::add_or_reduce_calc_total_baby_coin($appid, $uid, 0, abs(floatval($amount)));

                    $pipe = $this->redis->pipeline();
                    $pipe->hIncrByFloat("user:$uid", STABLE_CURRENCY_NAME . '_balance', abs(floatval($amount)));
                    $pipe->sAdd("user:balance:update", $uid);
                    $pipe->exec();

                }


                $this->db->trans_commit();
                ajax_return(SUCCESS, "操作成功", [$insert_id, $this->redis->hget('user:' . $uid, 'eurc_balance')]);

            } else {
                $this->db->trans_rollback();
                ajax_return(ERROR, "操作失败");
            }


        } catch (Exception $exception) {

            $this->db->trans_rollback();

            ajax_return(ERROR, "操作失败");
        }


    }

    /**
     *
     * @package  查看用户接口
     */
    public function get_userinfo()
    {
        $uid = intval(htmlspecialchars(isset($_POST['uid'])) ? $_POST['uid'] : 0);
        if (!$uid) {
            ajax_return("0", get_tips(1006));
        }
        $this->init_db();
        //互关字段不要了
        $res = $this->redis->hmget('user:' . $uid, ['nickname', 'avatar', 'status', 'gender', 'country', 'following_num', 'follower_num', 'msq_balance', 'eurc_balance', 'vip_expire', 'svip_expire', 'rating_sum', 'rating_num']);
        $res['avatar'] = get_pic_url($res['avatar'], 'avatar');
        $res['country'] = intval($res['country']) ?? 0;
        $country_sql = "select country from cat_country_" . DATABASESUFFIX . " where id = {$res['country']}";
        $country_arr = $this->db->query($country_sql)->row_array();
        $res['country'] = $country_arr['country'];
        switch ($res['status']) {    //审核状态
            case 0:
                $res['status'] = get_tips(2025);
                break;
            case 1:
                $res['status'] = get_tips(2026);
                break;
            case 2:
                $res['status'] = get_tips(2016);
                break;
            case 3:
                $res['status'] = get_tips(5027);
                break;
        }
        //查询是否实名认证
        $certiyf_sql = "select id from  user_certify_" . DATABASESUFFIX . " where uid = {$uid}";
        $certify_arr = $this->db->query($certiyf_sql)->row_array();
        if (!empty($certify_arr)) {
            $res['certify'] = get_tips(8065);
        } else {
            $res['certify'] = get_tips(8066);
        }

        //sql查询用户信息
        $data = $this->db->query('select id,is_recommend, is_certified, is_anchor, join_date, mobile,video_fee,pm_fee from user_' . DATABASESUFFIX . '_0 where id=' . $uid)->row_array();
        $data['join_date'] = time_to_local_string($data['join_date']);
        if ($data['is_recommend']) {  //推荐
            $data['is_recommend'] = 'yes';
        } else {
            $data['is_recommend'] = 'no';
        }
        //        if($data['is_certified']){  //实名
        //            $data['is_certified'] = '是';
        //        }else{
        //            $data['is_certified'] = '否';
        //        }
        if ($data['is_anchor']) { //主播
            $data['is_anchor'] = 'yes';
        } else {
            $data['is_anchor'] = 'no';
        }

        //查询所属工会sociaty_id
        $sociaty_id = $this->redis->hGet("user:$uid", 'sociaty_id');
        if ($sociaty_id) {
            $sql = "select name from sociaty_" . DATABASESUFFIX . " where id = $sociaty_id";
            $result = $this->db->query($sql)->row_array();
            $data['sociaty'] = $result['name'];
        } else {
            $data['sociaty'] = get_tips(8067);
        }
        $data['video_fee'] = number_format($data['video_fee'], 2);
        $data['pm_fee'] = number_format($data['pm_fee'], 2);
        //系统版本
        $sys_info = $this->db->query('select platform,imei,imsi,device,os_version,app_version,market_id from user_login_' . DATABASESUFFIX . ' where uid=' . $uid)->row_array();
        $sys_info = isset($sys_info['platform']) ? $sys_info : [];
        $data = isset($data['is_recommend']) ? $data : [];
        //主播评分
        $score = $this->redis->hmGet('user:' . $uid, ['rating_sum']);
        $result = array_merge($res, $data, $sys_info, $score);
        ajax_return(SUCCESS, '', $result);
    }

    /**
     *
     * @package 推荐
     */
    public function prompt()
    {
        $uid = intval($_POST['uid'] ?? 0);
        if (!$uid) {
            ajax_return("0", get_tips(8068));
        }
        $this->init_db();
        $uid_res = $this->db->query('select id uid from user_' . DATABASESUFFIX . '_0  where  id =' . $uid)->row_array();
        if (!$uid_res['uid']) {
            ajax_return("0", get_tips(8068));
        }
        $this->redis->hSet(sprintf(RedisKey::USER, $uid), 'is_recommend', '1');
        $this->init_db();
        $res = $this->db->query('update user_' . DATABASESUFFIX . '_0 set is_recommend=1 where id =' . $uid);
        if ($res->affected_rows() >= 0) {
            ajax_return(SUCCESS, '');
        }
        ajax_return(ERROR, get_tips(1004));
    }

    /**
     *
     * @package 測試用戶
     */
    public function fake()
    {
        $uid = intval($_POST['uid'] ?? 0);
        if (!$uid) {
            ajax_return("0", get_tips(8068));
        }
        $this->init_db();
        $sql = "update user_" . DATABASESUFFIX . "_0 set is_fake = 1 where id = $uid";
        $res = $this->db->query($sql)->affected_rows();
        if ($res) {
            $this->redis->hSet(sprintf(RedisKey::USER, $uid), 'is_fake', 1);
            ajax_return(SUCCESS, '操作成功');
        }
        ajax_return(ERROR, get_tips(1004));
    }

    /**
     * @package 封禁和解禁
     */
    public function block()
    {
        $uid = intval(htmlspecialchars(isset($_POST['uid'])) ? $_POST['uid'] : 0);
        $admin_id = $this->user['id'];
        $comm = htmlspecialchars(isset($_POST['comm']) ? $_POST['comm'] : ''); //处理原理
        $region = intval($_POST['region'] ?? 0); //封禁方式   0解封
        $renew = $_POST['renew'] ?? ""; //封禁结束时间
        if (empty($renew)) {
            $renew = '2000-01-01 00:00:00';
        }

        if (!$uid) {
            ajax_return(ERROR, get_tips(8068));
        }

        if (!$comm && $region) {
            ajax_return(ERROR, get_tips(8069));
        }

        $this->init_db();
        $res = $this->redis->hmGet(sprintf(RedisKey::USER, $uid), ['nickname', 'gender']);
        $nickname = $res['nickname'];
        $gender = $res['gender'];
        $admin_name = $this->user['username'];
        if ($region) {
            // 封禁
            // 更新用户状态标识
            $usercode = (string)$this->redis->hGet(sprintf(RedisKey::USER, $uid), 'usercode');
            $cache_key = 'usercode:' . substr(md5($usercode), 0, 3);
            $this->redis->hDel($cache_key, $usercode);
            $this->redis->hSet(sprintf(RedisKey::USER, $uid), 'status', 1);
            $this->redis->hDel(sprintf(RedisKey::USER, $uid), 'usercode');
            // 从在线用户中删除
            $this->redis->zrem("user:online:$gender", $uid);
            $this->redis->zrem("user:video_online:$gender", $uid);

            // 更新用户表数据
            $sql = "UPDATE user_" . DATABASESUFFIX . "_0 SET status = 1 WHERE id = " . $uid;
            $this->db->query($sql);

            //写入日记记录
            $uptime = date('Y-m-d m:i:s');
            $sql = "INSERT INTO user_status_" . DATABASESUFFIX . " (uid, status, admin_id, uptime ,comm, admin_name, nickname, renew) VALUES ({$uid}, {$region}, {$admin_id}, '{$uptime}', '{$comm}', '{$admin_name}', '{$nickname}', '$renew')";
            $insert_id = $this->db->query($sql)->insert_id();
            if ($insert_id > 0) {
                ajax_return(SUCCESS, get_tips(1005));
            }
        } else {
            // 解封
            $this->redis->hSet(sprintf(RedisKey::USER, $uid), 'status', 0);
            $res = $this->db->query('UPDATE user_' . DATABASESUFFIX . '_0 SET status = 0 where id =' . $uid);
            if ($res->affected_rows() >= 0) {
                //写入日记记录
                $uptime = date('Y-m-d m:i:s');
                $sql = "UPDATE user_status_" . DATABASESUFFIX . " SET status = 0, admin_id_2 = $admin_id, uptime = '$uptime' WHERE uid = $uid AND status = 1";
                $res = $this->db->query($sql);
                if ($res->affected_rows() > 0) {
                    ajax_return(SUCCESS, get_tips(1005));
                }
            }
        }

        ajax_return(ERROR, get_tips(1004));
    }

    /**
     * @package 头像列表
     */
    public function head_list()
    {
        $page_size = intval(isset($_POST['page_size']) ? htmlspecialchars($_POST['page_size']) : ADMIN_PAGE_SIZE);
        $page = intval(isset($_POST['page']) ? htmlspecialchars($_POST['page']) : 1);
        $uid = intval(isset($_POST['uid']) ? $_POST['uid'] : 0);
        $nickname = htmlspecialchars(isset($_POST['nickname']) ? $_POST['nickname'] : '');
        $status = is_numeric($_POST['status']) ? $_POST['status'] : ''; //0,未审核，1，审核通过，2，审核失败，3，已删除
        $type = intval(isset($_POST['type']) ? htmlspecialchars($_POST['type']) : 1);
        $limit = (($page - 1) * $page_size);

        $where = ' where 1=1';
        if ($uid) {
            $where .= ' and pic.uid=' . $uid;
        }
        if ($nickname) {
            $where .= " and user.nickname like '$nickname%'";
        }
        if ($status !== '') {
            $where .= " and  pic.status =  $status";
        }

        $this->init_db();
        $sql = 'select pic.id, pic.uid, pic.pic_url, pic.thumb_url, pic.status, pic.uptime,user.nickname from user_pic_' . DATABASESUFFIX . " as pic left join user_" . DATABASESUFFIX . "_0 as user on pic.uid = user.id" . $where . ' and pic.type = 1 ORDER BY pic.id DESC limit ' . $page_size . ' offset ' . $limit;

        $res = $this->db->query($sql)->result_array();
        foreach ($res as $k => $v) {
            $res[$k]['uptime'] = time_to_local_string($v['uptime']);
            switch ($v['status']) {
                case 0:
                    $res[$k]['status_txt'] = get_tips(2025);
                    break;
                case 1:
                    $res[$k]['status_txt'] = get_tips(2015);
                    break;
                case 2:
                    $res[$k]['status_txt'] = get_tips(2016);
                    break;
                case 3:
                    $res[$k]['status_txt'] = get_tips(5027);
                    break;
            }
            $res[$k]['pic_url'] = get_pic_url($v['pic_url'], 'avatar');
        }

        if ($type == 2) {
            $header = array(
                get_tips(2002),
                get_tips(2003),
                get_tips(8055),
                get_tips(1001),
                get_tips(2009),
            );

            $new_data = [];
            foreach ($res as $k => $v) {
                $new_data[$k]['uid'] = $v['uid'];
                $new_data[$k]['nickname'] = $v['nickname'];
                $new_data[$k]['pic_url'] = $v['pic_url'];
                $new_data[$k]['uptime'] = $v['uptime'];
                $new_data[$k]['status'] = $v['status'];
            }
            $path = export(get_tips(8071), $header, $new_data);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        $total_count_res = $this->db->query("select count(pic.id) num from  user_pic_" . DATABASESUFFIX . " as pic left join user_" . DATABASESUFFIX . "_0 as user on pic.uid = user.id $where and pic.type = 1")->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }
        $data = array(
            'list' => $res,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => (int)$total_count_res['num']
        );

        ajax_return(SUCCESS, '', $data);
    }

    /**
     * @package 头像审核
     */
    public function pic_trial()
    {
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        $status = intval($_POST['status']);

        $this->init_db();
        $sql = "select pic_url,uid from user_pic_" . DATABASESUFFIX . " where id in ($id)";
        $pic_arr = $this->db->query($sql)->result_array();
        if (empty($pic_arr)) {
            ajax_return(ERROR, 'empty');
        }

        //执行
        $blog_sql = "update user_pic_" . DATABASESUFFIX . " set status = $status where id in ($id)";
        $this->db->query($blog_sql);

        foreach ($pic_arr as $k => $v) {
            //审核不通过redis
            if ($status != 1) {
                // 清空缓存中的头像地址（清空后会取默认图片）
                $this->redis->hSet("user:{$v['uid']}", "avatar", "");
                // 删除不通过的头像文件
                $v['pic_url'] = preg_replace("/\?.*/", "", $v['pic_url']);
                unlink(UPLOAD_PATH . $v['pic_url']);
            }

            send_system_msg($v['uid'], $status == 1 ? get_tips(8072) : get_tips(8073));
        }

        ajax_return(SUCCESS, get_tips(1005));
    }


    /**
     * @package 封禁列表
     */
    public function block_list()
    {
        $page = intval($_POST['page'] ?: 1);
        $page_size = ADMIN_PAGE_SIZE;
        $limit = (($page - 1) * $page_size);
        $uid = intval($_POST['uid'] ?: 0);
        $nickname = htmlspecialchars($_POST['nickname'] ?: '');
        $block = intval($_POST['block_type'] ?: 0);
        $block_time_start = input('post.block_time_start', '', '');
        $block_time_end = input('post.block_time_end', '', '');
        $admin_name = input('post.operator', '', '');
        $type = intval($_POST['type'] ?: 0);

        // 条件处理
        $where = 'users.status = 1';
        if ($nickname) {
            $where .= ' and user_status.nickname like "%' . $nickname . '%" ';
        }
        if ($block_time_start > 0 && $block_time_end > $block_time_end) {
            $where .= ' and user_status.uptime >=' . $block_time_start . ' and user_status.uptime <=' . $block_time_end;
        }
        if ($admin_name) {
            $where .= " and user_status.admin_name = '$admin_name'";
        }
        if ($block) {
            $where .= " and user_status.status = $block";
        } else {
            $where .= " and user_status.status in(1,2)";
        }
        if ($uid) {
            $where .= " and user_status.uid = $uid";
        }

        //查询
        $table = 'user_' . DATABASESUFFIX . '_0';
        $join_table = "user_status_" . DATABASESUFFIX;
        $fields = 'user_status.uid, user_status.nickname, user_status.status, user_status.admin_name, user_status.comm, user_status.uptime';
        $sql = "SELECT {$fields} FROM {$table} AS users left join {$join_table} as user_status ON users.id = user_status.uid WHERE {$where} ORDER BY user_status.uptime DESC LIMIT {$page_size} OFFSET {$limit}";

        $res = $this->db->query($sql)->result_array();

        if ($type == 2) {
            $header = array(
                get_tips(2002),
                get_tips(2003),
                get_tips(8055),
                get_tips(8074),
                get_tips(8075),
                get_tips(8076),
            );
            $new_data = [];
            foreach ($res as $key => $val) {
                $new_data[$key]['uid'] = $val['uid'];
                $new_data[$key]['nickname'] = $val['nickname'];
                $new_data[$key]['avatar'] = get_pic_url($this->redis->hGet(sprintf(RedisKey::USER, $val['uid']), 'avatar') ?? '', 'avatar');
                $new_data[$key]['comm'] = $val['comm'];
                switch ($val['status']) {
                    case 1:
                        $new_data[$key]['status'] = get_tips(8077);
                        break;
                    case 2:
                        $new_data[$key]['status'] = get_tips(8078);
                        break;
                }
                $new_data[$key]['admin_name'] = $val['admin_name'];
            }

            $path = export('封禁列表', $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        foreach ($res as $key => $val) {
            switch ($val['status']) {
                case 1:
                    $res[$key]['status'] = get_tips(8077);
                    break;
                case 2:
                    $res[$key]['status'] = get_tips(8078);
                    break;
            }
            $res[$key]['avatar'] = get_pic_url($this->redis->hGet(sprintf(RedisKey::USER, $val['uid']), 'avatar') ?? '', 'avatar');
        }

        $total_count_res = $this->db->query("select count(user_status.uid) as num from {$table} as users left join {$join_table} as user_status on users.id = user_status.uid where {$where}")->row_array();

        $page_count = $total_count_res['num'] / $page_size;

        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }
        $data = array(
            'list' => $res,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => (int)$total_count_res['num']
        );
        ajax_return(SUCCESS, '', $data);
    }

    /**
     * @package 所有会员列表
     */
    public function vip_list()
    {
        $page_size = intval(isset($_POST['page_size']) ? htmlspecialchars($_POST['page_size']) : ADMIN_PAGE_SIZE);
        $page = intval(isset($_POST['page']) ? htmlspecialchars($_POST['page']) : 1);
        $uid = intval(htmlspecialchars(isset($_POST['uid'])) ? $_POST['uid'] : 0);
        $nickename = trim(isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : '');
        $vip_name = isset($_POST['vip_name']) ? htmlspecialchars($_POST['vip_name']) : ''; //1个月, 3,个月，终身会员
        $vip_type = intval(isset($_POST['vip_type']) ? $_POST['vip_type'] : 0);  //1,vip, 2 svip
        $type = intval(isset($_POST['type']) ? $_POST['type'] : 0);
        // if ($page_size > 50) {
        //     $page_size = 50;
        // }

        $this->init_db();
        // $res= $this->db->query('select id,nickname,join_date,vip_expire from  user_' . DATABASESUFFIX . '_0 where UNIX_TIMESTAMP(vip_expire) >UNIX_TIMESTAMP(now()) limit '. $page_size . ' offset ' . ($page - 1) * $page_size)->result_array();
        $where = ' where 1=1';
        if ($uid) {
            $where .= ' and pay_uid=' . $uid;
        }
        if ($nickename) {
            $where .= " and nickname like '%$nickename%'";
        }
        if ($vip_name) {
            $where .= " and vip_name like '{$vip_name}%'";
        }
        if ($vip_type) {
            $where .= ' and vip_type =' . $vip_type;
        }

        $res = $this->db->query('select id, pay_uid uid, fee, vip_type, vip_id, vip_name, active_life, vip_expire, svip_expire, uptime from exp_vip_' . DATABASESUFFIX . $where . ' limit ' . $page_size . ' offset ' . ($page - 1) * $page_size)->result_array();
        foreach ($res as $k => &$v) {
            $v['uptime'] = time_to_local_string($v['uptime']);
            $v['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $v['uid']), 'nickname');
            if ($v['vip_type'] == 1) {
                $res[$k]['vip_type'] = 'vip';
                $res[$k]['vip_expire'] = $v['vip_expire'];
            } elseif ($v['vip_type'] == 2) {
                $res[$k]['vip_type'] = 'svip';
                $res[$k]['vip_expire'] = $v['svip_expire'];
            }
            $res[$k]['active_life'] = $v['active_life'] . 'day';
        }

        if ($type == 2) {
            $header = array(
                get_tips(2002),
                get_tips(2003),
                get_tips(8079),
                get_tips(8080),
                get_tips(6032),
            );

            $new_data = [];
            foreach ($res as $k => $v) {
                $new_data[$k]['uid'] = $v['uid'];
                $new_data[$k]['nickname'] = $v['nickname'];
                $new_data[$k]['active_life'] = $v['active_life'];
                $new_data[$k]['vip_type'] = $v['vip_type'];
                $new_data[$k]['vip_expire'] = $v['vip_expire'];
            }
            $path = export(get_tips(8081), $header, $new_data);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        $total_count_res = $this->db->query('select count(pay_uid) num from exp_vip_' . DATABASESUFFIX . $where)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }
        $data = array(
            'list' => $res,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => (int)$total_count_res['num']
        );
        ajax_return(SUCCESS, '', $data);
    }

    /**
     * @package --超级会员列表
     */
    public function svip_list()
    {
        $page_size = intval(isset($_POST['page_size']) ? htmlspecialchars($_POST['page_size']) : 10);
        $page = intval(isset($_POST['page']) ? htmlspecialchars($_POST['page']) : 1);

        if ($page_size > 50) {
            $page_size = 50;
        }
        $this->init_db();
        $res = $this->db->query('select id,nickname,avatar,join_date,svip_expire from  user_' . DATABASESUFFIX . '_0 where UNIX_TIMESTAMP(svip_expire) >UNIX_TIMESTAMP(now()) limit ' . $page_size . ' offset ' . ($page - 1) * $page_size)->result_array();
        $total_count_res = $this->db->query('select count(id) num from  user_' . DATABASESUFFIX . '_0 where UNIX_TIMESTAMP(svip_expire) >UNIX_TIMESTAMP(now())  limit ' . $page_size . ' offset ' . ($page - 1) * $page_size)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }
        $data = array(
            'list' => $res,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => $total_count_res['num']
        );
        ajax_return("success", '', $data);
    }

    /**
     *
     * @package 查看会员详情
     */
    public function get_vip_info()
    {
        $id = intval($_POST['id']); //exp_vip id
        if (!$id) {
            ajax_return(ERROR, get_tips(8068));
        }
        $res = $this->db->query('select pay_uid, uptime, svip_expire, active_life, vip_expire, vip_type from exp_vip_' . DATABASESUFFIX . ' where pay_uid=' . $id)->result_array();

        foreach ($res as $k => $val) {
            $res[$k]['nickname'] = $this->redis->hGet("user:{$val['pay_uid']}", 'nickname');
            if ($val['vip_type'] == 1) {
                $res[$k]['vip_type'] = 'vip';
                $res[$k]['exp_time'] = $val['vip_expire'];
            } else {
                $res[$k]['vip_type'] = 'svip';
                $res[$k]['exp_time'] = $val['svip_expire'];
            }
        }


        ajax_return(SUCCESS, get_tips(1005), $res);
    }

    /**
     * @package  导出用户头像列表
     */
    public function export_head()
    {
        $page_size = intval(isset($_POST['page_size']) ? htmlspecialchars($_POST['page_size']) : ADMIN_PAGE_SIZE);
        $page = intval(isset($_POST['page']) ? htmlspecialchars($_POST['page']) : 1);
        $uid = intval(isset($_POST['uid']) ? $_POST['uid'] : 0);
        $nickname = htmlspecialchars($_POST['nickname'] ?? '');
        $status = intval(isset($_POST['status']) ? $_POST['status'] : ''); //0,未审核，1，审核通过，2，审核失败，3，已删除
        if ($page_size > 50) {
            $page_size = 50;
        }

        $where = ' where 1=1';
        if ($uid) {
            $where .= ' and uid=' . $uid;
        }

        $this->init_db();
        $res = $this->db->query('select uid,pic_url,status from  user_pic_' . DATABASESUFFIX . $where . '  and  type=1  limit ' . $page_size . ' offset ' . ($page - 1) * $page_size)->result_array();
        $data = array();
        foreach ($res as $k => &$v) {
            $v['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $v['uid']), 'nickname');
            if ($nickname) {
                if (strpos($v['nickname'], $nickname) !== false) {
                    $data[] = $v;
                }
            }
        }

        if (!empty($data)) {
            $res = $data;
        }
        $title = get_tips(8082);
        $header = [
            get_tips(2002),
            get_tips(8055),
            get_tips(2009),
            get_tips(8083)
        ];
        export($title, $header, $res);
    }

    /**
     * 重置密码 reset_password
     *
     * @return void
     */
    public function reset_password()
    {
        $uid = intval($_POST['uid'] ?: 0);
        $pwd = sql_format($_POST['pwd'] ?: 0);
        $repwd = sql_format($_POST['repwd'] ?: 0);
        if (!$uid || !$pwd || !$repwd) ajax_return(ERROR, get_tips(1006));

        if ($pwd != $repwd) {
            ajax_return(ERROR, get_tips(8084));
        }

        $pwd = password_hash($pwd, PASSWORD_DEFAULT);
        $sql = "update user_" . DATABASESUFFIX . "_0 set password = '{$pwd}' where id = {$uid}";
        $result = $this->db->query($sql)->affected_rows();

        $save_status = ERROR;
        $save_msg = get_tips(1004);
        if ($result) {
            $this->redis->hDel(sprintf(RedisKey::USER, $uid), 'usercode');
            $save_msg = get_tips(1005);
            $save_status = SUCCESS;
        }

        ajax_return($save_status, $save_msg);
    }

    /**
     * 重置头像 reset_head
     *
     * @return void
     */
    public function reset_head()
    {
        $uid = intval($_POST['uid'] ?: 0);
        $img_url = sql_format($_POST['img_url'] ?? '');
        if (!$img_url || !$uid) ajax_return(ERROR, get_tips(1006));

        $sql = "update user_" . DATABASESUFFIX . "_0 set avatar = '{$img_url}' where id = {$uid}";
        $result = $this->db->query($sql)->affected_rows();

        $save_status = ERROR;
        $save_msg = get_tips(1004);
        if ($result) {
            $save_msg = get_tips(1005);
            $save_status = SUCCESS;
        }

        ajax_return($save_status, $save_msg);
    }

    /**
     * 用户分销列表 user_distribution
     *
     * @return void
     */
    public function user_distribution()
    {
        $page_size = ADMIN_PAGE_SIZE;
        // 处理参数
        $page = input('post.page', '', 1);
        $limit = (($page - 1) * $page_size);
        $uid = input('post.uid', 'intval', 0);
        $nickname = input('post.nickname', 'htmlspecialchars');
        $exp = input('post.exp', 'intval', 1);
        // $end = $limit + $page_size;

        // 从redis里获取数据
        $rank = $this->redis->zRange('money:pyra:rank', 0, -1, true);
        $eurc = $this->redis->zRange('money:pyra:rank:eurc', 0, -1, true);
        // $msq = $this->redis->zRange('money:pyra:rank:msq', 0, 0, true);

        // 获得交集
        $new = [];
        if (!empty($rank)) {
            $new = $this->merge_distribution($new, $rank, 'rank');
        }
        if (!empty($eurc)) {
            $new = $this->merge_distribution($new, $eurc, 'eurc');
        }
        // if (!empty($msq)) {
        //     $new = $this->merge_distribution($new, $msq, 'msq');
        // }
        // 获取信息
        foreach ($new as &$item) {
            $user_info = $this->redis->hMGet(sprintf(RedisKey::USER, $item['uid']), ['nickname', 'mobile', 'upper_uid']);
            $item['nickname'] = $user_info['nickname'] ?: '';
            $item['invitation_user_num'] = $this->redis->sCard('user:lower:' . $item['uid']) ?: 0;
            $item['total'] = ($item['rank'] ?? 0) + ($item['eurc'] ?? 0);
            $item['upper_uid'] = $user_info['upper_uid'] ?: '';
            if ($user_info['upper_uid']) {
                $item['upper_name'] = $this->redis->hGet(sprintf(RedisKey::USER, $user_info['upper_uid']), 'nickname');
            } else {
                $item['upper_name'] = '';
            }
        }

        // 处理搜索
        $new = array_values($new);
        if ($exp == 2) {
            $exp_list = [];
            $header = array(
                get_tips(2002),
                get_tips(2003),
                get_tips(8085),
                get_tips(8086),
                get_tips(8087),
                get_tips(8088),
                get_tips(8089),
                get_tips(8090),
                get_tips(8091),
                get_tips(8092),
            );
        }
        if ($nickname || $uid || $exp == 2) {
            foreach ($new as $key => $items) {
                // 搜索昵称
                if ($nickname && strpos($items['nickname'], $nickname) === false) {
                    unset($new[$key]);
                }
                // 搜索uid
                if ($items['uid'] != $uid && $uid > 0) {
                    unset($new[$key]);
                }
                if ($exp == 2) {
                    $exp_list[$key]['uid'] = $items['uid'];
                    $exp_list[$key]['nickname'] = $items['nickname'];
                    $exp_list[$key]['up'] = $items['upper_name'] . '(' . $items['upper_uid'] . ')';
                    $exp_list[$key]['invitation_user_num'] = $items['invitation_user_num'];
                    $exp_list[$key]['lower_user_num'] = $items['invitation_user_num'];
                    $exp_list[$key]['rank'] = $items['rank'] ?? 0;
                    // $exp_list[$key]['msq'] = $items['msq'] ?? 0;
                    $exp_list[$key]['eurc'] = $items['eurc'];
                    $exp_list[$key]['total'] = $items['total'];
                }
            }
        }
        // 导出
        if ($exp == 2) {
            $page_list = array_splice($exp_list, $limit, $page_size);
            $path = export(get_tips(8093), $header, $page_list);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        // 统计条数形成分页
        $total = $this->get_distribution_total();
        $page_list = array_splice($new, $limit, $page_size);

        // 返回数据
        ajax_return(SUCCESS, get_tips(1003), array(
            'data' => $page_list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 用户分销详情 user_distribution_desc
     *
     * @return void
     */
    public function user_distribution_desc()
    {
        $page_size = ADMIN_PAGE_SIZE;
        $page = input('post.page', 'intval', 1);
        $uid = input('post.uid', 'intval', 0);
        $exp = input('post.exp', 'intval', 1);
        $limit = (($page - 1) * $page_size);
        if (!$uid) ajax_return(ERROR, get_tips(1006));
        $lower_user = $this->redis->sMembers('user:lower:' . $uid);
        //  处理数据
        $new = [];
        foreach ($lower_user as $item) {
            $new[$item]['level'] = get_tips(8094);
            $new[$item]['uid'] = $item;
            $new[$item]['nickname'] = $this->redis->hGet(sprintf(RedisKey::USER, $item), 'nickname') ?: 'empty';
            $rank = $this->redis->zScore('money:pyra:rank', $item) ?: 0;
            $eurc = $this->redis->zScore('money:pyra:rank:eurc', $item) ?: 0;
            $msq = $this->redis->zScore('money:pyra:rank:msq', $item) ?: 0;
            $new[$item]['rank'] = $rank;
            $new[$item]['eurc'] = $eurc;
            $new[$item]['msq'] = $msq;
            $new[$item]['total'] = $rank + $eurc;
            $new[$item]['reg_date'] = date('Y-m-d H:i:s');
        }

        // 统计数量
        $total = $this->redis->sCard('user:lower:' . $uid);
        $page_list = array_splice($new, $limit, $page_size);
        // 导出
        if ($exp == 2) {
            $header = array(
                get_tips(8095),
                get_tips(2002),
                get_tips(2003),
                get_tips(8096),
                get_tips(8097),
                get_tips(8098),
                get_tips(8092),
                get_tips(8063),
            );
            $path = export(get_tips(8099), $header, $page_list);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        // 返回数据
        ajax_return(SUCCESS, get_tips(1003), array(
            'data' => $page_list,
            'total' => intval($total),
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => intval($total / $page_size)
        ));
    }

    /**
     * 获取用户分销列表的数量 get_distribution_total
     *
     * @return void
     */
    private function get_distribution_total()
    {
        $rank = $this->redis->zCard('money:pyra:rank');
        $eurc = $this->redis->zCard('money:pyra:rank:eurc');
        $msq = $this->redis->zCard('money:pyra:rank:msq');
        $sort = [(int)$rank, (int)$eurc, (int)$msq];
        rsort($sort);
        return $sort[0];
    }

    /**
     * 处理数据 merge_distribution
     *
     * @param array $rank
     * @param string $type
     * @return void
     */
    private function merge_distribution(array $new, array $rank, string $type)
    {
        // 合并数组
        foreach ($rank as $uid => $score) {
            if (isset($new[$uid])) {
                if (isset($new[$uid][$type])) {
                    $new[$uid][$type] += $score / MONEY_DECIMAL_MULTIPLE;
                    continue;
                }
                $new[$uid][$type] = $score / MONEY_DECIMAL_MULTIPLE;
                continue;
            }
            $new[$uid] = [
                $type => $score / MONEY_DECIMAL_MULTIPLE,
                'uid' => $uid
            ];
        }
        return $new;
    }
}
