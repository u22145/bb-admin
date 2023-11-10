<template>
    <div class="vipcollocation" v-loading.fullscreen.lock="loading">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">ID或昵称</label>&nbsp;
                <el-input clearable style="width: 200px" v-model="search_value"></el-input>
                <label style="margin-left: 20px">工会名称</label>&nbsp;
                <el-input clearable style="width: 200px" v-model="search_value2"></el-input>
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
            <div class="screen" style="width: 100%">
                <template>
                    <el-table
                            :data="tableData"
                            border
                            style="width: 100%">
                        <el-table-column
                                fixed
                                prop="sociaty_name"
                                label="所属工会"
                                align="center"
                                width="150">
                        </el-table-column>
                        <el-table-column
                                prop="uid"
                                label="主播ID"
                                align="center"
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="nickname"
                                label="主播昵称"
                                align="center"
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="call_people_num"
                                label="视频人次"
                                align="center"
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="call_times"
                                align="center"
                                label="视频时长(分钟)">
                        </el-table-column>
                        <el-table-column
                                prop="gift_money"
                                align="center"
                                label="礼物收益">
                        </el-table-column>
                        <el-table-column
                                prop="video_money"
                                align="center"
                                label="视频收益(BABY)">
                        </el-table-column>
                        <el-table-column
                                prop="total_money"
                                align="center"
                                label="总收益(BABY)">
                        </el-table-column>
                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="200">
                            <template slot-scope="scope">
                                <el-button @click="handleViewClick(scope.row)" type="text" size="small">查看</el-button>
                                <el-button @click="stopPlay(1,scope.row.id)" v-if="parseInt(scope.row.is_deleted)!==2"
                                           type="text"
                                           size="small">禁播
                                </el-button>
                                <el-button @click="stopPlay(2,scope.row.id)" v-else type="text" size="small">取消禁播
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </template>
                <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
                    <el-pagination
                            @current-change="handleCurrentChange"
                            :current-page="page"
                            background
                            :page-sizes="[200]"
                            :page-size="limit"
                            layout="total, sizes, prev, pager, next, jumper"
                            :total="total"
                            class="fr"
                    ></el-pagination>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "chatMoneyList",

        data() {
            return {
                loading: false,
                search_value: '',
                search_value2: '',
                limit: 200,
                page: 1,
                total: 0,

                tableData: []
            }
        },
        mounted() {
            this.page = 1;
            this.tableData = [];
            this.getData();
        },


        watch: {
            tableData(newVal) {
                this.$forceUpdate();
            }
        },


        methods: {

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
            getData() {
                this.loading = true;
                this.$http
                    .post("chat/chat_money_list", {
                        page: this.page,
                        content: this.search_value,
                        team: this.search_value2,
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status !== 1) {
                            this.$message.error('获取数据错误')
                        } else {
                            this.total = res.data.total;
                            this.tableData = res.data.info;
                        }

                        console.log(res)
                    });
            },


            /**
             * 查看
             */
            handleViewClick(row) {
                console.log(row);
                this.$router.push({
                    path: '/chat_detail_list',
                    query: {
                        id: row.uid
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
             * 查看总的收益详情
             * @param uid
             */
            showTotalRecord(uid) {
                console.log('查看总收益')
            },


            /**
             * 禁播
             * @param type
             * @param id
             */
            stopPlay(type, id) {
                this.$confirm("确认需要进行该操作吗？", "提示", {
                    confirmButtonText: "确定",
                    cancelButtonText: "取消",
                    type: "warning"
                })
                    .then(() => {
                        this.$http
                            .post("chat/stop_start_chat", {
                                type: type,
                                id: id
                            })
                            .then(res => {
                                this.$message({
                                    type: res.status === 1 ? "success" : "error",
                                    message: res.msg
                                });
                                if (res.status === 1) {
                                    this.getData();
                                }
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