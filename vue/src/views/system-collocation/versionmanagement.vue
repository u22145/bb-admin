<template>
  <div class="versionmanagement">
    <!--
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("resources.type")}}</label>&nbsp;
        <el-select placeholder v-model="type">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option :label='$t("resources.Android")' value="1"></el-option>
          <el-option label="ios" value="2"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("resources.load")}}</label>&nbsp;
        <el-select placeholder v-model="load">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option :label='$t("money.yes")' value="yes"></el-option>
          <el-option :label='$t("money.on")' value="on"></el-option>
        </el-select>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
          v-model="times"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    //-->
    <div class="whiteBg">
      <!--
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="create">{{$t("common.add")}}</el-button>
      </div>
      //-->
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column :label='$t("resources.type")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.platform>=5">{{$t("resources.Android")}}</span>
              <span v-if="scope.row.platform==4 || scope.row.platform==1">iOS</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.version")' align="center" prop="version"></el-table-column>
          <el-table-column :label='$t("resources.uptitle")' align="center" prop="uptitle"></el-table-column>
          <el-table-column :label='$t("resources.enforce")' align="center">
            <template slot-scope="scope">
              <span>{{scope.row.enforce==1? $t("money.yes"):$t("money.no")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.Downloadaddress")' align="center" prop="url" min-width="250px"></el-table-column>
          <el-table-column :label='$t("resources.loadmode")' align="center">
            <template slot-scope="scope">
              <span>{{scope.row.download==1?$t("resources.urlload"):"Apple Store"}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime" min-width="130px"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
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

    <!-- 版本管理弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("resources.type")' :label-width="formLabelWidth">
          <el-select v-model="form.platform" placeholder disabled>
            <el-option :label='$t("common.all")' value="0"></el-option>
            <el-option :label='$t("resources.Android")' value="5"></el-option>
            <el-option :label='$t("resources.Android")' value="2"></el-option>
            <el-option label="iOS" value="4"></el-option>
            <el-option label="iOS" value="1"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("resources.version")' :label-width="formLabelWidth">
          <el-input v-model="form.version" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.uptitle")' :label-width="formLabelWidth">
          <el-input v-model="form.uptitle" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.uplog")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.uplog" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.enforce")' :label-width="formLabelWidth">
          <el-radio v-model="form.enforce" label="1">是</el-radio>
          <el-radio v-model="form.enforce" label="0">否</el-radio>
        </el-form-item>
        <el-form-item :label='$t("resources.download")' :label-width="formLabelWidth">
          <el-select v-model="form.download" placeholder>
            <el-option :label='$t("resources.urlload")' value="1"></el-option>
            <el-option label="Apple Store" value="2"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("resources.Downloadaddress")' :label-width="formLabelWidth">
          <el-input v-model="form.url" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="editUserInfo()">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data() {
    return {
      page: 1, //当前页
      limit: this.$store.state.adminPageSize, //每页条数
      total: 1,
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      form: {
        platform: "",
        version: "",
        uptitle: "",
        uplog: "",
        enforce: "",
        download: "",
        url: ""
      },
      formLabelWidth: "120px",
      type: "",
      load: "",
      status: "",
      times: []
    };
  },
  created() {
    this.getData();
  },
  methods: {
    clear() {
      this.type = "";
      this.load = "";
      this.times = [];
    },
    create() {
      this.form.platform = "";
      this.form.version = "";
      this.form.uptitle = "";
      this.form.uplog = "";
      this.form.enforce = "";
      this.form.download = "";
      this.form.url = "";
      this.dialogFormVisible = true;
    },
    seeUserInfo(row) {
      this.form.platform = row.platform;
      this.form.version = row.version;
      this.form.uptitle = row.uptitle;
      this.form.uplog = row.uplog;
      this.form.enforce = row.enforce;
      this.form.download = row.download;
      this.form.url = row.url;
      this.dialogFormVisible = true;
    },
    // 修改版本信息
    editUserInfo() {
      this.$http
        .post("/other/version_edit", {
          platform: this.form.platform,
          download: this.form.download,
          enforce: this.form.enforce,
          status: this.form.status,
          uplog: this.form.uplog,
          uptitle: this.form.uptitle,
          url: this.form.url,
          version: this.form.version
        })
        .then(res => {
          if (res.status == 1) {
            this.$message.success(res.msg);
            this.dialogFormVisible = false;
            this.getData();
          }
        });
    },
    search() {
      this.getData();
    },
    // 获取版本列表
    getData() {
      this.loading = true;
      this.$http
        .post("/other/version_list", {
          type: this.type,
          load: this.load,
          status: this.status,
          start_time: this.times[0],
          end_time: this.times[1]
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.list;
          this.page = res.data.page;
          this.total = res.data.total;
        });
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    }
  }
};
</script>

<style lang="scss" scoped>
.versionmanagement {
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



