<?php

/**
 * 公共模型
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Common_model
{

    /**
     * 获取即时汇率
     * @param $coin          string     虚拟币
     * @param $currency      string     法币币种
     * @param $country_id    int        国家id
     * @param $appid         int        应用id
     * @return               float      汇率
     */
    public function exchange_rate($coin = 'eurc', $currency = '', $country_id = 0, $appid = 0)
    {
        // 初始化变量
        $exchange_rate = 0;

        // 获取法币币种
        if (!$currency && $country_id) {
            $sql = "select currency from cat_country_$appid where id = $country_id";
            $res = $this->db->query($sql)->row_array();
            if ($res) {
                $currency = $res['currency'];
            }
        }

        // 查询最新汇率
        if (('eurc' == $coin || 'msq' == $coin) && $currency) {
            $sql = "select {$coin}_exchange_rate, uptime from cat_currency_$appid where currency = '$currency'";
            $res = $this->db->query($sql)->row_array();
            if ($res && (time() - strtotime($res['uptime']) < 86400)) {
                $exchange_rate = $res["{$coin}_exchange_rate"];
            }
            if (!$exchange_rate) {
                // 第三方接口获取即时汇率
                /*
                
                
                
                临时指定
                
                
                
                */
                $exchange_rate = 1.5;

                // 写入数据库
                if ($res) {
                    // 更新记录
                    $sql = "update cat_currency_$appid set {$coin}_exchange_rate = $exchange_rate, uptime = current_timestamp where currency = '$currency'";
                } else {
                    // 插入新记录
                    $sql = "insert into cat_currency_$appid (currency, {$coin}_exchange_rate) values ('$currency', $exchange_rate)";
                }
                $this->db->query($sql);
            }
        }
        return $exchange_rate;
    }

    /**
     * 导出excel
     * @param $filename string 导出文件名
     * @param $title array 表头
     * @param $data  array 需要导出的数据
     * @return      string 文件保存路径
     */
    public function export_excel($filename, $title, $data)
    {
        $writer = new XLSXWriter();

        //工作簿名称
        $sheet1 = 'sheet1';
        //对每列指定数据类型，对应单元格的数据类型
        foreach ($title as $key => $item) {
            $col_style[] =  'string';
        }
        $writer->writeSheetHeader($sheet1, $col_style, ['suppress_row' => true, 'widths' => [20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20]]);
        $writer->writeSheetHeader($sheet1, $col_style);

        //写入第二行的数据，顺便指定样式
        $writer->writeSheetRow(
            $sheet1,
            ['用户列表'],
            ['height' => 32, 'font-size' => 20, 'font-style' => 'bold', 'halign' => 'center', 'valign' => 'center']
        );

        /*设置标题头，指定样式*/
        $styles1 = array(
            'font' => '宋体', 'font-size' => 10, 'font-style' => 'bold', 'fill' => '#eee',
            'halign' => 'center', 'border' => 'left,right,top,bottom'
        );
        $writer->writeSheetRow($sheet1, $title, $styles1);

        $styles2 = ['height' => 16];
        foreach ($data as $row) {
            $writer->writeSheetRow($sheet1, $row, $styles2);
        }

        $writer->markMergedCell($sheet1, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = 12);
        //输出文档
        $file_name = $filename . date('YmdHis') . '.xlsx';
        $path = get_load_path($file_name);
        $writer->writeToFile($path['absolutely_path']);
        return $path['relative_path'];
    }

    /**
     * @param $log_text :string
     * 后台操作日志;
     * @author :Kobe
     */
    public function admin_write_log($log_text)
    {
        global $controller;
        global $method;
        $admin_id = $this->user['id'];
        $user_name = $this->user['username'];
        $uptime = date('Y-m-d H:i:s');
        $sql = "INSERT INTO admin_log_" . DATABASESUFFIX . " (controller, method, `log_text`,`admin_id`,`username`,`uptime`) VALUES ('$controller', '$method', '$log_text','$admin_id','$user_name','$uptime')";
        $this->db->query($sql);
    }

    /**
     * 移动推送消息
     * @param int $uid
     * @param string $msg_title
     * @param string $msg_body
     * @param array $data
     * @param string $click_action
     * @return bool
     */
    public function push_notification($uid = 0, $msg_title = '', $msg_body = '', $data = [], $click_action = '')
    {
        if (!$uid || !$msg_title || !$msg_body) {
            return false;
        }

        if ('all' == $uid) {
            // 群发系统消息
            $res = $this->redis->hvals('user:device_token');
            $res1 = array_chunk($res, 999);
            foreach ($res1 as $val) {
                // 消息内容
                $message = [
                    'registration_ids' => json_encode($val), // 多个设备令牌数组，最多1000个
                    'msg_title' => $msg_title, // 通知的标题
                    'msg_body' => $msg_body, // 通知的正文
                    'click_action' => $click_action, // 与用户点击通知相关的操作
                    'data' => json_encode($data), // 有效负载
                ];
                // 加入队列
                $this->redis->xadd("mq:msg:push", '*', $message);
            }
        } else {
            // 发送单条消息
            $device_token = $this->redis->hget("user:$uid", 'device_token');
            if (!$device_token) {
                return false;
            }
            // 消息内容
            $message = [
                'to' => $device_token, // 设备的注册令牌
                'msg_title' => $msg_title, // 通知的标题
                'msg_body' => $msg_body, // 通知的正文
                'click_action' => $click_action, // 与用户点击通知相关的操作
                'data' => json_encode($data), // 有效负载
            ];
            // 加入队列
            $this->redis->xadd("mq:msg:push", '*', $message);
        }
        return true;
    }

    /**
     * 系统给用户发送消息
     * @param $_uid
     * @param $msg
     */
    public function send_msg($_uid,$msg){
            $from_uid = -1;
            // 发消息到私信
            // 准备缓存的私信数据
            $msg_data = [
                'pm_id' => $this->redis->incr(RedisKey::PM_NEW_ID),
                'times' => time(),
                'from_uid' => $from_uid,
                'to_uid' => $_uid,
                'type' => 2,
                'msg_content' => $msg
            ];
            // 双方的聊天列表
            $this->redis->rPush(RedisKey::get_pm_msg_list_key($from_uid, $_uid), json_encode($msg_data, JSON_UNESCAPED_UNICODE));
            // 首页消息
            $this->redis->zAdd(sprintf(RedisKey::PM_HOME_LIST, $_uid), time() + 86400 * 365, $from_uid);
            // 对方的新消息数量+1
            $this->redis->hIncrBy(sprintf(RedisKey::PM_NEW, $_uid), $from_uid, 1);
    }
}
