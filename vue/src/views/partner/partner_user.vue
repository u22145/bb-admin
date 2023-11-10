<template>
    <div class="realnamelist">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">用户ID</label>&nbsp;
                <el-input
                        style="width: 120px"
                        v-model="selectData.uid"
                        @keyup.enter.native="getData"
                ></el-input>
                <label style="margin-left: 20px">用户昵称</label>&nbsp;
                <el-input
                        style="width: 120px"
                        v-model="selectData.nickname"
                        @keyup.enter.native="getData"
                ></el-input>
                <label style="margin-left: 20px">用户手机</label>&nbsp;
                <el-input
                        style="width: 120px"
                        id="partner_name"
                        v-model="selectData.mobile"
                        @keyup.enter.native="getData"
                ></el-input>
                <label style="margin-left: 20px">用户状态</label>&nbsp;
                <el-select placeholder v-model="selectData.active">
                    <el-option label='全部' value="0"></el-option>
                    <el-option label='已激活' value="1"></el-option>
                    <el-option label='未激活' value="2"></el-option>
                </el-select>
                <label style="margin-left: 20px">是否代理商</label>&nbsp;
                <el-select placeholder v-model="selectData.agent">
                    <el-option label='全部' value="0"></el-option>
                    <el-option label='是' value="1"></el-option>
                    <el-option label='否' value="2"></el-option>
                </el-select>
                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">搜索</el-button>
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
                    <el-table-column label='合作商ID' align="center" prop="partner_id"></el-table-column>
                    <el-table-column label='用户ID' align="center" prop="id"></el-table-column>
                    <el-table-column label='用戶昵称' align="center" prop="nickname"></el-table-column>
                    <el-table-column label='手机' align="center" prop="mobile"></el-table-column>
                    <el-table-column label='状态' align="center" prop="active"></el-table-column>
                    <el-table-column label='手机系统' align="center" prop="platform"></el-table-column>
                    <el-table-column label='注册时间' align="center" prop="join_date"></el-table-column>
                    <el-table-column label='激活时间' align="center" prop="active_time"></el-table-column>
                    <el-table-column label='最近一次访问' align="center" prop="last_visit"></el-table-column>
                    <el-table-column label='所属上级' align="center" prop="upper_uid"></el-table-column>
                    <el-table-column label='是否代理商' align="center" prop="agent"></el-table-column>
                    <el-table-column label='成为代理商时间' align="center" prop="join_agent"></el-table-column>
                    <el-table-column label='充值金额' align="center" prop="deposit_total"></el-table-column>
                    <el-table-column label='操作' align="center" min-width="130px" class-name="operation">
                        <template slot-scope="scope">
                            <el-button @click="viewsub(scope.row.partner_id, scope.row.id)">查看下級</el-button>
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
        components: {},
        data() {
            return {
                loading: false,
                tableData: [],
                selectData: {
                    uid: "",
                    nickname: "",
                    mobile: '',
                    agent: '',
                    active: ''
                },
                page: 1,
                limit: 10,
                total: 1,
                status: false,
                id: 0,
                row: null,
                uid: "",
                partner_id: '0',
                partner_list: [],
                sumary: [],
                date: '',
                upper: ''
            };
        },
        created() {
            this.partner_id = this.$route.query.id;
            this.date = this.$route.query.date;
            this.upper = this.$route.query.upper;
            this.getData(1);
        },
        methods: {
            search() {
                this.page = 1;
                this.getData(1);
            },
            viewsub(id, upper) {
                this.partner_id = id;
                this.upper = upper;
                this.getData(1);
            },
            clear() {
                this.selectData.partner_id = "";
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
                    .post("/partner/partner_user", {
                        partner_id: this.partner_id,
                        uid: this.selectData.uid,
                        nickname: this.selectData.nickname,
                        mobile: this.selectData.mobile,
                        agent: this.selectData.agent,
                        active: this.selectData.active,
                        page_no: this.page,
                        date: this.date,
                        upper: this.upper,
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
