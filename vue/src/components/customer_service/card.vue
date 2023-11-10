<script>
export default {
  data() {
    return {
      user: this.$store.state.user
    };
  },
  computed: {
    userInState() {
      return this.$store.state.user;
    }
  },
  watch: {
    userInState() {
      this.user = this.$store.state.user;
    }
  },
  methods: {
    onKeyup(e) {
      this.$store.commit("SET_FILTER_KEY", e.target.value);
    }
  }
};
</script>

<template>
  <div class="card">
    <header>
      <img class="avatar" width="40" height="40" :alt="user.nickname" :src="user.avatar" />
      <p class="name">{{user.nickname}}</p>
    </header>
    <footer>
      <input class="search" type="text" placeholder="search user..." @keyup="onKeyup" />
    </footer>
  </div>
</template>

<style scoped lang="less">
.card {
  padding: 12px;
  border-bottom: solid 1px #24272c;

  footer {
    margin-top: 10px;
  }

  .avatar,
  .name {
    vertical-align: middle;
  }
  .avatar {
    border-radius: 2px;
  }
  .name {
    display: inline-block;
    margin: 0 0 0 15px;
    font-size: 16px;
  }
  .search {
    padding: 0 10px;
    width: 86%;
    font-size: 12px;
    color: #fff;
    height: 30px;
    line-height: 30px;
    border: solid 1px #3a3a3a;
    border-radius: 4px;
    outline: none;
    background-color: #26292e;
  }
}
</style>