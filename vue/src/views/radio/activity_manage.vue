<template>
    <div>
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">发布用户ID</label>&nbsp;
                <el-input clearable style="width: 200px" v-model="uid"></el-input>
                <label style="margin-left: 20px">报名者ID</label>&nbsp;
                <el-input clearable style="width: 200px" v-model="active_uid"></el-input>

                <label style="margin-left: 25px">活动时间</label>&nbsp;
                <el-date-picker
                        style="width: 300px"
                        v-model="active_time"
                        type="datetimerange"
                        format="yyyy-MM-dd"
                        value-format="timestamp"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期">
                </el-date-picker>
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
            <div class="tableCon">


                <el-table
                        ref="multipleTable"
                        :data="tableData"
                        v-loading="loading"
                        border
                        style="width: 100%;margin-top: 20px"
                >
                    <el-table-column label="电台ID" align="center" width="80" fixed prop="rrd_id"></el-table-column>
                    <el-table-column label="发布用户ID" align="center" width="80" fixed prop="u_id"></el-table-column>
                    <el-table-column label="发布用户昵称" align="center" width="100" fixed prop="nickname"></el-table-column>
                    <el-table-column label="活动类型" align="center" width="150" prop="act_ids"></el-table-column>
                    <el-table-column label="城市" align="center" prop="city"></el-table-column>
                    <el-table-column label="具体地址" align="center" width="150" prop="place"></el-table-column>
                    <el-table-column label="活动金额" align="center" prop="amount"></el-table-column>
                    <el-table-column label="活动时间" align="center" width="120" prop="act_time"></el-table-column>
                    <el-table-column label="期望类型" align="center" width="150" prop="exp_ids"></el-table-column>
                    <el-table-column label="备注" align="center" prop="notice"></el-table-column>
                    <el-table-column label="用户是否点击结束活动" align="center" fixed="right"
                                     prop="is_active_end"></el-table-column>
                    <el-table-column label="开始活动的用户ID" align="center" fixed="right" prop="active_uid"></el-table-column>
                    <el-table-column label="开始活动的用户昵称" align="center" fixed="right"
                                     prop="active_nickname"></el-table-column>
                    <el-table-column label="是否距离活动时间已到两天" align="center" width="80" fixed="right"
                                     prop="status_txt"></el-table-column>
                    <el-table-column label="结束活动时间" align="center" width="120" fixed="right"
                                     prop="end_time"></el-table-column>
                    <el-table-column
                            label="操作"
                            align="center"
                            fixed="right"
                            width="160px"
                            class-name="operation"
                    >
                        <template slot-scope="scope">
                            <el-button type="text" size="small" @click="showDeatil(scope.row)">详情</el-button>
                            <div v-if="!parseInt(scope.row.is_deal)">
                                <el-button type="text" size="small"
                                           @click="payMoneyToOne(scope.row,id,scope.row.amount,scope.row.u_id)">
                                    活动金额给发布者
                                </el-button>
                                <el-button type="text" size="small"
                                           @click="payMoneyToOne(scope.row.id,scope.row.amount,scope.row.active_uid)">
                                    活动金额给报名者
                                </el-button>
                            </div>


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
        name: "activity_manage",

        data() {
            return {
                uid: '',
                active_uid: '',
                active_time: '',

                tableData: [],
                loading: false,

                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 0,
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
                    .post("radio/activity_list", {
                        page: this.page,
                        page_size: this.limit,
                        r_id: this.uid,
                        a_id: this.active_uid,
                        active_time: this.active_time
                    })
                    .then(res => {
                        this.loading = false;

                        this.tableData = res.data.list;
                        this.page = res.data.page;
                        this.total = res.data.total;
                    });
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
             * 进行搜索
             */
            search() {
                this.getData();
            },


            /**
             * 查看活动的详情，用户判断将金额转给谁
             */
            showDeatil() {
                this.$message({
                    type: "error",
                    message: '该查看聊天记录功能未接入'
                })

            },

            /**
             * 支付金额给某一方
             * @param id
             * @param amount
             * @param uid
             */
            payMoneyToOne(id, amount, uid) {

                this.$confirm('此操作将进行金钱转移操作, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.loading = true;

                    this.$http
                        .post("/radio/trans_baby", {
                            id: id,
                            amount: amount,
                            uid: uid
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
                        message: '已取消操作'
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