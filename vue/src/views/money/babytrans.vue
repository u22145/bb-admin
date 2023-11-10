<template>
    <div class="container" v-loading.fullscreen.lock="loading">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">用户ID</label>&nbsp;
                <el-input
                        clearable
                        style="width: 200px"
                        id="inputID"
                        v-model="search_uid"
                ></el-input>

                <label style="margin-left: 20px">操作人</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="search_op_uid"
                >
                    <el-option v-for="(item,index) in op_uid_arr" :key="index" :label='item.username'
                               :value="item.id"></el-option>
                </el-select>

                <label for="mobile" style="margin-left: 20px">手机号</label>&nbsp;
                <el-input
                        style="width: 200px"
                        id="mobile"
                        v-model="search_mobile"
                ></el-input>

                <label style="margin-left: 20px">会员</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="search_vip"
                >
                    <el-option label='是' value="1"></el-option>
                    <el-option label='否' value="2"></el-option>
                </el-select>
            </div>
            <div class="screen">

                <label style="margin-left: 20px">主播</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="search_anchor"
                >
                    <el-option label='是' value="1"></el-option>
                    <el-option label='否' value="2"></el-option>
                </el-select>

                <label style="margin-left: 20px">操作时间</label>&nbsp;
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

                <label style="margin-left: 20px">加扣钱类型</label>&nbsp;
                <el-select
                        clearable
                        placeholder='请选择'
                        v-model="search_trans_type"
                >
                    <el-option label='加钱' value="1"></el-option>
                    <el-option label='扣钱' value="2"></el-option>
                </el-select>

                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search">
                    搜索
                </el-button>
            </div>
        </div>


        <div class="whiteBg">

            <div class="fr">
                <el-button @click="getData(2)">导出excel</el-button>
                <el-button @click="clearSearch">清空查询</el-button>
            </div>

            <div class="screen" style="width: 100%">

                <div>
                    <el-table
                            :data="tableData"
                            border
                            style="width: 100%">
                        <el-table-column
                                fixed
                                prop="uid"
                                label="用户ID"
                                align="center"
                                width="100">
                        </el-table-column>
                        <el-table-column
                                fixed
                                prop="nickname"
                                label="用户昵称"
                                align="center"
                                width="150">
                        </el-table-column>
                        <el-table-column
                                prop="mobile"
                                label="注册手机"
                                align="center"
                                width="150">
                        </el-table-column>
                        <el-table-column
                                prop="is_vip"
                                label="会员"
                                align="center"
                                width="120">
                        </el-table-column>
                        <el-table-column
                                prop="is_anchor"
                                label="主播"
                                align="center"
                                width="120">
                        </el-table-column>
                        <el-table-column
                                label="加扣款"
                                align="center"
                                width="150">
                            <template slot-scope="scope">
                                <div v-if="parseFloat(scope.row.amount)>0">
                                    <span style="color:green;">+{{scope.row.amount}}</span>
                                </div>
                                <div v-else>
                                    <span style="color: red;">{{scope.row.amount}}</span>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column
                                prop="memo"
                                align="center"
                                label="操作备注">
                        </el-table-column>
                        <el-table-column
                                prop="op_time"
                                label="操作时间"
                                align="center"
                                width="200">
                        </el-table-column>
                        <el-table-column
                                prop="op_nickname"
                                label="操作人"
                                align="center"
                                width="150">
                        </el-table-column>
                        <!--                        <el-table-column-->
                        <!--                                fixed="right"-->
                        <!--                                label="操作"-->
                        <!--                                align="center"-->
                        <!--                                width="180">-->
                        <!--                            <template slot-scope="scope">-->
                        <!--                                &lt;!&ndash;                                <el-button @click="handleShowClick(scope.row)" type="text" size="small">查看</el-button>&ndash;&gt;-->
                        <!--                                <el-button @click="handleDeleteClick(scope.row)" type="text" size="small">删除</el-button>-->
                        <!--                            </template>-->
                        <!--                        </el-table-column>-->
                    </el-table>

                    <div style="width: 90%;margin: 0 auto;margin-bottom: 4rem;">
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


    </div>
</template>

<script>
    //baby金额扣钱、派奖（自动、手动）记录
    export default {
        name: "babytrans",

        data() {
            return {
                loading: false,
                usercode: localStorage.getItem('usercode'),

                op_uid_arr: [],//操作人信息


                search_time: '',
                search_uid: '',
                search_mobile: '',
                search_vip: '',
                search_anchor: '',
                search_op_uid: '',
                search_trans_type:'',

                limit: 10,
                limits: [10, 15, 20, 50, 100],
                page: 1,
                total: 0,

                activeName: '1',
                check_activeName: '1',
                tableData: []
            }
        },

        mounted() {
            this.getOpUid();
            this.getData();
        },

        methods: {

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
             * 清空查询
             */
            clearSearch() {
                this.search_op_uid = '';
                this.search_uid = '';
                this.search_time = '';
                this.search_mobile = '';
                this.search_vip = '';
                this.search_anchor = '';
                this.search_trans_type = '';

                this.page = 1;
                this.getData();
            },

            /**
             * 查询
             */
            search() {
                this.getData();
            },


            /**
             * 获取数据 || 导出数据
             */
            getData(type = 1) {
                this.loading = true;
                this.$http
                    .post("babytrans/index", {
                        type: type,
                        page: this.page,
                        size: this.limit,
                        search_op_uid: this.search_op_uid,
                        search_vip: this.search_vip,
                        search_anchor: this.search_anchor,
                        search_mobile: this.search_mobile,
                        search_uid: this.search_uid,
                        search_time: this.search_time,
                        search_trans_type: this.search_trans_type
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
             * 查看
             * @param row
             */
            handleShowClick(row) {
                console.log(row)
            },


            /**
             * 删除
             * @param row
             */
            handleDeleteClick(row) {
                console.log(row);
                this.$confirm('此操作将进行软删除该文件, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.loading = true;
                    this.$http
                        .post("babytrans/delete_info", {
                            type: this.activeName,
                            id: row.id
                        })
                        .then(res => {
                            this.loading = false;

                            if (res.status === 1) {
                                this.$message.success(res.msg);
                                this.getData();
                            } else {
                                this.$message.error(res.msg);
                            }
                        });
                }).catch(() => {
                    this.$message({
                        type: 'info',
                        message: '已取消删除'
                    });
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
                this.page = 1;
                this.getData();
            },
        }
    }
</script>

<style lang="less" scoped>
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

</style>