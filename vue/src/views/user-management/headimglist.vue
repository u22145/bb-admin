<template>
  <div class="headimglist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          v-model="uid"
          style="width: 200px"
          id="selectUserID"
          @keyup.enter.native="getData(1,1)"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          v-model="nickname"
          style="width: 200px"
          id="nickname"
          @keyup.enter.native="getData(1,1)"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select v-model="status" placeholder @keyup.enter.native="getData(1,1)">
           <el-option :label='$t("common.Unaudited")' value="0"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData(1,1)">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button type="primary" @click="changeStatus(1)">{{$t("common.batch_pass")}}</el-button>
        <el-button type="danger" @click="changeStatus(2)">{{$t("common.no_pass")}}</el-button>
        <el-button @click="exportExecel">{{$t("common.export")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          @selection-change="handleSelectionChange"
          :data="itemData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :selectable="checkboxT" type="selection" width="55" align="center"></el-table-column>
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("common.img")' align="center">
            <template slot-scope="scope">
              <img :src="scope.row.pic_url" alt width="40" height="40" class="head_pic image-slot" />
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="seeUserInfo(scope.row,1)"
                v-if="scope.row.status == 0"
              >{{$t("common.adopt")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="editUserInfo(scope.row,2)"
                v-if="scope.row.status == 0"
              >{{$t("common.not_pass")}}</el-button>
              <p v-else>{{$t("common.examine")}}</p>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
          :total="total"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
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
      loading: false,
      export: 0,
      page: 1, //前往第几页
      total: 1, //总页码
      uid: "",
      nickname: "",
      status: "",
      itemData: [],
      multipleSelection: [],
      pic_id: "",
      limit: this.$store.state.adminPageSize
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    clear() {
      this.uid = "";
      this.nickname = "";
      this.status = "";
    },
    //不通过
    editUserInfo(row, data) {
      this.$http
        .post("/user/pic_trial", { id: row.id, status: data })
        .then(res => {
          this.getData();
          this.$message(res.msg);
        });
    },
    // 通过
    seeUserInfo(row, data) {
      this.$http
        .post("/user/pic_trial", { id: row.id, status: data })
        .then(res => {
          this.getData();
        });
    },
    // 批量1:通过 2:不通过
    changeStatus(data) {
      this.$http
        .post("/user/pic_trial", { id: this.pic_id, status: data })
        .then(res => {
          this.getData();
        });
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 获取头像列表
    getData(type = 1, is_page = 0) {
      if (is_page == 1) {
        this.page = 1;
      }
      this.loading = true;
      this.$http
        .post("/user/head_list", {
          uid: this.uid,
          nickname: this.nickname,
          status: this.status,
          page: this.page,
          type: type
        })
        .then(res => {
          this.loading = false;
          if (type == 1) {
            this.itemData = res.data.list;
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
    },
    // 导出
    exportExecel() {
      this.export = 1;
      this.getData(2);
    },
    //复选框
    checkboxT(row, rowIndex) {
      if (row.status == 1 || row.status == 3) {
        return false; //禁用
      } else {
        return true; //不禁用
      }
    },
    handleSelectionChange(val) {
      this.pic_id = "";
      this.multipleSelection = val;
      this.multipleSelection.map(item => {
        this.pic_id += item.id + ",";
      });
      this.pic_id = this.pic_id.substr(0, this.pic_id.length - 1);
    }
  }
};
</script>


<style lang="scss" scoped>
.headimglist {
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


