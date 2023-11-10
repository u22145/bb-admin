<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">組員ID</label>&nbsp;
        <el-input style="width: 300px" v-model="id"></el-input>
        <label style="margin-left: 20px">組員昵稱</label>&nbsp;
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
        <el-button @click="createAppkey">添加組員</el-button>
      </div>
      <div class="tableCon">
        <el-table
                ref="multipleTable"
                :data="tableData"
                v-loading="loading"
                border
                style="width: 100%;margin-top: 30px"
        >
          <el-table-column label="所屬小組ID" align="center" prop="tid"></el-table-column>
          <el-table-column label="所屬小組名稱" align="center" prop="team"></el-table-column>
          <el-table-column label="組員ID" align="center" prop="uid"></el-table-column>
          <el-table-column label="組員昵稱" align="center" prop="nickname"></el-table-column>
          <el-table-column label="加入時間" align="center" prop="uptime"></el-table-column>
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
                      @click="deleteSen(scope.row)"
              >移除組員</el-button>
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
        <el-form-item :label="'小組ID'" :label-width="formLabelWidth">
          <el-input v-model="form.tid" autocomplete="off" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="組員ID" :label-width="formLabelWidth">
          <el-input v-model="form.uid" autocomplete="off"></el-input>
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
                    url:'',
                    transactionStatus: "2",
                    id:'',
                    tid: '',
                },
                name: "",
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
                    type: "sales",
                    usercode: window.localStorage.getItem("usercode")
                };
            }
        },
        methods: {
            deleteSen(row) {
                this.$confirm("刪除操作不可以恢復，是否繼續？", "提示", {
                    confirmButtonText: "確定",
                    cancelButtonText: "取消",
                    type: "warning"
                })
                    .then(() => {
                        this.$http
                            .post("sales/sm_del", {
                                id: row.tid,
                                uid: row.uid
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
                this.form.tid = this.$route.query.id;
                this.dialogFormVisible = true;
            },
            createPost() {
                this.$http
                    .post("sales/sm_add", {
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
                    .post("sales/salesman_list", {
                        page: this.page,
                        page_size: this.limit,
                        name: this.name,
                        id: this.id,
                        tid: this.$route.query.id,
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
                            this.tid = res.data.tid;
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



                            