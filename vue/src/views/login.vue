<template>
  <div class="login">
    <div class="content">
      <h1>{{$t("main.title")}}</h1>
      <div class="uAndP">
        <div class="userName">
          <el-input
            :placeholder='$t("main.account")'
            v-model="userName"
            clearable
            @keyup.enter.native="landFunc"
          ></el-input>
        </div>
        <div class="passWord">
          <el-input
            :placeholder='$t("main.passWord")'
            v-model="passWord"
            show-password
            clearable
            @keyup.enter.native="landFunc"
          ></el-input>
        </div>
        <button class="land" @click="landFunc">{{$t("main.login")}}</button>
      </div>
    </div>
  </div>
</template>

<script>
import { reg } from "../utils/reg.js";
export default {
  data() {
    return {
      passWord: "",
      userName: "",
      regMethods: reg
    };
  },
  methods: {
    //  登录
    landFunc() {
      // if (this.regMethods.checkPhone(this.userName) && this.regMethods.checkPwd(this.passWord)) {
      this.$http
        .post("/login/login", {
          password: this.passWord,
          username: this.userName
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
            window.localStorage.setItem("usercode", res.data.usercode);
            window.localStorage.setItem("username", res.data.username);
            window.localStorage.setItem(
              "rtm_user",
              JSON.stringify(res.data.user)
            );

            // 取右侧菜单
            this.$http.post("/login/getmenu").then(ress => {
              this.$router.push(ress.data[0].son[0].vue_router);
            });
          } else {
            this.$message.error(res.msg);
          }
        });
      // }
    }
  }
};
</script>

<style lang="scss" scoped>
.login {
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  position: relative;
  .content {
    height: 400px;
    width: 600px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ccc;
    -moz-box-shadow: 2px 2px 5px #333333;
    -webkit-box-shadow: 2px 2px 5px #333333;
    box-shadow: 2px 2px 5px #333333;
    border-radius: 5px;
    h1 {
      color: #35495d;
      font-size: 35px;
      text-align: center;
      margin: 40px auto 40px;
    }
    .uAndP {
      width: 40%;
      margin: 30px auto;
      .userName,
      .passWord {
        width: 100%;
        margin: 20px 0 0;
      }
      .land {
        margin: 60px auto 0;
        width: 100%;
        height: 40px;
        background: rgba(0, 121, 255, 1);
        border-radius: 5px;
        color: #fff;
        text-align: center;
        line-height: 40px;
        font-size: 22px;
        cursor: pointer;
        border: none;
      }
    }
  }
}
</style>


