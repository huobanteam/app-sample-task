<template>
  <div>
    <menu></menu>
    <dialog></dialog>
    <error-tip></error-tip>
    <router-view v-if="isRender"></router-view>
    <div class="page_loader" v-if="!isRender"><span class="loader"></span></div>
  </div>
</template>
<script>
import compareVersion from 'compare-versions'
import { getInitInfo } from 'src/vuex/actions'
import * as SDK from 'huoban-app-sdk'
import store from './vuex/store'
import Menu from 'components/common/menu'
import Dialog from 'components/common/dialog'
import ErrorTip from 'components/common/error-tip'

export default {
  replace: true,
  store,
  components: {
    Menu,
    Dialog,
    ErrorTip
  },
  vuex: {
    actions: {
      getInitInfo
    }
  },
  data() {
    return {
      isRender: false
    }
  },
  methods: {
    isVersionSupported(version) {
      if (SDK.isAndroid) {
        if (compareVersion(version, '2.3.4') <= 0) {
          return false
        }
      } else if (SDK.isIPad || SDK.isIPhone) {
        if (version <= 94) {
          return false
        }
      }

      return true
    }
  },
  ready() {
    this.client = SDK.client()
    this.client.on('error.connect', err => {
      console.log('error.connect', err)
      this.isRender = true
      this.$router.go({name: 'error', query: {type: 'error', routerName: 'home'}})
    })
    this.client.init(store.state.applicationId).then(ret => {
      this.isRender = true

      // 判断安卓、苹果支持任务应用的最低版本
      if (!this.isVersionSupported(ret.version)) {
        return this.$router.go({name: 'upgrade'})
      }

      if (ret.table && ret.ticket) {
        this.getInitInfo(ret)
      } else {
        this.$router.go({name: 'error', query: {type: 'error', routerName: 'home'}})
      }
    }).catch(err => {
      console.log('init failed', err)
      this.isRender = true
      this.$router.go({name: 'error', query: {type: 'error', routerName: 'home'}})
    })
  },

  route: {
    data: function(t) {
      t.next()
    }
  }
}
</script>

<style src='./assets/css/app_task.css'></style>
