<script>
import AgoraRTM from "agora-rtm-sdk";
export default {
  data() {
    return {
      rtm_client: null,
      currentImgSrc: ""
    };
  },
  components: {
    Card: () => import("../../components/customer_service/card"),
    List: () => import("../../components/customer_service/list"),
    MessageText: () => import("../../components/customer_service/MessageText"),
    Message: () => import("../../components/customer_service/message")
  },
  mounted() {
    this.$store.commit("INIT_DATA", this);
  }
};
</script>

<template>
  <div id="app">
    <div class="sidebar">
      <card></card>
      <list></list>
    </div>
    <div class="main" v-if="this.$store.state.currentSessionId">
      <message></message>
      <MessageText></MessageText>
      <div class="full-img" v-if="currentImgSrc != ''">
        <img :src="currentImgSrc" v-on:click="currentImgSrc=''"/>
      </div>
    </div>
  </div>
</template>

<style lang="less" scoped>
#app {
  margin: 20px auto;
  width: 800px;
  height: 600px;

  overflow: hidden;
  border-radius: 3px;

  .sidebar,
  .main {
    height: 100%;
  }
  .sidebar {
    float: left;
    width: 200px;
    color: #f4f4f4;
    background-color: #2e3238;
  }
  .main {
    position: relative;
    overflow: hidden;
    background-color: #eee;
  }
  .text {
    position: absolute;
    width: 100%;
    bottom: 0;
    left: 0;
  }
  .message {
    height: ~"calc(100% - 160px)";
  }

    .full-img {
    display: flex;
    position: fixed;
    width: 80%;
    height: 90%;
    left: 10%;
    top: 5%;
    text-align: center;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    z-index: 99;
    img {
      width: auto;
      height: auto;
    }
  }
}
</style>
