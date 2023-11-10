<template>
  <div class="exchangeRate">
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="editInfo(0)">{{$t("common.add")}}</el-button>
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
          <el-table-column :label='$t("resources.Fullname")' align="center" prop="title"></el-table-column>
          <el-table-column :label='$t("resources.Abbreviation")' align="center" prop="currency"></el-table-column>
          <el-table-column :label='$t("resources.Company")' align="center" prop="symbol"></el-table-column>
          <el-table-column :label='$t("resources.type")' align="center" prop="type"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0" style="color:red">{{$t("jurisdiction.Discontinue_use")}}</span>
              <span v-if="scope.row.status==1">{{$t("resources.Enabling")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                v-if="scope.row.status == 0"
                type="text"
                size="small"
                @click="saveLegalCurrencyStatus(scope.row.id,1)"
              >{{$t("jurisdiction.Enable")}}</el-button>
              <el-button
                v-if="scope.row.status == 1"
                type="text"
                size="small"
                @click="saveLegalCurrencyStatus(scope.row.id,0)"
              >{{$t("money.Disable")}}</el-button>
              <el-button type="text" size="small" @click="editInfo(scope.row.id)">{{$t("common.modify")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[10]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
        ></el-pagination>
      </div>
    </div>
    <!-- 弹窗 -->
    <el-dialog :title="add" :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("resources.Fullname")' :label-width="formLabelWidth">
          <el-input v-model="form.title" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.Abbreviation")' :label-width="formLabelWidth">
          <el-input v-model="form.currency" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.type")' :label-width="formLabelWidth">
          <el-input v-model="form.type" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.Company")' :label-width="formLabelWidth">
          <el-input v-model="form.symbol" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="edit">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data() {
    return {
      add: "ADD",
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      form: {
        title: "",
        currency: "",
        type: "",
        symbol: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: 10,
      total: 1,
      id: ""
    };
  },
  created() {
    this.getData();
  },
  methods: {
    editInfo(id = 0) {
      this.id = id;
      this.dialogFormVisible = true;
      if (id > 0) {
        this.add = "SAVE";
        this.info();
      }
    },
    getData() {
      this.loading = true;
      this.$http
        .post("/trade/exchange_rate_list", {
          page: this.page
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    },
    saveLegalCurrencyStatus(id, status) {
      this.loading = true;
      this.$http
        .post("/trade/save_legal_currency_status", {
          id: id,
          status: status
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
            this.getData();
          } else {
            this.$message.error(res.msg);
          }
        });
    },
    info() {
      this.$http
        .post("/trade/exchange_rate_info", {
          id: this.id
        })
        .then(res => {
          this.form = res.data;
        });
    },
    edit() {
      this.$http
        .post("/trade/save_legal_currency", {
          data: this.form
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
            this.dialogFormVisible = false;
            this.getData();
          } else {
            this.$message.error(res.msg);
          }
        });
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    }
  }
};
</script>

<style lang="scss" scoped>
.exchangeRate {
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



