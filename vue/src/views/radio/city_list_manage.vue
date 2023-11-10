<template>
    <div>
        <div class="whiteBg">
            <div class="screen">

                <div style="margin-right: 10px;">
                    <el-row>
                        <el-col :span="12">
                            <div style="text-align: left;">
                                <el-button size="small" @click="gotoBackPage">返回上一页</el-button>
                            </div>
                        </el-col>
                        <el-col :span="12">
                            <div style="text-align: right;">
                                <el-button size="small" @click="addTypeInfo">{{add_btn_msg}}</el-button>
                            </div>
                        </el-col>

                    </el-row>

                </div>

                <div style="margin-top: 20px;margin-bottom: 30px">
                    <el-table
                            :data="tableData"
                            border
                            v-loading="loading"
                            style="width: 100%">


                        <div v-for="(item,index) in table_title">
                            <el-table-column align="center"
                                             v-if="item.prop!=='img_url'"
                                             :prop="item.prop"
                                             :label="item.name">
                            </el-table-column>

                        </div>


                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="220">
                            <template slot-scope="scope">
                                <el-button type="text" @click="modifyInfo(scope.row)" size="small">编辑</el-button>
                                <el-button type="text" @click="deleteInfo(scope.row)" size="small">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <el-pagination
                            @current-change="handleCurrentChange"
                            :current-page="page"
                            :page-sizes="[30]"
                            :page-size="limit"
                            layout="total, sizes, prev, pager, next, jumper"
                            :total="total"
                            class="fr"
                            @size-change="sizeChange"
                    ></el-pagination>
                </div>

            </div>
        </div>

        <el-dialog :visible.sync="dialogFormVisible" width="40%">
            <el-form :model="form">

                <el-form-item label="地区名称" :label-width="formLabelWidth">
                    <el-input v-model="form.name" autocomplete="off"></el-input>
                </el-form-item>

            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="createPost">确认</el-button>
                <el-button @click="dialogFormVisible = false">取消</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
    export default {
        name: "city_list_manage",
        data() {
            return {
                add_btn_msg: '新增地区类型',
                loading: false,
                page: 1,
                limit: 30,
                total: 1,

                usercode: '',

                //表格表头
                table_title: [],

                //表数据
                tableData: [],

                search_id: 0, //父级国家id

                //弹框
                formLabelWidth: '100px',
                dialogFormVisible: false,
                form: {
                    id: 0,
                    name: ''
                },
            }
        },


        mounted() {

            this.search_id = this.$route.query.id;
            this.usercode = localStorage.getItem('usercode');
            this.getData();

        },

        methods: {

            gotoBackPage() {
                this.$router.go(-1);
            },


            /**
             * 获取数据
             */
            getData() {
                this.loading = true;
                this.$http
                    .post("radio/city_type_manage", {
                        page: this.page,
                        page_size: this.limit,
                        id: this.search_id,
                    })
                    .then(res => {
                        this.loading = false;
                        this.table_title = res.data.title;
                        this.tableData = res.data.list;
                        this.page = res.data.page;
                        this.total = res.data.total;
                    });
            },

            //分页
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            sizeChange(val) {
                this.limit = val;
                this.getData(val);
            },


            /**
             * 新增
             */
            addTypeInfo() {
                this.dialogFormVisible = true;
            },


            /**
             * 添加或者修改
             * @returns {boolean}
             */
            createPost() {

                if (!this.form.name) {
                    this.$message({
                        type: "error",
                        message: '请将数据填写完整'
                    });
                    return false;
                }
                this.loading = true;

                this.$http
                    .post("/radio/deal_city_type_info", {
                        name: this.form.name,
                        id: this.form.id,
                        pid: this.search_id
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status === 1) {
                            this.$message({
                                type: "success",
                                message: res.msg
                            });
                            this.dialogFormVisible = false;
                            this.form = {
                                id: 0,
                                name: ''
                            };
                            this.getData();
                        } else {
                            this.$message({
                                type: "error",
                                message: res.msg
                            });
                        }

                    });


            },

            /**
             * 修改
             * @param row
             */
            modifyInfo(row) {
                this.dialogFormVisible = true;
                this.form.id = row.id;
                this.form.name = row.name;
            },

            /**
             * 删除
             */
            deleteInfo(row) {
                this.$confirm('此操作将永久删除, 是否继续?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.loading = true;

                    this.$http
                        .post("/radio/delete_city_type_info", {
                            id: row.id,
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

<style scoped>

</style>