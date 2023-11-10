<template>
    <div class="container" v-loading.fullscreen.lock="loading">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">用户ID</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="inputID"
                        v-model="uid"
                ></el-input>

                <label style="margin-left: 20px">昵称</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="nickname"
                        v-model="nickname"
                ></el-input>


                <label style="margin-left: 20px">手机号</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="phone"
                        v-model="phone"
                ></el-input>

                <label style="margin-left: 20px">状态</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="status"
                >
                    <el-option label='全部' value="0"></el-option>
                    <el-option label='启用' value="1"></el-option>
                    <el-option label='禁用' value="2"></el-option>
                </el-select>

            </div>
            <div class="screen">

                <label style="margin-left: 20px">主播</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="is_anchor"
                >
                    <el-option label='是' value="1"></el-option>
                    <el-option label='否' value="2"></el-option>
                </el-select>

                <label style="margin-left: 20px">订单号</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="order_id"
                        v-model="order_id"
                ></el-input>


                <label style="margin-left: 20px">时间</label>&nbsp;
                <el-date-picker
                        clearable
                        style="width: 350px"
                        v-model="search_time"
                        type="datetimerange"
                        format="yyyy-MM-dd"
                        value-format="timestamp"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期">
                </el-date-picker>

                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">
                    搜索
                </el-button>
            </div>
        </div>

        <div class="whiteBg">
            <div class="fr">
                <el-button @click="clear">清空查询</el-button>
                <el-button @click="getData(2)">导出Excel</el-button>
            </div>
            <div class="screen" style="width: 100%">
                <template>
                    <el-table
                            :data="tableData"
                            border
                            style="width: 100%">
                        <el-table-column
                                prop="uid"
                                label="用户ID"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="username"
                                label="用户名字"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="mobile"
                                label="注册手机"
                                width="120"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="is_anchor"
                                label="主播"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="curr_baby"
                                label="当前baby币"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="total_profit_loss"
                                label="累计盈亏"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="total_recharge"
                                label="累计充值"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="total_lose"
                                label="累计打码"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="return_money"
                                label="返水币额"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="amount_txt"
                                align="center"
                                label="申请币额(baby)">
                        </el-table-column>
                        <el-table-column
                                prop="order_id"
                                align="center"
                                label="订单号">
                        </el-table-column>
                        <el-table-column
                                align="center"
                                prop="user_status"
                                label="状态">
                        </el-table-column>

                        <el-table-column
                                prop="created_at"
                                align="center"
                                label="提现时间"
                                width="170">
                        </el-table-column>

                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="150">
                            <template slot-scope="scope">
                                <el-button @click="handleShowClick(scope.row)" type="text" size="small">查看</el-button>


                                <el-button @click="handleDealClick(scope.row,2)" type="text" size="small">通过</el-button>


                                <el-button @click="handleDealClick(scope.row,3)" type="text" size="small">
                                    <span style="color: red;">拒绝</span>
                                </el-button>


                            </template>
                        </el-table-column>
                    </el-table>
                </template>
                <div style="width: 100%;margin-top: 3rem;">
                    <p style="margin-bottom: 0.5rem;font-size: 1.2rem;">
                        小计 （本页笔数/本页总币额）： {{tableData.length}} 笔 / {{page_money.toFixed(2)}}
                    </p>
                    <p style="font-size: 1.2rem;">
                        共计 （总笔数/总币额）： {{total}} 笔 / {{total_money.toFixed(2)}}
                    </p>
                </div>

                <div style="width: 100%;margin: 0 auto;margin-bottom: 4rem;">
                    <el-pagination
                            @current-change="handleCurrentChange"
                            @size-change="handleCurrentSizeChange"
                            :current-page="page"
                            background
                            :page-sizes="limits"
                            :page-size="limit"
                            layout="total, sizes, prev, pager, next, jumper"
                            :total="total"
                            class="fr"
                    ></el-pagination>
                </div>


            </div>
        </div>

        <!--审核-->
        <el-dialog title="操作备注" width="30%" center :show-close="false" :visible.sync="dialogFormVisible">
            <el-form :model="form">
                <el-input
                        type="textarea"
                        :rows="5"
                        placeholder=""
                        v-model="form.reason">
                </el-input>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="cancerDeal">取 消</el-button>
                <el-button type="primary" @click="subDeal">确 定</el-button>
            </div>
        </el-dialog>

    </div>
</template>

<script>
    export default {
        name: "withdrawal",

        data() {
            return {
                loading: false,
                usercode: localStorage.getItem('usercode'),

                status: '',
                uid: '',
                nickname: '',
                phone: '',
                search_time: '',
                is_anchor: '',
                order_id: '',

                tableData: [],
                limit: 10,
                limits: [10, 15, 20],
                page: 1,
                total: 0,
                total_money: 0,
                page_money: 0,


                formLabelWidth: '130px',
                //审核
                dialogFormVisible: false,
                record_row: {}, //记录要操作的信息
                form: {
                    status: '',
                    reason: ''
                },


            }
        },


        mounted() {

            this.getData();
        },

        methods: {

            /**
             * 查看
             */
            handleShowClick(row) {

                var start_time = this.search_time && this.search_time[0] ? this.dataFormat(this.search_time[0]) : '';
                var end_time = this.search_time && this.search_time[1] ? this.dataFormat(this.search_time[1]) : '';
                this.$router.push({
                    path: '/stat_summary_detail',
                    query: {
                        s_uid: row.uid,
                        time_from: start_time,
                        time_to: end_time,
                    }
                });

            },


            /**
             * 转化时间
             */
            dataFormat(datetime) {
                console.log(datetime)
                datetime = parseInt(datetime); //uni毫秒时间戳
                var date = new Date(datetime);
                var year = date.getFullYear(),
                    month = ("0" + (date.getMonth() + 1)).slice(-2),
                    sdate = ("0" + date.getDate()).slice(-2);
                // hour = ("0" + date.getHours()).slice(-2),
                // minute = ("0" + date.getMinutes()).slice(-2),
                // second = ("0" + date.getSeconds()).slice(-2);
                // 拼接
                return year + "-" + month + "-" + sdate + " 00:00:00";
            },

            clear() {
                this.uid = "";
                this.nickname = "";
                this.phone = "";
                this.status = "";
                this.is_anchor = "";
                this.search_time = [];
                this.order_id = [];


                this.page = 1;
                this.getData();
            },


            /**
             * 搜索
             */
            search() {
                this.page = 1;
                this.tableData = [];
                this.getData();
            },


            /**
             * 获取数据
             */
            getData(type = 1) {
                this.loading = true;
                this.$http
                    .post("withdrawal/index", {
                        type: type,
                        page: this.page,
                        size: this.limit,
                        status: this.status,
                        uid: this.uid,
                        time: this.search_time,
                        nickname: this.nickname,
                        order_id: this.order_id,
                        is_anchor: this.is_anchor,
                        phone: this.phone,
                    })
                    .then(res => {
                        this.loading = false;
                        if (type === 1) {
                            if (res.status !== 1) {
                                this.$message.error('获取数据错误')
                            } else {
                                this.total = res.data.total;
                                this.tableData = res.data.info;
                                this.total_money = res.data.total_money;
                                this.page_money = res.data.page_money;

                            }

                        } else {
                            if (res.status === 1) {
                                window.open(`${window.location.origin}${res.data}`);
                            } else {
                                this.$message.error(res.msg);
                            }
                        }

                    });
            },

            /**
             * 分页
             */
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },

            /**
             * 每页条数
             */
            handleCurrentSizeChange(val) {
                this.limit = val;
                this.getData();
            },


            /**
             * 审核处理
             * @param row
             * @param type
             */
            handleDealClick(row, type) {
                this.record_row = row;
                this.dialogFormVisible = true;
                this.form.status = type;
            },


            /**
             * 处理审核情况
             */
            subDeal() {

                if (!this.form.reason) {
                    this.$message.error("请输入操作备注说明");
                    return false;
                }
                this.loading = true;
                this.$http
                    .post("withdrawal/deal_info", {
                        status: this.form.status,
                        reason: this.form.reason,
                        id: this.record_row.id,
                        uid: this.record_row.uid,
                        amount: this.record_row.amount,
                        usercode: this.usercode,
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status !== 1) {
                            this.$message.error(res.msg);
                        } else {
                            this.$message.success(res.msg);
                            this.cancerDeal();
                            this.getData();
                        }
                    });
            },


            /**
             * 取消审核弹框
             */
            cancerDeal() {
                this.form.status = '';
                this.form.reason = '';
                this.record_row = {};
                this.dialogFormVisible = false;
            },


            /**
             * 删除操作
             */
            handleDeleteClick(row) {
                this.loading = true;
                this.$http
                    .post("withdrawal/delete_info", {
                        id: row.id,
                    })
                    .then(res => {
                        this.loading = false;
                        this.$message.error(res.msg);
                        if (res.status === 1) {
                            this.getData();
                        }
                    });
            },
        }
    }
</script>

<style lang="less" scoped>

    /deep/ .hidden {
        display: none !important;
    }

    .container {
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

    /deep/ .el-form-item__content {
        margin-left: 0px;
    }
</style>