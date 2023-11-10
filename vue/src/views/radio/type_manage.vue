<template>
    <div>
        <div class="whiteBg">
            <div class="screen">
                <el-tabs v-model="activeName" @tab-click="handleTabsClick">
                    <el-tab-pane label="地区管理" name="first"></el-tab-pane>
                    <el-tab-pane label="活动类型管理" name="second"></el-tab-pane>
                    <el-tab-pane label="期望类型管理" name="third"></el-tab-pane>
                    <el-tab-pane label="评价类型管理" name="forth"></el-tab-pane>
                    <el-tab-pane label="举报类型管理" name="fifth"></el-tab-pane>
                </el-tabs>

                <div style="text-align: right;margin-right: 10px;">
                    <div>
                        <el-button size="small" @click="addTypeInfo">{{add_btn_msg}}</el-button>
                    </div>

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

                            <el-table-column
                                    align="center"
                                    v-if="item.prop==='img_url'"
                                    label="图片信息">
                                <template slot-scope="scope">
                                    <img :src="scope.row.img_url" style="width: 50px;height: auto;" alt="">
                                </template>
                            </el-table-column>

                        </div>


                        <el-table-column
                                fixed="right"
                                align="center"
                                label="操作"
                                width="220">
                            <template slot-scope="scope">
                                <el-button v-if="activeName==='first'" @click="handleShowClick(scope.row.id)"
                                           type="text"
                                           size="small">查看
                                </el-button>
                                <el-button type="text" @click="modifyInfo(scope.row)" size="small">编辑</el-button>
                                <el-button type="text" @click="deleteInfo(scope.row)" size="small">删除</el-button>
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
        </div>


        <el-dialog :visible.sync="dialogFormVisible" width="40%">
            <el-form :model="form">
                <el-form-item v-if="activeName!=='first'" label="类型名称" :label-width="formLabelWidth">
                    <el-input v-model="form.name" autocomplete="off"></el-input>
                </el-form-item>

                <el-form-item v-if="activeName==='first'" label="地区名称" :label-width="formLabelWidth">
                    <el-input v-model="form.name" autocomplete="off"></el-input>
                </el-form-item>

                <el-form-item v-if="is_modify && activeName==='second'" label="" :label-width="formLabelWidth">
                    <el-switch
                            v-model="is_change_pic"
                            active-text="修改图片"
                            inactive-text="不修改图片">
                    </el-switch>
                </el-form-item>

                <el-form-item v-if="is_change_pic && activeName==='second'" label="上传图片" :label-width="formLabelWidth">
                    <el-upload
                            class="upload-demo"
                            ref="upload"
                            action="/radio/deal_type_info"
                            :data="post_data"
                            :on-success="fileSuccess"
                            :on-error="fileFail"
                            :on-remove="handleRemove"
                            :auto-upload="false">
                        <el-button slot="trigger" size="small" type="primary">选取文件</el-button>
                    </el-upload>
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
        name: "type_manage",

        data() {
            return {
                activeName: 'first',
                add_btn_msg: '新增地区类型',
                loading: false,
                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 0,

                usercode: '',

                //表格表头
                table_title: [],

                //表数据
                tableData: [],


                //弹框
                formLabelWidth: '100px',
                dialogFormVisible: false,
                form: {
                    id: 0,
                    name: ''
                },
                post_data: {}, //上传图片时候传递的参数
                is_modify: false,
                is_change_pic: true //是否修改类型图片
            }
        },
        mounted() {
            this.usercode = localStorage.getItem('usercode');
            this.getData();
        },
        methods: {
            /**
             * tabs点击
             * @param tab
             * @param event
             */
            handleTabsClick(tab, event) {
                this.activeName = tab.name;
                switch (tab.name) {
                    case 'first':
                        this.add_btn_msg = '新增地区类型';
                        break;
                    case 'second':
                        this.add_btn_msg = '新增活动类型';
                        break;
                    case 'third':
                        this.add_btn_msg = '新增期望类型';
                        break;
                    case 'forth':
                        this.add_btn_msg = '新增评价类型';
                        break;
                    case 'fifth':
                        this.add_btn_msg = '新增举报类型';
                        break;
                }

                this.page = 1;
                this.table_title = [];
                this.tableData = [];
                this.getData();
            },

            /**
             * 获取数据
             */
            getData() {
                this.loading = true;
                var check_type = '';
                switch (this.activeName) {
                    case 'first':
                        check_type = 1;
                        break;
                    case 'second':
                        check_type = 2;
                        break;
                    case 'third':
                        check_type = 3;
                        break;
                    case 'forth':
                        check_type = 4;
                        break;
                    case 'fifth':
                        check_type = 5;
                        break;
                }
                this.$http
                    .post("radio/type_manage", {
                        page: this.page,
                        page_size: this.limit,
                        type: check_type,
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


            addTypeInfo() {
                this.dialogFormVisible = true;
            },

            /**
             * 添加或者修改
             * @returns {boolean}
             */
            createPost() {
                var check_type = '';
                if (!this.form.name) {
                    this.$message({
                        type: "error",
                        message: '请将数据填写完整'
                    });
                    return false;
                }
                this.loading = true;
                switch (this.activeName) {
                    case 'first':
                        check_type = 1;
                        break;
                    case 'second':
                        check_type = 2;
                        break;
                    case 'third':
                        check_type = 3;
                        break;
                    case 'forth':
                        check_type = 4;
                        break;
                    case 'fifth':
                        check_type = 5;
                        break;
                }
                this.post_data = {
                    usercode: this.usercode,
                    type: check_type,
                    id: this.form.id,
                    name: this.form.name
                };

                if (JSON.stringify(this.post_data)) {
                    if (this.activeName === 'second' && this.is_change_pic) {
                        console.log(this.$refs.uploadFiles)
                        if (this.$refs.upload.uploadFiles.length) {
                            setTimeout(() => {
                                this.$refs.upload.submit();
                            }, 300);
                        } else {
                            this.loading = false;
                            this.$message({
                                type: "error",
                                message: '请选择需要上传的图片资源'
                            });
                        }

                    } else {
                        this.$http
                            .post("/radio/deal_type_info", {
                                type: check_type,
                                name: this.form.name,
                                id: this.form.id
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
                    }
                }


            },

            /**
             * 图片和信息上传成功
             */
            fileSuccess(response) {
                console.log(response);
                if (response.status === 1) {
                    this.$message({
                        type: "success",
                        message: '新增成功'
                    });
                    this.form = {
                        id: 0,
                        name: ''
                    };
                    this.dialogFormVisible = false;
                    this.getData();
                } else {
                    this.$message({
                        type: "error",
                        message: '新增失败'
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

            handleRemove() {
            },

            /**
             * 地区列表查看下一页
             */
            handleShowClick(id) {

                this.$router.push({
                    path: '/city_list_manage',
                    query: {
                        id: id
                    }
                });
            },

            /**
             * 修改
             * @param row
             */
            modifyInfo(row) {
                this.is_modify = true;
                this.dialogFormVisible = true;
                this.form.id = row.id;
                this.form.name = row.name;
                this.is_change_pic = true;
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
                    var check_type = '';
                    switch (this.activeName) {
                        case 'first':
                            check_type = 1;
                            break;
                        case 'second':
                            check_type = 2;
                            break;
                        case 'third':
                            check_type = 3;
                            break;
                        case 'forth':
                            check_type = 4;
                            break;
                        case 'fifth':
                            check_type = 5;
                            break;
                    }
                    this.$http
                        .post("/radio/delete_type_info", {
                            id: row.id,
                            type: check_type
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