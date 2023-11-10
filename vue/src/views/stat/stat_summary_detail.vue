<template>
    <div class="container" v-loading.fullscreen.lock="loading">
        <div class="whiteBg">
            <div class="screen">
                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData(1)">
                    导出Excel
                </el-button>
            </div>
            <div>充值明细</div>
            <div class="screen" style="width: 100%">
                <template>
                    <el-table :data="tableRechargeData" border style="width: 100%">
                        <el-table-column prop="index" label="序号" align="center"></el-table-column>
                        <el-table-column prop="money" label="充值金额" align="center"></el-table-column>
                        <el-table-column prop="channel_name" label="充值渠道" align="center"></el-table-column>
                        <el-table-column prop="order_id" label="订单号" align="center"></el-table-column>
                        <el-table-column prop="status" align="center" label="订单状态">
                            <template slot-scope="scope">
                                <span v-if="scope.row.status == '0'" style="color: #05acb5;">未支付</span>
                                <span v-if="scope.row.status == 'SOLVED' || scope.row.status == '1'" style="color: green;">成功</span>
                                <span v-if="scope.row.status == '2'" style="color: red;">失败</span>
                                <span v-if="scope.row.status == 'PROCESSING'" style="color: #05acb5;">未处理</span>
                                <span v-if="scope.row.status == 'DENIED'" style="color: red;">拒绝</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="order_time" align="center" label="订单时间"></el-table-column>
                        <el-table-column prop="order_note" align="center" label="备注"></el-table-column>
                        <el-table-column prop="related_name" align="center" label="关联方"></el-table-column>
                    </el-table>
                </template>
                <!-- <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
                    <el-pagination
                            @current-change="handleCurrentChange"
                            @size-change="handleCurrentSizeChange"
                            :current-page="page"
                            background
                            :page-sizes="[this.$store.state.adminPageSize]"
                            :page-size="limit"
                            layout="total, sizes, prev, pager, next, jumper"
                            :total="total"
                            class="fr"
                    ></el-pagination>
                </div> -->

            </div>

            <div>提款明细</div>
            <div class="screen" style="width: 100%">
                <template>
                    <el-table :data="tableWithdrawalData" border style="width: 100%">
                        <el-table-column fixed prop="index" label="序号" align="center"></el-table-column>
                        <el-table-column prop="amount" label="提款金额" align="center"></el-table-column>
                        <el-table-column prop="channel_name" label="提款渠道" align="center"></el-table-column>
                        <el-table-column prop="order_id" label="订单号" align="center"></el-table-column>
                        <el-table-column prop="status" align="center" label="订单状态">
                            <template slot-scope="scope">
                                <span v-if="scope.row.status == '等待审核'" style="color: #05acb5;">等待审核</span>
                                <span v-if="scope.row.status == '审核成功'" style="color: green;">审核成功</span>
                                <span v-if="scope.row.status == '审核失败'" style="color: red;">审核失败</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="created_at" align="center" label="订单时间"></el-table-column>
                        <el-table-column prop="reason" align="center" label="备注"></el-table-column>
                        <el-table-column prop="username" align="center" label="关联方"></el-table-column>
                        
                    </el-table>
                </template>
                <!-- <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
                    <el-pagination
                            @current-change="handleCurrentChange"
                            @size-change="handleCurrentSizeChange"
                            :current-page="page"
                            background
                            :page-sizes="[this.$store.state.adminPageSize]"
                            :page-size="limit"
                            layout="total, sizes, prev, pager, next, jumper"
                            :total="total"
                            class="fr"
                    ></el-pagination>
                </div> -->

            </div>

            <div>手工加扣款明细</div>
            <div class="screen" style="width: 100%">
                <template>
                    <el-table :data="tableManualData" border style="width: 100%">
                        <el-table-column prop="index" label="序号" align="center"></el-table-column>
                        <el-table-column prop="amount" label="加扣款币额" align="center">
                            <template slot-scope="scope">
                                <span v-if="scope.row.amount >= 0" style="color: #05acb5;">+ {{ scope.row.amount }}</span>
                                <span v-if="scope.row.amount < 0" style="color: red;"> {{ scope.row.amount }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="memo" label="加扣款备注" align="center"></el-table-column>
                        <el-table-column prop="uptime" label="加扣款时间" align="center"></el-table-column>
                        <el-table-column prop="username" align="center" label="关联方"></el-table-column>
                        
                    </el-table>
                </template>
                <!-- <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
                    <el-pagination
                            @current-change="handleCurrentChange"
                            @size-change="handleCurrentSizeChange"
                            :current-page="page"
                            background
                            :page-sizes="[this.$store.state.adminPageSize]"
                            :page-size="limit"
                            layout="total, sizes, prev, pager, next, jumper"
                            :total="total"
                            class="fr"
                    ></el-pagination>
                </div> -->

            </div>
        </div>

    </div>
</template>

<script>
    export default {
        name: "withdrawal",

        data() {
            return {
                loading: false,
                s_uid: '',
                time_from: '',
                time_to: '',

                tableRechargeData: '',
                tableWithdrawalData: '',
                tableManualData: '',

                search: '',
            }
        },
        created() {
            this.s_uid = this.$route.query.s_uid;
            this.time_from = this.$route.query.time_from;
            this.time_to = this.$route.query.time_to;
            this.getData();
        },

        methods: {

            /**
             * 获取数据
             */
            getData(search = 0) {
                this.loading = true;
                this.$http
                    .post("/stat/stat_summary_detail", {
                        page: this.page,

                        s_uid: this.s_uid,
                        time_from: this.time_from,
                        time_to: this.time_to,
                        search: search,
                    })
                    .then(res => {
                        this.loading = false;
                        this.tableRechargeData = res.data.rechargeData;
                        this.tableWithdrawalData = res.data.withdrawalData;
                        this.tableManualData = res.data.manualData;

                        // this.page = res.data.page;
                        // this.total = res.data.total;
                        // this.summary = res.data.summary;
                        // this.totalCharge = res.data.totalCharge;
                        // this.channels = res.data.channels;

                        if (res.status === 1) {
                            window.open(`${window.location.origin}${res.data}`);
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