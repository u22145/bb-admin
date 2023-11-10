<template>
    <div class="vipcollocation">
        <div class="whiteBg">
            <div class="screen">
                <el-row>
                    <el-col :span="24">
                        <el-button @click="goBack" type="small">返回上一页</el-button>
                    </el-col>
                </el-row>
                <div style="margin-top: 1.5rem;">
                    <label>ID或昵称</label>&nbsp;
                    <el-input clearable style="width: 200px" v-model="search_value"></el-input>
                    <label style="margin-left: 20px">时间</label>&nbsp;
                    <el-date-picker
                            style="width: 350px"
                            v-model="search_time"
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
                                prop="uid"
                                label="用户ID"
                                align="center"
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="nickname"
                                label="用户昵称"
                                align="center"
                                width="180">
                        </el-table-column>
                        <el-table-column
                                prop="connect_time"
                                align="center"
                                label="开始时间">
                        </el-table-column>
                        <el-table-column
                                prop="duration"
                                align="center"
                                label="视频时长(分钟)">
                        </el-table-column>
                        <el-table-column
                                prop="video_money"
                                align="center"
                                label="视频收益(BABY)">
                        </el-table-column>

                        <el-table-column
                                prop="gift_money"
                                align="center"
                                label="礼物收益(BABY)">
                        </el-table-column>

                        <el-table-column
                                prop="total_money"
                                align="center"
                                label="总收益(BABY)">
                        </el-table-column>
                    </el-table>
                </template>
                <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
                    <el-pagination
                            @current-change="handleCurrentChange"
                            :current-page="page"
                            background
                            :page-sizes="[10]"
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
        name: "chatDetailList",

        data() {
            return {
                uid: 0,
                search_value: '',
                search_time: '',
                limit: 10,
                page: 1,
                total: 0,

                tableData: []
            }
        },
        mounted() {
            this.uid = this.$route.query.id;
            this.page = 1;
            this.tableData = [];
            this.getData();


        },


        methods: {

            goBack() {
                history.go(-1);
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
            getData() {
                this.loading = true;
                this.$http
                    .post("chat/chat_detail", {
                        uid: this.uid,
                        page: this.page,
                        content: this.search_value,
                        time: this.search_time,
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