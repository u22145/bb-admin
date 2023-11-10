<?php


/**
 * 公用方法
 * Class Util
 */
class Util extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 统计累计打码量（baby币）和累计充值量（baby币）
     * @param $appid :appId
     * @param $uid :用户id
     * @param $code_amount :需要加的打码消费baby币额
     * @param $recharge_amount :需要加的充值baby币额
     */
    public static function add_or_reduce_calc_total_baby_coin($appid = 1, $uid = 0, $code_amount = 0, $recharge_amount = 0)
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
                $insert_values = '';

                $insert_field = "(`code_amount`,`recharge_amount`,`uid`)";
                if ($code_amount && !$recharge_amount) {
                    $insert_values = "({$code_amount},0,{$uid})";
                }

                if (!$code_amount && $recharge_amount) {
                    $insert_values = "(0,{$recharge_amount},{$uid})";
                }

                if ($code_amount && $recharge_amount) {
                    $insert_values = "({$code_amount},{$recharge_amount},{$uid})";
                }

                $sql = "insert into count_code_recharge_amount_{$appid} {$insert_field} values {$insert_values}";
            }

            $GLOBALS['db']->query($sql);


        }
    }


    /**
     * 后台手动发送自定消息
     * @param $appid
     * @param $insert_id int :消息id
     * @param $recive_arr array :接收用户的信息arr
     * @param $send_info array :发送用户的信息arr
     * @param $post_str string :发送的内容
     * @return bool|string
     */
    public static function send_agora_message($appid, $insert_id, $recive_arr, $send_info, $post_str)
    {
        global $config;
        $agora_conf = $config['agora'];
        $agora_app_id = $agora_conf['app_id'];
        $appCertificate = $agora_conf['cert'];
        $base64Credentials = base64_encode("$agora_app_id:$appCertificate");
        $arr_header = "Authorization: Basic " . $base64Credentials;
        $post_url = "https://api.agora.io/dev/v2/project/" . $agora_app_id . "/rtm/users/" . $send_info['uid'] . "/peer_messages?wait_for_ack=false";

        $curr_time = time();
        $insert_field = "(`send_uid`,`recive_uid`,`live_p_id`,`up_time`)";
        $insert_values = "({$send_info['uid']},{$recive_arr['uid']},{$insert_id},{$curr_time})";
        $sql = "insert into live_send_sms_log_{$appid} {$insert_field} values {$insert_values}";

        $log_insert_id = $GLOBALS['db']->query($sql)->insert_id();

        $log_insert_id = $log_insert_id ? $log_insert_id : 0;

        // type：1打招呼，2普通文字，3图片，4语音，5短视频 6礼物，7视频一对一邀请，10直播开播消息推送
        $type = 2;
        $agora = new Agora();
        $token = $agora->get_rtm_token($send_info['uid']);

        $post_info = [
            'pm_id' => $insert_id,
            'log_id' => $log_insert_id,
            'messageHash' => '',
            'type' => $type,
            'msg_content' => [
                'text' => $post_str,
                'roomId' => $send_info['uid'],
                'anchorId' => $send_info['uid'],
                'anchorName' => $send_info['nickname']
            ],
            'send_time' => time(),
            'to' => [
                'uid' => $recive_arr['uid'],
                'nickname' => $recive_arr['nickname'],
                'avatar' => $recive_arr['avatar'],

            ],
            'from' => [
                'uid' => $send_info['uid'],
                'nickname' => $send_info['nickname'],
                'avatar' => $send_info['avatar'],

            ]
        ];

        $post_info = json_encode($post_info, JSON_UNESCAPED_UNICODE);

        // 获取私信id
        $pm_id = $GLOBALS['redis']->incr(RedisKey::PM_NEW_ID);
        self::send_pm($pm_id, $recive_arr['uid'], $send_info['uid'], $post_str, $type);


        $post_data = [
            "destination" => $recive_arr['uid'],
            "enable_offline_messaging" => false,
            "enable_historical_messaging" => false,
            "payload" => $post_info
        ];
        $post_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
        $post_header = [
            $arr_header,
            'x-agora-token:' . $token,
            'x-agora-uid:' . $send_info['uid'],
            'Content-Type: application/json;charset=utf-8'

        ];

        $ch = curl_init($post_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $post_header);

        $output = curl_exec($ch);

        curl_close($ch);
        return $output;
    }


    /**
     * 发送私信给某人
     * @param $pm_id int 私信ID
     * @param $to_uid int 接收人ID
     * @param $content string|array 发送的内容
     *                  array 发送的图片和视频等链接
     * @param int $type 在 PmConst 中定义
     * @param int $send_uid 指定发送的用户ID，默认为当前登录用户ID
     * @return bool
     */
    public static function send_pm($pm_id, $to_uid, $send_uid, $content, $type)
    {
        $from_uid = $send_uid;

        // 准备缓存的私信数据
        $msg_data = [
            'pm_id' => $pm_id,
            'times' => time(),
            'from_uid' => $from_uid,
            'to_uid' => $to_uid,
            'type' => $type,
            'msg_content' => $content
        ];
        // 双方的聊天列表
        $GLOBALS['redis']->rPush(RedisKey::get_pm_msg_list_key($from_uid, $to_uid), json_encode($msg_data, JSON_UNESCAPED_UNICODE));
        // 对方的首页消息
        $GLOBALS['redis']->zAdd(sprintf(RedisKey::PM_HOME_LIST, $to_uid), time(), $from_uid);
        // 自己的首页消息
        $GLOBALS['redis']->zAdd(sprintf(RedisKey::PM_HOME_LIST, $from_uid), time(), $to_uid);
        // 对方的新消息数量+1
        $GLOBALS['redis']->hIncrBy(sprintf(RedisKey::PM_NEW, $to_uid), $from_uid, 1);

        return true;
    }

}