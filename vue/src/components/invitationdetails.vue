<template>
  <div class="invitationdetails">
    <!-- 帖子详情页 -->
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
        <div class="conTitle">{{$t("commentlist.conTitle3")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->

        <div class="detailed">
          <div class="essentialInformation">
            <!-- <div class="real_con"> 
              <div><span>帖子ID</span><p>{{datainfo.id}}</p></div>
              <div><span>帖子文本内容</span><p>{{datainfo.content}}</p></div>
              <div><span>帖子图片</span>
                <p>
                   <img
                    v-show="son"
                    width="250"
                    height="200"
                    v-for="(son,o) in datainfo.pic_url"
                    :key="o"
                    :src="son"
                    :class="{'active':isChoose}"
                    @click="imgScc"
                    style="margin:5px"
                  />
                </p>
              </div>
              <div><span>帖子短视频</span><p>
                 <video
                    v-if="datainfo.video !=''"
                    :src="datainfo.video"
                    class="avatar video-avatar"
                    controls="controls"
                    width="300"
                    height="200"
                  >您的浏览器不支持视频播放</video></p></div>
              <div><span>创建时间</span><p>{{datainfo.update}}</p></div>
              <div><span>用户ID</span><p>{{datainfo.uid}}</p></div>
              <div><span>用户昵称</span><p>{{datainfo.nickname}}</p></div>
              <div><span>点赞数</span><p>{{datainfo.likes_num}}</p></div>
              <div><span>评论数</span><p>{{datainfo.review_num}}</p></div>
              <div><span>转发数</span><p>{{datainfo.share_num}}</p></div>
              <div><span>审核状态</span><p>{{datainfo.status}}</p></div>
              <div><span>屏蔽原因</span><p>{{datainfo.description}}</p></div>
            </div>-->
            <div class="ess_con">
              <div>
                <div>{{$t("commentlist.tid")}}</div>
                <div>{{$t("commentlist.tcontent")}}</div>
                <div class="spac">{{$t("common.img")}}</div>
                <div style="height: 200px;padding: 0 10px">{{$t("commentlist.tvideo")}}</div>
                <div>{{$t("common.Creation_time")}}</div>
                <div>{{$t("common.Username")}}</div>
                <div>{{$t("common.nickname")}}</div>
                <div>{{$t("commentlist.Praise_points")}}</div>
                <div>{{$t("commentlist.Comment_number")}}</div>
                <div>{{$t("commentlist.Forwarding_number")}}</div>
                <div>{{$t("common.status")}}</div>
                <div>{{$t("commentlist.reason")}}</div>
              </div>
              <div>
                <div>{{datainfo.id}}</div>
                <div>{{datainfo.content}}</div>
                <div class="spac">
                  <img
                    v-for="(son,o) in datainfo.pic_url"
                    :key="o"
                    :src="son"
                    style="margin-right:5px"
                  />
                </div>
                <div style="height: 200px;padding: 0 10px">
                  <video
                    v-if="datainfo.video !=''"
                    :src="datainfo.video"
                    class="avatar video-avatar"
                    controls="controls"
                    width="300"
                    height="200"
                  >{{$t("resources.Tips1")}}</video>
                </div>
                <div>{{datainfo.uptime}}</div>
                <div>{{datainfo.uid}}</div>
                <div>{{datainfo.nickname}}</div>
                <div>{{datainfo.likes_num}}</div>
                <div>{{datainfo.review_num}}</div>
                <div>{{datainfo.share_num}}</div>
                <div>
                  <span v-if="datainfo.status==0">{{$t("common.Unaudited")}}</span>
                  <span v-if="datainfo.status==1">{{$t("common.Audit_pass")}}</span>
                  <span v-if="datainfo.status==2">{{$t("common.Audit_failed")}}</span>
                  <span v-if="datainfo.status==3">{{$t("common.Audit_del")}}</span>
                </div>
                <div>{{datainfo.description}}</div>
              </div>
            </div>
            <div class="screen clear" v-if="datainfo.status==0">
              <el-button
                type="danger"
                class="fr"
                style="padding: 8px 30px"
                @click="alert = true"
              >{{$t("common.not_pass")}}</el-button>
              <el-button
                type="primary"
                class="fr"
                style="padding: 8px 30px"
                @click="passStatus(1)"
              >{{$t("common.adopt")}}</el-button>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
    <el-dialog  :visible.sync="alert">
      <el-form :model="form">
        <el-form-item :label='$t("confirm.reason")'>
          <el-input type="textarea" v-model="form.content"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="alert = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="passStatus(2)">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: "invitationdetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      datainfo: {},
      // isChoose: false,
      alert: false,
      form: {
        content: "",
        status: 2
      }
    };
  },
  //监听事件
  watch: {
    status() {
      if (this.status == true) {
        this.getdetaile();
      }
    }
  },
  methods: {
    passStatus(data) {
      this.$http
        .post("/blog/update", {
          blog_id: this.id,
          status: data,
          description: this.form.content
        })
        .then(res => {
          if (res.status) {
            this.$message.success(res.msg);
            this.getdetaile();
          }
          this.alert = false;
        });
    },
    // imgScc() {
    //  :class="{'active':isChoose}"
    //               @click="imgScc"
    //   this.isChoose = !this.isChoose;
    // },
    goback() {
      this.$emit("changeStatus", false);
    },
    // 获取详情
    getdetaile() {
      this.$http
        .post("/blog/blog_detail", {
          id: this.id
        })
        .then(res => {
          this.datainfo = res.data;
        });
    }
  }
};
</script>

<style lang="scss" >
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
    //  .real_con{
    //     >div{
    //       min-height: 40px;
    //       margin:5px 0;
    //       overflow: hidden;
    //       >span{
    //         float: left;
    //         width: 10%;
    //       }
    //       >p{
    //         float: left;
    //         width:90%;
    //         line-height: 20px;
    //         >img{
    //         vertical-align: top;
    //         }
    //       }
    //     }
    //   }
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
  .spac {
    text-indent: 10px !important;
    height: 200px !important;
    padding: 10px 0 !important;
  }
  .spac img {
    max-height: 200px !important;
  }
  // img {
  //   transform: scale(1);
  //   transition: all ease 0.5s;
  // }
  // img.active {
  //   transform: scale(2);
  //   position: absolute;
  //   z-index: 100;
  // }
}
</style>

