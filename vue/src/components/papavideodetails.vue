<template>
  <div class="papavideodetails">
    <!-- 啪啪视频详情页 -->
    <el-dialog
      :visible.sync="status"
      :fullscreen="true"
      :modal-append-to-body="true"
      :append-to-body="true"
      lock-scroll
      :show-close="false"
      :close-on-press-escape="false"
      custom-class="UserInfoContent"
    >
      <div>
        <!-- 头部 -->
        <div class="conTitle">{{$t("commentlist.conTitle7")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->
        <div class="detailed">
          <div class="essentialInformation">
            <div class="video">
              <video-player
                class="video-player vjs-custom-skin"
                ref="videoPlayer"
                :playsinline="true"
                :options="playerOptions"
              ></video-player>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: "papavideodetails",
  props: {
    status: null,
    id: null,
    video_url: null
  },
  data() {
    return {
      playerOptions: {}
    };
  },
  methods: {
    goback() {
      this.$emit("changeStatus", false);
    },
    onLive(src) {
      this.playerOptions = {
        playbackRates: [0.7, 1.0, 1.5, 2.0], //播放速度
        autoplay: false, //如果true,浏览器准备好时开始回放。
        muted: false, // 默认情况下将会消除任何音频。
        loop: false, // 导致视频一结束就重新开始。
        preload: "auto", // 建议浏览器在<video>加载元素后是否应该开始下载视频数据。auto浏览器选择最佳行为,立即开始加载视频（如果浏览器支持）
        language: "zh-CN",
        aspectRatio: "16:9", // 将播放器置于流畅模式，并在计算播放器的动态大小时使用该值。值应该代表一个比例 - 用冒号分隔的两个数字（例如"16:9"或"4:3"）
        fluid: true, // 当true时，Video.js player将拥有流体大小。换句话说，它将按比例缩放以适应其容器。
        sources: [
          {
            type: "", //这里的种类支持很多种：基本视频格式、直播、流媒体等，具体可以参看git网址项目
            src: src //url地址
          }
        ],
        // poster: "../../static/images/test.jpg", //你的封面地址
        // width: document.documentElement.clientWidth, //播放器宽度
        notSupportedMessage: "此视频暂无法播放，请稍后再试", //允许覆盖Video.js无法播放媒体源时显示的默认信息。
        controlBar: {
          timeDivider: true,
          durationDisplay: true,
          remainingTimeDisplay: false,
          fullscreenToggle: true //全屏按钮
        }
      };
    }
  },
  watch: {
    status() {
      if (this.status == true) {
        this.onLive(this.video_url);
      }
    }
  }
};
</script>

<style lang="scss">
.UserInfoContent {
  .el-dialog__body {
    width: 1200px;
    margin: 0 auto;
  }
  .detailed {
    margin: 40px 0;
    .video-js .vjs-icon-placeholder {
      width: 100%;
      height: 100%;
      display: block;
    }
    .vjs-custom-skin > .video-js .vjs-big-play-button {
      width: 80px;
      height: 80px !important;
      line-height: 80px !important;
      border-radius: 50%;
      span {
        font-size: 40px;
      }
    }
  }
}
</style>

