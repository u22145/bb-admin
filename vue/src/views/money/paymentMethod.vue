<template>
  <div class="paymentMethod">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("money.payment_method")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.paymentMethod">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option :label='$t("money.Alipay")' value="3"></el-option>
          <el-option :label='$t("money.Bankcard")' value="1"></el-option>
          <el-option :label='$t("money.WeChat")' value="4"></el-option>
          <el-option label="PayPal" value="5"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.status">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option :label='$t("jurisdiction.Enable")' value="on"></el-option>
          <el-option label="关闭" value="off"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("money.Receivables_currency")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.currency">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option :label='$t("common.RMB")' value="CNY"></el-option>
          <el-option :label='$t("common.Euro")' value="EUR"></el-option>
          <el-option :label='$t("common.Yen")' value="JPY"></el-option>
          <el-option :label='$t("common.dollar")' value="USD"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="operations()">{{$t("common.add")}}</el-button>
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
          <el-table-column :label='$t("money.payment_method")' align="center" prop="pay_title"></el-table-column>
          <el-table-column :label='$t("money.account")' align="center" prop="account" min-width="150px"></el-table-column>
          <el-table-column :label='$t("money.PayeeID")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("money.Payee")' align="center" prop="payee"></el-table-column>
          <el-table-column :label='$t("money.Receivables_currency")' align="center" prop="currency"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0" style="color:red">{{$t("jurisdiction.Discontinue_use")}}</span>
              <span v-if="scope.row.status==1">{{$t("money.inuse")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                v-if="scope.row.status==1"
                @click="savePaymentMethod(scope.row.id,scope.row.status)"
              >{{$t("money.Disable")}}</el-button>
              <el-button
                type="text"
                size="small"
                v-if="scope.row.status==0"
                @click="savePaymentMethod(scope.row.id,scope.row.status)"
              >{{$t("jurisdiction.Enable")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="operations(scope.row.id)"
                v-show="scope.row.status!=1"
              >{{$t("common.modify")}}</el-button>
              <el-button type="text" size="small" @click="seeUserInfo(scope.row.id)">{{$t("common.See")}}</el-button>
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
    <el-dialog :title="operation" :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("money.payment_method")' :label-width="formLabelWidth">
          <el-select v-model="form.type" autocomplete="off">
            <el-option :label='$t("money.Alipay")' value="3"></el-option>
          <el-option :label='$t("money.Bankcard")' value="1"></el-option>
          <el-option :label='$t("money.WeChat")' value="4"></el-option>
            <el-option label="PayPel" value="5"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.Receivables_currency")' :label-width="formLabelWidth">
          <el-select v-model="form.currency" autocomplete="off">
          <el-option :label='$t("common.RMB")' value="CNY"></el-option>
          <el-option :label='$t("common.Euro")' value="EUR"></el-option>
          <el-option :label='$t("common.Yen")' value="JPY"></el-option>
          <el-option :label='$t("common.dollar")' value="USD"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.bank")' :label-width="formLabelWidth">
          <el-input v-model="form.bank" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.branch")' :label-width="formLabelWidth">
          <el-input v-model="form.branch" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.account")' :label-width="formLabelWidth">
          <el-input v-model="form.account" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.payee_name")' :label-width="formLabelWidth">
          <el-input v-model="form.payee" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.code")' :label-width="formLabelWidth">
          <el-upload
            class="upload-demo"
            action="/resource/qrcode_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            :data="type"
            :file-list="form.fileList"
            list-type="picture"
            :limit="1"
          >
            <el-button size="small" type="primary">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
        <el-form-item :label='$t("common.status")' :label-width="formLabelWidth">
          <el-radio v-model="form.status" label="1">{{$t("jurisdiction.Enable")}}</el-radio>
          <el-radio v-model="form.status" label="0">{{$t("money.Disable")}}</el-radio>
        </el-form-item>
        <el-form-item :label='$t("money.Remarks")' :label-width="formLabelWidth">
          <el-input v-model="form.remarks" type="textarea" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="save">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <el-dialog :visible.sync="detail" width="40%">
      <el-form :model="info">
        <el-form-item :label='$t("money.payment_method")' :label-width="formLabelWidth">
          <el-input v-model="info.pay_title" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.Receivables_currency")' :label-width="formLabelWidth">
          <el-input v-model="info.currency" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.bank")' :label-width="formLabelWidth">
          <el-input v-model="info.bank" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.branch")' :label-width="formLabelWidth">
          <el-input v-model="info.branch" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.account")' :label-width="formLabelWidth">
          <el-input v-model="info.account" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.payee_name")' :label-width="formLabelWidth">
          <el-input v-model="info.nickname" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("common.status")' :label-width="formLabelWidth">
          <el-input v-model="info.status" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.code")' :label-width="formLabelWidth">
          <img :src="info.qrcode" alt width="100" height="100" class="head_pic image-slot" />
        </el-form-item>
        <el-form-item :label='$t("money.Remarks")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="info.payee" disabled></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="detail = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="detail = false">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data() {
    return {
      operation: 'ADD',
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      selectData: {
        paymentMethod: "",
        status: "",
        currency: ""
      },
      form: {
        fileList: [{ url: "" }],
        type: "",
        currency: "",
        bank: "",
        bankBranch: "",
        person: "",
        account: "",
        status: [],
        remarks: "",
        status: "",
        payee: "",
        qrcode: "",
        id: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: 10,
      total: 1,
      detail: false,
      id: "",
      info: {}
    };
  },
  computed: {
    //上传图片参数
    type() {
      return {
        type: "qrcode",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  created() {
    this.getData();
  },
  methods: {
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    search() {
      this.page = 1;
      this.getData();
    },
    clear() {
      this.selectData.paymentMethod = "";
      this.selectData.status = "";
      this.selectData.currency = "";
    },
    seeUserInfo(id, is_form = 0) {
      if (is_form == 0) {
        this.detail = true;
      }
      this.$http
        .post("/trade/payment_method_info", {
          id: id
        })
        .then(res => {
          if (is_form == 0) {
            this.info = res.data;
          } else {
            this.form.type = res.data.pay_type;
            this.form.currency = res.data.currency;
            this.form.bank = res.data.bank;
            this.form.branch = res.data.branch;
            this.form.account = res.data.account;
            this.form.payee = res.data.payee;
            // this.form.uid = res.data.uid;
            this.form.status = res.data.status;
            this.form.remarks = res.data.remark;
            this.form.qrcode = res.data.qrcode;
            if (this.form.qrcode) {
              this.form.fileList.splice(0, 1, { url: this.form.qrcode });
            } else {
              this.form.fileList = [];
            }
          }
        });
    },
    getData() {
      this.loading = true;
      this.$http
        .post("/trade/payment_method", {
          page: this.page,
          paymentMethod: this.selectData.paymentMethod,
          status: this.selectData.status,
          currency: this.selectData.currency
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    },
    savePaymentMethod(id, status) {
      this.$http
        .post("/trade/save_payment_method_status", {
          id: id,
          status: status == 1 ? 0 : 1
        })
        .then(res => {
          this.$message.success(res.msg);
          this.getData();
          this.dialogFormVisible = false;
        });
    },
    operations(id = 0) {
      this.operation = id ? "SAVE" : "ADD";
      this.form.id = id;
      this.seeUserInfo(id, 1);
      this.dialogFormVisible = true;
    },
    save() {
      this.$http
        .post("/trade/save_payment_method", {
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
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    },
    handleAvatarSuccess(res, file) {
      this.form.qrcode = res.data.path;
      this.imageUrl = URL.createObjectURL(file.raw);
    },
    beforeAvatarUpload(file) {
      // const isJPG = file.type === "image/jpeg";
      // const isLt2M = file.size / 1024 / 1024 < 2;
      // if (!isJPG) {
      //   this.$message.error("上传头像图片只能是 JPG 格式!");
      // }
      if (!isLt2M) {
        this.$message.error("MAX 2MB!");
      }
      return isJPG && isLt2M;
    }
  }
};
</script>



<style lang="scss" scoped>
.paymentMethod {
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



