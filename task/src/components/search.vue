<template>
  <div>
    <div :class='{"search_bar": true,"search_focus": isSearching}' v-if='isUp'>
      <form class="search_outer">
        <label
              class="search_placeholder"
              @click='handleSearching'>
          <i class="icon" @click='handleClear'>&#xe901;</i>
          <span>搜索</span>
        </label>
        <div class="search_inner">
          <i class="icon">&#xe901;</i>
          <input type="text"
                 class="search_input" placeholder="搜索"
                 @keyup.enter='handleCommit'
                 v-model='keyword' />
          <a href="#" class="search_clear"><i>&#xe911;</i></a>
        </div>
      </form>
      <a class="search_cancel" @click='handleSearching'>取消</a>
    </div>
    <div class="menu_search" v-else>
      <div :class='{"search_box": true, "focus": isFocus}'>
        <i>&#xe901;</i>
        <input type="text" class="px_search"
               @keyup.enter='handleCommit'
               @click='handleClick'
               @blur = 'handleBlur'
               v-model='keyword'/>
      </div>
    </div>
  </div>
</template>

<script>
import {isMobile} from 'huoban-app-sdk'
export default {

  name: 'search',

  props: {
    isUp: Boolean
  },

  data() {
    return {
      keyword: '',
      isSearching: false,
      isFocus: false
    }
  },

  methods: {
    handleCommit() {
      if (this.keyword.trim() != '') {
        this.$dispatch('search-commit', this.keyword)
        this.$el.getElementsByTagName('input')[0].value = ''
        this.$el.getElementsByTagName('input')[0].blur()
      }
    },
    handleClick() {
      this.isFocus = true
    },
    handleBlur() {
      if (this.keyword.trim() != '' && isMobile) {
        this.$dispatch('search-commit', this.keyword)
      }
      this.isFocus = false
    },
    handleSearching() {
      this.isSearching = !this.isSearching
      if (this.isSearching) {
        this.$el.getElementsByTagName('input')[0].focus()
      }
    },
    handleClear() {
      this.$el.getElementsByTagName('input')[0].value = ''
    }
  }
}
</script>