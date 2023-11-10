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
          <el-table-column label="ID" align="center" prop="id"></el-table-column>
          <el-table-column label="IP" align="center" prop="ip"></el-table-column>
          <el-table-column :label='$t("resources.port")' align="center" prop="port"></el-table-column>
          <el-table-column :label='$t("jurisdiction.passWord")' align="center" prop="pwd"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0" style="color:red">{{$t("jurisdiction.Discontinue_use")}}</span>
              <span v-if="scope.row.status==1" style="color:green">{{$t("jurisdiction.Enable")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.active_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="pass(scope.row.id,'pass')"
                v-if="scope.row.status==0"
              >{{$t("jurisdiction.Discontinue_use")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="pass(scope.row.id,'ben')"
                v-if="scope.row.status==1"
              >{{$t("jurisdiction.Enable")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>
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
    // 0：启用 1：启用
    pass(id, status) {
      this.$http
        .post("other/update_vpn_status", {
          id: id,
          status: status
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.getData();
        });
    },
    // 获取VIP配置列表
    getData() {
      this.loading = true;
      this.$http.post("other/vpn_list").then(res => {
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



