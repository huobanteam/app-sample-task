<template>
  <div class="weui_loading_toast" v-show="show">
    <div class="weui_mask_transparent"></div>
    <div class="weui_toast">
      <div class="weui_loading">
        <div v-for="n in 12" class="weui_loading_leaf" :class="'weui_loading_leaf_' + n"></div>
      </div>
      <p class="weui_toast_content"><slot>数据加载中</slot></p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'loading',
  props: ['show']
}
</script>
