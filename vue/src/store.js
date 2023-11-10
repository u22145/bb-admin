/**
 * Vuex
 * http://vuex.vuejs.org/zh-cn/intro.html
 */
import Vue from 'vue';
import Vuex from 'vuex';
import AgoraRTM from "agora-rtm-sdk";

Vue.use(Vuex);

// 退出登录
const logout = (target) => {
  // 清除登录产生的数据
  window.localStorage.removeItem("usercode");
  window.localStorage.removeItem("username");
  window.localStorage.removeItem("rtm_user");
  // 跳转到登录页
  target.$router.push("/login");
}

// 发送消息成功后续处理
const sendPmSuccess = (store, session, res) => {
  let self = store;
  if (res.status == 2) {
    logout(self.target);
    return false;
  }
  if (res.status == 0) {
    self.target.$message.error(res.msg || "请求失败");
    return false;
  }

  var fromUser = self.state.user;
  var toUser = {
    id: session.id,
    nickname: session.user.name,
    avatar: session.user.img
  }

  // 发送给声网的数据
  var contentObj = {
    pm_id: res.data.pm_id,
    from: {
      uid: fromUser.id,
      nickname: fromUser.nickname,
      avatar: fromUser.avatar
    },
    to: {
      uid: toUser.id,
      nickname: toUser.nickname,
      avatar: toUser.avatar
    },
    msg_content: {},
    type: res.data.type,
    times: "1秒前",
    send_time: res.data.time || 158956236
  }
  // 放入本地消息列表的数据
  let localMessageData = {
    type: res.data.type,
    date: new Date(),
    self: true
  }; 
  if (localMessageData.type == 3) {
    contentObj.msg_content.url =  res.data.msg_content.url;
    localMessageData.url = res.data.msg_content.url;
  } else {
    contentObj.msg_content.text =  res.data.msg_content;
    localMessageData.content = res.data.msg_content;
  }
  session.messages.push(localMessageData);

  // 声网SDK发送消息给对方
  self.rtmClient.sendMessageToPeer(
    { text: JSON.stringify(contentObj) }, // 一个 RtmMessage 对象。
    toUser.id.toString(), // 对端用户的 uid
  ).then(sendResult => {
    console.log("发送结果", sendResult);
    if (sendResult.hasPeerReceived) {
      // 你的代码：远端用户收到消息事件。
    } else {
      // 你的代码：服务器已收到消息，对端未收到消息。
    }
  }).catch(error => {
    // 你的代码：点对点消息发送失败。
    self.target.$message.error("点对点消息发送失败。");
    console.log("你的代码：点对点消息发送失败。", error)
  });
}

// 获取与我聊天过的用户列表
const getChatUserList = (self, page) => {
  page = !page ? 1 : page;
  if (page == 1) {
    self.state.sessions = [];
  }
  self.target.$http.post("cs/pm_index", {
    page_no: page,
    app_usercode: self.state.user.usercode
  }).then((res) => {
    if (res.status == 2) {
      logout(self.target);
      return false;
    }
    if (res.status == 0) {
      self.target.$message.error(res.msg || "请求失败");
      return false;
    }
    if (res.status == 1) {
      let pm_list = res.data.pm_list || [];
      pm_list.forEach((element, index) => {
        if (element.uid == -1) return true;
        // 指定当前选定的用户是列表中的第一个用户(可能需要排除uid = -1 的情况，所以index <= 1, 即第一个或者第二个)
        // if (index <= 1) self.state.currentSessionId = element.uid;

        self.state.sessions.push({
          id: element.uid,
          user: {
            name: element.nickname,
            img: element.avatar,
            msg: element.msg || "",
            times: element.times,
            newNum: element.new_num,
          },
          last_pm_id: element.last_pm_id,
          messages: []
        });
      });
    }
  });
}

const store = new Vuex.Store({
  state: {
    /**
     * 当前用户
     * 结构：
     * {
        name: 'coffce',
        img: 'dist/images/1.jpg'
        } 
     * */
    user: {},

    /**
     * 会话列表
     * 结构:
     * {
            id: 1,
            user: {
                name: '示例介绍',
                img: 'dist/images/2.png',
                online: false,// 在线状态
                msg: "最新的一条消息",
                times: XX秒前
                new_num: 新记录条数
            },
            isLoadingData: false,
            last_pm_id:0,//最新的一个私信ID，排序使用
            the_min_pm_id: // 分页中最小的私信ID
            messages: [
                {
                    type: 2,
                    content: 'Hello，这是一个基于Vue + Vuex + Webpack构建的简单chat示例，聊天记录保存在localStorge, 有什么问题可以通过Github Issue问我。',
                    url:图片或者音视频的URL地址
                    thumb:缩略图
                    pic:视频封面图片
                    date: 时间
                }, {
                    content: '项目地址: https://github.com/coffcer/vue-chat',
                    date: now
                }
            ]
        }, 
     * */
    sessions: [],

    // 当前选中的会话
    currentSessionId: 0,

    // 过滤出只包含这个key的会话
    filterKey: '',

    // 分页size设置
    adminPageSize: 200
  },
  mutations: {
    // 初始化数据
    INIT_DATA(state, target) {
      var self = this;
      this.target = target;
      this.httpClient = target.$http;

      let rtm_user = localStorage.getItem("rtm_user");
      if (!rtm_user) {
        target.$message.error("rtm登录信息不存在，请退出重新登录系统");
        return false;
      }
      state.user = JSON.parse(rtm_user);
      console.log("当前登录用户信息:", state.user);

      // 获取私信消息列表
      getChatUserList(this);

      // 获取 APP ID
      this.httpClient.post("cs/get_app_id", { uid: state.user.id }).then((res) => {
        if (res.status == 2) {
          logout(self.target);
          return false;
        }
        if (res.status == 0) {
          self.target.$message.error(res.msg || "请求失败");
          return false;
        }
        if (res.status == 1) {
          // 登录
          self.rtmClient = AgoraRTM.createInstance(res.data.app_id);
          self.rtmClient.login({
            token: res.data.rtc_token,
            uid: state.user.id
          });

          // 通知 SDK 与 Agora RTM 系统的连接状态发生了改变            
          self.rtmClient.on("ConnectionStateChanged", function (newState, reason) {
            console.log("ConnectionStateChanged", newState, reason);
            if (newState == 'CONNECTED') {
              // 定时检测用户RTM是否在线
              setInterval(function () {
                let sessionUserIds = [];
                state.sessions.forEach((item) => {
                  sessionUserIds.push(item.id.toString());
                })
                console.log("检测的人数:" + sessionUserIds.length);
                if (sessionUserIds.length > 0) {
                  self.rtmClient.queryPeersOnlineStatus(sessionUserIds).then((list) => {
                    state.sessions.forEach((item) => {
                      item.user.online = list[item.id] || false;
                    })
                  });
                }
              }, 1000 * 60);
            }
          });

          // 收到来自对端的点对点消息
          self.rtmClient.on("MessageFromPeer", function (
            message,
            peerId,
            messageProps
          ) {
            console.log("收到来自对端的点对点消息", message, peerId, messageProps);
            let contentObj = JSON.parse(message.text);
            let session = state.sessions.find(item => item.id == peerId);
            // 如果收到的是一个未曾有个的会话消息，则生成并添加一个新的会话到列表
            if (!session) {
              console.log("新用户发来的消息", contentObj);
              let _from = contentObj.from;
              session = {
                id: _from.uid,
                user: {
                  name: _from.nickname,
                  img: _from.avatar,
                  msg: _from.msg || "",
                  times: contentObj.times,
                  newNum: 0,
                },
                messages: []
              }
              state.sessions.unshift(session);
            }

            // 若有pm_id，更新last_pm_id，用于更新排序
            if (contentObj.pm_id) session.last_pm_id = contentObj.pm_id;
            // 私信的用户列表，按最新的放到前面排序
            state.sessions.sort(function (a, b) {
              return a.last_pm_id > b.last_pm_id ? -1 : 1;
            });

            // 非当前聊天的客户发来消息时，新消息数量自增1,当前的则自动滚动到底部
            if (peerId != state.currentSessionId) {
              session.user.newNum++;
            } else {
              session.scrollToBottom = true;
            }

            // push到数组
            session.messages.push({
              type: contentObj.type,
              content: contentObj.msg_content.text,
              url: contentObj.msg_content.url,
              thumb: contentObj.msg_content.thumb,
              pic: contentObj.msg_content.pic,
              // 礼物数据
              gift_name: contentObj.msg_content.gift_name || "",
              gift_num: contentObj.msg_content.gift_num || "",
              gift_thumb: contentObj.msg_content.gift_thumb || "",
              gift_url: contentObj.msg_content.gift_url || "",
              date: new Date()
            });
          });

          // 该回调在 Token 过期时触发。收到该回调时，请尽快在你的业务服务端生成新的 Token 并调用 renewToken 方法把新的 Token 传给 Token 验证服务器。
          self.rtmClient.on("TokenExpired", function () {
            console.log("该回调在 Token 过期时触发。收到该回调时，请尽快在你的业务服务端生成新的 Token 并调用 renewToken 方法把新的 Token 传给 Token 验证服务器。");
          });
        }
      });
    },

    // 发送消息
    SEND_MESSAGE({ sessions, currentSessionId }, content) {
      let session = sessions.find(item => item.id === currentSessionId);
      if (!session) {
        this.target.$message.error("会话不存在");
        return false;
      }

      var self = this;
      // 私信消息接口
      this.httpClient.post("cs/send_pm", {
        app_usercode: self.state.user.usercode,
        op_uid: currentSessionId,
        pm: content,
        // 普通的文本消息
        type: 2,
      }).then((res) => {
        sendPmSuccess(self, session, res);
      });
    },

    // 获取私信详情
    GET_PM_DETAIL({ sessions, currentSessionId }, params) {
      var self = this;
      if (!params) params = {};
      let is_first_page = (params.the_min_pm_id || 0) === 0;
      let type = params.type || 0;
      let session = sessions.find(item => item.id == currentSessionId);
      if (!session) {
        return false;
      }

      if (is_first_page) {
        // 新消息数量置为0
        session.user.newNum = 0;
        // 清空原有的消息
        session.messages = [];
      }
      if (session.isLoadingData) {
        return false;
      }

      session.isLoadingData = true;
      this.target.$http.post("cs/pm_detail", {
        app_usercode: self.state.user.usercode,
        op_uid: currentSessionId,
        pm_id: params.the_min_pm_id,
        type: type //类型：0 读新私信，1 取历史记录
      }).then(res => {
        session.isLoadingData = false;
        if (res.status == 2) {
          self.logout(self.target);
          return false;
        }
        if (res.status == 0) {
          self.target.$message.error(res.msg || "请求失败");
          return false;
        }
        let msgList = res.data || [];
        if (msgList.length === 0) {
          self.target.$message.success("没有更多数据了");
          return false;
        }

        // 把当前消息列表中最小的pm_id 给 the_min_pm_id
        session.the_min_pm_id = msgList[0].pm_id;

        // 非第一页，倒序数组
        if (!is_first_page) msgList.reverse();

        // 循环处理数据
        msgList.forEach((element, k) => {
          let messageItem = {
            // 私信类型
            type: element.type,
            // 文字内容
            content: element.msg_content.text,
            // 图片或者音视频的URL地址
            url: element.msg_content.url,
            // 图片封面图
            thumb: element.msg_content.thumb,
            // 视频封面图
            pic: element.msg_content.pic,
            // 礼物数据
            gift_name: element.msg_content.gift_name || "",
            gift_num: element.msg_content.gift_num || "",
            gift_thumb: element.msg_content.gift_thumb || "",
            gift_url: element.msg_content.gift_url || "",

            //时间和 是否为自己的标识
            date: new Date(element.send_time * 1000),
            self: element.from.uid == self.state.user.id
          };
          if (is_first_page) {
            session.messages.push(messageItem);
          } else {
            session.messages.unshift(messageItem);
          }
        });

        // 消息赋值完成后，若请求的是第一页数据，则滚动到最底部
        if (is_first_page) {
          session.scrollToBottom = true;
        }
      });
    },

    // 选择会话
    SELECT_SESSION(state, id) {
      console.log("当前选择的ID：", id);
      state.currentSessionId = id;
    },

    // 搜索
    SET_FILTER_KEY(state, value) {
      console.log(state, value);
      state.filterKey = value;
    },
  }
});

store.watch(
  (state) => state.sessions,
  (val) => {
    // console.log('CHANGE: ', val);
  },
  {
    deep: true
  }
);

store.logout = logout;
store.sendPmSuccess = sendPmSuccess;
store.getChatUserList = getChatUserList;

export default store;
