<template>
  <div class="operatorlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="operator">{{$t("jurisdiction.AdministratorsID")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          v-model="uid"
          id="operator"
          @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
        <el-select placeholder v-model="ustatus" @keyup.enter.native="getData">
          <el-option :label='$t("common.all")' value="all"></el-option>
          <el-option :label='$t("jurisdiction.Enable")' value="pass"></el-option>
          <el-option :label='$t("jurisdiction.Discontinue_use")' value="ban"></el-option>
        </el-select>
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
        <el-button @click="create">{{$t("common.add")}}</el-button>
      </div>
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="itemData"
          border
          style="width: 99%;margin-top: 30px"
          v-loading="loading"
        >
          <el-table-column :label='$t("jurisdiction.AdministratorsID")' align="center" prop="id"></el-table-column>
          <el-table-column :label='$t("jurisdiction.administrator_account")' align="center" prop="username"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Area_code")' align="center" prop="area_code"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Cell_phone_number")' align="center" prop="mobile"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Administrator_role")' align="center" prop="role_name"></el-table-column>
          <el-table-column :label='$t("jurisdiction.Administrator_Description")' align="center" prop="description"></el-table-column>
          <el-table-column :label='$t("common.status")' align="center" prop="status_msg"></el-table-column>
          <el-table-column :label='$t("common.Creation_time")' align="center" prop="uptime" min-width="120px"></el-table-column>
          <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope" v-if="scope.row['is_super_admin']!=1">
              <el-button type="text" size="small" @click="seeUserInfo(scope.row)">{{$t("common.See")}}</el-button>
              <el-button type="text" size="small" @click="editUserInfo(scope.row)">{{$t("jurisdiction.role")}}</el-button>
              <el-button
                type="text"
                v-if="scope.row['status']==0"
                size="small"
                @click="stop(scope.row,1)"
              >{{$t("jurisdiction.Enable")}}</el-button>
              <el-button
                type="text"
                v-if="scope.row['status']==1"
                size="small"
                @click="stop(scope.row,0)"
              >{{$t("jurisdiction.Discontinue_use")}}</el-button>
              <el-button type="text" size="small" @click="open(scope.row)">{{$t("common.delete")}}</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[10]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
        ></el-pagination>
      </div>
    </div>

    <!-- 创建管理员 -->
    <el-dialog :title='$t("common.add")' :visible.sync="createdialog">
      <el-form :model="form">
<!--        <el-form-item :label='$t("jurisdiction.labelID")' :label-width="formLabelWidth">-->
<!--          <el-input v-model="form.uid" autocomplete="off"></el-input>-->
<!--        </el-form-item>-->
        <el-form-item :label='$t("jurisdiction.administrator_account")' :label-width="formLabelWidth">
          <el-input v-model="form.username" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.Area_code")' :label-width="formLabelWidth">
          <el-input v-model="form.area_code" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.Cell_phone_number")' :label-width="formLabelWidth">
          <el-input v-model="form.mobile" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.passWord")' :label-width="formLabelWidth">
          <el-input v-model="form.password" autocomplete="off" show-password></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.comPass")' :label-width="formLabelWidth">
          <el-input v-model="form.commitpass" autocomplete="off" show-password></el-input>
        </el-form-item>
        <el-form-item :label='$t("jurisdiction.Administrator_Description")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.description" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item label='渠道' :label-width="formLabelWidth">
          <el-select placeholder v-model="form.channel" @keyup.enter.native="getData">
            <el-option v-for="item in advertList" :value="item.id" :label="item.id +' - '+ item.name" :key="item.id"></el-option>
            <!-- <el-option :label='$t("common.all")' value="all"></el-option>
            <el-option :label='$t("jurisdiction.Enable")' value="pass"></el-option>
            <el-option :label='$t("jurisdiction.Discontinue_use")' value="ban"></el-option> -->
          </el-select>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="createrole">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <!-- 权限 -->
    <el-dialog :visible.sync="dialogFormVisible" width="40%">
      <el-transfer
        style="margin-left:80px"
        v-model="value"
        :data="data"
        :props="{key: 'role_id',label: 'role_name'}"
        :titles='[$t("jurisdiction.All_permissions"), $t("jurisdiction.Existing_authority")]'
        :button-texts='[$t("jurisdiction.Take_back"), $t("jurisdiction.increase")]'
      ></el-transfer>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="roleAssignment">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
    <Operatordetails :status="status" :id="id" v-on:changeStatus="changeStatus" />
  </div>
</template>

<script>
import { reg } from "../../utils/reg";
export default {
  components: {
    Operatordetails: () => import("../../components/operatordetails")
  },
  data() {
    return {
      loading: false,
      dloading: false,
      regMethods: reg,
      data: [],
      value: [],
      defaultValue: [],
      itemData: [],
      createdialog: false,
      dialogVisible: false,
      dialogFormVisible: false,
      form: {
        uid: "",
        username: "",
        area_code: "",
        mobile: "",
        password: "",
        commitpass: "",
        description: "",
        channel: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: 10,
      total: 1,
      ustatus: "",
      uid: "",
      seachtime: [],
      status: false,
      id: "",
      advertList: null
    };
  },
  created() {
    this.getData();
    this.getAdvert()
  },

  methods: {
    clear() {
      this.uid = "";
      this.ustatus = "";
      this.seachtime = [];
    },
    create() {
      this.form.uid = "";
      this.form.username = "";
      this.form.area_code = "";
      this.form.mobile = "";
      this.form.password = "";
      this.form.commitpass = "";
      this.form.description = "";
      this.createdialog = true;
    },
    // 删除
    open(row) {
      this.$confirm(this.$i18n.t('confirm.comDel'), {
        confirmButtonText: this.$i18n.t('confirm.ok'),
        cancelButtonText: this.$i18n.t('confirm.cancel'),
        type: "warning"
      })
        .then(() => {
          this.delrole(row.id);
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: this.$i18n.t('confirm.comDel')
          });
        });
    },
    getAdvert() {
      this.$http
        .post("advert/advert_list", {

        })
        .then(res => {
            this.advertList = res.data.list;
            console.log(this.advertList)
        });
    },
    seeUserInfo(row) {
      this.id = row.id;
      this.status = true;
    },
    changeStatus(data) {
      this.status = data;
    },
    editUserInfo(row) {
      this.dloading = true;
      this.id = row.id;
      this.$http
        .post("/system/role_assignment", {
          admin_id: row.id
        })
        .then(res => {
          this.dloading = false;
          this.data = res.data.role_list;
          let have_role_list = res.data.have_role_list;
          this.value = have_role_list;
          this.defaultValue = have_role_list;
        });
      this.dialogFormVisible = true;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    //获取管理员列表
    getData() {
      this.loading = true;
      this.$http
        .post("/system/admin_user_list", {
          uid: this.uid,
          status: this.ustatus,
          start_time: this.seachtime[0],
          end_time: this.seachtime[1]
        })
        .then(res => {
          if (res.status == 1) {
            this.loading = false;
            this.itemData = res.data;
          }
        });
    },
    //创建管理员
    createrole() {
      if (
        this.regMethods.confirmPwd(this.form.password, this.form.commitpass) &&
        this.regMethods.checkPwd(this.form.password)
      ) {
        this.$http
          .post("/system/add_admin_user", {
            uid:  0,
            username: this.form.username,
            area_code: this.form.area_code,
            mobile: this.form.mobile,
            password: this.form.password,
            commitpass: this.form.commitpass,
            description: this.form.description,
            advert_id: this.form.channel
          })
          .then(res => {
            if (res.status == 1) {
              this.getData();
            } else {
              this.$message({
                type: "error",
                message: res.msg
              });
            }
            this.createdialog = false;
          });
      }
    },
    //删除管理员
    delrole(id) {
      this.$http
        .post("/system/delete_admin_user", {
          id: id
        })
        .then(res => {
          if (res.status) {
            this.$message({
              type: "success",
              message: this.$i18n.t('confirm.delSucc')
            });
            this.getData();
          } else {
            this.$message({
              type: "error",
              message: res.msg
            });
          }
        });
    },
    //停用启用
    stop(row, s) {
      let message = this.$i18n.t('confirm.Disable');
      this.$http
        .post("/system/stop_admin", {
          id: row.id,
          status: s
        })
        .then(res => {
          if (res.status) {
            if (s) {
              message = this.$i18n.t('confirm.Enabled');
            }
            this.$message({
              type: "success",
              message: message
            });
            this.getData();
          } else {
          }
        });
    },

    roleAssignment() {
      if (this.value == this.defaultValue) {
        this.dialogFormVisible = false;
        return false;
      }
      this.$http
        .post("/system/update_admin_user_role", {
          admin_id: this.id,
          commit_admin_role: this.value
        })
        .then(res => {
          this.$message({
            type: res.status == 1 ? "success" : "error",
            message: res.msg
          });
          this.dialogFormVisible = false;
          this.getData();
        });
    }
  }
};
</script>



<style lang="scss" scoped>
.operatorlist {
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



