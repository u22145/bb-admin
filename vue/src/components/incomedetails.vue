<template>
  <div class="incomedetails">
    <!-- 主播收益详情页 -->
    <el-dialog
      :visible.sync="status"
      :fullscreen="true"
      :modal-append-to-body="true"
      :append-to-body="true"
      lock-scroll
      :show-close="false"
      custom-class="UserInfoContent"
      :close-on-press-escape="false"
    >
      <div>
        <!-- 头部 -->
        <div class="conTitle">{{$t("commentlist.conTitle2")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->
        <div class="detailed">
          <div class="essentialInformation">
            <div class="stat_con">
              <div style="margin-bottom: 20px">
                <div class="screen">
                  <label>{{$t("common.seleYear")}}&nbsp;</label>
                  <el-select v-model="year" @change="yearChange">
                    <el-option v-for="item in dates" :key="item" :label="item+'Year'" :value="item"></el-option>
                  </el-select>
                </div>
                <el-table :data="yearData" border style="width: 100%">
                  <el-table-column prop="time" :label='$t("common.Year")' align="center"></el-table-column>
                  <el-table-column prop="year_earnings" :label="$t('common.year_earnings')" align="center"></el-table-column>
                </el-table>
              </div>

              <div style="margin-bottom: 20px">
                <div class="screen">
                  <label>{{$t("common.selemonth")}}&nbsp;</label>
                  <el-select v-model="month" @change="monthChange">
                    <el-option label="1" value="01"></el-option>
                    <el-option label="2" value="02"></el-option>
                    <el-option label="3" value="03"></el-option>
                    <el-option label="4" value="04"></el-option>
                    <el-option label="5" value="05"></el-option>
                    <el-option label="6" value="06"></el-option>
                    <el-option label="7" value="07"></el-option>
                    <el-option label="8" value="08"></el-option>
                    <el-option label="9" value="09"></el-option>
                    <el-option label="10" value="10"></el-option>
                    <el-option label="11" value="11"></el-option>
                    <el-option label="12" value="12"></el-option>
                  </el-select>
                </div>
                <el-table :data="monthData" border style="width: 100%">
                  <el-table-column prop="time" :label='$t("common.month")' align="center"></el-table-column>
                  <el-table-column prop="year_earnings" :label='$t("common.month_earnings")' align="center"></el-table-column>
                </el-table>
              </div>
              <div style="margin-bottom: 20px">
                <el-button class="fr" @click="dailyEarnings(2)">{{$t("common.export")}}</el-button>
                <div class="screen">{{$t("common.dailyData")}}&nbsp;</div>
                <el-table :data="dailyData" border style="width: 100%">
                  <el-table-column prop="date" :label='$t("common.date")' align="center"></el-table-column>
                  <el-table-column prop="price" :label='$t("common.dailyDataprice")' align="center"></el-table-column>
                </el-table>
                <el-pagination
                  @size-change="handleSizeChange"
                  @current-change="handleCurrentChange"
                  :current-page="page"
                  :page-sizes="[10]"
                  :page-size="limit"
                  layout="total, sizes, prev, pager, next, jumper"
                  :total="total"
                  class="fr"
                ></el-pagination>
              </div>
              <!--
              <div style="margin-bottom: 20px">
                <el-button class="fr" @click="detaiEarningsList(2)">{{$t("common.export")}}</el-button>
                <div class="screen">{{$t("common.incomeList")}}&nbsp;</div>
                <el-table :data="detailData" border style="width: 100%">
                  <el-table-column prop="showId" :label='$t("LabourUnion.ShowID")' align="center"></el-table-column>
                  <el-table-column prop="startTime" :label='$t("LiveBroadcast.time")' align="center"></el-table-column>
                  <el-table-column prop="endTime" :label='$t("LiveBroadcast.endTime")' align="center"></el-table-column>
                  <el-table-column prop="userId" :label='$t("LabourUnion.UserID")' align="center"></el-table-column>
                  <el-table-column prop="payTime" :label='$t("money.pay_time")' align="center"></el-table-column>
                  <el-table-column prop="category" :label='$t("common.category")' align="center"></el-table-column>
                  <el-table-column prop="amount" :label='$t("LabourUnion.Amount")' align="center"></el-table-column>
                  <el-table-column prop="income" :label='$t("LiveBroadcast.income")' align="center"></el-table-column>
                  <el-table-column prop="GLIncome" :label='$t("LabourUnion.GLIncome")' align="center"></el-table-column>
                  <el-table-column prop="platformIncome" :label='$t("LabourUnion.PlatformIncome")' align="center"></el-table-column>
                </el-table>
                <el-pagination
                  @size-change="handleSizeChange"
                  @current-change="handleCurrentChange"
                  :current-page="pageDetail"
                  :page-sizes="[10]"
                  :page-size="limit"
                  layout="total, sizes, prev, pager, next, jumper"
                  :total="totalDetail"
                  class="fr"
                ></el-pagination>
              </div>
              -->
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>
<script>
export default {
  name: "incomedetails",
  props: {
    status: null,
    id: null
  },

  data() {
    return {
      tableData: [],
      year: "",
      month: "",
      day: "",
      page: 1,
      pageDetail: 1,
      limit: 10,
      total: 0,
      totalDetail: 0,
      dates: [],
      yearData: [],
      monthData: [],
      dailyData: [],
      detailData: [],
      pageDetailCount: 1,
      isShow: false
    };
  },
  watch: {
    status() {
      if (this.status == true) {
        let date = new Date();
        this.year = date.getFullYear();
        this.month = date.getMonth() + 1;
        this.dates = this.createDate();
        this.yearEarnings();
        this.monthEarnings();
        this.dailyEarnings();
        this.detaiEarningsList();
      }
    }
  },
  methods: {
    goback() {
      this.$emit("changeStatus", false);
    },
    handleSizeChange(val) {
      this.limit = val;
      this.page = 1;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    createDate() {
      var date = [];
      for (var i = 2019; i <= 2100; i++) {
        date.push(i);
      }
      return date;
    },
    // 年收益
    yearEarnings() {
      var uid = window.localStorage.getItem("uid");
      this.$http
        .post("/live/year_earnings", {
          uid: uid,
          year: this.year
        })
        .then(res => {
          this.yearData = [res.data];
        });
    },
    yearChange() {
      this.yearEarnings();
    },
    // 月收益
    monthEarnings() {
      var uid = window.localStorage.getItem("uid");
      this.$http
        .post("/live/month_earnings", {
          uid: uid,
          year: this.year,
          month: this.month
        })
        .then(res => {
          this.monthData = [res.data];
        });
    },
    monthChange() {
      this.monthEarnings();
      this.dailyEarnings();
    },
    // 日收益0：获取列表 2：导出
    dailyEarnings(exp = 0) {
      var uid = window.localStorage.getItem("uid");
      this.$http
        .post("/live/daily_earnings", {
          uid: uid,
          year: this.year,
          month: this.month,
          exp: exp
        })
        .then(res => {
          if (exp == 0) {
            this.dailyData = res.data.data;
            this.page = res.data.page;
            this.total = res.data.total;
          } else {
            window.open(`${window.location.origin}${res.data}`);
          }
        });
    },
    // 本场直播收益流水0：获取列表 2：导出
    detaiEarningsList(exp = 0) {
      var showId = window.localStorage.getItem("id"),
          startTime = window.localStorage.getItem("startTime"),
          endTime = window.localStorage.getItem("endTime"),
          roomId = window.localStorage.getItem("uid");
      this.$http
        .post("/live/get_liveshow_income_detail", {
          showId: showId,
          startTime: startTime,
          endTime: endTime,
          roomId: roomId,
          exp: exp
        })
        .then(res => {
          if (exp == 0) {
            this.detailData = res.data.incomeList;
            this.pageDetail = res.data.page;
            this.totalDetail = res.data.total;
            this.pageDetailCount = res.data.page_count;
          } else {
            window.open(`${window.location.origin}${res.data}`);
          }
        });
    },
  }
};
</script>

<style lang="scss">
.UserInfoContent {
  .el-dialog__body {
    width: 1200px;
    margin: 0 auto;
  }

  .detailed {
    margin: 40px 0;

    .screen {
      margin-top: 30px;
      margin-bottom: 20px;
      .fr {
        margin-left: 20px;
      }
    }

    .stat_con {
      > div {
        display: inline-block;
        vertical-align: top;
        width: 100%;
      }
      img {
        height: 100%;
      }
    }
  }
}
</style>
