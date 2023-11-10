
<template>
  <div class="pricelist">
    <div class="whiteBg">
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%"
        >
          <el-table-column :label='$t("resources.foreignExchange")' align="center" prop="coin"></el-table-column>
          <el-table-column :label='$t("resources.Fullname")' align="center" prop="full_name"></el-table-column>
          <el-table-column :label='$t("resources.type")' align="center" prop="coin_type"></el-table-column>
          <el-table-column :label='$t("money.price")' align="center" prop="price"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="editUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>
    <!-- 弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("resources.foreignExchange")' :label-width="formLabelWidth">
          <el-input type="input" v-model="form.coin" autocomplete="off" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.Fullname")' :label-width="formLabelWidth">
          <el-input type="input" v-model="form.fullname" autocomplete="off" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.type")' :label-width="formLabelWidth">
          <el-input type="input" v-model="form.type" autocomplete="off" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.price")' :label-width="formLabelWidth">
          <el-input type="input" v-model="form.price" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="confirmedit">{{$t("confirm.ok")}}</el-button>
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
        coin: "",
        fullname: "",
        type: "",
        price: ""
      },
      formLabelWidth: "120px"
    };
  },
  created() {
    this.getData();
  },
  methods: {
    editUserInfo(row) {
      this.dialogFormVisible = true;
      this.form.coin = row.coin;
      this.form.fullname = row.full_name;
      this.form.type = row.coin_type;
      this.form.price = row.price;
    },
    //编辑
    confirmedit() {
      this.$http
        .post("/resource/priced_edit", {
          coin: this.form.coin,
          price: this.form.price
        })
        .then(res => {
          let msg = res.msg;
          this.dialogFormVisible = false;
          this.getData();
          this.$message(msg);
        });
    },
    //  获取价格列表
    getData() {
      this.loading = true;
      this.$http.post("/resource/priced_list").then(res => {
        this.loading = false;
        this.tableData = res.data;
      });
    }
  }
};
</script>



<style lang="scss" scoped>
.pricelist {
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



