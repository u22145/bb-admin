<template>
  <div class="reflectlist">
    <!-- <el-button type="primary" class="fr" style="padding: 12px 30px" @click="removeChannel()">{{$t("common.reload")}}</el-button> -->
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("money.ThirdPartyName")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="third_party_name"
          v-model="third_party_name"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("money.channelType")}}</label>&nbsp;
        <el-select placeholder v-model="type" @keyup.enter.native="getData">
          <el-option label='支付宝' value="ALIPAY"></el-option>
          <el-option label='微信' value="WECHAT"></el-option>
          <el-option label='网银' value="BANKCARD"></el-option>
          <el-option label='话费' value="CREDIT"></el-option>
        </el-select>
        <label>{{$t("money.channelSwitch")}}</label>&nbsp;
        <el-select placeholder v-model="s_switch" @keyup.enter.native="getData">
          <el-option :label='ON' value="ON"></el-option>
          <el-option :label='OFF' value="OFF"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData(1)">{{$t("common.search")}}</el-button>

        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="createChannelForm()">{{$t("common.add")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >

          <el-table-column :label='$t("money.paymentIndex")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("money.ThirdPartyName")' align="center" prop="third_party_name" v-show="show_col"></el-table-column>
          <el-table-column :label='$t("money.paymentChannel")' align="center" prop="channel_name"></el-table-column>
          <el-table-column :label='$t("money.paymentType")' align="center" prop="label_type"></el-table-column>
          <el-table-column :label='$t("money.paymentSetting")' align="center" prop="setting"></el-table-column>
          <el-table-column :label='$t("money.paymentSequence")' align="center" prop="seq_id"></el-table-column>
          <el-table-column :label='$t("money.paymentStatus")' align="center" prop="c_switch"></el-table-column>
          <el-table-column :label='$t("money.paymentSwitch")' align="center" prop="switch" >
            <template slot-scope="scope">
              <span><el-button type="primary" class="fr" @click="swtichChannel('switch', scope.row.switch, 'OFF', scope.row.id)">{{$t("money.paymentOff")}}</el-button></span>
              <span><el-button type="primary" class="fr" @click="swtichChannel('switch', scope.row.switch, 'ON', scope.row.id)">{{$t("money.paymentOn")}}</el-button></span>
              <span><el-button type="primary" class="fr" @click="swtichChannel('delete', scope.row.is_deleted, 1, scope.row.id)">{{$t("money.paymentDel")}}</el-button></span>
              <span><el-button type="primary" class="fr" @click="showChannelForm(scope.row)">{{$t("money.paymentEdit")}}</el-button></span>
            </template>
          </el-table-column>
          
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
        ></el-pagination>
      </div>
    </div>
    <!-- 编辑弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible" width="50%">
      <el-form :model="form">
        <el-form-item :label='$t("money.id")' :label-width="formLabelWidth">
          <el-input v-model="form.id" disabled></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentStatus")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.status_switch" @keyup.enter.native="getData">
            <el-option :label='ON' value="ON"></el-option>
            <el-option :label='OFF' value="OFF"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.ThirdPartyName")' :label-width="formLabelWidth">
          <el-input v-model="form.third_party_name"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.channelAllowAmount")' :label-width="formLabelWidth">
          <el-input v-model="form.min"></el-input> TO
          <el-input v-model="form.max"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.channelSeq")' :label-width="formLabelWidth">
          <el-input v-model="form.seq_id"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentChannel")' :label-width="formLabelWidth">
          <el-input v-model="form.channel_name"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentChannelCode")' :label-width="formLabelWidth">
          <el-input v-model="form.channel_code"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentRoute")' :label-width="formLabelWidth">
          <el-input v-model="form.route_name"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentChannelType")' :label-width="formLabelWidth">
          <el-input v-model="form.pay_type"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentPayName")' :label-width="formLabelWidth">
          <el-input v-model="form.pay_name"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.channelHints")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.app_hints" placeholder></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.channelRechargeRequirement")' :label-width="formLabelWidth">
          <el-input v-model="form.history_recharge_limit"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.channelType")' :label-width="formLabelWidth">
        <!-- <el-form-item label='支付类型 - ALIPAY：支付宝 | WECHAT：微信 | BANKCARD：银行卡 | CREDIT：话费 ' :label-width="formLabelWidth"> -->
          <el-select placeholder v-model="form.type" @keyup.enter.native="getData">
            <el-option label='支付宝' value="ALIPAY"></el-option>
            <el-option label='微信' value="WECHAT"></el-option>
            <el-option label='网银' value="BANKCARD"></el-option>
            <el-option label='话费' value="CREDIT"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.isRecommand")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.is_recommend" @keyup.enter.native="getData">
            <el-option :label='ON' value="ON"></el-option>
            <el-option :label='OFF' value="OFF"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.channelAdaptAndroid")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.adapt_android" @keyup.enter.native="getData">
            <el-option :label='ON' value="ON"></el-option>
            <el-option :label='OFF' value="OFF"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.channelAdaptIOS")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.adapt_ios" @keyup.enter.native="getData">
            <el-option :label='ON' value="ON"></el-option>
            <el-option :label='OFF' value="OFF"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.channelAdaptWEB")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.adapt_web" @keyup.enter.native="getData">
            <el-option :label='ON' value="ON"></el-option>
            <el-option :label='OFF' value="OFF"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.channelCustomAmountSwitch")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.custom_amount_switch" @keyup.enter.native="getData">
            <el-option :label='ON' value="ON"></el-option>
            <el-option :label='OFF' value="OFF"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("money.channelCustomAmount")' :label-width="formLabelWidth">
          <el-input v-model="form.custom_amount_min"></el-input> TO
          <el-input v-model="form.custom_amount_max"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.limit")' :label-width="formLabelWidth">
          <el-input v-model="form.limit"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.paymentAmount")' :label-width="formLabelWidth">
          <el-input v-model="form.opt_money0"></el-input>
          <el-input v-model="form.opt_money1"></el-input>
          <el-input v-model="form.opt_money2"></el-input>
          <el-input v-model="form.opt_money3"></el-input>
          <el-input v-model="form.opt_money4"></el-input>
          <el-input v-model="form.opt_money5"></el-input>
          <el-input v-model="form.opt_money6"></el-input>
          <el-input v-model="form.opt_money7"></el-input>
          <el-input v-model="form.opt_money8"></el-input>
        </el-form-item>
        <el-form-item :label='$t("money.callbackWhiteIpList")' :label-width="formLabelWidth">
          <el-input v-model="form.callback_whitelist"></el-input>
        </el-form-item>
        <el-form-item label='手动回调的方法名称 - 咨询后端程序员' :label-width="formLabelWidth">
          <el-input v-model="form.callback_route"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" v-if="addoredit==1" @click="createChannel()">{{$t("confirm.ok")}}</el-button>
        <el-button type="primary" v-else @click="updateChannel()">{{$t("confirm.ok")}}</el-button>
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
      third_party_name:"",
      type: "",
      s_switch: "",
      form: {
        id: "",
        third_party_name: "",
        min: "",
        max: "",
        seq_id: "",
        channel_name: "",
        channel_code: "",
        route_name: "",
        pay_name: "",
        type: "",
        pay_type: "",
        app_hints: "",
        history_recharge_limit: "",
        callback_whitelist: "",
        status_switch: "OFF",
        is_recommend: "OFF",
        custom_amount_switch: "OFF",
        custom_amount_min: "",
        custom_amount_max: "",
        limit: "0",
        adapt_android: "ON",
        adapt_ios: "ON",
        adapt_web: 'ON',
        callback_route: '',
        opt_money0: "",
        opt_money1: "",
        opt_money2: "",
        opt_money3: "",
        opt_money4: "",
        opt_money5: "",
        opt_money6: "",
        opt_money7: "",
        opt_money8: "",
      },
      dialogFormVisible: false,
      search_type: "",
      search_info: "",
      price_type: "",
      tableData: [],
      order_status: "",
      seach_time: [],
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      id:"",
      sumary: [],
      show_col: false,
    };
  },
  created() {
    this.getData();
  },
  methods: {

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // search = 1要将page重置为1
    getData(search = 0) {
      this.loading = true;
      if(search == 1) {
        this.page = 1;
      }
      this.$http
        .post("/trade/payment_channel_config", {
          page: this.page,
          third_party_name: this.third_party_name,
          type: this.type,
          s_switch: this.s_switch,
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.list;
          this.page = res.data.page;
          this.total = res.data.total;
          this.summary = res.data.summary;
        });
    },
    createChannelForm() {
      this.form.id = "",
      this.form.third_party_name = "",
      this.form.min = "",
      this.form.max = "",
      this.form.seq_id = "",
      this.form.channel_name = "",
      this.form.channel_code = "",
      this.form.route_name = "",
      this.form.pay_name = "",
      this.form.type = "",
      this.form.app_hints = "",
      this.form.history_recharge_limit = "",
      this.form.callback_whitelist = "",
      this.form.pay_type = "",
      this.form.status_switch = "",
      this.form.is_recommend = "",
      this.form.custom_amount_switch = "",
      this.form.custom_amount_min = "",
      this.form.custom_amount_max = "",
      this.form.limit = "",
      this.form.adapt_android = "",
      this.form.adapt_ios = "",
      this.form.adapt_web = "",
      this.form.callback_route = "",
      this.form.opt_money0 = "",
      this.form.opt_money1 = "",
      this.form.opt_money2 = "",
      this.form.opt_money3 = "",
      this.form.opt_money4 = "",
      this.form.opt_money5 = "",
      this.form.opt_money6 = "",
      this.form.opt_money7 = "",
      this.form.opt_money8 = "",

      this.addoredit = 1;
      this.dialogFormVisible = true;
    },

    showChannelForm(row) {
      this.addoredit = 0;
      this.form = row;
      this.dialogFormVisible = true;
    },
    // 添加
    createChannel() {
      this.$http
        .post("/trade/create_payment_channel", {
          id: this.form.id,
          third_party_name: this.form.third_party_name,
          min: this.form.min,
          max: this.form.max,
          seq_id: this.form.seq_id,
          channel_name: this.form.channel_name,
          channel_code: this.form.channel_code,
          route_name: this.form.route_name,
          pay_name: this.form.pay_name,
          type: this.form.type,
          app_hints: this.form.app_hints,
          history_recharge_limit: this.form.history_recharge_limit,
          callback_whitelist: this.form.callback_whitelist,
          pay_type: this.form.pay_type,
          adapt_android : this.form.adapt_android,
          adapt_ios: this.form.adapt_ios,
          adapt_web: this.form.adapt_web,
          status_switch: this.form.status_switch,
          is_recommend: this.form.is_recommend,
          custom_amount_switch: this.form.custom_amount_switch,
          custom_amount_min: this.form.custom_amount_min,
          custom_amount_max: this.form.custom_amount_max,
          limit: this.form.limit,
          callback_route: this.form.callback_route,
          opt_money0: this.form.opt_money0,
          opt_money1: this.form.opt_money1,
          opt_money2: this.form.opt_money2,
          opt_money3: this.form.opt_money3,
          opt_money4: this.form.opt_money4,
          opt_money5: this.form.opt_money5,
          opt_money6: this.form.opt_money6,
          opt_money7: this.form.opt_money7,
          opt_money8: this.form.opt_money8,
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    // 更新
    updateChannel(){
      this.$http
        .post("/trade/update_payment_channel", {
          id: this.form.id,
          third_party_name: this.form.third_party_name,
          min: this.form.min,
          max: this.form.max,
          seq_id: this.form.seq_id,
          channel_name: this.form.channel_name,
          channel_code: this.form.channel_code,
          route_name: this.form.route_name,
          pay_name: this.form.pay_name,
          type: this.form.type,
          app_hints: this.form.app_hints,
          history_recharge_limit: this.form.history_recharge_limit,
          callback_whitelist: this.form.callback_whitelist,
          pay_type: this.form.pay_type,
          status_switch: this.form.status_switch,
          is_recommend: this.form.is_recommend,
          custom_amount_switch: this.form.custom_amount_switch,
          custom_amount_min: this.form.custom_amount_min,
          custom_amount_max: this.form.custom_amount_max,
          limit: this.form.limit,
          adapt_android : this.form.adapt_android,
          adapt_ios: this.form.adapt_ios,
          adapt_web: this.form.adapt_web,
          callback_route: this.form.callback_route,
          opt_money0: this.form.opt_money0,
          opt_money1: this.form.opt_money1,
          opt_money2: this.form.opt_money2,
          opt_money3: this.form.opt_money3,
          opt_money4: this.form.opt_money4,
          opt_money5: this.form.opt_money5,
          opt_money6: this.form.opt_money6,
          opt_money7: this.form.opt_money7,
          opt_money8: this.form.opt_money8,
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    swtichChannel(action, key, value, id) {
        this.loading = true;
        this.$http
            .post("/trade/switch_payment_channel", {
                action: action,
                key: key,
                value: value,
                id: id
            })
        .then(res => {
            console.log(res.data);
            if (res.status == 1) {

                this.getData();
            } else {
                this.$message.error(res.msg);
                this.getData();
            }
        });
    },
  }
};

</script>

<style lang="scss" scoped>
.reflectlist {
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



