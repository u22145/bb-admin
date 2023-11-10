<template>
  <div class="prohibitlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="uid"
          id="selectUserID"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="nickname"
          id="nickname"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label for="operator">{{$t("user_management.operator")}}</label>&nbsp;
        <el-input
          v-model="operator"
          style="width: 200px"
          id="operator"
          @keyup.enter.native="getData(1)"
        ></el-input>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("user_management.prohibition")}}</label>&nbsp;
        <el-select placeholder v-model="prohibition" @keyup.enter.native="getData(1)">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option :label='$t("user_management.Banning")' value="1"></el-option>
          <el-option :label='$t("user_management.Banning1")' value="2"></el-option>
        </el-select>
        <label>{{$t("user_management.Banningtime")}}</label>&nbsp;
        <el-date-picker
          v-model="datetime"
          type="datetimerange"
           :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" @click="search()" style="padding: 8px 30px">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getData(2)">{{$t("common.export")}}</el-button>
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
          <el-table-column :label='$t("user_management.Banningtime")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("user_management.reason")' align="center" prop="comm"></el-table-column>
          <el-table-column :label='$t("user_management.prohibition")' align="center" prop="status"></el-table-column>
          <el-table-column :label='$t("user_management.operator")' align="center" prop="admin_name"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="commitPass(scope.row)">{{$t('main.open')}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
        ></el-pagination>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      datetime: [],
      uid: "",
      nickname: "",
      operator: "",
      prohibition: "",
      type: "",
      itemData: []
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
      this.datetime = [];
      this.uid = "";
      this.nickname = "";
      this.operator = "";
      this.prohibition = "";
    },
    //选择页码
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    //请求列表
    getData(type = 1) {
      this.loading = true;
      this.$http
        .post("/user/block_list", {
          page: this.page,
          uid: this.uid,
          nickname: this.nickname,
          block_type: this.prohibition,
          operator: this.operator,
          block_time_start: this.datetime[0],
          block_time_end: this.datetime[1],
          type: type
        })
        .then(res => {
          this.loading = false;
          if (type == 1) {
            this.itemData = res.data.list;
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
    },
    //解封请求
    commitPass(row) {
      this.$http
        .post("/user/block", {
          uid: row.uid,
          region: 0
        })
        .then(res => {
          this.$message({
            message: "解禁成功",
            type: "success"
          });
          this.getData();
        });
    }
  }
};
</script>


<style lang="scss" scoped>
.prohibitlist {
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


