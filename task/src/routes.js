export default function(router, store) {
  router.map({
    '/': {
      name: 'home',
      displayName: '任务',
      component: require('views/home'),
      subRoutes: {
        'project/:project_id': {
          name: 'item-list',
          component: require('views/item-list'),
          subRoutes: {
            'item/:item_id': {
              name: 'item-detail',
              component: require('views/item-detail')
            }
          }
        },

        'search/:keyword': {
          name: 'item-search',
          displayName: '搜索结果',
          component: require('views/item-list'),
          subRoutes: {
            'item/:item_id': {
              name: 'search-item-detail',
              component: require('views/item-detail')
            }
          }
        }
      }
    },

    '/item/:item_id': {
      name: 'item',
      component: require('views/item-detail')
    },

    '/error': {
      name: 'error',
      component: require('views/error')
    },

    '/upgrade': {
      name: 'upgrade',
      component: require('views/upgrade')
    }
  })

  router.alias({
    '/index': '/'
  })

  router.redirect({
    '*': '/' // default router
  })
}
