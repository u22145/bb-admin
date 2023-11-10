<?php

/**
 * Created by PhpStorm
 * User: mm
 * Date: 2019/7/24
 * Time: 10:59
 */
class Papa extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 啪啪视频列表
     */
    public function papa_list()
    {
        $appid = DATABASESUFFIX;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE);; //每页显示个
        $page = intval($_POST['page_no'] ?? 1);    //页码
        $uid = intval($_POST['uid'] ?? 0);  //搜索用户id
        $video_id = sql_format($_POST['video_id'] ?? "");  //视频id
        $nickname =  sql_format($_POST['nickname'] ?? "");
        $title = sql_format($_POST['title']);   //用户标题
        $type = $_POST['type'] ?: 1;
        $starttime = $_POST['starttime'] ?? '';
        $endtime = $_POST['endtime'] ?? '';
        $status = $_POST['status'];
        $fee_status = $_POST['fee_status'];
        $status = $status == "" ? 99 : intval($status);
        $fee_status = $fee_status == "" ? 99 : intval($fee_status);
        $this->init_db();

        //查询条件
        $where = " 1 = 1";
        if ($uid) {
            $where .= " and uid = $uid";
        }

        if ($video_id) {
            $where .= " and id = $video_id";
        }

        if ($status < 99) {
            $where .= " and status = $status";
        }

        if ($fee_status == 1) {
            $where .= " and fee > 0";
        } else if ($status == 2) {
            $where .= " and fee = 0";
        }

        if ($title) {
            $where .= " and title like '%$title%'";
        }

        if ($nickname) {
            $sql = "select id from user_{$appid}_0 where nickname = '$nickname'";
            $res = $this->db->query($sql)->row_array();
            $where .= " and uid = {$res['id']}";
        }

        if ($starttime && $endtime) {
            $where .= " and uptime between '$starttime' and '$endtime'";
        }

        $now_id = (($page - 1) * $page_size);
        //条件
        $order = " order by top desc, id desc";
        $fields = "id, uid, title, ts, pic_url, video_url, likes_num, share_num, review_num, status, uptime, if(fee > 0, fee, '0') fee, buy_num, top";

        //动态列表
        $sql = "select $fields from papa_$appid where $where $order limit $now_id,$page_size";
        $res = $this->db->query($sql)->result_array();
        $res = $this->com_blog($res);

        //导出
        if ($type == 2) {
            $header = array(
                get_tips(5016),
                get_tips(2002),
                get_tips(5017),
                get_tips(5018),
                get_tips(5019),
                get_tips(5020),
                get_tips(5021),
                get_tips(5022),
                get_tips(5023),
                get_tips(1001),
                get_tips(2003),
            );
            $path = export(get_tips(5024), $header, $res);
            ajax_return(SUCCESS, get_tips(1002), $path);
            exit;
        }
        //总条数
        $sql = "select count(id) num from papa_" . DATABASESUFFIX . " where $where";
        $total_count_res = $this->db->query($sql)->row_array();
        $page_count = $total_count_res['num'] / $page_size;
        if (is_float($page_count)) {
            $page_count = floor($page_count) + 1;
        }

        // 返回数据
        $data = array(
            'list' => $res,
            'page' => $page,                           // 第几页
            'page_size' => $page_size,                 // 每页显示几条
            'page_count' => $page_count,               // 有几页
            'total' => intval($total_count_res['num']) // 总条数
        );

        ajax_return(SUCCESS, get_tips(1007), $data);
    }

    /**
     * 啪啪详情（停用）
     */
    public function detail()
    {
        //接收参数
        $appid = DATABASESUFFIX;
        $id = intval($_POST['id'] ?? 0);   //帖子id
        $this->init_db();

        $fields = 'id, uid, nickname, content, pic, video, status, admin_id, admin_time, likes_num, share_num, review_num';
        //审核通过  id大于上一页最后一条数据id
        $detail_sql = "select $fields from papa_$appid  where id = $id";
        $res = $this->db->query($detail_sql)->row_array();   //获取数组集合

        ajax_return(SUCCESS, '', $res);
    }

    /**
     * papa操作
     */
    public function update()
    {
        //接收参数
        $papa_id = intval($_POST['papa_id'] ?? 1);
        $status = intval($_POST['status'] ?? 0);    //通过还是不通过
        $appid = DATABASESUFFIX;
        $description = sql_format($_POST['description']);

        if (empty($status) || empty($papa_id)) {
            ajax_return(ERROR, get_tips(1006));
        }

        //执行
        $blog_sql = "update papa_$appid set status = $status,description = '$description' where id = $papa_id";
        $this->init_db();
        $this->db->query($blog_sql);

        //TODO 重复审核 通过
        if ($status == 1) {
            $sql = "select uid,status from papa_$appid where id = $papa_id";
            $res = $this->db->query($sql)->row_array();
            // if ($res['status'] > 0) {    //第二次审核
            //     $this->redis->hIncrBy("user:{$res['uid']}", "blog_num", 1);
            // }
        }

        //TODO 审核不通过  -1 发推送
        if ($status == 2) {
            $sql = "select uid,status from papa_$appid where id = $papa_id";
            $res = $this->db->query($sql)->row_array();
            $this->redis->hIncrBy("user:{$res['uid']}", "blog_num", -1);
            //TODO 审核不通过写入用户消息队列
            $this->load_model('common_model');
            $data = [
                "papa_id" => $papa_id,
                "status" => "$status",
                "msg" => $description
            ];
            $this->common_model->send_msg($res['uid'], get_tips(5025));
            $this->common_model->push_notification($res['uid'], get_tips(5025), $description, $data);
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     *   papa 視頻置頂
     */
    public function edit_top()
    {
        $appid = DATABASESUFFIX;
        $type = intval($_POST['type'] ?? 0);
        $papa_id = intval($_POST['papa_id'] ?? 0);
        $papa_id || ajax_return(ERROR, 'papa視頻id不能為空!');

        switch ($type) {
            case '1':
                $top = time();
                $sql = "update papa_$appid set top = $top where id = $papa_id";
                break;
            case '2':
                $sql = "update papa_$appid set top = 0 where id = $papa_id";
                break;
        }
        $this->db->query($sql);
        ajax_return(SUCCESS, '操作成功');
    }

    /**
     * 评论操作
     */
    public function rev_update()
    {
        $appid = DATABASESUFFIX;
        $review_id = input('post.review_id');
        $papa_id = input('post.papa_arr');
        $uid = input('post.uid', 'intval');
        $status = intval($_POST['status']);
        $description = sql_format($_POST['description'] ?? '');

        if (!$review_id && !$papa_id && !$uid) {
            ajax_return(ERROR, get_tips(1006));
        }
        if ($status == 2 && empty($description)) {
            ajax_return(ERROR, get_tips(5026));
        }
        $this->init_db();

        //TODO  评论不通过  -1
        if ($status == 2) {
            $sql = "update papa_$appid set review_num = review_num - 1 where id = $papa_id";
            $this->db->query($sql);

            $data = [
                'papa_id' => $papa_id,
                "review_id" => $review_id,
                "status" => $status,
                "msg" => $description
            ];

            $this->load_model('common_model');
            $this->common_model->send_msg($uid, get_tips(2027));
            $this->common_model->push_notification($uid, get_tips(2027), $description, $data);
        }

        //TODO 评论通过  +1
        if ($status == 1) {
            $sql = "update papa_$appid set review_num = review_num + 1 where id in ($papa_id)";
            $this->db->query($sql);
        }

        //TODO 修改评论状态
        $sql = "update sns_review_$appid set status = $status,description = '$description' where id in ($review_id)";
        $this->db->query($sql);

        ajax_return(SUCCESS, get_tips(1005));
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

        $fields = "id, review, nickname, uid, tid, uptime ,status, description";
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
                $res['status'] = get_tips(5027);
                break;
        }
        ajax_return(SUCCESS, '', $res);
    }


    /**
     * @param $res   array   动态结果集
     * @param $uid   int     用户id (用户在线检查是否动态点赞)
     * @param $type  int     1 动态，2 啪啪，3 评论
     * 动态列表公共处理函数
     */
    private function com_blog($res)
    {
        foreach ($res as $key => $value) {
            if ($value['status'] == 0) {
                $res[$key]['status_txt'] = get_tips(2025);
            }
            if ($value['status'] == 1) {
                $res[$key]['status_txt'] = get_tips(2015);
            }
            if ($value['status'] == 2) {
                $res[$key]['status_txt'] = get_tips(2016);
            }
            if ($value['status'] == 3) {
                $res[$key]['status_txt'] = get_tips(5027);
            }
            $res[$key]['nickname'] = $this->redis->hGet("user:{$value['uid']}", "nickname") ?: "用户名未定义";
            //动态图片
            $res[$key]['pic_url'] = get_pic_url($value['pic_url']);
            //视频url
            $res[$key]['video_url'] = get_pic_url($res[$key]['video_url']);
        }
        return $res;
    }
}
