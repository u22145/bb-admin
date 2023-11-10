<template>
  <div class="liveSort">
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="add = true">添加分类</el-button>
      </div>
      <el-table border :data="tableData" style="width: 100%">
        <el-table-column align="center" label="序号" type="index"></el-table-column>
        <el-table-column align="center" prop="catName" label="分类名称"></el-table-column>
        <el-table-column align="center" prop="seq" label="排序" sortable></el-table-column>
        <el-table-column align="center" prop="Operation" label="操作">
          <template slot-scope="scope">
            <div class="allBT">
              <el-button v-if="scope.row.defaultCat == 0" type="text" @click="showEditPopup(scope.row,1)">手动排序</el-button>
              <el-button v-if="scope.row.defaultCat == 0" type="text" @click="showEditPopup(scope.row,2)">修改</el-button>
              <el-button v-if="scope.row.defaultCat == 1" type="text" disabled>默认</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <el-row>
        <el-pagination
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
          :current-page="page"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
        ></el-pagination>
      </el-row>
    </div>
    <!-- add -->
    <el-dialog title="添加分类" :visible.sync="add">
      <el-form status-icon :model="addForm" ref="addForm" label-width="100px" class="demo-dynamic">
        <el-form-item
          prop="catName"
          label="分类名称："
          :rules="[{ required: true, message: '分类名称不能为空', trigger: 'blur' },]"
        >
          <el-input v-model="addForm.catName"></el-input>
        </el-form-item>
        <el-form-item
          label="分类排序："
          prop="seq"
          :rules="[
                { required: true, message: '分类排序不能为空'},
                { type: 'number', message: '分类排序必须为数字值'}
              ]"
        >
          <el-input v-model.number="addForm.seq"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="add = false">取 消</el-button>
        <el-button type="primary" @click="verification('addForm', 0)">提交</el-button>
      </div>
    </el-dialog>
    <!-- 手動排序 -->
    <el-dialog title="分类排序" :visible.sync="sort">
      <el-form status-icon :model="sortForm" ref="sortForm" :label-width="formLabelWidth">
        <el-form-item
          label="分类排序："
          prop="seq"
          :rules="[{ required: true, message: '分类排序不能为空'},
                   { type: 'number', message: '分类排序必须为数字值'}]"
        >
          <el-input type="seq" v-model.number="sortForm.seq" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="sort = false">取 消</el-button>
        <el-button type="primary" @click="verification('sortForm', 1)">提交</el-button>
      </div>
    </el-dialog>
    <!-- 修改分类名称 -->
    <el-dialog status-icon title="修改分类名称" :visible.sync="modify">
      <el-form :model="modifyForm" ref="modifyForm" :label-width="formLabelWidth">
        <el-form-item label="当前名称：" :label-width="formLabelWidth">
          <el-input v-model="modifyForm.catName" autocomplete="off" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item
          label="修改名称："
          prop="catNameNew"
          :rules="[{ required: true, message: '修改名称不能为空'},]"
        >
          <el-input v-model="modifyForm.catNameNew"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="modify = false">取 消</el-button>
        <el-button type="primary" @click="verification('modifyForm', 2)">提交</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import axios from "axios";
export default {
  data() {
    return {
      total: 1,
      pageNum: 1,
      pages: 1,
      size: 1,
      tableData: [
        {
          id: 1,
          catName: "畚箕假資料关注",
          seq: 1,
        },
        {
          id: 2,
          catName: "热门",
          seq: 2,
        },
        {
          id: 3,
          catName: "新人",
          seq: 3,
        },
        {
          id: 4,
          catName: "戶外",
          seq: 4,
        },
        {
          id: 5,
          catName: "新人",
          seq: 5,
        },
      ],
      formLabelWidth: "120px",
      add: false,
      addForm: {
        seq: 0,
        catName: "",
      },
      sort: false,
      sortForm: {
        seq: 0,
      },
      modify: false,
      modifyForm: {
        catName: "範例",
        catNameNew: "",
      },
      isShow: false,
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData() {
      this.$httpJava
        .get("/ls/cat", {})
        .then(
          function (response) {
            console.log("response");
            console.log(response.data.data.data);
            this.tableData = response.data.data.data;
            this.total = response.data.data.total;
            this.pageNum = response.data.data.pageNum;
            this.pages = response.data.data.pages;
            this.size = response.data.data.size;
     
          }.bind(this)
        )
        .catch(function (error) {
          console.log("请求失败的返回值");
        });
    },
    handleCurrentChange(val) {
      // console.log(e)
      this.page = val;
      this.getGuildContribution();
    },
    showEditPopup(data, type) {
      if (type === 1) {
        this.sort = true;
        this.sortForm.seq = data.seq;
        this.sortForm.id = data.id;
      } else {
        this.modify = true;
        this.modifyForm.catName = data.catName;
        this.modifyForm.id = data.id;
      }
    },
    // 添加
    addMarquee() {
      let body = {
        catName: this.addForm.catName,
        seq: parseInt(this.addForm.seq),
      };
      axios
        .post(process.env.VUE_APP_API_URL + "ls/cat", body)
        .then((res) => {
          console.log("res");
          console.log(res);
          if (res.data.status == 1) {
            this.$message({
              message: "提交成功",
              type: "success",
            });
            this.add = false;
            this.getData()
          }
        })
        .catch((err) => {
          console.log(err);
        });
    },
    // 手動排序
    sortFunction() {
      axios
        .put(process.env.VUE_APP_API_URL + "ls/cat/seq", {
          id: parseInt(this.sortForm.id),
          seq: parseInt(this.sortForm.seq),
        })
        .then(
          function (response) {
            console.log(response);
            if (response.data.data == "OK" && response.data.status == 1) {
              this.$message({
                message: "提交成功",
                type: "success",
              });
              this.sort = false;
              this.getData()
            } else {
              this.$message.error(response.data.msg);
            }
          }.bind(this)
        )
        .catch(function (error) {
          console.log(error);
        });
    },
    // 修改
    modifyFunction() {
      axios
        .put(process.env.VUE_APP_API_URL + "ls/cat/name", {
          id: parseInt(this.modifyForm.id),
          catName: this.modifyForm.catNameNew.toString(),
        })
        .then(
          function (response) {
            console.log(response);
            if (response.data.data == "OK" && response.data.status == 1) {
              this.$message({
                message: "提交成功",
                type: "success",
              });
              this.modify = false;
              this.modifyForm.catNameNew= " ";
              this.getData()
            } else {
              this.$message.error(response.data.msg);
            }
          }.bind(this)
        )
        .catch(function (error) {
          console.log(error);
        });
    },
    // 驗證
    verification(formName, type) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          if (type === 0) {
            this.addMarquee();
          } else if (type === 1) {
            this.sortFunction();
          } else {
            this.modifyFunction();
          }
        } else {
          console.log("error submit!!");
          return false;
        }
      });
    },
  },
};
</script>
<style lang="scss"  scoped>
.liveSort {
  .fr {
    margin-bottom: 20px;
  }
  .el-pagination {
    text-align: center;
    margin-bottom: 0;
  }
}
</style>