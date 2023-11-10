<template>
    <!--一对一单一list-->
    <div class="container_div">

        <div class="video_container">
            <div v-if="!is_play" @click="clickStartPlay">
                <img src="/static/img/video_load_img.png" style="width: 100%;height: 200px;" alt="">
            </div>
            <div v-else>
                <video
                        :id="'my-video'+id"
                        class="video-js vjs-default-skin video_box"
                        controls
                        autoplay="false"
                        preload="auto"
                >
                    <!--                <source :src="data.ts" type="application/x-mpegURL"/>-->
                </video>
            </div>


        </div>
        <div class="content_container">

            <div class="list_div">
                <div class="list_title">
                    <span>主播ID:</span>
                </div>
                <div class="list_info">
                    <span>{{data.uid}}</span>
                </div>

                <div class="list_status">
                    <span v-if="!data.status" style="color: green;">空闲中</span>
                    <span v-else style="color: green;">忙线中</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>昵称:</span>
                </div>
                <div class="list_info">
                    <span>{{data.nickname}}</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>主题:</span>
                </div>
                <div class="list_info">
                    <span>{{data.name}}</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>视频聊天人次:</span>
                </div>
                <div class="list_info">
                    <span>{{data.call_people_num}}人</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>视频聊天时长:</span>
                </div>
                <div class="list_info">
                    <span>{{data.call_times}}分钟</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>收到礼物收益:</span>
                </div>
                <div class="list_info">
                    <span>{{data.gift_money}}BABY</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>视频收益:</span>
                </div>
                <div class="list_info">
                    <span>{{data.video_money}}BABY</span>
                </div>
            </div>

            <div class="list_div">
                <div class="list_title">
                    <span>总收益:</span>
                </div>
                <div class="list_info">
                    <span>{{data.total_money}}BABY</span>
                </div>
            </div>

            <div class="list_div" style="justify-content: flex-end;">
                <div>
                    <el-button size="mini" v-if="data.is_top" @click="setTop(2,data.uid)">取消置顶</el-button>
                    <el-button size="mini" v-else @click="setTop(1,data.uid)">置顶</el-button>
                </div>
                <div style="margin-left: 1rem;">
                    <el-button size="mini" @click="stopOrStartOneToOne(1,data.id)" v-if="parseInt(data.is_deleted)!==2">
                        禁播
                    </el-button>
                    <el-button size="mini" @click="stopOrStartOneToOne(2,data.id)" v-else>取消禁播</el-button>
                </div>

            </div>

        </div>

    </div>
</template>

<script>

    import videojs from 'video.js'
    import 'videojs-contrib-hls'

    export default {
        name: "chatList",

        data() {
            return {
                myVideo: null, //保存视频播放器对象
                id: '',
                is_play: false,
            }
        },
        props: ["data"],

        mounted() {
            this.id = this.data.id;
            this.is_play = this.data.is_play;
            if (this.is_play)
                this.playVideo();
        },


        methods: {

            /**
             * 开始播放
             */
            clickStartPlay() {
                this.is_play = true;
                this.playVideo();
            },

            /**
             * 播放视频
             */
            playVideo() {
                this.$nextTick(function () {
                });
                this.$nextTick(function () {
                    this.myVideo = videojs(
                        "my-video" + this.id,
                        {
                            hls: {
                                withCredentials: true,
                            },
                        },
                        () => {
                            this.myVideo.src({
                                src: this.data.video_url,
                                type: "application/x-mpegURL",
                            });

                            this.myVideo.on('ended', () => {
                                this.is_play = false;
                                if (this.myVideo) this.myVideo.dispose();
                            });
                        }
                    );
                });
            },


            /**
             * 禁播
             */
            stopOrStartOneToOne(type, id) {
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
                            this.$emit('getData')
                        }
                    });
            },


            /**
             * 置顶 ｜ 取消置顶
             * @param type
             * @param uid
             */
            setTop(type, uid) {
                this.$http
                    .post("chat/set_top", {
                        type: type,
                        uid: uid
                    })
                    .then(res => {
                        this.$message({
                            type: res.status === 1 ? "success" : "error",
                            message: res.msg
                        });
                        if (res.status === 1) {
                            this.$emit('getData')
                        }
                    });
            }
        }
    }
</script>

<style lang="less" scoped>

    @height: 33rem; //框框的高度

    @border_color: #ccc; //边框颜色

    @list_margin_bottom: .5rem; //每个距离下面的距离

    .container_div {

        height: @height;
        width: 90%;
        border: 2px solid @border_color;
        background-color: white;
        margin-bottom: 1.5rem;

        .video_container {
            width: 100%;
            height: 200px;
            border-bottom: 2px solid @border_color;

            .video_box {
                width: 100%;
                height: 200px;
            }
        }

        .content_container {
            width: 90%;
            margin: 1.5rem auto;
            font-size: 1rem;

            .list_div {
                display: flex;
                flex-direction: row;
                align-items: center;
                margin-bottom: @list_margin_bottom;

                .list_title {
                    display: flex;
                    flex-direction: row;
                    justify-content: flex-start;
                }

                .list_info {
                    display: flex;
                    flex-direction: row;
                    justify-content: center;
                    margin-left: 0.5rem;
                }

                .list_status {
                    display: flex;
                    flex-direction: row;
                    justify-content: flex-end;
                    flex-grow: 1;
                }

            }
        }

    }

</style>