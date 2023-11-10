<template>
  <div class="LabourUnionturnoverlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="LabourUnionID">{{$t("LabourUnion.GuildID")}}</label>
        <el-input
          style="width: 200px"
          id="LabourUnionID"
          v-model="query_data.sociaty_id"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="LabourUnionName">{{$t("LabourUnion.Guild_name")}}</label>
        <el-input
          style="width: 200px"
          id="LabourUnionName"
          v-model="query_data.sociaty_name"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="selectUserID">{{$t("LabourUnion.PresidentID")}}</label>
        <el-input
          style="width: 200px"
          id="selectUserID"
          v-model="query_data.owner_uid"
          @keyup.enter.native="getData"
        ></el-input>
      </div>
      <div class="screen">
        <label>{{$t("LabourUnion.time")}} </label>
        <el-date-picker
          v-model="query_data.time"
          value-format="yyyy-MM-dd"
          type="daterange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getData(1)"> {{$t("common.export")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("LabourUnion.GuildID")' align="center" prop="sociaty_id"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Guild_name")' align="center" prop="sociaty_name"></el-table-column>
          <el-table-column :label='$t("LabourUnion.GuildGross")' align="center" prop="soc_eurc_gross"></el-table-column>
          <el-table-column :label='$t("LabourUnion.GuildIncome")' align="center" prop="soc_eurc_sum"></el-table-column>
          <el-table-column :label='$t("LabourUnion.StreamerIncome")' align="center" prop="soc_eurc_anchor"></el-table-column>
          <el-table-column :label='$t("LabourUnion.GuildLeaderIncome")' align="center" prop="soc_eurc_net"></el-table-column>
          <el-table-column :label='$t("LabourUnion.time")' align="center" prop="date"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t('LabourUnion.look_labour_price')}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          @prev-click="handlePrevClick"
          @next-click="handleNextClick"
          :current-page="page"
          :page-sizes="[5]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
        ></el-pagination>
      </div>
    </div>
    <!-- 查看主播收益 -->
    <LabourUnionanchorincome
      :status="status"
      :id="id"
      :date="date"
      v-on:changeStatus="changeStatus"
    />
  </div>
</template>


<script>
export default {
  components: {
    LabourUnionanchorincome: () =>
      import("../../components/LabourUnionanchorincome")
  },
  data() {
    return {
      loading: false,
      query_data: {
        owner_uid: "",
        time: [],
        sociaty_id: "",
        sociaty_name: ""
      },
      tableData: [],
      page: 1,
      limit: 5,
      total: 1,
      list_url: "",
      status: false,
      id: "",
      date: null
    };
  },
  created() {
    this.getData(0);
  },

  methods: {
    search() {
      this.page = 1;
      this.getData(0);
    },
    clear() {
      this.query_data.owner_uid = "";
      this.query_data.time = [];
      this.query_data.sociaty_id = "";
      this.query_data.sociaty_name = "";
    },
    seeUserInfo(row) {
      this.id = row.sociaty_id;
      this.date = row.date;
      this.status = true;
    },
    changeStatus(data) {
      this.status = data;
    },
    // 分页设置
    handlePrevClick(val) {
      this.page = val;
      this.getData();
    },
    handleNextClick(val) {
      this.page = val;
      this.getData();
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 获取公会流水列表
    getData(e = 0) {
      this.loading = true;
      if (e == 1) {
        this.list_url = "/sociaty/export_sociaty_statement_list";
      } else {
        this.list_url = "/sociaty/sociaty_statement_list";
      }
      this.$http
        .post(this.list_url, {
          owner_uid: this.query_data.owner_uid,
          start_date: this.query_data.time[0],
          end_date: this.query_data.time[1],
          sociaty_id: this.query_data.sociaty_id,
          sociaty_name: this.query_data.sociaty_name,
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.loading = false;
          if (e == 0) {
            this.tableData = res.data.data;
            this.page = res.data.page_no;
            this.limit = res.data.page_size;
            this.total = res.data.total;
          } else {
            if (res.status == 1) {
              window.open(`${window.location.origin}${res.data.path}`);
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
.LabourUnionturnoverlist {
  p:nth-child(1) {
    border-bottom: #f5f7fa solid 1px;
    margin: 0;
    padding: 0;
  }
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



