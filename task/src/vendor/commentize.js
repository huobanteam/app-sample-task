function detectIE() {
  var ua = window.navigator.userAgent;

  // Test values; Uncomment to check result …

  // IE 10
  // ua = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';

  // IE 11
  // ua = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';

  // Edge 12 (Spartan)
  // ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0';

  // Edge 13
  // ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

  var msie = ua.indexOf('MSIE ')
  if (msie > 0) {
    // IE 10 or older => return version number
    return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10)
  }

  var trident = ua.indexOf('Trident/')
  if (trident > 0) {
    // IE 11 => return version number
    var rv = ua.indexOf('rv:')
    return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10)
  }

  var edge = ua.indexOf('Edge/')
  if (edge > 0) {
    // Edge (IE 12+) => return version number
    return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10)
  }

  // other browser
  return false
}

var isIE = detectIE()

var stylesheet = document.createElement('style')
stylesheet.innerHTML = 'a.reference-select{background: #b3d4fd}.hb_at { position: absolute; top: 0; left: 0; min-width: 100px; max-width: 250px; max-height: 262px; overflow-y: auto; z-index: 1000; font-size: 14px; background-color: #FFF; -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3); box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3); }.hb_at ul { padding: 5px 0; }.hb_at ul li { overflow: hidden; padding: 0 10px; height: 36px; line-height: 36px; cursor: pointer; overflow: hidden; display: block; white-space: nowrap; -o-text-overflow: ellipsis; text-overflow: ellipsis; color: #9f9f9f; }.hb_at ul li .at_avatar { float: left; margin: 7px 5px 0 0; width: 20px; height: 20px; border-radius: 100px; }.hb_at ul li .at_name { color: #4B4B4B; }.hb_at ul li .at_account{ margin-left: 5px; color: #9F9F9F; }.hb_at ul li:hover { background-color: #F5F5F5; }.hb_at ul li.current { background-color: #F2F6FF }html,body{ -webkit-user-select: none;-webkit-touch-callout: none;}*[contenteditable] {-webkit-user-select: auto !important;}.contenteditable p{padding:0; margin:0}'
document.head.appendChild(stylesheet)

var users = []

// '评论框化'，可使用jquery or zepto
module.exports = function($, _, SDK, element /*原输入元素*/, options) {
  var client = SDK.client()
  var placeholder = '<span style="color: #CCC">添加评论</span>'
  var content = element.val()

  if (users.length === 0 && client) {
    client.getSpaceMembers().then(function(response) {
      users = response.members
    })
  }

  let editor = $('<div class="contenteditable" contenteditable></div>').html(content || placeholder)
  // copy textarea's style
  _.each(
    ['margin', 'padding', 'height', 'line-height', 'border', 'box-sizing', 'color', 'background', 'box-shadow', 'outline', 'border-radius', 'font'],
    function(key) {
      editor.css(key, element.css(key))
    }
  )
  // editor.css('overflow', 'auto')
  element.after(editor)
  element.hide()

  var userChooser = $('<div class="hb_at" style="top: 50px; left: 50px; display: none"></div>')
  userChooser.on('click', 'li', handleChoose)
  var userChooserMatchIndex = 0
  var _searchText = '' // last search text
  var _rangeInfo = null // last range object

  $(document.body).append(userChooser)

  // function clearifyValue(text) {
  //   var el = document.createElement('div')
  //   // text = text.replace(/<a[^>]*>([^<]*)<\/a>/ig, "$1")
  //   el.innerHTML = text
  //   return el.innerText
  // }

  function formatText(text) {
    var ret = text.replace(/<\/div>/gi, '')
              .split(/<div>/gi)
              .filter(function(line) {
                return !!_.trim(line)
              })
              .map(function(line) {
                return '<div>' + line + '</div>'
              })
              .join('')
    return ret
  }

  // 同步数据
  function syncValue(text) {
    // console.log(text)
    // element.val(clearifyValue(text)).change()
    element.val(formatText(text)).change()
    content = text
  }

  // 替换选中的文字
  function replaceCurrentSelection(data) {
    var selection = window.getSelection()
    var range = selection.getRangeAt(0)
    range.deleteContents()
    var fragment = document.createTextNode(data)
    // fragment.textContent = data
    // var replacementEnd = fragment.lastChild
    range.insertNode(fragment)
    // Set cursor at the end of the replaced content, just like browsers do.
    // range.setStartAfter(replacementEnd)
    range.setStart(fragment, fragment.length)
    range.collapse(true)
    selection.removeAllRanges()
    selection.addRange(range)
  }

  // 判断光标是否在@人的元素中
  function isCaretInsideReference(range, selection) {
    if (!selection) {
      selection = window.getSelection()
    }
    if (selection.rangeCount === 0) {
      return false
    }
    if (!range) {
      range = selection.getRangeAt(0)
    }
    if (range.startContainer === range.endContainer) {
      return isReferenceNode(range.startContainer) || isReferenceNode(range.startContainer.parentNode)
    }
  }

  function insertReference(node, range, selection) {
    var userId = Number(node.attr('user_id'))
    var user = _.find(users, {user_id: userId})
    referencePeople(user, range, selection)
  }

  function referencePeople(who, range, selection) {
    var name = ''
    var userId = ''

    if (typeof who === 'object') {
      name = who.name
      userId = who.user_id
    } else {
      name = who
    }

    if (isCaretInsideReference(range, selection)) {
      range.selectNode(range.startContainer)
      range.deleteContents()
      range.insertNode(document.createTextNode('@' + name))
      range.startContainer.setAttribute('user_id', userId)

      range.collapse(false)
      if (range.startContainer.childNodes.length > 1) {
        range.startContainer.removeChild(range.startContainer.childNodes[1])
      }

      range.selectNode(range.startContainer)
      range.collapse(false)
      range.insertNode(document.createTextNode('\u00A0'))
      syncValue(editor.html())

      range.collapse(false)
      selection.removeAllRanges()
      selection.addRange(range)

      // console.log(range.startContainer)
    } else {
      range.deleteContents()
      var node = $('<a href="#" reference style="" hb_type="user" user_id="' + userId + '">@'+name+'</a>')[0]
      range.insertNode(node)

      range.selectNode(node)
      range.collapse(false)
      if (!_.isEmpty(who)) {
        range.insertNode(document.createTextNode('\u00A0'))
      }

      syncValue(editor.html())

      range.collapse(false)
      selection.removeAllRanges()
      selection.addRange(range)
    }
    handleCaret()
  }

  function handleChoose(e) {
    // console.log('handleChoose', e.target)
    var el = null
    var $target = $(e.target)
    if ($target.is('li')) {
      el = $target
    } else {
      el = $target.parents('li.contact-option')
    }
    if (el.length === 0) {
      return
    }

    var selection = window.getSelection()
    var range = genRangeFromInfo(_rangeInfo, editor[0])
    // console.log(range)
    // var range = document.createRange()

    // var node = editor.find('.reference-select')[0].childNodes[0]
    // range.setStart(node, 1)
    // range.setEnd(node, 1)

    insertReference(el, range, selection)
  }

  // 处理回车
  function insertEnter(keyCode) {
    var selection = window.getSelection()
    var range = selection.getRangeAt(0)
    // first delete selection content
    range.deleteContents()
    range.collapse(false)

    // 在@内部按enter时
    if (isReferenceNode(range.startContainer.parentNode)) {
      // empty reference
      if (userChooser.css('display') !== 'none') {
        var current = userChooser.find('li.current')
        insertReference(current, range, selection)
      } else {
        if (_.trim(selection.anchorNode.parentNode.text) === '@') {
          range.selectNode(selection.anchorNode.parentNode)
          range.deleteContents()
        } else {
          var parent = range.endContainer.parentNode
          if (range.endContainer.nextElementSibling) {
            range.selectNode(range.endContainer.nextElementSibling)
            range.deleteContents()
          }
          range.selectNode(parent)
          range.collapse(false)
          range.insertNode(document.createTextNode('\u00A0'))
          range.collapse(false)
          selection.removeAllRanges()
          selection.addRange(range)
        }
      }
      syncValue(editor.html())
      handleCaret()
      return true
    } else {
      if (keyCode === 13 && isIE) {
        return true
      }
    }
  }

  // 判断node是否是@的node
  function isReferenceNode(node) {
    if (node.nodeType === 3) return false
    return !_.isNull(node.getAttribute('reference'))
  }

  // 高亮当前光标所在的@ node
  function setHighlightReferenceNode(node, highlight) {
    var classes = node.className.split(' ')
    var index = classes.indexOf('reference-select')
    if (highlight) {
      if (index === -1) {
        classes.push('reference-select')
      }
    } else {
      if (index > -1) {
        classes.splice(index, 1)
      }
    }
    node.className = classes.join(' ')
  }

  // 处理@的输入
  function handleAt(range) {
    var selection = window.getSelection()
    if (!range) {
      range = selection.getRangeAt(0)
    }
    range.deleteContents()
    range.collapse(false)

    // console.log(range.startContainer.parentNode)
    // 如果当前光标位置在@当中，再输入@无效
    if (isReferenceNode(range.startContainer.parentNode)) {
      return
    }
    // console.log(range)

    let node = document.createElement('a')
    node.setAttribute('href', '#')
    node.setAttribute('reference', '')
    node.setAttribute('hb_type', 'user')
    node.textContent = '@\u00A0'

    range.insertNode(node)
    range.setStart(node.childNodes[0], 1)
    range.setEnd(node.childNodes[0], 1)
    range.collapse(false)
    selection.removeAllRanges()
    selection.addRange(range)

    syncValue(editor.html())
    handleCaret()
  }
  function handleChooseUserFromMobile(result) {
    if (result) {
      var user = result.users[0]
      var range = genRangeFromInfo(_rangeInfo, editor[0])
      // console.log(range)
      referencePeople(user.name, range, window.getSelection())
    }
  }
  function showMobileUserPicker() {
    client.openUserPicker({multi: false, title: '@'}, handleChooseUserFromMobile)
  }
  // 显示下拉
  function showUserChooser(node, searchText) {
    if (SDK.isMobile) {
      // showMobileUserPicker()
      return
    }

    if (searchText !== _searchText) { // 如果两次输入的内容不一样，就重置位置
      userChooserMatchIndex = 0
      _searchText = searchText
    }

    // filter
    if (searchText.indexOf('@') === 0) {
      searchText = searchText.substring(1)
    }

    var getRegex = text => {
      var s = '~!$%^&*()-+\'\\"?.,:|[]'
      return new RegExp(_.map(text.split(''), (c) => {
        if (s.indexOf(c) > -1) {
          return `\\${c}`
        } else {
          return c
        }
      }).join('.*'))
    }

    var contains = (source, regexp) => regexp.test(source || '')

    var matches = users.filter((user) => {
      var member = _.assign({}, user)
      var regexp = getRegex(searchText)

      var y = contains(member.pinyin, regexp)
      var n = contains(member.name, regexp)
      var e = contains(member.email, regexp)
      var p = contains(member.phone, regexp)

      if (y || n || e || p) {
        switch (true) {
          case y:
          case n:
            member.match = 'name'
            break
          case p:
            member.match = 'phone'
            break
          case e:
            member.match = 'email'
            break
        }
        return true
      }
      return false
    }).slice(0, 10)

    userChooser.html(
      '<ul>' +
      matches.map((user, i) => {
        return '<li class="contact-option' + (userChooserMatchIndex === i ? ' current' : '') + '" user_id="' + user.user_id + '">' +
          '<img src="' + (user.avatar.small_link ? user.avatar.small_link : user.avatar) + '" class="at_avatar">' +
          '<span class="at_name">' + user.name + '</span>' +
          '<span class="at_account">' + (user.match === 'phone' ? user.phone : user.email) + '</span>' +
          '</li>'
      }).join('') +
      '</ul>'
    )

    if (matches.length > 0) {
      userChooser.show()
    } else {
      userChooser.hide()
    }

    // position
    var $node = $(node)
    var offset = $node.offset()
    var left = offset.left
    var top = offset.top + 1.5 * $node.height()

    if (left + userChooser.width() > window.document.documentElement.clientWidth) {
      left = window.document.documentElement.clientWidth - userChooser.width()
    }

    if (top + userChooser.height() > window.document.documentElement.clientHeight) {
      top = offset.top - userChooser.height() - 0.5 * $node.height()
    }
    userChooser.css({
      left: left + 'px',
      top: top + 'px'
    })
  }

  // function userChooser() {

  // }

  function nodeRelationToEditor(node) {
    if (node === editor[0]) {
      return 'equal'
    } else {
      if (node.parentNode) {
        if (node.parentNode === editor[0]) {
          return 'inside'
        } else {
          return nodeRelationToEditor(node.parentNode)
        }
      } else {
        return 'outside'
      }
    }
  }

  // 处理光标
  function handleCaret() {
    // 所有高亮的@都取消高亮
    editor.find('[reference]').removeClass('reference-select')

    var selection = window.getSelection()
    if (selection.rangeCount === 0) {
      return
    }
    var range = selection.getRangeAt(0)
    // 如果选中区域的起始和结束在同一个node，并且在@内部的时候
    if (selection.anchorNode === selection.focusNode && isReferenceNode(selection.anchorNode.parentNode)) {
      setHighlightReferenceNode(selection.focusNode.parentNode, true)
      var searchText = selection.focusNode.textContent.substring(0, range.endOffset)
      showUserChooser(selection.focusNode.parentNode, searchText)
    } else {
      userChooser.hide()
    }

    var relation = nodeRelationToEditor(selection.anchorNode)
    if (relation === 'inside' || relation === 'equal') {
      _rangeInfo = makeRangeInfo(true, range, {})
      // console.log(_rangeInfo)
    }
  }

  function makeRangeInfo(start, range, ret) {
    if (range.startContainer === editor[0]) {
      if (content.length === 0) {
        return null
      } else {
        if (editor[0].childNodes.length === 0) {
          ret.startNodeIndex = 0
          ret.startOffset = range.startOffset
          return ret
        } else {
          var node = editor[0].childNodes[range.startOffset > 0 ? (range.startOffset - 1) : 0]
          var pos = node.childNodes.length > 0 ? 1 : 0
          range.setStart(node, pos)
          range.collapse(true)
          return makeRangeInfo(start, range, ret)
        }
      }
    }
    if (start) {
      ret.startOffset = range.startOffset
    }
    ret.startNodeIndex = _.indexOf(range.startContainer.parentNode.childNodes, range.startContainer)
    if (range.startContainer.parentNode === editor[0]) {
      return ret
    }
    return makeRangeInfo(false, {startContainer: range.startContainer.parentNode}, {child: ret})
  }

  function genRangeFromInfo(rangeInfo, node) {
    var selection = window.getSelection()
    var range = null
    if (!rangeInfo || !(node && node.childNodes.length > 0)) {
      range = document.createRange()
      range.selectNode(editor[0])
      // range.insertNode(document.createTextNode('\u00A0'))
      range.setStart(editor[0], 0)
      range.setEnd(editor[0], 0)
      selection.removeAllRanges()
      selection.addRange(range)
    } else {
      if (rangeInfo.child) {
        var child = node.childNodes[rangeInfo.startNodeIndex]
        return genRangeFromInfo(rangeInfo.child, child)
      } else {
        range = document.createRange()
        range.setStart(node.childNodes[rangeInfo.startNodeIndex], rangeInfo.startOffset)
      }
    }
    return range
  }

  // 鼠标点击
  function globalMouseUp(e) {
    // console.log('select')
    if (!$.contains(userChooser[0], e.target)) {
      handleCaret()
    }
  }
  // var __range = null

  editor.on('keyup', function(e) {
    // e.preventDefault()
    // console.log('keyup', e, e.keyCode)
    syncValue(editor.html())
    handleCaret()
    // return false
  }).on('keydown', function(e) {
    // console.log('keydown', e.keyCode, e)

    var prev = function() {
      e.preventDefault()
      e.stopPropagation()
    }
    // if (true){
    //   e.preventDefault()
    //   return false
    // }
    var forbiddenKeys = [
      66, // bold
      73  // italic
    ]
    if (e.metaKey && forbiddenKeys.indexOf(e.keyCode)>-1) {
      prev()
      return
    }

    if (e.keyCode === 13 || e.keyCode === 32) {
      if (insertEnter(e.keyCode)) {
        prev()
      }
      return
    }
    // @ 在safari上按@，event没有key属性，而且keyCode是229
    // if (e.key === '@') {
    if (e.keyCode === 50 && e.shiftKey) {
      prev()
      handleAt()
      return
    }
    // 40 - ArrowDown / 38 - ArrowUp
    if (e.keyCode === 40 || e.keyCode === 38) {
      if (isCaretInsideReference()) {
        var optionsLength = userChooser.find('li').length
        if (e.keyCode === 40) {
          userChooserMatchIndex += 1
          if (userChooserMatchIndex >= optionsLength) {
            userChooserMatchIndex = 0
          }
        } else {
          userChooserMatchIndex -= 1
          if (userChooserMatchIndex <= -1) {
            userChooserMatchIndex = optionsLength - 1
          }
        }
        // console.log(userChooserMatchIndex)
        prev()
        return
      }
    }
  }).on('focus', function(e) {
    if (!content) {
      editor.html(content)
    }
    if (options) {
      if (options.afterHeight) {
        editor.css('height', options.afterHeight)
      }

      if (options.onInitialized) {
        options.onInitialized()
      }
    }
    editor.css('overflow', 'auto')
  }).on('blur', function(e) {
    if (!content) {
      editor.html(placeholder)
    }
    // editor.html(content || placeholder)
    // userChooser.hide()
  }).on('paste', function(e) {
    e.preventDefault()
    var data = e.clipboardData.getData('text/plain')
    replaceCurrentSelection(data)
  }).on('click', function(e) {
    e.preventDefault()
    handleCaret()
    // var selection = window.getSelection()
    // var range = selection.getRangeAt(0)
    // console.log(range, __range)
    // __range = range.cloneRange()
  })

  $(document.body).on('mouseup', globalMouseUp)

  console.log('commentized')

  // console.log(editor)

  return (function(editor) {
    return {
      focus: function() {
        editor.focus()
      },
      clean: function() {
        editor.html(placeholder)
        syncValue('')
        _rangeInfo = null
      },

      destroy: function() {
        console.log('editor destroy')
        content = ''

        editor.off('keyup').off('keydown').off('focus').off('blur').off('click').off('paste').remove()
        $(document.body).off('mouseup', globalMouseUp)
        element.show()
        editor = null

        userChooser.remove()
        userChooser = null
      },

      setValue: function(text) {
        editor.html(text)
        syncValue(text)
      },

      getValue: function() {
        return content
      },

      insertAt: function() {
        console.log('insertAt')
        if (SDK.isMobile) {
          showMobileUserPicker()
          return
        }
        var range = genRangeFromInfo(_rangeInfo, editor[0])
        // referencePeople('', range, window.getSelection())
        handleAt(range)
      },

      referencePeople: function(who) {
        console.log('referencePeople')
        var range = genRangeFromInfo(_rangeInfo, editor[0])
        // console.log(range)
        referencePeople(who, range, window.getSelection())
      }
    }
  })(editor)
}
