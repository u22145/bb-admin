<template>
    <div class="userlist">
        <div class="whiteBg">
            <div class="screen">
                <label for="s_uid">用户ID</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="s_uid"
                        v-model="s_uid"
                        @keyup.enter.native="seach"
                ></el-input>
                <label for="s_nickname">昵称</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="s_nickname"
                        v-model="s_nickname"
                        @keyup.enter.native="seach"
                ></el-input>
                <label for="s_mobile">手机</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="s_mobile"
                        v-model="s_mobile"
                        @keyup.enter.native="seach"
                ></el-input>
               

                <label for="s_time">查询时间</label>&nbsp;
                <el-date-picker
                        v-model="s_time"
                        type="datetimerange"
                        @change="handleTimeChange"
                        :range-separator='$t("common.to")'
                        start-placeholder='开始时间'
                        end-placeholder='结束时间'
                        value-format="yyyy-MM-dd HH:mm:ss"
                ></el-date-picker>
                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="seach()">查询</el-button>
            </div>
        </div>

        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            充值人数/充值金额<br/>{{summary.summary_recharge_users}} / {{summary.summary_recharge_amount}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            提款人数/提款金额<br/>{{summary.summary_withdrawal_users}} / {{summary.summary_withdrawal_amount}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            首冲人数/首冲金额<br/>{{summary.summary_first_users}} / {{summary.summary_first_amount}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            线上支付金额<br/>{{summary.summary_online_amount}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            线下支付金额<br/>{{summary.summary_offline_amount}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            注册人数/新增VIP<br/>{{summary.summary_register_users}} / {{ summary.summary_new_vips }}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            直播盈利/直播礼物<br/>{{summary.summary_platform_income}} / {{summary.summary_gift_num}}
        </div>

        <div class="whiteBg">
            <div class="fr">
                <el-button @click="getData(1)">导出Excel</el-button>
                <el-button @click="clear">清空查询</el-button>
            </div>
            <div class="tableCon">
                <el-table
                        ref="multipleTable"
                        :data="tableData"
                        v-loading="loading"
                        border
                        style="width: 99%;margin-top: 30px"
                >
                    <!-- <el-table-column label="序号" align="center" type="index" width="50">
                      <template slot-scope="scope">
                        <span>{{scope.$index+(page - 1) * limit + 1}}</span>
                      </template>
                    </el-table-column>-->
                    <el-table-column label='序号' align="center" fixed prop="index"></el-table-column>
                    <el-table-column label='用户ID' align="center" fixed prop="uid"></el-table-column>
                    <el-table-column label='用户昵称' align="center" fixed prop="nickname"></el-table-column>
                    <el-table-column label='注册手机号' align="center" fixed prop="mobile"></el-table-column>
                    <el-table-column label='会员' align="center" fixed prop="is_member"></el-table-column>
                    <el-table-column label='当前baby币' align="center" fixed prop="eurc_balance"></el-table-column>
                    <el-table-column label='充值总额' align="center" fixed prop="user_recharge_amount"></el-table-column>
                    <el-table-column label='提款总额' align="center" fixed prop="user_withdrawal_amount"></el-table-column>
                    <el-table-column label='盈亏总额' align="center" fixed prop="user_profit">
                        <template slot-scope="scope">
                            <span v-if="scope.row.user_profit >= 0" style="color: #05acb5;">+ {{ scope.row.user_profit }}</span>
                            <span v-if="scope.row.user_profit < 0" style="color: red;"> {{ scope.row.user_profit }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label='手工加扣款总币额' align="center" fixed prop="user_manual_amount">
                        <template slot-scope="scope">
                            <span v-if="scope.row.user_manual_amount >= 0" style="color: #05acb5;">+ {{ scope.row.user_manual_amount }}</span>
                            <span v-if="scope.row.user_manual_amount < 0" style="color: red;"> {{ scope.row.user_manual_amount }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label='提款总手续费' align="center" fixed prop="user_wtf_amount"></el-table-column>
                    <el-table-column label='反水总额' align="center" fixed prop="user_commission"></el-table-column>

                    <el-table-column :label='$t("common.operation")' fixed="right" align="center" class-name="operation">
                        <template slot-scope="scope">
                            <el-button type="text" size="small" @click="checkDetailInfo(scope.row)">查看</el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <div>
                    {{`小计 （本页会员 充值总额/提款总额/手工加扣款总币额）： ` + summary.page_recharge_total + `  / ` + summary.page_withdrawal_total + `  / ` +  summary.page_manual_total }} 
                    <br>
                    {{`共计 （所有会员 充值总额/提款总额/手工加扣款总币额）： ` + summary.total_recharge_amount + `  / ` + summary.total_withdrawal_amount + `  / ` +  summary.total_manual_amount}}
                </div>
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
        components: {
            Userdetails: () => import("../../components/userdetails")
        },
        data() {
            return {
                loading: false,
                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 1,

                s_uid: '',
                s_time: [],
                time_from: '',
                time_to: '',
                s_mobile: '',
                s_nickname: '',

                summary: [],
                search: '',
                
            };
        },

        mounted() {
            this.getData();
        },
        methods: {
            clear() {
                this.s_uid = "";
                this.s_nickname = "";
                this.s_mobile = "";
                this.s_time = "";

                this.page = 1;
                this.getData();
            },
            // 查询
            seach() {
                this.page = 1;
                this.getData();
            },
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            handleTimeChange() {
                this.time_from = this.s_time[0].toString();
                this.time_to = this.s_time[1].toString();
            },
            //请求列表
            getData(search = 0) {
                this.loading = true;
                this.$http
                    .post("/stat/stat_summary", {
                        page: this.page,

                        s_uid: this.s_uid,
                        s_nickname: this.s_nickname,
                        s_mobile: this.s_mobile,
                        s_time: this.s_time,

                        search: search,
                    })
                    .then(res => {
                        this.loading = false;
                        this.tableData = res.data.data;
                        this.page = res.data.page;
                        this.total = res.data.total;
                        this.summary = res.data.summary;
                        // this.totalCharge = res.data.totalCharge;
                        // this.channels = res.data.channels;

                        if (res.status === 1) {
                            res.data.forEach(function(item) {
                                window.open(`${window.location.origin}${item}`);
                            })
                            
                        }
                    });
            },

            checkDetailInfo(row) {
                this.$router.push({
                    path: '/stat_summary_detail',
                    query: {
                        s_uid: row.uid,
                        time_from: this.time_from,
                        time_to: this.time_to,
                    }
                });
            }
        }
    };
</script>

<style lang="less" scoped>
    .userlist {
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


        /deep/ .add_desc_coin_class {
            .el-dialog--center .el-dialog__body {
                padding: unset;
            }

            .el-dialog__body {
                padding: unset;
            }
        }
    }
</style>



