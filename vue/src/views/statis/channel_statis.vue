<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("statis.channel")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.channel_id">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option
                  v-for="item in channel_list"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
          ></el-option>
        </el-select>
        <label>{{$t("statis.select_time")}}</label>&nbsp;
        <el-date-picker
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          v-model="selectData.times"
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getData(2)">{{$t("common.export")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("statis.date")' align="center" prop="date"></el-table-column>
          <el-table-column :label='$t("statis.channel_id")' align="center" prop="channel_id"></el-table-column>
          <el-table-column :label='$t("statis.channel_name")' align="center" prop="channel_name"></el-table-column>
          <el-table-column :label='$t("statis.new_reg_num")' align="center" prop="new_reg_num"></el-table-column>
          <el-table-column :label='$t("statis.online_today")' align="center" prop="online_today"></el-table-column>
          <el-table-column :label='$t("statis.online_yesterday")' align="center" prop="online_yesterday"></el-table-column>
          <el-table-column :label='$t("statis.online_sever_days_ago")' align="center" prop="online_sever_days_ago"></el-table-column>
          <el-table-column :label='$t("statis.pay_num")' align="center" prop="pay_num"></el-table-column>
          <el-table-column :label='$t("statis.stable_currency_recharge")' align="center" prop="stable_currency_recharge"></el-table-column>
          <el-table-column :label='$t("statis.arpu")' align="center" prop="arpu"></el-table-column>
          <el-table-column :label='$t("statis.arppu")' align="center" prop="arppu"></el-table-column>
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
  components: {
  },
  data() {
    return {
      loading: false,
      tableData: [],
      selectData: {
          channel_id: "",
        times: []
      },
      page: 1,
      limit: 10,
      total: 1,
      status: false,
      id: 0,
      row: null,
      uid: "",
        channel_id: '0',
        channel_list: [],
    };
  },
  created() {
    this.getData(1);
    this.get_channel_list();
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
      this.selectData.channel_id = "";
      this.selectData.times = [];
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    //1：获取列表 2：导出
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/statis/channel_statis", {
            channel_id: this.selectData.channel_id,
            day_start: this.selectData.times[0],
            day_end: this.selectData.times[1],
            page: this.page,
            exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.list;
            this.page = res.data.page;
          } else {
            if (res.status == 1) {
              window.open(`${window.location.origin}${res.data}`);
            } else {
              this.$message.error(res.msg);
            }
          }
        });
    },
    get_channel_list() {
        this.$http.post("/statis/channel_list").then(res => {
            this.channel_list = res.data;
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
