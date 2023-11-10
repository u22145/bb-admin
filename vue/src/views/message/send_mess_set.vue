<template>
    <div>
        <div class="whiteBg">
            <el-form ref="form" :model="form" :label-width="label_width">
                <el-form-item label="推送消息格式:">
                    <el-radio-group v-model="form.sms_type">
                        <el-radio label="1">文本消息</el-radio>
                        <el-radio label="2">图文消息</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="推送消息对象:">
                    <el-checkbox-group v-model="form.sms_obj" @change="getCheckObjInfo">
                        <el-checkbox label="1" name="1" :disabled="disabled1">所有用户</el-checkbox>
                        <el-checkbox label="2" name="2" :disabled="disabled2">会员</el-checkbox>
                        <el-checkbox disabled label="3" name="3">VIP</el-checkbox>
                        <el-checkbox label="4" name="4" :disabled="disabled2">主播</el-checkbox>
                        <el-checkbox label="5" name="5" :disabled="disabled3">单个用户</el-checkbox>
                    </el-checkbox-group>
                </el-form-item>
                <el-form-item label="用户ID:" v-if="form.sms_obj.indexOf('5')>-1">
                    <el-input v-model="form.sms_uid" style="width: 400px;"></el-input>
                </el-form-item>
                <el-form-item label="定时发送:">
                    <el-radio-group v-model="form.is_set_time">
                        <el-radio label="0">关闭</el-radio>
                        <el-radio label="1">开启</el-radio>
                    </el-radio-group>

                    <div v-if="form.is_set_time==='1'">
                        <el-date-picker
                                v-model="form.set_time"
                                type="date"
                                format="yyyy-MM-dd"
                                value-format="timestamp"
                                placeholder="选择日期">
                        </el-date-picker>
                    </div>
                </el-form-item>
                <el-form-item label="推送消息标题:">
                    <el-input v-model="form.sms_title" style="width: 400px;"></el-input>
                </el-form-item>
                <el-form-item label="推送消息内容:">
                    <el-input type="textarea" rows="4" resize="none" v-model="form.sms_content"
                              style="width: 400px;"></el-input>
                </el-form-item>
                <el-form-item>
                    <el-button type="default" @click="onSubmit">确认发送</el-button>
                </el-form-item>
            </el-form>
        </div>

    </div>
</template>

<script>
    export default {
        name: "send_mess_set",
        data() {
            return {
                usercode: localStorage.getItem('usercode'),

                label_width: '120px',
                form: {
                    sms_type: '1', //消息类型
                    sms_obj: ['1'], //推送对象
                    sms_uid: '', //具体推送的人
                    is_set_time: '0', //是否定时
                    set_time: '',//定时时间
                    sms_title: '', //消息title
                    sms_content: '', //消息内容
                },
                disabled1: false,
                disabled2: true,
                disabled3: true,
            }
        },
        methods: {
            onSubmit() {
                if (this.form.sms_type === '2') {
                    this.$message.error('图文消息发送功能暂不支持');
                    return false;
                }

                if (!this.form.sms_obj.length) {
                    this.$message.error('请选择发送对象');
                    return false;
                }

                if (this.form.sms_obj.indexOf('5') > -1 && !this.form.sms_uid) {
                    this.$message.error('请选择单个用户时需要指定单一发送的用户ID');
                    return false;
                }

                if (this.form.is_set_time === '1' && !this.form.set_time) {
                    this.$message.error('请选择定时发送功能需要设置发送时间');
                    return false;
                }

                if (!this.form.sms_title && !this.form.sms_content) {
                    this.$message.error('请填写完整内容');
                    return false;
                }

                const loading = this.$loading({
                    lock: true,
                    text: 'Loading',
                    spinner: 'el-icon-loading',
                    background: 'rgba(0, 0, 0, 0.4)'
                });
                this.$http
                    .post("messagesend/send_mess_set", {
                        usercode: this.usercode,
                        info: JSON.stringify(this.form),
                    })
                    .then(res => {
                        loading.close();
                        if (res.status !== 1) {
                            this.$message.error(res.msg);
                        } else {
                            this.$message.success(res.msg);
                            this.clearInfo();
                        }

                        console.log(res)
                    });


            },

            /**
             * 清除数据
             */
            clearInfo() {
                this.form = {
                    sms_type: '1', //消息类型
                    sms_obj: ['1'], //推送对象
                    sms_uid: '', //具体推送的人
                    is_set_time: '0', //是否定时
                    set_time: '',//定时时间
                    sms_title: '', //消息title
                    sms_content: '', //消息内容
                };
                this.disabled1 = false;
                this.disabled2 = true;
                this.disabled3 = true;
            },

            getCheckObjInfo(val) {
                if (val.indexOf('1') >= 0) {
                    if (this.form.sms_obj.length > 0) {
                        this.form.sms_obj = [];
                        this.form.sms_obj.push(val[val.length - 1])
                    }
                    this.disabled3 = true;
                    this.disabled2 = true;

                } else {
                    if (val.indexOf('5') >= 0) {
                        if (this.form.sms_obj.length > 0) {
                            this.form.sms_obj = [];
                            this.form.sms_obj.push(val[val.length - 1])
                        }
                        this.disabled1 = true;
                        this.disabled2 = true;
                        this.disabled3 = false;
                    } else {
                        this.disabled1 = false;
                        this.disabled2 = false;
                        this.disabled3 = false;
                    }
                }
            }
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
</style>