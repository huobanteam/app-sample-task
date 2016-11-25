<template>
  <span class="user">
    <img :src='user.avatar' />
    <span>{{user.name}}</span>
    <i v-if='hasDropDown' @click='selectUser'>&#xe904;</i>
  </span>
</template>

<script>
// import * as SDK from 'huoban-app-sdk'
export default {

  name: 'user',

  props: ['hasDropDown', 'user'],

  methods: {
    selectUser(e) {

    }
  }
};
</script>