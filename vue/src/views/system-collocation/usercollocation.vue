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
          <el-table-column label="Id" prop="id" align="center"></el-table-column>
          <el-table-column :label='$t("resources.vip_name")' align="center" prop="rec_name"></el-table-column>
          <el-table-column :label='$t("money.price")' align="center" prop="amount"></el-table-column>
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
        <el-form-item :label='$t("resources.vip_name")' :label-width="formLabelWidth">
          <el-input v-model="form.rec_name" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.price")' :label-width="formLabelWidth">
          <el-input v-model="form.amount" autocomplete="off"></el-input>
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
    // 确认编辑普通用戶配置
    editUserInfo() {
      this.$http
        .post("/other/user_edit", {
          id: this.form.id,
          rec_name: this.form.rec_name,
          amount: this.form.amount,
          money:this.form.money,
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible = false;
        });
    },
    // 获取普通用戶配置列表
    getData() {
      this.loading = true;
      this.$http.post("other/user_list").then(res => {
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



