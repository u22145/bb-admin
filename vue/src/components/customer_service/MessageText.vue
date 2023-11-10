<script>
export default {
  data() {
    return {
      content: "",
      showEmoji: false,
      emojiList: [],
      formData: {}
    };
  },
  methods: {
    onKeyup(e) {
      if (e.keyCode === 13 && this.content.length) {
        this.$store.commit("SEND_MESSAGE", this.content);
        this.content = "";
        this.setSessionScrollToBottom();
      }
    },
    uploadChange(e) {
      console.log("upload change");
    },
    uploadSuccess(e) {
      console.log("uploadSuccess", e);
      let session = this.setSessionScrollToBottom();
      this.$store.sendPmSuccess(this.$store, session, e);
    },
    uploadError(e) {
      console.log("uploadError", e);
    },
    // 更新会话窗口自动滚动到最新的消息记录位置
    setSessionScrollToBottom() {
      let sessions = this.$store.state.sessions;
      let currentSessionId = this.$store.state.currentSessionId;
      let session = sessions.find(item => item.id == currentSessionId);
      session.scrollToBottom = true;
      return session;
    },
    // 显示表情列表
    showEmojiList() {
      var self = this;
      this.showEmoji = !this.showEmoji;
      if (this.emojiList.length > 0) return;
      this.$http
        .post("/cs/emoji", { app_usercode: this.$store.state.user.usercode })
        .then(res => {
          self.emojiList = res.data.emoji || [];
        });
    },
    
    // 添加表情到文本中
    addEmojiToContent(emoji) {
      console.log(emoji);
      this.content += emoji;
    },
  },
  updated() {
    let emojisDom = this.$refs.emojis;
    if (emojisDom) {
      this.$refs.emojis.style.top = -emojisDom.offsetHeight + "px";
    }
  },
  mounted() {
    this.formData = {
      type: 3,
      usercode: window.localStorage.getItem("usercode"),
      app_usercode: this.$store.state.user.usercode,
      op_uid: this.$store.state.currentSessionId
    };
  }
};
</script>

<template>
  <div class="text">
    <textarea placeholder="按 Ctrl + Enter 发送" v-model="content" @keyup="onKeyup" @focus="showEmoji=false"></textarea>
    <div class="ops">
      <img
        class="emoji"
        src="/static/img/emoji.png"
        width="30"
        height="30"
        v-on:click="showEmojiList"
      />
      <el-upload
        action="/cs/send_pm"
        :data="formData"
        accept=".jpg, .jpeg, .png, .gif, .bmp, .JPG, .JPEG, .PBG, .GIF, .BMP"
        list-type="picture"
        :on-success="uploadSuccess"
        :on-error="uploadError"
        :on-change="uploadChange"
        :show-file-list="false"
      >
        <img class="wenjianjia" src="/static/img/wenjianjia.png" width="30" height="30" />
      </el-upload>
    </div>
    <div ref="emojis" class="emojis" v-if="showEmoji">
      <span v-for="(emoji, index) in emojiList" :key="index" v-on:click="addEmojiToContent(emoji)">{{emoji}}</span>
    </div>
  </div>
</template>

<style lang="less" scoped>
.text {
  height: 160px;
  border-top: solid 1px #ddd;

  textarea {
    padding: 10px;
    height: 100%;
    width: 100%;
    border: none;
    outline: none;
    font-family: "Micrsofot Yahei";
    resize: none;
  }

  .ops {
    display: flex;
    justify-content: space-between;
    width: 100px;
    position: absolute;
    top: 15px;
    right: 30px;
    .wenjianjia,
    .emoji {
      cursor: pointer;
    }
  }

  .emojis {
    position: absolute;
    top: -102px;
    right: 0;
    font-size: 30px;
    background-color: #eee;
    border: 1px solid #ccc;
    padding: 5px;
    -webkit-user-select: none;
    span {
      padding: 5px;
      cursor: pointer;
    }
  }
}
</style>