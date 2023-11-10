<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t("channel.id")}}</label>&nbsp;
        <el-input style="width: 200px" v-model="id"></el-input>
        <label style="margin-left: 20px">{{$t("channel.name")}}</label>&nbsp;
        <el-input style="width: 200px" v-model="name"></el-input>
        <label style="margin-left: 20px">{{$t("channel.status")}}</label>&nbsp;
        <el-select placeholder v-model="type">
          <el-option :label="$t('common.all')" value="all"></el-option>
          <el-option :label="$t('channel.off')" value="off"></el-option>
          <el-option :label="$t('channel.on')" value="on"></el-option>
        </el-select>
        <el-button
          type="primary"
          class="fr"
          style="padding: 8px 30px"
          @click="search"
        >{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="createAppkey">{{$t("common.add")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column label="ID" align="center" prop="id"></el-table-column>
          <el-table-column label="父ID" align="center" prop="pid"></el-table-column>
          <el-table-column :label="$t('channel.name')" align="center" prop="name"></el-table-column>
          <el-table-column :label="'結算方式'" align="center" prop="type"></el-table-column>
          <el-table-column :label="'結算周期'" align="center" prop="period1"></el-table-column>
          <el-table-column :label="$t('channel.url')" align="center" prop="url"></el-table-column>
          <el-table-column :label="$t('channel.pic')" prop="avatar" align="center">
            <template slot-scope="scope">
              <img :src="scope.row.pic_url" alt width="40" height="40" class="head_pic" />
            </template>
          </el-table-column>
          <el-table-column :label="$t('channel.status')" align="center" prop="status_txt"></el-table-column>
          <el-table-column :label="$t('common.Creation_time')" align="center" prop="uptime"></el-table-column>
          <el-table-column
            :label="$t('common.operation')"
            align="center"
            width="130px"
            class-name="operation"
          >
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="copyLink(scope.row)">複製鏈接</el-button>
              <el-button type="text" size="small" @click="saveInfo(scope.row)">{{$t("common.modify")}}</el-button>
              <el-button
                type="text"
                size="small"
                v-if="scope.row.status == 1"
                @click="saveStatus(scope.row.id,0)"
              >{{$t("jurisdiction.Discontinue_use")}}</el-button>
              <el-button
                type="text"
                size="small"
                v-if="scope.row.status == 0"
                @click="saveStatus(scope.row.id,1)"
              >{{$t("jurisdiction.Enable")}}</el-button>
              <el-button
                type="text"
                size="small"
                @click="deleteSen(scope.row.id)"
              >{{$t("common.delete")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[10,20,30,50]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
          @size-change="sizeChange"
        ></el-pagination>
      </div>
    </div>
    <!-- 创建授权弹窗 -->
    <el-dialog :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label="$t('channel.name')" :label-width="formLabelWidth">
          <el-input v-model="form.name" autocomplete="off"></el-input>
        </el-form-item>
         <el-form-item :label="'上级渠道id'" :label-width="formLabelWidth">
          <el-input v-model="form.pid" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label="'通路賬號'" :label-width="formLabelWidth">
          <el-input v-model="form.id" autocomplete="off" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item :label="'通路密碼'" :label-width="formLabelWidth">
          <el-input v-model="form.password" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label="'結算方式'" :label-width="formLabelWidth">
          <el-select placeholder v-model="form.type">
            <el-option :label="'A類結算'" value="A"></el-option>
            <el-option :label="'C類結算'" value="C"></el-option>
            <el-option :label="'A+S類結算'" value="A+S"></el-option>
            <el-option :label="'S類結算'" value="S"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label="'結算周期'" :label-width="formLabelWidth">
          <el-select placeholder v-model="form.period">
            <el-option label="每周" value=1></el-option>
            <el-option label="半個月" value=2></el-option>
            <el-option label="每月" value=3></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label="$t('channel.pic')" :label-width="formLabelWidth">
          <el-upload
            class="upload-demo"
            action="/resource/voucher_upload"
            :on-preview="handlePreview"
            :on-remove="handleRemove"
            list-type="picture"
            :file-list="form.file"
            :on-success="handleAvatarSuccess"
            :before-upload="beforeAvatarUpload"
            :data="types"
          >
            <el-button size="small" type="primary">{{$t("money.Upload")}}</el-button>
            <div slot="tip" class="el-upload__tip">{{$t("money.jp")}}</div>
          </el-upload>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="createPost">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <el-dialog :visible.sync="copyLinkForm">
      <el-form :model="form">
        <el-form-item :label="$t('channel.name')" :label-width="formLabelWidth">
          {{form.name}}
        </el-form-item>
        <el-form-item :label="'結算方式'" :label-width="formLabelWidth">
          {{form.type}}
        </el-form-item>
        <el-form-item :label="'推廣二維碼'" :label-width="formLabelWidth">
          <img :src="form.qrcode" width="100" height="100" class="head_pic" />
        </el-form-item>
        <el-form-item :label="'推廣鏈接'" :label-width="formLabelWidth">
          {{form.url}}
        </el-form-item>
        <el-form-item :label="'登錄鏈接'" :label-width="formLabelWidth">
          {{form.login_url}}
        </el-form-item>
        <el-form-item :label="'登錄賬號'" :label-width="formLabelWidth">
          {{form.id}}
        </el-form-item>
        <el-form-item :label="'登錄密碼'" :label-width="formLabelWidth">
          {{form.password}}
        </el-form-item>
      </el-form>
    </el-dialog>
  </div>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      tableData: [],
      dialogFormVisible: false,
      copyLinkForm: false,
      form: {
        name: "",
        url:'',
        upload_img: "",
        fileList: [],
        file:[{url:''}],
        transactionStatus: "2",
        id:'',
        pid:'',
          password:'',
          type:'',
          period: '',
          period1: '',
          login_url:'',
          qrcode:'',
      },
      name: "",
      type: "all",
      formLabelWidth: "120px",
      page: 1,
      limit: 10,
      total: 1,
      id:'',
    };
  },
  created() {
    this.getData();
  },
  computed: {
    types() {
      return {
        type: "channel",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  methods: {
    deleteSen(id) {
      this.$confirm("刪除操作不可以恢復，是否繼續？", "提示", {
        confirmButtonText: "確定",
        cancelButtonText: "取消",
        type: "warning"
      })
        .then(() => {
          this.$http
            .post("other/del_channel", {
              id: id
            })
            .then(res => {
              this.$message({
                type: res.status == 1 ? "success" : "error",
                message: res.msg
              });
              this.getData();
            });
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: "Cancel"
          });
        });
    },
    createAppkey() {
      this.form = [];
      this.dialogFormVisible = true;
    },
    createPost() {
      this.$http
        .post("other/create_channel", {
          data: this.form
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    },
    search() {
      this.getData();
    },
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("other/channel_list", {
          page: this.page,
          page_size: this.limit,
          name: this.name,
          type: this.type,
            period: this.period,
            period1: this.period1,
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 2) {
            window.open(`${window.location.origin}${res.data.path}`);
          } else {
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
          }
        });
    },
    saveInfo(info){
      this.form.file = [{'url':info.pic_url}];
      this.form.name = info.name;
      this.form.password = info.password;
      this.form.type = info.type;
      this.form.period = info.period;
      this.form.upload_img = info.pic_url;
      this.form.id = info.id;
      this.form.pid = info.pid;
      this.dialogFormVisible = true;
    },
      copyLink(info){
          this.form.file = [{'url':info.pic_url}];
          this.form.name = info.name;
          this.form.password = info.password;
          this.form.type = info.type;
          this.form.upload_img = info.pic_url;
          this.form.id = info.id;
          this.form.url = info.url;
          this.form.login_url = info.login_url;
          this.form.qrcode = info.qrcode;
          this.copyLinkForm = true;
      },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    sizeChange(val) {
      this.limit = val;
      this.getData(val);
    },
    saveStatus(id, status) {
      this.$http
        .post("other/channel_status", {
          id: id,
          status: status
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.getData();
        });
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    },
    handleAvatarSuccess(res, file) {
      this.form.fileList.push(res.data.path);
      this.form.upload_img = res.data.path;
      this.imageUrl = URL.createObjectURL(file.raw);
    },
    beforeAvatarUpload(file) {
      const isLt2M = file.size / 1024 / 1024 < 2;

      if (!isLt2M) {
        this.$message.error("MAX 2MB!");
      }
      return isLt2M;
    }
  }
};
</script>

<style lang="scss" scoped>
.vipcollocation {
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



                            