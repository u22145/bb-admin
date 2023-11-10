<template>
  <div class="commentdetails">
    <!-- 评论详情页 -->
    <el-dialog
      :visible.sync="status"
      :fullscreen="true"
      :modal-append-to-body="true"
      :append-to-body="true"
      lock-scroll
      :show-close="false"
      custom-class="UserInfoContent"
      :close-on-press-escape="false"
    >
      <div>
        <!-- 头部 -->
        <div class="conTitle">{{$t("commentlist.conTitle1")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->

        <div class="detailed">
          <div class="essentialInformation">
            <div class="ess_con">
              <div>
                <div>{{$t("commentlist.Comment_ID")}}</div>
                <div>{{$t("commentlist.Comment_content")}}</div>
                <div>{{$t("commentlist.tid")}}</div>
                <div>{{$t("common.status")}}</div>
                <div>{{$t("common.Username")}}</div>
                <div>{{$t("common.nickname")}}</div>
                <div>{{$t("commentlist.reason")}}</div>
                <div>{{$t("commentlist.time")}}</div>
              </div>
              <div>
                <div>{{datainfo.id}}</div>
                <div>{{datainfo.review}}</div>
                <div>{{datainfo.tid}}</div>
                <div>{{datainfo.status}}</div>
                <div>{{datainfo.uid}}</div>
                <div>{{datainfo.nickname}}</div>
                <div>{{datainfo.description}}</div>
                <div>{{datainfo.uptime}}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: "commentdetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      datainfo: {}
    };
  },
  //监听事件
  watch: {
    status() {
      if (this.status == true) {
        this.getinfo();
      }
    }
  },
  methods: {
    goback() {
      this.$emit("goback", false);
    },
    //获取详情
    getinfo() {
      this.$http
        .post("/blog/rev_detail", {
          review_id: this.id
        })
        .then(res => {
          this.datainfo = res.data;
        });
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
    .screen {
      margin-top: 30px;
      .fr {
        margin-left: 20px;
      }
    }

    .ess_con {
      > div {
        display: inline-block;
        vertical-align: top;
      }
      img {
        height: 100%;
      }
      > div:nth-child(1) {
        div {
          height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          border: 1px solid #d9d9d9;
          border-bottom: 0;
          width: 200px;
          padding: 0 10px;
          color: #4c4c4c;
          background: #d9d9d9;
        }

        div:last-child {
          border-bottom: 1px solid #d9d9d9;
        }
      }

      > div:nth-child(2) {
        div {
          overflow: hidden;
          height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          border: 1px solid #d9d9d9;
          border-bottom: 0;
          border-left: 0;
          width: 1000px;
          padding: 0 10px;
        }

        div:last-child {
          border-bottom: 1px solid #d9d9d9;
        }
      }
    }
  }
}
</style>

