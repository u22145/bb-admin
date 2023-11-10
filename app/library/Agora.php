<?php
require "Agora/AccessToken.php";
require "Agora/RtmTokenBuilder.php";
require "Agora/RtcTokenBuilder.php";

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/8/6
 * Time: 9:51
 */
class Agora
{
    /**
     * 生成 RTM Token时，需要传入下列参数：
     * appID：项目的 App ID，详见 获取 App ID。
     * uId：申请登录 Agora RTM 系统的用户 ID。
     *
     * 设置 RTM Client 角色时，需要传入下列参数：
     * privilege ：RTM Client 暂时只支持一种角色，将该值设为 1000。
     * expireTimeStamp：该功能目前仍在开发中，将该值设为 0。
     *
     * Server 端收到请求后 Token Generator 会生成一个 RTM Token，然后将生成的 RTM Token 发送给 Client 端。
     * 出于安全考虑，请在 Token 生成后 24 小时内登录 Agora RTM 系统。超过 24 小时则需要重新生成 Token。
     * @param $uid
     * @return string
     */
    public function get_rtm_token($uid = 0) {
        global $config;
        $agora_conf = $config['agora'];
        $appID = $agora_conf['app_id'];
        $appCertificate = $agora_conf['cert'];
        $expiredTs = time() + 86400;
        
        return RtmTokenBuilder::buildToken($appID, $appCertificate, (string)$uid, "", $expiredTs);
    }

    /**
     * 生成 RTC token
     * 一对一音视频聊天 和 直播时 使用
     * 直播时需要确认调用方是主播还是观众
     * @param $uid
     * @param $room_id
     * @param $type string anchor 主播， audience 观众
     * @return string
     */
    public function get_rtc_token($uid, $room_id, $type) {
        global $config;
        $agora_conf = $config['agora'];
        $appID = $agora_conf['app_id'];
        $appCertificate = $agora_conf['cert'];
        $expiredTs = time() + 86400;

        //角色默认是直播模式下的观众，若用户在直播中，则角色变更为直播模式下的主播
        $role = RtcTokenBuilder::RoleSubscriber;
        if ($type == "anchor") {
            $role = RtcTokenBuilder::RolePublisher;
        }

        return RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, (string)$room_id, $uid, $role, $expiredTs);
    }
}