<template>
  <div class="partlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="partname">{{$t("jurisdiction.Role_name")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="username"
          id="partname"
          @keyup.enter.native="getData"
        ></el-input>
        <label>{{$t("common.Creation_time")}}</label>&nbsp;
        <el-date-picker
          v-model="seachtime"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="createdialog=true">{{$t("common.add")}}</el-button>
      </div>

      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="itemData"
          border
          style="width: 99%;margin-top: 30px"
          v-loading="loading"
        >
          <el-table-column :label='$t("jurisdiction.roleID")' align="center" prop="role_id"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Role_name")' align="center" prop="role_name"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Role_Description")' align="center" prop="desc"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
              <el-button type="text" size="small" @click="editUserInfo(scope.row)">{{$t("jurisdiction.Jurisdiction")}}</el-button>
              <el-button type="text" size="small" @click="open(scope.row)">{{$t("common.delete")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>

    <!-- 创建 -->
    <el-dialog  :visible.sync="createdialog">
      <el-form :model="form">
        <el-form-item :label='$t("jurisdiction.Role_name")'>
          <el-input v-model="form.name" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.Role_Description")'>
          <el-input type="textarea" v-model="form.desc" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="createdialog = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="addRole">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <!-- 权限分配 -->
    <el-dialog  :visible.sync="dialogFormVisible" width="35%">
      <el-form :model="form" v-loading="dloading">
        <div
          class="infinite-list-wrapper"
          style="overflow:auto;height:450px;border:1px #ccc solid;padding:20px;"
        >
          <el-tree
            :data="poweritem"
            show-checkbox
            node-key="id"
            :check-strictly="true"
            ref="tree"
            highlight-current
            :default-checked-keys="poweritemChecked"
          ></el-tree>
        </div>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="getChecked">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
    

    <Partdetails :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>


<script>
export default {
  components: {
    Partdetails: () => import("../../components/partdetails")
  },
  data() {
    return {
      dloading: false,
      loading: false,
      itemData: [],
      username: "",
      seachtime: [],
      poweritem: [],
      poweritemChecked: [],
      createdialog: false,
      dialogFormVisible: false,
      form: {
        name: "",
        desc: ""
      },
      page: 1,
      limit: 10,
      total: 1,
      status: false,
      id: ""
    };
  },
  created() {
    this.getData();
  },

  methods: {
    clear() {
      this.username = "";
      this.seachtime = [];
    },
    // 删除
    open(row) {
      this.$confirm(this.$i18n.t('confirm.comDel'), {
        confirmButtonText:this.$i18n.t('confirm.ok'),
        cancelButtonText: this.$i18n.t('confirm.cancel'),
        type: "warning"
      })
        .then(res => {
          this.$http
            .post("/system/delete_role", {
              role_id: row.role_id
            })
            .then(res => {
              if (res.status) {
                this.$message({
                  type: "success",
                  message: this.$i18n.t('confirm.delSucc'),
                });
                this.getData();
              } else {
                this.$message({
                  type: "error",
                  message: res.msg
                });
              }
            });
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: this.$i18n.t('confirm.comDel')
          });
        });
    },
    seeUserInfo(row) {
      window.localStorage.setItem("role_id", row.role_id);
      this.status = true;
    },
    editUserInfo(row) {
      this.dloading = true;
      this.role_id = row.role_id;
      this.$http
        .post("/system/show_power", {
          role_id: row.role_id
        })
        .then(res => {
          this.dloading = false;
          this.poweritem = res.data.power_list;
          this.poweritemChecked = res.data.role_power;
        });
      this.dialogFormVisible = true;
    },
    changeStatus(data) {
      this.status = data;
    },
    //选中页码
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    //获取数据
    getData() {
      this.loading = true;
      this.$http
        .post("/system/role_list", {
          role_name: this.username,
          starttime: this.seachtime[0],
          endtime: this.seachtime[1]
        })
        .then(res => {
          if (res.status == 1) {
            this.loading = false;
            this.itemData = res.data;
          } else {
            this.$message.error(res.msg);
          }
        });
    },
    getChecked() {
      let tree = this.$refs.tree.getCheckedKeys();
      this.$http
        .post("/system/update_role_power", {
          commit_role_power: this.$refs.tree.getCheckedKeys(),
          role_id: this.role_id
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.dialogFormVisible = false;
        });
    },
    addRole() {
      this.$http
        .post("/system/add_role", {
          role_name: this.form.name,
          desc: this.form.desc
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.createdialog = false;
          this.getData();
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.partlist {
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



