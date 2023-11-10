<template>
  <div class="reflectlist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("money.type")}}</label>&nbsp;
        <el-select
          :placeholder='$t("money.placeholdertype")'
          v-model="search_type"
          @keyup.enter.native="getData"
        >
            <el-option :label='$t("common.Username")' value="uid"></el-option>
        </el-select>
        <el-input
          style="width: 200px"
          id="inputID"
          v-model="search_info"
          @keyup.enter.native="getData"
        ></el-input>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="order_status" @keyup.enter.native="getData">
          <el-option :label='$t("money.success2")' value="success"></el-option>
          <el-option :label='$t("money.doing")' value="doing"></el-option>
          <el-option :label='$t("money.error2")' value="error"></el-option>
        </el-select>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="seach_time"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div style="background-color: #33ccff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">提幣人次(日/總)<br />{{sumary.user_day}} / {{sumary.user_day_all}}</div>
    <div style="background-color: #0099ff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">提幣數量(日/总)<br />{{sumary.user_day_amount}} / {{sumary.user_all_amount}}</div>
    <div style="background-color: #0099ff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">手續費(日/总)<br />{{sumary.user_day_fee}} / {{sumary.user_all_fee}}</div>
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

          <el-table-column label='序號' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column label='提幣地址' align="center" prop="to_addr"></el-table-column>
          <el-table-column :label='$t("money.amount")' align="center" prop="amount"></el-table-column>
          <el-table-column :label='$t("money.fee")' align="center" prop="fee"></el-table-column>
          <el-table-column :label='$t("money.net_money")' align="center" prop="net_amount"></el-table-column>
          <el-table-column label='餘額' align="center" prop="balance"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("money.admin_id")' align="center" prop="admin_id"></el-table-column>
          <el-table-column :label='$t("money.admin_time")' align="center" prop="admin_time"></el-table-column>
          <el-table-column label='審核狀態' align="center" prop="audit">
            <template slot-scope="scope">
              <span v-if="scope.row.audit==0"><el-button type="primary" class="fr" @click="editWithdraw(scope.row.id, 1)">{{$t("money.review_doing")}}</el-button></span>
              <span v-if="scope.row.audit==0"><el-button type="primary" class="fr" @click="editWithdraw(scope.row.id, 2)">{{$t("money.review_refuse")}}</el-button></span>
              <span v-if="scope.row.audit==1">{{$t("money.review_success")}}</span>
              <span v-if="scope.row.audit==2" style="color:red">{{$t("money.error_status")}}</span>
            </template>
          </el-table-column>
          <el-table-column label='支付狀態' align="center" prop="status">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0">未處理</span>
              <span v-if="scope.row.status==1">成功</span>
              <span v-if="scope.row.status==2" style="color:red">失敗</span>
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
  </div>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      tableData: [],
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
    };
  },
  created() {
    this.getData();
  },
  methods: {
    clear() {
      this.search_type = "";
      this.search_info = "";
      this.price_type = "";
      this.tableData = [];
      this.order_status = "";
      this.seach_time = [];
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    getData() {
      this.loading = true;
      this.$http
        .post("/trade/trade_withdraw", {
          page: this.page,
          search_info: this.search_info,
          price_type: this.price_type,
          order_status: this.order_status,
          start_time: this.seach_time[0],
          end_time: this.seach_time[1],
          search_type: this.search_type
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page;
          this.total = res.data.total;
          this.sumary = res.data.sumary;
        });
    },
    editWithdraw(ids, type) {
        this.loading = true;
        this.$http
            .post("/trade/trade_review", {
                id: ids,
                type:type,
            })
        .then(res => {
            if (res.status == 1) {
                this.$message.success(res.msg);
                this.dialogFormVisible = false;
                this.getData();
            } else {
                this.$message.error(res.msg);
                this.getData();
            }
        });
    },
    payment(ids, type) {
        this.loading = true;
        this.$http
            .post("/pay/trade_check", {
                id: ids,
                type:type,
            })
        .then(res => {
            if (res.status == 1) {
                this.$message.success(res.msg);
                this.dialogFormVisible = false;
                this.getData();
            } else {
                this.$message.error(res.msg);
                this.getData();
            }
        });
    }
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



