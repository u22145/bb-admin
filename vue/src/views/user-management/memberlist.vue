<template>
  <div class="memberlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="uid"
          id="selectUserID"
          @keyup.enter.native="seach"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="nickname"
          id="nickname"
          @keyup.enter.native="seach"
        ></el-input>
        <label style="margin-left: 20px">{{$t("user_management.Duration_membership")}}</label>&nbsp;
        <el-select placeholder v-model="vip_name" @keyup.enter.native="seach">
          <el-option :label='$t("common.all")' value="$t('common.all')"></el-option>
          <el-option :label='$t("resources.one")' value="$t('resources.one')"></el-option>
          <el-option :label='$t("resources.three")' value="$t('resources.three')"></el-option>
          <el-option :label='$t("resources.foreve")' value="$t('resources.foreve')"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("user_management.Membership_type")}}</label>&nbsp;
        <el-select placeholder v-model="vip_type" @keyup.enter.native="seach">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option label="VIP" value="1"></el-option>
          <el-option label="SVIP" value="2"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="seach">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getData(2)">{{$t("common.export")}}</el-button>
      </div>
      <div class="tableCon">
        <!-- 表格 -->
        <el-table
          ref="multipleTable"
          :data="itemData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("user_management.Duration_membership")' align="center" prop="vip_name"></el-table-column>
          <el-table-column :label='$t("user_management.Membership_type")' align="center" prop="vip_type"></el-table-column>
          <el-table-column :label='$t("resources.time")' align="center" prop="vip_expire"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <!-- 分页 -->
        <el-pagination
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

    <!-- 弹窗 -->
    <el-dialog  :visible.sync="dialogTableVisible">
      <el-table :data="gridData" border style="width: 90%;margin:0 auto;">
        <el-table-column property="pay_uid" :label='$t("common.Username")' align="center"></el-table-column>
        <el-table-column property="nickname" :label='$t("common.nickname")' align="center"></el-table-column>
        <el-table-column property="uptime" :label='$t("user_management.buytime")' align="center" min-width="130px"></el-table-column>
        <el-table-column property="active_life" :label='$t("user_management.Duration_membership")' align="center"></el-table-column>
        <el-table-column property="vip_type" :label='$t("user_management.Membership_type")' align="center"></el-table-column>
        <el-table-column property="exp_time" :label='$t("resources.time")' align="center" min-width="130px"></el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: false,
      export: 0,
      itemData: [],
      gridData: [],
      dialogTableVisible: false,
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      uid: "",
      nickname: "",
      vip_type: "",
      vip_name: ""
    };
  },
  created() {
    this.getData();
  },
  methods: {
    clear() {
      this.uid = "";
      this.nickname = "";
      this.vip_type = "";
      this.vip_name = "";
    },
    //  查看
    seeUserInfo(row) {
      this.$http
        .post("/user/get_vip_info", {
          id: row.uid
        })
        .then(res => {
          this.gridData = res.data;
        });
      this.dialogTableVisible = true;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 查询
    seach() {
      this.page = 1;
      this.getData();
    },
    // 获取会员列表
    getData(type = 1) {
      this.loading = true;
      let vm = this;
      this.$http
        .post("/user/vip_list", {
          module: 1,
          page: this.page,
          uid: this.uid,
          nickname: this.nickname,
          vip_type: this.vip_type,
          vip_name: this.vip_name,
          type: type
        })
        .then(res => {
          this.loading = false;
          if (type == 1) {
            vm.itemData = res.data.list;
            vm.page = res.data.page;
            vm.total = res.data.total;
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



<style lang="scss" scoped>
.memberlist {
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



