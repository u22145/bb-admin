<template>
  <div class="noticelist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="select_status" @keyup.enter.native="getData">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option :label='$t("resources.shelf")' value="1"></el-option>
          <el-option :label='$t("resources.on_shelf")' value="2"></el-option>
        </el-select>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="uptime"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="addMessage">{{$t("common.add")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column label="ID" align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("resources.title")' align="center" prop="title"></el-table-column>
          <el-table-column :label='$t("resources.content")' align="center" prop="content"></el-table-column>
          <el-table-column :label='$t("money.Remarks")' align="center" prop="remark"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0" style="color:red">{{$t("resources.shelf")}}</span>
              <span v-if="scope.row.status==1">{{$t("resources.on_shelf")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="150px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="seeUserInfo(scope.row)"
                v-show="scope.row.status!=1"
              >{{$t("common.modify")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="editUserInfo(scope.row.id,'up')"
                v-if="scope.row.status==0"
              >{{$t("resources.shelf1")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="editUserInfo(scope.row.id,'down')"
                v-if="scope.row.status==1"
              >{{$t("resources.shelf2")}}</el-button>
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
    <!-- 修改公告弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item label='$t("resources.title")' :label-width="formLabelWidth">
          <el-input v-model="form.title" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="$t('resources.content')" :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.info" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="$t('status.status')" :label-width="formLabelWidth">
          <el-radio-group v-model="form.add_status">
            <!-- 改成待上架 -->
            <el-radio label="down">{{$t('resources.shelf1')}}</el-radio>
            <el-radio label="up">{{$t('resources.shelf2')}}</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="$t('money.Remarks')" :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.remark" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" @click="saveMessageInfo">{{$t('common.ok')}}</el-button>
      </div>
    </el-dialog>
    <!-- 添加公告弹窗 -->
    <el-dialog  :visible.sync="addMsg">
      <el-form :model="form">
        <el-form-item :label='$t("resources.title")' :label-width="formLabelWidth">
          <el-input v-model="form.title" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.content")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.info" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("common.status")' :label-width="formLabelWidth">
          <el-radio-group v-model="form.add_status">
            <!-- 改成待上架 -->
            <el-radio label="down">{{$t("resources.shelf1")}}</el-radio>
            <el-radio label="up">{{$t("resources.shelf2")}}</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label='$t("money.Remarks")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.remark" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="addMsg = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="addPost">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      addMsg: false,
      form: {
        info: "",
        add_status: "down",
        title: "",
        remark: ""
      },
      formLabelWidth: "120px",
      page: 1, //当前页
      limit: this.$store.state.adminPageSize, //每页条数
      total: 1, //总共条数
      select_status: "",
      status: "",
      id: "",
      uptime: [],
      start_time: "",
      end_time: "",
      add_times: [],
      save_times: []
    };
  },
  created() {
    this.getData();
  },

  methods: {
    // 清除筛选
    clear() {
      this.select_status = "";
      this.uptime = [];
    },

    seeUserInfo(row) {
      this.id = row.id;
      this.form.info = row.content;
      this.form.title = row.title;
      this.form.remark = row.remark;
      this.dialogFormVisible = true;
    },
    changeStatus(data) {
      this.status = data;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 获取数据
    getData() {
      this.loading = true;
      this.$http
        .post("/system/message_list", {
          status: this.select_status,
          start_time: this.start_time,
          end_time: this.start_time,
          page: this.page
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    },
    // 编辑
    editUserInfo: function(id, status) {
      this.$http
        .post("/system/message_pass", {
          id: id,
          status: status
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: res.status == 1 ? "success" : "error"
          });
          if (res.status == 1) {
            this.getData();
          }
        });
    },
    // 确定修改公告
    saveMessageInfo() {
      this.$http
        .post("/system/save_message_info", {
          id: this.id,
          status: this.form.add_status,
          info: this.form.info,
          title: this.form.title,
          remark: this.form.remark
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: res.status == 1 ? "success" : "error"
          });
          if (res.status == 1) {
            this.getData();
          }
          this.dialogFormVisible = false;
        });
    },
    addMessage() {
      this.addMsg = true;
    },
    // 确定添加公告
    addPost() {
      if (this.form.info == "") {
        this.$message({
          message: "Not Empty！",
          type: "error"
        });
        return false;
      }
      this.$http
        .post("/system/add_message", {
          status: this.form.add_status,
          info: this.form.info,
          title: this.form.title,
          remark: this.form.remark
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: res.status == 1 ? "success" : "error"
          });
          if (res.status == 1) {
            this.getData();
          }
          this.addMsg = false;
          this.addform.info = "";
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.noticelist {
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



      