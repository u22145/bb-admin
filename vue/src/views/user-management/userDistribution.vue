<template>
  <div class="userDistribution">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input  v-model="uid" style="width: 200px" id="selectUserID"></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input  v-model="nickname" style="width: 200px" id="nickname"></el-input>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData(1)">{{$t('common.search')}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="exportExecel">{{$t("common.export")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="itemData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("user_management.upper_name")' align="center">
            <template slot-scope="scope">
              <span>{{ scope.row.upper_name }}{{scope.row.upper_uid}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("user_management.invitation_user_num")' align="center" prop="invitation_user_num"></el-table-column>
          <el-table-column :label='$t("user_management.invitationnum")' align="center" prop="invitation_user_num"></el-table-column>
          <el-table-column :label='$t("user_management.rank")' align="center" prop="rank" min-width="130px">
            <template slot-scope="scope">
              <span>{{ scope.row.rank?scope.row.rank:0 }}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("user_management.eurc")' align="center"  min-width="140px">
            <template slot-scope="scope">
              <span>{{ scope.row.eurc?scope.row.eurc:0 }}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("user_management.total")' align="center" prop="total"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t('LabourUnion.look_price')}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
          :page-size="limit"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          class="fr"
        ></el-pagination>
      </div>
    </div>
    <!-- 用户分销收益明细 -->
    <userDistributiondetails :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>


<script>
export default {
  components: {
    userDistributiondetails: () =>
      import("../../components/userDistributiondetails")
  },
  data() {
    return {
      loading: false,
      export: 0,
      page: 1, //前往第几页
      total: 1, //总页码
      limit: this.$store.state.adminPageSize,
      uid: "",
      nickname: "",
      itemData: [],
      id: "",
      status: false
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    clear() {
      this.uid = "";
      this.nickname = "";
    },
    seeUserInfo(row) {
      this.id = row.id;
      this.status = true;
      localStorage.setItem("uid", row.uid);
    },
    changeStatus(data) {
      this.status = data;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/user/user_distribution", {
          page: this.page,
          uid: this.uid,
          nickname: this.nickname,
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.itemData = res.data.data;
            this.page =parseInt(res.data.page);
            this.total = res.data.total;
          } else {
            if (res.status == 1) {
              window.open(`${window.location.origin}${res.data}`);
            } else {
              this.$message.error(res.msg);
            }
          }
        });
    },
    // 导出
    exportExecel() {
      this.export = 1;
      this.getData(2);
    }
  }
};
</script>


<style lang="scss" scoped>
.userDistribution {
  .fr {
    margin-bottom: 20px;
  }
  .screen {
    margin-bottom: 15px;
    label {
      color: #767474;
      font-size: 14px;
      margin-left: 20px;
    }
  }
  .operation {
    i {
      margin-right: 3px;
      font-size: 14px;
    }
  }

  .tableCon:after {
    content: "";
    display: block;
    clear: both;
  }
}
</style>


