<template>
  <div class="page_tips" v-show="show">{{info}}</div>
</template>

<script>
  export default {
    name: 'error-tip',
    vuex: {
      getters: {
        show: state => state.error.show,
        info: state => state.error.info
      }
    }
  }
</script>
