<template>
    <div class="rechargelist">
        <div class="whiteBg">
            <div class="screen">
                <label style="margin-left: 20px">{{$t("money.type")}}</label>&nbsp;
                <el-select
                        :placeholder='$t("money.placeholdertype")'
                        v-model="search_type"
                        @keyup.enter.native="getData"
                >
                    <el-option :label='$t("common.Username")' value="uid"></el-option>
                    <el-option :label='$t("money.number")' value="number"></el-option>
                    <el-option :label='$t("money.order")' value="order"></el-option>
                </el-select>
                <el-input
                        style="width: 200px"
                        id="inputID"
                        v-model="search_info"
                        @keyup.enter.native="getData"
                ></el-input>
                <label style="margin-left: 20px">请选择充值渠道商</label>
                <el-select
                        clearable
                        style="margin-left: 10px;"
                        placeholder='充值渠道商'
                        v-model="merchant"
                        @keyup.enter.native="getData"
                >
                    <el-option label='所有渠道商' value=""></el-option>
                    <el-option v-for="(item,index) in merchants" :key="index" :value="item.third_party_name"
                               :label="item.third_party_name" ></el-option>
                </el-select>
                <label style="margin-left: 20px">请选择渠道名称</label>
                <el-select
                        clearable
                        style="margin-left: 10px;"
                        placeholder='渠道名称'
                        v-model="channel"
                        @keyup.enter.native="getData"
                >
                    <el-option label='所有渠道名称' value=""></el-option>
                    <el-option v-for="(item,index) in channels" :key="index" :value="item.id"
                               :label="item.channel_name"></el-option>
                </el-select>
            </div>
            <div class="screen">
                <label style="margin-left: 20px">请选择充值类型</label>&nbsp;
                <el-select placeholder v-model="type" @keyup.enter.native="getData">
                    <el-option label='所有类型' value=""></el-option>
                    <el-option label='支付宝' value="ALIPAY"></el-option>
                    <el-option label='微信' value="WECHAT"></el-option>
                    <el-option label='网银' value="BANKCARD"></el-option>
                    <el-option label='话费' value="CREDIT"></el-option>
                </el-select>
                <label style="margin-left: 20px">{{$t("common.status")}}</label>&nbsp;
                <el-select placeholder v-model="order_status" @keyup.enter.native="getData">
                    <el-option label='所有状态' value=""></el-option>
                    <el-option :label='$t("money.success1")' value="success"></el-option>
                    <el-option :label='$t("money.doing")' value="doing"></el-option>
                    <el-option :label='$t("money.error1")' value="error"></el-option>
                </el-select>
                <label>{{$t("common.Creation_time")}}</label>&nbsp;
                <el-date-picker
                        v-model="seach_time"
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
        <!-- <div style="background-color: #33ccff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            充值人數(日/總)<br/>{{sumary.user_day_dps}} / {{sumary.user_all_dps}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            充值金额(日/总)<br/>{{sumary.user_day_money}} / {{sumary.user_all_money}}
        </div> -->

        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            充值笔数<br/>{{sumary.total_order}}
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            成功订单数/成功订单率<br/>{{sumary.total_paid}} / {{sumary.total_rate}}%
        </div>
        <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;">
            充值总金额<br/>{{sumary.total_money}}
        </div>

       <!--  <div style="background-color: #0099ff; width: 200px; margin: 10px; text-align: center; padding: 24px; line-height: 20px; float: left; color: #fff;"
             v-for="(item,index) in totalCharge" :key="index">渠道{{item.pay_channel}}(日/总)<br/>{{item.todayCharge}} /
            {{item.totalCharge}}
        </div> -->

        <div class="whiteBg">
            <div class="fr">
                <el-button @click="clear">{{$t("common.clear")}}</el-button>
                <el-button @click="getData(1)"> {{$t("common.export")}} </el-button>
            </div>
            <div class="tableCon">
                <el-table
                        ref="multipleTable"
                        :data="tableData"
                        v-loading="loading"
                        border
                        style="width: 100%;margin-top: 30px"
                >
                    <el-table-column :label='$t("common.Username")' align="center" prop="idname"></el-table-column>
                    <el-table-column :label='$t("money.deposit")' align="center" prop="money"></el-table-column>
                    <el-table-column :label='$t("money.merchantName")' align="center" prop="third_party_name"></el-table-column>
                    <el-table-column :label='$t("user_management.channel_name")' align="center" prop="channel_name"></el-table-column>
                    <el-table-column :label='$t("money.type")' align="center" prop="type"></el-table-column>
                    <el-table-column :label='$t("money.order")' align="center" prop="order_id"></el-table-column>
                    <el-table-column :label='$t("money.txn_hash")' align="center" prop="transact_id"></el-table-column>
                    <el-table-column :label='$t("common.Creation_time")' align="center" prop="order_time"></el-table-column>
                    <el-table-column :label='$t("money.pay_time")' align="center" prop="pay_time"></el-table-column>

                    <!-- <el-table-column :label='$t("money.number")' align="center" prop="username"></el-table-column> -->
                    <el-table-column :label='$t("common.status")' align="center" prop="status">
                        <template slot-scope="scope">
                            <span v-if="scope.row.status==0" style="color:#66b1ff">{{$t("money.doing")}}</span>
                            <span v-if="scope.row.status==1" style="color: green;">{{$t("money.success")}}</span>
                            <span v-if="scope.row.status==2" style="color:red">{{$t("money.error")}}</span>
                        </template>
                    </el-table-column>
                    <!-- <el-table-column :label='$t("money.addr_balance")' align="center" prop="surplus"></el-table-column> -->
                    <el-table-column :label='$t("common.operation")' align="center" min-width="130px" class-name="operation">
                        <template slot-scope="scope">
                          <el-button type="primary" @click="manualCallback( scope.row )">{{$t("money.manualCallback")}}</el-button>
                        </template>
                      </el-table-column>
                </el-table>
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
    </div>
</template>


<script>
    export default {
        data() {
            return {
                loading: false,
                search_type: "",
                search_info: "",
                price_type: "",
                tableData: [],
                order_status: "",
                seach_time: [],
                page: 1,
                limit: this.$store.state.adminPageSize,
                total: 1,
                sumary: [],
                totalCharge: [],
                channels: [],
                channel: "",
                merchants:[],
                merchant:'',
                apiHost: "",
                type:'',
                apiUrl: ''
            };
        },
        created() {
            this.getData();
            this.getServerUrl();
        },
        methods: {
            clear() {
                this.search_type = "";
                this.search_info = "";
                this.price_type = "";
                this.order_status = "";
                this.seach_time = [];
                this.channel = "";
                this.merchant = '';
                this.type = '';
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
                    .post("/trade/trade_deposit", {
                        page: this.page,
                        search_info: this.search_info,
                        price_type: this.price_type,
                        order_status: this.order_status,
                        start_time: this.seach_time[0],
                        end_time: this.seach_time[1],
                        search_type: this.search_type,
                        channel: this.channel,
                        merchant: this.merchant,
                        type: this.type,
                        search: search,
                    })
                    .then(res => {
                        this.loading = false;
                        this.tableData = res.data.data;
                        this.page = res.data.page;
                        this.total = res.data.total;
                        this.sumary = res.data.sumary;
                        this.totalCharge = res.data.totalCharge;
                        this.channels = res.data.channels;
                        this.merchants = res.data.merchants;

                        if (search == 1 && res.status == 1) {
                            window.open(`${window.location.origin}${res.data}`);
                        }
                    });
            },
            getServerUrl() {
                this.$http.post("index/get_server_url")
                .then(res => {
                    this.apiUrl = res.data.url
                })
            },
            manualCallback(row) {
                console.log(row)
                if(!row.callback_route) {
                    alert("未配置回調接口")
                }
                else {
                    this.$http.post(this.apiUrl+"/h5/"+row.callback_route, {
                        order_id: row.order_id
                    })
                    .then(res => {
                        if(res.status == 1) {
                            alert('回調成功')
                        }
                        this.getData()
                    })
                }
                

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
    }
</style>



