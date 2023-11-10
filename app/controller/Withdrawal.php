<?php

/**
 * 手动提现相关接口
 * Class Withdrawal
 */
class Withdrawal extends Controller
{

    private $trans_rate = 0.25;  //baby与金额的转化比例

    public function __construct()
    {
        $this->init_db();
        parent::__construct();
    }


    /**
     * 获取数据
     */
    public function index()
    {

        $appid = DATABASESUFFIX;

        $page = intval($_POST['page'] ?? 1);
        $size = intval($_POST['size'] ?? 10);

        $type = intval($_POST['type'] ?? 0);

        $status = intval($_POST['status'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);
        $time = $_POST['time'] ?? [];
        $nickname = trim($_POST['nickname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $is_anchor = trim($_POST['is_anchor'] ?? '');
        $order_id = trim($_POST['order_id'] ?? '');

        $usercode = trim($_POST['usercode'] ?? '');

        $offset = ($page - 1) * $size;


        $sql = "select w.id,w.uid,w.username,w.curr_baby,w.total_profit_loss,w.total_recharge,w.total_lose,w.return_money,w.amount,w.order_id,w.created_at,u.mobile,u.status as user_status,u.is_anchor from withdrawal_record_{$appid} as w left join user_{$appid}_0 as u on u.id=w.uid where w.is_deleted=0 and w.status=1";

        if ($status > 0) {
            $sql .= $status == 1 ? " and u.status = 0" : " and u.status = 1";
        }
        if ($is_anchor > 0) {
            $sql .= $is_anchor == 1 ? " and u.is_anchor = 1" : " and u.is_anchor = 0";
        }
        if ($uid) {
            $sql .= " and w.uid={$uid}";
        }
        if ($time) {
            $time[0] = $time[0] / 1000;
            $time[1] = $time[1] / 1000;
            $sql .= " and w.created_at between {$time[0]} and {$time[1]}";
        }

        if ($phone) {
            $sql .= " and u.mobile like '{$phone}%'";
        }

        if ($nickname) {
            $sql .= " and w.username like '{$nickname}%'";
        }
        if ($order_id) {
            $sql .= " and w.order_id = '{$order_id}'";
        }


        $all_info = $this->db->query($sql)->result_array();

        $total_money = 0;
        foreach ($all_info as $key => $item) {
            $total_money += floatval($item['amount']);
        }

        $sql .= " order by w.created_at desc limit {$size} offset {$offset}";

        $info = $this->db->query($sql)->result_array();

        $page_money = 0;
        $time = time();
        foreach ($info as $key => &$item) {

            $page_money += floatval($item['amount']) / $this->trans_rate;

            $item['created_at'] = date("Y-m-d H:i:s", $item['created_at']);

            $item['order_id'] = $item['order_id'] ? $item['order_id'] : "/";

            $item['is_anchor'] = $item['is_anchor'] ? '是' : '否';

            $item['amount_txt'] = floatval($item['amount']) / $this->trans_rate;


            switch ($item['user_status']) {
                case 0:
                    $item['user_status'] = "启用";
                    break;
                case 1:
                    $item['user_status'] = "禁用";
                    break;
            }
        }

        $info = array_values($info);

        if ($type == 2) {
            foreach ($info as $key => &$item) {
                unset($item['amount_txt']);
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

        $return_data = [
            'total' => count($all_info),
            'info' => $info,
            'total_money' => $total_money,
            'page_money' => $page_money
        ];
        ajax_return(SUCCESS, '获取成功', $return_data);

    }


    /**
     * 获取银行类型信息
     */
    public function bank_type()
    {
        $appid = DATABASESUFFIX;

        $info = $this->bank_info($appid);

        ajax_return(SUCCESS, '获取成功', $info);
    }


    /**
     * 处理审核状态
     */
    public function deal_info()
    {
        $appid = DATABASESUFFIX;

        $status = intval($_POST['status'] ?? 0);
        $id = intval($_POST['id'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        $usercode = trim($_POST['usercode'] ?? '');

        if (!$status || !$id) {
            ajax_return(ERROR, '参数错误', []);
        }

        $sql = "select id from admin_user_{$appid} where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();

        $op_uid = 0;
        if ($userinfo) {
            $op_uid = $userinfo['id'];
        }

        $set_value = "status={$status}";

        $time = time();

        $this->db->trans_begin();
        try {
            $rate_sql = "select id,rate_num from withdrawal_record_{$appid} where id={$id}";

            $rate_info = $this->db->query($rate_sql)->row_array();

            if (!$rate_info) {
                $this->db->trans_rollback();
                ajax_return(ERROR, '数据错误', []);
            }

            if (!$rate_info['rate_num']) {
                $this->db->trans_rollback();
                ajax_return(ERROR, '提现比例错误', []);
            }
            if ($rate_info['rate_num'] <= 0) {
                $this->db->trans_rollback();
                ajax_return(ERROR, '提现比例错误', []);
            }

            $diff_amount = 0;
            if ($status == 3) {

                //审核不过，退还流水
//                $diff_amount = round($amount / $rate_info['rate_num'], 2) / $this->trans_rate;
                $diff_amount = round($amount, 2) / $this->trans_rate;
//                $check_buy_sql = "update user_game_coin_record_{$appid} set total_amount=(total_amount + {$diff_amount}) where uid={$uid}";
//                $this->db->query($check_buy_sql);
            }

            if ($reason) {
                $set_value .= ",reason='{$reason}'";
            }

            if ($op_uid) {
                $set_value .= ",op_uid='{$op_uid}'";
            }
            $set_value .= ",up_time={$time}";

            $sql = "update withdrawal_record_{$appid} set {$set_value} where id={$id}";

            $info = $this->db->query($sql)->affected_rows();

            if ($info) {

                if ($status == 3) {

                    // 更新redis里的baby_balance 扣除
                    $pipe = $this->redis->pipeline();
                    $pipe->hIncrByFloat("user:$uid", STABLE_CURRENCY_NAME . '_balance', $diff_amount);
                    $pipe->sAdd("user:balance:update", $uid);
                    $pipe->exec();
                }

                $this->db->trans_commit();
                ajax_return(SUCCESS, '处理成功', []);
            } else {
                $this->db->trans_rollback();
                ajax_return(ERROR, '处理失败', []);
            }
        } catch (Exception $exception) {
            $this->db->trans_rollback();
            ajax_return(ERROR, '处理失败', []);
        }


    }


    /**
     * 提现下发
     */
    public function remit_money()
    {
        $appid = DATABASESUFFIX;

        $page = intval($_POST['page'] ?? 1);
        $size = intval($_POST['size'] ?? 10);

        $type = intval($_POST['type'] ?? 0);

        $status = intval($_POST['status'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);
        $time = $_POST['time'] ?? [];
        $op_uid = intval($_POST['op_uid'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $order_id = trim($_POST['order_id'] ?? '');

        $usercode = trim($_POST['usercode'] ?? '');

        $offset = ($page - 1) * $size;


        $sql = "select w.id,w.uid,w.username,w.bank_id,w.bank_name,w.bank_num,w.amount,w.service_charge,w.order_id,w.created_at,w.op_uid,w.status,w.reason,u.mobile from withdrawal_record_{$appid} as w left join user_{$appid}_0 as u on u.id=w.uid where w.is_deleted=0 and w.status in (2,3) and w.pay_status=0 ";

        if ($status) {
            $sql .= " and w.status = {$status}";
        }

        if ($uid) {
            $sql .= " and w.uid={$uid}";
        }
        if ($time) {
            $time[0] = $time[0] / 1000;
            $time[1] = $time[1] / 1000;
            $sql .= " and w.created_at between {$time[0]} and {$time[1]}";
        }

        if ($phone) {
            $sql .= " and u.mobile like '{$phone}%'";
        }

        if ($op_uid) {
            $sql .= " and w.op_uid = {$op_uid}";
        }
        if ($order_id) {
            $sql .= " and w.order_id = '{$order_id}'";
        }


        $all_info = $this->db->query($sql)->result_array();

        $total_money = 0;
        foreach ($all_info as $key => $item) {
            $total_money += floatval($item['amount']);
        }

        $sql .= " order by w.created_at desc limit {$size} offset {$offset}";

        $info = $this->db->query($sql)->result_array();

        $page_money = 0;
        $time = time();
        foreach ($info as $key => &$item) {

            $page_money += floatval($item['amount']);

            $item['created_at'] = date("Y-m-d H:i:s", $item['created_at']);

            $item['order_id'] = $item['order_id'] ? $item['order_id'] : "/";

            $item['bank_type'] = '银行卡';

            $item['amount_txt'] = round(floatval($item['amount']) - floatval($item['service_charge']), 2);

            $bank_info = $this->bank_info($appid);

            foreach ($bank_info as $b_key => $b_item) {
                if ($b_item['id'] == $item['bank_id']) {
                    $item['bank_info'] = $b_item['name'] . '/' . $item['bank_name'] . '/' . $item['bank_num'];
                    break;
                }
            }

//            $item['amount_txt'] = round($item['amount'] - $item['service_charge'], 2);

            $sql = "select id,username from admin_user_{$appid} where id = {$item['op_uid']}";
            $userinfo = $this->db->query($sql)->row_array();

            $op_name = '/';
            if ($userinfo) {
                $op_name = $userinfo['username'];
            }

            $item['op_name'] = $op_name;

            unset($item['bank_name'], $item['bank_id'], $item['bank_num'], $item['op_uid']);

            switch ($item['status']) {
                case 2:
                    $item['status_txt'] = "通过";
                    break;
                case 3:
                    $item['status_txt'] = "拒绝";
                    break;
            }


        }

        $info = array_values($info);

        if ($type == 2) {
            foreach ($info as &$item) {
                unset($item['status'], $item['amount_txt']);
            }
            $info = array_values($info);
            $header = [
                'id编号', '用户ID', '用户名字', '提现金额(包含服务费)', '服务费', '订单号', '提现时间',
                '审核备注', '手机号',
                '提款渠道', '渠道账号', '操作人', '状态'];
            $path = export('提现审核', $header, $info);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        $return_data = [
            'total' => count($all_info),
            'info' => $info,
            'total_money' => $total_money,
            'page_money' => $page_money
        ];
        ajax_return(SUCCESS, '获取成功', $return_data);

    }


    /**
     * 拒绝下发
     */
    public function refuse_pay()
    {
        $appid = DATABASESUFFIX;

        $status = 2; //拒绝
        $id = intval($_POST['id'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        $usercode = trim($_POST['usercode'] ?? '');

        if (!$status || !$id) {
            ajax_return(ERROR, '参数错误', []);
        }

        $sql = "select id from admin_user_{$appid} where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();

        $op_uid = 0;
        if ($userinfo) {
            $op_uid = $userinfo['id'];
        }

        $set_value = "pay_status={$status}";

        $this->db->trans_begin();
        try {

            $time = time();

            $rate_sql = "select id,rate_num from withdrawal_record_{$appid} where id={$id}";

            $rate_info = $this->db->query($rate_sql)->row_array();

            if (!$rate_info) {
                $this->db->trans_rollback();
                ajax_return(ERROR, '数据错误', []);
            }

            if (!$rate_info['rate_num']) {
                $this->db->trans_rollback();
                ajax_return(ERROR, '提现比例错误', []);
            }

            if ($rate_info['rate_num'] <= 0) {
                $this->db->trans_rollback();
                ajax_return(ERROR, '提现比例错误', []);
            }

//            $diff_amount = round($amount / $rate_info['rate_num'], 2) / $this->trans_rate;
            $diff_amount = round($amount, 2) / $this->trans_rate;

            if ($reason) {
                $set_value .= ",pay_content='{$reason}'";
            }

            if ($op_uid) {
                $set_value .= ",op_uid2='{$op_uid}'";
            }

            $set_value .= ",up_time2={$time}";

            $sql = "update withdrawal_record_{$appid} set {$set_value} where id={$id}";

            $info = $this->db->query($sql)->affected_rows();

            if ($info) {

                // 更新redis里的baby_balance 扣除
                $pipe = $this->redis->pipeline();
                $pipe->hIncrByFloat("user:$uid", STABLE_CURRENCY_NAME . '_balance', $diff_amount);
                $pipe->sAdd("user:balance:update", $uid);
                $pipe->exec();

                $this->db->trans_commit();
                ajax_return(SUCCESS, '处理成功', []);
            } else {
                $this->db->trans_rollback();
                ajax_return(ERROR, '处理失败', []);
            }
        } catch (Exception $exception) {
            $this->db->trans_rollback();
            ajax_return(ERROR, '处理失败', []);
        }
    }

    /**
     * 处理新增或者修改类型的数据
     * 打款处理，下发处理
     */
    public function pay_deal()
    {
        $status = 1;
        $id = intval($_POST['id'] ?? 0);
        $uid = intval($_POST['uid'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $pay_content = trim($_POST['pay_content'] ?? '');

        $appid = DATABASESUFFIX;
        $usercode = trim($_POST['usercode'] ?? '');

        $time = time();
        $img_arr = [];

        if (!$status || !$id || !$pay_content) {
            ajax_return(ERROR, '参数错误', []);
        }

        $file = null;
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
        }

        $rate_sql = "select id,rate_num from withdrawal_record_{$appid} where id={$id}";

        $rate_info = $this->db->query($rate_sql)->row_array();

        if (!$rate_info) {
            ajax_return(ERROR, '数据错误', []);
        }

        if (!$rate_info['rate_num']) {
            ajax_return(ERROR, '提现比例错误', []);
        }
        if ($rate_info['rate_num'] <= 0) {
            ajax_return(ERROR, '提现比例错误', []);
        }

        if ($file) {
            // 取扩展名
            $ext_arr = explode(".", $file['name']);
            $ext = array_pop($ext_arr);
            $path = "/withdrawal/pay_trans/pay" . date("/Ymd_") . rand_code(16, 'both');
            $filename = $path . "." . $ext;

            $sql_save_path = '/upload' . $filename;
            $file_name = '/upload' . $filename;


            $_file = $_FILES['file'];
            //解析文件路径
            if (!$file_name) {
                $file_info = pathinfo($_file['name']);//解析文件路径
                $ext = '.' . strtolower($file_info['extension']);//文件后缀
                $file_name = date('His') . $ext;//保存文件名
                $file_path = $this->path . '' . '/' . date('Ymd') . '/';//存储路径
            } else {
                $file_info = pathinfo($file_name);//解析文件路径
                $ext = '.' . strtolower($file_info['extension']);//文件后缀
                $file_name = strtolower($file_info['basename']);//保存文件名
                $file_path = strtolower($file_info['dirname']) . '/';//存储路径
            }

            //判断目录是否存在，如果不存在则自动创建
            if (!file_exists(BASEPATH . $file_path)) {
                if (!@mkdir(BASEPATH . $file_path, 0775, true)) {
                    $result['msg'] = get_tips(15009) . BASEPATH . $file_path;
                    ajax_return(ERROR, get_tips(7015) . ':' . ($result['msg'] ?? ""));
                }
            }

            //执行上传
            $dist_path = BASEPATH . $file_path . iconv('UTF-8', 'GB2312//IGNORE', $file_name);
            if (!@copy($_file['tmp_name'], $dist_path)) {
                if (!@move_uploaded_file($_file['tmp_name'], BASEPATH . $file_path . $file_name)) {
                    $result['msg'] = get_tips(15010);
                    ajax_return(ERROR, get_tips(7015) . ':' . ($result['msg'] ?? ""));
                }
            }

            array_push($img_arr, $sql_save_path);
        }

        $sql = "select id from admin_user_{$appid} where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();

        $op_uid = 0;
        if ($userinfo) {
            $op_uid = $userinfo['id'];
        }

        $rate_info['rate_num'] = $this->redis->get('payment:baby_price') ?? 1;
        $diff_amount = round($amount / $rate_info['rate_num'], 2);


        $this->db->trans_begin();

        try {
            $table = 'withdrawal_record_' . DATABASESUFFIX;

            if ($file){
                $img_arr_str = implode('|', $img_arr);
                $info_sql = "update {$table} set pay_status={$status},op_uid2={$op_uid},up_time2={$time},pay_content='{$pay_content}',pay_imgs='{$img_arr_str}' where id={$id}";
            }else{
                $info_sql = "update {$table} set pay_status={$status},op_uid2={$op_uid},up_time2={$time},pay_content='{$pay_content}' where id={$id}";
            }

            $affected_rows = $this->db->query($info_sql)->affected_rows();

            if ($affected_rows) {

                $redis_arr = $this->redis->hMGet("user:$uid", ['nickname', 'is_anchor']);

                //不是主播的情况下
                if (!$redis_arr['is_anchor']) {
                    $balance = $this->redis->hget("user:$uid", STABLE_CURRENCY_NAME . '_balance');
                    // 查询余额
                    if ($balance <= 0) {
                        $update_filed = "code_amount=0,recharge_amount=0";
                    } else {
                        $check_sql = "select id,code_amount,recharge_amount from count_code_recharge_amount_{$appid} where uid={$uid}";

                        $check_info = $this->db->query($check_sql)->row_array();

                        $update_filed = "";
                        if ($check_info['code_amount'] <= $diff_amount) {
                            $update_filed .= "code_amount=0,";
                        } else {
                            $update_filed .= "code_amount=code_amount-{$diff_amount},";
                        }

                        if ($check_info['recharge_amount'] <= $diff_amount) {
                            $update_filed .= "recharge_amount=0,";
                        } else {
                            $update_filed .= "recharge_amount=recharge_amount-{$diff_amount},";
                        }

                        $update_filed .= "uid={$uid}";
                    }

                    $sql = "update count_code_recharge_amount_{$appid} set {$update_filed} where uid={$uid}";

                    $this->db->query($sql);
                }


                $this->db->trans_commit();

                ajax_return(SUCCESS, '操作成功', []);
            } else {
                $this->db->trans_rollback();
                ajax_return(ERROR, '操作失败', []);
            }
        } catch (Exception $exception) {
            $this->db->trans_rollback();
            ajax_return(ERROR, '操作失败', []);
        }


    }


    /**
     * 下发记录
     */
    public function remit_money_record()
    {
        $appid = DATABASESUFFIX;

        $page = intval($_POST['page'] ?? 1);
        $size = intval($_POST['size'] ?? 10);

        $type = intval($_POST['type'] ?? 0);

        $pay_status = intval($_POST['pay_status'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        $uid = intval($_POST['uid'] ?? 0);

        $time = $_POST['time'] ?? [];

        $op_uid = intval($_POST['op_uid'] ?? '');
        $op_uid2 = intval($_POST['op_uid2'] ?? '');

        $phone = trim($_POST['phone'] ?? '');
        $order_id = trim($_POST['order_id'] ?? '');

        $usercode = trim($_POST['usercode'] ?? '');

        $offset = ($page - 1) * $size;


        $sql = "select w.id,w.uid,w.username,w.amount,w.service_charge,w.order_id,w.created_at,w.up_time,w.up_time2,w.op_uid,w.op_uid2,w.status,w.pay_status,w.reason,w.pay_content,w.pay_imgs,u.mobile,u.is_anchor from withdrawal_record_{$appid} as w left join user_{$appid}_0 as u on u.id=w.uid where w.is_deleted=0 and w.status in (2,3) and w.pay_status in (1,2)";

        if ($status) {
            $sql .= " and w.status = {$status}";
        }
        if ($pay_status) {
            $sql .= " and w.pay_status = {$pay_status}";
        }

        if ($uid) {
            $sql .= " and w.uid={$uid}";
        }
        if ($time) {
            $time[0] = $time[0] / 1000;
            $time[1] = $time[1] / 1000;
            $sql .= " and w.created_at between {$time[0]} and {$time[1]}";
        }

        if ($phone) {
            $sql .= " and u.mobile like '{$phone}%'";
        }

        if ($op_uid) {
            $sql .= " and w.op_uid = {$op_uid}";
        }
        if ($op_uid2) {
            $sql .= " and w.op_uid2 = {$op_uid2}";
        }
        if ($order_id) {
            $sql .= " and w.order_id = '{$order_id}'";
        }


        $all_info = $this->db->query($sql)->result_array();

        $total_money = 0;
        foreach ($all_info as $key => $item) {
            $total_money += floatval($item['amount']);
        }

        $sql .= " order by w.created_at desc limit {$size} offset {$offset}";

        $info = $this->db->query($sql)->result_array();

        $page_money = 0;
        $time = time();
        foreach ($info as $key => &$item) {

            $page_money += floatval($item['amount']);

            $item['amount_txt'] = round(floatval($item['amount']) - floatval($item['service_charge']), 2);

            $item['created_at'] = date("Y-m-d H:i:s", $item['created_at']);
            $item['up_time'] = date("Y-m-d H:i:s", $item['up_time']);
            $item['up_time2'] = date("Y-m-d H:i:s", $item['up_time2']);

            $item['order_id'] = $item['order_id'] ? $item['order_id'] : "/";

            $item['is_anchor'] = $item['is_anchor'] ? '是' : '否';
            $item['mobile'] = $item['mobile'] ? $item['mobile'] : '/';

            $item['bank_type'] = '银行卡';


            $sql = "select id,username from admin_user_{$appid} where id = {$item['op_uid']}";
            $userinfo = $this->db->query($sql)->row_array();

            $op_name = '/';
            if ($userinfo) {
                $op_name = $userinfo['username'];
            }

            $item['op_uid'] = $op_name;

            if ($item['op_uid2'] == $item['op_uid']) {
                $op_name2 = $op_name;
            } else {
                $sql2 = "select id,username from admin_user_{$appid} where id = {$item['op_uid2']}";
                $userinfo2 = $this->db->query($sql2)->row_array();

                $op_name2 = '/';
                if ($userinfo2) {
                    $op_name2 = $userinfo2['username'];
                }
            }


            $item['op_uid2'] = $op_name2;


            switch ($item['status']) {
                case 2:
                    $item['status'] = "通过";
                    break;
                case 3:
                    $item['status'] = "拒绝";
                    break;
            }

            switch ($item['pay_status']) {
                case 1:
                    $item['pay_status'] = "手动打款";
                    break;
                case 2:
                    $item['pay_status'] = "拒绝下发";
                    break;
            }

            $pay_img_arr = [];
            if ($item['pay_imgs']) {
                $pay_img_arr = explode('|', $item['pay_imgs']);
            }
            foreach ($pay_img_arr as $kk => $value) {
                $pay_img_arr[$kk] = 'http://' . $_SERVER['HTTP_HOST'] . $value;
            }
            $item['pay_imgs'] = $pay_img_arr;


        }

        $info = array_values($info);

        if ($type == 2) {
            foreach ($info as &$item) {
                $item['pay_imgs'] = $item['pay_imgs'] ? $item['pay_imgs'][0] : '/';
                unset($item['amount_txt']);
            }

            $info = array_values($info);

            $header = [
                'id编号', '用户ID', '用户名字', '提现金额(包含服务费)', '服务费', '订单号', '提现时间',
                '审核时间', '下发操作处理时间', '审核人', '下发人',
                '审核状态', '下发状态', '审核备注', '支付备注', '凭证地址',
                '手机号', '主播', '提款渠道'
            ];
            $path = export('提现下发记录', $header, $info);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        $return_data = [
            'total' => count($all_info),
            'info' => $info,
            'total_money' => $total_money,
            'page_money' => $page_money
        ];
        ajax_return(SUCCESS, '获取成功', $return_data);
    }


    /**
     * 删除审核记录
     */
    public function delete_info()
    {
        $appid = DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);
        $usercode = trim($_POST['usercode'] ?? '');

        if (!$id) {
            ajax_return(ERROR, '参数错误', []);
        }

        $time = time();

        $sql = "select id from admin_user_{$appid} where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();

        $op_uid = 0;
        if ($userinfo) {
            $op_uid = $userinfo['id'];
        }

        $table = 'withdrawal_record_' . DATABASESUFFIX;
        $info_sql = "update {$table} set is_deleted=1,op_uid={$op_uid},up_time={$time} where id={$id}";

        $affected_rows = $this->db->query($info_sql)->affected_rows();

        if ($affected_rows) {

            ajax_return(SUCCESS, '删除成功', []);
        } else {
            ajax_return(ERROR, '删除失败', []);
        }
    }


    /**
     * 获取银行信息
     * @param $appid
     * @return array
     */
    private function bank_info($appid)
    {
        $sql = "select id,name from bank_type_{$appid}";
        $info = $this->db->query($sql)->result_array();

        $info = $info ? $info : [];

        return $info;

    }

}