<template>
  <div class="giftlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="giftID">{{$t("resources.giftID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="giftID"
          v-model="selectData.id"
          @keyup.enter.native="getData"
        ></el-input>
        <label for="giftname">{{$t("resources.giftname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="giftname"
          v-model="selectData.gift"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="selectData.status" @keyup.enter.native="getData">
          <el-option :label='$t("resources.Enabling")' value="1"></el-option>
          <el-option :label='$t("resources.Offline")' value="0"></el-option>
        </el-select>
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
          <el-table-column :label='$t("resources.giftID")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("common.img")' align="center">
            <template slot-scope="scope">
              <img :src="scope.row.gift_url" width="40" height="40" class="head_pic" />
            </template>
          </el-table-column>
          <el-table-column :label='$t("resources.giftname")' align="center" prop="gift"></el-table-column>
          <el-table-column :label='$t("resources.giftfee")' align="center" prop="fee"></el-table-column>
          <el-table-column :label='$t("resources.giftlocation")' align="center" prop="location"></el-table-column>
          <el-table-column :label='$t("resources.gifteffect")' align="center" prop="effect"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center">
            <template slot-scope="scope">
              <span v-if="scope.row.status==1">{{$t("resources.Enabling")}}</span>
              <span v-if="scope.row.status==0" style="color:pink">{{$t("resources.Offline")}}</span>
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
          @size-change="sizeChange"
        ></el-pagination>
      </div>
    </div>
    <!-- 弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("common.img")' :label-width="formLabelWidth">
          <el-upload
            action="/resource/gift_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            list-type="picture"
            :file-list="form.file"
            :data="type"
            :on-success="uploadSucc"
            auto-upload
            :limit="1"
          >
            <el-button size="small" type="primary" slot="trigger">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
        <el-form-item :label='$t("resources.giftname")' :label-width="formLabelWidth">
          <el-input v-model="form.gift" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.giftlocation")' :label-width="formLabelWidth">
          <el-input v-model="form.location" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("resources.giftfee")' :label-width="formLabelWidth">
          <el-input v-model="form.fee" autocomplete="off"></el-input>
        </el-form-item>
         <el-form-item :label='$t("resources.gifteffect")' :label-width="formLabelWidth">
          <el-input v-model="form.effect" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="confirmEdit" v-if="addoredit===1">{{$t("confirm.ok")}}</el-button>
        <el-button type="primary" @click="addgift" v-if="addoredit===0">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>


<script>
export default {
  data() {
    return {
      loading: false,
      addoredit: null, //1:修改 0:添加
      tableData: [],
      selectData: {
        id: "",
        gift: "",
        status: ""
      },
      dialogFormVisible: false,
      form: {
        gift: "",
        fileList: [],
        fee: "",
        location: "",
        effect: "",
        file: [{ url: "" }]
      },
      formLabelWidth: "120px",
      page: 1, //当前页
      limit: this.$store.state.adminPageSize, //每页条数
      total: 1 //总共条数
    };
  },
  created() {
    this.getData();
  },
  computed: {
    type() {
      return {
        type: "gift",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  methods: {
    clear() {
      this.selectData.id = "";
      this.selectData.gift = "";
      this.selectData.status = "";
    },
    // 创建
    create() {
      this.form.file = [];
      this.form.gift = '';
      this.form.fee = '';
      this.form.location = '';
      this.form.effect = '';
      this.addoredit = 0;
      this.dialogFormVisible = true;
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
    // 修改
    editUserInfo(row) {
      this.addoredit = 1;
      this.form.file.splice(0, 1, { url: row.gift_url });
      this.form.gift = row.gift;
      this.form.fee = row.fee;
      this.form.effect = row.effect;
      this.form.location = row.location;
      this.dialogFormVisible = true;
      this.form.id = row.id;
    },
    // 确认编辑
    confirmEdit() {
      this.$http
        .post("/resource/gift_edit", {
          id: this.form.id,
          gift: this.form.gift,
          fee: this.form.fee,
          location: this.form.location,
          effect: this.form.effect,
          gift_pic: this.form.fileList
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: res.status == 1 ? "success" : "error"
          });
          if (res.status == 1) {
            this.getData();
          }
          this.dialogFormVisible = false;
        });
    },
    //  删除
    deleteUserInfo(id) {
      this.$http
        .post("/resource/gift_del", {
          id: id
        })
        .then(res => {
          this.getData();
        });
    },
    //  礼物启用下线状态
    downloadUserInfo(row) {
      this.$http
        .post("/resource/gift_update_status", {
          id: row.id
        })
        .then(res => {
          let msg = res.msg;
          this.getData();
          this.$message(msg);
        });
    },
    // 添加礼物
    addgift() {
      this.$http
        .post("/resource/gift_add", {
          gift: this.form.gift,
          fee: this.form.fee,
          location: this.form.location,
          effect: this.form.effect,
          gift_pic: this.form.fileList
        })
        .then(res => {
          this.dialogFormVisible = false;
          this.$message.success(res.msg);
          this.getData();
        });
    },
    uploadSucc(response, file, fileList) {
      this.form.fileList = response.data.path;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    sizeChange(val){
      this.limit = val;
      this.getData(val);
    },
    //搜索
    seach() {
      this.page = 1;
      this.getData();
    },
    // 获取礼物列表
    getData(page_limit = 0) {
      this.loading = true;
      this.$http
        .post("/resource/gift_list", {
          id: this.selectData.id,
          gift: this.selectData.gift,
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
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    },
    // 导出
    exportExecel() {
      this.$http
        .post("/resource/export_gift_list", {
          id: this.selectData.id,
          gift: this.selectData.gift,
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
.giftlist {
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



      