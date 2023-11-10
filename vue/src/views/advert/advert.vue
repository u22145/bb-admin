<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">廣告渠道ID</label>&nbsp;
        <el-input style="width: 200px" v-model="id"></el-input>
        <label style="margin-left: 20px">廣告渠道名稱</label>&nbsp;
        <el-input style="width: 200px" v-model="name"></el-input>
        <label style="margin-left: 20px">狀態</label>&nbsp;
        <el-select placeholder v-model="status">
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
          <el-table-column :label="'廣告渠道名稱'" align="center" prop="name"></el-table-column>
          <el-table-column label="渠道代码" align="center" prop="code"></el-table-column>
          <el-table-column label="推廣鏈接" align="center" prop="url"></el-table-column>
          <el-table-column label="渠道描述" align="center" prop="desc"></el-table-column>
          <el-table-column :label="'狀態'" align="center" prop="status_txt"></el-table-column>
          <el-table-column :label="$t('common.Creation_time')" align="center" prop="uptime"></el-table-column>
          <el-table-column
            :label="$t('common.operation')"
            align="center"
            width="130px"
            class-name="operation"
          >
            <template slot-scope="scope">
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
          :page-sizes="[this.$store.state.adminPageSize]"
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
        <el-form-item label="廣告渠道名稱" :label-width="formLabelWidth">
          <el-input v-model="form.name" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="廣告渠道代号" :label-width="formLabelWidth">
          <el-input v-model="form.code" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label="'廣告渠道鏈接'" :label-width="formLabelWidth">
          <el-input v-model="form.url" autocomplete="off" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item :label="'廣告渠道短鏈接'" :label-width="formLabelWidth">
                  <el-input v-model="form.short_url" autocomplete="off" :disabled="true"></el-input>
                </el-form-item>
        <el-form-item :label="'廣告渠道描述'" :label-width="formLabelWidth">
          <el-input v-model="form.desc" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="createPost">{{$t("confirm.ok")}}</el-button>
      </div>
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
      form: {
        name: "",
        code: "",
        transactionStatus: "2",
        desc:"",
      },
      name: "",
        status: "all",
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      id:'',
    };
  },
  created() {
    this.getData();
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
            .post("advert/del_advert", {
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
        .post("advert/create_advert", {
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
        .post("advert/advert_list", {
          page: this.page,
          page_size: this.limit,
          name: this.name,
            id: this.id,
          status: this.status,
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
      this.form.name = info.name;
      this.form.url = info.url;
      this.form.desc = info.desc;
      this.form.id = info.id;
      this.dialogFormVisible = true;
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
        .post("advert/advert_status", {
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



                            