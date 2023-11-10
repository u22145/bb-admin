<?php


/**
 * 电台约会
 * Class Radio
 */
class Radio extends Controller
{

    private $path = '/data/upload/'; //默认目录

    public function __construct()
    {
        parent::__construct();
        $this->init_db();
    }


    /**
     * 类型列表
     */
    public function type_manage()
    {
        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $type = input('post.type', 'intval', 1);

        $title = []; //表格标题

        $sql = '';

        switch ($type) {
            case 1:
                $title = [
                    ['name' => 'ID', 'prop' => 'id'],
                    ['name' => '地区名', 'prop' => 'name'],
                    ['name' => '地区类型', 'prop' => 'area_type'],
                ];
                $table = 'radio_area_' . DATABASESUFFIX;
                $sql = "select id,name,area_type from {$table} where area_type=1 and p_id=0";
                break;
            case 2:

                $title = [
                    ['name' => 'ID', 'prop' => 'id'],
                    ['name' => '活动名字', 'prop' => 'name'],
                    ['name' => '图片', 'prop' => 'img_url'],
                ];

                $table = 'radio_activity_type_' . DATABASESUFFIX;
                $sql = "select id,name,img_url from {$table}";

                break;
            case 3:

                $title = [
                    ['name' => 'ID', 'prop' => 'id'],
                    ['name' => '活动名字', 'prop' => 'name'],
                ];

                $table = 'radio_expect_type_' . DATABASESUFFIX;
                $sql = "select id,name from {$table}";

                break;
            case 4:
                $title = [
                    ['name' => 'ID', 'prop' => 'id'],
                    ['name' => '活动名字', 'prop' => 'name'],
                ];

                $table = 'radio_evaluation_types_' . DATABASESUFFIX;
                $sql = "select id,name from {$table}";

                break;
            case 5:
                $title = [
                    ['name' => 'ID', 'prop' => 'id'],
                    ['name' => '活动名字', 'prop' => 'name'],
                ];

                $table = 'radio_report_type_' . DATABASESUFFIX;
                $sql = "select id,name from {$table}";

                break;
        }

        $total_info = $this->db->query($sql)->result_array();
        $total = $total_info && count($total_info) ? count($total_info) : 0;

        $sql .= " limit {$page_size} offset {$limit}";
        $list = $this->db->query($sql)->result_array();

        $list = $list && count($list) ? $list : [];

        switch ($type) {
            case 1:
                foreach ($list as $key => $item) {
                    switch ($item['area_type']) {
                        case 1:
                            $list[$key]['area_type'] = '中国';
                            break;
                        case 2:
                            $list[$key]['area_type'] = '北美国家';
                            break;
                        case 3:
                            $list[$key]['area_type'] = '亚太地区';
                            break;
                        case 4:
                            $list[$key]['area_type'] = '拉丁美洲';
                            break;
                        case 5:
                            $list[$key]['area_type'] = '非洲';
                            break;
                        case 6:
                            $list[$key]['area_type'] = '欧洲地区';
                            break;
                    }
                }
                break;
            case 2:
                foreach ($list as $key => $item) {
                    $list[$key]['img_url'] = get_pic_url($item['img_url']);
                }
                break;
        }

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'title' => $title,
            'list' => $list,
            'page' => $page,
            'total' => (int)$total
        ));

    }


    /**
     * 获取指定省份或者国家下一级
     */
    public function city_type_manage()
    {
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', 10);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $title = [
            ['name' => 'ID', 'prop' => 'id'],
            ['name' => '地区名', 'prop' => 'name']
        ];
        $table = 'radio_area_' . DATABASESUFFIX;
        $sql = "select id,name,area_type from {$table} where area_type=1 and p_id={$id}";

        $total_info = $this->db->query($sql)->result_array();
        $total = $total_info && count($total_info) ? count($total_info) : 0;

        $sql .= " limit {$page_size} offset {$limit}";
        $list = $this->db->query($sql)->result_array();

        $list = $list && count($list) ? $list : [];


        // 返回数据
        ajax_return(SUCCESS, '', array(
            'title' => $title,
            'list' => $list,
            'page' => $page,
            'total' => (int)$total
        ));
    }


    /**
     * 处理指定省份或者国家的子类型
     */
    public function deal_city_type_info()
    {
        $name = input('post.name', '', '');
        $id = input('post.id', 'intval', 0);
        $pid = input('post.pid', 'intval', 0);


        $table = 'radio_area_' . DATABASESUFFIX;
        if ($id) {
            $info_sql = "update {$table} set name='{$name}' where id={$id}";
        } else {
            $info_sql = "insert into {$table} (`name`,`area_type`,`p_id`) values ('{$name}',1,{$pid})";
        }

        $affected_rows = $this->db->query($info_sql)->affected_rows();

        if ($affected_rows) {
            ajax_return(SUCCESS, '操作成功', []);
        } else {
            ajax_return(ERROR, '操作失败', []);
        }

    }


    /**
     * 处理新增或者修改类型的数据
     */
    public function deal_type_info()
    {

        $type = input('post.type', 'intval', 1);

        $name = input('post.name', '', '');

        $id = input('post.id', 'intval', 0);

        $file = null;
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
        }

        $affected_rows = 0;
        $info_sql = '';
        if ($file) {

            // 取扩展名
            $ext_arr = explode(".", $file['name']);
            $ext = array_pop($ext_arr);
            $path = "/radio_activity" . date("/Ymd_") . rand_code(16, 'both');
            $filename = $path . "." . $ext;

            $file_name = '/upload' . $filename;

            $upload = new Upload();

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

            $data_info = [
                'name' => $file_name
            ];


            $url = API_SERVER_URL . '/radio/upload_single_img';
            $sys_result = $this->curl_post($url, $data_info);
//            var_dump($sys_result);
            if (!$sys_result || $sys_result !== 1) {
                ajax_return(ERROR, get_tips(7015) . '同步到接口项目失败');
            }

            $table = 'radio_activity_type_' . DATABASESUFFIX;
            if ($id) {
                $info_sql = "update {$table} set name='{$name}',img_url='{$filename}' where id={$id}";
            } else {
                $info_sql = "insert into {$table} (`name`,`img_url`) values ('{$name}','{$filename}')";
            }

            if ($info_sql) {
                $affected_rows = $this->db->query($info_sql)->affected_rows();
            } else {
                ajax_return(ERROR, '系统错误，操作失败', []);
            }
        } else {

            switch ($type) {
                case 1:
                    $table = 'radio_area_' . DATABASESUFFIX;
                    if ($id) {
                        $info_sql = "update {$table} set name='{$name}' where id={$id}";
                    } else {
                        $info_sql = "insert into {$table} (`name`,`area_type`,`p_id`) values ('{$name}',1,0)";
                    }
                    break;
                case 2:
                    $table = 'radio_activity_type_' . DATABASESUFFIX;
                    if ($id) {
                        $info_sql = "update {$table} set name='{$name}' where id={$id}";
                    }
                    break;
                case 3:
                    $table = 'radio_expect_type_' . DATABASESUFFIX;
                    if ($id) {
                        $info_sql = "update {$table} set name='{$name}' where id={$id}";
                    } else {
                        $info_sql = "insert into {$table} (`name`) values ('{$name}')";
                    }
                    break;
                case 4:
                    $table = 'radio_evaluation_types_' . DATABASESUFFIX;
                    if ($id) {
                        $info_sql = "update {$table} set name='{$name}' where id={$id}";
                    } else {
                        $info_sql = "insert into {$table} (`name`) values ('{$name}')";
                    }
                    break;
                case 5:
                    $table = 'radio_report_type_' . DATABASESUFFIX;
                    if ($id) {
                        $info_sql = "update {$table} set name='{$name}' where id={$id}";
                    } else {
                        $info_sql = "insert into {$table} (`name`) values ('{$name}')";
                    }
                    break;
            }

            if ($info_sql) {
                $affected_rows = $this->db->query($info_sql)->affected_rows();
            } else {
                ajax_return(ERROR, '系统错误，操作失败', []);
            }


        }


        if ($affected_rows) {
            ajax_return(SUCCESS, '操作成功', []);
        } else {
            ajax_return(ERROR, '操作失败', []);
        }

    }

    /**
     * 删除指定的类型数据
     */
    public function delete_type_info()
    {
        $type = input('post.type', 'intval', 1);
        $id = input('post.id', 'intval', 0);

        if (!$id) {
            ajax_return(ERROR, '参数错误', []);
        }

        switch ($type) {
            case 1:
                $table = 'radio_area_' . DATABASESUFFIX;
                break;
            case 2:
                $table = 'radio_activity_type_' . DATABASESUFFIX;
                break;
            case 3:
                $table = 'radio_expect_type_' . DATABASESUFFIX;
                break;
            case 4:
                $table = 'radio_evaluation_types_' . DATABASESUFFIX;
                break;
            case 5:
                $table = 'radio_report_type_' . DATABASESUFFIX;
                break;
        }

        $delete_sql = "delete from {$table} where id={$id}";
        $affected_rows = $this->db->query($delete_sql)->affected_rows();
        if ($affected_rows) {
            ajax_return(SUCCESS, '删除成功', []);
        } else {
            ajax_return(ERROR, '删除失败', []);
        }

    }

    /**
     * 删除指定省份或者国家的子类
     */
    public function delete_city_type_info()
    {
        $id = input('post.id', 'intval', 0);

        if (!$id) {
            ajax_return(ERROR, '参数错误', []);
        }

        $table = 'radio_area_' . DATABASESUFFIX;

        $delete_sql = "delete from {$table} where id={$id}";
        $affected_rows = $this->db->query($delete_sql)->affected_rows();
        if ($affected_rows) {
            ajax_return(SUCCESS, '删除成功', []);
        } else {
            ajax_return(ERROR, '删除失败', []);
        }

    }


    /**
     * 电台约会管理列表
     */
    public function radio_list()
    {
        $user_id = input('post.user_id', 'intval', 0);
        $release_time = input('post.release_time', '', '');
        $status = input('post.status', 'intval', 0);
        $active_time = input('post.active_time', '', '');
        $op_status = input('post.op_status', 'intval', '');

        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);

        $limit = (($page - 1) * $page_size);

        $user_table_num = 0;

        $rrd_table = 'radio_release_date_' . DATABASESUFFIX;

        $user_table = 'user_' . DATABASESUFFIX . '_' . $user_table_num;

        $expect_table = 'radio_expect_type_' . DATABASESUFFIX;

        $activity_table = 'radio_activity_type_' . DATABASESUFFIX;

        $img_table = 'radio_img_' . DATABASESUFFIX;

        $like_table = 'radio_release_date_like_' . DATABASESUFFIX;

        $leave_message_table = 'radio_leave_message_' . DATABASESUFFIX;

        $sign_up_table = 'radio_sign_up_' . DATABASESUFFIX;


        $sql = "select u.nickname,rrd.* from {$rrd_table} as rrd left join {$user_table} as u on u.id=rrd.u_id where is_deleted=0";


        if ($user_id) {
            $sql .= " and rrd.u_id=" . intval($user_id);
        }

        if ($status) {
            $sql .= " and rrd.is_active=" . intval($status - 1);
        }

        if ($op_status) {
            $sql .= " and rrd.status=" . intval($op_status - 1);
        }


        if ($release_time && count($release_time) == 2) {
            $release_time[0] = $release_time[0] / 1000;
            $release_time[1] = $release_time[1] / 1000;
            $sql .= " and rrd.release_time between {$release_time[0]} and {$release_time[1]}";
        }
        if ($active_time && count($active_time) == 2) {
            $active_time[0] = $active_time[0] / 1000;
            $active_time[1] = $active_time[1] / 1000;
            $sql .= " and rrd.act_time between {$active_time[0]} and {$active_time[1]}";;
        }


        $total_info = $this->db->query($sql)->result_array();
        $total = $total_info && count($total_info) ? count($total_info) : 0;

        $sql .= " order by rrd.release_time desc limit {$page_size} offset {$limit}";

        $list = $this->db->query($sql)->result_array();

        $list = $list && count($list) ? $list : [];

        foreach ($list as $key => &$item) {

            $item['act_time'] = date('Y-m-d', $item['act_time']);
            $item['release_time'] = date('Y-m-d', $item['release_time']);


            switch ($item['status']) {
                case 0:
                    $item['status_txt'] = '待审核';
                    break;
                case 1:
                    $item['status_txt'] = '已审核';
                    break;
                case 2:
                    $item['status_txt'] = '审核未过';
                    break;
                case 3:
                    $item['status_txt'] = '已删除';
                    break;
            }

            switch ($item['is_active']) {
                case 0:
                    $item['is_active'] = '未开始活动';
                    break;
                case 1:
                    $item['status_txt'] = '已开始活动';
                    break;
            }


            //点赞数量
            $like_num_sql = "select id,num from {$like_table} where release_id={$item['id']}";
            $like_num = $this->db->query($like_num_sql)->row_array();
            $item['like_num'] = $like_num ? $like_num['num'] : 0;


            //留言数量
            $select_com_num_sql = "SELECT count('id') as num FROM {$leave_message_table} WHERE release_date_id={$item['id']} AND status=1 AND is_deleted=0";
            $com_num_info = $this->db->query($select_com_num_sql)->row_array();
            $item['com_num'] = $com_num_info ? $com_num_info['num'] : 0;

            //报名数量
            $select_sign_num_sql = "SELECT count('id') as num FROM  {$sign_up_table} WHERE release_id={$item['id']} AND status!=5 AND status!=6";
            $sign_num_info = $this->db->query($select_sign_num_sql)->row_array();
            $item['sign_num'] = $sign_num_info ? $sign_num_info['num'] : 0;


            //获取对应act_ids的名字
            $a_ids = trim($item['act_ids']);

            if ($a_ids) {
                $where_in_a_ids = "id IN(" . $a_ids . ")";

                $act_a_ids_sql = "SELECT name FROM $activity_table WHERE {$where_in_a_ids}";
                $act_ids_info = $this->db->query($act_a_ids_sql)->result_array();
                if ($act_ids_info) {
                    $act_ids = [];
                    foreach ($act_ids_info as $kk => $value) {
                        $act_ids[] = $value['name'];
                    }
                    $item['act_ids'] = implode(',', $act_ids);
//                    var_dump($item['act_ids']);
//                    return false;
                } else {
                    $item['act_ids'] = '';
                }
            } else {
                $item['act_ids'] = '';
            }


            //获取对应exp_ids的名字
            $e_ids = trim($item['exp_ids']);
            if ($e_ids) {
                $where_in_e_ids = "id IN(" . $e_ids . ")";
                //拿到img_url和name
                $exp_ids_sql = "SELECT name FROM {$expect_table} WHERE {$where_in_e_ids}";
                $exp_ids_info = $this->db->query($exp_ids_sql)->result_array();
                if ($exp_ids_info && count($exp_ids_info)) {
                    $exp_ids = [];
                    foreach ($exp_ids_info as $kk => $value) {
                        $exp_ids[] = $value['name'];
                    }
                    $item['exp_ids'] = implode(',', $exp_ids);
                } else {
                    $item['exp_ids'] = '';
                }
            } else {
                $item['exp_ids'] = '';
            }


            //约会信息图片
            $img_url_sql = "SELECT img_url FROM {$img_table} WHERE img_type=1 AND img_p_id={$item['id']}";

            $img_url_row = $this->db->query($img_url_sql)->result_array();
            if ($img_url_row) {
                foreach ($img_url_row as $img_key => $value) {
                    $img_url_row[$img_key]['img_url'] = get_pic_url($value['img_url'], '', '', false);
                }
                $item['img_list'] = $img_url_row;
            } else {
                $item['img_list'] = [];
            }
        }
        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int)$total
        ));

    }


    /**
     * 修改电台审核状态
     */
    public function change_radio_status()
    {
        $id = input('post.id', 'intval', 0);
        $status = intval(input('post.status', 'intval', 0));

        $rrd_table = 'radio_release_date_' . DATABASESUFFIX;

        $update_sql = "update {$rrd_table} set status={$status} where id={$id}";

        $affected_rows = $this->db->query($update_sql)->affected_rows();

        if ($affected_rows) {
            ajax_return(SUCCESS, '更新成功', []);
        } else {
            ajax_return(ERROR, '更新失败', []);
        }

    }


    /**
     * 获取留言列表
     */
    public function get_leave_message()
    {
        //使用哪张user表
        //todo 用户表使用
        //$user_table_num = $uid % 10;
        $user_table_num = 0;

        $appid = DATABASESUFFIX;

        $message_id = input('post.id', 'intval', 0);; //留言的列表id


        $select_sql = "SELECT rlm.id,rlm.u_id,rlm.notice as content,rlm.op_time as uptime,u.nickname,u.age,u.gender,u.avatar,u.country FROM radio_leave_message_{$appid} as rlm left join user_{$appid}_{$user_table_num} as u on u.id=rlm.u_id WHERE rlm.release_date_id={$message_id} AND rlm.is_deleted=0 ORDER BY rlm.op_time DESC";

        $result_info = $this->db->query($select_sql)->result_array();


        if ($result_info) {

            $review_id = implode(',', array_column($result_info, 'u_id'));
            $leave_ids = implode(',', array_column($result_info, 'id'));

            //回复id
            $reply_res = [];
            if ($review_id) {
                $reply_sql = "select rlmr.id,rlmr.leave_id,rlmr.uid as u_id,rlmr.p_id,rlmr.content,rlmr.uptime,u.nickname,u.age,u.gender,u.avatar,u.country from radio_leave_message_reply_{$appid} as rlmr left join user_{$appid}_{$user_table_num} as u on u.id=rlmr.uid where rlmr.p_id in ({$review_id}) and rlmr.leave_id in ({$leave_ids}) and rlmr.t_id = {$message_id} and rlmr.is_deleted=0 order by rlmr.uptime desc";
                $reply_res = $this->db->query($reply_sql)->result_array();
            }

            $floor = 0;

            foreach ($result_info as $key => $item) {

                $country_sql = "SELECT id,zh,country,area_code FROM  cat_country_{$appid} WHERE id = {$item['country']} and status=1";
                $country_rows = $this->db->query($country_sql)->result_array();
                if (count($country_rows)) {
                    foreach ($country_rows as $c_key => $c_value) {
                        $redis_arr = $redis_arr = $this->redis->hMGet("user:{$item['u_id']}", ['nickname', 'avatar', 'gender', 'age', 'country']);
                        $country_rows[$c_key]['country_flag'] = get_pic_url($redis_arr['country'] ?? '', 'country');
                    }
                    $result_info[$key]['country'] = $country_rows;
                } else {
                    $result_info[$key]['country'] = [];
                }

                $result_info[$key]['avatar'] = get_pic_url($item['avatar'], '', '', false);

                $result_info[$key]['floor_id'] = ++$floor . '楼';

                if (empty($reply_res)) {
                    $result_info[$key]['reply'] = [];
                } else {
                    $result_info[$key]['reply'] = [];
                    foreach ($reply_res as $reply_k => $reply) {
                        if ($item['u_id'] == $reply['p_id'] && $item['id'] == $reply['leave_id']) {

                            $country_sql = "SELECT id,zh,country,area_code FROM  cat_country_{$appid} WHERE id = {$reply['country']} and status=1";
                            $country_rows = $this->db->query($country_sql)->result_array();
                            if (count($country_rows)) {
                                foreach ($country_rows as $c_key => $c_value) {
                                    $redis_arr = $redis_arr = $this->redis->hMGet("user:{$reply['u_id']}", ['nickname', 'avatar', 'gender', 'age', 'country']);
                                    $country_rows[$c_key]['country_flag'] = get_pic_url($redis_arr['country'] ?? '', 'country');
                                }
                                $reply['country'] = $country_rows;
                            } else {
                                $reply['country'] = [];
                            }

                            $reply['avatar'] = get_pic_url($reply['avatar'], '', '', false);
                            unset($reply['p_id']);
                            unset($reply['leave_id']);

                            $result_info[$key]['reply'][] = $reply;
                        }
                    }
                }
            }
        } else {
            $result_info = array();
        }

        ajax_return(SUCCESS, '拉去数据成功', $result_info);

    }


    /**
     * 删除电台发布信息
     */
    public function delete_radio_info()
    {

        $id = input('post.id', 'intval', 0);

        if (!$id) {
            ajax_return(ERROR, '参数错误', []);
        }


        $rrd_table = 'radio_release_date_' . DATABASESUFFIX;

        $img_table = 'radio_img_' . DATABASESUFFIX;

        $like_table = 'radio_release_date_like_' . DATABASESUFFIX;

        $like_record_table = 'radio_release_date_like_record_' . DATABASESUFFIX;

        $leave_message_table = 'radio_leave_message_' . DATABASESUFFIX;

        $leave_message_reply_table = 'radio_leave_message_reply_' . DATABASESUFFIX;

        $sign_up_table = 'radio_sign_up_' . DATABASESUFFIX;

        $sign_up_log_table = 'radio_sign_up_log_' . DATABASESUFFIX;

        $this->db->trans_begin();

        try {
            $delete_sql = "delete from {$rrd_table} where id={$id}";
            $this->db->query($delete_sql)->affected_rows();

            $delete_leave_mess_sql = "delete from {$leave_message_table} where release_date_id={$id}";
            $this->db->query($delete_leave_mess_sql)->affected_rows();

            $delete_leave_mess_reply_sql = "delete from {$leave_message_reply_table} where t_id={$id}";
            $this->db->query($delete_leave_mess_reply_sql)->affected_rows();

            $delete_sign_up_sql = "delete from {$sign_up_table} where release_id={$id}";
            $this->db->query($delete_sign_up_sql)->affected_rows();

            $delete_sign_up_log_sql = "delete from {$sign_up_log_table} where release_id={$id}";
            $this->db->query($delete_sign_up_log_sql)->affected_rows();

            $delete_img_sql = "delete from {$img_table} where img_p_id={$id} and img_type=1";
            $this->db->query($delete_img_sql)->affected_rows();

            $date_like_id_sql = "select id from {$like_table} where release_id={$id}";
            $date_like_ids = $this->db->query($date_like_id_sql)->result_array();
            if ($date_like_ids && count($date_like_ids)) {
                $del_ids = [];
                foreach ($date_like_ids as $key => $item) {
                    $del_ids[] = $item['id'];
                }
                $del_ids = implode(',', $del_ids);

                $delete_data_like_record_sql = "delete from {$like_record_table} where like_id in ({$del_ids})";
                $this->db->query($delete_data_like_record_sql)->affected_rows();
            }

            $delete_data_like_sql = "delete from {$like_table} where release_id={$id}";
            $this->db->query($delete_data_like_sql)->affected_rows();


            $this->db->trans_commit();
            ajax_return(SUCCESS, '删除成功', []);
        } catch (\Exception $exception) {
            $this->db->trans_rollback();
            ajax_return(ERROR, '删除失败', []);
        }


    }


    /**
     * 活动管理列表信息
     */
    public function activity_list()
    {

        $r_id = input('post.r_id', 'intval', 0);
        $a_id = input('post.a_id', 'intval', 0);

        $active_time = input('post.active_time', '', '');

        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);

        $limit = (($page - 1) * $page_size);

        $user_table_num = 0;

        $rrd_table = 'radio_release_date_' . DATABASESUFFIX;

        $rar_table = 'radio_activity_record_time_' . DATABASESUFFIX;

        $user_table = 'user_' . DATABASESUFFIX . '_' . $user_table_num;

        $expect_table = 'radio_expect_type_' . DATABASESUFFIX;

        $activity_table = 'radio_activity_type_' . DATABASESUFFIX;


        $sql = "select rar.id,rar.sign_id as active_uid,rar.release_id as rrd_id,rar.pay_num as amount,rar.end_time,rar.is_deal,rrd.u_id,rrd.act_ids,rrd.city,rrd.place,rrd.act_time,rrd.exp_ids,rrd.notice from {$rar_table} as rar left join {$rrd_table} as rrd on rar.release_id=rrd.id";


        if ($r_id) {
            $sql .= " and rrd.u_id=" . intval($r_id);
        }

        if ($a_id) {
            $sql .= " and rar.sign_id=" . intval($a_id);
        }


        if ($active_time && count($active_time) == 2) {
            $active_time[0] = $active_time[0] / 1000;
            $active_time[1] = $active_time[1] / 1000;
            $sql .= " and rrd.act_time between {$active_time[0]} and {$active_time[1]}";;
        }


        $total_info = $this->db->query($sql)->result_array();
        $total = $total_info && count($total_info) ? count($total_info) : 0;

        $sql .= " order by rrd.act_time desc limit {$page_size} offset {$limit}";

        $list = $this->db->query($sql)->result_array();

        $list = $list && count($list) ? $list : [];

        $time = time();

        foreach ($list as $key => &$item) {

            $res = $this->redis->hmget("user:{$item['u_id']}", ['nickname']);
            $item['nickname'] = $res['nickname'];
            $res1 = $this->redis->hmget("user:{$item['active_uid']}", ['nickname']);
            $item['active_nickname'] = $res1['nickname'];

            /**
             * 172800:两天的时间戳
             */
            if ($time > $item['act_time'] && ($time - $item['act_time']) >= 172800) {
                $item['status_txt'] = '是';
            } else {
                $item['status_txt'] = '否';
            }
            $item['act_time'] = date('Y-m-d', $item['act_time']);

            if ($item['end_time']) {
                $item['end_time'] = date('Y-m-d', $item['end_time']);
                $item['is_active_end'] = '是';
            } else {
                $item['end_time'] = '/';
                $item['is_active_end'] = '否';
            }

            //获取对应act_ids的名字
            $a_ids = trim($item['act_ids']);

            if ($a_ids) {
                $where_in_a_ids = "id IN(" . $a_ids . ")";

                $act_a_ids_sql = "SELECT name FROM $activity_table WHERE {$where_in_a_ids}";
                $act_ids_info = $this->db->query($act_a_ids_sql)->result_array();
                if ($act_ids_info) {
                    $act_ids = [];
                    foreach ($act_ids_info as $kk => $value) {
                        $act_ids[] = $value['name'];
                    }
                    $item['act_ids'] = implode(',', $act_ids);
                } else {
                    $item['act_ids'] = '';
                }
            } else {
                $item['act_ids'] = '';
            }


            //获取对应exp_ids的名字
            $e_ids = trim($item['exp_ids']);
            if ($e_ids) {
                $where_in_e_ids = "id IN(" . $e_ids . ")";
                //拿到img_url和name
                $exp_ids_sql = "SELECT name FROM {$expect_table} WHERE {$where_in_e_ids}";
                $exp_ids_info = $this->db->query($exp_ids_sql)->result_array();
                if ($exp_ids_info && count($exp_ids_info)) {
                    $exp_ids = [];
                    foreach ($exp_ids_info as $kk => $value) {
                        $exp_ids[] = $value['name'];
                    }
                    $item['exp_ids'] = implode(',', $exp_ids);
                } else {
                    $item['exp_ids'] = '';
                }
            } else {
                $item['exp_ids'] = '';
            }
        }
        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int)$total
        ));
    }


    /**
     * 活动确认转移baby余额
     * type 1给发布者，2给报名者
     */
    public function trans_baby()
    {
        $id = input('post.id', 'intval', 0);
        $uid = input('post.uid', 'intval', 0);
        $amount = input('post.amount', '', '');

        if (!$id && !$uid && !$amount) {
            ajax_return(ERROR, '参数错误', []);
        }

        if (floatval($amount) <= 0) {
            ajax_return(ERROR, '参数错误', []);
        }
        $coin = STABLE_CURRENCY_NAME;


        $time = time();

        $rar_table = 'radio_activity_record_time_' . DATABASESUFFIX;

        $sql = "update {$rar_table} set is_deal=1,end_time={$time} where id={$id}";

        $info = $this->db->query($sql)->affected_rows();
        if ($info) {
            $pipe = $this->redis->pipeline();
            $pipe->hIncrByFloat(sprintf(RedisKey::USER, $uid), $coin . '_balance', floatval($amount));

            $pipe->sAdd(sprintf(RedisKey::USER_BALANCE_UPDATE), $uid);

            $pipe->exec();

            ajax_return(SUCCESS, '操作成功', []);

        } else {
            ajax_return(ERROR, '操作失败', []);
        }
    }

    /**
     * post请求
     * @param $url
     * @param array $post_data
     * @return bool|string
     */
    private function curl_post($url, $post_data = array())
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;

    }


}