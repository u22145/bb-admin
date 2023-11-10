<template>
  <div class="sellbilllist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("money.Choice")}}</label>&nbsp;
        <el-select  :placeholder='$t("money.placeholdertype")' v-model="selectType">
          <el-option :label='$t("common.Username")' value="uid"></el-option>
          <el-option :label='$t("money.order")' value="id"></el-option>
        </el-select>
        <el-input  style="width: 200px" id="inputID" v-model="selectText"></el-input>
        <label style="margin-left: 20px">{{$t("money.price_type")}}</label>&nbsp;
        <el-select placeholder v-model="coin">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option label="EURC" value="eurc"></el-option>
          <el-option label="MSQ" value="mast"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="status">
          <el-option :label='$t("common.all")' value="99"></el-option>
          <el-option :label='$t("money.Normal_lading")' value="0"></el-option>
          <el-option :label='$t("money.over")' value="1"></el-option>
          <el-option :label='$t("money.Frozen")' value="2"></el-option>
          <el-option :label='$t("money.Withdrawal")' value="4"></el-option>
        </el-select>
        <label>{{$t("money.Ordertime")}}</label>&nbsp;
        <el-date-picker
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          v-model="times"
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table ref="multipleTable" :data="tableData" border style="width: 100%;margin-top: 30px">
          <el-table-column :label='$t("money.id")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("jurisdiction.labelID")' align="center" prop="sell_uid"></el-table-column>
          <el-table-column :label='$t("money.account1")' align="center" prop="account"></el-table-column>
          <el-table-column :label='$t("money.coin")' align="center" prop="coin"></el-table-column>
          <el-table-column :label='$t("money.price")' align="center" prop="price"></el-table-column>
          <el-table-column :label='$t("money.num")' align="center" prop="amount"></el-table-column>
          <el-table-column :label='$t("money.min_buy")' align="center" prop="min_buy"></el-table-column>
          <el-table-column :label='$t("money.max_buy")' align="center" prop="max_buy"></el-table-column>
          <el-table-column :label='$t("money.payment_method")' align="center" prop="pay_title">
          </el-table-column>
          <el-table-column :label='$t("money.Payment_account")' align="center" prop="account"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt">
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
  </div>
</template>


<script>
export default {
  data() {
    return {
      tableData: [],
      selectType: "",
      selectText: "",
      status: "99",
      coin: "",
      times: [],
      page: 1,
      limit: 10,
      total: 1
    };
  },
  created() {
    this.getData();
  },
  methods: {
    clear() {
      this.selectType= ""
      this.selectText= ""
      this.status= ""
      this.coin= ""
      this.times=[]
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    getData() {
      this.$http
        .post("/trade/sell_list", {
          page: this.page,
          selectType: this.selectType,
          coin: this.coin,
          selectText: this.selectText,
          status: this.status,
          start_time: this.times[0],
          end_time: this.times[1]
        })
        .then(res => {
          this.tableData = res.data.data;
        });
    }
  }
};
</script>



<style lang="scss" scoped>
.sellbilllist {
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



