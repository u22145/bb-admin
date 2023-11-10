<template>
  <div class="userdetails">
    <!-- 用户详情页 -->
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
        <div class="conTitle">{{$t("commentlist.conTitle12")}}</div>
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
                <div class="spac">{{$t("common.img")}}</div>
                <div>{{$t("common.status")}}</div>
                <div>{{$t("user_management.Gender")}}</div>
                <div>{{$t("jurisdiction.Cell_phone_number")}}</div>
                <div>{{$t("user_management.country")}}</div>
                <div>{{$t("user_management.Registrationtime")}}</div>
                <div>{{$t("user_management.follow")}}</div>
                <div>{{$t("user_management.fans")}}</div>
                <div>{{$t("user_management.msq_balance")}}</div>
                <div>{{$t("user_management.eurc_balance")}}</div>
                <div>{{$t("user_management.vip")}}</div>
                <div>{{$t("user_management.Recommendedusers")}}</div>
                <div>{{$t("user_management.Real_nameauthentication")}}</div>
                <div>{{$t("user_management.AnchorAuthentication")}}</div>
                <div>{{$t("user_management.AnchorScoring")}}</div>
                <div>{{$t("user_management.AssociationOwnership")}}</div>
                <div>{{$t("user_management.DevicePlatform")}}</div>
                <div>{{$t("user_management.SystemVersion")}}</div>
                <div>{{$t("user_management.IMEI")}}</div>
                <div>{{$t("user_management.IMSI")}}</div>
                <div>{{$t("user_management.ChannelSources")}}</div>
                <div>{{$t("user_management.AppVersion")}}</div>
                <div>{{$t("user_management.VideoFees")}}</div>
                <div>{{$t("user_management.PrivateCharge")}}</div>
              </div>
              <div>
                <div>{{userInformation.id}}</div>
                <div>{{userInformation.nickname}}</div>
                <div class="spac">
                  <img :src="userInformation.avatar" />
                </div>
                <div>{{userInformation.status}}</div>
                <div>{{userInformation.gender==1?'女':'男'}}</div>
                <div>{{userInformation.mobile}}</div>
                <div>{{userInformation.country}}</div>
                <div>{{userInformation.join_date}}</div>
                <div>{{userInformation.following_num}}</div>
                <div>{{userInformation.follower_num}}</div>
                <div>{{userInformation.msq_balance}}</div>
                <div>{{userInformation.eurc_balance}}</div>
                <div>{{userInformation.is_vip}}</div>
                <div>{{userInformation.is_recommend}}</div>
                <div>{{userInformation.certify}}</div>
                <div>{{userInformation.is_anchor}}</div>
                <div>{{userInformation.rating_sum}}</div>
                <div>{{userInformation.sociaty}}</div>
                <div>{{userInformation.device}}</div>
                <div>{{userInformation.os_version}}</div>
                <div>{{userInformation.imei}}</div>
                <div>{{userInformation.imsi}}</div>
                <div>{{userInformation.market_id}}</div>
                <div>{{userInformation.app_version}}</div>
                <div>{{userInformation.video_fee}}</div>
                <div>{{userInformation.pm_fee}}</div>
              </div>
            </div>
            <div class="screen clear">
              <el-button type="primary" class="fr" style="padding: 8px 30px" @click="pwdShow">重置密码</el-button>
              <el-button
                type="primary"
                class="fr"
                style="padding: 8px 30px"
                @click="headAlert=true"
              >{{$t("user_management.ResetAvatar")}}</el-button>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("user_management.Banningreason")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.reason_text" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("user_management.prohibition")' :label-width="formLabelWidth">
          <el-select v-model="form.reason_status">
            <el-option :label='$t("user_management.Banning")' value="1"></el-option>
            <el-option :label='$t("user_management.Banning1")' value="2"></el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="commitPass">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <el-dialog  :visible.sync="resetAlert">
      <el-form :model="form">
        <el-form-item :label='$t("confirm.new_passW")' :label-width="formLabelWidth">
          <el-input type="password" v-model="form.pwd" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("confirm.confirm_passW")' :label-width="formLabelWidth">
          <el-input type="password" v-model="form.repwd" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="resetAlert = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="resetPassword">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <el-dialog  :visible.sync="headAlert">
      <el-form :model="form">
        <el-form-item :label='$t("user_management.NewAvatar")' :label-width="formLabelWidth">
          <el-upload
            class="avatar-uploader"
            action="/resource/reset_upload"
            :show-file-list="false"
            :on-success="handleAvatarSuccess"
            :before-upload="beforeAvatarUpload"
            :data="type"
          >
            <img v-if="imageUrl" :src="imageUrl" class="avatar" />
            <i v-else class="el-icon-plus avatar-uploader-icon"></i>
          </el-upload>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="headAlert = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="resetHead">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>
<script>
export default {
  name: "userdetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      userInformation: {},
      form: {
        reason_text: "",
        reason_status: "",
        pwd: "",
        repwd: ""
      },
      dialogFormVisible: false,
      resetAlert: false,
      headAlert: false,
      formLabelWidth: "120px",
      imageUrl: "",
      upload_img: ""
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
  computed: {
    //上传图片参数
    type() {
      return {
        type: "avatar",
        usercode: window.localStorage.getItem("usercode"),
        uid: this.id
      };
    }
  },
  methods: {
    goback() {
      this.$emit("changeStatus", false);
    },
    // 获取详情
    getdetaile() {
      this.$http
        .post("/user/get_userinfo", {
          uid: this.id
        })
        .then(res => {
          this.data = res.data;
          this.userInformation = res.data;
        });
    },
    reason() {
      this.dialogFormVisible = true;
    },
    pwdShow() {
      this.resetAlert = true;
    },
    resetHead() {
      if (!this.upload_img) {
        this.$message({
          message: "请先上传头像",
          type: "error"
        });
      }
      this.$http
        .post("/user/reset_head", {
          uid: this.id,
          img_url: this.upload_img
        })
        .then(res => {
          if (res.status == 1) {
            this.$message({
              message: res.msg,
              type: "success"
            });
          } else {
            this.$message({
              message: res.msg,
              type: "error"
            });
          }
          this.headAlert = false;
        });
    },
    resetPassword() {
      if (this.form.pwd != this.form.repwd) {
        this.$message({
          message: "两次密码不一致",
          type: "error"
        });
      }
      this.$http
        .post("/user/reset_password", {
          uid: this.id,
          pwd: this.form.pwd,
          repwd: this.form.repwd
        })
        .then(res => {
          if (res.status == 1) {
            this.$message({
              message: res.msg,
              type: "success"
            });
          } else {
            this.$message({
              message: res.msg,
              type: "error"
            });
          }
          this.resetAlert = false;
        });
    },
    commitPass() {
      this.$http
        .post("/user/block", {
          uid: this.id,
          comm: this.form.reason_text,
          region: this.form.reason_status
        })
        .then(res => {
          if (res.status == 1) {
            this.$message({
              message: "封禁成功",
              type: "success"
            });
          } else {
            this.$message({
              message: "封禁失败",
              type: "error"
            });
          }
          this.dialogFormVisible = false;
        });
    },
    handleAvatarSuccess(res, file) {
      this.upload_img = res.data.path;
      this.imageUrl = URL.createObjectURL(file.raw);
    },
    beforeAvatarUpload(file) {
      const isJPG = file.type === "image/jpeg";
      const isLt2M = file.size / 1024 / 1024 < 2;

      if (!isJPG) {
        this.$message.error("上传头像图片只能是 JPG 格式!");
      }
      if (!isLt2M) {
        this.$message.error("上传头像图片大小不能超过 2MB!");
      }
      return isJPG && isLt2M;
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
  img {
    transform: scale(1);
    transition: all ease 0.5s;
  }
  img.active {
    transform: scale(2);
    position: absolute;
    z-index: 100;
  }
}
.avatar-uploader .el-upload {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.avatar-uploader .el-upload:hover {
  border-color: #409eff;
}
.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 178px;
  height: 178px;
  line-height: 178px;
  text-align: center;
}
.avatar {
  width: 178px;
  height: 178px;
  display: block;
}
</style>

