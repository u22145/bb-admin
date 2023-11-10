<template>
  <div class="LabourUnionlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="LabourUnionID">{{$t("LabourUnion.PresidentID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="LabourUnionID"
          v-model="query_data.owner_uid"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="phone">{{$t("jurisdiction.Cell_phone_number")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="phone"
          v-model="query_data.mobile"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="ID">{{$t("LabourUnion.GuildID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="ID"
          v-model="query_data.id"
          @keyup.enter.native="getData"
        ></el-input>
      </div>
      <div class="screen">
        <label for="realname">{{$t("LabourUnion.Real_name")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="realname"
          v-model="query_data.realname"
          @keyup.enter.native="getData"
        ></el-input>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="query_data.time"
          value-format="yyyy-MM-dd HH:mm:ss"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear"> {{$t("common.clear")}}</el-button>
        <el-button @click="addUserInfoShow">{{$t("common.add")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("LabourUnion.GuildID")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Guild_name")' align="center" prop="name"></el-table-column>
          <el-table-column :label='$t("LabourUnion.PresidentID")' align="center" prop="owner_uid"></el-table-column>
          <el-table-column :label='$t("LabourUnion.name")' align="center" prop="realname"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Cell_phone_number")' align="center" prop="mobile"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Split_ratio")' align="center" prop="share_rate"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Anchor_num")' align="center" prop="anchor_num"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==1" style="color:green">{{$t("LabourUnion.normal")}}</span>
              <span v-if="scope.row.status==3" style="color:red">{{$t("LabourUnion.Disbanded")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="200px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                v-if="scope.row.status!=3"
                @click="seeUserInfo(scope.row)"
              >{{$t("common.modify")}}</el-button>
              <p style="color:#606266;"
                v-if="scope.row.status==3"
              >{{$t("LabourUnion.Disbanded")}}</p>
              <el-button
                type="text"
                size="small"
                @click="changeStatus(scope.row.id,3)"
                v-if="scope.row.status!=3"
              >{{$t("LabourUnion.dissolution")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          @prev-click="handlePrevClick"
          @next-click="handleNextClick"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
        ></el-pagination>
      </div>
    </div>
    <!-- 编辑弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("LabourUnion.Guild_name")' :label-width="formLabelWidth">
          <el-input v-model="form.name" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("LabourUnion.PresidentID")' :label-width="formLabelWidth">
          <el-input v-model="form.owner_uid" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("LabourUnion.Real_name")' :label-width="formLabelWidth">
          <el-input v-model="form.realname" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.Cell_phone_number")' :label-width="formLabelWidth">
          <el-input v-model="form.mobile" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("LabourUnion.Split_ratio")' :label-width="formLabelWidth">
          <el-input v-model="form.share_rate" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" v-if="addoredit==1" @click="addUserInfo">{{$t("confirm.ok")}}</el-button>
        <el-button type="primary" v-else @click="editUserInfo">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>


<script>
export default {
  data() {
    return {
      loading: false,
      query_data: {
        owner_uid: "",
        time: [],
        mobile: "",
        id: "",
        realname: ""
      },
      tableData: [],
      addoredit: null, //1:添加，，0:修改
      dialogFormVisible: false,
      form: {
        name: "",
        owner_uid: "",
        realname: "",
        mobile: "",
        share_rate: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1
    };
  },
  created() {
    this.getData();
  },

  methods: {
    clear() {
      this.query_data.owner_uid = "";
      this.query_data.time = [];
      this.query_data.mobile = "";
      this.query_data.id = "";
      this.query_data.realname = "";
    },
    seeUserInfo(row) {
      this.addoredit = 0;
      this.form = row;
      this.dialogFormVisible = true;
    },
    editUserInfo() {
      this.$http
        .post("/sociaty/sociaty_edit", {
          id: this.form.id,
          name: this.form.name,
          owner_uid: this.form.owner_uid,
          realname: this.form.realname,
          mobile: this.form.mobile,
          share_rate: this.form.share_rate
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible = false;
        });
    },
    addUserInfoShow() {
      this.form.name = "";
      this.form.owner_uid = "";
      this.form.realname = "";
      this.form.mobile = "";
      this.form.share_rate = "";
      this.addoredit = 1;
      this.dialogFormVisible = true;
    },
    // 添加
    addUserInfo() {
      this.$http
        .post("/sociaty/sociaty_add", {
          name: this.form.name,
          owner_uid: this.form.owner_uid,
          realname: this.form.realname,
          mobile: this.form.mobile,
          share_rate: this.form.share_rate
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    // 解散公会
    changeStatus(id, status) {
      this.$http
        .post("/sociaty/sociaty_update_status", {
          id: id,
          status: status
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.getData();
        });
    },
    // 分页
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    handlePrevClick(val) {
      this.page = val;
      this.getData();
    },
    handleNextClick(val) {
      this.page = val;
      this.getData();
    },
    // 获取列表
    getData() {
      this.loading = true;
      this.$http
        .post("/sociaty/sociaty_list", {
          owner_uid: this.query_data.owner_uid,
          uptime_start: this.query_data.time[0],
          uptime_end: this.query_data.time[1],
          id: this.query_data.id,
          mobile: this.query_data.mobile,
          realname: this.query_data.realname,
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page_no;
          this.limit = res.data.page_size;
          this.total = res.data.total;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.LabourUnionlist {
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



