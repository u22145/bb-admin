<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">渠道廣告ID</label>&nbsp;
        <el-input
                style="width: 120px"
                id="advert_id"
                v-model="selectData.id"
                @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">渠道廣告名稱</label>&nbsp;
        <el-input
                style="width: 120px"
                id="partner_name"
                v-model="selectData.name"
                @keyup.enter.native="getData"
        ></el-input>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <!--<div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getData(2)">{{$t("common.export")}}</el-button>
      </div>-->

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column label='渠道ID' align="center" prop="id"></el-table-column>
          <el-table-column label='渠道名稱' align="center" prop="name"></el-table-column>
          <el-table-column label='加入時間' align="center" prop="uptime"></el-table-column>
          <el-table-column label='點擊量(安卓)' align="center" prop="android"></el-table-column>
          <el-table-column label='點擊量(ios)' align="center" prop="ios"></el-table-column>
          <el-table-column label='點擊增長率(安卓)' align="center" prop="android_rate"></el-table-column>
          <el-table-column label='點擊增長率(ios)' align="center" prop="ios_rate"></el-table-column>
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
          id: "",
          name: '',
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
        advert_id: '0',
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
      this.selectData.advert_id = "";
      this.selectData.times = [];
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    goUrl(id) {
        this.$router.push({
            path: '/advert_day',
            query: {
                id: id
            }
        });
    },
    //1：获取列表 2：导出
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/advert/advert_total", {
            advert_id: this.selectData.id,
            advert_name: this.selectData.name,
            page_no: this.page,
            exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
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
