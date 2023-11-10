<template>
  <div class="LabourUnionanchorlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="anchorID">{{$t("LabourUnion.AnchorID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="anchorID"
          v-model="query_data.uid"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="phone">{{$t("jurisdiction.Cell_phone_number")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="phone"
          v-model="query_data.mobile"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="LabourUnionanchorID">{{$t("LabourUnion.PresidentID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="LabourUnionanchorID"
          v-model="query_data.owner_uid"
          @keyup.enter.native="getData"
        ></el-input>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("LabourUnion.Guild")}}</label>&nbsp;
        <el-select placeholder v-model="query_data.sociaty_id" @keyup.enter.native="getData">
          <el-option v-for="v in sociaty_list" :label="v.name" :value="v.id" :key="v.id"></el-option>
        </el-select>
        <label for="realname">{{$t("LabourUnion.Real_name")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="realname"
          v-model="query_data.realname"
          @keyup.enter.native="getData"
        ></el-input>
        <label>{{$t("LabourUnion.Adding_time")}}</label>&nbsp;
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
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
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
          <el-table-column :label='$t("LabourUnion.AnchorID")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Real_name")' align="center" prop="realname"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Cell_phone_number")' align="center" prop="mobile"></el-table-column>
          <el-table-column :label='$t("LabourUnion.PresidentID")' align="center" prop="sociaty_owner_id"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Guild")' align="center" prop="sociaty_name"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Split_ratio")' align="center" prop="share_rate"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Adding_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
              <el-button type="text" size="small" @click="changeStatus(scope.row.id)">{{$t("LabourUnion.Kick_out_guild")}}</el-button>
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
    <!-- 编辑 -->
    <el-dialog  :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("LabourUnion.AnchorID")' :label-width="formLabelWidth">
          <el-input v-model="form.id" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("LabourUnion.Split_ratio")' :label-width="formLabelWidth">
          <el-input v-model="form.share_rate" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("LabourUnion.Guild")' :label-width="formLabelWidth">
          <el-select v-model="form.sociaty_id" placeholder>
            <el-option v-for="v in sociaty_list" :label="v.name" :value="v.id" :key="v.id"></el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <!-- 添加 -->
        <el-button type="primary" v-if="addoredit==1" @click="addUserInfo">{{$t("confirm.ok")}}</el-button>
        <!-- 编辑 -->
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
        uid: "",
        realname: "",
        sociaty_id: ""
      },
      tableData: [],
      addoredit: null, //1:添加，，0:修改
      dialogFormVisible: false,
      form: {
        id: "",
        share_rate: "",
        sociaty_id: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      sociaty_list: {}
    };
  },
  created() {
    this.getData();
    this.getSociaty();
  },

  methods: {
    clear() {
      this.query_data.owner_uid = "";
      this.query_data.time = [];
      this.query_data.mobile = "";
      this.query_data.uid = "";
      this.query_data.realname = "";
      this.query_data.sociaty_id = "";
    },
    seeUserInfo(row) {
      this.addoredit = 0;
      this.form.id = row.uid;
      this.form.share_rate = row.share_rate;
      this.form.sociaty_id = row.sociaty_id;
      this.dialogFormVisible = true;
    },
    getSociaty() {
      this.$http.post("/sociaty/sociaty_list", {page_size: 100}).then(res => {
        this.sociaty_list = res.data.data;
      });
    },
    // 编辑
    editUserInfo() {
      this.$http
        .post("/sociaty/sociaty_anchor_edit", {
          uid: this.form.id,
          sociaty_id: this.form.sociaty_id,
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
    addUserInfoShow() {
      this.form.id = "";
      this.form.share_rate = "";
      this.form.sociaty_id = "";
      this.addoredit = 1;
      this.dialogFormVisible = true;
    },
    // 添加主播
    addUserInfo() {
      this.$http
        .post("/sociaty/sociaty_anchor_add", {
          sociaty_id: this.form.sociaty_id,
          uid: this.form.id,
          share_rate: this.form.share_rate
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: res.status == 1?"success":'error'
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    // 踢出公会
    changeStatus(data) {
      this.$http
        .post("/sociaty/sociaty_anchor_kick_out", {
          uid: data
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
    handlePrevClick(val) {
      this.page = val;
      this.getData();
    },
    handleNextClick(val) {
      this.page = val;
      this.getData();
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 获取公会主播列表
    getData() {
      this.loading = true;
      this.$http
        .post("/sociaty/sociaty_anchor_list", {
          owner_uid: this.query_data.owner_uid,
          join_sociaty_start: this.query_data.time[0],
          join_sociaty_end: this.query_data.time[1],
          uid: this.query_data.uid,
          mobile: this.query_data.mobile,
          realname: this.query_data.realname,
          sociaty_id: this.query_data.sociaty_id,
          page: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.LabourUnionanchorlist {
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



