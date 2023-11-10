<?php

/**
 * Class Chat 一对一直播
 */

class Chat extends Controller
{

    public function __construct()
    {

        $this->init_db();
        parent::__construct();
    }


    /**
     * 一对一直播列表
     */
    public function chat_list()
    {

        header("Access-Control-Allow-Origin: *");

        // 接受参数
        $page = intval($_POST['page']) ?? 1;
        $page_size = 201;

        $offset = ($page - 1) * $page_size;

        $appid = DATABASESUFFIX;

        $one_to_one_top_num = $this->redis->zCard('onetoone:settop');

        $top_info = [];
        $uid_str = '';

        if ($one_to_one_top_num && $one_to_one_top_num > $offset) {
            $uid_arr = $this->redis->zRevRange('onetoone:settop', 0, -1);
            if (floor((float)$one_to_one_top_num / $page_size) > $page) {
                $uid_arr = array_slice($uid_arr, $offset, $page_size);
            } else {
                if ($page == 1) {
                    if ($one_to_one_top_num < $page_size) {
                        $uid_arr = array_slice($uid_arr, $offset, $one_to_one_top_num);
                    } else {
                        $uid_arr = array_slice($uid_arr, $offset, $page_size);
                    }
                } else {

                    if (($page - 1) * $page_size > $one_to_one_top_num) {
                        if ($page * $page_size > $one_to_one_top_num) {
                            $uid_arr = array_slice($uid_arr, $offset, $one_to_one_top_num - $offset);
                        } else {
                            $uid_arr = array_slice($uid_arr, $offset, $page_size);
                        }
                    }
                }
            }

            //置顶数据
            $uid_str = implode(',', $uid_arr);

            $top_sql = "select id,uid,name,introduce,img_url,video_url,created_at,is_deleted from chat_create_log_{$appid} where is_deleted in (0,2) and uid in ({$uid_str})";
            $top_info = $this->db->query($top_sql)->result_array();

        }

        $top_info = $top_info && count($top_info) ? $top_info : [];

        $total_info = [];

        $return_data = [];
        $res = [];
        if ($top_info) {

            if ($uid_str) {
                $sql = "select id,uid,name,introduce,img_url,video_url,created_at,is_deleted from chat_create_log_{$appid} where is_deleted in (0,2) and uid not in ({$uid_str}) ORDER BY created_at DESC";

            } else {
                $sql = "select id,uid,name,introduce,img_url,video_url,created_at,is_deleted from chat_create_log_{$appid} where is_deleted in (0,2) ORDER BY created_at DESC";

            }
            $total_info = $this->db->query($sql)->result_array();

        } else {
            $sql = "select id,uid,name,introduce,img_url,video_url,created_at,is_deleted from chat_create_log_{$appid} where is_deleted in (0,2) ORDER BY created_at DESC limit {$page_size} offset {$offset}";
            $res = $this->db->query($sql)->result_array();
        }

        //总数
        $total_sql = "select id from chat_create_log_{$appid} where is_deleted in (0,2)";
        $total_num_info = $this->db->query($total_sql)->result_array();
        $total_num = $total_num_info && count($total_num_info) ? count($total_num_info) : 0;
        $return_data['total'] = $total_num;

        foreach ($top_info as $key => $item) {
            $top_info[$key]['is_top'] = true;
        }

        if ($one_to_one_top_num) {
            $top_offset_diff = $offset - $one_to_one_top_num;
            if ($top_offset_diff > 0) {
                $new_offset = $top_offset_diff - $page_size;

                if ($top_offset_diff <= $page_size) {
                    if ($top_offset_diff - $page_size < 0) {
                        $res = array_slice($total_info, $new_offset, $page_size - $top_offset_diff);
                    } else {
                        $res = array_slice($total_info, $new_offset, $page_size);
                    }
                } else {
                    $res = array_slice($total_info, $new_offset, $page_size);
                }
            } else {
                if ($page_size - abs($top_offset_diff) > 0) {
                    $res = array_slice($total_info, 0, $page_size - abs($top_offset_diff));
                }
            }
        }


        $res = $res && count($res) ? $res : [];

        foreach ($res as $key => $item) {
            $res[$key]['is_top'] = false;
        }

        $res = array_merge($top_info, $res);

        //数据处理
        foreach ($res as $key => $item) {

            //一对一信息记录
            $call_sql = "select anchor_uid,duration,amount from chat_call_log_{$appid} where anchor_uid={$item['uid']} and status=2";

            $call_info = $this->db->query($call_sql)->result_array();

            $call_info = $call_info && count($call_info) ? $call_info : [];
            $res[$key]['call_people_num'] = count($call_info) ? count($call_info) : 0;

            $total_times = 0;
            $video_money = 0;
            foreach ($call_info as $t => $times) {
                $total_times += intval($times['duration']);
                $video_money += floatval($times['amount']);
            }
            $res[$key]['call_times'] = number_format(floatval($total_times) / 60, 0);
            $res[$key]['video_money'] = number_format($video_money, 2);

            //礼物记录
            $gift_sql = "select fee from exp_gift_{$appid} where to_uid={$item['uid']} and type=1 and call_id!=0";

            $gift_info = $this->db->query($gift_sql)->result_array();
            $gift_info = $gift_info && count($gift_info) ? $gift_info : [];
            $gift_num = 0;
            foreach ($gift_info as $g_k => $gift) {
                $gift_num += floatval($gift['fee']);
            }
            $res[$key]['gift_money'] = number_format($gift_num, 2);


            $res[$key]['total_money'] = number_format(floatval($res[$key]['video_money']) + floatval($res[$key]['gift_money']), 2);

            $res[$key]['ts_server'] = TS_SERVER . '/';

            $redis_arr = $this->redis->hMGet("user:{$item['uid']}", ['nickname']);

            //检测用户是否在直播中以及1v1
            $item = $this->check_list_user_status($item, $item['uid']);

            if ($item['is_online'] == 2 || $item['is_online'] == 3) {
                //忙线中
                $res[$key]['status'] = 1;
            } else {
                //空闲中
                if ($item['is_online'] == 1) {
                    $res[$key]['status'] = 0;
                }
            }
            $res[$key]['status'] = 0;

            $res[$key]['is_online'] = $item['is_online'];
            // unset($item['is_online']);

            $res[$key]['nickname'] = $redis_arr['nickname'];

            // 检查是否已经自带了scheme，添加https或替换原来的scheme
            $urlScheme = parse_url($res[$key]['ts_server'], PHP_URL_SCHEME);
            if (NULL === $urlScheme) $svrAddress = 'https://' . $res[$key]['ts_server'];
            else {
                $url = parse_url($res[$key]['ts_server']);
                $url['scheme'] = 'https';
                $svrAddress = (isset($url['scheme']) ? "{$url['scheme']}:" : '') .
                    ((isset($url['host'])) ? '//' : '') .
                    (isset($url['host']) ? "{$url['host']}" : '') .
                    (isset($url['path']) ? "{$url['path']}" : '');
            }

            $filename = $this->getMilliSecond() . '.m3u8';
            $res[$key]['video_url'] = $this->saveContentToFile(explode('|', $item['video_url']),
                $filename,
                $svrAddress,
                'chat_video_headers/' . $item['uid'] . '/');


            $res[$key]['img_url'] = $item['img_url'] ? $res[$key]['ts_server'] . $item['img_url'] : '';

            $res[$key]['is_play'] = false;
            $res[$key]['created_at'] = date("Y-m-d H:i:s", $item['created_at']);
        }

        $return_data['info'] = $res;
        ajax_return(SUCCESS, '获取成功', $return_data);


    }


    /**
     * 禁播或者取消禁播
     */
    public function stop_start_chat()
    {
        $type = intval($_POST['type']) ?? 0;
        $id = intval($_POST['id']) ?? 0;

        if (!$type || !$id) {
            ajax_return(ERROR, '参数错误', []);
        }

        $appid = DATABASESUFFIX;

        $update_sql = '';
        switch ($type) {
            case 1:
                //禁播
                $update_sql = "update chat_create_log_{$appid} set is_deleted=2 where id={$id}";

                break;
            case 2:
                //取消禁播
                $update_sql = "update chat_create_log_{$appid} set is_deleted=0 where id={$id}";
                break;
            default:
                break;
        }

        $info = $this->db->query($update_sql)->affected_rows();

        $msg = '';
        switch ($type) {
            case 1:
                //禁播
                if ($info) {
                    $msg = "禁播成功";
                } else {
                    $msg = "禁播失败";
                }
                break;
            case 2:
                //取消禁播
                if ($info) {
                    $msg = "取消禁播成功";
                } else {
                    $msg = "取消禁播失败";
                }
                break;
            default:
                break;
        }

        if ($info) {
            ajax_return(SUCCESS, $msg, []);
        } else {
            ajax_return(ERROR, $msg, []);
        }

    }


    /**
     * 置顶 | 取消置顶
     */
    public function set_top()
    {
        $type = intval($_POST['type']) ?? 0;
        $uid = intval($_POST['uid']) ?? 0;
        if (!$type || !$uid) {
            ajax_return(ERROR, '参数错误', []);
        }
        $msg = '';
        $pipe = $this->redis->pipeline();
        $time = time();
        switch ($type) {
            case 1:
                //置顶
                $pipe->zAdd("onetoone:settop", $time, $uid);
                $msg = "置顶成功";
                break;
            case 2:
                //取消置顶
                $pipe->zRem("onetoone:settop", $uid);
                $msg = "取消置顶成功";
                break;
        }

        $pipe->exec();
        ajax_return(SUCCESS, $msg, []);

    }


    /**
     * 一对一直播收益清单
     */
    public function chat_money_list()
    {

        // 接受参数
        $page = intval($_POST['page']) ?? 1;

        $search_info = trim($_POST['content']); //搜索内容

        $search_team = trim($_POST['team']); //公会名称

        $page_size = 200;

        $offset = ($page - 1) * $page_size;

        $appid = DATABASESUFFIX;
        $user_f = '0';

        $return_data = [
            'total' => 0,
            'info' => []
        ];

        $where = '';
        if ($search_info) {
            if (is_numeric($search_info)) {
                //uid
                $where .= " and uid={$search_info}";
            } else {
                //昵称
                $user_id_str = '';
                $user_sql = "select id from user_{$appid}_{$user_f} where is_anchor=1 and nickname like %{$search_info}%";
                $user_info = $this->db->query($user_sql)->result_array();
                $user_info = $user_info && count($user_info) ? $user_info : [];
                if (count($user_info)) {
                    $new_user_ids = [];
                    foreach ($user_info as $u_k => $user) {
                        array_push($new_user_ids, $user['id']);
                    }
                    $user_id_str = implode(',', $new_user_ids);
                } else {
                    ajax_return(SUCCESS, '获取成功', $return_data);
                }

                if ($user_id_str) {
                    $where .= " and uid in ({$user_id_str})";
                }
            }
        }


        $team_sql = "select id,name from sociaty_{$appid}";
        $team_info = $this->db->query($team_sql)->result_array();

        $team_info = $team_info && count($team_info) ? $team_info : [];

        if ($search_team) {
            //公会
            $team_id_str = '';
            if (count($team_info)) {
                $new_team_ids = [];
                foreach ($team_info as $key => $value) {
                    if (strpos($value['name'], $search_team) !== false) {
                        array_push($new_team_ids, $value['id']);
                    }
                }
                if (count($new_team_ids)) {
                    $team_id_str = implode(',', $new_team_ids);
                } else {
                    ajax_return(SUCCESS, '获取成功', $return_data);
                }

            } else {
                ajax_return(SUCCESS, '获取成功', $return_data);
            }

            if ($team_id_str) {
                $where .= " and u.sociaty_id in ({$team_id_str})";
            }
        }


        if ($search_team) {

            $sql = "select ccl.id,ccl.uid,ccl.is_deleted from chat_create_log_{$appid} as ccl left join user_{$appid}_{$user_f} as u on u.id = ccl.uid where ccl.is_deleted in (0,2)";
            $sql .= $where;

            $total_sql = $sql;

            $sql .= " ORDER BY ccl.created_at DESC limit {$page_size} offset {$offset}";
        } else {

            $sql = "select id,uid,is_deleted from chat_create_log_{$appid} where is_deleted in (0,2)";
            $sql .= $where;
            $total_sql = $sql;

            $sql .= " ORDER BY created_at DESC limit {$page_size} offset {$offset}";
        }

        $total_res = $this->db->query($total_sql)->result_array();
        $return_data['total'] = $total_res && count($total_res) ? count($total_res) : 0;

        $res = $this->db->query($sql)->result_array();


        foreach ($res as $key => $item) {

            $redis_arr = $this->redis->hMGet("user:{$item['uid']}", ['nickname', 'sociaty_id']);

            $res[$key]['nickname'] = $redis_arr['nickname'];

            //公会处理
            if (count($team_info)) {
                $res[$key]['sociaty_name'] = '暂无公会';
                foreach ($team_info as $kk => $value) {
                    if ($value['id'] == $redis_arr['sociaty_id']) {
                        $res[$key]['sociaty_name'] = $value['name'];
                        break;
                    }
                }
            } else {
                $res[$key]['sociaty_name'] = '暂无公会';
            }


            //一对一信息记录
            $call_sql = "select anchor_uid,duration,amount from chat_call_log_{$appid} where anchor_uid={$item['uid']} and status=2";

            $call_info = $this->db->query($call_sql)->result_array();

            $call_info = $call_info && count($call_info) ? $call_info : [];
            $res[$key]['call_people_num'] = count($call_info) ? count($call_info) : 0;

            $total_times = 0;
            $video_money = 0;
            foreach ($call_info as $t => $times) {
                $total_times += intval($times['duration']);
                $video_money += floatval($times['amount']);
            }
            $res[$key]['call_times'] = number_format(floatval($total_times) / 60, 2);
            $res[$key]['video_money'] = number_format(floatval($video_money), 2);

            //礼物记录
            $gift_sql = "select fee from exp_gift_{$appid} where to_uid={$item['uid']} and type=1 and call_id!=0";

            $gift_info = $this->db->query($gift_sql)->result_array();
            $gift_info = $gift_info && count($gift_info) ? $gift_info : [];
            $gift_num = 0;
            foreach ($gift_info as $g_k => $gift) {
                $gift_num += floatval($gift['fee']);
            }
            $res[$key]['gift_money'] = number_format(floatval($gift_num), 2);


            $res[$key]['total_money'] = number_format(floatval($res[$key]['video_money']) + floatval($res[$key]['gift_money']), 2);


        }

        $return_data['info'] = $res;
        ajax_return(SUCCESS, '获取成功', $return_data);

    }


    /**
     * 指定用户的具体一对一
     */
    public function chat_detail()
    {
        // 接受参数

        $uid = intval($_POST['uid']) ?? 0;

        $page = intval($_POST['page']) ?? 1;

        $search_info = trim($_POST['content']); //搜索内容

        $time = $_POST['time']; //时间

        $page_size = 10;

        $offset = ($page - 1) * $page_size;

        $appid = DATABASESUFFIX;

        $user_f = '0';


        $where1 = '';
        $where2 = '';

        $return_data = [
            'total' => 0,
            'info' => []
        ];


        if ($search_info) {
            if (is_numeric($search_info)) {
                //uid
                $where1 .= " and anchor_uid={$search_info}";

            } else {
                //昵称
                $user_id_str = '';
                $user_sql = "select id from user_{$appid}_{$user_f} where is_anchor=1 and nickname like %{$search_info}%";
                $user_info = $this->db->query($user_sql)->result_array();
                $user_info = $user_info && count($user_info) ? $user_info : [];
                if (count($user_info)) {
                    $new_user_ids = [];
                    foreach ($user_info as $u_k => $user) {
                        array_push($new_user_ids, $user['id']);
                    }
                    $user_id_str = implode(',', $new_user_ids);
                } else {
                    ajax_return(SUCCESS, '获取成功', $return_data);
                }

                if ($user_id_str) {
                    $where1 .= " and anchor_uid in ({$user_id_str})";
                }
            }
        }

        if ($time) {
            //时间
            $time[0] = date("Y-m-d H:i:s", $time[0] / 1000);
            $time[1] = date("Y-m-d H:i:s", $time[1] / 1000);

            $where1 .= " and connect_time between {$time[0]} and {$time[1]}";
            $where2 .= " and uptime between {$time[0]} and {$time[1]}";
        }


        //一对一信息记录
        $chat_sql = "select id,anchor_uid as uid,duration,amount as video_money,connect_time from chat_call_log_{$appid} where anchor_uid={$uid} and status=2";

        if ($where1) {
            $chat_sql .= $where1;
        }

        $chat_total = $this->db->query($chat_sql)->result_array();
        $return_data['total'] = $chat_total && count($chat_total) ? count($chat_total) : 0;


        $chat_sql .= " ORDER BY connect_time DESC limit {$page_size} offset {$offset}";
        $chat_info = $this->db->query($chat_sql)->result_array();
        $chat_info = $chat_info && count($chat_info) ? $chat_info : [];

//        var_dump($chat_info);
        foreach ($chat_info as $key => $item) {

            $chat_info[$key]['duration'] = number_format(floatval($item['duration']) / 60, 2);

            //礼物记录
            $gift_sql = "select fee from exp_gift_{$appid} where to_uid={$uid} and type=1 and call_id={$item['id']}";
            if ($where2) {
                $gift_sql .= $where2;
            }

            $gift_info = $this->db->query($gift_sql)->result_array();
            $gift_info = $gift_info && count($gift_info) ? $gift_info : [];

            $gift_num = 0;
            foreach ($gift_info as $g_k => $gift) {
                $gift_num += floatval($gift['fee']);
            }
            $chat_info[$key]['gift_money'] = number_format(floatval($gift_num), 2);

            $chat_info[$key]['total_money'] = number_format(floatval($item['video_money']) + floatval($chat_info[$key]['gift_money']), 2);

            $redis_arr = $this->redis->hMGet("user:{$item['uid']}", ['nickname']);

            $chat_info[$key]['nickname'] = $redis_arr['nickname'];

        }

        $return_data['info'] = $chat_info;
        ajax_return(SUCCESS, '获取成功', $return_data);
    }


    /**
     * 该功能将文本内容保存至服务器的存储文件夹
     * @param $content      array 将要写入的内容
     * @param $filename     string 将写入（或创建）的文件名
     * @param $svrAddress   string ts服务器地址
     * @param $path         string 保存路径，不传默认为/static/video_headers/
     * @return string OR false on fail
     */
    private function saveContentToFile($content = [], $filename = '', $svrAddress, $path = '')
    {
        $dir = BASEPATH . 'static/data/' . $path;
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $file = $dir . $filename;

        if (!file_exists($file)) {
            $this->fileTemplate($file, $content, $svrAddress);
            usleep(1000);
            return DATA_SERVER . $path . $filename;
        } else {
            return false;
        }
    }

    private function getMilliSecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    private function fileTemplate($file, $content = [], $svrAddress = '')
    {
        if ($content) {
            $handler = fopen($file, "w");
            fwrite($handler, '#EXTM3U' . "\n");
            fwrite($handler, '#EXT-X-VERSION:3' . "\n");
            fwrite($handler, '#EXT-X-TARGETDURATION:10' . "\n");
            fwrite($handler, '#EXT-X-MEDIA-SEQUENCE:0' . "\n");
            fwrite($handler, '#EXT-X-KEY:METHOD=AES-128,URI="https://cdn.0025985.com/enc4.key",IV=0x323a0f4a78ed1aa50f6883c589e61c43' . "\n");
            $total = count($content);
            foreach ($content as $key => $value) {
                if ($total == ($key + 1)) fwrite($handler, '#EXTINF:10,416667,' . "\n");
                else fwrite($handler, '#EXTINF:10,416667' . "\n");
                fwrite($handler, $svrAddress . $value . "\n");
            }
            fwrite($handler, '#EXT-X-ENDLIST' . "\n");
            fclose($handler);
        }

        return true;
    }


    /**
     * 检测用户是否在线和离线以及直播中
     * @param $data
     * @param $uid
     * @return mixed
     */
    private function check_list_user_status($data, $uid)
    {

        $key = "live:anchor:hot";
        $anchor_ids = $this->redis->zRevRange($key, 0, -1);
        $star = $this->redis->sMembers("live:anchor:star");
        if ($star) {
            foreach ($star as $v) {
                if (in_array($v, $anchor_ids)) {
                    $anchor_ids = array_diff($anchor_ids, [$v]);
                    array_unshift($anchor_ids, $v);
                }
            }
        }

        $res = $this->redis->hmget("user:$uid", ['gender', 'video_on', 'heartbeat', 'is_fake']);
        $time = time();

        if ($anchor_ids) {
            if (($time - intval($res['heartbeat'])) > 300) {
                $data['is_online'] = 0;//离线
            } else {
                $data['is_online'] = 1;//在线
            }
            foreach ($anchor_ids as $anchor_id) {
                if ($anchor_id == $uid) {
                    if ($this->redis->exists("live:room:$anchor_id")) {
                        $data['is_online'] = 2;//直播中
                    } else {
                        //检测一对一
                        $data = $this->check_is_chat($data, $uid);
                    }
                    break;
                }

            }
        } else {
            if (($time - intval($res['heartbeat'])) > 300) {
                $data['is_online'] = 0;//离线
            } else {
                $data['is_online'] = 1;//在线
            }
            //检测一对一
            $data = $this->check_is_chat($data, $uid);
        }


        return $data;
    }

    /**
     * 检查是否已正在一对一
     * @param $data
     * @param $uid
     * @return mixed
     */
    private function check_is_chat($data, $uid)
    {
        $call_id = $this->redis->get("call:user:$uid");
        if ($call_id) {
            $res = $this->redis->hmget("call:$call_id", ['status']);
            if ($res !== false && $res['status'] == 1) {
                $data['is_online'] = 3;
            }
        }
        return $data;
    }

}