import * as SDK from 'huoban-app-sdk'

export default {
  install: function(Vue, options) {
    Vue.prototype.$client = SDK.client()
    /**
     * @param pathInfo Object - vue-router go 函数所用的路径对象
     * @param mobileOptions Object - 手机对链接响应的配置信息
     *                               {self: Boolean, // 在本窗口替换
     *                                ignore: Boolean, // 是否忽略
     *                                close: Boolean // 关闭窗口
     *                                }
     **/
    Vue.prototype.$redirect = function(pathInfo, mobileOptions = {self: false, ignore: false, close: false}) {
      if (SDK.isPC || mobileOptions.self) {
        router.go(pathInfo)
      } else {
        if (mobileOptions.close) {
          this.$client.closeWebPage()
          return
        }

        if (mobileOptions.ignore) {
          return
        }

        if (pathInfo.name === 'item-detail' || pathInfo === 'search-item-detail') {
          pathInfo.name = 'item'
        }

        let url = location.protocol + '//' + location.host + this.$route.router.stringifyPath(pathInfo)

        this.$client.openWebPage(url)
      }
    }
  }
}