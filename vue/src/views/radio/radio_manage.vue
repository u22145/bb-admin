<template>
    <div>
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 25px">时间</label>&nbsp;
                <el-date-picker
                        style="width: 350px"
                        v-model="release_time"
                        type="datetimerange"
                        format="yyyy-MM-dd"
                        value-format="timestamp"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期">
                </el-date-picker>


                <label style="margin-left: 20px">发起人ID</label>&nbsp;
                <el-input clearable style="width: 150px" v-model="user_id"></el-input>


                <label style="margin-left: 20px">参与人ID</label>&nbsp;
                <el-input clearable style="width: 150px" v-model="user_id"></el-input>


                <label style="margin-left: 25px">状态</label>&nbsp;
                <el-select style="width: 150px" placeholder="请选择" clearable v-model="status">
                    <el-option label="全部" value="0"></el-option>
                    <el-option label="等待中" value="1"></el-option>
                    <el-option label="发布人取消" value="2"></el-option>
                    <el-option label="参与人取消" value="3"></el-option>
                    <el-option label="发起人违规" value="4"></el-option>
                    <el-option label="参与人违规" value="5"></el-option>
                    <el-option label="超时结束" value="6"></el-option>
                    <el-option label="顺利结束" value="7"></el-option>
                </el-select>


                <el-button
                        type="primary"
                        class="fr"
                        style="padding: 8px 30px;margin-left: 10px;"
                        @click="search"
                >搜索
                </el-button>

                <el-button
                        type="primary"
                        class="fr"
                        style="padding: 8px 30px"
                        @click="search"
                >重置
                </el-button>


            </div>
        </div>


        <div class="whiteBg">
            <div class="tableCon">
                <el-table
                        ref="multipleTable"
                        :data="tableData"
                        v-loading="loading"
                        border
                        style="width: 100%;margin-top: 20px"
                >
                    <el-table-column label="发布时间" align="center" width="150"
                                     prop="release_time"></el-table-column>
                    <el-table-column label="发起人ID" align="center" width="80" prop="u_id"></el-table-column>
                    <el-table-column label="活动类型" align="center" width="150" prop="act_ids"></el-table-column>
                    <el-table-column label="活动内容" align="center" prop="notice"></el-table-column>
                    <el-table-column label="活动金额" align="center" prop="amount" width="100"></el-table-column>
                    <el-table-column label="报名人数" align="center" prop="sign_num"></el-table-column>
                    <el-table-column label="参与人ID" align="center" prop="active_uid"></el-table-column>
                    <el-table-column label="支付方ID" align="center" prop="active_uid"></el-table-column>
                    <el-table-column label="状态" align="center" width="120"
                                     prop="status_txt"></el-table-column>
                    <el-table-column label="担保截止" align="center" width="150" prop="act_time"></el-table-column>
                    <el-table-column
                            label="操作"
                            align="center"
                            fixed="right"
                            width="100px"
                            class-name="operation"
                    >
                        <template slot-scope="scope">
                            <el-button
                                    type="text"
                                    size="small"
                                    @click="deleteInfo(scope.row.id)"
                            ><span>
                                删除
                            </span>
                            </el-button>
                            <el-button type="text" size="small" @click="radio_info(scope.row.id)">审核</el-button>

                            <el-button
                                    type="text"
                                    size="small"
                                    @click="radio_info(scope.row.id)"
                            >查看</el-button>

                        </template>
                    </el-table-column>
                </el-table>
                <div style="margin-bottom: 40px">
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



    </div>
</template>

<script>
    export default {
        name: "radio_manage",

        data() {
            return {
                user_id: '',
                release_time: '',
                active_time: '',
                status: '0',
                op_status: '0',

                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 0,

                loading: false,
                tableData: [],


                formLabelWidth: '120px',
                dialogStatusFormVisible: false, //审核弹框

                watchForm: false,

                form: {},

                status_form: {
                    id: '',
                    status: ''
                }, //弹框内容

            }
        },

        mounted() {
            this.getData();
        },

        methods: {


            /**
             * 获取数据
             */
            getData() {
                this.loading = true;
                this.$http
                    .post("radio/radio_list", {
                        page: this.page,
                        page_size: this.limit,
                        user_id: this.user_id,
                        release_time: this.release_time,
                        status: this.status,
                        active_time: this.active_time,
                        op_status: this.op_status
                    })
                    .then(res => {
                        this.loading = false;

                        this.tableData = res.data.list;
                        this.page = res.data.page;
                        this.total = res.data.total;
                    });
            },


            /**
             * 进行搜索
             */
            search() {
                this.getData();
            },


            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            sizeChange(val) {
                this.limit = val;
                this.getData();
            },


            /**
             * 查看留言列表
             */
            radio_info(id) {
                this.$router.push({
                    path: '/radio_info',
                    query: {
                        id: id
                    }
                });
            },


            /**
             * 删除指定的约会电台信息
             * @param id
             */
            deleteInfo(id) {
                this.$confirm('你确定要删除这行内容吗?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.loading = true;

                    this.$http
                        .post("/radio/delete_radio_info", {
                            id: id,
                        })
                        .then(res => {
                            this.loading = false;
                            if (res.status === 1) {
                                this.$message({
                                    type: "success",
                                    message: res.msg
                                });
                                this.getData();
                            } else {
                                this.$message({
                                    type: "error",
                                    message: res.msg
                                });
                            }

                        });
                }).catch(() => {
                    this.$message({
                        type: 'info',
                        message: '已取消删除'
                    });
                });
            }
        }

    }
</script>

<style lang="less" scoped>
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