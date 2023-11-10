<template>
  <div class="invitationlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="uid"
          id="selectUserID"
          @keyup.enter.native="seach"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="nickname"
          id="nickname"
          @keyup.enter.native="seach"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="status" @keyup.enter.native="seach">
          <el-option :label='$t("common.all")' value></el-option>
          <el-option :label='$t("common.Unaudited")' value="0"></el-option>
          <el-option :label='$t("common.Audit_pass")' value="1"></el-option>
          <el-option :label='$t("common.Audit_failed")' value="2"></el-option>
        </el-select>
        <label style="margin-left: 20px">{{$t("common.fee")}}</label>&nbsp;
        <el-select placeholder v-model="fee_status" @keyup.enter.native="seach">
          <el-option :label='$t("common.all")' value></el-option>
          <el-option :label='$t("common.fee_yes")' value="1"></el-option>
          <el-option :label='$t("common.fee_no")' value="2"></el-option>
        </el-select>
      </div>
      <div class="screen">
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="times"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" @click="seach" style="padding: 8px 30px">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear"> {{$t("common.clear")}}</el-button>
        <el-button type="primary" @click="passStatus(0)"> {{$t("common.batch_pass")}}</el-button>
        <el-button @click="getList(2)"> {{$t("common.export")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          v-loading="loading"
          ref="multipleTable"
          :data="tableData"
          @selection-change="handleSelectionChange"
          border
          style="width: 99%;margin-top: 30px"
        >
          <el-table-column :selectable="checkboxT" type="selection" width="55" align="center"></el-table-column>
          <el-table-column :label='$t("commentlist.tid")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column :label='$t("common.img")' prop="pic_url" align="center">
            <template slot-scope="scope">
              <img :src="scope.row.pic" alt width="40" height="40" class="head_pic" />
            </template>
          </el-table-column>
          <el-table-column :label='$t("commentlist.tcontent")' align="center" min-width="150px">
            <template slot-scope="scope">
              <p>{{scope.row.content}}</p>
            </template>
          </el-table-column>
          <el-table-column :label='$t("commentlist.Praise_points")' align="center" prop="likes_num"></el-table-column>
          <el-table-column :label='$t("commentlist.Comment_number")' align="center" prop="review_num"></el-table-column>
          <el-table-column :label='$t("commentlist.Forwarding_number")' align="center" prop="share_num"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_txt"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime" min-width="120px"></el-table-column>
          <el-table-column :label='$t("common.balance")' align="center" prop="fee" min-width="120px"></el-table-column>
          <el-table-column :label='$t("common.buy_num")' align="center" prop="buy_num" min-width="120px"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="150px" class-name="operation">
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="editTop(scope.row.id, 1)"
                v-if="scope.row.top ==0"
              >置頂</el-button>
              <el-button
                type="text"
                size="small"
                @click="editTop(scope.row.id, 2)"
                v-if="scope.row.top >0"
                style="color:red"
              >取消置頂</el-button>
              <el-button
                type="text"
                size="small"
                @click="passStatus(scope.row)"
                v-if="scope.row.status ==0||scope.row.status ==2||scope.row.status ==3"
              >{{$t("common.adopt")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="passNoshow(scope.row)"
                v-if="scope.row.status ==0||scope.row.status ==1"
              >{{$t("common.not_pass")}}</el-button>
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
            </template>
          </el-table-column>
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
    <!-- 不通过弹窗 -->
    <el-dialog :title='$t("common.not_pass")' :visible.sync="dialogFormVisible" width="40%">
      <el-form :model="form">
        <el-form-item :label='$t("confirm.reason")'>
          <el-input type="textarea" v-model="form.content"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="passNostatus">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  
    <!-- 帖子详情页 -->
    <Invitationdetails :status="show" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>


<script>
export default {
  components: {
    Invitationdetails: () => import("../../components/invitationdetails")
  },
  data() {
    return {
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      form: {
        content: "",
        status: 2
      },
      show: false,
      status: "",
      fee_status:"",
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      trial_status: "",
      uid: "",
      id: "",
      nickname: "",
      selectid: [],
      noPass_uid: 0,
      times: []
    };
  },
  mounted() {
    this.getList();
  },
  methods: {
    clear() {
      this.uid = "";
      this.status = "";
      this.fee_status = "";
      this.nickname = "";
      this.times = [];
    },
    changeStatus(data) {
      this.show = data;
      this.getList(1);
    },
    //查看用户信息
    seeUserInfo(row) {
      this.id = row.id;
      this.show = true;
    },
    //选择页码
    handleCurrentChange(val) {
      this.page = val;
      this.getList(1);
    },
    //搜索
    seach() {
      this.page = 1;
      this.getList(1);
    },
    //审核通过
    passStatus(row) {
      let review_id;
      if (row.id) {
        review_id = row.id;
      } else {
        review_id = this.selectid;
      }
      this.$http
        .post("/blog/update", {
          blog_id: review_id,
          status: 1,
          description: ""
        })
        .then(res => {
          if (res.status) {
            this.$message.success(res.msg);
            this.getList();
          }
        });
    },
    editTop(row, t) {
      this.$http
        .post("/blog/edit_top", {
          blog_id: row,
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
    //不通过弹窗
    passNoshow(row) {
      this.selectid = row.id;
      this.noPass_uid = row.uid;
      this.dialogFormVisible = true;
    },
  
    //审核不通过
    passNostatus() {
      this.$http
        .post("/blog/update", {
          blog_id: this.selectid,
          status: 2,
          uid: this.noPass_uid,
          description: this.form.content
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.getList();
          this.dialogFormVisible = false;
        });
    },
    //获取数据
    getList(type = 1) {
      this.loading = true;
      this.$http
        .post("/blog/blog_list", {
          page_no: this.page,
          uid: this.uid,
          nickname: this.nickname,
          status: this.status,
          fee_status: this.fee_status,
          tid: 0,
          starttime: this.times[0],
          endtime: this.times[1],
          type: type
        })
        .then(res => {
          if (type == 1) {
            this.loading = false;
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
    //选中多选
    handleSelectionChange(val) {
      this.selectid = "";
      this.multipleSelection = val;
      this.multipleSelection.map(item => {
        this.selectid += item.id + ",";
      });
      this.selectid = this.selectid.substr(0, this.selectid.length - 1);
    },
    //复选框
    checkboxT(row, rowIndex) {
      if (row.status == 1) {
        return false; //禁用
      } else {
        return true; //不禁用
      }
    }
   
  }
};
</script>


<style lang="scss" scoped>
.invitationlist {
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


