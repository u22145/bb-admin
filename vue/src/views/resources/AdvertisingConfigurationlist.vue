<template>
  <div class="AdvertisingConfigurationlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="advertiserID">{{$t("resources.id")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="advertiserID"
          v-model="selectData.id"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="advertiserName">{{$t("resources.ad_title")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="advertiserName"
          v-model="selectData.ad_title"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="advertiser">{{$t("resources.owner_name")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="advertiser"
          v-model="selectData.ad_owner_name"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.status" @keyup.enter.native="getData">
            <el-option :label='$t("resources.Enabling")' value="0"></el-option>
          <el-option :label='$t("resources.Offline")' value="1"></el-option>
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
        <label>{{$t("resources.time")}}</label>&nbsp;
        <el-date-picker
          v-model="selectData.Maturitytime"
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
          <el-table-column :label='$t("resources.id")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("resources.ad_title")' align="center" prop="ad_title" min-width="120px"></el-table-column>
          <el-table-column :label='$t("common.img")' align="center">
            <template slot-scope="scope">
              <img :src="scope.row.ad_pic" width="40" height="40" class="head_pic" />
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.ad_video")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.ad_video">{{$t("money.yes")}}</span>
              <span v-if="!scope.row.ad_video" style="color:pink">{{$t("money.no")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.ad_url")' align="center" prop="ad_url" min-width="150px"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="ctime" min-width="120px"></el-table-column>
          <el-table-column :label='$t("resources.time")' align="center" prop="expire_time" min-width="120px"></el-table-column>
          <el-table-column :label='$t("resources.click_count")' align="center" prop="click_count"></el-table-column>
          <el-table-column :label='$t("resources.owner_name")' align="center" prop="owner_name"></el-table-column>
          <el-table-column :label='$t("resources.type")' align="center" prop="type">
            <template slot-scope="scope">
              <span v-if="scope.row.type==1">app</span>
              <span v-if="scope.row.type==2">H5</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.status")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.status==0">{{$t("resources.Enabling")}}</span>
              <span v-if="scope.row.status==1" style="color:pink">{{$t("resources.offline")}}</span>
            </template>
          </el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="120px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="editUserInfo(scope.row)">{{$t("common.modify")}}</el-button>
              <el-button type="text" size="small" @click="open(scope.row)">{{$t("common.delete")}}</el-button>
              <el-button type="text" size="small" @click="downloadUserInfo(scope.row)">{{$t("resources.offline")}}</el-button>
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
            action="/resource/ad_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            list-type="picture"
            :data="type"
            :on-success="uploadSucc"
            :before-upload="beforeUploadPic"
            :file-list="fileList"
            :limit="1"
            auto-upload
          >
            <el-button size="small" type="primary">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
        <el-form-item :label='$t("resources.ad_title")' :label-width="formLabelWidth">
          <el-input v-model="form.ad_title" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.owner_name")' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.ad_owner_id">
            <el-option v-for="v in owner" :label="v.ad_owner_name" :value="v.id" :key="v.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label='$t("resources.ad_url")' :label-width="formLabelWidth">
          <el-input v-model="form.ad_url" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.time")' :label-width="formLabelWidth">
          <el-date-picker
            v-model="form.expire_time"
            type="datetime"
            value-format="yyyy-MM-dd HH:mm:ss"
          ></el-date-picker>
        </el-form-item>
        <el-form-item :label='$t("resources.location")' :label-width="formLabelWidth">
          <el-radio v-model="form.location" label="0">{{$t("common.all")}}</el-radio>
          <el-radio v-model="form.location" label="1">{{$t("resources.location1")}}</el-radio>
          <el-radio v-model="form.location" label="2">{{$t("resources.location2")}}</el-radio>
        </el-form-item>
        <el-form-item :label='$t("resources.type")' :label-width="formLabelWidth">
          <el-radio v-model="form.type" label="1">app</el-radio>
          <el-radio v-model="form.type" label="2">h5</el-radio>
        </el-form-item>
        <el-form-item :label='$t("resources.ad_video")' :label-width="formLabelWidth">
          <el-upload
            class="avatar-uploader el-upload--text"
            :action="uploadUrl"
            :data="videotype"
            :show-file-list="false"
            :on-success="handleVideoSuccess"
            :before-upload="beforeUploadVideo"
            :on-progress="uploadVideoProcess"
          >
            <video
              v-if="this.form.ad_video !='' && !videoFlag"
              :src="this.form.ad_video"
              class="avatar video-avatar"
              controls="controls"
            >{{$t("resources.Tips1")}}</video>
            <i
              v-else-if="this.form.ad_video =='' && !videoFlag"
              class="el-icon-plus avatar-uploader-icon"
            ></i>
            <el-progress
              v-if="videoFlag == true"
              type="circle"
              :percentage="videoUploadPercent"
              style="margin-top:30px;"
            ></el-progress>
            <el-button
              class="video-btn"
              slot="trigger"
              size="small"
              v-if="isShowUploadVideo"
              type="primary"
            >{{$t("money.Upload")}}</el-button>
          </el-upload>
          <P v-if="isShowUploadVideo" class="text">{{$t("resources.Tips2")}}</P>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" v-if="addoredit==0" @click="addAd">{{$t("confirm.ok")}}</el-button>
        <el-button type="primary" v-else @click="editAd">{{$t("confirm.ok")}}</el-button>
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
        ad_title: "",
        ad_owner_name: "",
        status: "",
        times: [],
        Maturitytime: []
      },
      addFileName: "",
      tableData: [],
      dialogFormVisible: false,
      form: {
        ad_pic: "",
        expire_time: "",
        ad_owner_id: "",
        ad_url: "",
        ad_title: "",
        location: "0",
        ad_video: "",
        type: "1",
        ad_logo: "",
        ad_owner_name: ""
      },
      //广告商
      owner: [],
      fileList: [
        {
          url: ""
        }
      ],
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      addoredit: null, //1:修改 0:添加
      id: "",
      uploadUrl: "/resource/ad_upload", //你要上传视频到你后台的地址
      videoFlag: false, //是否显示进度条
      videoUploadPercent: "", //进度条的进度，
      isShowUploadVideo: true, //显示上传按钮
      global: {
        company: {
          showVideoPath: ""
        }
      }
    };
  },
  created() {
    this.getData();
  },
  computed: {
    //上传图片参数
    type() {
      return {
        type: "ad_pic",
        usercode: window.localStorage.getItem("usercode")
      };
    },
    //上传图片参数
    videotype() {
      return {
        type: "ad_video",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  methods: {
    clear() {
      this.selectData.id = "";
      this.selectData.ad_title = "";
      this.selectData.ad_owner_id = "";
      this.selectData.status = "";
      this.selectData.times = [];
      this.selectData.Maturitytime = [];
    },
    //视频上传前回调
    beforeUploadVideo(file) {
      const isLt200M = file.size / 1024 / 1024 < 200;
      if (
        [
          "video/mp4",
          "video/rmvb",
          "video/ogg",
          "video/flv",
          "video/avi",
          "video/wmv",
          "video/rmvb"
        ].indexOf(file.type) == -1
      ) {
        this.$message.error("Type ERROR");
        return false;
      }
      if (!isLt200M) {
        this.$message.error("Max:200MB!");
        return false;
      }
      this.isShowUploadVideo = false;
    },
    //进度条
    uploadVideoProcess(event, file, fileList) {
      this.videoFlag = true;
      this.videoUploadPercent = file.percentage.toFixed(0) * 1;
    },
    //图片上传前回调
    beforeUploadPic(file) {
      const isLt5M = file.size / 1024 / 1024 < 5;
      // if (["image/jpg", "image/jpeg"].indexOf(file.type) == -1) {
      //   this.$message.error("请上传正确格式的图片");
      //   return false;
      // }
      if (!isLt5M) {
        this.$message.error("Max:200MB!");
        return false;
      }
      // this.isShowUploadVideo = false;
    },
    //上传成功回调
    handleVideoSuccess(res, file) {
      this.isShowUploadVideo = true;
      this.videoFlag = false;
      this.videoUploadPercent = 0;
      if (res.status) {
        this.form.ad_video = res.data.path;
      } else {
        this.$message.error("ERROR！");
      }
    },

    // 创建
    create() {
      this.form.location = "0";
      this.form.type = "1";
      this.form.ad_url = "";
      this.form.ad_title = "";
      this.form.ad_url = "";
      this.form.ad_url = "";
      this.form.ad_video = "";
      this.form.ad_owner_id = "";
      this.fileList = [];
      this.addoredit = 0;
      this.dialogFormVisible = true;
    },
    // 修改
    editUserInfo(row) {
      this.addoredit = 1;
      this.form = row;
      this.form.location = row.location;
      this.form.type = row.type;
      this.fileList.splice(0, 1, { url: row.ad_pic });
      this.id = row.id;
      this.dialogFormVisible = true;
    },
    //添加广告
    addAd() {
      for (let i = 0; i < this.owner.length; i++) {
        if (this.owner[i].id == this.form.ad_owner_id) {
          this.form.ad_logo = this.owner[i].logo;
          this.form.ad_owner_name = this.owner[i].ad_owner_name;
          break;
        }
      }
      this.$http
        .post("/resource/ad_add", {
          ad_title: this.form.ad_title,
          ad_url: this.form.ad_url,
          ad_pic: this.form.ad_pic,
          ad_video: this.form.ad_video,
          ad_type: this.form.type,
          ad_logo: this.form.ad_logo,
          ad_owner_name: this.form.ad_owner_name,
          expire_time: this.form.expire_time,
          ad_owner_id: this.form.ad_owner_id,
          location: this.form.location
        })
        .then(res => {
          this.dialogFormVisible = false;
          this.$message.success(res.msg);
          this.getData();
        });
    },
    //编辑广告
    editAd() {
      for (let i = 0; i < this.owner.length; i++) {
        if (this.owner[i].id == this.form.ad_owner_id) {
          this.form.ad_logo = this.owner[i].logo;
          this.form.ad_owner_name = this.owner[i].ad_owner_name;
          break;
        }
      }
      console.log(this.form);
      this.$http
        .post("/resource/ad_edit", {
          id: this.id,
          ad_title: this.form.ad_title,
          ad_url: this.form.ad_url,
          ad_video: this.form.ad_video,
          ad_type: this.form.type,
          ad_logo: this.form.ad_logo,
          ad_owner_name: this.form.ad_owner_name,
          expire_time: this.form.expire_time,
          ad_owner_id: this.form.ad_owner_id,
          location: this.form.location,
          ad_pic: this.form.ad_pic
        })
        .then(res => {
          let msg = res.msg;
          this.dialogFormVisible = false;
          this.getData();
          this.$message({
            type: "success",
            message: res.msg
          });
        });
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
    //删除广告
    deleteUserInfo(id) {
      this.$http
        .post("/resource/ad_del", {
          id: id
        })
        .then(res => {
          this.getData();
        });
    },
    //下线广告
    downloadUserInfo(row) {
      this.$http
        .post("/resource/ad_update_status", {
          id: row.id,
          status: 1
        })
        .then(res => {
          let msg = res.msg;
          this.getData();
          this.$message(msg);
        });
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    //上传图片成功
    uploadSucc(response, file, fileList) {
      this.form.ad_pic = response.data.path;
    },
    //搜索
    seach() {
      this.page = 1;
      this.getData();
    },
    //获取广告列表
    getData() {
      this.loading = this;
      this.$http
        .post("/resource/ad_list", {
          id: this.selectData.id,
          ad_title: this.selectData.ad_title,
          ad_owner_name: this.selectData.ad_owner_name,
          status: this.selectData.status,
          ctime_start: this.selectData.times[0],
          ctime_end: this.selectData.times[1],
          expire_time_start: this.selectData.Maturitytime[0],
          expire_time_end: this.selectData.Maturitytime[1],
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.loading = false;
          this.tableData = res.data.data;
          this.page = res.data.page_no;
          this.total = res.data.total;
          this.owner = res.data.owner_res;
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
        .post("/resource/export_ad_list", {
          id: this.selectData.id,
          ad_title: this.selectData.ad_title,
          ad_owner_id: this.selectData.ad_owner_id,
          status: this.selectData.status,
          ctime_start: this.selectData.times[0],
          ctime_end: this.selectData.times[1],
          expire_time_start: this.selectData.Maturitytime[0],
          expire_time_end: this.selectData.Maturitytime[1],
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
.AdvertisingConfigurationlist {
  .uploadVideo {
    color: #fff;
    background-color: #409eff;
    border-color: #409eff;
    padding: 0 5px;
    font-size: 13px;
    border-radius: 3px;
    width: 60px;
    text-align: center;
    position: absolute;
    cursor: pointer;
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

  .avatar-uploader .el-upload {
    border: 1px dashed #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
  }
  .avatar-uploader .el-upload:hover {
    border-color: #409eff;
  }
  .avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 178px;
    height: 178px;
    line-height: 178px;
    text-align: center;
  }
  .avatar {
    width: 178px;
    height: 178px;
    display: block;
  }
  .video-avatar {
    width: 400px;
    height: 200px;
  }
}
</style>



      