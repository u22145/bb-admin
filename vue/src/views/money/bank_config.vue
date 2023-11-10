<template>
    <div class="container" v-loading.fullscreen.lock="loading">
        <div class="whiteBg">
            <div class="screen">
                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="showCreateBankForm">
                    创建
                </el-button>
            </div>
        </div>

        <div class="whiteBg">
            <div class="screen" style="width: 100%">
                <template>
                    <el-table
                            :data="tableData"
                            border
                            style="width: 100%">
                        <el-table-column
                                fixed
                                prop="index"
                                label="序号"
                                align="center"
                                width="100">
                        </el-table-column>
                        <el-table-column
                                prop="channel_name"
                                label="前端显示"
                                align="center"
                                fixed
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="bank_name"
                                label="所属银行"
                                align="center"
                                fixed
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="branch_name"
                                label="所属支行"
                                align="center"
                                width="150">
                        </el-table-column>
                        <el-table-column
                                prop="card_number"
                                align="center"
                                label="银行卡号"
                                width="200">
                        </el-table-column>
                        <el-table-column
                                prop="card_number"
                                align="center"
                                width="200"
                                label="持有人">
                        </el-table-column>
                        <el-table-column
                                prop="operator"
                                align="center"
                                width="200"
                                label="操作人">
                        </el-table-column>
                        <el-table-column
                                prop="updated_at"
                                align="center"
                                label="操作时间"
                                width="170">
                        </el-table-column>
                        <el-table-column
                                prop="status"
                                align="center"
                                label="状态">
                        </el-table-column>
                       
                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="150">
                            <template slot-scope="scope">
                                <span v-if="scope.row.status == 'ON' "><el-button type="primary" class="fr" @click="swtichBankChannel('switch', 'OFF', scope.row.id)">{{$t("money.paymentOff")}}</el-button></span>
                                <span v-if="scope.row.status == 'OFF'" ><el-button type="primary" class="fr" @click="swtichBankChannel('switch', 'ON', scope.row.id)">{{$t("money.paymentOn")}}</el-button></span>
                                <span v-if="scope.row.status == 'OFF'" ><el-button type="primary" class="fr" @click="swtichBankChannel('delete', 1, scope.row.id)">{{$t("money.paymentDel")}}</el-button></span>
                            </template>
                        </el-table-column>
                    </el-table>
                </template>
                <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
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
                </div>

            </div>
        </div>


        <!--添加银行卡-->
        <el-dialog title="添加银行卡" width="50%" center :show-close="false" :visible.sync="dialogFormVisibleDetail">
            <el-form :model="show_info">

                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-form-item label="所属银行" :label-width="formLabelWidth">
                            <el-select
                                    clearable
                                    placeholder='请选择'
                                    v-model="bank_form.bank_id"
                            >
                                <el-option v-for="(item,index) in bank_info" :key="index" :label='item.name'
                                           :value="item.id"></el-option>
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="所属支行" :label-width="formLabelWidth">
                            <el-input  v-model="bank_form.branch_name" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <el-col :span="12">
                        <el-form-item label="银行卡号" :label-width="formLabelWidth">
                            <el-input  v-model="bank_form.card_number" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <el-col :span="12">
                        <el-form-item label="确认卡号" :label-width="formLabelWidth">
                            <el-input  v-model="bank_form.card_number_confirm" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                    <el-col :span="12">
                        <el-form-item label="持有人" :label-width="formLabelWidth">
                            <el-input  v-model="bank_form.holder_name" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="前端显示名称" :label-width="formLabelWidth">
                            <el-input  v-model="bank_form.channel_name" autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>

                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="cancelDeal">取消</el-button>
                <el-button type="primary" @click="submitDeal">确定</el-button>
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

                index: '',
                channel_name: '',
                bank_name: '',
                bank_branch_name: '',
                bankcard_no: '',
                bankcard_owner: '',
                operator: '',
                created_at: '',
                status: '',

                bank_form: {
                    bank_id: '',
                    branch_name: '',
                    card_number: '',
                    card_number_confirm: '',
                    holder_name: '',
                    channel_name: '',
                },

                bank_info: [],//银行类型集合

                tableData: [],
                limit: this.$store.state.adminPageSize,
                limits: [10, 15, 20],
                page: 1,
                total: 0,
                total_money: 0,
                page_money: 0,

                formLabelWidth: '130px',


                //创建银行卡
                dialogFormVisibleDetail: false,
                show_info: {}
            }
        },


        mounted() {

            this.getBankData();

            this.getData();
        },

        methods: {

            /**
             * 获取银行类型
             */
            getBankData() {
                this.$http
                    .post("withdrawal/bank_type", {})
                    .then(res => {
                        this.loading = false;
                        if (res.status !== 1) {
                            this.$message.error('获取数据错误')
                        } else {
                            this.bank_info = res.data;
                        }
                    });
            },


            /**
             * 获取数据
             */
            getData() {
                this.loading = true;
                this.$http
                    .post("trade/bank_payment_channel_config", {
                        page: this.page,
                        size: this.limit,
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status !== 1) {
                            this.$message.error('获取数据错误')
                        } else {
                            this.total = res.data.total;
                            this.page = res.data.page;
                            this.tableData = res.data.list;
                            this.page_count = res.data.page_count;
                            this.page_size = res.data.page_size;
                        }
                    });
            },

            /**
             * 创建银行卡
             * @param row
             */
            showCreateBankForm() {
                this.dialogFormVisibleDetail = true;
            },

            /**
             * 取消创建弹框
             */
            cancelDeal() {
                this.record_row = {};
                this.dialogFormVisibleDetail = false;
            },

            /**
             * 处理审核情况
             */
            submitDeal() {
                
                this.loading = true;
                this.$http
                    .post("trade/create_bank_payment_channel", {
                        bank_id: this.bank_form.bank_id,
                        branch_name:this.bank_form.branch_name,
                        card_number: this.bank_form.card_number,
                        card_number_confirm: this.bank_form.card_number_confirm,
                        holder_name: this.bank_form.holder_name,
                        channel_name: this.bank_form.holder_name,
                        
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status !== 1) {
                            this.$message.error(res.msg);
                        } else {
                            this.$message.success(res.msg);
                            this.cancelDeal();
                            this.getData();
                        }
                    });
            },

            swtichBankChannel(action, value, id) {
                this.loading = true;
                this.$http
                    .post("/trade/switch_bank_payment_channel", {
                        action: action,
                        value: value,
                        id: id
                    })
                .then(res => {
                    console.log(res.data);
                    if (res.status == 1) {

                        this.getData();
                    } else {
                        this.$message.error(res.msg);
                        this.getData();
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