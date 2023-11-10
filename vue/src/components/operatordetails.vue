<template>
  <div class="operatordetails">
    <!-- 管理人员详情页 -->
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
        <div class="conTitle">{{$t("commentlist.conTitle5")}}</div>
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
                <div>{{$t("jurisdiction.roleID")}}</div>
                <div>{{$t("jurisdiction.Cell_phone_number")}}</div>
                <div>{{$t("common.Creation_time")}}</div>
                <div>{{$t("jurisdiction.Jurisdiction")}}</div>
                <div>{{$t("jurisdiction.Role_Description")}}</div>
              </div>
              <div>
                <div>{{data.id}}</div>
                <div>{{data.mobile}}</div>
                <div>{{data.uptime}}</div>
                <div>{{data.is_super_admin_msg}}</div>
                <div>{{data.description}}</div>
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
  name: "operatordetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      data: []
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
      this.$emit("changeStatus", false);
    },
    //获取角色权限详情
    getinfo() {
      this.$http
        .post("/system/show_admin_user", {
          role_id: this.id
        })
        .then(res => {
          this.data = res.data;
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
          height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          border: 1px solid #d9d9d9;
          border-bottom: 0;
          border-left: 0;
          width: 1000px;
          padding: 0 10px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        div:last-child {
          border-bottom: 1px solid #d9d9d9;
        }
      }
    }
  }
}
</style>

