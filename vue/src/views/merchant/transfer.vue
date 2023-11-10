<template>
  <div class="vipcollocation">
    <div class="whiteBg">
      <div class="screen">
        <label style="margin-left: 20px">銀商ID</label>&nbsp;
        <el-input style="width: 300px" v-model="id"></el-input>
        <label style="margin-left: 20px">銀商名稱</label>&nbsp;
        <el-input style="width: 300px" v-model="name"></el-input>
        <el-button
          type="primary"
          class="fr"
          style="padding: 8px 30px"
          @click="search"
        >查詢</el-button>
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
          <el-table-column label="序號" align="center" prop="id"></el-table-column>
          <el-table-column label="銀商ID" align="center" prop="mid"></el-table-column>
          <el-table-column label="銀商名稱" align="center" prop="mname"></el-table-column>
          <el-table-column label="用戶ID" align="center" prop="uid"></el-table-column>
          <el-table-column label="用戶昵稱" align="center" prop="nickname"></el-table-column>
          <el-table-column label="轉賬金幣數量" align="center" prop="amount"></el-table-column>
          <el-table-column label="用戶餘額" align="center" prop="user_balance"></el-table-column>
          <el-table-column label="銀商餘額" align="center" prop="mer_balance"></el-table-column>
          <el-table-column label="轉賬時間" align="center" prop="uptime"></el-table-column>
        </el-table>
        <el-pagination
          @current-change="handleCurrentChange"
          :current-page="page"
          :page-sizes="[this.$store.state.adminPageSize]"
          :page-size="limit"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          class="fr"
          @size-change="sizeChange"
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
      dialogFormVisible: false,
      form: {
          uid:'',
          amount: "",
      },
      name: "",
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      id:'',
    };
  },
  created() {
    this.getData();
  },
  computed: {
    types() {
      return {
        type: "sales",
        usercode: window.localStorage.getItem("usercode")
      };
    }
  },
  methods: {
    search() {
      this.getData();
    },
    getData(exp = 1) {
      this.loading = true;
      this.$http
        .post("merchant/transfer", {
          page: this.page,
          page_size: this.limit,
          name: this.name,
            id: this.id,
          exp: exp
        })
        .then(res => {
          this.loading = false;
          if (exp == 2) {
            window.open(`${window.location.origin}${res.data.path}`);
          } else {
            this.tableData = res.data.list;
            this.page = res.data.page;
            this.total = res.data.total;
          }
        });
    },
    handleCurrentChange(val) {
      this.page = val;
      this.getData();
    },
    sizeChange(val) {
      this.limit = val;
      this.getData(val);
    }
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



                            