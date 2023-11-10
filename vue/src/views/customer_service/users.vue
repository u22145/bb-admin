<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%"
        >
          <el-table-column label="序号" align="center" prop="id"></el-table-column>
          <el-table-column label="用户ID" align="center" prop="op_uid"></el-table-column>
          <el-table-column label="用户昵称" align="center" prop="nickname"></el-table-column>
          <el-table-column label="对接客服" align="center" prop="customer_service_name"></el-table-column>
          <el-table-column label="操作" align="center" width="130px" class-name="operation">
            <template slot-scope="scope">
              <el-button type="text" size="small" @click="showChatRecord(scope.row)">对话记录</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="userPage"
          :page-sizes="[this.$store.state.adminPageSize]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="userTotal"
          class="fr"
        ></el-pagination>
      </div>
    </div>

    <!-- 聊天记录 -->
    <el-dialog title="聊天记录" :visible.sync="dialogTableVisible">
      <el-table
        :data="chatList"
        border
        style="width: 90%;margin:0 auto;"
        :empty-text="this.chatRecordLoading ? '加载中.....' : '暂无数据111'"
      >
        <el-table-column property="serialNumber" label="序号" align="center"></el-table-column>
        <el-table-column property="from.nickname" label="发送方" align="center"></el-table-column>
        <el-table-column property="to.nickname" label="接收方" align="center"></el-table-column>
        <el-table-column
          property="msg_content"
          :formatter="showRealyContent"
          label="发送内容"
          align="center"
          min-width="130px"
        ></el-table-column>
        <el-table-column
          property="send_time"
          :formatter="formatDate"
          min-width="108px"
          label="发送时间"
          align="center"
        ></el-table-column>
        <el-table-column property="type" :formatter="formatType" label="消息类型" align="center"></el-table-column>
      </el-table>
      <el-pagination
        @current-change="chatRecord"
        :total="total"
        layout="total, next"
        next-text="点击加载更多>>>"
      ></el-pagination>
    </el-dialog>
  </div>
</template>
<script>
export default {
  data() {
    return {
      chatRecordLoading: false,
      loading: false,
      total: 0,
      serialNumber: 0,
      tableData: [],
      chatList: [],
      types: {
        1: "打招呼",
        2: "文本",
        3: "图片",
        5: "视频"
      },
      userPage: 1,
      userPageSize: 10,
      userTotal: 0,
      limit:this.$store.state.adminPageSize,
      dialogTableVisible: false,
      // 分页请求消息详情的参数
      chatParams: {
        type: 0, //类型：0读新私信，1取历史记录
        pm_id: 0, //用以判断的私信id
        op_uid: 0, //对方用户id
        app_usercode: ""
      },
      form: {
        comm: ""
      },
      formLabelWidth: "120px"
    };
  },
  created() {
    this.getData();
  },
  methods: {
    // 根据类型显示相应的消息内容
    showRealyContent(row) {
      let data = row.msg_content;
      switch (row.type) {
        case 1:
        case 2:
          return data.text;
          break;
        case 3:
        case 5:
          return data.url;
          break;
      }
      return "未设定";
    },
    // 格式化消息类型
    formatType(row, column) {
      var type = row[column.property];
      return this.types[type] || type;
    },
    // 格式化时间
    formatDate(row, column) {
      var date = row[column.property];
      if (date == undefined) {
        return "";
      }
      let strConcat = str => {
        return str.length == 1 ? "0" + str : str;
      };
      let dateTime = new Date(date * 1000);

      let m = strConcat((dateTime.getMonth() + 1).toString());
      let d = strConcat(dateTime.getDate().toString());
      let h = strConcat(dateTime.getHours().toString());
      let i = strConcat(dateTime.getMinutes().toString());
      let s = strConcat(dateTime.getSeconds().toString());

      return (
        dateTime.getFullYear() + "-" + m + "-" + d + " " + h + ":" + i + ":" + s
      );
    },
    // 对话记录
    chatRecord() {
      var self = this;
      self.chatRecordLoading = true;
      if (self.chatParams.pm_id > 0) self.chatParams.type = 1;
      this.$http.post("cs/pm_detail", this.chatParams).then(res => {
        self.chatRecordLoading = false;
        self.total = res.total;
        let msgList = res.data || [];
        if (msgList.length == 0) {
          self.$message.success("没有更多数据");
          return false;
        }

        if (self.chatParams.pm_id === 0) {
          msgList.reverse();
          self.chatParams.pm_id = msgList.slice(-1)[0].pm_id;
        } else {
          self.chatParams.pm_id = msgList[0].pm_id;
        }
        msgList.forEach((element, k) => {
          self.serialNumber += 1;
          element.serialNumber = self.serialNumber;
          self.chatList.push(element);
        });
      });
    },
    // 显示聊天记录
    showChatRecord(row) {
      this.dialogTableVisible = true;
      this.chatList = [];
      this.serialNumber = 0;
      this.total = 0;

      this.chatParams.type = 0;
      this.chatParams.pm_id = 0;
      this.chatParams.app_usercode = row.app_usercode;
      this.chatParams.op_uid = row.op_uid;

      this.chatRecord();
    },
    handleCurrentChange(val) {
      this.userPage = val;
      this.getData();
    },
    // 获取对接过的用户列表
    getData() {
      this.loading = true;
      this.$http.post("cs/users", {page: this.userPage, page_size: this.userPageSize}).then(res => {
        this.loading = false;
        let list = [];
        this.userTotal = res.data.total || 0;
        (res.data.list || []).forEach(item => {
          if (item.op_uid == -1) return true; // return true 等价于 continue
          list.push(item);
        });
        this.tableData = list;
      });
    }
  }
};
</script>

<style lang="scss" scoped>
.vipcollocation {
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



