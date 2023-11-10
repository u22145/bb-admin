<template>
  <div class="coverlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="selectUserID"
          v-model="room_id"
          @keyup.enter.native="getList(1)"
        ></el-input>
        <label for="selectUserID">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="nickname"
          v-model="nickname"
          @keyup.enter.native="getList(1)"
        ></el-input>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="audit_status" @keyup.enter.native="getList(1)">
            <el-option :label='$t("common.all")' value="99"></el-option>
          <el-option :label='$t("common.Unaudited")' value="0"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
        <label>{{$t("common.Creation_time")}}</label>
        &nbsp;
        <el-date-picker
          v-model="uptime"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getList(1)">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getList(2)">{{$t("common.export")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table ref="multipleTable" :data="tableData" border style="width: 100%;margin-top: 30px">
          <el-table-column :label='$t("common.Username")' align="center" prop="room_id"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime" sortable></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="50px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                v-if="scope.row.status == 0"
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
    <el-dialog  :visible.sync="dialogTableVisible" width="60%">
      <el-table :data="gridData" v-loading="loading" border style="width:90%;margin:0 auto;">
        <el-table-column property="room_id" :label='$t("common.Username")' align="center"></el-table-column>
        <el-table-column property="nickname" :label='$t("common.nickname")' align="center"></el-table-column>
        <el-table-column property="new_pic" :label='$t("LiveBroadcast.new_cover")' align="center">
          <template slot-scope="scope">
            <img :src="scope.row.new_pic" width="40" height="40" class="head_pic" />
          </template>
        </el-table-column>
        <el-table-column property="top_pic" :label='$t("LiveBroadcast.old_cover")' align="center">
          <template slot-scope="scope">
            <img :src="scope.row.top_pic" width="40" height="40" class="head_pic" />
          </template>
        </el-table-column>
        <el-table-column property="status" :label='$t("common.status")' align="center">
          <template slot-scope="scope">
            <span v-if="scope.row.status==0">{{$t("common.Unaudited")}}</span>
            <span v-if="scope.row.status==1">{{$t("common.Audit_pass")}}</span>
            <span v-if="scope.row.status==2">{{$t("common.Audit_failed")}}</span>
            <!-- <span v-if="scope.row.status==3">已屏蔽</span> -->
          </template>
        </el-table-column>
        <el-table-column property="uptime" :label='$t("common.Creation_time")' align="center" min-width="120px"></el-table-column>
        <el-table-column :label='$t("common.operation")' align="center">
          <template slot-scope="scope">
            <el-button type="text" size="small" @click="check(scope.row.id, 1, scope.row.pic)">{{$t("common.adopt")}}</el-button>
            <el-button type="text" size="small" @click="check(scope.row.id, 2)">{{$t("common.not_pass")}}</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      tableData: [],
      audit_status: "",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      status: false,
      id: "",
      row: null,
      room_id: "",
      nickname: null,
      uptime: [],
      dialogTableVisible: false,
      gridData: [],
      uid: ""
    };
  },
  created() {
    this.getList(1);
  },
  methods: {
    search() {
      this.page = 1;
      this.getList(1);
    },
    // 1：通过 2：不通过
    check(id, status, new_pic = "") {
      this.$http
        .post("/live/check_live", {
          id: id,
          room_id: this.uid,
          status: status,
          new_pic: new_pic
        })
        .then(res => {
          if (res.status != 1) {
            this.$message({
              type: "error",
              message: 'ERROR'
            });
          } else {
            this.$message({
              type: "success",
              message: 'SUCCESS'
            });
            this.dialogTableVisible = false;
            this.getList();
          }
        });
    },

    clear() {
      this.room_id = "";
      this.nickname = "";
      this.start_date = "";
      this.uptime = [];
      this.audit_status = "";
    },
    // 审核
    seeUserInfo(row) {
      this.id = row.id;
      this.uid = row.room_id;
      this.$http
        .post("/live/live_info", {
          id: row.id
        })
        .then(res => {
          if (res.status != 1) {
            this.$message({
              type: "error",
              message: 'ERROR'
            });
          } else {
            this.gridData = res.data;
            this.dialogTableVisible = true;
          }
        });
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getList(1);
    },
    //1：获取列表 2：导出
    getList(exp = 1) {
      this.loading = true;
      this.$http
        .post("/live/live_pic_list", {
          page: this.page,
          nickname: this.nickname,
          room_id: this.room_id,
          status: this.audit_status,
          start_date: this.uptime[0],
          end_date: this.uptime[1],
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

<style lang="scss" scoped>
.coverlist {
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
