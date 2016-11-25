import _ from 'lodash'

export const genHash = (scope, params = {}, minify) => {
  const objHash = (obj) => {
    return _.reduce(obj, (r, v, k) => {
      let ss = `${k}=${v}`
      let c = _.reduce(ss, (r, s) => r + s.charCodeAt(0), 0)
      return r + c
    }, 0)
  }

  let suffix = ''
  if (!_.isEmpty(params)) {
    let hashParams = _.mapValues(params, (value, key) => {
      if (_.isPlainObject(value)) {
        return objHash(value)
      } else {
        return encodeURIComponent(value)
      }
    })

    if (minify) {
      suffix = `_${objHash(hashParams)}`
    } else {
      suffix = _.reduce(hashParams, (r, v, k) => {
        if (r.length > 1) {
          return `${r}_${k}_${v}`
        }
        return `${r}${k}_${v}`
      }, '_')
    }
  }

  return `${scope}${suffix}`
}

export const percent = (l, t) => {
  let s = l / t
  return Math.round(s * 10000) / 100
}

export const nl2br = str => {
  let breakTag = '<br ' + '/>'
  return (str||'' + '')
    .replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2')
}

export const stripTags = (input, allowed) => {
  allowed = (((allowed || '') + '')
    .toLowerCase()
    .match(/<[a-z][a-z0-9]*>/g) || [])
    .join('') // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
  let tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
  let commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi
  return (input || '').replace(commentsAndPhpTags, '')
    .replace(tags, function($0, $1) {
      return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : ''
    })
}
