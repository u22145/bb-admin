<template>
  <div class="commentlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="uid"
          id="selectUserID"
          @keyup.enter.native="getList"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          v-model="nickname"
          style="width: 200px"
          id="nickname"
          @keyup.enter.native="getList"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="rew_status" @keyup.enter.native="getList">
          <el-option :label='$t("common.all")' value="99"></el-option>
          <el-option :label='$t("common.Unaudited")' value="0"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="ctime"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
          id="dateRange"
        ></el-date-picker>
        <label for="videoID">{{$t("papa.tid")}}</label>&nbsp;
        <el-input
          v-model="tid"
          style="width: 200px"
          id="videoID"
          @keyup.enter.native="getList"
        ></el-input>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button type="primary" @click="changeStatus">{{$t("common.batch_pass")}}</el-button>
        <el-button @click="exportExcel">{{$t("common.export")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          @selection-change="handleSelectionChange"
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :selectable="checkboxT" type="selection" width="55" align="center"></el-table-column>
          <el-table-column :label='$t("commentlist.Comment_ID")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("papa.tid")' align="center" prop="tid"></el-table-column>
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("commentlist.Comment_content")' align="center" min-width="150px">
            <template slot-scope="scope">
              <p>{{scope.row.review}}</p>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column  :label='$t("common.operation")' align="center" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="changeStatus(scope.row)"
                v-if="scope.row.status==0||scope.row.status==2"
              >{{$t("common.adopt")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="writepass(scope.row)"
                v-if="scope.row.status==0||scope.row.status==1"
              >{{$t("common.not_pass")}}</el-button>
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
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

    <!-- 屏蔽弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("confirm.reason")'>
          <el-input type="textarea" v-model="form.name"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="passStatus">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <!-- 啪啪评论详情页 -->
    <Papacommentdetails :status="status" :id="id" v-on:goback="goback" />
  </div>
</template>


<script>
export default {
  components: {
    Papacommentdetails: () => import("../../components/papacommentdetails")
  },
  data() {
    return {
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      form: {
        name: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      nickname: "",
      uid: "",
      rew_status: "",
      tid: "",
      ctime: [],
      status: false,
      id: "",
      row: null,
      papa_id: "",
      papa_arr: "",
      suid: ""
    };
  },
  created() {
    this.getList();
  },
  methods: {
    search() {
      this.page = 1;
      this.getList();
    },
    clear() {
      this.nickname = "";
      this.uid = "";
      this.rew_status = "";
      this.tid = "";
      this.ctime = [];
    },
    seeUserInfo(row) {
      this.id = row.id;
      this.status = true;
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getList();
    },
    goback(data) {
      this.status = data;
    },
    getList() {
      this.loading = true;
      this.$http
        .post("/blog/rev_list", {
          page_no: this.page,
          module: 2,
          uid: this.uid,
          nickname: this.nickname,
          status: this.rew_status,
          tid: this.tid,
          starttime: this.ctime[0],
          endtime: this.ctime[1]
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.list;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    },
    exportExcel() {
      this.$http
        .post("/blog/rev_list", {
          page_no: this.page,
          module: 2,
          uid: this.uid,
          nickname: this.nickname,
          status: this.rew_status,
          tid: this.tid,
          starttime: this.ctime[0],
          endtime: this.ctime[1],
          exp: "exp"
        })
        .then(res => {
          if (res.status == 1) {
            window.open(`${window.location.origin}${res.data}`);
          } else {
            this.$message.error(res.msg);
          }
        });
    },
    //审核  批量审核
    changeStatus(row) {
      let review_id;
      if (row.id) {
        review_id = row.id;
        this.papa_arr = row.tid;
      } else {
        review_id = this.rev_id;
      }
      this.$http
        .post("/papa/rev_update", {
          review_id: review_id,
          status: 1,
          description: "",
          papa_arr: this.papa_arr,
          uid: row.uid
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
          } else {
            this.$message.error(res.msg);
          }
          this.getList();
        });

      this.dialogFormVisible = false;
    },
    handleSelectionChange(val) {
      this.rev_id = "";
      this.papa_arr = "";
      this.multipleSelection = val;
      this.multipleSelection.map(item => {
        this.rev_id += item.id + ",";
        this.papa_arr += item.tid + ",";
      });
      this.rev_id = this.rev_id.substr(0, this.rev_id.length - 1);
      this.papa_arr = this.papa_arr.substr(0, this.papa_arr.length - 1);
    },
    //复选框
    checkboxT(row, rowIndex) {
      if (row.status == 1 || row.status == 3) {
        return false; //禁用
      } else {
        return true; //不禁用
      }
    },
    //屏蔽审核
    passStatus() {
      this.$http
        .post("/papa/rev_update", {
          review_id: this.httpid,
          papa_arr: this.papa_arr,
          status: 2,
          description: this.form.name,
          uid: this.suid
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
          } else {
            this.$message.error(res.msg);
          }
          this.getList();
          this.dialogFormVisible = false;
        });
    },
    //屏蔽审核弹窗
    writepass(row) {
      this.dialogFormVisible = true;
      this.httpid = row.id;
      this.papa_arr = row.tid;
      this.suid = row.uid;
    }
  }
};
</script>


<style lang="scss" scoped>
.commentlist {
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
  p {
    white-space: nowrap;
    width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .tableCon:after {
    content: "";
    display: block;
    clear: both;
  }
}
</style>


