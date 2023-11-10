<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">用戶ID</label>&nbsp;
        <el-input
                style="width: 120px"
                id="channel_id"
                v-model="selectData.uid"
                @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">用戶手機</label>&nbsp;
        <el-input
                style="width: 120px"
                id="channel_name"
                v-model="selectData.mobile"
                @keyup.enter.native="getData"
        ></el-input>
        <label style="margin-left: 20px">用戶狀態</label>&nbsp;
        <el-select placeholder v-model="selectData.type">
          <el-option label='全部' value="0"></el-option>
          <el-option label='已激活' value="1"></el-option>
          <el-option label='未激活' value="2"></el-option>
        </el-select>
        <label style="margin-left: 20px">用戶等級</label>&nbsp;
        <el-select placeholder v-model="selectData.vip">
          <el-option label='全部' value="0"></el-option>
          <el-option label='普通用戶' value="1"></el-option>
          <el-option label='VIP' value="2"></el-option>
          <el-option label='SVIP' value="3"></el-option>
        </el-select>
        <label style="margin-left: 20px">是否充值</label>&nbsp;
        <el-select placeholder v-model="selectData.deposit">
          <el-option label='全部' value="0"></el-option>
          <el-option label='已充值' value="1"></el-option>
          <el-option label='未充值' value="2"></el-option>
        </el-select>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">{{$t("common.search")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      <!--<div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <el-button @click="getData(2)">{{$t("common.export")}}</el-button>
      </div>-->

      <div class="tableCon">
        <el-table
                ref="multipleTable"
                :data="tableData"
                v-loading="loading"
                border
                style="width: 99%;margin-top: 30px"
        >
          <el-table-column label='用戶ID' align="center" prop="id"></el-table-column>
          <el-table-column label='暱稱' align="center" prop="nickname"></el-table-column>
          <el-table-column label='手機' align="center" prop="mobile"></el-table-column>
          <el-table-column label='狀態' align="center" prop="active"></el-table-column>
          <el-table-column label='手機系統' align="center" prop="platform"></el-table-column>
          <el-table-column label='註冊時間' align="center" prop="join_date"></el-table-column>
          <el-table-column label='激活時間' align="center" prop="active_time"></el-table-column>
          <el-table-column label='最近一次訪問' align="center" prop="last_visit"></el-table-column>
          <el-table-column label='用戶級別' align="center" prop="vip"></el-table-column>
          <el-table-column label='充值金額' align="center" prop="deposit_total"></el-table-column>
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

  </div>
</template>


<script>
    export default {
        components: {
        },
        data() {
            return {
                loading: false,
                tableData: [],
                selectData: {
                    uid: "",
                    mobile: '',
                    type: '',
                    vip: '',
                    deposit: ''
                },
                page: 1,
                limit: 10,
                total: 1,
                status: false,
                id: 0,
                row: null,
                uid: "",
                channel_id: '0',
                channel_list: [],
                sumary: [],
            };
        },
        created() {
            this.channel_id = this.$route.query.id;
            this.date = this.$route.query.date;
            this.getData(1);
        },
        methods: {
            search() {
                this.page = 1;
                this.getData(1);
            },
            clear() {
                this.selectData.channel_id = "";
                this.selectData.times = [];
            },
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            //1：获取列表 2：导出
            getData(exp = 1) {
                this.loading = true;
                this.$http
                    .post("/statis/channel_user", {
                        channel_id: this.channel_id,
                        date: this.date,
                        uid: this.selectData.uid,
                        mobile: this.selectData.mobile,
                        type: this.selectData.type,
                        vip: this.selectData.vip,
                        deposit: this.selectData.deposit,
                        page_no: this.page,
                        exp: exp
                    })
                    .then(res => {
                        this.loading = false;
                        if (exp == 1) {
                            this.tableData = res.data.list;
                            this.page = res.data.page;
                            this.total = res.data.total;
                        } else {
                            if (res.status == 1) {
                                window.open(`${window.location.origin}${res.data}`);
                            } else {
                                this.$message.error(res.msg);
                            }
                        }
                    });
            }
        }
    };
</script>

<style lang="scss" scoped>
  .realnamelist {
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
