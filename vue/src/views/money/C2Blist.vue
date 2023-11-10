<template>
  <div class="C2Clist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("money.Choice")}}</label>&nbsp;
        <el-select
          :placeholder='$t("money.placeholder")'
          v-model="search_type"
        >
          <el-option :label='$t("money.Order_number")' value="order"></el-option>
          <el-option :label='$t("money.Buyer_account")' value="buy"></el-option>
          <el-option :label='$t("money.Seller_account")' value="sell"></el-option>
          <el-option :label='$t("money.Payee")' value="have"></el-option>
        </el-select>
        <el-input
          placeholder
          style="width: 200px"
          id="inputID"
          v-model="search_info"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("money.Trading_currency")}}</label>&nbsp;
        <el-select placeholder v-model="search_coin" @keyup.enter.native="getData">
          <el-option :label='$t("common.all")' value></el-option>
          <el-option label="EURC" value="eurc"></el-option>
          <el-option label="MSQ" value="msq"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("money.Appeal")}}</label>&nbsp;
        <el-select placeholder v-model="search_appeal" @keyup.enter.native="getData">
          <el-option :label='$t("money.yes")' value="yes"></el-option>
          <el-option :label='$t("money.no")' value="no"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="search_status" @keyup.enter.native="getData">
          <el-option :label='$t("money.buyerconfirm")' value="0"></el-option>
          <el-option :label='$t("money.Sellerconfirm")' value="1"></el-option>
          <el-option :label='$t("money.buyercancel")' value="3"></el-option>
          <el-option :label='$t("money.Sellercancel")' value="4"></el-option>
          <el-option :label='$t("money.Completed")' value="2"></el-option>
          <el-option :label='$t("money.Deleted")' value="5"></el-option>
        </el-select>
        <label>{{$t("money.transactiontime")}}</label>&nbsp;
        <el-date-picker
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
          v-model="uptime"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column :label='$t("money.Order_number")' align="center" prop="order_id" min-width="110"></el-table-column>
          <el-table-column :label='$t("money.Trading_currency")' align="center" prop="coin"></el-table-column>
          <el-table-column :label='$t("money.UnitPrice")' align="center" prop="price"></el-table-column>
          <el-table-column :label='$t("money.num")' align="center" prop="amount"></el-table-column>
          <el-table-column :label='$t("money.Total")' align="center" prop="money"></el-table-column>
          <el-table-column :label='$t("money.parameter")' align="center" prop="postscript"></el-table-column>
          <el-table-column :label='$t("money.account")' align="center" prop="account"></el-table-column>
          <el-table-column :label='$t("money.Payee")' align="center" prop="payee"></el-table-column>
          <el-table-column :label='$t("money.Buyer_account")' align="center" prop="buy_uid"></el-table-column>
          <el-table-column :label='$t("money.transactiontime")' align="center" prop="order_time" min-width="120"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0" style="color:pink">{{$t("money.Unpaid")}}</span>
              <span v-if="scope.row.status==1" style="color:green">{{$t("money.Paid")}}</span>
              <span v-if="scope.row.status==2">{{$t("money.Completed")}}</span>
              <span v-if="scope.row.status==3" style="color:red">{{$t("money.buyercancel")}}</span>
              <span v-if="scope.row.status==4" style="color:red">{{$t("money.Sellercancel")}}</span>
              <span v-if="scope.row.status==5" style="color:red">{{$t("money.Deleted")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("money.Appeal")' align="center" prop="appeal">
            <template slot-scope="scope">
              <span v-if="scope.row.appeal==0">{{$t("money.no")}}</span>
              <span v-if="scope.row.appeal==1" style="color:red">{{$t("money.yes")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("money.Remarks")' align="center" prop="memo"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="orderConfirm(scope.row.id)"
                v-if="scope.row.status==1"
              >{{$t("money.Confirmreceipt")}}</el-button>
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
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
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("money.status")' :label-width="formLabelWidth">
          <el-radio v-model="form.status" label="2">{{$t("money.Success")}}</el-radio>
          <el-radio v-model="form.status" label="4">{{$t("money.failure")}}</el-radio>
        </el-form-item>
        <el-form-item :label='$t("money.Uploadcredentials")' :label-width="formLabelWidth">
          <el-upload
            class="upload-demo"
            action="/resource/voucher_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            list-type="picture"
            :file-list="form.file"
            :on-success="handleAvatarSuccess"
            :before-upload="beforeAvatarUpload"
            :data="type"
          >
            <el-button size="small" type="primary">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
        <el-form-item :label='$t("money.Remarks")' :label-width="formLabelWidth">
          <el-input v-model="form.remarks" type="textarea" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="uploadVoucher">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
    <!-- 交易详情 -->
    <Transactiondetails :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>


<script>
export default {
  components: {
    Transactiondetails: () => import("../../components/transactiondetails")
  },
  data() {
    return {
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      form: {
        status: "2",
        file:[{url:''}],
        transactionStatus: "2",
        fileList: [],
        remarks: "",
        upload_img: "",
        id: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: 10,
      total: 1,
      search_type: "",
      search_info: "",
      search_coin: "",
      search_appeal: "",
      search_status: "",
      uptime: [],
      status: false,
      id: ""
    };
  },
  computed: {
    type() {
      return {
        type: "voucher",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  created() {
    this.getData();
  },
  methods: {
    clear() {
      this.search_type = "";
      this.search_info = "";
      this.search_coin = "";
      this.search_appeal = "";
      this.search_status = "";
      this.uptime = [];
    },
    uploadVoucher() {
      this.$http
        .post("/trade/upload_voucher", {
          data: this.form
        })
        .then(res => {
          if (res.status == 1) {
            this.$message({
              type: "success",
              message: res.msg
            });
            this.getData()
            this.dialogFormVisible = false;
          } else {
            this.$message({
              type: "error",
              message: res.msg
            });
          }
        });
    },
    seeUserInfo(row) {
      this.id = row.id;
      this.status = true;
    },
    changeStatus(data) {
      this.status = data;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    orderConfirm(id) {
      this.form.file = [];
      this.form.remarks=""
      this.id = id;
      this.form.id = id;
      this.dialogFormVisible = true;
    },
    getData() {
      this.loading = true;
      this.$http
        .post("/trade/trade_b2c", {
          page: this.page,
          search_info: this.search_info,
          order_status: this.search_status,
          start_time: this.uptime[0],
          end_time: this.uptime[1],
          search_type: this.search_type,
          search_coin: this.search_coin,
          search_appeal: this.search_appeal
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    },
     orderConfirm(id) {
      this.form.file = [];
      this.form.remarks=""
      this.id = id;
      this.form.id = id;
      this.dialogFormVisible = true;
    },
    confirmOrder() {
      this.$http
        .post("/trade/confirm_order", {
          id: this.id,
          status: this.form.transactionStatus,
          memo: this.form.remarks
        })
        .then(res => {
          if (res.status == 1) {
            this.$message({
              type: "success",
              message: res.msg
            });
            this.getData();
            this.dialogFormVisible = false;
          } else {
            this.$message({
              type: "error",
              message: res.msg
            });
          }
        });
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    },
    handleAvatarSuccess(res, file) {
      this.form.fileList.push(res.data.path);
      this.imageUrl = URL.createObjectURL(file.raw);
    },
    beforeAvatarUpload(file) {
      const isLt2M = file.size / 1024 / 1024 < 2;

      if (!isLt2M) {
        this.$message.error("MAX 2MB!");
      }
      return  isLt2M;
    }
  }
};
</script>



<style lang="scss" scoped>
.C2Clist {
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