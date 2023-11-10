<?php

/**
 * 客户服务 客服.
 * User: Admin
 * Date: 2019/9/25
 * Time: 10:22
 */
class Cs extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 对接过的用户列表
     */
    public function users()
    {
        $page = intval($_POST['page'] ?? 1);
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE);
        $list = [];

        // 所有客服
        $customer_service_uids = $this->redis->sMembers("customer:service");
        if (empty($customer_service_uids)) {
            ajax_return(SUCCESS, "success", $list);
        }

        $i = 1;
        $this->init_db();
        $sql = "SELECT id, uid, username FROM admin_user_" . DATABASESUFFIX . " WHERE uid IN (" . join(",", $customer_service_uids) . ")";
        $customer_services = $this->db->query($sql)->result_array();
        foreach ($customer_services as $row) {
            $app_usercode = $this->redis->hGet(sprintf(RedisKey::USER, $row['uid']), 'usercode');
            $his_op_uids = $this->redis->zRevRange(sprintf(RedisKey::PM_HOME_LIST, $row['uid']), 0, -1);
            foreach ($his_op_uids as $op_uid) {
                $op_user_nickname = $this->redis->hGet(sprintf(RedisKey::USER, $op_uid), 'nickname');
                $list[] = [
                    'id' => $i++,
                    'app_usercode' => $app_usercode,
                    'customer_service_uid' => $row['uid'],
                    'customer_service_name' => $row['username'],
                    'nickname' => $op_user_nickname,
                    'op_uid' => $op_uid
                ];
            }
        }

        $total = count($list);
        $offset = ($page - 1) * $page_size;
        $list = array_slice($list, $offset, $page_size);

        ajax_return(SUCCESS, 'success', ['list' => $list, 'total' => $total]);
    }

    /**
     * 对话/聊天记录
     */
    public function chat_record()
    {
        $page = intval($_POST['page'] ?? 1);
        $page_size = ADMIN_PAGE_SIZE;
        $uid = intval($_POST['uid'] ?? 0);
        $op_uid = intval($_POST['op_uid'] ?? 0);
        if ($uid <= 0 || $op_uid <= 0) {
            ajax_return(ERROR, get_tips(3001));
        }

        $list = [];
        $redis_key = $uid > $op_uid ? "pm:msg:$op_uid:$uid" : "pm:msg:$uid:$op_uid";
        $msgs = $this->redis->lRange($redis_key, 0, -1);
        if (!empty($msgs)) {
            foreach ($msgs as $json) {
                $item = json_decode($json, true);
                $list[] = [];
            }
        }

        ajax_return(SUCCESS, "success", $list);
    }

    /**
     * 获取 appID
     */
    public function get_app_id()
    {
        $uid = $_POST['uid'] ?? 0;
        if (empty($uid)) {
            ajax_return(ERROR, get_tips(3002));
        }

        ajax_return(SUCCESS, "success", [
            //todo 从数据库取
            'app_id' => "49b54c8ee3a940ecb88476fb3d7959e7",
            'rtc_token' => (new Agora())->get_rtm_token($uid)
        ]);
    }

    /**
     * 代理发送私信消息
     */
    public function send_pm()
    {
        $_POST['pm'] = rtrim($_POST['pm'] ?? "");
        $res = $this->post(API_SERVER_URL . "/pm/send_pm", $_POST['app_usercode'] ?? '', $_POST, $_FILES['file'] ?? "");
        if ($res === false) {
            ajax_return(ERROR, get_tips(3003));
        }
        echo $res;
    }

    /**
     * 代理获取私信列表
     */
    public function pm_index()
    {   
        $res = $this->post(API_SERVER_URL . "/pm/index", $_POST['app_usercode'] ?? '', $_POST);
        if ($res === false) {
            ajax_return(ERROR, get_tips(3004));
        }
        echo $res;
    }

    /**
     * 代理获取私信详情
     */
    public function pm_detail()
    {
        $res = $this->post(API_SERVER_URL . "/pm/detail", $_POST['app_usercode'] ?? '', $_POST);
        if ($res === false) {
            ajax_return(ERROR, get_tips(3004));
        }
        $op_uid = $_POST['op_uid'];
        $uid = $this->redis->hGet("usercode:" . substr(md5($_POST['app_usercode']), 0, 3), $_POST['app_usercode']);
        $cache_key = $uid > $op_uid ? "pm:msg:$op_uid:$uid" : "pm:msg:$uid:$op_uid";
        $total = $this->redis->lLen($cache_key);
        $result = json_decode($res, true);
        $result['total'] = $total;

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 表情列表
     */
    public function emoji()
    {
        $res = $this->post(API_SERVER_URL . "/blog/emoji", $_POST['app_usercode'] ?? '');
        if ($res === false) {
            ajax_return(ERROR, get_tips(3004));
        }
        echo $res;
    }

    /**
     * 封装数据
     */
    private function post($url = '', $usercode = '', $arr = [], $file = null)
    {
        $config = $this->load_config('dbfield');
        //应用参数
        $appkey    = $config['h5_appkey']['appkey'];
        $appsecret = $config['h5_appkey']['appsecret'];

        //计算accesskey
        $accesskey = hash_hmac('sha256', $usercode, $appsecret);

        //数据编码
        ksort($arr);
        $input = json_encode($arr);

        //计算签名
        $signature = hash_hmac('sha256', $input, $accesskey);

        //数据封装
        $arr1 = compact('appkey', 'usercode', 'input', 'signature');
        $data['data'] = base64_encode(json_encode($arr1));
        if (!empty($file)) {
            $data['file'] = $file;
        }

        //模拟请求
        $res = http($url, $data);
        return $res;
    }
}
