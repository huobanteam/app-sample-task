<template>
  <div>
    <template v-if='items.length > 0'>
      <p v-for='item of items' track-by='hash'>
        <span class="file">
          <a href="#">{{item.file.name}}</a>
          <em class="iconload"></em>
        </span>
<!--         <span class="file progress">
          <a href="#">{{item.file.name}}</a><i class="del">&#xe910;</i>
          <span class="progressbar">
            <span class="len" :style="{width: item.progress + '%'}"></span>
          </span>
        </span> -->
      </p>
    </template>
    <form style='display: none' @submit.prevent v-el:form>
      <input type='file' @change='handleFileChoose' v-el:file/>
    </form>
    <p>
      <span @click='handleSelectClick' v-if='!loading'>
        <slot></slot>
      </span>
    </p>
  </div>
</template>

<script>
import $ from 'zepto'
import _ from 'lodash'
// import Vue from 'vue'
import {genHash, percent} from 'src/utils/functions'
import Api from 'src/api'
import * as SDK from 'huoban-app-sdk'

export default {

  name: 'component_name',

  props: {
    taskId: Number
  },

  data() {
    return {
      uploadIndex: 0,
      items: [],
      loading: false
    }
  },

  methods: {
    handleSelectClick() {
      // 由于用了fastclick导致手机无法弹出选文件界面
      // 延时并多调用几次可以解决
      let $file = $(this.$els.file)
      if (SDK.isIPhone) {
        setTimeout(() => {
          $file.click()
          $file.click()
          $file.click()
          $file.click()
          $file.click()
        }, 300)
      } else {
        $file.click()
      }
    },
    handleFileChoose(e) {
      this.loading = true
      let files = Array.prototype.slice.call(e.target.files)
      // so user can repeat upload same file multiple times
      this.$els.form.reset()

      let requests = files.map((file) => {
        let hash = genHash(`file_${++this.uploadIndex}`, _.pick(file, ['name', 'size', 'lastModified']), true)
        let promise = Api.file.upload(file, {
          upload: {
            onprogress: _.partial(this.handleFileProgress, hash),
            onload: _.partial(this.handleFileLoad, hash)
          }
        })
        return {hash, file, promise}
      })

      let items = requests.map(({hash, file}) => {
        return {hash, file, loaded: false, progress: 0}
      })
      let promises = _.map(requests, 'promise')
      this.items = this.items.concat(items)

      let currentTaskId = this.taskId

      Promise.all(promises).then((results) => {
        let files = _.map(results, 'data')
        // 避免切换任务时，上一个任务还没有上传完的数据，到下一个任务界面才上传完，结果导致update task错误
        if (currentTaskId === this.taskId) {
          this.$dispatch('uploader-finished', files, items)
        }
        // this.items = []
      })
    },

    handleFileProgress(hash, e) {
      let item = _.find(this.items, {hash})
      if (item) {
        item.progress = percent(e.loaded, e.total)
        // console.log(item, item.progress)
      }
    },

    handleFileLoad(hash, e) {
      let item = _.find(this.items, {hash})
      if (item) {
        item.loaded = true
      }
    },

    finishItems(finishedItems) {
      this.items = this.items.filter((item) => {
        if (_.find(finishedItems, {hash: item.hash})) {
          return false
        }
        return true
      })
      this.loading = false
    },

    cleanItems() {
      this.items = []
      this.loading = false
    }
  }
}
</script>
