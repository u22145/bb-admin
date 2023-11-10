<template>
  <div class="anchorlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 120px"
          id="selectUserID"
          v-model="selectData.id"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 120px"
          id="nickname"
          v-model="selectData.nickname"
          @keyup.enter.native="getData(1)"
        ></el-input>
        <label style="margin-left: 20px">申请分类</label>&nbsp;
        <el-select
          placeholder
          style="width: 120px;"
          v-model="selectData.catId"
        >
          <el-option  v-for="item in catList" :value="item.id" :key="item.id">{{item.catName}}</el-option>
        </el-select>
        <label style="margin-left: 20px">審核狀態</label>&nbsp;
        <el-select
          placeholder
          style="width: 120px;"
          v-model="selectData.verifyStatus"
        >
          <el-option  v-for="item in verifyOption" :value="item.value" :key="item.value" :label="item.label"></el-option>
        </el-select>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="selectData.times"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search()">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="exportExecel">{{$t("common.export")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column label='申请分类' align="center" prop="catName">
          </el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="createdAt"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                v-if="scope.row.status==3"
                type="text"
                size="small"
                @click="seeUserInfo(scope.row)"
              >{{$t("common.to_examine")}}</el-button>
               <el-button
                v-if="scope.row.status!=3"
                type="text"
                size="small"
                @click="seeUserInfo(scope.row)"
              >{{$t("common.See")}}</el-button>
            </template>
          </el-table-column>
          <!-- <el-table-column :label='$t("common.operations")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                v-if="scope.row.status==1"
                type="text"
                size="small"
                @click="setPlay(scope.row, 1)"
              >禁播</el-button>
               <el-button
                v-if="scope.row.status==2"
                type="text"
                size="small"
                @click="setPlay(scope.row, 0)"
              >取消禁播</el-button>
            </template>
          </el-table-column> -->
        </el-table>
        <!-- 分页 -->
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

    <Receiveanchor :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>
<script>
import moment from 'moment'
export default {
  components: {
    Receiveanchor: () => import("../../components/receiveanchor")
  },
  data() {
    return {
      loading: false,
      selectData: {
        id: "",
        nickname: "",
        catId: "",
        audit_status: "",
        times: [],
        verifyStatus: 1
      },
      catList: null,
      tableData: [],
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      export: 0,
      status: false,
      id: "",
      row: null,
      verifyOption: [
        { value: 1, label: "審核通過" },
        { value: 2, label: "審核失敗" },
        { value: 3, label: "待審核" }
      ]
    };
  },
  created() {
    this.getData();
    this.getCategoryList()
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
      this.selectData.id = "";
      this.selectData.nickname = "";
      this.selectData.audit_status = "";
      this.selectData.times = [];
    },
    seeUserInfo(row) {
      this.id = row.id;
      this.status = true;
      window.localStorage.setItem("anchorlist_uid", row.uid);
    },
    setPlay(row, is_play) {
      this.loading = true;
      this.$http
        .post("/live/set_play", {
          is_play: is_play,
          uid:row.uid,
        })
        .then(res => {
          this.loading = false;
          if (res.status) {
            this.$message.success(res.msg);
            this.getData();
          }else{
            this.$message.error(res.msg);
          }
        });
    },
    changeStatus(data) {
      this.status = data;
      this.getData();
    },
    // 分页
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 1:获取列表 2:导出
    getData(exp = 1) {
      this.loading = true;
      this.$httpJava
        .get("/anchor/cert", {
          params: {
            uid: this.selectData.id,
            nickname: this.selectData.nickname,
            catId: this.selectData.catId,
            from: moment(this.selectData.times[0]).format('yyyyMMDD'),
            to: moment(this.selectData.times[1]).format('yyyyMMDD'),
            page: this.page
          }
        })
        .then(res => {
          this.loading = false;
          if (exp == 1) {
            this.tableData = res.data.data.data;
            this.page = res.data.page;
            this.total = res.data.total;
          } else {
            if (res.status == 1) {
              window.open(`${window.location.origin}${res.data}`);
            } else {
              this.$message.error(res.msg);
            }
          }
        })
      // this.$http
      //   .post("/live/anchor_list", {
      //     uid: this.selectData.id,
      //     nickname: this.selectData.nickname,
      //     audit_status: this.selectData.audit_status,
      //     upload_time_left: this.selectData.times[0],
      //     upload_time_right: this.selectData.times[1],
      //     page: this.page,
      //     exp: exp
      //   })
      //   .then(res => {
      //     this.loading = false;
      //     if (exp == 1) {
      //       this.tableData = res.data.list;
      //       this.page = res.data.page;
      //       this.total = res.data.total;
      //     } else {
      //       if (res.status == 1) {
      //         window.open(`${window.location.origin}${res.data}`);
      //       } else {
      //         this.$message.error(res.msg);
      //       }
      //     }
      //   });
    },
    exportExecel() {
      this.export = 1;
      this.getData(2);
    },
    getCategoryList() {
      this.$httpJava
        .get("/ls/cat", {
          page: this.page
        })
        .then(res => {
          this.catList = res.data.data.data
        });
    },
  }
};
</script>


<style lang="scss" scoped>
.anchorlist {
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


