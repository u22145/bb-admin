<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">{{$t('private_msg_restrict.input')}}</label>&nbsp;
        <el-input style="width: 200px" v-model="level"></el-input>
        <el-button
          type="primary"
          class="fr"
          style="padding: 8px 30px"
          @click="getData()"
        >{{$t("private_msg_restrict.enter")}}</el-button>
      </div>
    </div>
    <div class="whiteBg">
      
      <div class="tableCon">
        <el-table
          ref="multipleTable"
          :data="tableData"
          v-loading="loading"
          border
          style="width: 100%;margin-top: 30px"
        >
          <el-table-column label="ID" align="center" prop="id"></el-table-column>
          <el-table-column :label="$t('private_msg_restrict.restrictLevel')" align="center" prop="level"></el-table-column>
          <el-table-column :label="$t('private_msg_restrict.modifiedById')" align="center" prop="admin_id"></el-table-column>
          <el-table-column :label="$t('private_msg_restrict.modifiedByName')" align="center" prop="admin_name"></el-table-column>
          <el-table-column :label="$t('private_msg_restrict.modifiedAt')" align="center" prop="update_at"></el-table-column>
          
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
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
  data() {
    return {
      loading: false,
      tableData: [],
      level: 0,
      // form: {
      //   level: ""
      // },
      name: "",
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,

    };
  },
  created() {
    this.getData();
  },
  methods: {
    // createLog() {
    //   this.loading = true;
    //   this.$http
    //     .post("other/set_private_restrict_level", {
    //       level: this.setLevel,
    //     })
    //     .then(res => {
    //       this.loading = false;
    //       this.tableData = res.data.list;
    //       this.page = res.data.page;
    //       this.total = res.data.total;
    //     });
    // },
    getData() {
      this.loading = true;
      this.$http
        .post("other/msg_restrict_list", {
          page: this.page,
          page_size: this.limit,
          level: this.level,
        })
        .then(res => {
          this.loading = false;
          
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
        });
    },
  }
};
</script>

<style lang="scss" scoped>
.vipcollocation {
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
