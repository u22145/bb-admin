<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%"
        >
          <el-table-column label="vip_Id" prop="vip_id" align="center"></el-table-column>
          <el-table-column  :label='$t("resources.type")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.vip_type==1">VIP</span>
              <span v-if="scope.row.vip_type==2">SVIP</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.vip_name")' align="center" prop="vip_name"></el-table-column>
          <el-table-column :label='$t("money.price")' align="center" prop="fee"></el-table-column>
          <el-table-column :label='$t("money.money")' align="center" prop="money"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>

    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("resources.type")' :label-width="formLabelWidth">
          <el-radio v-model="form.vip_type" label="1">VIP</el-radio>
          <el-radio v-model="form.vip_type" label="2">SVIP</el-radio>
        </el-form-item>
        <el-form-item :label='$t("resources.vip_name")' :label-width="formLabelWidth">
          <el-input v-model="form.vip_name" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.active_life")' :label-width="formLabelWidth">
          <el-select v-model="form.active_life" placeholder>
            <el-option :label='$t("resources.one")' value="30"></el-option>
            <el-option :label='$t("resources.two")' value="60"></el-option>
            <el-option :label='$t("resources.three")' value="90"></el-option>
            <el-option :label='$t("resources.six")' value="180"></el-option>
            <el-option :label='$t("resources.oneyear")' value="365"></el-option>
            <el-option :label='$t("resources.foreve")' value="25550"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.price")' :label-width="formLabelWidth">
          <el-input v-model="form.fee" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.money")' :label-width="formLabelWidth">
          <el-input v-model="form.money" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="editUserInfo()">{{$t("confirm.ok")}}</el-button>
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
      form: {
        vip_type: [],
        vip_name: "",
        active_life: "",
        fee: ""
      },
      formLabelWidth: "120px"
    };
  },
  created() {
    this.getData();
  },
  methods: {
    //编辑
    seeUserInfo(row) {
      this.form = row;
      this.dialogFormVisible = true;
    },
    // 确认编辑vip配置
    editUserInfo() {
      this.$http
        .post("/other/vip_edit", {
          vip_type: this.form.vip_type,
          vip_id: this.form.vip_id,
          vip_name: this.form.vip_name,
          fee: this.form.fee,
          money:this.form.money,
          active_life: this.form.active_life
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible = false;
        });
    },
    // 获取VIP配置列表
    getData() {
      this.loading = true;
      this.$http.post("other/vip_list").then(res => {
        this.loading = false;
        this.tableData = res.data;
      });
    }
  }
};
</script>



<style lang="scss" scoped>
.vipcollocation {
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



