<template>
  <div class="advertiserlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="advertiserID">{{$t("resources.advertiserID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="advertiserID"
          v-model="selectData.id"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="advertiserName">{{$t("resources.advertiserName")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="advertiserName"
          v-model="selectData.ad_owner_name"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.status" @keyup.enter.native="getData">
          <el-option :label='$t("resources.Enabling")' value="0"></el-option>
          <el-option :label='$t("resources.Offline")' value="1"></el-option>
          <el-option :label='$t("common.delete")' value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="selectData.times"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="seach">{{$t("common.search")}}</el-button>
      </div>
    </div>

    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button type="primary" @click="create">{{$t("common.add")}}</el-button>
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
          <el-table-column :label='$t("resources.advertiserID")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("resources.advertiserName")' align="center" prop="ad_owner_name"></el-table-column>
          <el-table-column :label='$t("common.img")' align="center">
            <template slot-scope="scope">
              <img :src="scope.row.logo" width="40" height="40" class="head_pic" />
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="ctime"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0">{{$t("resources.Enabling")}}</span>
              <span v-if="scope.row.status==1" style="color:pink">{{$t("resources.Offline")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="editUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
              <el-button type="text" size="small" @click="open(scope.row)">{{$t("common.delete")}}</el-button>
              <el-button type="text" size="small" @click="downloadUserInfo(scope.row)">
                <span v-if="scope.row.status==1">{{$t("jurisdiction.Enable")}}</span>
                <span v-if="scope.row.status==0">{{$t("resources.offline")}}</span>
              </el-button>
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
    <!-- 弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("common.img")' :label-width="formLabelWidth">
          <el-upload
            action="/resource/ad_owner_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            list-type="picture"
            :data="type"
            :on-success="uploadSucc"
            :file-list="form.fileList"
            :limit="1"
            auto-upload
          >
            <el-button size="small" type="primary">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
        <el-form-item :label='$t("resources.advertiserName")' :label-width="formLabelWidth">
          <el-input v-model="form.ad_owner_name" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <!-- 添加 -->
        <el-button type="primary" v-if="addoredit==0" @click="confirmAdd">{{$t("confirm.ok")}}</el-button>
        <!-- 编辑 -->
        <el-button type="primary" v-else @click="confirmEdit">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>


<script>
export default {
  data() {
    return {
      loading: false,
      selectData: {
        id: "",
        ad_owner_name: "",
        times: [],
        status: ""
      },
      tableData: [],
      dialogFormVisible: false, //弹窗是否弹出
      form: {
        logo: [],
        ad_owner_name: "",
        fileList: [{ url: "" }]
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      id: "",
      addoredit: null //1:修改 0:添加
    };
  },
  created() {
    this.getData();
  },
  computed: {
    // 上传图片
    type() {
      return {
        type: "ad_owner_logo",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  methods: {
    clear() {
      this.selectData.id = "";
      this.ad_owner_name = "";
      this.times = [];
      this.status = "";
    },
    // 删除
    deleteUserInfo(id) {
      this.$http
        .post("/resource/ad_owner_update_status", {
          id: id,
          status: 2
        })
        .then(res => {
          this.getData();
        });
    },
    //  删除弹窗    物理删除
    open(row) {
      this.$confirm("刪除操作不可以恢復，是否繼續？", "提示", {
        confirmButtonText: "確定",
        cancelButtonText: "取消",
        type: "warning"
      })
        .then(() => {
          this.deladver(row.id);
          this.$message({
            type: "success",
            message: "success!"
          });
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: "Cancel"
          });
        });
    },
    //  广告商上线下线状态
    downloadUserInfo(row) {
      let status = 1;
      if (row.status == 1) {
        status = 0;
      } else {
        status = 1;
      }
      this.$http
        .post("/resource/ad_owner_update_status", {
          id: row.id,
          status: status
        })
        .then(res => {
          this.getData();
          this.$message({
            type: "success",
            message: res.msg
          });
        });
    },
    //  广告商删除
    deladver(id) {
      this.$http
        .post("/resource/ad_owner_del_status", {
          id: id
        })
        .then(res => {
          if (res.status) {
            this.getData();
            this.$message(res.msg);
          }
        });
    },
    // 创建
    create() {
      this.form.ad_owner_name = "";
      this.form.fileList = [];
      this.addoredit = 0;
      this.dialogFormVisible = true;
    },
    // 修改
    editUserInfo(row) {
      this.id = row.id;
      this.addoredit = 1;
      this.form.ad_owner_name = row.ad_owner_name;
      this.form.fileList.splice(0, 1, { url: row.logo });
      this.dialogFormVisible = true;
    },
    //  确认删除广告商接口
    confirmEdit() {
      this.$http
        .post("/resource/ad_owner_edit", {
          id: this.id,
          logo: this.form.logo,
          ad_owner_name: this.form.ad_owner_name
        })
        .then(res => {
          let msg = res.msg;
          this.$message(msg);
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    // 上传图片成功
    uploadSucc(response, file, fileList) {
      this.form.logo = response.data.path;
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    },
    //  分页

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 添加广告商接口
    confirmAdd() {
      this.$http
        .post("/resource/ad_owner_add", {
          logo: this.form.logo,
          ad_owner_name: this.form.ad_owner_name
        })
        .then(res => {
          this.dialogFormVisible = false;
          this.$message.success(res.msg);
          this.getData();
        });
    },
    //搜索
    seach() {
      this.page = 1;
      this.getData();
    },
    // 广告商列表接口
    getData() {
      this.loading = true;
      this.$http
        .post("/resource/ad_owner_list", {
          id: this.selectData.id,
          ad_owner_name: this.selectData.ad_owner_name,
          ctime_start: this.selectData.times[0],
          ctime_end: this.selectData.times[1],
          status: this.selectData.status,
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page_no;
          this.total = res.data.total;
        });
    },
    //导出
    exportExecel() {
      this.$http
        .post("/resource/export_ad_owner_list", {
          id: this.selectData.id,
          ad_owner_name: this.selectData.ad_owner_name,
          ctime_start: this.selectData.times[0],
          ctime_end: this.selectData.times[1],
          status: this.selectData.status,
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          if (res.status == 1) {
            window.open(`${window.location.origin}${res.data.path}`);
          } else {
            this.$message.error(res.msg);
          }
        });
    }
  }
};
</script>



<style lang="scss" scoped>
.advertiserlist {
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



