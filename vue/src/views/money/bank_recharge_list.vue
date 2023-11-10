<template>
    <div class="rechargelist">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">用户ID</label>
                <el-input
		                style="width: 120px"
		                id="uid"
		                v-model="uid"
		                @keyup.enter.native="getData"
		                placeholder="uid / 用户ID"
		        ></el-input>
		        <label style="margin-left: 20px">操作人</label>
                <el-input
		                style="width: 120px"
		                id="order_operator"
		                v-model="order_operator"
		                @keyup.enter.native="getData"
		        ></el-input>
		        <label style="margin-left: 20px">手机号</label>
                <el-input
		                style="width: 160px"
		                id="mobile"
		                v-model="mobile"
		                @keyup.enter.native="getData"
		        ></el-input>
		        <label style="margin-left: 20px">转账人</label>
                <el-input
		                style="width: 140px"
		                id="payment_username"
		                v-model="payment_username"
		                @keyup.enter.native="getData"
		        ></el-input>
		        <label style="margin-left: 20px">转账卡号</label>
                <el-input
		                style="width: 240px"
		                id="payment_bankcard_no"
		                v-model="payment_bankcard_no"
		                @keyup.enter.native="getData"
		        ></el-input>

                <label style="margin-left: 20px">订单号</label>
                <el-input
		                style="width: 200px"
		                id="order_id"
		                v-model="order_id"
		                @keyup.enter.native="getData"
		        ></el-input>
		        
            </div>
            <div class="screen">
                <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
                <el-select placeholder v-model="status" @keyup.enter.native="getData">
                	<el-option label='不选' value=''> </el-option>
                    <el-option :label='$t("common.solved")' value="SOLVED"></el-option>
                    <el-option :label='$t("common.denied")' value="DENIED"></el-option>
                    <el-option :label='$t("common.processing")' value="PROCESSING"></el-option>
                </el-select>
                <label>{{$t("common.Creation_time")}}</label>&nbsp;
                <el-date-picker
                        v-model="transfer_time"
                        type="datetimerange"
                        :range-separator='$t("common.to")'
                        :start-placeholder='$t("common.start_time")'
                        :end-placeholder='$t("common.end_time")'
                        value-format="yyyy-MM-dd HH:mm:ss"
                ></el-date-picker>
                <label>{{$t("common.process_time")}}</label>&nbsp;
                <el-date-picker
                        v-model="process_time"
                        type="datetimerange"
                        :range-separator='$t("common.to")'
                        :start-placeholder='$t("common.start_time")'
                        :end-placeholder='$t("common.end_time")'
                        value-format="yyyy-MM-dd HH:mm:ss"
                ></el-date-picker>

                <el-button type="primary" class="fr" style="padding: 8px 30px" @click="getData()">
                    {{$t("common.search")}}
                </el-button>

                
            </div>
        </div>

        <div class="whiteBg">
            
            <div class="fr">
                <el-button @click="getData(1)"> {{$t("common.export")}}  </el-button>
            </div>
            <div class="fr">
                <el-button @click="clear">{{$t("common.clear")}}</el-button>
            </div>
            <div class="tableCon">
                <el-table
                        ref="multipleTable"
                        :data="tableData"
                        v-loading="loading"
                        border
                        style="width: 100%;margin-top: 30px"
                >
                	<el-table-column label='序号' align="center" prop="index"></el-table-column>
                    <el-table-column label='用户ID' align="center" prop="uid"></el-table-column>
                    <el-table-column label='用户昵称' align="center" prop="nickname"></el-table-column>
                    <el-table-column label='注册手机号' align="center" prop="mobile"></el-table-column>
                    <el-table-column label='转账人' align="center" prop="payment_username"></el-table-column>
                    <el-table-column label='转账金额' align="center" prop="money"></el-table-column>
                    <el-table-column label='转账卡号' align="center" prop="payment_bankcard_no"></el-table-column>
                    <el-table-column label='收款卡信息' align="center" prop="payee_account_info"></el-table-column>
                    <el-table-column label='订单号' align="center" prop="order_id"></el-table-column>
                    <el-table-column label='状态' align="center" prop="status"></el-table-column>
                    <el-table-column label='操作人' align="center" prop="operator"></el-table-column>
                    <el-table-column label='操作备注' align="center" prop="order_note"></el-table-column>
                    <el-table-column label='操作时间' align="center" prop="updated_at"></el-table-column>


                    <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
                        <template slot-scope="scope">
                          <el-button v-if="scope.row.status != '未处理' " type="primary" @click="handleCheckClick( scope.row )">查看</el-button>
                          <el-button v-if="scope.row.status == '未处理' " type="primary" @click="handleProcessClick( scope.row )">到帐上分</el-button>
                          <el-button v-if="scope.row.status == '未处理' " type="primary" @click="handleDenyClick( scope.row )">拒绝</el-button>
                        </template>
                      </el-table-column>
                </el-table>
                <div>
                	{{`小计 （本页已上分笔数/本页已上分总金额）： ` + summary.page_count_solved + `笔  / ` + summary.page_total_solved }} 
                    <br>
                	{{`共计 （总上分笔数/总上分金额）：` + summary.page_count_solved + `笔  / ` + summary.page_total_solved }}
            	</div>

                <!-- 分页 -->
                <el-pagination
                        @current-change="handleCurrentChange"
                        :current-page="page"
                        :page-sizes="[this.$store.state.adminPageSize]"
                        :page-size="limit"
                        layout="total, sizes, prev, pager, next, jumper"
                        :total="total"
                        class="fr"
                ></el-pagination>
            </div>
        </div>
        <!-- 查看表单 -->
        <el-dialog title="查看表单" width="50%" center :show-close="false" :visible.sync="dialogFormVisibleCheck">
            <el-form :model="chk_form">

            	<el-form-item label="用户ID" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.uid" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="用户昵称" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.nickname" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="转账金额" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.money" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="订单状态" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.status" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="操作备注" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.order_note" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="操作人" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.operator" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="操作时间" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.updated_at" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="订单号" :label-width="formLabelWidth">
                    <el-input disabled v-model="chk_form.order_id" autocomplete="off"></el-input>
                </el-form-item>

                <el-form-item label="收款凭证" :label-width="formLabelWidth">
                    <img v-if="chk_form.upload_image != ''" :src="chk_form.upload_image" />
                </el-form-item>
                
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="closeChkForm">关 闭</el-button>
            </div>
        </el-dialog>

        <!-- 拒绝处理 -->
        <el-dialog title="拒绝处理" width="50%" center :show-close="false" :visible.sync="dialogFormVisibleDeny">
            <el-form :model="deny_form">

            	<el-form-item label="拒绝原因" :label-width="formLabelWidth">
                    <el-input type="textarea" :rows="10" v-model="deny_form.order_note" autocomplete="off"></el-input>
                </el-form-item>
                
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="closeDenyForm">取 消</el-button>
                <el-button type="primary" @click="subDenyProcess">拒 绝</el-button>
            </div>
        </el-dialog>

        <!--到帐上分处理-->
        <el-dialog title="到帐上分处理" width="50%" center :show-close="false" :visible.sync="dialogFormVisibleProcess">
            <el-form :model="process_form">

            	<el-form-item label="用户ID" :label-width="formLabelWidth">
                    <el-input disabled v-model="process_form.uid" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="用户昵称" :label-width="formLabelWidth">
                    <el-input disabled v-model="process_form.nickname" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="订单号" :label-width="formLabelWidth">
                    <el-input disabled v-model="process_form.order_id" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="充值金额" :label-width="formLabelWidth">
                    <el-input disabled v-model="process_form.money" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="上分BABY币" :label-width="formLabelWidth">
                    <el-input disabled v-model="process_form.baby_amount" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item label="操作备注" :label-width="formLabelWidth">
                    <el-input v-model="process_form.order_note" autocomplete="off"></el-input>
                </el-form-item>

                <el-form-item label="收款凭证" :label-width="formLabelWidth">
                    <el-upload
                            ref="upload"
                            action="/trade/process_bank_payment_form"
                            list-type="picture-card"
                            :limit="pic_limit"
                            :disabled="disabled"
                            :on-preview="handlePictureCardPreview"
                            :on-exceed="handleExceedPic"
                            :on-success="fileSuccess"
                            :on-error="fileFail"
                            :on-change="handlefileChange"
                            :auto-upload="false"
                            :data="post_data"
                            :on-remove="handleRemove">
                        <i class="el-icon-plus"></i>
                    </el-upload>
                    <el-dialog :visible.sync="dialogVisible">
                        <img width="100%" :src="dialogImageUrl" alt="">
                    </el-dialog>
                </el-form-item>
                
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="cancelProcess">取 消</el-button>
                <el-button type="primary" @click="subProcess">确 定</el-button>
            </div>
        </el-dialog>

    </div>
</template>


<script>
    export default {
        data() {
            return {
                loading: false,
                uid: "",
                operator: "",
                mobile: "",
                payment_username: "",
                payment_bankcard_no: "",
                status: '',
                order_id: '',
                transfer_time: [],
                process_time: [],

                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 1,
                summary: [],
                totalCharge: [],
                channels: [],
                channel: "",
                apiHost: "",

                // 到帐处理表单显示
                dialogFormVisibleProcess: false,
                process_form: {
                	baby_amount: '',
                	order_note: '',
                    money: '',
                    order_id:'',
                },

                // 查看表单
                dialogFormVisibleCheck: false,
                chk_form: {},

                // 拒绝表单
                dialogFormVisibleDeny: false,
                deny_form: {},

                //上传图片
                pic_limit: 1,//限制张数
                disabled: false,
                dialogVisible: false,
                dialogImageUrl: '',
                post_data: {},
            };
        },
        created() {
            this.getData();
        },
        methods: {
            clear() {
                this.uid = "";
                this.operator = "";
                this.mobile = "";
                this.payment_username = "";
                this.payment_bankcard_no = "";
                this.channel = "";
                this.status = "";
                this.order_id = "";
                this.transfer_time = [];
                this.process_time = [];
            },
            handleCurrentChange(val) {
                this.page = val;
                this.getData();
            },
            // search = 1要将page重置为1
            getData(search = 0) {
                this.loading = true;
                if (search == 1) {
                    this.page = 1;
                }
                this.$http
                    .post("/trade/trade_deposit_bank", {
                        page: this.page,
                        search: search,

                        uid: this.uid,
		                operator: this.operator,
		                mobile: this.mobile,
		                payment_username: this.payment_username,
		                payment_bankcard_no: this.payment_bankcard_no,
		                channel: this.channel,
		                status: this.status,
		                order_id: this.order_id,
		                transfer_time: this.transfer_time,
		                process_time: this.process_time,
                    })
                    .then(res => {
                        this.loading = false;
                        this.tableData = res.data.data;
                        this.page = res.data.page;
                        this.total = res.data.total;
                        this.page_size = res.data.page_size;
                        this.page_count = res.data.page_count;
                        this.summary = res.data.summary;
                        
                        if (search == 1 && res.status == 1) {
                            window.open(`${window.location.origin}${res.data}`);
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
             * 显示到帐上分
             * @param row
             */
            handleProcessClick(row) {
                this.process_form = row;
                // this.process_form.baby_amount = '';
                this.process_form.order_note = '';
                this.dialogImageUrl = '';
                this.dialogFormVisibleProcess = true;
            },
            /**
             * 显示查看表单
             * @param row
             */
            handleCheckClick(row) {
            	this.chk_form = row;
                // this.process_form.baby_amount = '';
                // this.process_form.order_note = '';
                this.dialogFormVisibleCheck = true;
            },
            /**
             * 显示拒绝表单
             * @param row
             */
            handleDenyClick(row) {
            	this.deny_form = row;
                // this.process_form.baby_amount = '';
                // this.process_form.order_note = '';
                this.dialogFormVisibleDeny = true;
            },
            /**
             * 提交到帐上分表单
             * @param row
             */
            subProcess() {
                // console.log(this.process_form.row);
                if (!this.process_form.money ) {
                    this.$message.error("请输入上分金额");
                    return false;
                }

                this.post_data = {
                    // usercode: this.usercode,
                    usercode: localStorage.getItem('usercode'),
                    baby_amount: this.process_form.baby_amount,
                    order_note: this.process_form.order_note,
                    id: this.process_form.id,
                    uid: this.process_form.uid,
                    money: this.process_form.money,
                    order_id: this.process_form.order_id,
                };

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
            },
            /**
             * 提交拒绝表单
             * @param row
             */
            subDenyProcess() {

                this.loading = true;

                if (!this.deny_form.order_note ) {
                    this.$message.error("请输入拒绝理由");
                    return false;
                }
                this.$http

                    .post("/trade/deny_bank_payment_form", {

                        page: this.page,

                        id: this.deny_form.id,
		                order_note: this.deny_form.order_note,
                    })
                    .then(res => {
                        this.loading = false;
                        this.dialogFormVisibleDeny = false;
                        this.getData();
                    });
            },
            /**
             * 取消到帐上分处理
             */
            cancelProcess() {
                this.process_form = {};
                this.dialogFormVisibleProcess = false;
            },
            /**
             * 关闭查看表单
             */
            closeChkForm() {
				this.chk_form = {};
                this.dialogFormVisibleCheck = false;
            },
            /**
             * 关闭拒绝表单
             */
            closeDenyForm() {
				this.deny_form = {};
                this.dialogFormVisibleDeny = false;
            },
            /**
             * 上传图片超过个数
             */
            handleExceedPic() {
                this.$message.error('最多只能上传' + this.pic_limit + '张打款凭证图片');
            },

            handleRemove(file, fileList) {
                setTimeout(() => {
                    console.log(document.getElementsByClassName('el-upload')[0])
                    document.getElementsByClassName('el-upload')[0].classList.remove('hidden')
                }, 300)
            },
            handlePictureCardPreview(file) {
                console.log('方大')
                this.dialogImageUrl = file.url;
                this.dialogVisible = true;
            },
            handlefileChange(file, fileList) {
                console.log(fileList)
                if (fileList.length === this.pic_limit) {
                    setTimeout(() => {
                        console.log(document.getElementsByClassName('el-upload')[0])
                        document.getElementsByClassName('el-upload')[0].classList.add('hidden')
                    }, 300)

                }
            },
            /**
             * 图片和信息上传成功
             */
            fileSuccess(response) {

                if (response.status === 1) {
                    this.$message({
                        type: "success",
                        message: '处理成功'
                    });
                    this.form = {
                        id: 0,
                        name: ''
                    };
                    this.dialogFormVisible = false;
                    this.cancelProcess();
                    this.getData();
                } else {
                    this.$message({
                        type: "error",
                        message: '处理失败'
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
            
        }
    };
</script>

<style lang="scss" scoped>
    .rechargelist {
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
        .operation .el-button {
            margin-bottom: 10px;
        } 
    }
</style>



