<template>
    <div style="overflow-x: hidden">
        <div v-loading.fullscreen.lock="loading">
            <el-row :gutter="20">
                <el-col :xs="12" :sm="8" :md="8" :lg="6" v-for="(item,index) in data_info" :key="index">
                    <chat_list @getData="getData" :data="item"/>
                </el-col>
            </el-row>

            <div style="width: 90%;margin: 0 auto;margin-bottom: 1.5rem;">
                <el-pagination
                        @current-change="handleCurrentChange"
                        :current-page="page"
                        background
                        :page-sizes="[201]"
                        :page-size="limit"
                        layout="total, sizes, prev, pager, next, jumper"
                        :total="total"
                        class="fr"
                        @size-change="sizeChange"
                ></el-pagination>
            </div>
        </div>

    </div>
</template>

<script>

    import chat_list from '@/components/chatList'

    export default {
        name: "chatList",

        components: {
            chat_list
        },

        data() {
            return {
                page: 1,
                limit: 201,
                total: 0,

                loading: false,

                data_info: [],//列表数据
            }
        },

        mounted() {

            this.getData();
        },

        watch: {

            data_info(newVal) {
                this.$forceUpdate();
            }
        },

        methods: {


            /**
             * 获取数据
             */
            getData() {
                this.loading = true;
                this.$http
                    .post("chat/chat_list", {
                        page: this.page
                    })
                    .then(res => {
                        this.loading = false;
                        if (res.status !== 1) {
                            this.$message.error('获取数据错误')
                        } else {
                            this.total = res.data.total;
                            this.data_info = res.data.info;
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

            /**
             * 切换每页个数
             * @param val
             */
            sizeChange(val) {
                this.limit = val;
                this.getData();
            },


            /**
             * 解密数据
             * @param url 解密资源地址
             */
            readFileAsArrayBuffer(url) {
                const axios = this.$axios;
                return axios({
                    method: 'get',
                    url,
                    responseType: 'arraybuffer',
                }).then((response) => response.data);
            },

            /**
             * 解密数据
             * @param imgArrayBuffer 二进制数据
             * @returns {*}
             */
            decryptImageFromArrayBuffer(imgArrayBuffer) {
                const imgUint8Array = new Uint8Array(imgArrayBuffer);
                const aesJs = this.$aesJs;
                const key = aesJs.utils.utf8.toBytes('128bitslength*@#');
                // eslint-disable-next-line new-cap
                const aesEcb = new aesJs.ModeOfOperation.ecb(key);
                const decImgUint8Array = aesEcb.decrypt(imgUint8Array);
                const imgBlob = new Blob([decImgUint8Array.buffer]);
                const blobUrl = window.URL.createObjectURL(imgBlob);
                return blobUrl;
            },


        }

    }
</script>

<style scoped>

</style>