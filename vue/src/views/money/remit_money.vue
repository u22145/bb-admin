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

                <label style="margin-left: 20px">审核人</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="op_uid"
                >
                    <el-option v-for="(item,index) in op_uid_arr" :key="index" :label='item.username'
                               :value="item.id"></el-option>
                </el-select>


                <label style="margin-left: 20px">手机号</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="phone"
                        v-model="phone"
                ></el-input>

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
                                prop="bank_type"
                                label="提款渠道"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="bank_info"
                                label="渠道账号"
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
                                prop="op_name"
                                label="审核人"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                label="审核结果"
                                align="center">
                            <template slot-scope="scope">
                                <div>
                                    <span v-if="parseInt(scope.row.status)===2">{{scope.row.status_txt}}</span>
                                    <span v-else style="color: red;">{{scope.row.status_txt}}</span>
                                </div>

                            </template>
                        </el-table-column>
                        <el-table-column
                                label="审核备注"
                                align="center">
                            <template slot-scope="scope">
                                <div>
                                    <span v-if="parseInt(scope.row.status)===2">{{scope.row.reason}}</span>
                                    <span v-else style="color: red;">{{scope.row.reason}}</span>
                                </div>

                            </template>
                        </el-table-column>

                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="150">
                            <template slot-scope="scope">
                                <el-button @click="handlePayClick(scope.row)" type="text" size="small">确认
                                </el-button>


                                <el-button @click="handleDealClick(scope.row,4)" type="text" size="small">
                                    <span style="color: red;">拒绝下发</span>
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

        <!--拒绝打款-->
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

        <!--打款处理-->
        <el-dialog title="打款处理" width="30%" center :show-close="false" :visible.sync="dialogFormVisiblePay">
            <el-form :model="pay_record_info">

                <!--                <el-form-item label="打款凭证" :label-width="formLabelWidth">-->
                <!--                    <el-upload-->
                <!--                            ref="upload"-->
                <!--                            action="/withdrawal/pay_deal"-->
                <!--                            list-type="picture-card"-->
                <!--                            :limit="pic_limit"-->
                <!--                            :disabled="disabled"-->
                <!--                            :on-preview="handlePictureCardPreview"-->
                <!--                            :on-exceed="handleExceedPic"-->
                <!--                            :on-success="fileSuccess"-->
                <!--                            :on-error="fileFail"-->
                <!--                            :on-change="handlefileChange"-->
                <!--                            :auto-upload="false"-->
                <!--                            :data="post_data"-->
                <!--                            :on-remove="handleRemove">-->
                <!--                        <i class="el-icon-plus"></i>-->
                <!--                    </el-upload>-->
                <!--                    <el-dialog :visible.sync="dialogVisible">-->
                <!--                        <img width="100%" :src="dialogImageUrl" alt="">-->
                <!--                    </el-dialog>-->
                <!--                </el-form-item>-->

                <el-form-item label="操作备注" :label-width="formLabelWidth">
                    <el-input
                            type="textarea"
                            :rows="5"
                            placeholder=""
                            v-model="pay_record_info.pay_content">
                    </el-input>
                </el-form-item>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="cancerPAy">取 消</el-button>
                <el-button type="primary" @click="subPay">确 定</el-button>
            </div>
        </el-dialog>


    </div>
</template>

<script>
    export default {
        name: "remit_money",

        data() {
            return {
                loading: false,
                usercode: localStorage.getItem('usercode'),

                status: '',
                uid: '',
                op_uid: '',
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
                //拒绝
                dialogFormVisible: false,
                record_row: {}, //记录要操作的信息
                form: {
                    status: '',
                    reason: ''
                },

                //打款处理
                dialogFormVisiblePay: false,
                pay_record_info: {},

                //上传图片
                pic_limit: 1,//限制张数
                disabled: false,
                dialogVisible: false,
                dialogImageUrl: '',
                post_data: {},


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
                    .post("withdrawal/remit_money", {
                        type: type,
                        page: this.page,
                        size: this.limit,
                        status: this.status,
                        uid: this.uid,
                        time: this.search_time,
                        op_uid: this.op_uid,
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
             * 拒绝处理
             * @param row
             * @param type
             */
            handleDealClick(row, type) {
                this.record_row = row;
                this.dialogFormVisible = true;
                this.form.status = type;
            },


            /**
             * 处理拒绝情况
             */
            subDeal() {

                if (!this.form.reason) {
                    this.$message.error("请输入操作备注说明");
                    return false;
                }
                this.loading = true;
                this.$http
                    .post("withdrawal/refuse_pay", {
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
             * 打款处理
             * @param row
             */
            handlePayClick(row) {
                this.pay_record_info = row;
                this.pay_record_info.status1 = '';
                this.dialogFormVisiblePay = true;
            },

            changePayStatus(val) {
                this.$forceUpdate();
            },


            /**
             * 确认打款
             */
            subPay() {
                if (!this.pay_record_info.pay_content) {
                    this.$message.error("请输入打款凭证补充");
                    return false;
                }

                this.post_data = {
                    usercode: this.usercode,
                    id: this.pay_record_info.id,
                    uid: this.pay_record_info.uid,
                    amount: this.pay_record_info.amount,
                    pay_content: this.pay_record_info.pay_content
                };

                // if (this.$refs.upload.uploadFiles.length) {
                //     setTimeout(() => {
                //         this.$refs.upload.submit();
                //     }, 300);
                // } else {
                this.loading = true;
                this.$http
                    .post("withdrawal/pay_deal", this.post_data)
                    .then(res => {
                        this.loading = false;
                        if (res.status === 1) {
                            this.$message({
                                type: "success",
                                message: res.msg
                            });
                            this.form = {
                                id: 0,
                                name: ''
                            };
                            this.dialogFormVisible = false;
                            this.cancerPAy();
                            this.getData();
                        } else {
                            this.$message({
                                type: "error",
                                message: res.msg
                            });
                        }
                    });
                // }

            },

            /**
             * 图片和信息上传成功
             */
            fileSuccess(response) {
                // console.log(response);
                if (response.status === 1) {
                    this.$message({
                        type: "success",
                        message: response.msg
                    });
                    this.form = {
                        id: 0,
                        name: ''
                    };
                    this.dialogFormVisible = false;
                    this.cancerPAy();
                    this.getData();
                } else {
                    this.$message({
                        type: "error",
                        message: response.msg
                    });
                }

            },

            /**
             * 上传失败
             */
            fileFail() {
                this.$message({
                    type: "error",
                    message: '新增失败'
                });
            },


            /**
             * 取消打款
             */
            cancerPAy() {
                this.pay_record_info = {};
                this.dialogFormVisiblePay = false;
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

            handlefileChange(file, fileList) {
                console.log(fileList)
                if (fileList.length === this.pic_limit) {
                    setTimeout(() => {
                        console.log(document.getElementsByClassName('el-upload')[0])
                        document.getElementsByClassName('el-upload')[0].classList.add('hidden')
                    }, 300)

                }
            },

            /**
             * 上传图片超过个数
             */
            handleExceedPic() {
                this.$message.error('最多只能上传' + this.pic_limit + '张打款凭证图片');
            },

            handleRemove(file, fileList) {
                setTimeout(() => {
                    console.log(document.getElementsByClassName('el-upload')[0])
                    document.getElementsByClassName('el-upload')[0].classList.remove('hidden')
                }, 300)
            },
            handlePictureCardPreview(file) {
                // console.log('方大')
                this.dialogImageUrl = file.url;
                this.dialogVisible = true;
            }
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