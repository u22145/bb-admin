<template>
  <div class="pushlist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("resources.receiver")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.receiver">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option :label='$t("resources.male")' value="1"></el-option>
          <el-option :label='$t("resources.female")' value="2"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.status">
          <el-option :label='$t("resources.push")' value="1"></el-option>
          <el-option :label='$t("resources.pushed")' value="0"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("resources.redirect")}}</label>&nbsp;
        <el-select placeholde v-model="selectData.redirect">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option label="APP" value="1"></el-option>
          <el-option label="H5" value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="selectData.times"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="seach">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="addpush">{{$t("common.add")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
         
          <el-table-column label="ID" align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("resources.pushContent")' align="center" prop="msg"></el-table-column>
          <el-table-column :label='$t("resources.receiver")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.receiver==0">{{$t("common.all")}}</span>
              <span v-if="scope.row.receiver==1">{{$t("resources.male")}}</span>
              <span v-if="scope.row.receiver==2">{{$t("resources.female")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.active")' align="center" prop="active">
            <template slot-scope="scope">
              <span v-if="scope.row.active==0">{{$t("common.all")}}</span>
              <span v-if="scope.row.active==1">{{$t("resources.tDay")}}</span>
              <span v-if="scope.row.active==2">{{$t("resources.week")}}</span>
              <span v-if="scope.row.active==3">{{$t("resources.month")}}</span>
              <span v-if="scope.row.active==4">{{$t("resources.Tmonth")}}</span>
              <span v-if="scope.row.active==5">{{$t("resources.year")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.redirect")' align="center">
            <template slot-scope="scope">{{scope.row.redirect==1?'app':'H5'}}</template>
          </el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="push_time"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0">{{$t("resources.pushed")}}</span>
              <span v-if="scope.row.status==1" style="color:pink">{{$t("resources.push")}}</span>
              <span v-if="scope.row.status==2" style="color:red">{{$t("common.delete")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="editUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
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
    <!-- 弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("resources.pushContent")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.msg" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.receiver")' :label-width="formLabelWidth">
          <el-select v-model="form.receiver" placeholder>
            <el-option :label='$t("common.all")' value="0"></el-option>
            <el-option :label='$t("resources.male")' value="1"></el-option>
            <el-option :label='$t("resources.female")' value="2"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("resources.active")' :label-width="formLabelWidth">
          <el-select v-model="form.active" placeholder>
            <el-option :label='$t("common.all")' value="0"></el-option>
            <el-option :label='$t("resources.tDay")' value="1"></el-option>
            <el-option :label='$t("resources.week")' value="2"></el-option>
            <el-option :label='$t("resources.month")' value="3"></el-option>
            <el-option :label='$t("resources.Tmonth")' value="4"></el-option>
            <el-option :label='$t("resources.year")' value="5"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("resources.redirect")' :label-width="formLabelWidth">
          <el-select v-model="form.redirect" placeholder>
            <el-option :label='$t("common.all")' value="0"></el-option>
            <el-option label="APP" value="1"></el-option>
            <el-option label="H5" value="2"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("common.Creation_time")' :label-width="formLabelWidth">
          <el-date-picker
            v-model="form.push_time"
            type="datetime"
            value-format="yyyy-MM-dd HH:mm:ss"
          ></el-date-picker>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" v-if="editoradd==1" @click="confirmAdd">{{$t("confirm.ok")}}</el-button>
        <el-button type="primary" v-else @click="confirmEdit">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      selectData: {
        receiver: "",
        status: "",
        times: "",
        redirect: ""
      },
      tableData: [],
      dialogFormVisible: false,
      form: {
        msg: "",
        receiver: "",
        push_time: "",
        redirect: "",
        active: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      editoradd: null, //1:添加 0：编辑
      id: ""
    };
  },
  created() {
    this.getData();
  },

  methods: {
    clear() {
      this.selectData = {};
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    editUserInfo(row) {
      this.editoradd = 0;
      this.form = row;
      this.dialogFormVisible = true;
      this.id = row.id;
    },
    // 编辑推送
    confirmEdit() {
      this.$http
        .post("/resource/push_edit", {
          id: this.id,
          msg: this.form.msg,
          receiver: this.form.receiver,
          active: this.form.active,
          redirect: this.form.redirect,
          push_time: this.form.push_time
        })
        .then(res => {
          let msg = res.msg;
          this.dialogFormVisible = false;
          this.getData();
          this.$message(msg);
        });
    },
    addpush() {
      this.form = {};
      this.editoradd = 1;
      this.dialogFormVisible = true;
    },
    // 添加推送
    confirmAdd() {
      this.$http
        .post("/resource/push_add", {
          msg: this.form.msg,
          receiver: this.form.receiver,
          active: this.form.active,
          redirect: this.form.redirect,
          push_time: this.form.push_time
        })
        .then(res => {
          let msg = res.msg;
          this.dialogFormVisible = false;
          this.getData();
          this.$message(msg);
        });
    },
    //搜索
    seach() {
      this.page = 1;
      this.getData();
    },
    // 获取推送列表
    getData() {
      this.loading = true;
      this.$http
        .post("/resource/push_list", {
          receiver: this.selectData.receiver,
          status: this.selectData.status,
          start_time: this.selectData.times[0],
          end_time: this.selectData.times[1],
          redirect: this.selectData.redirect,
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page_no;
          this.total = res.data.total;
        });
    }
  }
};
</script>



<style lang="scss" scoped>
.pushlist {
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



