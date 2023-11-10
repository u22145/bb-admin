<template>
  <div class="distribution">
    <div class="whiteBg">
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%"
        >
          <el-table-column label="ID" align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("resources.level")' align="center" prop="title"></el-table-column>
          <el-table-column :label='$t("resources.rate")' align="center" prop="rate"></el-table-column>
          <el-table-column :label='$t("resources.exp_rate")' align="center" prop="exp_rate"></el-table-column>
          <el-table-column :label='$t("resources.uptime")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("main.save")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>

    <!-- 版本管理弹窗 -->
    <el-dialog :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("common.level")' :label-width="formLabelWidth">{{ form.name }}</el-form-item>
        <el-form-item :label='$t("common.rate")' :label-width="formLabelWidth">
          <el-input v-model="form.rate" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("common.exp_rate")' :label-width="formLabelWidth">
          <el-input v-model="form.exp_rate" autocomplete="off"></el-input>
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
        rate: "",
        exp_rate: "",
        id: "",
        name: ""
      },
      formLabelWidth: "120px",
      id: ""
    };
  },
  created() {
    this.getData();
  },
  methods: {
    seeUserInfo(row) {
      this.id = row.id;
      this.form.rate = row.rate;
      this.dialogFormVisible = true;
      this.form.exp_rate = row.exp_rate;
      this.form.name = row.title;
      this.form.id = row.id;
    },
    // 修改版本信息
    editUserInfo() {
      if (this.form.rate == false) {
        this.$message.error("rate no empty");
        return false;
      }
      this.$http
        .post("/other/pyra_edit", {
          data: this.form
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
            this.dialogFormVisible = false;
            this.getData(1);
          } else {
            this.$message.error(res.msg);
          }
        });
    },
    // 获取版本列表
    getData() {
      this.loading = true;
      this.$http.post("/other/pyra_list").then(res => {
        this.loading = false;
        this.tableData = res.data;
      });
    }
  }
};
</script>


<style lang="scss" scoped>
.distribution {
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



