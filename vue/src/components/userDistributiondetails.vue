<template>
  <div class="incomedetails">
    <el-dialog
      :visible.sync="status"
      :fullscreen="true"
      :modal-append-to-body="true"
      :append-to-body="true"
      lock-scroll
      :show-close="false"
      custom-class="UserInfoContent"
      :close-on-press-escape="false"
      v-loading="loading"
    >
      <div>
        <!-- 头部 -->
        <div class="conTitle">{{$t("commentlist.conTitle13")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->
        <div class="detailed">
          <div class="essentialInformation">
            <div class="stat_con">
              <div style="margin-bottom: 20px">
                <el-button class="fr" @click="getDetils(2)" style="margin-bottom:20px">{{$t("common.export")}}</el-button>
                <el-table :data="tableData" border style="width: 100%">
                  <el-table-column prop="level" :label='$t("resources.level")' align="center"></el-table-column>
                  <el-table-column prop="uid" :label='$t("common.Username")' align="center"></el-table-column>
                  <el-table-column prop="nickname" :label='$t("common.nickname")' align="center"></el-table-column>
                  <el-table-column prop="rank" :label='$t("user_management.consumeeurc")' align="center" min-width="120px"></el-table-column>
                  <el-table-column
                    prop="msq"
                    :label='$t("user_management.consumemsq")'
                    align="center"
                    min-width="120px"
                  ></el-table-column>
                  <el-table-column
                    prop="eurc"
                    :label='$t("user_management.OneOnone")'
                    align="center"
                    min-width="120px"
                  ></el-table-column>
                  <el-table-column prop="total" :label='$t("user_management.total")' align="center"></el-table-column>
                  <el-table-column prop="reg_date" :label='$t("user_management.Registrationtime")' align="center" min-width="120px"></el-table-column>
                </el-table>
                <el-pagination
                  @size-change="handleSizeChange"
                  @current-change="handleCurrentChange"
                  :current-page="page"
                  :page-sizes="[this.$store.state.adminPageSize]"
                  :page-size="limit"
                  layout="total, sizes, prev, pager, next, jumper"
                  :total="total"
                  class="fr"
                ></el-pagination>
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
  name: "userDistributiondetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      tableData: [],
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      loading: false
    };
  }, //监听事件
  watch: {
    status() {
      if (this.status == true) {
        this.getDetils();
      }
    }
  },
  methods: {
    goback() {
      this.$emit("changeStatus", false);
    },
    handleSizeChange(val) {
      this.limit = val;
      this.page = 1;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getDetils();
    },
    getDetils(exp = 1) {
      this.$http
        .post("/user/user_distribution_desc", {
          page: this.page,
          uid: localStorage.getItem("uid"),
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.data;
            this.page = res.data.page;
            this.total = res.data.total;
          } else {
            if (res.status == 1) {
              window.open(`${window.location.origin}${res.data}`);
            } else {
              this.$message.error(res.msg);
            }
          }
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
      margin-bottom: 20px;
      .fr {
        margin-left: 20px;
      }
    }

    .stat_con {
      > div {
        display: inline-block;
        vertical-align: top;
        width: 100%;
      }
      img {
        height: 100%;
      }
    }
  }
}
</style>

