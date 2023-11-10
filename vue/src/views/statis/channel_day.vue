<template>
  <div class="realnamelist">
    <div class="whiteBg">
      <div>
        <div style="background-color: #33ccff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">DAU<br />{{sumary.dau}}</div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">WAU<br />{{sumary.wau}}</div>
        <div style="background-color: #99cc66; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">MAU<br />{{sumary.mau}}</div>
        <div style="background-color: #ff9900; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">激活用戶數<br />{{sumary.total_active}}</div>
        <div style="background-color: #ffcc00; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">充值用戶數<br />{{sumary.total_dep}}</div>
      </div>
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
          <el-table-column label='日期' align="center" prop="date"></el-table-column>
          <el-table-column label='通路ID' align="center" prop="id"></el-table-column>
          <el-table-column label='UV' align="center" prop="uv"></el-table-column>
          <el-table-column label='註冊' align="center" prop="reg"></el-table-column>
          <el-table-column label='下載' align="center" prop="down"></el-table-column>
          <el-table-column label='激活' align="center" prop="active"></el-table-column>
          <el-table-column label='充值人數' align="center" prop="dep2"></el-table-column>
          <el-table-column label='充值金額' align="center" prop="dep1"></el-table-column>
          <el-table-column label='UV-註冊' align="center" prop="rate1"></el-table-column>
          <el-table-column label='註冊-充值' align="center" prop="rate2"></el-table-column>
          <el-table-column label='操作' align="center" min-width="130px" class-name="operation">
            <template slot-scope="scope">
              <router-link :to="'/channel_user?id='+scope.row.id+'&date='+scope.row.date">查看</router-link>
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
                    channel_id: "",
                    channel_name: '',
                    type: '',
                    times: []
                },
                page: 1,
                limit: 10,
                total: 50,
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
            this.getData(1);
        },
        methods: {
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            //1：获取列表 2：导出
            getData(exp = 1) {
                this.loading = true;
                this.$http
                    .post("/statis/channel_day", {
                        channel_id: this.channel_id,
                        page_no: this.page,
                        exp: exp
                    })
                    .then(res => {
                        this.loading = false;
                        if (exp == 1) {
                            this.tableData = res.data.list;
                            this.page = res.data.page;
                            this.total = res.data.total;
                            this.sumary = res.data.sumary;
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
