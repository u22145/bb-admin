<template>
  <!-- 角色详情页 -->
  <div class="partdetails">
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
        <div class="conTitle">{{$t("commentlist.conTitle8")}}</div>
        <el-button
          icon="el-icon-arrow-left"
          class="fr"
          style="transform: translateY(-5px)"
          @click="goback"
        >{{$t("commentlist.goback")}}</el-button>
        <!-- 内容 -->
        <div class="detailed" :data="itemData">
          <div class="essentialInformation">
            <div class="part_con">
              <div>
                <div>{{$t("jurisdiction.roleID")}}:</div>
                <div>{{$t("jurisdiction.Role_name")}}:</div>
                <div>{{$t("jurisdiction.Role_Description")}}:</div>
                <div>{{$t("common.Creation_time")}}:</div>
                <div>{{$t("jurisdiction.Jurisdiction")}}:</div>
              </div>
              <div>
                <div>{{ itemData.role_id }}</div>
                <div>{{ itemData.role_name }}</div>
                <div>{{ itemData.desc }}</div>
                <div>{{ itemData.uptime }}</div>
                <div>
                  <el-tree
                    :data="poweritem"
                    node-key="id"
                    ref="tree"
                    :default-checked-keys="poweritemChecked"
                  ></el-tree>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: "partdetails",
  props: {
    status: null,
    id: null
  },
  data() {
    return {
      itemData: [],
      data: [],
      defaultProps: {
        children: "children",
        label: "power_name"
      },
      poweritem: [],
      poweritemChecked: []
    };
  },
  //监听事件
  watch: {
    status() {
      if (this.status == true) {
        this.getinfo()
        this.newGetInfo();
      }
    }
  },
  methods: {
    goback() {
      this.$emit("changeStatus", false);
    },
    //获取角色权限详情
    getinfo() {
      this.role_id = window.localStorage.getItem("role_id");
      this.$http
        .post("/system/show_role", {
          role_id: this.role_id
        })
        .then(res => {
          if (res.status != 1) {
            this.$message({
              type: "error",
              message: res.msg
            });
          } else {
            this.itemData = res.data.role_info;
            this.data = res.data.role_level;
          }
        });
    },
    newGetInfo() {
      this.role_id = window.localStorage.getItem("role_id");
      this.$http
        .post("/system/show_power", {
          role_id: this.role_id
        })
        .then(res => {
          this.poweritem = res.data.power_list;
          this.poweritemChecked = res.data.role_power;
              setTimeout(() => {
                let nodes = document.getElementsByClassName("el-tree-node")
                nodes
            }, 0)
        });
    },
    handleNodeClick(data) {
      console.log(data);
    }
  },
  mounted() {

  },
};
</script>

<style lang="scss">
.UserInfoContent {
  .el-dialog__body {
    width: 1200px;
    margin: 0 auto;
  }
  .el-tree-node__children .el-tree-node.is-focusable {
    display: none;
    &.is-checked {
      display: block;
    }
  }
  .detailed {
    margin: 40px 0;

    .screen {
      margin-top: 30px;
      .fr {
        margin-left: 20px;
      }
    }

    .part_con {
      > div {
        display: inline-block;
        vertical-align: top;
      }
      img {
        height: 100%;
      }
      > div:nth-child(1) {
        div {
          min-height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          // border: 1px solid #D9D9D9;
          // border-bottom: 0;
          // width:200px;
          padding: 0 10px;
          color: #4c4c4c;
          // background: #D9D9D9;
        }

        // div:last-child {
        // border-bottom: 1px solid #D9D9D9;
        // }
      }

      > div:nth-child(2) {
        div {
          min-height: 40px;
          line-height: 40px;
          box-sizing: border-box;
          // border: 1px solid #D9D9D9;
          // border-bottom: 0;
          // border-left: 0;
          width: 1000px;
          padding: 0 10px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        // div:last-child {
        // border-bottom: 1px solid #D9D9D9;
        // }
      }
    }
  }
}
</style>

