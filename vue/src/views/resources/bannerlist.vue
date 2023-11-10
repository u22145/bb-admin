<template>
  <div class="bannerlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="bannerID">bannerID</label>&nbsp;
        <el-input
          style="width: 200px"
          id="bannerID"
          v-model="selectData.id"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("resources.redirect")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.redirect" @keyup.enter.native="getData">
          <el-option label="APP" value="1"></el-option>
          <el-option label="H5" value="4"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.status" @keyup.enter.native="getData">
          <el-option :label='$t("resources.Enabling")' value="0"></el-option>
          <el-option :label='$t("resources.Offline")' value="1"></el-option>
          <el-option :label='$t("common.delete")' value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label style="margin-left: 20px">{{$t("resources.banner_type")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.banner_type" @keyup.enter.native="getData">
          <el-option :label='$t("resources.Livebroadcast")' value="1"></el-option>
          <el-option :label='$t("resources.news")' value="2"></el-option>
          <el-option label='社區banner' value="3"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("resources.banner_location")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.location" @keyup.enter.native="getData">
          <el-option label="1" value="1"></el-option>
          <el-option label="2" value="2"></el-option>
          <el-option label="3" value="3"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("resources.show_crowd")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.show_crowd" @keyup.enter.native="getData">
          <el-option :label='$t("common.all")' value="0"></el-option>
          <el-option :label='$t("resources.male")' value="1"></el-option>
          <el-option :label='$t("resources.female")' value="2"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="seach">查询</el-button>
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
          border
          style="width: 99%;margin-top: 30px"
          v-loading="loading"
        >
          <el-table-column label="bannerID" align="center" prop="id"></el-table-column>
          <el-table-column label="banner" align="center">
            <template slot-scope="scope">
              <img :src="scope.row.banner_pic" width="40" height="40" class="head_pic" />
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.banner_type")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.banner_type==1">{{$t("resources.Livebroadcast")}}</span>
              <span v-else-if="scope.row.banner_type==2">{{$t("resources.news")}}</span>
              <span v-else-if="scope.row.banner_type==3">社區banner</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.banner_location")' align="center" prop="location"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="ctime" min-width="120px"></el-table-column>
          <el-table-column :label='$t("resources.redirect")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.redirect==1">App</span>
              <span v-else-if="scope.row.redirect==2">H5</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.url")' align="center" prop="url" min-width="150px"></el-table-column>
          <el-table-column :label='$t("resources.show_crowd")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.show_crowd==0">{{$t("common.all")}}</span>
              <span v-if="scope.row.show_crowd==1">{{$t("common.male")}}</span>
              <span v-if="scope.row.show_crowd==2">{{$t("common.female")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.banner_desc")' align="center" min-width="150px">
            <template slot-scope="scope">
              <p>{{scope.row.banner_desc}}</p>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status">
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
    <el-dialog title="banner" :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item label="banner" :label-width="formLabelWidth">
          <el-upload
            action="/resource/banner_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            list-type="picture"
            :file-list="form.fileList"
            :data="type"
            :on-success="uploadSucc"
            auto-upload
            :limit="1"
          >
            <el-button size="small" type="primary">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
        <el-form-item :label='$t("resources.redirect")' :label-width="formLabelWidth">
          <el-radio v-model=" form.redirect" label="1">APP</el-radio>
          <el-form-item :label='$t("resources.page")'>
            <el-select v-model="form.url" placeholder>
              <el-option :label='$t("resources.Real_name")' value="1"></el-option>
              <el-option :label='$t("resources.Anchor")' value="2"></el-option>
              <el-option :label='$t("resources.make_money")' value="3"></el-option>
            </el-select>
          </el-form-item>
          <el-radio v-model="form.redirect" label="2">H5</el-radio>
          <el-form-item :label='$t("resources.url")'>
            <el-input v-model="form.urlH5" autocomplete="off"></el-input>
          </el-form-item>
        </el-form-item>
        <el-form-item :label='$t("resources.show_crowd")' :label-width="formLabelWidth">
          <el-radio v-model="form.show_crowd" label="0">{{$t("common.all")}}</el-radio>
          <el-radio v-model="form.show_crowd" label="1">{{$t("resources.male")}}</el-radio>
          <el-radio v-model="form.show_crowd" label="2">{{$t("resources.female")}}</el-radio>
        </el-form-item>
        <el-form-item :label='$t("resources.banner_type")' :label-width="formLabelWidth">
          <el-radio v-model="form.banner_type" label="1">{{$t("resources.Livebroadcast")}}</el-radio>
          <el-radio v-model="form.banner_type" label="2">{{$t("resources.news")}}</el-radio>
          <el-radio v-model="form.banner_type" label="3">社區banner</el-radio>
        </el-form-item>
        <el-form-item :label='$t("resources.banner_desc")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.banner_desc" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.banner_location")' :label-width="formLabelWidth">
          <el-select v-model="form.location" placeholder>
            <el-option label="1" value="1"></el-option>
            <el-option label="2" value="2"></el-option>
            <el-option label="3" value="3"></el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" v-if="addoredit==1" @click="confirmAdd">{{$t("confirm.ok")}}</el-button>
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
        redirect: "",
        status: "",
        banner_type: "",
        location: null,
        show_crowd: ""
      },
      addoredit: null, //1:创建 0：修改
      tableData: [],
      dialogFormVisible: false,
      form: {
        pic: [],
        redirect: [],
        url: "",
        show_crowd: [],
        banner_desc: "",
        banner_type: [],
        location: "",
        urlH5: "",
        fileList: [{ url: "" }]
      },
      formLabelWidth: "120px",
      id: "",
      page: 1, //当前页
      limit: this.$store.state.adminPageSize, //每页条数
      total: 1 //总条数
    };
  },
  created() {
    this.getData();
  },
  computed: {
    type() {
      return {
        type: "banner",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  methods: {
    clear() {
      this.selectData.id = "";
      this.selectData.redirect = "";
      this.selectData.status = "";
      this.selectData.banner_type = "";
      this.selectData.location = null;
      this.selectData.show_crowd = "";
    },
    //编辑
    editUserInfo(row) {
      this.addoredit = 0;
      this.id = row.id;
      this.form.redirect = row.redirect;
      this.form.show_crowd = row.show_crowd;
      this.form.banner_desc = row.banner_desc;
      this.form.banner_type = row.banner_type;
      this.form.location = row.location;
      this.form.fileList.splice(0, 1, { url: row.banner_pic });
      // this.form.fileList=[{url:row.banner_pic}]
      this.form.pic = row.pic;
      if (row.redirect == 1) {
        this.form.url = row.url;
      } else {
        this.form.urlH5 = row.url;
      }
      this.dialogFormVisible = true;
    },
    //  确认编辑banner
    confirmEdit() {
      if (this.form.redirect == 1) {
        let url = this.form.url;
        this.$http
          .post("/resource/banner_edit", {
            id: this.id,
            pic: this.form.pic,
            redirect: this.form.redirect,
            url: url,
            banner_type: this.form.banner_type,
            location: this.form.location,
            show_crowd: this.form.show_crowd,
            banner_desc: this.form.banner_desc
          })
          .then(res => {
            this.dialogFormVisible = false;
            this.getData();
          });
      } else {
        let url = this.form.urlH5;
        this.$http
          .post("/resource/banner_edit", {
            id: this.id,
            pic: this.form.pic,
            redirect: this.form.redirect,
            url: url,
            banner_type: this.form.banner_type,
            location: this.form.location,
            show_crowd: this.form.show_crowd,
            banner_desc: this.form.banner_desc
          })
          .then(res => {
            this.dialogFormVisible = false;
            this.getData();
          });
      }
    },
    //  删除
    open(row) {
      this.$confirm("刪除操作不可以恢復，是否繼續？", "提示", {
        confirmButtonText: "確定",
        cancelButtonText: "取消",
        type: "warning"
      })
        .then(() => {
          this.deleteUserInfo(row.id);
          this.$message({
            type: "success",
            message: "SUCCESS!"
          });
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: "Cancel"
          });
        });
    },
    //创建
    create() {
      this.form.fileList = [];
      this.form.redirect = [];
      this.form.url = "";
      this.form.show_crowd = [];
      this.form.banner_desc = "";
      this.form.banner_type = [];
      this.form.location = "";
      this.form.urlH5 = "";
      this.addoredit = 1;
      this.dialogFormVisible = true;
    },
    // 确认创建banner
    confirmAdd() {
      if (this.form.redirect == 1) {
        let url = this.form.url;
        this.$http
          .post("/resource/banner_add", {
            pic: this.form.pic,
            redirect: this.form.redirect,
            url: url,
            banner_type: this.form.banner_type,
            location: this.form.location,
            show_crowd: this.form.show_crowd,
            banner_desc: this.form.banner_desc
          })
          .then(res => {
            this.dialogFormVisible = false;
            this.getData();
            this.$message(res.msg);
          });
      } else {
        let url = this.form.urlH5;
        this.$http
          .post("/resource/banner_add", {
            pic: this.form.pic,
            redirect: this.form.redirect,
            url: url,
            banner_type: this.form.pic,
            location: this.form.location,
            show_crowd: this.form.show_crowd,
            banner_desc: this.form.banner_desc
          })
          .then(res => {
            this.dialogFormVisible = false;
            this.getData();
          });
      }
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    // 获取banner列表
    getData() {
      this.loading = true;
      this.$http
        .post("/resource/banner_list", {
          id: this.selectData.id,
          redirect: this.selectData.redirect,
          status: this.selectData.status,
          banner_type: this.selectData.banner_type,
          location: this.selectData.location,
          show_crowd: this.selectData.show_crowd,
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
    //搜索
    seach() {
      this.page = 1;
      this.getData();
    },
    // 图片上传成功
    uploadSucc(response, file, fileList) {
      this.form.pic = response.data.path;
    },
    //  删除banner
    deleteUserInfo(id) {
      this.$http
        .post("/resource/banner_update_status", {
          id: id,
          status: 2
        })
        .then(res => {
          this.getData();
        });
    },
    //  更改banner
    downloadUserInfo(row) {
      let status = 1;
      if (row.status == 1) {
        status = 0;
      } else {
        status = 1;
      }
      this.$http
        .post("/resource/banner_update_status", {
          id: row.id,
          status: status
        })
        .then(res => {
          this.getData();
          this.$message(res.msg);
        });
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    },
    //导出
    exportExecel() {
      this.$http
        .post("/resource/export_banner_list", {
          id: this.selectData.id,
          redirect: this.selectData.redirect,
          status: this.selectData.status,
          banner_type: this.selectData.banner_type,
          location: this.selectData.location,
          show_crowd: this.selectData.show_crowd,
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
.bannerlist {
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
  p {
      white-space: nowrap;
      width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  .tableCon:after {
    content: "";
    display: block;
    clear: both;
  }
}
</style>



