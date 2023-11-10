<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 用户模型
 */
class User_model
{
    /**
     * 获取用户信息
     * @param int $uid
     * @param array $fields
     * @return array|string
     */
    public function get_user($uid, $fields = [])
    {
        $str_keys = ['nickname','symbol','invite_code', 'im_accid'];
        $int_keys = ['vip_expire', 'svip_expire','uid','age','gender','blog_num','likes_num','follower_num','following_num','gift_num','country','is_anchor','video_on','video_fee','pm_on','pm_fee','live_id','heartbeat','rating_num','sociaty_id'];
        $float_keys = ['income', 'eurc_balance','msq_balance', 'rating_sum'];
        /**
         * @var Redis $redis
         */
        global $redis;
        if (is_string($fields)) {
            $fields = str_replace(" ", "", $fields);
            $fields = explode(",", $fields);
        }
        $cache_key = sprintf(RedisKey::USER, $uid);
        if (empty($fields)) {
            $user_data = $redis->hGetAll($cache_key);
        } elseif (count($fields) == 1) {
            $key = $fields[0];
            $val = $redis->hGet($cache_key, $key);
            if (in_array($key, $str_keys)) {
                return (string)$val;
            } elseif(in_array($key, $int_keys)) {
                return (int)$val;
            } else if (in_array($key, $float_keys)) {
                return (float)$val;
            }
            return $val;
        } else {
            $user_data = $redis->hMGet($cache_key, $fields);
        }

        //查了不存在的数据，会返回false,例blog_num 在没有发布过blog时，没有这个值
        foreach ($user_data as $key => $val) {
            if (in_array($key, $str_keys)) {
                $user_data[$key] = (string)$val;
            } elseif(in_array($key, $int_keys)) {
                $user_data[$key] = (int)$val;
            } else if (in_array($key, $float_keys)) {
                $user_data[$key] = (float)$val;
            }
        }
        $user_data['vip'] = 0;
        $user_data['svip'] = 0;
        if (isset($user_data['vip_expire'])) {
            $user_data['vip'] = intval($user_data['vip_expire'] > time());
        }
        if (isset($user_data['svip_expire'])) {
            $user_data['svip'] = intval($user_data['svip_expire'] > time());
        }

        return $user_data;
    }

    /**
     * 附近的人
     * @param   int     $uid
     * @param   int     $dist  距离,km
     * @param   int     $count   返回数量
     * @return  array   key为用户uid，value为距离（m）
     */
    public function nearby($uid, $dist = 20, $count = 500)
    {
        // 读取缓存
        $mc_key = "nearby:$uid";
        $res1 = $this->mc->get($mc_key);
        if (false === $res1) {
            // 读取用户信息
            $gender = $this->redis->hget("user:$uid", "gender");
            if (1 == $gender) {
                $op_gender = 2;
            } elseif (2 == $gender) {
                $op_gender = 1;
            } else {
                $op_gender = 0;
            }

            $res1 = [];
            if ($op_gender > 0) {
                // 先读用户经纬度
                $key = "user:geo:" . date('Ymd') . ":" . $gender;
                $res = $this->redis->geopos($key, $uid);

                // 查找距离最近的异性用户
                if ($res[0]) {
                    $key = "user:geo:" . date('Ymd') . ":" . $op_gender;
                    $res = $this->redis->georadius($key, $res[0][0], $res[0][1], $dist, 'km', ['WITHDIST', 'asc', 'count' => $count]);
                    foreach ($res as $val) {
                        $res1[$val[0]] = intval($val[1] * 1000);
                    }
                }

                // 数据不足用在线用户补足
                $count -= count($res1);
                if ($count > 0) {
                    $res = $this->redis->zrevrange("user:online:$op_gender", 0, $count);
                    foreach ($res as $val) {
                        $res1[$val] = 10000;
                    }
                }
            }
            $this->mc->set($mc_key, $res1, 60); // 缓存1分钟，正式服务器缓存1天
        }
        return $res1;
    }

}
