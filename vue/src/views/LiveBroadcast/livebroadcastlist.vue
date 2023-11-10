<template>
  <div class="livebroadcastlist">
    <div class="whiteBg">
      <div class="screen">
        <div class="el-row">
          <label for="selectUserID">{{$t("LabourUnion.AnchorID")}}</label>&nbsp;
          <el-input
            style="width: 200px"
            id="selectUserID"
            v-model="selectData.id"
            @keyup.enter.native="getData(1)"
          ></el-input>
          <label for="nickname">{{$t("common.nickname")}}</label>&nbsp;
          <el-input
            style="width: 200px"
            id="nickname"
            v-model="selectData.nickname"
            @keyup.enter.native="getData(1)"
          ></el-input>
          <label for="guild">所属公会</label>
          <el-input
            style="width: 200px"
            id="guild"
            v-model="selectData.guild"
            @keyup.enter.native="getData(1)"
          ></el-input>

          <label>游戏直播</label>
          <el-select v-model="isGame" placeholder="请选择">
              <el-option
              v-for="item in gameOption"
              :key="item.value"
              :label="item.label"
              :value="item.value">
              </el-option>
          </el-select>
        </div>
        <div class="el-row">
          <label>热门</label>
          <el-select v-model="isPopular" placeholder="请选择">
              <el-option
              v-for="item in popularOption"
              :key="item.value"
              :label="item.label"
              :value="item.value">
              </el-option>
          </el-select>
            <label>{{$t("LiveBroadcast.Livebroadcasttime")}}</label>&nbsp;
              <el-date-picker
                v-model="selectData.times"
                type="datetimerange"
                :range-separator='$t("common.to")'
                :start-placeholder='$t("common.start_time")'
                :end-placeholder='$t("common.end_time")'
                value-format="yyyy-MM-dd HH:mm:ss"
              ></el-date-picker>
        </div>
        <el-button type="primary" class="fr" style="padding: 8px 30px" @click="search()">{{$t('common.search')}}</el-button>
      </div>
    </div>
    <!-- 列表 -->
    <div class="whiteBg">
      <div class="fr">
        <el-button @click="clear">{{$t("common.clear")}}</el-button>
        <!-- <el-button @click="getData(2)">{{$t("common.export")}}</el-button> -->
      </div>
      <div class="tableCon" style="margin-top:50px" v-loading="loading">
                  <el-table
              :data="tableData"
              v-loading="loading"
              border
              style="width: 99%;margin-top: 30px">
              <el-table-column type="index" align="center" ></el-table-column>
              <el-table-column :label='$t("common.Username")' align="center" prop="uid"></el-table-column>
              <el-table-column label='主播ID' align="center" prop="roomId"></el-table-column>
              <el-table-column label='主播分类' align="center" prop="catName"></el-table-column>
              <el-table-column label='主播昵称' align="center" prop="nickname"></el-table-column>
              <el-table-column label='直播标题' align="center" prop="title"></el-table-column>
              <el-table-column label='所属公会' align="center" prop="guildName"></el-table-column>
              <el-table-column label='开播时间' align="center" prop="startedAt"></el-table-column>
              <el-table-column label='开播时长' align="center" prop="duration"></el-table-column>
              <el-table-column label='房间人气' align="center" prop="viewers"></el-table-column>
              <el-table-column label='房间收益' align="center" prop="turnover"></el-table-column>
              <el-table-column label='房间热度' align="center" prop="popularity"></el-table-column>
              <el-table-column label='游戏直播' align="center" prop="isGameLS">
                <template slot-scope="scope">
                  <div>
                    {{ scope.row.isGameLS == 0 ? "否" : "是" }}
                  </div>
                </template>
              </el-table-column>
              <el-table-column label='热门' align="center" prop="isPopular">
                <template slot-scope="scope">
                  <div>
                      <el-switch
                        v-model="scope.row.isPopular"
                        active-color="#13ce66"
                        inactive-color="#ff4949"
                        @change="switchChange($event, scope.row)"
                        >
                      </el-switch>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label='操作' align="center" width="130px" class-name="operation">
                <template slot-scope="scope">
                  <el-button
                    type="text"
                    size="small"
                    @click="showLiveVideo(scope.row.uid)"
                  >{{ "觀看直播" }}</el-button>
                </template>
              </el-table-column>
            </el-table>
        <!-- <el-row style="margin-bottom:20px">
          <el-col
            style="margin-bottom:20px"
            :span="6"
            v-for="(item, index) in tableData"
            :key="index"
            :offset="index > 0 ? 3 : 0"
            :push="2"
            v-bind:style="{margin:'15px 15px'}"
          > -->

            <!-- <el-card :body-style="{ padding: '0px' }">
              <div
                :id="get_video_id(item.uid)"
                class="video"
                :style="live_video_style(item.live_cover)"
              ></div>
              <div style="padding: 14px;">
                <p>
                  {{$t("LabourUnion.AnchorID")}}：
                  <span>{{item.uid}}</span>
                </p>
                <p>
                  {{$t("common.nickname")}}：
                  <span>{{item.nickname}}</span>
                </p>
                <p>
                  {{$t("LiveBroadcast.topic")}}：
                  <span>{{item.topic}}</span>
                </p>
                <p>
                  {{$t("LiveBroadcast.time")}}：
                  <span>{{item.startedAt}}</span>
                </p>
                <p>
                  {{$t("LiveBroadcast.Numberviewers")}}：
                  <span>{{item.viewers}}</span>
                </p>
                <p>
                  {{$t("LiveBroadcast.NumberviewersNum")}}：
                  <span>{{item.viewers}}</span>
                </p>
                <p>
                  {{$t("LiveBroadcast.NumOfGifts")}}：
                  <span>{{item.turnover}}</span>
                </p>
                <p>
                  {{$t("LiveBroadcast.ValueOfGifts")}}：
                  <span>{{item.gift}}</span>
                </p>
                <div class="bottom clearfix">
                  <el-button type="text" class="button" @click="showLiveVideo(item.uid)">觀看直播</el-button>
                  <el-button type="text" class="button" @click="starAnchor(item.uid)">
                    <span v-if="item.star==0">置頂</span>
                    <span v-if="item.star==1" style="color:red">取消置頂</span>
                  </el-button>
                </div>
              </div>
            </el-card> -->
          <!-- </el-col>
        </el-row> -->
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
    <!-- 警告弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible1">
      <el-form :model="form">
        <el-form-item :label='$t("LiveBroadcast.warningreason")'>
          <el-input type="textarea" v-model="form.name"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible1 = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="liveWarning">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>

    <!-- 禁播弹窗 -->
    <el-dialog  :visible.sync="dialogFormVisible">
      <el-form :model="form">
        <el-form-item :label='$t("LiveBroadcast.Nobroadcastingreason")' :label-width="formLabelWidth">
          <el-input type="textarea" v-model="form.reason" autocomplete="off"></el-input>
        </el-form-item>
        <el-form-item :label='$t("LiveBroadcast.NobroadcastingNum")' :label-width="formLabelWidth">
          <el-select v-model="form.region" placeholder>
            <el-option :label='$t("LiveBroadcast.oneDay")' value="0"></el-option>
            <el-option :label='$t("LiveBroadcast.permanent")' value="1"></el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t("confirm.cancel")}}</el-button>
        <el-button type="primary" @click="dialogFormVisible = false">{{$t("confirm.ok")}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>


<script>
import AgoraRTM from "agora-rtm-sdk";
import AgoraRTC from "agora-rtc-sdk";
import axios from 'axios'
export default {
  data() {
    return {
      loading: false,
      selectData: {
        id: "",
        nickname: "",
        live_status: "1",
        forbidden_status: "",
        times: [],
        guild: ""
      },
      tableData: [],
      dialogFormVisible: false, //禁播
      dialogFormVisible1: false, //警告
      form: {
        name: "",
        region: "",
        reason: ""
      },
      formLabelWidth: "120px",
      page: 1,
      limit: this.$store.state.adminPageSize,
      total: 1,
      room_id: "",
      rtc_client: null,
      uid: "",
      guild: "",
      anchorCat: "",
      isGame: null,
      isPopular: null,
      gameOption: [
        {label: "空", value: 0},
        {label: "是", value: 1}
      ],
      popularOption: [
        {label: "否", value: 0},
        {label: "是", value: 1}
      ],
    };
  },
  created() {
    this.getData(1);
  },
  methods: {
    search() {
      this.page = 1;
      this.getData(1);
    },
    clear() {
      this.selectData.id = "";
      this.selectData.nickname = "";
      this.selectData.live_status = "1";
      this.selectData.forbidden_status = "";
      this.selectData.times = [];
    },
    get_video_id(room_id) {
      return "live_video_" + room_id;
    },
    live_video_style(cover_url) {
      return (
        "height:250px;background-size: cover;background-position: center;background-repeat: no-repeat;background-image:url('" +
        cover_url +
        "')"
      );
    },

    handleCurrentChange(val) {
      this.page = val;
      this.getData(1);
    },
    // 进入房间
    enterRoom(self, rtc_token) {
      var appId = "49b54c8ee3a940ecb88476fb3d7959e7";
      self.rtc_client.init(
        appId,
        function(res) {
          // 加入之前，设置角色为观众
          self.rtc_client.setClientRole("audience");
          // 加入频道
          self.rtc_client.join(
            rtc_token,
            self.room_id,
            null,
            function(_uid) {
              console.log(_uid + " Joined~~!~!");
            },
            function(err) {
              console.error("client join failed ", err);
            }
          );
        },
        function(err) {
          console.log(err);
        }
      );
    },
    switchChange(status, data) {
      if(status) {
        axios.post(process.env.VUE_APP_API_URL+'ls/popular', {
          roomId: data.roomId,
          isAnchor: 1
        })
      }
      else {
        axios.delete(process.env.VUE_APP_API_URL+'ls/popular/'+data.roomId, {
        })     
      }
    },
    // 显示直播
    showLiveVideo(uid) {
      this.room_id = uid.toString();
      var self = this;
      //获取TOKEN
      this.$http
        .post("/live/get_rtc_token", {
          room_id: this.room_id
        })
        .then(res => {
          var rtc_token = res.data.rtc_token;
          if (self.rtc_client === null) {
            self.rtc_client = AgoraRTC.createClient({
              mode: "live",
              codec: "vp8"
            });
          }
          if (self.rtc_client.getConnectionState() != "DISCONNECTED") {
            self.rtc_client.leave(
              function() {
                console.log("client leaves channel");
                self.enterRoom(self, rtc_token);
              },
              function(err) {
                console.log("client leave failed ", err);
              }
            );
          } else {
            self.enterRoom(self, rtc_token);
          }

          // 监听流的加入
          self.rtc_client.on("stream-added", function(evt) {
            console.log("stream-added");
            let stream = evt.stream;
            // 订阅流
            self.rtc_client.subscribe(stream, function(err) {
              console.log("Subscribe stream failed", err);
            });
          });

          // 监听订阅流
          self.rtc_client.on("stream-subscribed", function(evt) {
            console.log("stream-subscribed");
            var stream = evt.stream;
            console.log("new stream subscribed ", stream.getId());
            // 播放
            stream.play(
              "live_video_" + self.room_id,
              { fit: "contain" },
              function(errState) {
                console.log("errStat ===>", errState);
                if (errState && errState.status !== "aborted") {
                  // 播放失败，一般为浏览器策略阻止。引导用户用手势触发恢复播放。
                }
                console.log("isPlaying", stream.isPlaying());
              }
            ); // stream will be played in the element with the ID agora_remote
          });

          // 监听连接
          self.rtc_client.on("connected", function(res) {
            console.log("connected:", res);
          });

          // 监听连接状态改变
          self.rtc_client.on("connection-state-change", function(res) {
            console.log("connection-state-change:", res);
          });
        });
    },
    //1：获取列表 2：导出
    getData(exps = 1) {
      this.loading = true;
      this.$httpJava
        .get("ls/", {
          params: {
            uid:this.selectData.id,
            roomid: this.selectData.id,
            guild: this.guild,
            nickname: this.selectData.nickname,
            anchorCat: this.anchorCat,
            isGame: this.isGameLS,
            isPopular: this.isPopular
          }
        })
        .then(res => {
          this.loading = false;
          if (exps == 2) {
            if (res.data.status == 1) {
              window.open(`${window.location.origin}${res.data}`);
            } else {
              this.$message.error(res.data.msg);
            }
          } else {
            if (res.data.status === 1) {
              this.tableData = res.data.data;
              this.page = res.data.page;
              this.total = res.data.total;
            } else {
              this.tableData = [];
            }
          }
        })
    },
      starAnchor(uid) {
          this.$http
              .post("/live/star", {
                  uid: uid
              })
              .then(res => {
                  this.$message({
                      message: res.msg,
                      type: "success"
                  });
                  this.getData(1);
              });
      },
    // 断开直播
    breakOff(uid) {
      this.$http
        .post("/live/break_off", {
          uid: uid
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
        });
    },
    liveWarningAlert(uid) {
      this.dialogFormVisible1 = true;
      this.uid = uid;
    },
    // 警告
    liveWarning() {
      this.$http
        .post("/live/live_warning", {
          uid: this.uid,
          info: this.form.name
        })
        .then(res => {
          this.$message({
            message: res.msg,
            type: "success"
          });
          this.dialogFormVisible1 = false;
        });
    }
  }
};
</script>


<style lang="scss" scoped>
.livebroadcastlist {
  .time {
    font-size: 13px;
    color: #999;
  }
  .el-row {
    margin-bottom: 10px;
    label {
      margin-right: 10px;
    }
  }
  p {
    margin-bottom: 8px;
    span {
      display: inline-block;
      width: 65%;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }
  }
  .bottom {
    margin-top: 13px;
    line-height: 12px;
  }

  .button {
    padding: 0;
    float: right;
  }
  .el-button--text {
    margin-left: 10px;
  }
  .image {
    width: 100%;
    display: block;
  }

  .clearfix:before,
  .clearfix:after {
    display: table;
    content: "";
  }

  .clearfix:after {
    clear: both;
  }
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
  .el-row {
    > div:nth-child(1) {
      margin-left: 200px;
    }
  }
  .tableCon:after {
    content: "";
    display: block;
    clear: both;
  }
}
</style>


