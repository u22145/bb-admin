<template>
    <div class="vipcollocation">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">合作商ID</label>&nbsp;
                <el-input clearable style="width: 200px" v-model="id"></el-input>
                <label style="margin-left: 20px">合作商名稱</label>&nbsp;
                <el-input clearable style="width: 200px" v-model="name"></el-input>
                <label style="margin-left: 20px">狀態</label>&nbsp;
                <el-select placeholder="请选择" clearable v-model="status">
                    <el-option label="全部" value="all"></el-option>
                    <el-option label="启用" value="off"></el-option>
                    <el-option label="停用" value="on"></el-option>
                </el-select>

                <label style="margin-left: 20px">收款方式</label>&nbsp;
                <el-select placeholder="请选择" clearable v-model="collect_type">
                    <el-option
                            v-for="item in collect_money_type_info"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                    </el-option>
                </el-select>

                <el-button
                        type="primary"
                        class="fr"
                        style="padding: 8px 30px"
                        @click="search"
                >搜索
                </el-button>
            </div>
        </div>
        <div class="whiteBg">
            <div class="fr">
                <el-button @click="createAppkey" style="background-color:rgb(70,154,208);color:#fff">创建</el-button>
            </div>
            <div class="tableCon">
                <el-table
                        ref="multipleTable"
                        :data="tableData"
                        v-loading="loading"
                        border
                        style="width: 100%;margin-top: 30px"
                >
                    <el-table-column label="ID" align="center" prop="id"></el-table-column>
                    <el-table-column label="上级ID" align="center" prop="pid"></el-table-column>
                    <el-table-column label="名称" align="center" prop="name"></el-table-column>
                    <el-table-column label="收款方式" align="center" prop="collect_type_address"></el-table-column>
                    <el-table-column label="链接" align="center" prop="url"></el-table-column>
                    <el-table-column label="分成比例" align="center" prop="share_rate_txt"></el-table-column>
                    <el-table-column label="状态" align="center" prop="status_txt"></el-table-column>
                    <el-table-column label="创建日期" align="center" prop="uptime"></el-table-column>
                    <el-table-column
                            :label="$t('common.operation')"
                            align="center"
                            width="120px"
                            class-name="operation"
                    >
                        <template slot-scope="scope">
                            <el-button type="text" size="small" @click="watchInfo(scope.row)">查看</el-button>
                            <el-button
                                    type="text"
                                    size="small"
                                    v-if="scope.row.status == 1"
                                    @click="saveStatus(scope.row.id,scope.row.status)"
                            ><span style="color: red;">
                                停用
                            </span>
                            </el-button>
                            <el-button
                                    type="text"
                                    size="small"
                                    v-if="scope.row.status == 0"
                                    @click="saveStatus(scope.row.id,scope.row.status)"
                            ><span style="color: green;">
                                启用
                            </span>
                            </el-button>
                            <el-button type="text" size="small" @click="saveInfo(scope.row)">修改
                            </el-button>
                            <el-button
                                    type="text"
                                    size="small"
                                    @click="deleteSen(scope.row.id)"
                            ><span style="color: red;">
                                删除
                            </span>
                            </el-button>
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
                        @size-change="sizeChange"
                ></el-pagination>
            </div>
        </div>
        <!-- 创建授权弹窗 -->
        <el-dialog :visible.sync="dialogFormVisible">
            <el-form :model="form">
                <el-form-item label="合作商名称" :label-width="formLabelWidth">
                    <el-input v-model="form.name" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="上级ID" :label-width="formLabelWidth">
                    <el-input v-if="parseInt(form.id)>=0" v-model="form.pid" disabled type="text"
                              autocomplete="off"></el-input>
                    <el-input v-else v-model="form.pid" type="text" placeholder="可不填，填写后不可修改"
                              autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="登录密码" :label-width="formLabelWidth">
                    <el-input v-model="form.password" type="text" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="分成比例" :label-width="formLabelWidth">
                    <el-input @change="getRateValue" v-model="form.share_rate" type="text"
                              autocomplete="off"></el-input>
                </el-form-item>

                <el-form-item label="请选择收款方式" :label-width="formLabelWidth">
                    <el-select v-model="form.collect_money_type" clearable placeholder="请选择">
                        <el-option
                                v-for="item in collect_money_type_info"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="收款地址" :label-width="formLabelWidth">
                    <el-input v-model="form.collect_money_addr" type="text" autocomplete="off"></el-input>
                </el-form-item>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="createPost">确认</el-button>
                <el-button @click="dialogFormVisible = false">取消</el-button>
            </div>
        </el-dialog>

        <el-dialog :visible.sync="watchForm">
            <el-form :model="form">
                <el-row>
                    <el-col :span="12">
                        <el-form-item label="合作商名称" :label-width="formLabelWidth">
                            {{form.name}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="上级ID" :label-width="formLabelWidth">
                            {{form.pid}}
                        </el-form-item>
                    </el-col>

                    <el-col :span="12">
                        <el-form-item label="推广二维码" :label-width="formLabelWidth">
                            <img :src="form.qrcode" width="100" height="100" class="head_pic"/>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="推广链接" :label-width="formLabelWidth">
                            {{form.url}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="登录链接" :label-width="formLabelWidth">
                            {{form.login_url}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="登录账号" :label-width="formLabelWidth">
                            {{form.id}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="登录密码" :label-width="formLabelWidth">
                            {{form.password}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="分成比例" :label-width="formLabelWidth">
                            {{form.share_rate}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="请选择收款方式" :label-width="formLabelWidth">
                            {{form.collect_money_type_info}}
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="收款地址" :label-width="formLabelWidth">
                            {{form.collect_money_addr}}
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
        </el-dialog>
    </div>
</template>
<script>
    export default {
        data() {
            return {
                loading: false,
                tableData: [], //table表数据
                dialogFormVisible: false,
                watchForm: false,
                form: {},
                name: "",
                status: "all",
                collect_type: "", //收款方式
                formLabelWidth: "120px",
                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 1,
                id: '',
                //收款方式集合
                collect_money_type_info: [{
                    value: '1',
                    label: 'BABY钱包地址'
                }, {
                    value: '2',
                    label: '支付宝'
                }, {
                    value: '3',
                    label: '微信'
                }, {
                    value: '4',
                    label: '银行卡'
                }],

                //检测字段
                check_flag: false,
            }
                ;
        },
        created() {
            this.getData();
        },
        computed: {
            types() {
                return {
                    type: "partner",
                    usercode: window.localStorage.getItem("usercode")
                };
            }
        },
        methods: {

            /**
             * 删除信息
             * @param id
             */
            deleteSen(id) {
                this.$confirm("刪除操作不可以恢复，是否继续？", "提示", {
                    confirmButtonText: "确定",
                    cancelButtonText: "取消",
                    type: "warning"
                })
                    .then(() => {
                        this.$http
                            .post("partner/del_partner", {
                                id: id
                            })
                            .then(res => {
                                this.$message({
                                    type: res.status == 1 ? "success" : "error",
                                    message: res.msg
                                });
                                this.getData();
                            });
                    })
                    .catch(() => {
                        this.$message({
                            type: "info",
                            message: "已取消操作"
                        });
                    });
            },
            createAppkey() {
                this.dialogFormVisible = true;
            },

            /**
             * 输入比例之后，检测是否合适
             */
            getRateValue() {
                if (this.form.pid && this.form.pid !== 0 && this.form.pid !== '0') {
                    this.loading = true;
                    this.$http
                        .post("partner/check_rate", {
                            pid: this.form.pid,
                            rate: this.form.share_rate
                        })
                        .then(res => {
                            if (res.status !== 1) {
                                this.$message({
                                    type: "error",
                                    message: res.msg
                                });
                            } else {
                                this.check_flag = true;
                            }

                            this.loading = false;
                        });
                } else {
                    this.check_flag = true;
                }
            },

            createPost() {
                if (this.form.pid === undefined) {
                    this.form.pid = 0;
                }

                this.getRateValue();

                if (!this.check_flag) {
                    return false;
                }

                if (!this.form.name || !this.form.password || !this.form.share_rate || !this.form.collect_money_type_info || !this.form.collect_money_addr) {
                    this.$message({
                        type: 'error',
                        message: '请填写正确数据'
                    });
                    return false;
                }

                this.$http
                    .post("partner/create_partner", {
                        data: this.form
                    })
                    .then(res => {
                        this.$message({
                            type: res.status == 1 ? "success" : "error",
                            message: res.msg
                        });
                        this.dialogFormVisible = false;
                        this.getData();
                    });
            },
            /**
             * 查询指定数据
             */
            search() {
                this.getData();
            },

            /**
             * 获取数据
             */
            getData(exp = 1) {
                this.loading = true;
                this.$http
                    .post("partner/partner_list", {
                        page: this.page,
                        page_size: this.limit,
                        name: this.name,
                        id: this.id,
                        status: this.status,
                        collect_type: this.collect_type,
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

            /**
             * 修改数据
             */
            saveInfo(info) {
                this.form = info;
                this.dialogFormVisible = true;
            },

            /**
             * 查看
             */
            watchInfo(info) {
                this.form = info;
                this.watchForm = true;
            },
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            sizeChange(val) {
                this.limit = val;
                this.getData(val);
            },


            /**
             * 启用或者停用
             * @param id
             * @param status
             */
            saveStatus(id, status) {
                var notice = '';
                if (parseInt(status) === 1) {
                    notice = '停用'
                } else {
                    notice = '启用'
                }
                this.$confirm("是否" + notice + "该条？", "提示", {
                    confirmButtonText: "确定",
                    cancelButtonText: "取消",
                    type: "warning"
                })
                    .then(() => {
                        this.$http
                            .post("partner/partner_status", {
                                id: id,
                                status: status
                            })
                            .then(res => {
                                this.$message({
                                    type: res.status == 1 ? "success" : "error",
                                    message: res.msg
                                });
                                this.getData();
                            });
                    })
                    .catch(() => {
                        this.$message({
                            type: "info",
                            message: "已取消操作"
                        });
                    });
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



                            