<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">用戶</label>&nbsp;
        <el-input
                style="width: 120px"
                id="partner_id"
                v-model="selectData.content"
                @keyup.enter.native="getData"
                placeholder="id / 暱稱"
        ></el-input>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div>
        <div style="background-color: #33ccff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">註冊人數<br />{{sumary.reg_all}}</div>
        <div style="background-color: #0099ff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">日活用戶<br />{{sumary.user_active}}</div>
        <div style="background-color: #99cc66; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">直播收益(Baby)<br />{{sumary.room_day}} / {{sumary.room_week}} / {{sumary.room_all}}</div>
         <div style="background-color: #99cc66; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">平台收益(Baby)<br />{{sumary.boss_day}} / {{sumary.boss_week}} / {{sumary.boss_all}}</div>
        <div style="background-color: #ff9900; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">每日观看付费内容<br />{{sumary.day_exp_num}} min / {{sumary.day_exp_fee}} Baby</div>
        <div style="background-color: #ffcc00; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">开通会员人数<br />{{sumary.day_vip}} / {{sumary.week_vip}} / {{sumary.all_vip}}</div>
        <div style="background-color: #33ccff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">用户充值(CNY)<br />{{sumary.user_day_dps}} / {{sumary.user_all_dps}}</div>
        <div style="background-color: #0099ff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">各档位等级人数<br />{{sumary.five_sum}} / {{sumary.ten_sum}} / {{sumary.fif_sum}} / {{sumary.twen_sum}} / {{sumary.twen_five_sum}} / {{sumary.thirty_sum}}</div>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column label='用戶id' align="center" prop="id"></el-table-column>
          <el-table-column label='用戶暱稱' align="center" prop="nickname"></el-table-column>
          <el-table-column label='充值金額' align="center" prop="money"></el-table-column>
          <el-table-column label='消費Baby' align="center" prop="exp_eurc_balance"></el-table-column>
          <el-table-column label='剩餘Baby' align="center" prop="eurc_balance"></el-table-column>
          <el-table-column label='每天在線時間(min)' align="center" prop="online_time"></el-table-column>
          <el-table-column label='是否會員' align="center" prop="is_vip"></el-table-column>
          <el-table-column label='用戶等級' align="center" prop="level"></el-table-column>
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
  components: {
  },
  data() {
    return {
      loading: false,
      tableData: [],
      selectData: {
          content: "",
      },
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      status: false,
      id: 0,
      row: null,
      uid: "",
      sumary: [],
    };
  },
  created() {
    this.getData(1);
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData(1);
    },
    //1：获取列表 2：导出
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/stat/user_statistics", {
            content: this.selectData.content,
            page_no: this.page,
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
              this.sumary = res.data.data;
          } else {
            if (res.status == 1) {
              window.open(`${window.location.origin}${res.data}`);
            } else {
              this.$message.error(res.msg);
            }
          }
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.realnamelist {
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
