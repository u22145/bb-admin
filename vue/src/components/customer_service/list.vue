<script>
import { mapMutations } from "vuex";

export default {
  data() {
    return {
      page: 2,
      pageLoading: false,
      currentId: this.$store.state.currentSessionId,
      sessions: this.$store.state.sessions
    };
  },
  computed: {
    currentSessionId() {
      return this.$store.state.currentSessionId;
    }
  },
  methods: {
    ...mapMutations({
      selectSession: "SELECT_SESSION"
    })
  },
  mounted() {
    var self = this;
    console.log("mounted", this.$refs);
    this.$refs.userList.addEventListener(
      "scroll",
      e => {
        let distToBottom =
          e.target.scrollHeight - e.target.scrollTop - e.target.clientHeight;
        if (distToBottom < 1 && !self.pageLoading) {
          self.pageLoading = true;
          self.$store.getChatUserList(self.$store, this.page);
          this.page++;
          self.pageLoading = false;
        }
      },
      false
    );
  },
  watch: {
    currentSessionId() {
      var self = this;
      this.currentId = this.$store.state.currentSessionId;
      self.$store.commit("GET_PM_DETAIL");
    }
  }
};
</script>

<template>
  <div class="list">
    <ul ref="userList">
      <li
        v-for="item in sessions"
        :key="item.id"
        :class="{ active: item.id === currentId }"
        @click="selectSession(item.id)"
      >
        <img
          class="avatar"
          width="30"
          height="30"
          :src="item.user.img || '/static/img/default_avatar.png'"
        />
        <div class="user-info">
          <div style="display: flex;justify-content: space-between;">
            <p class="name">{{item.user.name}}{{item.user.online ? " (在线)" : ""}}</p>
            <p class="times">{{item.user.times}}</p>
          </div>
          <p class="msg">{{item.user.msg.substr(0, 8)}}</p>
        </div>
        <p v-if="item.user.newNum" class="new_nums">{{item.user.newNum}}</p>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="less">
::-webkit-scrollbar {
  width: 0;
  height: 0;
}
.list {
  height: 100%;
  ul {
    height: calc(100% - 106px);
    overflow-y: auto;
  }
  li {
    position: relative;
    padding: 12px 15px;
    border-bottom: 1px solid #292c33;
    cursor: pointer;
    transition: background-color 0.1s;
    display: flex;
    justify-content: space-between;

    &:hover {
      background-color: rgba(255, 255, 255, 0.03);
    }
    &.active {
      background-color: rgba(255, 255, 255, 0.1);
    }
    .user-info {
      width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    > p {
      padding: 0 15px;
    }
    }
  }
  .avatar,
  .name {
    vertical-align: middle;
  }
  .avatar {
    border-radius: 2px;
  }
  .name {
    display: inline;
    margin: 0 0 0 15px;
  }
  .new_nums {
    position: absolute;
    top: 10px;
    right: 10px;
    height: 10px;
    min-width: 10px;
    background-color: red;
    padding: 5px;
    border-radius: 10px;
    line-height: 10px;
    text-align: center;
  }
}
</style>