<template>
  <div class="incomelist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("LabourUnion.AnchorID")}}</label>&nbsp;
        <el-input
          style="width:150px"
          id="selectUserID"
          v-model="anchor_id"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 150px"
          id="nickname"
          v-model="nickname"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label style="margin-left: 20px">直播時長</label>&nbsp;
        <el-select placeholder v-model="labour_min" @keyup.enter.native="getData(1)">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option :label='$t("common.six_min")' value="1"></el-option>
          <el-option :label='$t("common.six_mins")' value="2"></el-option>
          <el-option :label='$t("common.two_min")' value="3"></el-option>
          <el-option :label='$t("common.eight_min")' value="4"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("LabourUnion.Guild")}}</label>&nbsp;
        <el-select placeholder v-model="labour_union" @keyup.enter.native="getData(1)">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option
            v-for="item in labour_union_list"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          ></el-option>
        </el-select>
        <label>{{$t("LiveBroadcast.Livebroadcasttime")}}</label>&nbsp;
        <el-date-picker
          v-model="time"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData(1)">{{$t("common.search")}}</el-button>
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
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column :label='$t("LabourUnion.AnchorID")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("LiveBroadcast.Duration")' align="center" prop="times"></el-table-column>
          <el-table-column :label='$t("LiveBroadcast.dayIncomeTotal")' align="center" prop="total"></el-table-column>
          <el-table-column :label='$t("LiveBroadcast.income")' align="center" prop="gift"></el-table-column>
          <el-table-column :label='$t("LabourUnion.Guild")' align="center" prop="sociaty"></el-table-column>
          <el-table-column :label='$t("LiveBroadcast.time")' align="center" prop="begin_time"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
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
    <Incomedetails :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>

<script>
export default {
  components: {
    Incomedetails: () => import("../../components/incomedetails")
  },
  data() {
    return {
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      status: false,
      id: "",
      row: null,
      anchor_id: "",
      nickname: "",
      time: [],
      labour_union: "0",
      labour_min: "0",
      labour_union_list: []
    };
  },
  created() {
    this.getData(1);
    this.getLabourUnionList();
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
      this.id = "";
      this.nickname = "";
      this.labour_union = "";
      this.labour_min = "";
      this.anchor_id = "";
      this.time = [];
    },
    seeUserInfo(row) {
      this.id = row.id;
      window.localStorage.setItem("uid", row.uid);
      this.status = true;
    },
    changeStatus(data) {
      this.status = data;
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData(1);
    },
    //1：获取列表 2：导出
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("/live/anchor_profit", {
          page: this.page,
          anchor_id: this.anchor_id,
          nickname: this.nickname,
          labour_union: this.labour_union,
          labour_min: this.labour_min,
          begin_time_left: this.time[0],
          begin_time_right: this.time[1],
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (res.status == 1) {
            if (exp == 1) {
              this.tableData = res.data.data;
              this.page = res.data.page;
              this.total = res.data.total;
            } else {
              if (res.status == 1) {
                window.open(`${window.location.origin}${res.data}`);
              } else {
                this.$message.error(res.msg);
              }
            }
          } else {
            this.$message({
              message: res.msg,
              type: "error"
            });
          }
        });
    },
    getLabourUnionList() {
      this.$http.post("/live/labour_union_list").then(res => {
        this.labour_union_list = res.data;
      });
    }
  }
};
</script>

<style lang="scss" scoped>
.incomelist {
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

<!-- <template>
      <div class="incomelist">
        <div class="whiteBg">
          <div class="screen">
            <label for="selectUserID">{{$t("LabourUnion.AnchorID")}}</label>&nbsp;
            <el-input
              style="width:150px"
              id="selectUserID"
              v-model="anchor_id"
              @keyup.enter.native="getData(1)"
            ></el-input>
            <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
            <el-input
              style="width: 150px"
              id="nickname"
              v-model="nickname"
              @keyup.enter.native="getData(1)"
            ></el-input>
            <label style="margin-left: 20px">直播時長</label>&nbsp;
            <el-select placeholder v-model="labour_min" @keyup.enter.native="getData(1)">
              <el-option :label='$t("common.all")' value="0"></el-option>
              <el-option :label='$t("common.six_min")' value="1"></el-option>
              <el-option :label='$t("common.six_mins")' value="2"></el-option>
              <el-option :label='$t("common.two_min")' value="3"></el-option>
              <el-option :label='$t("common.eight_min")' value="4"></el-option>
            </el-select>
            <label style="margin-left: 20px">{{$t("LabourUnion.Guild")}}</label>&nbsp;
            <el-select placeholder v-model="labour_union" @keyup.enter.native="getData(1)">
              <el-option :label='$t("common.all")' value="0"></el-option>
              <el-option
                v-for="item in labour_union_list"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              ></el-option>
            </el-select>
            <label>{{$t("LiveBroadcast.Livebroadcasttime")}}</label>&nbsp;
            <el-date-picker
              v-model="time"
              type="datetimerange"
              :range-separator='$t("common.to")'
              :start-placeholder='$t("common.start_time")'
              :end-placeholder='$t("common.end_time")'
              value-format="yyyy-MM-dd HH:mm:ss"
            ></el-date-picker>
            <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData(1)">{{$t("common.search")}}</el-button>
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
              style="width: 100%;margin-top: 30px"
            >
              <el-table-column :label='$t("LabourUnion.ShowID")' align="center" prop="id"></el-table-column>
              <el-table-column :label='$t("LabourUnion.AnchorID")' align="center" prop="uid"></el-table-column>
             <el-table-column :label='$t("LabourUnion.AnchorID")' v-show="!expidHidden" align="center" prop="expid"></el-table-column> -->
              <!-- <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
              <el-table-column :label='$t("LiveBroadcast.Duration")' align="center" prop="times"></el-table-column>
              <el-table-column :label='$t("LiveBroadcast.income")' align="center" prop="gift"></el-table-column>
              <el-table-column :label='$t("LabourUnion.Guild")' align="center" prop="sociaty"></el-table-column>
              
              <el-table-column :label='$t("LabourUnion.GLIncome")' align="center" prop="gl_income"></el-table-column>
              <el-table-column :label='$t("LabourUnion.PlatformIncome")' align="center" prop="pltf_income"></el-table-column>
              <el-table-column :label='$t("LiveBroadcast.time")' align="center" prop="begin_time"></el-table-column>
              <el-table-column :label='$t("LiveBroadcast.endTime")' align="center" prop="end_time"></el-table-column>
              <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
                <template slot-scope="scope">
                  <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
                </template>
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
        <Incomedetails :status="status" :id="id" v-on:changeStatus="changeStatus" />
      </div>
    </template>

    <script>
    export default {
      components: {
        Incomedetails: () => import("../../components/incomedetails")
      },
      data() {
        return {
          loading: false,
          tableData: [],
          dialogFormVisible: false,
          page: 1,
          limit: 10,
          total: 1,
          status: false,
          id: "",
          row: null,
          anchor_id: "",
          nickname: "",
          time: [],
          labour_union: "0",
          labour_min: "0",
          labour_union_list: []
        };
      },
      created() {
        this.getData(1);
        this.getLabourUnionList();
      },
      methods: {
        search() {
          this.page = 1;
          this.getData(1);
        },
        clear() {
          this.id = "";
          this.nickname = "";
          this.labour_union = "";
          this.labour_min = "";
          this.anchor_id = "";
          this.time = [];
        },
        seeUserInfo(row) {
          this.id = row.id;
          window.localStorage.setItem("uid", row.uid);
          window.localStorage.setItem("id", row.id);
          window.localStorage.setItem("startTime", row.begin_time);
          window.localStorage.setItem("endTime", row.end_time);
          this.status = true;
        },
        changeStatus(data) {
          this.status = data;
        },

        handleCurrentChange(val) {
          this.page = val;
          this.getData(1);
        },
        //1：获取列表 2：导出
        getData(exp = 1) {
          this.loading = true;
          this.$http
            .post("/live/anchor_profit", {
              page: this.page,
              anchor_id: this.anchor_id,
              nickname: this.nickname,
              labour_union: this.labour_union,
              labour_min: this.labour_min,
              begin_time_left: this.time[0],
              begin_time_right: this.time[1],
              exp: exp
            })
            .then(res => {
              this.loading = false;
              if (res.status == 1) {
                if (exp == 1) {
                  this.tableData = res.data.data;
                  this.page = res.data.page;
                  this.total = res.data.total;
                } else {
                  if (res.status == 1) {
                    window.open(`${window.location.origin}${res.data}`);
                  } else {
                    this.$message.error(res.msg);
                  }
                }
              } else {
                this.$message({
                  message: res.msg,
                  type: "error"
                });
              }
            });
        },
        getLabourUnionList() {
          this.$http.post("/live/labour_union_list").then(res => {
            this.labour_union_list = res.data;
          });
        }
      }
    };
    </script>

    <style lang="scss" scoped>
    .incomelist {
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
    } -->