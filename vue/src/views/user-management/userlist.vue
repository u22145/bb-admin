<template>
    <div class="userlist">
        <div class="whiteBg">
            <div class="screen">
                <label for="selectUserID">用户ID</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="selectUserID"
                        v-model="uid"
                        @keyup.enter.native="seach"
                ></el-input>
                <label for="nickname">昵称</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="nickname"
                        v-model="nickname"
                        @keyup.enter.native="seach"
                ></el-input>
                <label for="nickname">手机</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="mobile"
                        v-model="mobile"
                        @keyup.enter.native="seach"
                ></el-input>
                <label for="nickname">{{$t("user_management.advert_name")}}</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="advert_id"
                        v-model="advert_id"
                        @keyup.enter.native="seach"
                ></el-input>
                <label style="margin-left: 20px">用户性别</label>&nbsp;
                <el-select placeholder v-model="gender" @keyup.enter.native="seach">
                    <el-option :label='$t("common.all")' value="0"></el-option>
                    <el-option :label='$t("resources.male")' value="2"></el-option>
                    <el-option :label='$t("resources.female")' value="1"></el-option>
                </el-select>
            </div>
            <div class="screen">
                <label>状态</label>&emsp;
                <el-select placeholder v-model="ustatus" @keyup.enter.native="seach">
                    <el-option :label='$t("common.all")' value="0"></el-option>
                    <el-option :label='$t("LabourUnion.normal")' value="1"></el-option>
                    <el-option :label='$t("user_management.Banning2")' value="2"></el-option>
                </el-select>
                <label style="margin-left: 20px">国籍</label>&emsp;
                <el-select placeholder v-model="country" filterable>
                    <el-option :label='$t("common.all")' value="0"></el-option>
                    <el-option
                            v-for="item in countryList"
                            :key="item.id"
                            :label="item.country"
                            :value="item.id"
                    ></el-option>
                </el-select>
                <label style="margin-left: 20px">会员</label>&nbsp;
                <el-select placeholder v-model="isvip" @keyup.enter.native="seach">
                    <el-option :label='$t("common.all")' value="0"></el-option>
                    <el-option :label='$t("money.yes")' value="1"></el-option>
                    <el-option :label='$t("money.no")' value="2"></el-option>
                </el-select>

                <label>注册时间</label>&nbsp;
                <el-date-picker
                        v-model="selecttime"
                        @change="handleChange"
                        type="datetimerange"
                        :range-separator='$t("common.to")'
                        start-placeholder='开始时间'
                        end-placeholder='结束时间'
                        value-format="yyyy-MM-dd HH:mm:ss"
                ></el-date-picker>
                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="seach()">查询</el-button>
            </div>
        </div>
        <div class="whiteBg">
            <div class="fr">
                <el-button @click="exportUsers(null, 0)">全部导出</el-button>
                <el-button @click="clear">清空查询</el-button>
                <!-- <el-button @click="getData(2)">导出</el-button> -->
            </div>
            <div class="tableCon">
                <el-table
                        ref="multipleTable"
                        :data="itemData"
                        v-loading="loading"
                        border
                        @sort-change="changeSort"
                        style="width: 99%;margin-top: 30px"
                >
                    <!-- <el-table-column label="序号" align="center" type="index" width="50">
                      <template slot-scope="scope">
                        <span>{{scope.$index+(page - 1) * limit + 1}}</span>
                      </template>
                    </el-table-column>-->
                    <el-table-column label="用户ID" align="center" fixed prop="id"></el-table-column>
                    <el-table-column label='昵称' align="center" fixed
                                     prop="nickname"></el-table-column>
                    <el-table-column label='图片' prop="avatar" align="center">
                        <template slot-scope="scope">
                            <img :src="scope.row.avatar" alt width="40" height="40" class="head_pic"/>
                        </template>
                    </el-table-column>
                    <el-table-column label='用户性别' align="center"
                                     prop="gender"></el-table-column>
                    <el-table-column label='年龄' align="center" prop="age"></el-table-column>
                    <el-table-column label='国籍' align="center"
                                     prop="country"></el-table-column>
                    <el-table-column label='手机' align="center" prop="mobile" width="150"></el-table-column>
                    <el-table-column label='会员' align="center" prop="vip">
                        <template slot-scope="scope">
                            <span v-if="scope.row.vip=='NO'" style="color:red">{{$t("money.no")}}</span>
                            <span v-if="scope.row.vip=='YES'" style="color:green">{{$t("money.yes")}}</span>
                        </template>
                    </el-table-column>
                    <!-- sortable
                         :sort-orders="['ascending', 'descending']"
                         :sort-by="['prop']" -->
                    <el-table-column label='baby币' align="center"
                                     sortable="eurc_balance"
                                     min-width="100px"
                                     prop="eurc_balance"></el-table-column>
                    <el-table-column :label='$t("user_management.advert_name")' align="center"
                                     sortable="advert_id"
                                     min-width="110px"
                                     prop="advert_id"></el-table-column>
                    <el-table-column :label='$t("user_management.pay_total")' align="center"
                                     min-width="110px"
                                     sortable="pay_total"
                                     prop="pay_total"></el-table-column>
                    <el-table-column label='累计提现金额' align="center"
                                     sortable="withdrawal_total"
                                     min-width="130px"
                                     prop="withdrawal_total"></el-table-column>
                    <el-table-column :label='$t("user_management.upper_name")'
                                     sortable="upper_uid"
                                     align="center"
                                     min-width="100px"
                                     prop="upper_uid"></el-table-column>
                    <el-table-column label='推广员' align="center"
                                     sortable="sm_uid"
                                     min-width="100px"
                                     prop="sm_uid"></el-table-column>
                    <el-table-column :label='$t("user_management.Registrationtime")'
                                     sortable="join_date"
                                    align="center" prop="join_date"
                                     min-width="180px"></el-table-column>
                    <el-table-column :label='$t("common.status")' align="center" prop="status">
                        <template slot-scope="scope">
                            <span v-if="scope.row.status=='OFF'" style="color:red">{{$t("jurisdiction.Discontinue_use")}}</span>
                            <span v-if="scope.row.status=='ON'" style="color:green">{{$t("jurisdiction.Enable")}}</span>
                        </template>
                    </el-table-column>
                    <el-table-column :label='$t("common.operation")' fixed="right" align="center" min-width="180px"
                                     class-name="operation">
                        <template slot-scope="scope">
                            <el-button type="text" size="small" @click="seeUserInfo(scope.row)">查看</el-button>
                            <el-button
                                    v-if="scope.row.status == 'ON'"
                                    type="text"
                                    size="small"
                                    @click="passNouser(scope.row)"
                            >{{$t("user_management.Banning2")}}
                            </el-button>
                            <el-button
                                    v-if="scope.row.status == 'OFF'"
                                    type="text"
                                    size="small"
                                    @click="uncommitPass(scope.row)"
                            >{{$t("user_management.Unsealing")}}
                            </el-button>
                            <el-button v-if="scope.row.is_fake == '0'" type="text" size="small"
                                       @click="fakeUser(scope.row)">測試
                            </el-button>
                            <el-button type="text" size="small" @click="handleDealClick(scope.row)">加扣款</el-button>

                        </template>
                    </el-table-column>
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
        <!-- 弹窗 -->
        <el-dialog :visible.sync="dialogFormVisible" width="40%">
            <el-form :model="form">
                <el-form-item :label='$t("user_management.Banningreason")' :label-width="formLabelWidth">
                    <el-input type="textarea" v-model="form.reasons" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item :label='$t("user_management.prohibition")' :label-width="formLabelWidth">
                    <el-select v-model="form.region">
                        <el-option :label='$t("user_management.Banning")' value="1"></el-option>
                        <el-option :label='$t("user_management.Banning1")' value="2"></el-option>
                    </el-select>
                </el-form-item>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
                <el-button type="primary" @click="commitPass">{{$t("confirm.ok")}}</el-button>
            </div>
        </el-dialog>
        <!-- 用户详情 -->
        <Userdetails :status="status" :id="id" v-on:changeStatus="changeStatus"/>

        <!--手动扣钱和分派奖金-->
        <el-dialog title="加扣款" custom-class="add_desc_coin_class" width="30%" center :show-close="true"
                   :visible.sync="transDialogFormVisible" @close="cancerDeal">

            <el-divider></el-divider>

            <el-row :gutter="20" style="margin-left: 0.5rem;">
                <el-col :span="12">
                    <div style="font-size: 1.1rem;">
                        用户ID:{{trans_record_row.id}}
                    </div>
                </el-col>
                <el-col :span="12">
                    <div style="font-size: 1.1rem;margin-top: 1rem;">
                        用户昵称:{{trans_record_row.nickname}}
                    </div>
                </el-col>
                <el-col :span="24">
                    <div style="font-size: 1.1rem;margin-top: 1rem;">
                        当前Baby币:{{trans_record_row.eurc_balance}}
                    </div>
                </el-col>
            </el-row>

            <el-divider></el-divider>

            <el-form :model="transform" style="" :label-width="formLabelWidth1">
                <el-row :gutter="20">
                    <el-col :span="16">
                        <el-form-item label="加扣款金额:">
                            <el-input @change="checkInput" style="width:100%;" type="number" min="0" step="1"
                                      v-model="transform.amount"
                                      autocomplete="off"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="22">
                        <el-form-item label="加扣款备注:">
                            <el-input
                                    style="width:100%;"
                                    type="textarea"
                                    placeholder="请输入内容"
                                    v-model="transform.memo"
                                    :rows="4"
                                    resize="none"
                            ></el-input>
                        </el-form-item>
                    </el-col>
                </el-row>

            </el-form>

            <el-divider></el-divider>

            <div slot="footer" class="dialog-footer">
                <el-button @click="subDeal(1)">扣款</el-button>
                <el-button @click="subDeal(2)">加款</el-button>
            </div>
        </el-dialog>

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
                itemData: [],
                countryList: [],
                export: 0,
                dialogFormVisible: false,
                form: {
                    reasons: "",
                    region: ""
                },
                formLabelWidth: "120px",
                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 1,
                selecttime: [],
                time_from: '',
                time_to: '', 
                uid: "",
                nickname: "",
                advert_id: '',
                mobile: '',
                is_remcommand: "",
                gender: "",
                ustatus: "",
                country: "",
                isvip: "",
                status: false,
                id: "",
                passid: 0,
                pickerOptions0: {
                    disabledDate(time) {
                        return time.getTime() < Date.now() - 8.64e7;
                    }
                },

                //排序字段
                order_filed_obj: null,
                order_filed_arr: [null],

                sort: '',
                attrib: '',

                //手动加扣款，派发彩金
                transDialogFormVisible: false,
                trans_record_row: {}, //记录要操作的信息
                formLabelWidth1: "100px",
                transform: {},
            };
        },

        mounted() {
            this.getCountryList();
            this.getData();
        },
        methods: {

            /**
             * 排序
             */
            // changeSort(val) {
            //     console.log('排序字段');
            //     console.log(val);
            //     if (val.order) {
            //         this.order_filed_obj = {
            //             prop: val.prop,
            //             order: val.order
            //         };
            //     } else {
            //         this.order_filed_obj = null;
            //     }

            //     console.log(this.order_filed_obj);
            //     this.getData();
            // },


            clear() {
                this.uid = "";
                this.nickname = "";
                this.gender = "";
                this.ustatus = "";
                this.country = "";
                this.isvip = "";
                this.selecttime = [];
                this.advert_id= '',
                this.sort = '',
                this.attrib = '',

                this.page = 1;
                this.getData();
            },
            handleChange() {
                this.time_from = this.selecttime[0].toString();
                this.time_to = this.selecttime[1].toString();
            },
            seeUserInfo(row) {
                this.id = row.id;
                this.status = true;
            },
            changeStatus(data) {
                this.status = data;
            },

            fakeUser(row) {
                this.$http
                    .post("/user/fake", {
                        uid: row.id
                    })
                    .then(res => {
                        this.$message({
                            message: "操作成功",
                            type: "success"
                        });
                        this.getData(1);
                    });
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
            //修改用户状态 推荐
            editUserInfo(row) {
                this.$http
                    .post("/user/prompt", {
                        uid: row.id
                    })
                    .then(res => {
                        this.$message({
                            message: "推荐成功",
                            type: "success"
                        });
                    });
            },
            // 获取国家列表
            getCountryList() {
                this.$http.post("/other/get_country", {}).then(res => {
                    this.countryList = res.data || [];
                });
            },
            //封禁弹窗
            passNouser(row) {
                this.passid = row.id;
                this.dialogFormVisible = true;
            },
            //封禁请求
            commitPass() {
                this.$http
                    .post("/user/block", {
                        uid: this.passid,
                        comm: this.form.reasons,
                        region: this.form.region,
                        renew: this.form.renew
                    })
                    .then(res => {
                        if (res.status == 1) {
                            this.$message({
                                message: res.msg,
                                type: "success"
                            });
                        } else {
                            this.$message({
                                message: res.msg,
                                type: "error"
                            });
                        }
                        this.getData(1);
                        this.dialogFormVisible = false;
                    });
            },
            //解封请求
            uncommitPass(row) {
                this.$http
                    .post("/user/block", {
                        uid: row.id,
                        region: 0
                    })
                    .then(res => {
                        this.$message({
                            message: res.msg,
                            type: "success"
                        });
                        this.getData();
                    });
            },
            //请求列表
            getData(type = 1, sort = '', attrib='') {
                this.loading = true;
                this.$http
                    .post("/user/index", {
                        page: this.page,
                        uid: this.uid,
                        nickname: this.nickname,
                        advert_id: this.advert_id,
                        mobile: this.mobile,
                        gender: this.gender,
                        status: this.status,
                        country: this.country,
                        is_remcommand: this.is_remcommand,
                        type: type,
                        isvip: this.isvip,
                        ustatus: this.ustatus,
                        // order: this.order_filed_obj ? JSON.stringify(this.order_filed_obj) : '',
                        join_date_start: this.selecttime[0],
                        join_date_end: this.selecttime[1],
                        sort: ('' == sort) ? this.sort : sort,
                        attrib: ('' == attrib) ? this.attrib : attrib,
                    })
                    .then(res => {
                        this.loading = false;
                        if (type === 1) {
                            this.itemData = res.data.list;
                            this.page = res.data.page;
                            this.total = res.data.total;
                        } else {
                            if (res.status === 1) {
                                window.open(`${window.location.origin}${res.data}`);
                            } else {
                                this.$message.error(res.msg);
                            }
                        }
                    });
            },
            // 导出用户
            exportUsers(filename=null, offset=0) {
                this.loading = true;
                if( offset === 0) 
                    alert('由于下载量大，下载会在后台运行，约持续数分钟，期间请选择休息一下，不要再浏览数据');
                this.$http
                    .post("/user/export_users", {
                        filename: filename,
                        offset: offset,

                        uid: this.uid,
                        nickname: this.nickname,
                        advert_id: this.advert_id,
                        mobile: this.mobile,
                        gender: this.gender,
                        status: this.status,
                        country: this.country,
                        is_remcommand: this.is_remcommand,
                        isvip: this.isvip,
                        ustatus: this.ustatus,
                        
                        join_date_start: this.time_from,
                        join_date_end: this.time_to,
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status === 1) {
                                if(false === res.data.end ) {
                                    this.exportUsers(res.data.filename, res.data.offset);
                                } else window.open(`${window.location.origin}${res.data.filename}`);
                            } else {
                                this.$message.error(res.msg);
                            }
                    });
            },
            changeSort (val) {
                // console.log(val) // column: {…} order: "ascending" prop: "date"
                // 根据当前排序重新获取后台数据,一般后台会需要一个排序的参数
                this.attrib = val.prop;
                this.sort = val.order;
                this.getData(1, val.order, val.prop);
            },
            /**
             * 手动金额转移处理
             * @param row
             */
            handleDealClick(row) {
                this.trans_record_row = row;
                this.transDialogFormVisible = true;
            },

            /**
             * 检测数据
             */
            checkInput(val) {
                if (parseFloat(val) <= 0) {
                    this.transform.amount = 0;
                    this.$forceUpdate();
                    this.$message.error('输入数据不能为负数');
                } else {
                    this.isRealNum(val);
                }
            },


            /**
             * 判断是否是数字
             */
            isRealNum(val) {

                if (val === "" || val == null) {
                    this.transform.amount = 0;
                    this.$forceUpdate();
                    this.$message.error('输入数据只能是数字');
                    return false;
                }
                if (!isNaN(val)) {
                    return true;
                } else {
                    this.transform.amount = 0;
                    this.$forceUpdate();
                    this.$message.error('输入数据只能是数字');
                    return false;
                }
            },


            /**
             * 处理手动加|扣金额转移情况
             * @param type 1：扣钱，2：加钱
             */
            subDeal(type) {

                // console.log(this.transform);
                switch (type) {
                    case 1: //扣钱
                        if (!this.transform.memo || !this.transform.amount) {
                            this.$message.error('请输入完整信息');
                            return false;
                        }
                        this.isRealNum(this.transform.amount);
                        if (parseInt(this.transform.amount) <= 0) {
                            this.$alert('扣款数不能为负数', '温馨提示', {
                                cancelButtonText: '确定',
                                center: true,
                            });
                            return false;
                        }

                        if ((parseFloat(this.transform.amount) - parseFloat(this.trans_record_row.eurc_balance)) > 0) {

                            this.$alert('用户当前Baby币不可为负数', '温馨提示', {
                                cancelButtonText: '确定',
                                center: true,
                            });
                            return false;
                        }
                        this.$confirm('是否确定对用户进行用户列表操作！', '温馨提示', {
                            confirmButtonText: '确定',
                            cancelButtonText: '取消',
                            type: 'warning',
                            center: true
                        }).then(() => {
                            this.transform.type = type;
                            this.subRquestInfo();
                        }).catch(() => {
                            this.$message({
                                type: 'info',
                                message: '已取消该操作'
                            });
                        });

                        break;
                    case 2: //加钱
                        if (!this.transform.memo || !this.transform.amount) {
                            this.$message.error('请输入完整信息');
                            return false;
                        }
                        this.isRealNum(this.transform.amount);
                        if (parseInt(this.transform.amount) <= 0) {
                            this.$alert('加款不能为负数', '温馨提示', {
                                cancelButtonText: '确定',
                                center: true,
                            });
                            return false;
                        }

                        this.$confirm('是否确定对用户进行用户列表操作！', '温馨提示', {
                            confirmButtonText: '确定',
                            cancelButtonText: '取消',
                            type: 'warning',
                            center: true
                        }).then(() => {
                            this.transform.type = type;
                            this.subRquestInfo();
                        }).catch(() => {
                            this.$message({
                                type: 'info',
                                message: '已取消该操作'
                            });
                        });
                        break;
                    default:
                        this.$message.error('请输入完整信息');
                        return false;
                        break;
                }

            },

            /**
             * 提交添扣款网络请求
             */
            subRquestInfo() {
                const loading = this.$loading({
                    lock: true,
                    text: 'Loading',
                    spinner: 'el-icon-loading',
                    background: 'rgba(0, 0, 0, 0.4)'
                });
                this.$http
                    .post("user/trans_money_deal_info", {
                        uid: this.trans_record_row.id,
                        info: JSON.stringify(this.transform),
                    })
                    .then(res => {
                        loading.close();
                        if (res.status !== 1) {
                            this.$message.error(res.msg);
                        } else {
                            this.$message.success(res.msg);
                            this.cancerDeal();
                            this.getData();
                        }

                        console.log(res)
                    });
            },

            /**
             * 取消弹框
             */
            cancerDeal() {
                this.transform = {};
                this.transDialogFormVisible = false;
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



