<template>
  <div class="page_error">
    <img src="../assets/images/404.png" v-if='$route.query.type === "404"'/>
    <div class="error_tips" v-if='$route.query.type === "error"'>
      <img src="../assets/images/error.png" />
      <p><span>加载应用数据失败！</span><a href="#" @click.prevent='backToPrevRouter()'>重新加载</a></p>
    </div>
  </div>
</template>
<script>
  export default {
    name: 'error',
    methods: {
      backToPrevRouter() {
        this.$router.go({name: this.$route.query.routerName})
      }
    }
  }
</script>