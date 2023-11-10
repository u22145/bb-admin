<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">銀商ID</label>&nbsp;
        <el-input style="width: 300px" v-model="id"></el-input>
        <label style="margin-left: 20px">銀商名稱</label>&nbsp;
        <el-input style="width: 300px" v-model="name"></el-input>
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
          <el-table-column label="銀商ID" align="center" prop="id"></el-table-column>
          <el-table-column label="銀商名稱" align="center" prop="mname"></el-table-column>
          <el-table-column label="聯係人" align="center" prop="contact"></el-table-column>
          <el-table-column label="電話" align="center" prop="phone"></el-table-column>
          <el-table-column label="QQ" align="center" prop="qq"></el-table-column>
          <el-table-column label="微信" align="center" prop="wechat"></el-table-column>
          <el-table-column label="金幣餘額" align="center" prop="balance"></el-table-column>
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
        <el-form-item :label="'銀商ID'" :label-width="formLabelWidth">
          <el-input v-model="form.id" autocomplete="off" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="銀商名稱" :label-width="formLabelWidth">
          <el-input v-model="form.mname" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="登錄密碼" :label-width="formLabelWidth">
          <el-input v-model="form.password" autocomplete="off" v-bind:disabled="form.id"></el-input>
        </el-form-item>
        <el-form-item label="聯係人" :label-width="formLabelWidth">
          <el-input v-model="form.contact" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="電話" :label-width="formLabelWidth">
          <el-input v-model="form.phone" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="QQ" :label-width="formLabelWidth">
          <el-input v-model="form.qq" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label="微信" :label-width="formLabelWidth">
          <el-input v-model="form.wechat" autocomplete="off"></el-input>
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
          id:'',
          mname: "",
          password:'',
          contact:'',
          phone:'',
          qq:'',
          wechat:'',
      },
      name: "",
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
  computed: {
    types() {
      return {
        type: "sales",
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
            .post("merchant/merchant_del", {
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
        this.form.id = 0;
      this.dialogFormVisible = true;
    },
    createPost() {
      this.$http
        .post("merchant/merchant_add", {
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
        .post("merchant/merchant_list", {
          page: this.page,
          page_size: this.limit,
          name: this.name,
            id: this.id,
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
        this.form.id = info.id;
        this.form.mname = info.mname;
        this.form.password = info.password;
        this.form.contact = info.contact;
        this.form.phone = info.phone;
        this.form.qq = info.qq;
        this.form.wechat = info.wechat;
        this.dialogFormVisible = true;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    sizeChange(val) {
      this.limit = val;
      this.getData(val);
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



                            