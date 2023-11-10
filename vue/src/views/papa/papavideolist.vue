<template>
  <div class="videolist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("jurisdiction.labelID")}}</label>&nbsp;
        <el-input
          v-model="postparm.uid"
          clearable
          style="width: 200px"
          id="selectUserID"
          @keyup.enter.native="getList(1)"
        ></el-input>
        <label for="videoID">{{$t("papa.videoID")}}</label>&nbsp;
        <el-input
          v-model="postparm.video"
          clearable
          style="width: 200px"
          id="videoID"
          @keyup.enter.native="getList(1)"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select v-model="postparm.status" placeholder @keyup.enter.native="getList(1)">
           <el-option :label='$t("common.all")' value></el-option>
          <el-option :label='$t("common.Unaudited")' value="0"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
         <label style="margin-left: 20px">{{$t("common.fee")}}</label>&nbsp;
        <el-select v-model="postparm.fee_status" placeholder @keyup.enter.native="getList(1)">
           <el-option :label='$t("common.all")' value></el-option>
          <el-option :label='$t("common.fee_yes")' value="1"></el-option>
          <el-option :label='$t("common.fee_no")' value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          v-model="postparm.nickname"
          clearable
          style="width: 200px"
          id="nickname"
          @keyup.enter.native="getList(1)"
        ></el-input>
        <label for="videoTitle">{{$t("papa.videoTitle")}}</label>&nbsp;
        <el-input
          v-model="postparm.title"
          clearable
          style="width: 200px"
          id="videoTitle"
          @keyup.enter.native="getList(1)"
        ></el-input>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="postparm.datetime"
          @change="selectTime"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
        ></el-date-picker>
        <el-button @click="search()" type="primary" class="fr" style="padding: 8px 30px">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getList(2)">{{$t("common.export")}}</el-button>
      </div>
      <div class="tableCon" style="margin-top:80px" v-loading="loading">
        <div class="flex">
          <el-card :body-style="{ padding: '0px'}" v-for="(item, index) in tableData" :key="index">
            <img :src="item.pic_url" class="image" style="height:250px;" />
            <div style="padding: 14px;">
              <p>
                {{$t("papa.videoID")}}：
                <span>{{item.id}}</span>
              </p>
              <p>
                {{$t("papa.videoTitle")}}：
                <span>{{item.title}}</span>
              </p>
              <p>
                {{$t("common.nickname")}}：
                <span>{{item.nickname}}</span>
              </p>
              <p>
                {{$t("jurisdiction.labelID")}}：
                <span>{{item.uid}}</span>
              </p>
              <p>
                {{$t("common.Creation_time")}}：
                <span>{{item.uptime}}</span>
              </p>
              <p>
                {{$t("commentlist.Comment_number")}}：
                <span>{{item.review_num}}</span>
              </p>
              <p>
                 {{$t("commentlist.Praise_points")}}：
                <span>{{item.likes_num}}</span>
              </p>
              <p>
                 {{$t("commentlist.Forwarding_number")}}：
                <span>{{item.share_num}}</span>
              </p>
               <p>
                 {{$t("commentlist.fee")}}：
                <span>{{item.fee}}</span>
              </p>
               <p>
                 {{$t("commentlist.buy_num")}}：
                <span>{{item.buy_num}}</span>
              </p>
              <p>
                {{$t("common.status")}}：
                <span v-if="item.status==0" style="color:red">{{item.status_txt}}</span>
                <span v-if="item.status==2" style="color:pink">{{item.status_txt}}</span>
                <span v-if="item.status==1" style="color:green">{{item.status_txt}}</span>
              </p>
              <div class="bottom clearfix">
                <el-button type="text" class="button" @click="seeUserInfo(item)">{{$t("common.See")}}</el-button>
                <el-button
                  type="text"
                  class="button"
                  @click="editTop(item.id, 1)"
                  v-if="item.top == 0"
                >置頂</el-button>
                <el-button
                  type="text"
                  class="button"
                  @click="editTop(item.id, 2)"
                  v-if="item.top > 0"
                  style="color:red"
                >取消置頂</el-button>
                <el-button
                  type="text"
                  class="button"
                  @click="passStatus(item.id,1)"
                  v-if="item.status == 0||item.status == 2"
                >{{$t("common.adopt")}}</el-button>
                <el-button
                  type="text"
                  class="button"
                  @click="noPassshow(item.id)"
                  v-if="item.status == 0||item.status == 1"
                >{{$t("common.not_pass")}}</el-button>
              </div>
            </div>
          </el-card>
        </div>
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
    <!------------------------屏蔽弹窗----------------------------->
    <el-dialog  :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("confirm.reason")'>
          <el-input type="textarea" v-model="form.content" placeholder></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="noPass">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <!-- 啪啪视频详情页 -->
    <Papavideodetails
      :status="status"
      :id="id"
      :video_url="video_url"
      v-on:changeStatus="changeStatus"
    />
  </div>
</template>
<script>
export default {
  components: {
    Papavideodetails: () => import("../../components/papavideodetails")
  },
  data() {
    return {
      loading: false,
      tableData: [],
      postparm: {
        uid: "",
        nickname: "",
        status: "",
        fee_status: "",
        video: "",
        title: "",
        datetime: [],
        description: ""
      },
      dialogFormVisible: false,
      form: {
        content: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      id: "",
      row: null,
      noID: 0,
      status: false,
      video_url: "",
      ts: "",
      html: "",
      url1: "",
    };
  },
  mounted() {
    this.url1 = this.html;
    this.getList();
  },
  methods: {
    search() {
      this.page = 1;
      this.getList(1);
    },
    clear() {
      this.postparm = {
        uid: "",
        status: "",
        video: "",
        title: "",
        datetime: [],
        description: ""
      };
    },
    seeUserInfo(item) {
        if (!item.ts) {
            this.id = item.id;
            this.status = true;
            this.video_url = item.video_url;
        } else {
            window.open("/index/ts_play?id=" + item.id, '_blank');
        }
    },
    changeStatus(data) {
      this.status = data;
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getList();
    },
    //审核
    passStatus(row, s) {
      //审核通过
      if (s == 1) {
        this.postparm.description = "";
      }
      this.$http
        .post("/papa/update", {
          papa_id: row,
          status: s,
          description: this.postparm.description
        })
        .then(res => {
          if (res.status) {
            this.$message({
              message: res.msg,
              type: "success"
            });
            this.getList();
          }
        });
    },
    editTop(row, t) {
      this.$http
        .post("/papa/edit_top", {
          papa_id: row,
          type: t,
        })
        .then(res => {
          if (res.status) {
            this.$message({
              message: res.msg,
              type: "success"
            });
            this.getList();
          }
        });
    },
    noPassshow(row) {
      this.noID = row;
      this.dialogFormVisible = true;
    },
    noPass() {
      this.dialogFormVisible = false;
      this.passStatus(this.noID, 2);
    },
    //获取数据
    getList(type = 1) {
      this.loading = true;
      this.$http
        .post("/papa/papa_list", {
          page_no: this.page,
          uid: this.postparm.uid,
          video_id: this.postparm.video,
          status: this.postparm.status,
          fee_status: this.postparm.fee_status,
          title: this.postparm.title,
          starttime: this.postparm.datetime[0],
          endtime: this.postparm.datetime[1],
          type: type,
          nickname: this.postparm.nickname
        })
        .then(res => {
          this.loading = false;
          if (type == 1) {
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
    },
    selectTime(val) {
      this.datetime = val;
    }
  }
};
</script>


<style lang="scss" scoped>
.videolist {
  .time {
    font-size: 13px;
    color: #999;
  }
  .flex {
    justify-content: flex-start;
    display: flex;
    flex-wrap: wrap;
    .el-card {
      width: 30%;
      margin: 15px;
    }
  }
  p {
    margin-bottom: 8px;
    span {
      display: inline-block;
      width: 80%;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }
  }
  .bottom {
    margin-top: 13px;
    line-height: 12px;
  }

  .button {
    padding: 0;
    float: right;
  }
  .el-button--text {
    margin-left: 10px;
  }
  .image {
    width: 100%;
    display: block;
  }

  .clearfix:before,
  .clearfix:after {
    display: table;
    content: "";
  }

  .clearfix:after {
    clear: both;
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


