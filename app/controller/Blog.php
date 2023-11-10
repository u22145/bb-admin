<?php

/**
 * Created by PhpStorm
 * User: mm
 * Date: 2019/7/24
 * Time: 10:59
 */
class Blog extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 动态列表 搜索动态
     */
    public function blog_list()
    {
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE);
        $page = intval($_POST['page_no'] ?? 1);
        $type = intval($_POST['type'] ?? 1);
        $start_time = input('post.starttime', '', '');
        $end_time = input('post.endtime', '', '');

        $appid = DATABASESUFFIX;
        $this->init_db();
        $uid = intval($_POST['uid'] ?? 0);
        $nickname = sql_format($_POST['nickname'] ?? "");
        $status = $_POST['status'] == "" ? 99 : intval($_POST['status']);
        $fee_status = $_POST['fee_status'] == "" ? 99 : intval($_POST['fee_status']);

        //查询条件
        $where = '1';
        if ($uid) {
            $where .= " and uid = $uid";
        }

        if ($nickname) {
            $where .= " and nickname like '%$nickname%'";
        }

        if ($status < 99) {
            $where .= " and status = $status";
        }

        if ($fee_status == 1) {
            $where .= " and fee > 0";
        } else if ($fee_status == 2) {
            $where .= " and fee = 0";
        }

        if ($start_time && $end_time) {
            $where .= " and uptime >= '{$start_time}' and uptime <= '{$end_time}'";
        }

        $now_id = (($page - 1) * $page_size);
        //条件
        $order = " order by top desc, id desc";
        $fields = "id, uid, nickname, pic, left(content, 20) content, likes_num, review_num, share_num, status, uptime, if(fee > 0, fee, '0') fee, buy_num, top";
        //动态列表
        $sql = "select $fields from sns_blog_$appid  where $where $order limit $now_id,$page_size";
        $res = $this->db->query($sql)->result_array();
        $res = $this->com_blog($res);

        if ($type == 2) {
            $header = [
                get_tips(2001),
                get_tips(2002),
                get_tips(2003),
                get_tips(2004),
                get_tips(2005),
                get_tips(2006),
                get_tips(2007),
                get_tips(2008),
                get_tips(2009),
                get_tips(1001)
            ];

            $path = export(get_tips(2010), $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        //总条数
        $sql = "select count(id) num from sns_blog_" . DATABASESUFFIX  . " where $where";
        $total_count_res = $this->db->query($sql)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }
        $data = array(
            'list' => $res,
            'page' => $page,
            'page_size' => $page_size,
            'page_count' => $page_count,
            'total' => intval($total_count_res['num'])
        );
        ajax_return(SUCCESS, get_tips(1003), $data);
    }

    /**
     * 动态详情
     */
    public function blog_detail()
    {
        //接收参数
        $appid = DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);
        $this->init_db();
         
        $fields = 'id, uid, nickname, content, pic, video, status, likes_num, share_num, review_num, description, uptime';
        $detail_sql = "select $fields from sns_blog_$appid  where id = $id";
        $res = $this->db->query($detail_sql)->row_array();
        $res['video'] = get_pic_url($res['video']);

        $pic_url_arr = explode('|', $res['pic']);
        $pic_arr = [];
        if (!empty($pic_url_arr)) {
            $aes = new Aes();
            foreach ($pic_url_arr as $pic) {
                if (substr($pic, 0, 1) != '/') {
                    $pic_arr[] = $aes->pic_decrypt(TS_SERVER.'/'.$pic);
                } else {
                    $pic_arr[] = get_pic_url($pic);
                }
            }
        }
        $res['pic_url'] = $pic_arr;
        ajax_return(SUCCESS, get_tips(1003), $res);
    }

    /**
     * 帖子操作 //批量  单个操作
     */
    public function update()
    {
        $appid = DATABASESUFFIX;
        $blog_id = input('post.blog_id');
        $status = intval($_POST['status']);
        $content = sql_format($_POST['description'] ?? '');
        $uid = intval($_POST['uid'] ?? 0);

        if (!$blog_id) {
            ajax_return(ERROR, get_tips(2011));
        }

        if ($status == 2 && empty($content)) {
            ajax_return(ERROR, get_tips(2012));
        }

        //TODO 帖子通过 不是第一次审核+1
        if ($status == 1) {
            //查询所有用户id  循环增加redis
            $sql = "select uid,status from sns_blog_$appid  where id in ($blog_id)";
            $blog_arr = $this->db->query($sql)->result_array();
            foreach ($blog_arr as $key => $val) {
                if ($val['status']) {
                    $this->redis->hIncrBy("user:{$val['uid']}", "blog_num", 1);
                }
            }
        }

        //TODO 帖子不通过 -1     写入用户消息队列
        if ($status == 2) {
            $this->load_model('common_model');
            $data = [
                "papa_id" => $blog_id,
                "status" => "$status",
                "msg" => $content
            ];
            $this->common_model->send_msg($uid, get_tips(2013));
            $this->common_model->push_notification($uid, get_tips(2013), $content, $data);
        }

        //修改状态
        $blog_sql = "update sns_blog_$appid set status = $status, description='$content' where id in ($blog_id)";
        $this->init_db();
        $result = $this->db->query($blog_sql)->affected_rows();

        $status_msg = ERROR;
        $msg = get_tips(1004);
        if ($result) {
            if ($status == 2) {
                // 判断值是否是小于0
                $num = $this->redis->hGet("user:$uid", 'blog_num');
                if ($num <= 0) {
                    $this->redis->hSet("user:$uid", "blog_num", 0);
                } else {
                    $this->redis->hIncrBy("user:$uid", "blog_num", -1);
                }
            }
            $status_msg = SUCCESS;
            $msg = get_tips(1005);
        }

        ajax_return($status_msg, $msg);
    }

    /**
     *   blog 視頻置頂
     */
    public function edit_top()
    {
        $appid = DATABASESUFFIX;
        $type = intval($_POST['type'] ?? 0);
        $blog_id = intval($_POST['blog_id'] ?? 0);
        $blog_id || ajax_return(ERROR, 'papa視頻id不能為空!');

        switch ($type) {
            case '1':
                $top = time();
                $sql = "update sns_blog_$appid set top = $top where id = $blog_id";
                break;
            case '2':
                $sql = "update sns_blog_$appid set top = 0 where id = $blog_id";
                break;
        }
        $this->db->query($sql);
        ajax_return(SUCCESS, '操作成功');
    }

    /**
     * 评论列表    搜索评论
     */
    public function rev_list()
    {
        //接收参数
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE);;
        $page = intval($_POST['page_no'] ?? 1);
        $module = intval($_POST['module'] ?? 1);
        $exp = $_POST['exp'] ?? 1;
        $starttime = $_POST['starttime'] ?? '';
        $endtime = $_POST['endtime'] ?? '';
        $this->init_db();

        $uid = intval($_POST['uid'] ?? 0);
        $nickname = sql_format($_POST['nickname'] ?? "");
        $tid = intval($_POST['tid'] ?? 0);

        if ($_POST['status'] == "") {
            $status = 99;
        } else {
            $status = intval($_POST['status']);
        }

        //查询条件
        $where = " module = $module";
        if ($uid) {
            $where .= " and uid = $uid";
        }
        if ($nickname) {
            $where .= " and nickname like '%$nickname%'";
        }
        if ($status != 99) {
            $where .= " and status = $status";
        } else {
            $where .= " and status in (0,1)";
        }
        if ($starttime && $endtime) {
            $where .= " and uptime >= '{$starttime}' and uptime <= '{$endtime}'";
        }
        $where .= $tid > 0 ? " and tid = {$tid}" : '';
        $now_id = (($page - 1) * $page_size);

        //条件
        $order = " order by id desc";
        $fields = "id, tid, uid, nickname, review, status, uptime";

        //动态列表
        $sql = "select $fields from sns_review_" . DATABASESUFFIX . "  where $where $order limit $now_id,$page_size";

        $res = $this->db->query($sql)->result_array();

        foreach ($res as &$val) {
            switch ($val['status']) {
                case 0:
                    $val['status_txt'] = get_tips(2014);
                    break;
                case 1:
                    $val['status_txt'] = get_tips(2015);
                    break;
                case 2:
                    $val['status_txt'] = get_tips(2016);
                    break;
                case 3:
                    $val['status_txt'] = get_tips(2017);
                    break;
            }
        }

        if ($exp == 'exp') {
            $header = [
                get_tips(2018),
                get_tips(2019),
                get_tips(2020),
                get_tips(2021),
                get_tips(2022),
                get_tips(2023),
                get_tips(1001)
            ];
            $path = export(get_tips(2024), $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }

        //总条数
        $sql = "select count(id) num from sns_review_" . DATABASESUFFIX . " where $where";
        $total_count_res = $this->db->query($sql)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }

        $data = array(
            'list' => $res,
            'page' => $page, //第几页
            'page_size' => $page_size, //每页显示几条
            'page_count' => $page_count, //有几页
            'total' => intval($total_count_res['num']) //总条数
        );

        ajax_return(SUCCESS, get_tips(1003), $data);
    }

    /**
     * 评论详情
     */
    public function rev_detail()
    {
        //接收参数
        $appid = DATABASESUFFIX;
        $id = intval($_POST['review_id'] ?? 0);   //帖子id
        $this->init_db();

        $fields = "id, review, nickname, uid, tid, uptime ,status";
        //审核通过  id大于上一页最后一条数据id
        $detail_sql = "select $fields from sns_review_$appid  where id = $id";
        $res = $this->db->query($detail_sql)->row_array();   //获取数组集合

        switch ($res['status']) {
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
                $res['status'] = get_tips(2017);
                break;
        }
        ajax_return(SUCCESS, get_tips(1003), $res);
    }

    /**
     * 评论操作
     */
    public function rev_update()
    {
        $review_id = input('post.review_id');
        $blog_id = input('post.blog_arr');
        $status = intval($_POST['status']);
        $uid = input('post.uid', 'intval');
        $description = sql_format($_POST['description'] ?? '');
        if (!$review_id || !$blog_id) {
            ajax_return(ERROR, get_tips(1006));
        }
        $appid = DATABASESUFFIX;

        // 审核不通过
        if ($status == 2) {
            $num_sql = "SELECT review_num FROM sns_blog_$appid WHERE id = {$blog_id}";
            $num = $this->db->query($num_sql)->row_array();

            if ($num['review_num'] <= 0) {
                $sql = "update sns_blog_$appid set review_num = 0 where id = $blog_id";
            } else {
                $sql = "update sns_blog_$appid set review_num = review_num - 1 where id = $blog_id";
            }

            $data = [
                'blog_id' => $blog_id,
                "review_id" => $review_id,
                "status" => $status,
                "msg" => $description
            ];

            $this->load_model('common_model');
            $this->common_model->send_msg($uid, get_tips(2027));
            $this->common_model->push_notification($uid, get_tips(2027), $description, $data);
        }

        //TODO 评论通过时 +1
        if ($status == 1) {
            $sql = "UPDATE sns_blog_$appid set `review_num` = review_num + 1 WHERE id in ($blog_id)";
        }

        // 增加评论数量
        $this->db->query($sql);
        // 修改评论状态
        $save_status = $this->db->query("UPDATE sns_review_$appid SET `status` = {$status} WHERE id in ($review_id)");

        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($save_status) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }
        ajax_return($result_status, $result_msg);
    }

    /**
     * 处理帖子数据
     */
    private function com_blog($res)
    {
        $aes = new Aes();
        foreach ($res as $key => $value) {
            $res[$key]['pic'] = '';
            /*if (!empty($value['pic'])) {
                $pic_url_arr = explode('|', $value['pic']);
                if (strpos($pic_url_arr[0], '/') !== 0) {
                    $res[$key]['pic'] = $aes->pic_decrypt(TS_SERVER.'/'.$pic_url_arr[0]);
                } else {
                    $res[$key]['pic'] = get_pic_url($pic_url_arr[0]);
                }
            }*/
            switch ($value['status']) {
                case 0:
                    $res[$key]['status_txt'] = get_tips(2014);
                    break;
                case 1:
                    $res[$key]['status_txt'] = get_tips(2015);
                    break;
                case 2:
                    $res[$key]['status_txt'] = get_tips(2016);
                    break;
                case 3:
                    $res[$key]['status_txt'] = get_tips(2017);
                    break;
            }
        }
        return $res;
    }
}
