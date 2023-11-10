<script>
import Vue from "vue";
export default {
  data() {
    return {
      imgC: "",
      user: this.$store.state.user,
      session: null,
      pageLoading: false,
      preScrollHeight: 0,
      currentImgSrc: ""
    };
  },
  filters: {
    // 将日期过滤为 hour:minutes
    time(date) {
      if (typeof date === "string") {
        date = new Date(date);
      }
      let nowDate = new Date().getDate();
      if (nowDate == date.getDate()) {
        return date.getHours() + ":" + date.getMinutes();
      }
      return (
        date.getFullYear() +
        "-" +
        (date.getMonth() + 1) +
        "-" +
        date.getDate() +
        " " +
        date.getHours() +
        ":" +
        date.getMinutes()
      );
    }
  },
  directives: {
    // 发送消息后滚动到底部
    "scroll-bottom"(el, binding, vnode) {
      Vue.nextTick(function() {
        if (!vnode.context.session.scrollToBottom) return false;
        el.scrollTop = el.scrollHeight - el.clientHeight;
        vnode.context.session.scrollToBottom = false;
      });
    }
  },
  computed: {
    currentSessionId() {
      return this.$store.state.currentSessionId;
    }
  },
  methods: {
    renderMessageList() {
      var sessions = this.$store.state.sessions;
      var currentSessionId = this.$store.state.currentSessionId;
      this.session = sessions.find(session => session.id === currentSessionId);
    },
    showImgInfo(url) {
      this.$parent.currentImgSrc = url;
    }
  },
  created() {
    this.renderMessageList();
  },
  mounted() {
    var self = this;
    this.$refs.messageUl.addEventListener(
      "scroll",
      e => {
        if (e.target.scrollTop < 1 && !self.pageLoading) {
          self.pageLoading = true;
          self.$store.commit("GET_PM_DETAIL", {
            the_min_pm_id: self.session.the_min_pm_id,
            type: 1
          });
          self.pageLoading = false;
        }
      },
      false
    );
  },
  beforeUpdate() {
    this.preScrollHeight = this.$refs.messageUl.scrollHeight;
  },
  updated() {
      this.$refs.messageUl.scrollTop = this.$refs.messageUl.scrollHeight - this.preScrollHeight;
  },
  watch: {
    currentSessionId() {
      this.renderMessageList();
    }
  }
};
</script>

<template>
  <div ref="messageUl" class="message" v-scroll-bottom="this.$store.state.scrollToBottom">
    <ul v-if="session">
      <li v-for="item in session.messages" :key="item.id">
        <p class="time">
          <span>{{ item.date | time }}</span>
        </p>
        <div class="main" :class="{ self: item.self }">
          <img
            class="avatar"
            width="30"
            height="30"
            :src="item.self ? user.avatar : (session.user.img || '/static/img/default_avatar.png')"
          />
          <div
            class="text"
            v-if="item.type == 1 || item.type == 2 || item.type == 8"
          >{{ item.content }}</div>
          <div class="img" v-if="item.type == 3">
            <img :src="item.url" v-on:click="showImgInfo(item.url)"/>
          </div>
          <div class="video" v-if="item.type == 5">
            <video width="160" height="190" :src="item.url" autoplay controls="controls"></video>
          </div>
          <div class="gift text" v-if="item.type == 6">[礼物] {{item.gift_name}}</div>
        </div>
      </li>
    </ul>
  </div>
</template>

<style lang="less" scoped>
.message {
  padding: 10px 15px;
  overflow-y: scroll;

  li {
    margin-bottom: 15px;
  }
  .time {
    margin: 7px 0;
    text-align: center;

    > span {
      display: inline-block;
      padding: 0 18px;
      font-size: 12px;
      color: #999;
      border-radius: 2px;
    }
  }
  .img {
    img {
      width: 450px;
      height:250px;
    }
  }
  .avatar {
    float: left;
    margin: 0 10px 0 0;
    border-radius: 3px;
  }
  .text {
    display: inline-block;
    position: relative;
    padding: 0 10px;
    max-width: ~"calc(100% - 40px)";
    min-height: 30px;
    line-height: 2.5;
    font-size: 12px;
    text-align: left;
    word-break: break-all;
    background-color: #fafafa;
    border-radius: 4px;

    &:before {
      content: " ";
      position: absolute;
      top: 9px;
      right: 100%;
      border: 6px solid transparent;
      border-right-color: #fafafa;
    }
  }

  .self {
    text-align: right;

    .avatar {
      float: right;
      margin: 0 0 0 10px;
    }
    .text {
      background-color: #b2e281;

      &:before {
        right: inherit;
        left: 100%;
        border-right-color: transparent;
        border-left-color: #b2e281;
      }
    }
  }
}
</style>