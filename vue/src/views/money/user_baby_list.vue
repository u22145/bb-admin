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
        <div style="background-color: #33ccff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">購買人次(日 / 總)<br />{{this.sumary.user_day_buy}} / {{this.sumary.user_all_buy}}</div>
        <div style="background-color: #0099ff; width: 300px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">購買數量(日 / 總)<br />{{this.sumary.user_day_amount}} / {{this.sumary.user_all_amount}}</div>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column label='序號' align="center" prop="id"></el-table-column>
          <el-table-column label='用戶id' align="center" prop="uid"></el-table-column>
          <el-table-column label='用戶暱稱' align="center" prop="nickname"></el-table-column>
          <el-table-column label='CNY支出' align="center" prop="money"></el-table-column>
          <el-table-column label='購買Baby數量' align="center" prop="amount"></el-table-column>
          <el-table-column label='餘額(baby)' align="center" prop="eurc_balance"></el-table-column>
          <el-table-column label='購買時間' align="center" prop="uptime"></el-table-column>
          <el-table-column label='購買狀態' align="center" prop="status_txt"></el-table-column>
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
      user_day_buy:"",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      status: false,
      id: 0,
      row: null,
      uid: "",
      sumary:[],
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
        .post("/trade/get_baby_list", {
            content: this.selectData.content,
            page: this.page,
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
