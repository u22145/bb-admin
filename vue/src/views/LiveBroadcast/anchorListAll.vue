<template>
  <div class="anchorlist">
    <div class="whiteBg">
      <div class="screen">
        <label for="selectUserID">{{$t("common.Username")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="selectUserID"
          v-model="selectData.id"
        ></el-input>
        <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
        <el-input
          style="width: 200px"
          id="nickname"
          v-model="selectData.nickname"
        ></el-input>
        <label for="operator">操作人</label>&nbsp;
        <el-input
          style="width: 200px"
          id="operator"
          v-model="selectData.operator"
        ></el-input>
        <label for="category">申请分类</label>
        <el-select v-model="selectData.category" placeholder="请选择">
            <el-option
            v-for="item in selectData.categoryOptions"
            :key="item.value"
            :label="item.catName"
            :value="item.id">
            </el-option>
        </el-select>
        <br>
        <label for="category">状态</label>
        <el-select style="width:100px;" v-model="selectData.status" placeholder="请选择">
            <el-option
            v-for="item in selectData.statusOptions"
            :key="item.value"
            :label="item.label"
            :value="item.value">
            </el-option>
        </el-select>
        <label>操作时间</label>&nbsp;
        <el-date-picker
          v-model="selectData.times"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <label>申请时间</label>&nbsp;
        <el-date-picker
          v-model="selectData.registerTime"
          type="datetimerange"
          :range-separator='$t("common.to")'
          :start-placeholder='$t("common.start_time")'
          :end-placeholder='$t("common.end_time")'
          value-format="yyyy-MM-dd HH:mm:ss"
        ></el-date-picker>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getDate()">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 99%;margin-top: 30px">
          <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
          <el-table-column :label='$t("common.nickname")' align="center" prop="nickname"></el-table-column>
          <el-table-column label='申请分类' align="center" prop="catName"></el-table-column>
          <el-table-column label='申请时间' align="center" prop="createdAt"></el-table-column>
          <el-table-column label='状态' align="center" style="width:120px;">
              <template slot-scope="scope">
                  <div>
                      {{scope.row.status == 1 ? "禁播" : "正常"}}
                  </div>
              </template>
          </el-table-column>
          <el-table-column label='操作人' align="center" prop="operator"></el-table-column>
          <el-table-column label='操作备注' align="center" prop="comm"></el-table-column>
          <el-table-column label='操作时间' align="center" prop="operatedAt"></el-table-column>
           <el-table-column :label='$t("common.operation")' align="center" width="130px" >
            <template slot-scope="scope">
              <el-button
                type="text"
                size="small"
                @click="operateAnchor(scope.row)"
              >{{ scope.row.status == 0 ? "禁播" : "解禁" }}</el-button>
            </template>
          </el-table-column>
        </el-table>
    </div>
  </div>
</template>
<script>
import axios from "axios";
export default {

  data() {
    return {
      loading: false,
      tableData: null,
      selectData: {
        id: "",
        nickname: "",
        operator: "",
        categoryOptions: [
            // {label: '新人', value: 0},
            // {label: '顏值', value: 1}
        ]
        ,
        category: null,
        status: null,
        statusOptions: [
            {label: '禁播', value: 0},
            {label: '正常', value: 1}
        ],
        times: [],
        registerTime: []
      },
      tableData: [],
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      export: 0,
      status: false,
      id: "",
      row: null,
      fakeData:[
        {
            "id": 286,
            "uid": 1003,
            "nickname": "king",
            "catName": "大秀",
            "createdAt": "2020-03-31T03:43:59.000+00:00",
            "status": 2,
            "operator": null,
            "comm": "不通过",
            "operatedAt": "2000-01-01T14:00:00.000+00:00"
        },
                {
            "id": 286,
            "uid": 1003,
            "nickname": "king",
            "catName": "大秀",
            "createdAt": "2020-03-31T03:43:59.000+00:00",
            "status": 1,
            "operator": null,
            "comm": "不通过",
            "operatedAt": "2000-01-01T14:00:00.000+00:00"
        }
        ]
    };
  },
  created() {
      this.getDate()
      this.getCategoryList()
  },
  methods: {
      getDate() {
        this.$httpJava
        .get("/anchor/live",{params: {
                uid: this.selectData.id,
                nickname: this.selectData.nickname,
                operator: this.selectData.operator,
                catId: this.selectData.category,
                status: this.selectData.status,
                opFrom: this.selectData.times[0],
                opTo: this.selectData.times[1],
                createdFrom: this.selectData.registerTime[0],
                createdTo: this.selectData.registerTime[1],
                page: this.page
            }
        })
        .then(res => {
            // this.tableData = this.fakeData
          this.loading = false;
          this.tableData = res.data.data.data
        })
        .catch(error => {
        })
      },
      getCategoryList() {
        this.$httpJava
        .get("/ls/cat",{
        })
        .then(res => {
            // this.tableData = this.fakeData
            this.selectData.categoryOptions = res.data.data.data
        })
        .catch(error => {
        })
      },
      operateAnchor(data) {
        let oprateStatus;
        if(data.status == 1) {
            oprateStatus = 0
        }
        if(data.status == 0) {
            oprateStatus = 1
        }
        let self = this;
        axios
        .put(process.env.VUE_APP_API_URL +"anchor/live", {
          uid: data.uid,
          status: oprateStatus,
          adminId: window.localStorage.anchorlist_uid
        })
        .then(res => {
            // this.tableData = this.fakeData
            self.getDate()
        })
        .catch(error => {
        })
      }
  }
};
</script>


<style lang="scss" scoped>
.anchorlist {
  .fr {
    margin-bottom: 20px;
  }
  .screen {
    margin-bottom: 15px;

    label {
      display: inline-block;
      color: #767474;
      font-size: 14px;
      margin: 5px 20px;
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
  .el-select {
      margin-top: 20px;
  }
}
</style>


