<template>
  <div class="page_error">
    <div class="error_tips">
      <p><span>请升级云表格App到最新版本 {{version}} </span>
    </div>
  </div>
</template>
<script>
  import * as SDK from 'huoban-app-sdk'

  export default {
    name: 'upgrade',
    computed: {
      version() {
        if (SDK.isAndroid) {
          return '2.3.6'
        } else if (SDK.isIPad || SDK.isIPhone) {
          return 'version 2.3.4 build 100'
        }

        return ''
      }
    }
  }
</script>