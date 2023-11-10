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


                <label style="margin-left: 20px">手机号</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="phone"
                        v-model="phone"
                ></el-input>

                <label style="margin-left: 20px">审核人</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="op_uid"
                >
                    <el-option v-for="(item,index) in op_uid_arr" :key="index" :label='item.username'
                               :value="item.id"></el-option>
                </el-select>

                <label style="margin-left: 20px">处理人</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="op_uid_2"
                >
                    <el-option v-for="(item,index) in op_uid_arr" :key="index" :label='item.username'
                               :value="item.id"></el-option>
                </el-select>

                <label style="margin-left: 20px">审核结果</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="status"
                >
                    <el-option label='通过' value="2"></el-option>
                    <el-option label='拒绝' value="3"></el-option>
                </el-select>

            </div>
            <div class="screen">
                <label style="margin-left: 20px">提款状态</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="pay_status"
                >
                    <el-option label='手动打款' value="1"></el-option>
                    <el-option label='拒绝下发' value="2"></el-option>
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
                                fixed
                                label="用户ID"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="username"
                                label="用户名字"
                                fixed
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
                                prop="amount_txt"
                                align="center"
                                label="提款金额(元)">
                        </el-table-column>
                        <el-table-column
                                prop="service_charge"
                                align="center"
                                label="提款手续费(元)">
                        </el-table-column>
                        <el-table-column
                                prop="bank_type"
                                label="提款渠道"
                                align="center">
                        </el-table-column>

                        <el-table-column
                                prop="order_id"
                                align="center"
                                label="订单号">
                        </el-table-column>
                        <el-table-column
                                prop="created_at"
                                align="center"
                                label="申请时间"
                                width="170">
                        </el-table-column>
                        <el-table-column
                                prop="op_uid"
                                label="审核人"
                                align="center">
                        </el-table-column>

                        <el-table-column
                                prop="status"
                                label="审核结果"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="pay_status"
                                label="提款状态"
                                align="center">
                        </el-table-column>

                        <el-table-column
                                prop="up_time2"
                                label="处理时间"
                                width="170"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="op_uid2"
                                label="处理人"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="pay_content"
                                label="处理备注"
                                align="center">
                        </el-table-column>


                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="80">
                            <template slot-scope="scope">
                                <el-button @click="handleShowClick(scope.row)" type="text" size="small">查看
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

        <!--查看审核详情-->
        <el-dialog title="提款下发" width="35%" center :show-close="false" :visible.sync="dialogFormVisibleDetail">
            <el-form :model="show_info">

                <el-row :gutter="20">
                    <el-col :span="24">
                        <el-form-item label="审核人" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.op_uid" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <el-col :span="24">
                        <el-form-item label="审核结果" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.status" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <el-col :span="24">
                        <el-form-item label="审核备注" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.reason" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <el-col :span="24">
                        <el-form-item label="审核时间" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.up_time" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="提款状态" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.pay_status" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="处理人" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.op_uid2" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="处理备注" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.pay_content" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="处理时间" :label-width="formLabelWidth">
                            <el-input disabled v-model="show_info.up_time2" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <div v-if="show_info.pay_imgs && show_info.pay_imgs.length">
                        <el-col :span="24" v-if="show_info.pay_imgs.length">
                            <el-form-item label="处理凭证" :label-width="formLabelWidth">
                                <div v-for="(item,index) in show_info.pay_imgs" :key="index"
                                     style="width: 150px;display: inline-block;vertical-align:top;text-align: left;">
                                    <el-image :src="item" :preview-src-list="show_info.pay_imgs"
                                              style="width: 130px;height: 130px;"></el-image>
                                </div>
                            </el-form-item>
                        </el-col>
                    </div>

                </el-row>


            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="cancerShow">关 闭</el-button>
            </div>
        </el-dialog>


    </div>
</template>

<script>
    export default {
        name: "remit_money_record",

        data() {
            return {
                loading: false,
                usercode: localStorage.getItem('usercode'),

                status: '',
                pay_status: '',
                uid: '',
                op_uid: '',
                op_uid_2: '',
                phone: '',
                search_time: '',
                order_id: '',


                op_uid_arr: [],
                tableData: [],
                limit: 10,
                limits: [10, 15, 20],
                page: 1,
                total: 0,
                total_money: 0,
                page_money: 0,


                formLabelWidth: '130px',
                //审核详情
                dialogFormVisibleDetail: false,
                show_info: {},


            }
        },


        mounted() {
            this.getOpUid();
            this.getData();
        },

        methods: {

            /**
             * 获取审核人
             */
            getOpUid() {
                this.$http
                    .post("babytrans/get_opuid", {})
                    .then(res => {
                        if (res.status !== 1) {
                            this.$message.error('获取操作人数据错误')
                        } else {
                            this.op_uid_arr = res.data;
                        }
                    });
            },

            clear() {
                this.uid = "";
                this.nickname = "";
                this.phone = "";
                this.status = "";
                this.pay_status = "";
                this.search_time = [];
                this.order_id = [];
                this.op_uid = '';
                this.op_uid_2 = '';


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
                    .post("withdrawal/remit_money_record", {
                        type: type,
                        page: this.page,
                        size: this.limit,

                        status: this.status,
                        pay_status: this.pay_status,
                        uid: this.uid,
                        time: this.search_time,
                        op_uid: this.op_uid,
                        op_uid2: this.op_uid_2,
                        order_id: this.order_id,
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
             * 查看审核详情
             * @param row
             */
            handleShowClick(row) {
                this.show_info = row;
                this.dialogFormVisibleDetail = true;
            },


            cancerShow() {
                this.show_info = {};
                this.dialogFormVisibleDetail = false;
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