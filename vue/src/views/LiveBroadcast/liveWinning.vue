<template>
  <div class="liveWinning">
    <div class="whiteBg">
      <el-table border :data="tableData" style="width: 100%">
        <el-table-column align="center" width="50" type="index" label="序号"></el-table-column>
        <el-table-column align="center" prop="desc" label="说明 "></el-table-column>
        <el-table-column header-align="center" width="500" prop="content" label="中奖信息"></el-table-column>
        <el-table-column align="center" prop="condition" label="条件">
          <template slot-scope="scope">
            <div>{{scope.row.condition == 1 ? "中大奖" : "一般中奖" }}</div>
          </template>  
        </el-table-column>
        <el-table-column align="center" prop="value" label="条件值"></el-table-column>
        <el-table-column align="center" prop="icon" label="图示"></el-table-column>
        <el-table-column align="center" prop="Operation" label="操作">
          <template slot-scope="scope">
            <el-button type="text" @click="showEditPopup(scope.row)">修改</el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-row>
        <el-pagination
          :current-page="pages"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
        ></el-pagination>
      </el-row>
    </div>
    <!-- 修改中奖信息设置 -->
    <el-dialog title="中奖信息设置：" :visible.sync="modify">
      <el-form status-icon :model="modifyForm" ref="modifyForm" label-width="100px">
        <el-form-item
          label="说明："
          :label-width="formLabelWidth">
          <el-input v-model="modifyForm.desc" autocomplete="off" :disabled="true"></el-input>
        </el-form-item>
        <el-form-item label="条件：" :label-width="formLabelWidth">
          <el-select v-model="modifyForm.condition" placeholder="请选择发送对象">
            <el-option
              v-for=" item in conditionOption"
              :label="item.label"
              :value="item.value"
              :key="item.value"
            ></el-option>
          </el-select>
        </el-form-item>
        <el-form-item
          label="条件值："
          :label-width="formLabelWidth"
          prop="value"
          :rules="[
                { required: true, message: '条件值：不能为空', trigger: 'blur' },
              ]"
        >
          <el-input v-model="modifyForm.value" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item
          label="图示："
          :label-width="formLabelWidth"
          prop="icon"
          :rules="[
                { required: true, message: '图示不能为空', trigger: 'blur' },
              ]"
        >
          <el-input v-model="modifyForm.icon" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item
          label="中奖信息："
          :label-width="formLabelWidth"
          prop="content"
          :rules="[
                { required: true, message: '中奖信息不能为空', trigger: 'blur' },
              ]"
        >
          <el-input type="textarea" v-model="modifyForm.content"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="modify = false">取 消</el-button>
        <el-button type="primary" @click="verification('modifyForm')">确 定</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import axios from "axios";
export default {
  data() {
    return {
      id: 1,
      total: 0,
      pageNum: 0,
      pages: 0,
      pageSize: 0,
      condition:"",
      tableData: [
        // {
        //   id: 1,
        //   desc: "畚箕假資料中大奖",
        //   content: "恭喜%room_name%的%nickname%在%game_name%中奖了%amount%元",
        //   condition: 0,
        //   value: 200,
        //   icon: null,
        // },
        // {
        //   id: 2,
        //   desc: "一般中奖",
        //   content: "%room_name%的%nickname%在%game_name%中奖了%amount%元",
        //   condition: 0,
        //   value: 2,
        //   icon: "https:xxxxx",
        // },
      ],
      modify: false,
      modifyForm: {
        desc: "",
        condition: 1,
        value: 0,
        icon: "",
        content: "",
      },
      conditionOption: [
        {
          value: 1,
          label: "中大奖",
        },
        {
          value: 0,
          label: "一般中奖",
        },
      ],
      formLabelWidth: "120px",
      modifytext:"",
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData() {
      this.$httpJava
        .get("ls/lottoSetting", {})
        .then(
          function (response) {
            this.tableData = response.data.data.data;
            this.total = response.data.data.total;
            this.pageNum = response.data.data.pageNum;
            this.pages = response.data.data.pages;
            this.pageSize = response.data.data.pageSize;
          }.bind(this)
        )
        .catch(function (error) {
          console.log("请求失败的返回值");
        });
    },
    showEditPopup(data, type) {
      this.modify = true;
      this.modifyForm.id = data.id;
      this.modifyForm.desc = data.desc;
      this.modifyForm.content = data.content;
      this.modifyForm.condition = data.condition;
      this.modifyForm.value = data.value;
      this.modifyForm.icon = data.icon;
    
    },
    // 修改
    modifyFunction() {
      axios
        .put(process.env.VUE_APP_API_URL + "ls/lottoSetting", {
          id: parseInt(this.modifyForm.id),
          desc: this.modifyForm.desc.toString(),
          content: this.modifyForm.content.toString(),
          condition: parseInt(this.modifyForm.condition),
          value: parseInt(this.modifyForm.value),
          icon: this.modifyForm.icon.toString(),
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
            }
          }.bind(this)
        )
        .catch(function (error) {
          console.log(error);
        });
    },
    // 驗證
    verification(formName, id) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          this.modifyFunction();
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
.el-pagination {
  text-align: center;
  margin-bottom: 0;
}
</style>