<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="selectData.id"
          id="selectUserID"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="selectData.nickname"
          @keyup.enter.native="getData(1)"
          id="nickname"
        ></el-input>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select
          placeholder
          v-model="selectData.audit_status"
          @keyup.enter.native="getData(1)"
        >
          <el-option :label='$t("common.all")' value="-1"></el-option>
          <el-option :label='$t("common.Unaudited")' value="3"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          v-model="selectData.times"
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
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
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("LiveBroadcast.name")' align="center" prop="realname"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                v-if="scope.row.status==3"
                type="text"
                size="small"
                @click="seeUserInfo(scope.row)"
              >{{$t("common.to_examine")}}</el-button>
              <el-button v-else type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
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

    <RealnameCertify :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>


<script>
export default {
  components: {
    RealnameCertify: () => import("../../components/RealnameCertify")
  },
  data() {
    return {
      loading: false,
      tableData: [],
      selectData: {
        id: "",
        nickname: "",
        audit_status: "",
        times: []
      },
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      status: false,
      id: 0,
      row: null,
      uid: ""
    };
  },
  created() {
    this.getData(1);
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
      this.selectData.id = "";
      this.selectData.nickname = "";
      this.selectData.audit_status = "";
      this.selectData.times = [];
    },
    seeUserInfo(row) {
      this.status = true;
      this.id = row.id;
      this.uid = row.uid;
      window.localStorage.setItem("real_id", this.id);
      window.localStorage.setItem("real_uid", this.uid);
    },
    changeStatus(data) {
      this.status = data;
      this.getData();
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    //1：获取列表 2：导出
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/live/certify", {
          uid: this.selectData.id,
          nickname: this.selectData.nickname,
          audit_status: this.selectData.audit_status,
          upload_time_left: this.selectData.times[0],
          upload_time_right: this.selectData.times[1],
          page: this.page,
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.list;
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

<style lang="scss" scoped>
.realnamelist {
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
