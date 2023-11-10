<template>
  <div class="realnamelist">
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
          <el-table-column label='日期' align="center" prop="date"></el-table-column>
          <el-table-column label='渠道ID' align="center" prop="id"></el-table-column>
          <el-table-column label='名稱' align="center" prop="name"></el-table-column>
          <el-table-column label='点击量(Android)' align="center" prop="dl_android"></el-table-column>
          <el-table-column label='点击量(ios)' align="center" prop="dl_ios"></el-table-column>
          <el-table-column label='浏览量小網)' align="center" prop="dl_web"></el-table-column>
          <el-table-column label='浏览量(大網)' align="center" prop="dl_pc"></el-table-column>
          <el-table-column label='註冊(Android)' align="center" prop="reg_android"></el-table-column>
          <el-table-column label='註冊(IOS)' align="center" prop="reg_ios"></el-table-column>
          <el-table-column label='註冊(小網)' align="center" prop="reg_web"></el-table-column>
          <el-table-column label='註冊(大網)' align="center" prop="reg_pc"></el-table-column>
          
          <el-table-column label='狀態' align="center" prop="status_txt"></el-table-column>
        </el-table>
        <el-pagination
                @current-change="handleCurrentChange"
                :current-page="page"
                :page-sizes="[limit]"
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
                page: 1,
                limit: '',
                total: "",
                status: false,
                id: 0,
                row: null,
                uid: "",
                advert_id: '0',
            };
        },
        created() {
            this.advert_id = this.$route.query.id;
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
                    .post("/advert/advert_day", {
                        advert_id: this.advert_id,
                        page_no: this.page,
                        exp: exp
                    })
                    .then(res => {
                        this.loading = false;
                        if (exp == 1) {
                            this.tableData = res.data.list;
                            this.page = res.data.page;
                            this.total = res.data.total;
                            this.limit = res.data.limit;
                        } else {
                            if (res.status == 1) {
                                window.open(`${window.location.origin}${res.data}`);
                            } else {
                                this.$message.error(res.msg);
                            }
                        }
                    });
            },
            // 跳转到user统计表
            showUsers(name, date) {
              
                this.loading = true;
                this.$router.push({
                    path: '/user/index',
                    query: {
                        advert_name: name,
                        join_date_end: date
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
