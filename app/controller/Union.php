<?php

/**
 * Created by PhpStorm
 * User: mm
 * Date: 2019/7/24
 * Time: 10:59
 */
class Union extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 啪啪视频列表   啪啪搜索
     */
    public function papa_list()
    {
        //接收参数
        $page_size = 5; //每页显示个数
        $page_no = intval($this->req_data['page_no'] ?? 1);    //页码
        $appid = $this->req_data['appid'];
        $roleid = intval($this->user['id'] ?? 0);   //管理员id
        $this->init_db();

        //seach
        $uid = intval($this->req_data['uid'] ?? 0);  //搜索用户id
        $video_id = sql_format($this->req_data['video_id'] ?? "");  //视频id
        $status = intval($this->req_data['status'] ?? 0);   //用户状态
        $starttime = strtotime($this->req_data['starttime']);
        $endtime = strtotime($this->req_data['starttime']) ?? strtotime(data("Y-m-d h:i"));

        //查询条件
        $where = " 1 = 1";
        if ($uid) {
            $where .= " and uid = $uid";
        }
        if ($video_id) {
            $where .= " and video_id = $video_id";
        }
        if ($status != 99) {
            $where .= " and status = $status";
        }

        $now_id = (($page_no - 1) * $page_size);

        //条件
        $order = " order by id desc";
        $fields = "id, uid, nickname, content, pic, video, uptime, status, likes_num, share_num, review_num";

        //子查询优化
        $now_sql = " and id <= (select id from sns_blog_$appid where $where $order limit $now_id,1)";
        //动态列表
        $sql = "select $fields from papa_$appid  where $where  $now_sql $order limit $page_size";
        $res = $this->db->query($sql)->result_array();

        $res = $this->com_blog($res, $uid, 1);

        ajax_return(SUCCESS, get_tips(1003), $res);
    }


    /**
     * 动态详情
     */
    public function detail()
    {
        //接收参数
        $appid = $this->req_data['appid'] ?? 1;
        $id = intval($this->req_data['id'] ?? 0);   //帖子id
        $this->init_db();

        $fields = 'id, uid, nickname, avatar, gender, age, country, content, pic, video, status, admin_id, admin_time, likes_num, share_num, review_num';
        //审核通过  id大于上一页最后一条数据id
        $detail_sql = "select $fields from papa_$appid  where id = $id";
        $res = $this->db->query($detail_sql)->row_array();   //获取数组集合

        ajax_return(SUCCESS, get_tips(1003), $res);
    }

    /**
     * 帖子操作
     */
    public function update()
    {
        //接收参数
        $blog_id = sql_format($this->req_data['blog_id'] ?? 1);
        $status = intval($this->req_data['status']);    //通过还是不通过
        $appid = $this->req_data['appid'];
        $roleid = intval($this->user['id'] ?? 0);   //后台管理员id

        //执行
        $blog_sql = "update papa_$appid set status = $status where id in ($blog_id)";
        $this->init_db();
        $this->db->query($blog_sql);

        ajax_return(SUCCESS, get_tips(1003));
    }

    /**
     * 导出excel
     */
    public function export_excel()
    { }
}
