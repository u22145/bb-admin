<template>
    <div>
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">用户ID</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="inputID"
                        v-model="uid"
                ></el-input>

                <label style="margin-left: 20px">操作人</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="op_uid"
                >
                    <el-option v-for="(item,index) in op_uid_arr" :key="index" :label='item.username'
                               :value="item.id"></el-option>
                </el-select>

                <label style="margin-left: 20px">消息类型</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="sms_type"
                >
                    <el-option label='文本消息' value="1"></el-option>
                    <el-option label='图文消息' value="2"></el-option>
                </el-select>

                <label style="margin-left: 20px">定时消息</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="sms_timer"
                >
                    <el-option label='定时' value="1"></el-option>
                    <el-option label='不定时' value="0"></el-option>
                </el-select>

            </div>
            <div class="screen">
                <label style="margin-left: 20px">消息对象</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="sms_obj"
                >
                    <el-option label='所有用户' value="1"></el-option>
                    <el-option label='会员' value="2"></el-option>
                    <el-option label='VIP' value="3"></el-option>
                    <el-option label='主播' value="4"></el-option>
                    <el-option label='单个用户' value="5"></el-option>
                </el-select>
                <label style="margin-left: 20px">发送时间</label>&nbsp;
                <el-date-picker
                        clearable
                        style="width: 350px"
                        v-model="search_time"
                        type="datetimerange"
                        :unlink-panels="true"
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

            <div class="screen" style="width: 100%;">
                <template>
                    <el-table
                            :data="tableData"
                            border
                            style="width: 100%;">
                        <el-table-column
                                fixed
                                prop="id"
                                align="center"
                                label="索引ID"
                                width="100">
                        </el-table-column>
                        <el-table-column
                                prop="sms_title"
                                label="消息标题"
                                align="center"
                                width="150">
                        </el-table-column>
                        <el-table-column
                                prop="sms_content"
                                align="center"
                                label="消息内容"
                                width="170">
                        </el-table-column>
                        <el-table-column
                                prop="sms_type"
                                label="消息类型"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="img_url_txt"
                                label="图片"
                                align="center"
                                width="100">
                        </el-table-column>
                        <el-table-column
                                prop="is_set_time"
                                label="定时消息"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                width="120"
                                prop="push_obj"
                                label="消息对象"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="up_time"
                                label="发送时间"
                                align="center"
                                width="170">
                        </el-table-column>
                        <el-table-column
                                prop="send_num"
                                label="发送条数"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="watch_num"
                                label="已阅条数"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="op_uid"
                                label="操作人"
                                align="center">
                        </el-table-column>
                        <el-table-column
                                prop="op_time"
                                align="center"
                                label="操作时间"
                                width="170">
                        </el-table-column>
                    </el-table>
                </template>

                <div style="width: 100%;margin: 1rem auto;margin-bottom: 4rem;">
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
    </div>
</template>

<script>
    export default {
        name: "send_mess_list",

        data() {
            return {
                loading: false,
                usercode: localStorage.getItem('usercode'),


                uid: '',
                op_uid: '',
                sms_type: '',
                sms_timer: '',
                sms_obj: '',
                search_time: [],


                op_uid_arr: [],

                tableData: [],
                limit: 10,
                limits: [10, 15, 20, 50, 100, 200],
                page: 1,
                total: 0,

            }
        },


        mounted() {
            this.getOpUid();
            this.getData();
        },

        methods: {

            /**
             * 获取操作人
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

            /**
             * 清除数据
             */
            clear() {
                this.uid = "";
                this.op_uid = "";
                this.sms_type = "";
                this.sms_timer = "";
                this.search_time = [];
                this.sms_obj = '';

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
                    .post("messagesend/send_mess_list", {
                        type: type,
                        page: this.page,
                        size: this.limit,
                        uid: this.uid,
                        time: this.search_time,
                        op_uid: this.op_uid,
                        sms_type: this.sms_type,
                        sms_timer: this.sms_timer,
                        sms_obj: this.sms_obj,
                    })
                    .then(res => {
                        this.loading = false;
                        if (type === 1) {
                            if (res.status !== 1) {
                                this.$message.error('获取数据错误')
                            } else {
                                this.total = res.data.total;
                                this.tableData = res.data.info;
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

        }
    }
</script>

<style lang="less" scoped>
    .screen {
        margin-bottom: 15px;

        label {
            color: #767474;
            font-size: 14px;
            margin-left: 20px;
        }
    }

    .fr {
        margin-bottom: 20px;
    }
</style>