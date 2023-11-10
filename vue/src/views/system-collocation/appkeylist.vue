<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="createAppkey">{{$t("common.add")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column label="platForm" align="center" prop="platform"></el-table-column>
          <el-table-column label="appKey" align="center" prop="appkey"></el-table-column>
          <el-table-column label="appSecret" align="center" prop="appsecret"></el-table-column>
          <el-table-column :label='$t("money.Remarks")' align="center" prop="comm"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="deleteAppkey(scope.row.appkey)">{{$t("common.delete")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>
    <!-- 创建授权弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("money.Remarks")' :label-width="formLabelWidth">
          <el-input v-model="form.comm" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="createPost">{{$t("confirm.ok")}}</el-button>
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
        comm: ""
      },
      formLabelWidth: "120px"
    };
  },
  created() {
    this.getData();
  },
  methods: {
    deleteAppkey(appkey) {
      this.$confirm("刪除操作不可以恢復，是否繼續？", "提示", {
        confirmButtonText: "確定",
        cancelButtonText: "取消",
        type: "warning"
      })
        .then(() => {
          this.$http
            .post("other/delete_appkey", {
              appkey: appkey
            })
            .then(res => {
              this.$message({
                type: res.status == 1 ? "success" : "error",
                message: res.msg
              });
              this.getData();
            });
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: "Cancel"
          });
        });
    },
    createAppkey() {
      this.dialogFormVisible = true;
    },
    // 确定授权
    createPost() {
      this.$http
        .post("other/create_appkey", {
          comm: this.form.comm
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    // 获取VIP配置列表
    getData() {
      this.loading = true;
      this.$http.post("other/appkey_list").then(res => {
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



