<?php

/**
 * baby金额扣钱、派奖（自动、手动）记录
 * Class Babytrans
 */

class Babytrans extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 获取操作人信息
     */
    public function get_opuid()
    {
        $appid = DATABASESUFFIX;
        $user_sql = "select id,username from admin_user_{$appid} where status=1";

        $user_info = $this->db->query($user_sql)->result_array();

        $user_info = $user_info ? $user_info : [];
        ajax_return(SUCCESS, '获取成功', $user_info);
    }

    /**
     * 获取记录数据
     */
    public function index()
    {

        $appid = DATABASESUFFIX;

        $type = intval($_POST['type'] ?? 1);//2导出数据

        $page = intval($_POST['page'] ?? 1);
        $size = intval($_POST['size'] ?? 10);
        $op_uid = intval($_POST['search_op_uid'] ?? 0);
        $search_trans_type = intval($_POST['search_trans_type'] ?? 0);
        $is_vip = intval($_POST['search_vip'] ?? 0);
        $is_anchor = intval($_POST['search_anchor'] ?? 0);
        $phone = trim($_POST['search_mobile'] ?? '');
        $uid = intval($_POST['search_uid'] ?? 10);
        $time = $_POST['search_time'] ?? [];

        $offset = ($page - 1) * $size;

        if ($time) {
            $time[0] = $time[0] / 1000;
            $time[1] = $time[1] / 1000;
        }


        $date = date('Y-m-d H:i:s');
        $sql = "";

        $total_info = [];

        $sql .= "select opamr.*, u.mobile from other_pay_add_money_record_{$appid} as opamr left join user_{$appid}_0 as u on u.id=opamr.uid where opamr.is_deleted=0";

        if ($is_vip > 0) {
            $sql .= $is_vip == 1 ? " and (u.vip_expire > '{$date}' or u.svip_expire > '{$date}')" : " and (u.vip_expire < '{$date}' or u.svip_expire < '{$date}')";
        }

        if ($uid) {
            $sql .= " and opamr.uid={$uid}";
        }

        if ($op_uid) {
            $sql .= " and opamr.op_uid={$op_uid}";
        }

        if ($phone) {
            $sql .= " and u.mobile={$phone}";
        }

        if ($search_trans_type) {
            $sql .= " and opamr.type={$search_trans_type}";
        }

        if ($is_anchor > 0) {
            if ($is_anchor == 1)
                $sql .= " and u.is_anchor=1";
            else
                $sql .= " and u.is_anchor=0";
        }

        if ($time) {
            $sql .= " and opamr.uptime between {$time[0]} and {$time[1]}";
        }
        if ($type !== 2) {
            $total_info = $this->db->query($sql)->result_array();
        }


        $sql .= " order by uptime desc";

        if ($type !== 2) {
            $sql .= " limit {$size} offset {$offset}";
        }

        $info = $this->db->query($sql)->result_array();

        foreach ($info as $key => &$item) {

            $redis_arr = $this->redis->hMGet("user:{$item['uid']}", ['nickname', 'vip_expire', 'svip_expire', 'is_anchor']);
            $item['nickname'] = $redis_arr['nickname'] ? $redis_arr['nickname'] : '/';

            $item['mobile'] = $item['mobile'] ? $item['mobile'] : '/';


            if ($redis_arr['vip_expire'] > date('Y-m-d h:i:s') || $redis_arr['svip_expire'] > date('Y-m-d h:i:s')) {
                $item['is_vip'] = "是";
            } else {
                $item['is_vip'] = "否";
            }

            if ($redis_arr['is_anchor']) {
                $item['is_anchor'] = '是';
            } else {
                $item['is_anchor'] = '否';
            }

            $user_sql = "select username from admin_user_{$appid} where id = {$item['op_uid']}";
            $userinfo = $this->db->query($user_sql)->row_array();

            $item['op_nickname'] = '/';
            if ($userinfo) {
                $item['op_nickname'] = $userinfo['username'];
            }

            $item['op_time'] = date("Y-m-d H:i:s", $item['uptime']);
        }

        $info = array_values($info);

        if ($type !== 2) {
            $return_data = [
                'total' => count($total_info),
                'info' => $info
            ];
            ajax_return(SUCCESS, '获取成功', $return_data);

        } else {

            $new_arr = [];
            foreach ($info as $key => $item) {
                $new_arr[$key]['id'] = $item['id'];
                $new_arr[$key]['uid'] = $item['uid'];
                $new_arr[$key]['nickname'] = $item['nickname'];
                $new_arr[$key]['mobile'] = $item['mobile'];
                $new_arr[$key]['is_vip'] = $item['is_vip'];
                $new_arr[$key]['is_anchor'] = $item['is_anchor'];
                $new_arr[$key]['amount'] = intval($item['amount']) > 0 ? '+' . $item['amount'] : $item['amount'];
                $new_arr[$key]['memo'] = $item['memo'];
                $new_arr[$key]['op_nickname'] = $item['op_nickname'];
                $new_arr[$key]['op_time'] = $item['op_time'];
            }
            $header = [
                '序号id',
                '用户ID',
                '昵称',
                '手机号',
                '是否是vip',
                '是否是主播',
                '加扣钱',
                '操作备注',
                '操作人',
                '操作时间',
            ];
            $path = export('手动加扣款记录', $header, $new_arr);
            ajax_return(SUCCESS, '导出成功', $path);
            exit;

        }

    }


    /**
     * 删除
     */
    public function delete_info()
    {
        $appid = DATABASESUFFIX;

        $choose_type = intval($_POST['type'] ?? 0);
        $id = intval($_POST['id'] ?? 0);

        $sql = '';

        switch ($choose_type) {
            case 1:
                $sql .= "update live_game_coin_record_{$appid} set is_deleted=1 where id={$id} ";

                break;
            case 2:
                $sql .= "update other_pay_add_money_record_{$appid} set is_deleted=1 where id={$id} ";
                break;
            default:
                ajax_return(ERROR, '参数错误', []);
                break;
        }

        $info = $this->db->query($sql)->affected_rows();

        if ($info) {
            ajax_return(SUCCESS, '删除成功', []);
        } else {
            ajax_return(ERROR, '删除错误', []);
        }
    }

}