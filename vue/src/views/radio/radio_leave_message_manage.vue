<template>

    <div>
        <div class="whiteBg">

            <div class="screen">
                <el-row>
                    <el-col :span="12">
                        <div style="color: #333333;margin: 20px auto;font-size: 20px;">
                            留言列表详情：
                        </div>
                    </el-col>
                    <el-col :span="12">
                        <div style="text-align: right;margin-right: 20px;">
                            <el-button size="small" @click="gotoBackPage">返回上一页</el-button>
                        </div>
                    </el-col>

                </el-row>

                <div style="margin-top: 20px;">
                    <el-collapse v-model="activeNames" @change="handleChange"
                                 v-loading="loading">
                        <el-collapse-item v-for="(item,index) in tableData" :key="index" :name="''+(index+1)">
                            <template slot="title">
                                <div style="height: 50px;line-height: 50px;">
                                    <div style="display: flex;flex-direction: row;">
                                        <div class="avatar_view">
                                            <img :src="item.avatar"
                                                 style="width: 30px;height: 30px;" alt="">
                                        </div>
                                        <div class="reply_people_view">
                                            <span>{{item.nickname}}</span>
                                            <span style="margin-left: 10px;">{{item.floor_id}}：{{item.content}}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-if="item.reply.length" v-for="(item1,index1) in item.reply" :key="index1"
                                 style="margin-top: 20px;">
                                <div style="margin-left: 20px;">
                                    <div style="display: flex;flex-direction: row;">
                                        <div class="avatar_view">
                                            <img :src="item1.avatar"
                                                 style="width: 20px;height: 20px;" alt="">
                                        </div>
                                        <div class="reply_people_view">回复人：{{item1.nickname}}</div>
                                    </div>
                                </div>
                                <div style="margin-left: 60px">
                                    <span>{{item1.content}}</span>
                                </div>
                            </div>

                            <div v-if="!item.reply.length" style="margin-top: 20px;">
                                <div style="margin-left: 20px;">
                                    <div style="display: flex;flex-direction: row;">
                                        <div class="reply_people_view" style="margin-left:30px">无回复信息</div>
                                    </div>
                                </div>

                            </div>


                        </el-collapse-item>
                    </el-collapse>

                    <div v-if="!loading && !tableData.length">
                        <div style="margin: 0 auto;margin-top: 20px;text-align: center;">
                            无留言信息
                        </div>

                    </div>
                </div>


            </div>
        </div>


    </div>
</template>

<script>
    export default {
        name: "radio_leave_message_manage",


        data() {
            return {
                search_id: '',
                usercode: "",
                activeNames: ['1'],
                tableData: [],
                loading: false
            }
        },

        mounted() {
            this.search_id = this.$route.query.id;
            this.usercode = localStorage.getItem('usercode');
            this.getData();
        },


        methods: {

            /**
             * 获取数据
             */
            getData() {
                this.loading = true;
                this.$http
                    .post("radio/get_leave_message", {
                        id: this.search_id,
                    })
                    .then(res => {
                        this.loading = false;
                        console.log(res.data);
                        this.tableData = res.data;
                    });
            },

            /**
             * 返回上一页
             */
            gotoBackPage() {
                this.$router.go(-1);
            },


            handleChange(val) {
                console.log(val);
            }
        }
    }
</script>

<style scoped>

    .avatar_view {
        display: flex;
        justify-content: center;
        align-content: center;
        align-items: center;
    }

    .reply_people_view {
        display: flex;
        justify-content: center;
        align-content: center;
        align-items: center;
        margin-left: 10px;
    }

</style>