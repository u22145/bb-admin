<template>
  <div class="LabourUnionanchorincome">
    <!-- 公会主播收益情况 -->
    <el-dialog
      :visible.sync="status"
      :fullscreen="true"
      :modal-append-to-body="true"
      :append-to-body="true"
      lock-scroll
      :show-close="false"
      custom-class="UserInfoContent"
      :close-on-press-escape="false"
    >
      <div>
        <!-- 头部 -->
        <div class="conTitle">{{$t("commentlist.conTitle4")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->

        <div class="detailed">
          <div class="essentialInformation">
            <div class="stat_con">
              <div style="margin-bottom: 20px">
                <div class="screen">
                  <label for="uid">{{$t("LabourUnion.AnchorID")}}</label>&emsp;
                  <el-input
                    style="width: 200px"
                    id="uid"
                    v-model="query_data.uid"
                  ></el-input>
                  &emsp;<label for="nickname">{{$t("common.nickname")}}</label>&emsp;
                  <el-input
                    style="width: 200px"
                    id="nickname"
                    v-model="query_data.nickname"
                  ></el-input>
                  <el-button class="fr" style="padding: 8px 30px" @click="getData()">{{$t('common.search')}}</el-button>
                </div>
                <el-table ref="multipleTable" :data="tableData" border style="width: 100%">
                  <el-table-column prop="uid" :label='$t("LabourUnion.AnchorID")' align="center"></el-table-column>
                  <el-table-column prop="nickname" :label='$t("common.nickname")' align="center"></el-table-column>
                  <el-table-column prop="soc_msq_pa" align="center" :label='$t("commentlist.soc_msq_pa")'></el-table-column>
                  <el-table-column prop="soc_eurc_pa" align="center" :label='$t("commentlist.soc_eurc_pa")'></el-table-column>
                  <el-table-column prop="soc_msq_pa_sum" align="center" :label='$t("commentlist.soc_msq_pa_sum")'></el-table-column>
                  <el-table-column prop="soc_eurc_pa_sum" align="center" :label='$t("commentlist.soc_eurc_pa_sum")'></el-table-column>
                </el-table>
                <!-- 分页 -->
                <el-pagination
                  @size-change="handleSizeChange"
                  @current-change="handleCurrentChange"
                  @prev-click="handlePrevClick"
                  @next-click="handleNextClick"
                  :current-page="page"
                  :page-sizes="[10,15,20]"
                  :page-size="limit"
                  layout="total, sizes, prev, pager, next, jumper"
                  :total="total"
                  class="fr"
                ></el-pagination>
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: "userdetails",
  data() {
    return {
      query_data: {
        uid: "",
        nickname: ""
      },
      tableData: [],
      page: 1,
      limit: 10,
      total: 1
    };
  },
  props: {
    status: null,
    id: null,
    date: null
  },
  created() {
    this.getData();
  },
  watch: {
    status() {
      if (this.status == true) {
        this.getData();
      }
    }
  },
  methods: {
    //  主播收益详情列表
    getData() {
      this.$http
        .post("/sociaty/sociaty_anchor_statement_list", {
          uid: this.query_data.uid,
          date: this.date,
          sociaty_id: this.id,
          nickname: this.query_data.nickname,
          page_no: this.page,
          page_size: this.limit
        })
        .then(res => {
          this.tableData = res.data.data;
          this.page = res.data.page_no;
          this.limit = res.data.page_size;
          this.total = res.data.total;
        });
    },
    //  返回
    goback() {
      this.$emit("changeStatus", false);
    },
    // 分页设置
    handleSizeChange(val) {
      this.limit = val;
      this.page = 1;
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    handlePrevClick(val) {
      this.page = val;
      this.getData();
    },
    handleNextClick(val) {
      this.page = val;
      this.getData();
    }
  }
};
</script>

<style lang="scss">
.UserInfoContent {
  .el-dialog__body {
    width: 1200px;
    margin: 0 auto;
  }

  .detailed {
    margin: 40px 0;

    .screen {
      margin-top: 30px;
      margin-bottom: 20px;
      .fr {
        margin-left: 20px;
      }
    }

    .stat_con {
      > div {
        display: inline-block;
        vertical-align: top;
        width: 100%;
      }
      img {
        height: 100%;
      }
    }
  }
}
</style>

