<template>
  <div class="receiveanchor">
    <!-- 实名审核页 -->
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
        <div class="conTitle">{{$t("commentlist.conTitle9")}}</div>
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
                <div>{{$t("LiveBroadcast.name")}}</div>
                <div>{{$t("commentlist.Documenttype")}}</div>
                <div>{{$t("commentlist.Documentnum")}}</div>
                <div>{{$t("commentlist.uptime")}}</div>
                <div class="spac">{{$t("commentlist.Positive")}}</div>
                <div class="spac">{{$t("commentlist.side")}}</div>
                <div class="spac">{{$t("commentlist.Hand_held")}}</div>
                <div class="spac">{{$t("commentlist.Selfie")}}</div>
                <div>{{$t("common.status")}}</div>
              </div>
              <div>
                <div>{{anchorInfo.uid}}</div>
                <div>{{anchorInfo.realname}}</div>
                <div>{{anchorInfo.id_type_name}}</div>
                <div>{{anchorInfo.id_no}}</div>
                <div>{{anchorInfo.uptime}}</div>
                <div class="spac">
                  <img :src="anchorInfo.idcard1" />
                </div>
                <div class="spac">
                  <img :src="anchorInfo.idcard2" />
                </div>
                <div class="spac">
                  <img :src="anchorInfo.idcard3" />
                </div>
                <div class="spac">
                  <img :src="anchorInfo.photo" />
                </div>
                <div>{{anchorInfo.status_text}}</div>
              </div>
            </div>
            <div class="screen clear" v-show="anchorInfo.status==3">
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
export default {
  name: "receiveanchor",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      dialogFormVisible: false,
      form: {
        comm: ""
      },
      formLabelWidth: "120px",
      anchorInfo: {}
    };
  },
  //监听事件
  watch: {
    id() {
      this.getdetaile();
    }
  },
  methods: {
    // 1:通过/2:驳回
    doCertify(status) {
      if (status == 1) this.form.comm = "";
      this.dialogFormVisible = false;
      this.$http
        .post("/live/do_certify", {
          id: this.id,
          status: status,
          type: "certify",
          comm: this.form.comm,
          uid: window.localStorage.getItem("real_uid")
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
          } else {
            this.$message.error(res.msg);
          }
          this.getdetaile();
        });
    },
    // 获取详情
    getdetaile() {
      this.$http
        .post("/live/certify_detail", {
          id: window.localStorage.getItem("real_id"),
          uid: window.localStorage.getItem("real_uid")
        })
        .then(res => {
          if (res.status == 1) {
            this.anchorInfo = res.data;
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
          min-height: 40px;
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
          min-height: 40px;
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

