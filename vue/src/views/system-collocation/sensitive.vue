<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t('sensitive.key')}}</label>&nbsp;
        <el-input style="width: 200px" v-model="name"></el-input>
        <label style="margin-left: 20px">{{$t("resources.type")}}</label>&nbsp;
        <el-select placeholder v-model="type">
          <el-option :label="$t('common.all')" value="0"></el-option>
          <el-option :label="$t('sensitive.Politics')" value="1"></el-option>
          <el-option :label="$t('sensitive.Yellowish')" value="2"></el-option>
          <el-option :label="$t('sensitive.Abuse')" value="3"></el-option>
          <el-option :label="$t('sensitive.Advertisement')" value="4"></el-option>
          <el-option :label="$t('sensitive.else')" value="5"></el-option>
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
        <el-button @click="getData(2)">{{$t("common.export")}}</el-button>
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
          <el-table-column :label="$t('sensitive.key')" align="center" prop="name"></el-table-column>
          <el-table-column :label="$t('sensitive.type')" align="center" prop="type"></el-table-column>
          <el-table-column :label="$t('sensitive.add')" align="center" prop="admin_name"></el-table-column>
          <el-table-column :label="$t('common.Creation_time')" align="center" prop="add_time"></el-table-column>
          <el-table-column
            :label="$t('common.operation')"
            align="center"
            width="130px"
            class-name="operation"
          >
            <template slot-scope="scope">
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
        <el-form-item :label="$t('sensitive.key')" :label-width="formLabelWidth">
          <el-input v-model="form.name" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label="$t('sensitive.type')" :label-width="formLabelWidth">
          <el-select placeholder v-model="form.type">
            <el-option :label="$t('sensitive.Politics')" value="1"></el-option>
            <el-option :label="$t('sensitive.Yellowish')" value="2"></el-option>
            <el-option :label="$t('sensitive.Abuse')" value="3"></el-option>
            <el-option :label="$t('sensitive.Advertisement')" value="4"></el-option>
            <el-option :label="$t('sensitive.else')" value="5"></el-option>
          </el-select>
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
        type: "Select"
      },
      name: "",
      type: "All",
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1
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
            .post("other/del_sensitive", {
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
      this.dialogFormVisible = true;
    },
    createPost() {
      this.$http
        .post("other/create_sensitive", {
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
        .post("other/sensitive", {
          page: this.page,
          page_size: this.limit,
          name: this.name,
          type: this.type,
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 2) {
            window.open(`${window.location.origin}${res.data}`);
          } else {
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
          }
        });
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



