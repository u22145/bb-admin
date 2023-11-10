<template>
  <div class="transactiondetails">
    <!-- 交易详情 -->
    <el-dialog
      :visible.sync="status"
      :fullscreen="true"
      :modal-append-to-body="true"
      :append-to-body="true"
      lock-scroll
      :show-close="false"
      custom-class="UserInfoContent"
      :close-on-press-escape="false"
    >
      <div>
        <!-- 头部 -->
        <div class="conTitle">{{$t("commentlist.conTitle11")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->
        <div class="detailed">
          <div class="essentialInformation">
            <div style="margin:20px;font-size:18px">{{$t("commentlist.Transactioninformation")}}</div>
            <div class="ess_con">
              <div>
                <div>{{$t("money.Order_number")}}</div>
                <div>{{$t("common.status")}}</div>
                <div>{{$t("commentlist.UnitPrice")}}</div>
                <div>{{$t("money.num")}}</div>
                <div>{{$t("commentlist.Total")}}</div>
                <div>{{$t("money.parameter")}}</div>
              </div>
              <div>
                <div>{{datainfo.order_id}}</div>
                <div>
                  <template>
                    <span v-if="datainfo.status==0" style="color:green">{{$t("money.Unpaid")}}</span>
                    <span v-if="datainfo.status==1" style="color:red">{{$t("money.Paid")}}</span>
                    <span v-if="datainfo.status==2" style="color:red">{{$t("money.Completed")}}</span>
                    <span v-if="datainfo.status==3" style="color:red">{{$t("money.buyercancel")}}</span>
                    <span v-if="datainfo.status==4" style="color:red">{{$t("money.Sellercancel")}}</span>
                    <span v-if="datainfo.status==5" style="color:red">{{$t("money.Deleted")}}</span>
                  </template>
                </div>
                <div>{{datainfo.price}}</div>
                <div>{{datainfo.amount}}</div>
                <div>{{datainfo.money}}</div>
                <div>{{datainfo.postscript}}</div>
              </div>
            </div>
            <div style="margin:20px;font-size:18px">{{$t("commentlist.SellerInformation")}}</div>
            <div class="ess_con">
              <div>
                <div>{{$t("commentlist.Sellerccount")}}</div>
                <div>{{$t("money.payment_method")}}</div>
                <div>{{$t("money.account")}}</div>
                <div>{{$t("money.Payee")}}</div>
                <div>{{$t("money.bank")}}</div>
                <div>{{$t("money.branch")}}</div>
              </div>
              <div>
                <div>{{datainfo.account}}</div>
                <div>
                 {{datainfo.pay_title}}
                </div>
                <div>{{datainfo.account}}</div>
                <div>{{datainfo.payee}}</div>
                <div>{{datainfo.bank}}</div>
                <div>{{datainfo.branch}}</div>
              </div>
            </div>
            <div style="margin:20px;font-size:18px">{{$t("commentlist.BuyInformation")}}</div>
            <div class="ess_con">
              <div>
                <div>{{$t("commentlist.Buyeraccount")}}</div>
              </div>
              <div>
                <div>{{datainfo.buy_uid}}</div>
              </div>
            </div>
            <div style="margin:20px;font-size:18px">{{$t("commentlist.Otherinformation")}}</div>
            <div class="ess_con">
              <div>
                <div>{{$t("money.Remarks")}}</div>
              </div>
              <div>
                <div>{{datainfo.memo}}</div>
              </div>
            </div>
            <div style="margin:20px;font-size:18px">{{$t("commentlist.Evidenceinformation")}}</div>
            <div class="ess_con">
              <div>
                <div class="spac">{{$t("commentlist.Evidenceinformation")}}</div>
              </div>
              <div>
                <div class="spac">
                  <img
                    v-for="(son,o) in datainfo.pic_url"
                    :key="o"
                    :src="son"
                    style="margin-right:5px"
                  />
                </div>
              </div>
            </div>
            <!-- <div class="screen clear">
              <el-button type="primary" class="fr" style="padding: 8px 30px">{{$t('main.ok')}}</el-button>
            </div> -->
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: "transactiondetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      datainfo: {}
    };
  },
  //监听事件
  watch: {
    status() {
      this.getdetaile();
    }
  },
  methods: {
    goback() {
      this.$emit("changeStatus", false);
    },
    // 获取详情
    getdetaile() {
      this.$http
        .post("/trade/order_info", {
          id: this.id
        })
        .then(res => {
          this.datainfo = res.data;
        });
    }
  }
};
</script>

<style lang="scss">
.UserInfoContent {
  .el-dialog__body {
    width: 1200px;
    margin: 0 auto;
  }

  .detailed {
    margin: 40px 0;
    .screen {
      margin-top: 30px;
      .fr {
        margin-left: 20px;
      }
    }
    .ess_con {
      > div {
        display: inline-block;
        vertical-align: top;
      }
      img {
        height: 100%;
      }
      > div:nth-child(1) {
        div {
          height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          border: 1px solid #d9d9d9;
          border-bottom: 0;
          width: 200px;
          padding: 0 10px;
          color: #4c4c4c;
          background: #d9d9d9;
        }
        div:last-child {
          border-bottom: 1px solid #d9d9d9;
        }
      }
      > div:nth-child(2) {
        div {
          height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          border: 1px solid #d9d9d9;
          border-bottom: 0;
          border-left: 0;
          width: 1000px;
          padding: 0 10px;
        }
        div:last-child {
          border-bottom: 1px solid #d9d9d9;
        }
      }
    }
  }
   .spac {
    text-indent: 10px !important;
    height: 200px !important;
    padding: 10px 0 !important;
  }
  .spac img {
    max-height: 200px !important;
  }
}
</style>

