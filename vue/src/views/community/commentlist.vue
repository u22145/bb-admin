<template>
  <div class="commentlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="uid">{{$t("common.Username")}}</label>&nbsp;
        <el-input style="width: 200px" id="uid" v-model="uid" @keyup.enter.native="getList"></el-input>
        <label for="tid">{{$t("commentlist.tid")}}</label>&nbsp;
        <el-input style="width: 200px" id="tid" v-model="tid" @keyup.enter.native="getList"></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="nickname"
          v-model="nickname"
          @keyup.enter.native="getList"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="ustatus" @keyup.enter.native="getList">
          <el-option :label='$t("common.all")' value="99"></el-option>
          <el-option :label='$t("common.Unaudited")' value="0"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="times"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button
          type="primary"
          class="fr"
          @click="search"
          style="padding: 8px 30px"
        >{{$t("common.search")}}</el-button>
      </div>
    </div>

    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button type="primary" @click="changeStatus">{{$t("common.batch_pass")}}</el-button>
        <el-button @click="exportExecel">{{$t("common.export")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          v-loading="loading"
          ref="multipleTable"
          :data="tableData"
          @selection-change="handleSelectionChange"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :selectable="checkboxT" type="selection" width="55" align="center"></el-table-column>
          <el-table-column :label='$t("commentlist.Comment_ID")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("commentlist.tid")' align="center" prop="tid"></el-table-column>
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column
            :label='$t("commentlist.Comment_content")'
            align="center"
            min-width="150px"
          >
            <template slot-scope="scope">
              <p>{{scope.row.review}}</p>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column
            :label='$t("common.operation")'
            align="center"
            class-name="operation"
            min-width="100px"
          >
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="changeStatus(scope.row)"
                v-if="scope.row.status ==0||scope.row.status ==2||scope.row.status ==3"
              >{{$t("common.adopt")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="writepass(scope.row)"
                v-if="scope.row.status ==0||scope.row.status ==1"
              >{{$t("common.not_pass")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="seeUserInfo(scope.row)"
              >{{$t("common.See")}}</el-button>
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

    <!-- 不通过弹窗 -->
    <el-dialog :title='$t("common.not_pass")' :visible.sync="dialogFormVisible" width="40%">
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

    <!-- 评论详情 -->
    <Commentdetails :status="status" :id="id" v-on:goback="goback" />
  </div>
</template>


<script>
export default {
  components: {
    Commentdetails: () => import("../../components/commentdetails")
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
      ustatus: "",
      nickname: "",
      uid: "",
      tid: "",
      httpid: 0,
      rev_id: "",
      multipleSelection: [],
      blog_arr: 0,
      status: false,
      id: "",
      row: null,
      times: [],
      suid:'',
    };
  },
  mounted() {
    this.getList();
  },
  methods: {
    search() {
      this.page = 1;
      this.getList();
    },
    clear() {
      this.ustatus = "";
      this.nickname = "";
      this.uid = "";
    },
    goback(data) {
      this.status = data;
    },
    seeUserInfo(row) {
      this.id = row.id;
      this.status = true;
    },
    //审核  批量审核
    changeStatus(row) {
      let review_id;
      if (row.id) {
        review_id = row.id;
        this.blog_arr = row.tid;
      } else {
        review_id = this.rev_id;
      }
      this.$http
        .post("/blog/rev_update", {
          review_id: review_id,
          blog_arr: this.blog_arr,
          status: 1,
          description: "",
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
    //屏蔽审核
    passStatus() {
      this.$http
        .post("/blog/rev_update", {
          review_id: this.httpid,
          blog_arr: this.blog_arr,
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
    //不通过审核弹窗
    writepass(row) {
      this.dialogFormVisible = true;
      this.httpid = row.id;
      this.blog_arr = row.tid;
      this.form.name = "";
      this.suid = row.uid;
    },
    //  导出
    exportExecel() {
      this.$http
        .post("/blog/rev_list", {
          page_no: this.page,
          module: 1,
          uid: this.uid,
          nickname: this.nickname,
          status: this.ustatus,
          exp: "exp",
          starttime: this.times[0],
          endtime: this.times[1]
        })
        .then(res => {
          if (res.status == 1) {
            window.open(`${window.location.origin}${res.data}`);
          } else {
            this.$message.error(res.msg);
          }
        });
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getList();
    },
    // 获取评论列表
    getList(exp = "list") {
      this.loading = true;
      this.$http
        .post("/blog/rev_list", {
          page_no: this.page,
          module: 1,
          uid: this.uid,
          nickname: this.nickname,
          status: this.ustatus,
          starttime: this.times[0],
          endtime: this.times[1],
          tid: this.tid
        })
        .then(res => {
          if (res.status == 1) {
            this.loading = false;
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
          }
        });
    },
    // 选框
    handleSelectionChange(val) {
      this.rev_id = "";
      this.blog_arr = "";
      this.multipleSelection = val;
      this.multipleSelection.map(item => {
        this.rev_id += item.id + ",";
        this.blog_arr += item.tid + ",";
      });
      this.rev_id = this.rev_id.substr(0, this.rev_id.length - 1);
      this.blog_arr = this.blog_arr.substr(0, this.blog_arr.length - 1);
    },
    //复选框
    checkboxT(row, rowIndex) {
      if (row.status == "审核通过") {
        return false; //禁用
      } else {
        return true; //不禁用
      }
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

