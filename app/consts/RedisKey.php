<?php

/**
 * Created by PhpStorm.
 * User: mm
 * Date: 2019/7/26
 * Time: 11:26
 */
class RedisKey
{
    /** ------------------ DB 1 --------------------------  */

    // 手机号注册验证码 %s => area_code ,%s => mobile
    const MOBILE_CODE = "mobile:code:%s:%s";

    // 邮箱注册验证码 %s => email
    const EMAIL_CODE = "email:code:%s";

    //用户的自增ID用redis incr 操作
    const USER_NEW_ID = "user:newid";

    // 用户信息 user:$id
    const USER = "user:%s";

    // 用户usercode对应的用户ID %s => md5(user_code)取前三位字符
    const USER_CODE = "usercode:%s";

    //邮箱注册对应用户ID %s => md5(email)取前三位字符
    const REG_MOBILE = "reg:mobile:%s";

    //手机注册对应用户ID %s => md5(area_code+mobile)取前三位字符
    const REG_EMAIL = "reg:email:%s";

    //vip用户，分值：过期时间
    const USER_VIP = "user:vip";

    //svip用户，分值：过期时间
    const USER_S_VIP = "user:svip";

    //用户上线，最多10层 %s => 用户ID
    const USER_UPPER = "user:upper:%s";

    //用户下线（直属1层）%s => 用户ID
    const USER_LOWER = "user:lower:%s";

    //用户余额已变动(用于同步余额到数据库)
    const USER_BALANCE_UPDATE = "user:balance:update";

    /** ------------------ DB 2 --------------------------  */

    // 在线男女用户的集合 按时间排序 %s 男女值
    const USER_ONLINE = "user:online:%s";

    //男女视频在线的集合 按时间排序  %s 男女值
    const USER_VIDEO_ONLINE = "user:video_online:%s";

    //粉丝 %s => uid
    const USER_FOLLOWER = "user:follower:%s";

    //关注的人 %s => uid
    const USER_FOLLOWING = "user:following:%s";

    //附近人的uid集合 附近的人，分值是距离m
    const USER_NEARBY = "user:nearby:%s";

    //黑名单集合 %s =>uid 值为被uid拉黑的用户ID
    const USER_BLOCK = "user:block:%s";


    /** ------------------ DB 3 --------------------------  */

    //生成通话记录id种子
    const CALL_NEW_ID = "call:newid";

    //通话记录信息
    const CALL = "call:%s";

    //正在通话的记录ID
    const CALL_ONLINE = "call:online";

    //正在通话的用户ID
    const CALL_ONLINE_USER = "call:online:user";

    //用户当天拨出次数，过期时间当晚24:00 男性有通话次数限制
    const CALL_TIMES = "call:times:%s";


    /** ------------------ 直播 --------------------------  */

    //正在直播或正在观看直播中的用户ID
    const LIVE_ONLINE_USER = "live:online:user";

    // 直播间
    const LIVE_ROOM = "live:room";
    const LIVE_ROOM_ROOMID = "live:room:%s";
    const LIVE_ROOM_ROOMID_USER = "live:room:%s:user";
    const LIVE_ROOM_ROOMID_GIFT = "live:room:%s:gift";

    // 直播场次
    const LIVE_ROOM_NEWID = "live:room:newid";

    // 主播信息
    const LIVE_ANCHOR_UID = "live:anchor:%s";

    // 热门主播排行
    const LIVE_ANCHOR_HOT = "live:anchor:hot";

    /** ------------------ 排行榜 --------------------------  */

    // 女神EURC榜
    const RANK_GDS_EURC = "rank:gds:eurc:%s";

    // 女神MSQ榜
    const RANK_GDS_MSQ = "rank:gds:msq:%s";

    // 女神财富榜
    const RANK_GDS_WEALTH = "rank:gds:wealth:%s";

    // 富豪EURC榜
    const RANK_RICH_EURC = "rank:rich:eurc:%s";

    // 富豪MSQ榜
    const RANK_RICH_MSQ = "rank:rich:msq:%s";

    // 富豪财富榜
    const RANK_RICH_WEALTH = "rank:rich:wealth:%s";

    /** ------------------ 公会 --------------------------  */
    //公会信息
    const SOCIATY = "sociaty:%s";

    //公会总收入eurc(1)
    const SOC_EURC_GROSS = "soc:eurc:gross:%s";

    //公会总收入msq
    const SOC_MSQ_GROSS = "soc:msq:gross:%s";

    //平台分成收入eurc(2)
    const SOC_EURC_BOSS = "soc:eurc:boss:%s";

    //平台分成收入msq
    const SOC_MSQ_BOSS = "soc:msq:boss:%s";

    //公会实际收入eurc，减去平台分成(3)
    const SOC_EURC_SUM = "soc:eurc:sum:%s";

    //公会实际收入msq，减去平台分成
    const SOC_MSQ_SUM = "soc:msq:sum:%s";

    //公会主播收入eurc(4)
    const SOC_EURC_ANCHOR = "soc:eurc:anchor:%s";

    //公会主播收入msq
    const SOC_MSQ_ANCHOR = "soc:msq:anchor:%s";

    //公会长收入eurc(5)
    const SOC_EURC_NET = "soc:eurc:net:%s";

    //公会长收入msq
    const SOC_MSQ_NET = "soc:msq:net:%s";

    //公会主播每日净收入明细eurc
    const SOC_EURC_PA = "soc:eurc:pa:%s:%s";

    //公会主播每日净收入明细msq
    const SOC_MSQ_PA = "soc:msq:pa:%s:%s";

    /**------------------------------------------------**/
    //消息记录ID种子
    const PM_NEW_ID = "pm:newid";

    // 首页列表,分值为最新发送时间戳,如果$op_uid为1则是系统通知
    const PM_HOME_LIST = "pm:list:%s";

    // 新消息数量 %s => uid
    const PM_NEW = "pm:new:%s";

    //某个用户的消息列表 %s => 小的uid, %s => 大的uid
    const PM_MSG_LIST = "pm:msg:%s:%s";

    /**
     * 获取私信信息列表的列表key
     * 固定KEY为RedisKey::PM_MSG_LIST:小uid:大uid,发送方和接收方统一为一个key
     * @param $min_uid
     * @param $max_uid
     * @return string
     */
    public static function get_pm_msg_list_key($min_uid, $max_uid)
    {
        if ($min_uid > $max_uid) {
            list($min_uid, $max_uid) = [$max_uid, $min_uid];
        }
        return sprintf(self::PM_MSG_LIST, $min_uid, $max_uid);
    }
}