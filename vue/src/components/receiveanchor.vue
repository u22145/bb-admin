<template>
  <div class="receiveanchor">
    <!-- 主播审核页 -->
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
        <div class="conTitle">{{$t("commentlist.conTitle10")}}</div>
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
                <div>{{$t("common.Username")}}</div>
                <div>{{$t("common.nickname")}}</div>
                <div>{{$t("commentlist.uptime")}}</div>
                <div>{{$t("common.registerCategory")}}</div>
                <div class="spac">{{$t("commentlist.Selfie")}}</div>
                <div class="spac">{{$t("commentlist.cover")}}</div>
                <div class="spac">{{$t("commentlist.video")}}</div>
                <div>{{$t("common.status")}}</div>
                <div class="spac">{{$t("common.reviewComment")}}</div>
              </div>
              <div>
                <div>{{anchorInfo.uid}}</div>
                <div>{{anchorInfo.nickname}}</div>
                <div>{{anchorInfo.uptime}}</div>
                <div>
                 <select v-model="registerCategoryValue" >
                      <option  v-for="item in registerCategory" :value="item.id" :key="item.id">{{item.catName}}</option>
                  </select>
                </div>
                <div class="spac">
                  <img :src="anchorInfo.take_photo" />
                </div>
                <div class="spac">
                  <img style="max-height: 200px;" :src="anchorInfo.pic_url" />
                </div>
                <div style="height: 200px;padding: 0 10px">
                  <video
                    v-if="anchorInfo.video_url !=''"
                    :src="anchorInfo.video_url"
                    class="avatar video-avatar"
                    controls="controls"
                  >{{$t("resources.Tips1")}}</video>
                </div>
                <div>{{anchorInfo.status_text}}</div>
                <div class="spac">
                  <h5>{{$t('common.reviewComment')}}</h5>
                  <el-input
                    type="textarea"
                    :rows="5"
                    placeholder="请输入内容"
                    v-model="textarea">
                  </el-input>
                </div>
              </div>
            </div>
            <div class="screen clear" v-show="anchorInfo.status">
              <el-button
                type="danger"
                class="fr"
                style="padding: 8px 30px"
                @click="dialogFormVisible = true"
              >{{$t("common.Audit_failed")}}</el-button>
              <el-button
                type="primary"
                class="fr"
                style="padding: 8px 30px"
                v-on:click="doCertify(1)"
              >{{$t("common.Audit_pass")}}</el-button>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>

    <!-- 审核不通过弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("confirm.reason")'>
          <el-input type="textarea" v-model="form.comm" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="doCertify(2)">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import axios from "axios";
export default {
  name: "receiveanchor",
  props: {
    status: null,
    id: null,
    uid: ""
  },
  data() {
    return {
      dialogFormVisible: false,
      form: {
        comm: ""
      },
      formLabelWidth: "120px",
      anchorInfo: {},
      registerCategoryValue: 1,
      registerCategory: null,
      textarea: "",
      page: 0
    };
  },
  //监听事件
  watch: {
    id() {
      this.getdetaile();
    }
  },
  mounted() {
    this.getCategoryList()
  },
  methods: {
    // 通过/驳回
    doCertify(status) {
      if (status == 1) this.form.comm = "";
      this.dialogFormVisible = false;
      let url = process.env.VUE_APP_API_URL + "anchor/cert/"
      let content = {
          id: this.id,
          status: status,
          remarks: this.form.comm,
          catId: this.registerCategoryValue
      }
       let req = {
        url,
        method: 'PUT',
        data: content
      }
      let self = this
      axios(req).then(res => {
          if (res.data.status == 1) {
            self.$message.success(res.data.data);
          } else {
            self.$message.error(res.data.data);
          }
          this.$emit("changeStatus", false);
      }, error => {
        self.$message.error(res.data.data);
      })
        // this.$http
        // .post("/live/do_certify", {
        //   id: this.id,
        //   status: status,
        //   type: "anchor",
        //   comm: this.form.comm,
        //   uid: window.localStorage.getItem("anchorlist_uid")
        // })
        // .then(res => {
        //   if (res.status == 1) {
        //     this.$message.success(res.msg);
        //   } else {
        //     this.$message.error(res.msg);
        //   }
        //   this.$emit("changeStatus", false);
        // });

    },
    getCategoryList() {
      this.$httpJava
        .get("/ls/cat", {
          page: this.page
        })
        .then(res => {
          this.registerCategory = res.data.data.data
        });
    },
    // 获取详情
    getdetaile() {
      this.$http
        .post("/live/anchor_detail", {
          id: this.id
        })
        .then(res => {
          if (res.status == 1) {
            this.anchorInfo = res.data;
          } else {
            this.$message({
              type: "error",
              message: res.msg
            });
          }
        });
    },
    goback() {
      this.$emit("changeStatus", false);
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
  .el-textarea {
    border: 0 !important;
    max-width: 400px;
    height: 200px;
  }
  .video-avatar {
    width: 400px;
    height: 200px;
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
}
</style>

