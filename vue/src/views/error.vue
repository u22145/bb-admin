<template>
  <div class="error">
    <div>
      <span class="iconfont icon-gantanhao"></span>
      <span class="iconfont icon-wenhao-"></span>
      <h1>您没有访问权限,{{num}} 秒后跳回登录页...</h1>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      num: 3
    };
  },
  created() {
    let time = setInterval(() => {
      if (this.num <= 0) {
        clearInterval(time);
        this.$router.push("/login");
      } else {
        this.num--;
      }
    }, 1000);
  }
};
</script>

<style lang="scss" scoped>
.error {
  div {
    width: 600px;
    margin: 200px auto;
    color: rgb(226, 108, 108);
    span {
      font-size: 80px;
    }
    h1 {
      margin-left: 200px;
      font-size: 25px;
    }
  }
}
</style>
