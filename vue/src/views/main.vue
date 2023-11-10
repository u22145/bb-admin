<template>
  <div class="main">
    <!-- 滚动条 -->
    <!-- <el-scrollbar style="height:100%"> -->
    <el-container>
      <!-- 头部 -->
      <el-header height="80px">
        <div class="fl">
          <span style="font-size: 35px">{{$t("main.title")}}</span>
        </div>
        <div class="fr">
          <el-dropdown trigger="click">
            <span class="el-dropdown-link" style="font-size: 18px;margin-right:20px;">
              {{username}}
              <i class="el-icon-arrow-down el-icon--right"></i>
            </span>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="edit_password">{{$t("main.edit_password")}}</el-dropdown-item>
              <el-dropdown-item @click.native="logout">{{$t("main.logout")}}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>

          <el-dropdown trigger="click">
            <span class="el-dropdown-link" style="font-size: 18px">
              {{$t("main.language")}}
              <i class="el-icon-arrow-down el-icon--right"></i>
            </span>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item @click.native="chanageZh">{{$t("main.zh")}}</el-dropdown-item>
              <el-dropdown-item @click.native="chanageEn">{{$t("main.en")}}</el-dropdown-item>
              <el-dropdown-item @click.native="chanageJa">{{$t("main.ja")}}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
        </div>
      </el-header>
      <el-container>
        <!-- 菜单 -->
        <el-aside width="200px" style="background-color: #fff" v-loading="loading">
          <el-menu unique-opened  @open="checkDirectJava" @close="checkDirectJava" :router="true" :default-active="$route.path">
            <el-submenu :index="item.power_id" v-for="(item,index) in navdata" :key="index">
              <template slot="title">{{item.power_name}}</template>
              <el-menu-item
               
                :index="son.vue_router"
                v-for="(son,o) in item.son"
                :key="o"
                @click="turnTab(son.vue_router,son.power_name,item.power_name)"
              >
                <span style="margin-right: 8px">•</span>
                {{son.power_name}}
              </el-menu-item>
            </el-submenu>
          </el-menu>
        </el-aside>

        <!-- main -->
        <el-main>
          <el-scrollbar style="height:100%">
            <el-breadcrumb separator-class="el-icon-arrow-right" style="margin-bottom:20px">
              <el-breadcrumb-item>{{$t("main.current_location")}}</el-breadcrumb-item>
              <el-breadcrumb-item v-for="(item,index) in breadcrumbData" :key="index">{{item}}</el-breadcrumb-item>
            </el-breadcrumb>
            <router-view></router-view>
          </el-scrollbar>
        </el-main>
      </el-container>
    </el-container>
    <!-- </el-scrollbar> -->
    <!-- 修改密码弹窗 -->
    <el-dialog :title="$t('main.edit_password')" :visible.sync="dialogFormVisible" width="30%">
      <el-form :model="form">
        <el-form-item :label="$t('confirm.old_passW')" :label-width="formLabelWidth">
          <el-input v-model="form.old_password" autocomplete="off" show-password></el-input>
        </el-form-item>
        <el-form-item :label="$t('confirm.new_passW')" :label-width="formLabelWidth">
          <el-input v-model="form.new_password" autocomplete="off" show-password></el-input>
        </el-form-item>
        <el-form-item :label="$t('confirm.confirm_passW')" :label-width="formLabelWidth">
          <el-input v-model="form.new_confirm_password" autocomplete="off" show-password></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="confirmPassword">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>


<script>
import { reg } from "../utils/reg.js";
export default {
  data() {
    return {
      usercode: "",
      username: "",
      ietmdata: [],
      regMethods: reg,
      dialogFormVisible: false,
      form: {
        old_password: "",
        new_password: "",
        new_confirm_password: ""
      },
      formLabelWidth: "150px",
      navdata: [],
      breadcrumbData: [],
      loading: false,
      javaGuildUrl: ""
    };
  },
  async created() {
    this.loading = true;
    this.username = window.localStorage.getItem("username");

    this.getJavaGuildLink()
    //获取菜单
    await this.$http.post("/login/getmenu").then(res => {
      this.loading = false;
      if (res.status == 1) {
        this.navdata = res.data;
      }
    });
    const urlName = this.$route.path;
    for (let i in this.navdata) {
      let element = this.navdata[i];
      for (let j in element.son) {
        let item = element.son[j];
        if (item.vue_router == urlName) {
          this.breadcrumbData.push(element.power_name);
          this.breadcrumbData.push(item.power_name);
          return;
        }
      }
    }
  },
  methods: {
    //  退出登陆
    logout() {
      window.localStorage.removeItem("usercode"); //账号
      window.localStorage.removeItem("username");
      this.$message.success("您已退出登录");
      this.$router.push("/login");
    },
    getJavaGuildLink() {
      this.$http.get("/index/get_java_server_url", {
      }).then(res => {
        this.javaGuildUrl = res.data.url
      });
    },
    checkDirectJava(key, keyPath) {
      // if(key == 600) {
      //   window.location.href = this.javaGuildUrl        
      // }
    },
    turnTab(url, name, parentName) {
      this.breadcrumbData = [];
      parentName
        ? this.breadcrumbData.push(parentName, name)
        : this.breadcrumbData.push(name);
      this.$router.push(url);
    },
    // 切换语言
    chanageZh() {
      this.$i18n.locale == "zh";
      window.localStorage.setItem("lang", "zh");
      this.changeLang();
    },
    chanageEn() {
      this.$i18n.locale == "en";
      window.localStorage.setItem("lang", "en");
      this.changeLang();
    },
    chanageJa() {
      this.$i18n.locale == "ja";
      window.localStorage.setItem("lang", "ja");
      this.changeLang();
    },
    //修改密码
    edit_password() {
      this.dialogFormVisible = true;
    },
    changeLang(){
      this.$http.post("/system/change_langs", {
        lang:window.localStorage.getItem("lang")
      }).then(res => {
        if(res.status == 1){
          window.location.reload();
        }
      });
    },
    //确认修改
    confirmPassword() {
      if (
        this.regMethods.confirmPwd(
          this.form.new_password,
          this.form.new_confirm_password
        ) &&
        this.regMethods.checkPwd(this.form.new_password)
      ) {
        this.$http
          .post("/login/edit_password", {
            old_password: this.form.old_password,
            new_password: this.form.new_password,
            new_confirm_password: this.form.new_confirm_password
          })
          .then(res => {
            if (res.status == 1) {
              window.localStorage.removeItem("usercode"); //清除文件
              window.localStorage.removeItem("main"); //清除文件
              this.$router.push("/login");
              this.$message.success(res.msg);
            } else {
              this.$message.error(res.msg);
            }
          });
      }
    }
  }
};
</script>

<style lang="scss" scoped>
.main {
  width: 100%;
  height: 100%;
  .el-header {
    margin-bottom: 10px;
    padding: 0 100px;
    background: #3d75e4;
    color: #fff;
    .fl,
    .fr {
      height: 80px;
      line-height: 80px;
      box-sizing: border-box;
      img,
      span,
      .el-button {
        vertical-align: middle;
      }
    }
    .el-dropdown-link {
      cursor: pointer;
      color: #fff;
    }
    .el-icon-arrow-down {
      font-size: 12px;
    }
  }
  .el-container {
    height: 100%;
  }
}
</style>
