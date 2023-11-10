<template>
  <div class="exchangeRate">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("resources.foreignExchange")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.foreignExchange">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option :label='$t("money.RMB")' value="CNY"></el-option>
          <el-option :label='$t("money.Euro")' value="EUR"></el-option>
          <el-option :label='$t("money.GBP")' value="GBP"></el-option>
          <el-option :label='$t("money.dollar")' value="USD"></el-option>
          <el-option :label='$t("money.Yen")' value="JPY"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("resources.currency")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.currency">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option label="CNY" value="CNY"></el-option>
          <el-option label="EUR" value="EUR"></el-option>
          <el-option label="GBP" value="GBP"></el-option>
          <el-option label="USD" value="USD"></el-option>
          <el-option label="JPY" value="JPY"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
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
          <el-table-column :label='$t("resources.foreignExchange")' align="center" prop="title"></el-table-column>
          <el-table-column :label='$t("resources.currency")' align="center" prop="currency"></el-table-column>
          <el-table-column :label='$t("resources.EURCexchange")' align="center" prop="eurc_exchange_rate"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0" style="color:red">{{$t("jurisdiction.Discontinue_use")}}</span>
              <span v-if="scope.row.status==1">{{$t("money.inuse")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
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
        <el-form-item :label='$t("resources.foreignExchange")' :label-width="formLabelWidth">
          <el-input v-model="form.currency" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.EURCexchange")' :label-width="formLabelWidth">
          <el-input v-model="form.eurc_exchange_rate" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("common.status")' :label-width="formLabelWidth">
          <el-radio v-model="form.status" label="1">{{$t("jurisdiction.Enable")}}</el-radio>
          <el-radio v-model="form.status" label="0">{{$t("money.Disable")}}</el-radio>
        </el-form-item>
        <el-form-item :label='$t("money.Remarks")' :label-width="formLabelWidth">
          <el-input v-model="form.remark" type="textarea" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="saveExchangeRate">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data() {
    return {
      add: "添加",
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      selectData: {
        foreignExchange: "",
        currency: ""
      },
      form: {
        currency: "",
        remark: "",
        eurc_exchange_rate: "",
        msq_exchange_rate: "",
        id: "",
        status: ""
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
    clear() {
      this.selectData.foreignExchange = "";
      this.selectData.currency = "";
    },
    editInfo(id = 0) {
      this.id = id;
      this.form.id = id;
      this.add = id ? "修改" : "添加";
      this.dialogFormVisible = true;
      if (id > 0) {
        this.info();
      }
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
    },
    getData() {
      this.loading = true;
      this.$http
        .post("/trade/exchange_rate_list", {
          page: this.page,
          foreigne_exchange: this.selectData.foreignExchange,
          currency: this.selectData.currency
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
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
    saveExchangeRate() {
      this.form.id = this.id;
      this.$http
        .post("/trade/save_exchange_rate", {
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



