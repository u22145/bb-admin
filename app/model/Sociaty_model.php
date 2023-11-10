<?php

/**
 * 公会管理
 * User: user
 * Date: 2019/8/15
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Sociaty_model
{
    /**
     * 主播加入退出公会日志记录
     * @param $data array
     */
    public function sociaty_anchor_log($data)
    {
        $time = date('Y-m-d H:i:s');
        $sql = "INSERT INTO sociaty_anchor_log_" . DATABASESUFFIX . " (uid,sociaty_id,`type`,uptime,admin_id) VALUES(" . $data['uid'] . "," . $data['sociaty_id'] . "," . $data['type'] . ",'$time'," . $data['admin_id'] . ")";
        $this->db->query($sql);

        // 更新用户表的share_rate和redis
        $table = 'user_' . DATABASESUFFIX . '_0';
        $share_rate = $data['share_rate'];
        $uid = $data['uid'];
        $sql = "UPDATE $table SET share_rate = {$share_rate} WHERE id = {$uid}";
        $this->db->query($sql);
        $this->redis->hset(sprintf(RedisKey::SOCIATY, $data['sociaty_id']), 'share_rate', $share_rate);
    }

    /**
     * 更新公会主播人数
     * @param $sociaty_id int
     * @param $num int
     */
    public function update_anchor_num($sociaty_id, $num)
    {
        $sql = "UPDATE sociaty_" . DATABASESUFFIX . " SET anchor_num=anchor_num+$num WHERE id=$sociaty_id";
        $this->db->query($sql);
        $this->redis->hIncrBy(sprintf(RedisKey::SOCIATY, $sociaty_id), 'anchor_num', $num);
    }

    /**
     * 更新用户信息
     * @param $sql_field string
     * @param $redis_field array
     * @param $uid int
     */
    public function update_user_info($arr, $uid)
    {
        $sql_field = '';
        foreach ($arr as $k => $v) {
            if (is_numeric($v)) {
                $sql_field .= "$k = $v, ";
            } else {
                $sql_field .= "$k = '$v', ";
            }
        }
        $sql_field = trim($sql_field, ', ');
        $sql = "UPDATE user_" . DATABASESUFFIX . "_0 SET $sql_field WHERE id=$uid";
        $this->db->query($sql);
        $this->redis->hmset(sprintf(RedisKey::USER, $uid), $arr);
    }
}
