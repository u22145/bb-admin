<?php
/**
 * Created by PhpStorm
 * User: mm
 * Date: 2019/7/24
 * Time: 10:59
 */
class Index extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 默认首页
     */
    public function index()
    {
        redirect("/home");
    }

    /**
     *  播放m3u8
     */
    public function ts_play()
    {
        $id = $_GET['id'];
        $url = "http://share.playbabies.net/h5/play_m3u8_g9s7k3v5p1/papa/$id";
        header('Location:'.$url);
        exit;
    }

    /**
     * @param $type 'share | api'
     *
     * @return $server_address
     */
    public function get_server_url()
    {
        $type   = $_POST['type'] ?? 'share';

        switch (strtolower($type)) {
            case 'share':
                $url = SHARE_SERVER_URL;
                break;
            case 'api':
                $url = API_SERVER_URL;
                break;
            default:
                $url = SHARE_SERVER_URL;
                break;
        }

        ajax_return(SUCCESS, '', ['url' => $url]);
    }

    public function get_java_server_url()
    {
        $url = JAVA_ADMIN_SERVER_URL;
        ajax_return(SUCCESS, '', ['url' => $url]);
    }
}
