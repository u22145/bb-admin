<template>
  <div class="hotGame">
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="add = true">添加热门游戏直播</el-button>
      </div>
      <el-table border :data="tableData" style="width: 100%">
        <el-table-column align="center" width="50" label="序号" type="index"></el-table-column>
        <el-table-column align="center" prop="catName" label="主播ID"></el-table-column>
        <el-table-column align="center" prop="roomId" label="房间ID"></el-table-column>
        <el-table-column align="center" prop="nickname" label="主播昵称"></el-table-column>
        <el-table-column align="center" width="200" prop="title" label="直播标题"></el-table-column>
        <el-table-column align="center" prop="guild" label="所属公会"></el-table-column>
        <el-table-column align="center" width="100" prop="startedAt" label="开播时间">
        <template slot-scope="scope">
          <div>{{moment(scope.row.startedAt).format('yyyy-MM-DD')}}</div>
            
        </template>
        </el-table-column>
        <el-table-column align="center" width="80" prop="duration" label="开播时长"></el-table-column>
        <el-table-column align="center" prop="viewers" label="房间人气"></el-table-column>
        <el-table-column align="center" prop="turnover" label="房间收益"></el-table-column>
        <el-table-column align="center" prop="popularity" label="房间热度"></el-table-column>
        <el-table-column align="center" prop="sGameLS" label="游戏直播"></el-table-column>
        <el-table-column align="center" width="100" prop="seq" label="热门排序" sortable></el-table-column>
        <el-table-column align="center" width="130" prop="Operation" label="操作">
          <template slot-scope="scope">
            <el-button type="text" @click="showEditPopup(scope.row,1)">手动排序</el-button>
            <el-button type="text" @click="deleteForm(scope.row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <!-- <el-row>
        <el-pagination
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
          :current-page="page"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
        ></el-pagination>
      </el-row> -->
    </div>
    <!-- 手动排序 -->
    <el-dialog title="热门排序" :visible.sync="sort">
      <el-form status-icon :model="sortForm" ref="sortForm" :label-width="formLabelWidth">
        <el-form-item
          label="热门排序:"
          prop="seq"
          :rules="[
                { required: true, message: '热门排序不能为空'},
                { type: 'number', message: '热门排序必须为数字值'}
              ]"
        >
          <el-input v-model.number="sortForm.seq" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="sort = false">取 消</el-button>
        <el-button type="primary" @click="verification('sortForm', 0)">提交</el-button>
      </div>
    </el-dialog>
    <!-- add -->
    <el-dialog title="添加热门游戏直播" :visible.sync="add">
      <el-form status-icon :model="addForm" ref="addForm" label-width="100px">
        <el-form-item
          prop="name"
          label="主播ID:">
          <el-input v-model.number="addForm.name"></el-input>
        </el-form-item>
        <el-form-item
          class="hasBT"
          label="房间ID:"
          prop="roomId"
          :rules="[
                { required: true, message: '房间ID不能为空'},
                { type: 'number', message: '房间ID必须为数字值'}
              ]"
        >
          
          <template slot-scope="scope">
            <el-input v-model.number="addForm.roomId"></el-input> 
            <el-button class="searchID" type="primary" @click="searchID(scope.row)">查詢</el-button>
          </template>
        </el-form-item>
        <el-row class="Collapse" :class="{'active':isShow}">
          <el-col :span="12">
            <div class="grid-content">主 播 ID ：{{catName}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">房 间 ID ：{{roomId}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">主播昵称：{{nickname}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">所属公会：{{guild}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">房间人气：{{viewers}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">房间收益：{{turnover}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">房间热度：{{popularity}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">游戏直播：{{isGameLS}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">直播状态：{{seq}}</div>
          </el-col>
          <el-col :span="12">
            <div class="grid-content">开播时长：{{duration}}</div>
          </el-col>
          <el-col :span="24">
            <div class="grid-content">开播时间：{{startedAt}}</div>
          </el-col>
          <el-col :span="24">
            <div class="grid-content">直播标题：{{title}}</div>
          </el-col>
        </el-row>
        <el-divider></el-divider>
        <el-form-item
          label="热门排序:"
          prop="seq"
          :rules="[
                { required: true, message: '热门排序不能为空'},
                { type: 'number', message: '热门排序必须为数字值'}
              ]"
        >
          <el-input v-model.number="addForm.seq"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="add = false">取 消</el-button>
        <el-button type="primary" @click="verification('addForm', 1)">添加</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import moment from 'moment'
import axios from "axios";
export default {
  data() {
    return {
      // total: 1,
      // pageNum: 1,
      // pages: 1,
      // size: 1,
      catName: "",
      roomId:0,
      nickname:"",
      title:"",
      guild:"",
      seq: 0,
      startedAt:"",
      duration:"",
      viewers:0,
      turnover:0,
      popularity:0,
      isGameLS:true,
      isShow: false,
      modify: false,
      formLabelWidth: "120px",
      sort: false,
      sortForm: {
        seq: 0,
      },
      add: false,
      addForm: {
        roomId: "",
        name: "",
        seq: 0,
      },
      tableData: [
        {
          catName: "新人",
          roomId:1005,
          nickname:"王曉燕",
          title:"等你来撩",
          guild:"帝王",
          startedAt:"2020-09-09 12:36:46",
          duration:"02:56:58",
          viewers:1234,
          turnover:1007898,
          popularity:256854,
          isGameLS:true,
          seq:2
        },
        {
          catName: "新人",
          roomId:1005,
          nickname:"王曉燕",
          title:"等你来撩",
          guild:"帝王",
          startedAt:"2020-09-09 12:36:46",
          duration:"02:56:58",
          viewers:1234,
          turnover:1007898,
          popularity:256854,
          isGameLS:true,
          seq:1
        },
      ],
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData() {
      this.$httpJava
        .get("/ls/popular", {
        })
        .then(
          function (response) {
            this.tableData = response.data.data;
            this.total = response.data.data.total;
            this.pageNum = response.data.data.pageNum;
            this.pages = response.data.data.pages;
            this.size = response.data.data.size;
            // this.startedAt = moment(response.data.data.startedAt).format('yyyy-MM-DD');
          }.bind(this)
        )
        .catch(function (error) {
          console.log("请求失败的返回值");
        });
    },
    // handleCurrentChange(val) {
    //   // console.log(e)
    //   this.page = val;
    //   this.getGuildContribution();
    // },
    showEditPopup(data, type) {
      if (type === 1) {
        this.sort = true;
        this.sortForm.seq = data.seq;
        this.sortForm.roomId = data.roomId;
        
      } else {
        this.modify = true;
        this.modifyForm.catName = data.catName;
        this.modifyForm.id = data.id;
      }
    },
   // 搜尋
    searchID() {
      this.isShow = !this.isShow;
      this.$httpJava
        .get("/ls/popular/" + this.addForm.roomId, {})
        .then(
          function (response) {
            console.log(response);
            this.guildData = response.data.data.data
            this.catName=response.data.data.catName
            this.roomId=response.data.data.roomId
            this.nickname=response.data.data.nickname
            this.title=response.data.data.title
            this.guild=response.data.data.guild
            this.startedAt=response.data.data.startedAt
            this.duration=response.data.data.duration
            this.viewers=response.data.data.viewers
            this.turnover=response.data.data.turnover
            this.popularity=response.data.data.popularity
            this.isGameLS=response.data.data.isGameLS
            this.seq=response.data.data.seq
          }.bind(this)
        )
        .catch(
          function (error) {
           
            console.log(error);
          }.bind(this)
        );
    },
    // 添加
    addMarquee() {
      let body = {
        roomId: parseInt(this.addForm.roomId),
        seq: parseInt(this.addForm.seq),
      };
      axios
        .post(process.env.VUE_APP_API_URL + "ls/popular", body)
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
        .put(process.env.VUE_APP_API_URL + "ls/popular", {
          roomId: parseInt(this.sortForm.roomId),
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
    // 刪除資料
    delete(data) {
      this.$httpJava
        .delete("/ls/popular/" + data.roomId, {})
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
    // 驗證
    verification(formName, type) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          if (type === 1) {
            this.addMarquee();
            
          } else {
            this.sortFunction()
            // this.$message({
            //   message: "提交成功",
            //   type: "success",
            // });
            // this.sort = false;
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
.hotGame {
  .fr,
  .Collapse {
    margin-bottom: 20px;
  }
  .el-input {
    width: 84%;
    & + .el-button {
      margin-left: 10px;
    }
  }
  .Collapse {
    line-height: 2;
    font-size: 20px;
    padding-left: 105px;
    height: 0;
    overflow-y: hidden;
    transition: height 0.7s;
    -webkit-transition: height 0.7s;
    -moz-transition: height 0.7s;
    -o-transition: height 0.7s;
  }
  .Collapse.active {
    height: 176px;
    overflow-y: hidden;
    transition: height 2s;
    -webkit-transition: height 2s;
    -moz-transition: height 2s;
    -o-transition: height 2s;
  }
  .el-pagination {
    text-align: center;
    margin-bottom: 0;
  }
}
</style>