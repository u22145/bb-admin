<template>
  <div class="marquee">
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="add = true">添加分类</el-button>
      </div>
      <el-table border :data="tableData" style="width: 100%">
        <el-table-column align="center" width="60" type="index" label="序号"></el-table-column>
        <el-table-column align="center" prop="target" label="发送对象">
          <template slot-scope="scope">
            <div>{{scope.row.target == 1 ? "所有對象" : "" }}</div>
            </template>  
        </el-table-column>
        <el-table-column header-align="center" width="500" prop="content" label="推播内容"></el-table-column>
        <el-table-column align="center" prop="triggerOn" label="条件值"></el-table-column>
        <el-table-column align="center" prop="Operation" label="操作">
          <template slot-scope="scope">
            <div>
              <el-button type="text" @click="showEditPopup(scope.row)">修改</el-button>
              <el-button type="text" @click="deleteForm(scope.row)">删除</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>
    <!-- 修改跑马灯 -->
    <el-dialog title="修改跑马灯：" :visible.sync="modify">
      <el-form :model="modifyForm" :rules="rules" ref="modifyForm" label-width="130px">
        <el-form-item label="发送对象：" prop="target">
          <el-select v-model="addForm.target" placeholder="请选择发送对象">
            <el-option
              v-for=" item in targetOption"
              :label="item.label"
              :value="item.value"
              :key="item.value"
            ></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="条件值：" prop="triggerOn">
          <el-input v-model="modifyForm.triggerOn"></el-input>
        </el-form-item>
        <el-form-item label="跑马灯内容：" prop="content">
          <el-input type="textarea" v-model="modifyForm.content"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="modify = false">取 消</el-button>
        <el-button type="primary" @click="verification('modifyForm',0)">提交</el-button>
      </div>
    </el-dialog>

    <!-- 添加分类名称 -->
    <el-dialog title="添加跑马灯：" :visible.sync="add">
      <el-form :model="addForm" :rules="rules" ref="addForm" label-width="130px">
        <el-form-item label="发送对象：" prop="target">
          <el-select v-model="addForm.target" placeholder="请选择发送对象">
            <el-option
              v-for=" item in targetOption"
              :label="item.label"
              :value="item.value"
              :key="item.value"
            ></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="条件值：" prop="triggerOn">
          <el-input v-model="addForm.triggerOn"></el-input>
        </el-form-item>
        <el-form-item label="跑马灯内容：" prop="content">
          <el-input type="textarea" v-model="addForm.content"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="add = false">取 消</el-button>
        <el-button type="primary" @click="verification('addForm',1)">提交</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import axios from "axios";
export default {
  data() {
    return {
      tableData: [
        {
          serialNumber: "1",
          categoryName: "本機資料王小虎",
          sort: "111",
        },
        {
          serialNumber: "122",
          categoryName: "本機資料王小虎",
          sort: "333",
        },
        {
          serialNumber: "1111",
          categoryName: "本機資料王小虎",
          sort: "222",
        },
        {
          serialNumber: "2",
          categoryName: "本機資料王小虎",
          sort: "555",
        },
      ],
      formLabelWidth: "120px",
      modify: false,
      modifyForm: {
        triggerOn: 0,
        target: 0,
        content: "假",
        id: 0,
      },
      add: false,
      addForm: {
        triggerOn: "",
        target: 1,
        content: "",
      },
      targetOption: [{ label: "所有用戶", value: 1 }],
      rules: {
        target: [
          { required: true, message: "请选择发送对象", trigger: "change" },
        ],
        triggerOn: [
          { required: true, message: "请输入条件值", trigger: "blur" },
        ],
        content: [
          { required: true, message: "请填写跑马灯内容", trigger: "blur" },
        ],
      },
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData() {
      this.$httpJava
        .get("ls/marquee", {})
        .then(
          function (response) {
            this.tableData = response.data.data;
          }.bind(this)
        )
        .catch(function (error) {
          console.log("请求失败的返回值");
        });
    },
    // 刪除資料
    delete(data) {
      this.$httpJava
        .delete("/ls/marquee/" + data.id, {})
        .then(
          function (response) {
            if (response.data.data == "OK") {
              this.$message({
                type: "success",
                message: "删除成功!",
              });
              this.getData()
            }
          }.bind(this)
        )
        .catch(
          function (error) {
            console.log(error);
          }.bind(this)
        );
    },
    // 驗證刪除
    deleteForm(data) {
      this.$confirm("此操作将永久删除跑马灯, 是否继续?", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning",
      })
        .then(
          function (response) {
            this.delete(data);
          }.bind(this)
        )
        .catch(() => {});
    },
    addMarquee() {
      let body = {
        target: parseInt(this.addForm.target),
        triggerOn: parseInt(this.addForm.triggerOn),
        content: this.addForm.content.toString(),
      };
      axios
        .post(process.env.VUE_APP_API_URL + "ls/marquee", body)
        .then((res) => {
          console.log("res");
          console.log(res);
          if (res.data.status == 1) {
            this.$message({
              message: "提交成功",
              type: "success",
            });
            this.add = false
            this.getData()
          }
        })
        .catch((err) => {
          console.log(err);
        });
    },
    // 修改
    showEditPopup(data) {
      this.modify = true;
      this.modifyForm.target = data.target;
      this.modifyForm.id = data.id;
      this.modifyForm.content = data.content;
      this.modifyForm.triggerOn = data.triggerOn;
    },
    modifyFunction() {
      axios
        .put(process.env.VUE_APP_API_URL+"ls/marquee", {
          id: parseInt(this.modifyForm.id),
          target: parseInt(this.modifyForm.target),
          content: this.modifyForm.content.toString(),
          triggerOn: parseInt(this.modifyForm.triggerOn)
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
      debugger
      this.$refs[formName].validate((valid) => {
        if (valid) {
          if (type === 1) {
            this.addMarquee();
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
.marquee {
  .fr {
    margin-bottom: 20px;
  }
  .el-pagination {
    text-align: center;
    margin-bottom: 0;
  }
}
</style>