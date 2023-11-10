<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">小組ID</label>&nbsp;
        <el-input
                style="width: 120px"
                id="tid"
                v-model="selectData.tid"
                @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">小組名稱</label>&nbsp;
        <el-input
                style="width: 120px"
                id="team"
                v-model="selectData.team"
                @keyup.enter.native="getData"
        ></el-input>
        <label>時間段</label>&nbsp;
        <el-date-picker
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          v-model="selectData.times"
          format="yyyy-MM-dd"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div>
        <div style="background-color: #ff9900; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">充值金額<br />{{sumary.dep1}}</div>
        <div style="background-color: #ffcc00; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">充值人數<br />{{sumary.dep2}}</div>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column label='ID' align="center" prop="id"></el-table-column>
          <el-table-column label='小組名稱' align="center" prop="team"></el-table-column>
          <el-table-column label='組員' align="center" prop="mem"></el-table-column>
          <el-table-column label='加入時間' align="center" prop="uptime"></el-table-column>
          <el-table-column label='充值人數' align="center" prop="dep2"></el-table-column>
          <el-table-column label='充值金額' align="center" prop="dep1"></el-table-column>
          <el-table-column label='操作' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="primary" @click="goUrl(scope.row.id)">查看</el-button>
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
  components: {
  },
  data() {
    return {
      loading: false,
      tableData: [],
      selectData: {
          tid: "",
          team: '',
          type: '',
        times: []
      },
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      status: false,
      id: 0,
      row: null,
      uid: "",
        partner_id: '0',
        partner_list: [],
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
      this.selectData.tid = "";
      this.selectData.times = [];
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    goUrl(id) {
        this.$router.push({
            path: '/sales_statis_sm',
            query: {
                id: id
            }
        });
    },
    //1：获取列表 2：导出
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/sales/sales_statis", {
            tid: this.selectData.tid,
            team: this.selectData.team,
            day_start: this.selectData.times[0],
            day_end: this.selectData.times[1],
            page_no: this.page,
            exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.list;
            this.page = res.data.page;
              this.total = res.data.total;
              this.sumary = res.data.sumary;
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
