import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

//防止点击相同节点报错
const originalPush = Router.prototype.push
Router.prototype.push = function push(location) {
    return originalPush.call(this, location).catch(err => err)
}

export default new Router({
  // mode: 'history',
  base: process.env.BASE_URL,
  routes: [
    {
      path: "/",
      redirect: "/login",
    },
    {
      path: "/main",
      component: () =>
        import(/* webpackChunkName: "main" */ "./views/main.vue"),
      children: [
        {
          // 用户列表
          path: "/userlist",
          component: () =>
            import(/* webpackChunkName: "userlist" */ "./views/user-management/userlist.vue"),
        },
        {
          // 会员列表
          path: "/memberlist",
          component: () =>
            import(/* webpackChunkName: "memberlist" */ "./views/user-management/memberlist.vue"),
        },
        {
          // 头像列表
          path: "/headimglist",
          component: () =>
            import(/* webpackChunkName: "headimglist" */ "./views/user-management/headimglist.vue"),
        },
        {
          // 封禁列表
          path: "/prohibitlist",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/user-management/prohibitlist.vue"),
        },
        {
          // 用户分销列表
          path: "/user_distribution",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/user-management/userDistribution.vue"),
        },
        {
          // 帖子列表
          path: "/invitationlist",
          component: () =>
            import(/* webpackChunkName: "invitationlist" */ "./views/community/invitationlist.vue"),
        },
        {
          // 评论列表
          path: "/commentlist",
          component: () =>
            import(/* webpackChunkName: "commentlist" */ "./views/community/commentlist.vue"),
        },
        {
          // 视频列表
          path: "/papavideolist",
          component: () =>
            import(/* webpackChunkName: "papavideolist" */ "./views/papa/papavideolist.vue"),
        },
        {
          // 视频回复列表
          path: "/papacommentlist",
          component: () =>
            import(/* webpackChunkName: "papacommentlist" */ "./views/papa/papacommentlist.vue"),
        },
        {
          // 直播列表
          path: "/livebroadcastlist",
          component: () =>
            import(/* webpackChunkName: "livebroadcastlist" */ "./views/LiveBroadcast/livebroadcastlist.vue"),
        },
        {
          // 直播分类设置
          path: "/liveSort",
          component: () =>
            import(/* webpackChunkName: "livebroadcastlist" */ "./views/LiveBroadcast/liveSort.vue"),
        },
        {
          // 熱門遊戲直撥設置
          path: "/hotGame",
          component: () =>
            import(/* webpackChunkName: "livebroadcastlist" */ "./views/LiveBroadcast/hotGame.vue"),
        },
        {
          // 直播間中獎设置
          path: "/liveWinning",
          component: () =>
            import(/* webpackChunkName: "livebroadcastlist" */ "./views/LiveBroadcast/liveWinning.vue"),
        },
        {
          // 跑馬燈
          path: "/marquee",
          component: () =>
            import(/* webpackChunkName: "livebroadcastlist" */ "./views/LiveBroadcast/marquee.vue"),
        },
        {
          // 主播稽核
          path: "/anchorlist",
          component: () =>
            import(/* webpackChunkName: "anchorlist" */ "./views/LiveBroadcast/anchorlist.vue"),
        },
        {
            path: "/anchorListAll",
            component: () =>
              import(/* webpackChunkName: "anchorlist" */ "./views/LiveBroadcast/anchorListAll.vue"),   
        },
        {
          // 主播收益列表
          path: "/incomelist",
          component: () =>
            import(/* webpackChunkName: "incomelist" */ "./views/LiveBroadcast/incomelist.vue"),
        },
        {
          // 实名列表
          path: "/realnamelist",
          component: () =>
            import(/* webpackChunkName: "realnamelist" */ "./views/LiveBroadcast/realnamelist.vue"),
        },
        {
          // 封面列表
          path: "/coverlist",
          component: () =>
            import(/* webpackChunkName: "coverlist" */ "./views/LiveBroadcast/coverlist.vue"),
        },
        {
          // banner列表
          path: "/bannerlist",
          component: () =>
            import(/* webpackChunkName: "bannerlist" */ "./views/resources/bannerlist.vue"),
        },
        {
          // 礼物列表
          path: "/giftlist",
          component: () =>
            import(/* webpackChunkName: "giftlist" */ "./views/resources/giftlist.vue"),
        },
        {
          // 广告配置列表
          path: "/AdvertisingConfigurationlist",
          component: () =>
            import(/* webpackChunkName: "AdvertisingConfigurationlist" */ "./views/resources/AdvertisingConfigurationlist.vue"),
        },
        {
          // 广告商列表
          path: "/advertiserlist",
          component: () =>
            import(/* webpackChunkName: "advertiserlist" */ "./views/resources/advertiserlist.vue"),
        },
        {
          // 推送列表
          path: "/pushlist",
          component: () =>
            import(/* webpackChunkName: "pushlist" */ "./views/resources/pushlist.vue"),
        },
        {
          // 定价列表
          path: "/pricelist",
          component: () =>
            import(/* webpackChunkName: "pricelist" */ "./views/resources/pricelist.vue"),
        },
        {
          // 公告列表
          path: "/noticelist",
          component: () =>
            import(/* webpackChunkName: "noticelist" */ "./views/resources/noticelist.vue"),
        },
        {
          // 汇率列表
          path: "/exchangeRate",
          component: () =>
            import(/* webpackChunkName: "paymentMethod" */ "./views/resources/exchangeRate.vue"),
        },
        {
          // 法币配置
          path: "/LegalTenderAllocation",
          component: () =>
            import(/* webpackChunkName: "paymentMethod" */ "./views/resources/LegalTenderAllocation.vue"),
        },
        {
          // 角色列表
          path: "/partlist",
          component: () =>
            import(/* webpackChunkName: "partlist" */ "./views/jurisdiction/partlist.vue"),
        },
        {
          // 管理员列表
          path: "/operatorlist",
          component: () =>
            import(/* webpackChunkName: "operatorlist" */ "./views/jurisdiction/operatorlist.vue"),
        },
        {
          // 版本管理
          path: "/versionmanagement",
          component: () =>
            import(/* webpackChunkName: "versionmanagement" */ "./views/system-collocation/versionmanagement.vue"),
        },
        {
          // vip配置
          path: "/vipcollocation",
          component: () =>
            import(/* webpackChunkName: "vipcollocation" */ "./views/system-collocation/vipcollocation.vue"),
        },
        {
          // 普通充值配置
          path: "/usercollocation",
          component: () =>
            import(/* webpackChunkName: "vipcollocation" */ "./views/system-collocation/usercollocation.vue"),
        },
        {
          // 分销配置
          path: "/distribution",
          component: () =>
            import(/* webpackChunkName: "distribution" */ "./views/system-collocation/distribution.vue"),
        },
        {
          // 应用授权
          path: "/appkeylist",
          component: () =>
            import(/* webpackChunkName: "appkeylist" */ "./views/system-collocation/appkeylist.vue"),
        },
        {
          // VPN管理
          path: "/vpnlist",
          component: () =>
            import(/* webpackChunkName: "vpnlist" */ "./views/system-collocation/vpnlist.vue"),
        },
        {
          // 敏感词管理
          path: "/sensitive",
          component: () =>
            import(/* webpackChunkName: "vpnlist" */ "./views/system-collocation/sensitive.vue"),
        },
        {
          // 私聊等级限制
          path: "/private_msg_restrict",
          component: () =>
            import(/* webpackChunkName: "versionmanagement" */ "./views/system-collocation/private_msg_restrict.vue"),
        },
        {
          // 工会列表
          path: "/LabourUnionlist",
          component: () =>
            import(/* webpackChunkName: "LabourUnionlist" */ "./views/LabourUnion/LabourUnionlist.vue"),
        },
        {
          // 工会主播列表
          path: "/LabourUnionanchorlist",
          component: () =>
            import(/* webpackChunkName: "LabourUnionanchorlist" */ "./views/LabourUnion/LabourUnionanchorlist.vue"),
        },
        {
          // 工会流水列表
          path: "/LabourUnionturnoverlist",
          component: () =>
            import(/* webpackChunkName: "LabourUnionturnoverlist" */ "./views/LabourUnion/LabourUnionturnoverlist.vue"),
        },
        {
          // 虚拟币充值订单
          path: "/rechargelist",
          component: () =>
            import(/* webpackChunkName: "rechargelist" */ "./views/money/rechargelist.vue"),
        },
        {
          // 虚拟币提币订单
          path: "/reflectlist",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/reflectlist.vue"),
        },
        {
          // 購買baby幣订单
          path: "/user_baby_list",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/user_baby_list.vue"),
        },
        {
          // 充币订单
          path: "/user_deposit_baby",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/user_deposit_baby.vue"),
        },
        {
          // 配置充值通道
          path: "/switch_payment_channel",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/switch_payment_channel.vue"),
        },
        {
          // 配置公司银行卡充值通道
          path: "/bank_config",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/bank_config.vue"),
        },
        {
          // 线下银行充值记录
          path: "/bank_recharge_list",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/bank_recharge_list.vue"),
        },
        {
          // 提现到银行卡记录
          path: "/withdrawal",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/withdrawal.vue"),
        },
        {
          // 提现下发
          path: "/remit_money",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/remit_money.vue"),
        },
        {
          // 提现下发记录
          path: "/remit_money_record",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/remit_money_record.vue"),
        },
        {
          // baby金额扣钱、派奖（自动、手动）记录
          path: "/babytrans",
          component: () =>
            import(/* webpackChunkName: "reflectlist" */ "./views/money/babytrans.vue"),
        },
        {
          // 卖币订单
          path: "/sellbilllist",
          component: () =>
            import(/* webpackChunkName: "sellbilllist" */ "./views/money/sellbilllist.vue"),
        },
        {
          // C2C
          path: "/C2Clist",
          component: () =>
            import(/* webpackChunkName: "C2Clist" */ "./views/money/C2Clist.vue"),
        },
        {
          // C2B
          path: "/C2Blist",
          component: () =>
            import(/* webpackChunkName: "C2Blist" */ "./views/money/C2Blist.vue"),
        },
        {
          // 收款方式
          path: "/paymentMethod",
          component: () =>
            import(/* webpackChunkName: "paymentMethod" */ "./views/money/paymentMethod.vue"),
        },
        {
          // 客服
          path: "/customer_service",
          component: () =>
            import(/* webpackChunkName: "customer_service" */ "./views/customer_service/main.vue"),
        },
        {
          // 客服-消息管理-对接详情
          path: "/customer_service/users",
          component: () =>
            import(/* webpackChunkName: "customer_service" */ "./views/customer_service/users.vue"),
        },
        {
          // 渠道管理
          path: "/channel",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/system-collocation/channel.vue"),
        },
        {
          // 渠道数据统计
          path: "/channel_total",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/statis/channel_total.vue"),
        },
        {
          // 渠道数据用户
          path: "/channel_day",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/statis/channel_day.vue"),
        },
        {
          // 渠道数据明細
          path: "/channel_user",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/statis/channel_user.vue"),
        },
        {
          //合作商清单
          path: "/partner",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/partner/partner.vue"),
        },
        {
          //合作商数据汇总
          path: "/partner_total",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/partner/partner_total.vue"),
        },
        {
          //合作商日数据
          path: "/partner_day",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/partner/partner_day.vue"),
        },
        {
          //
          path: "/partner_user",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/partner/partner_user.vue"),
        },
        {
          //合作商结算记录
          path: "/partner_settle",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/partner/partner_settle.vue"),
        },
        {
          //子合作商清单
          path: "/sub_partner",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/partner/sub_partner.vue"),
        },
        {
          path: "/advert",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/advert/advert.vue"),
        },
        {
          path: "/advert_total",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/advert/advert_total.vue"),
        },
        {
          path: "/advert_day",
          component: () =>
            import(/* webpackChunkName: "statis" */ "./views/advert/advert_day.vue"),
        },
        {
          path: "/sales",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/sales.vue"),
        },
        {
          path: "/salesman",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/salesman.vue"),
        },
        {
          path: "/sales_statis",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/statis.vue"),
        },
        {
          path: "/sales_statis_sm",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/statis_sm.vue"),
        },
        {
          path: "/sales_statis_user",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/statis_user.vue"),
        },
        {
          path: "/sales_statis_pay",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/statis_pay.vue"),
        },
        {
          path: "/sales_statis_expend",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/sales/statis_expend.vue"),
        },
        {
          path: "/merchant",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views//merchant//merchant.vue"),
        },
        {
          path: "/wholesale",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views//merchant//wholesale.vue"),
        },
        {
          path: "/mer_transfer",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views//merchant//transfer.vue"),
        },
        {
          path: "/stat_user",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/stat/stat_user.vue"),
        },
        {
          path: "/stat_summary",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/stat/stat_summary.vue"),
        },
        {
          path: "/stat_summary_detail",
          component: () =>
            import(/* webpackChunkName: "sales" */ "./views/stat/stat_summary_detail.vue"),
        },
        {
          // 用户數據統計
          path: "/user_total",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/stat/usertotal.vue"),
        },

        {
          // 电台约会部分
          path: "/type_manage",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/radio/type_manage.vue"),
        },
        {
          path: "/radio_manage",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/radio/radio_manage.vue"),
        },
        {
          path: "/activity_manage",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/radio/activity_manage.vue"),
        },
        {
          path: "/city_list_manage",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/radio/city_list_manage.vue"),
        },
        {
          path: "/radio_leave_message_manage",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/radio/radio_leave_message_manage.vue"),
        },
        {
          path: "/radio_info",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/radio/radio_info.vue"),
        },
        //一对一
        {
          path: "/chat_list",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/chat/chatList.vue"),
        },
        {
          path: "/chat_money_list",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/chat/chatMoneyList.vue"),
        },
        {
          path: "/chat_detail_list",
          component: () =>
            import(/* webpackChunkName: "prohibitlist" */ "./views/chat/chatDetailList.vue"),
        },

        //消息推送后台设置
        {
          path: '/send_mess_set',
          component: () => import( /* webpackChunkName: "prohibitlist" */ "./views/message/send_mess_set.vue"),
        },
        {
          path: '/send_mess_list',
          component: () => import( /* webpackChunkName: "prohibitlist" */ "./views/message/send_mess_list.vue"),
        },

      ],
    },
    //登录页
    {
      path: "/login",
      name: "login",
      component: () =>
        import(/* webpackChunkName: "login" */ "./views/login.vue"),
    },
    //错误页
    {
      path: "/error",
      name: "error",
      component: () =>
        import(/* webpackChunkName: "error" */ "./views/error.vue"),
    },
    {
      path: "*",
      redirect: "/error",
    },
  ],
});
