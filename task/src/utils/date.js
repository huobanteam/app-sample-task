module.exports = (function() {

  var _date = {};

  var paddingStr = function(str, count, padding) {
    if (typeof (padding) === 'undefined') {
      padding = '0';
    }

    if ((str = str + '').length < count) {
      return new Array((++count) - str.length).join(padding) + str;
    }

    return str;
  };
  var intval = function(mixedVar, base) {
    var tmp, type = typeof (mixedVar), ret;

    if (type === 'boolean') {
      ret = +mixedVar;
    } else if (type === 'string') {
      tmp = parseInt(mixedVar, base || 10);
      ret = (isNaN(tmp) || !isFinite(tmp)) ? 0 : tmp;
    } else if (type === 'number' && isFinite(mixedVar)) {
      ret = mixedVar | 0;
    } else {
      ret = 0;
    }

    return ret;
  };

  /**
   * 将任意类型的日期时间转换成js的日期时间对象
   *
   * @param mixed timestamp 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   */
  _date.parse = function(timestamp) {

    var tsObj;

    if (!timestamp) {
      // 没有参数
      tsObj = new Date();
    } else if (timestamp instanceof Date) {
      // js日期对象
      tsObj = new Date(timestamp);
    } else if (typeof (timestamp) == 'number') {
      // UNIX时间戳
      tsObj = new Date(timestamp * 1000);
    } else if (typeof (timestamp) == 'string') {
      if (isNaN(timestamp)) {
        var match = timestamp.match(/^(\d{2,4})-(\d{2})-(\d{2})(?:\s(\d{1,2}):(\d{2})(?::(\d{2}))?)?(?:\.(\d+)?)?$/);
        if (match) {
          // 标准日期时间字符串
          var year = paddingStr(match[1] >= 0 && match[1] <= 69 ? match[1] + 2000 : match[1], 2);
          var month = paddingStr(match[2], 2);
          var day = paddingStr(match[3], 2);
          var time = paddingStr(match[4] || 0, 2);
          var minute = paddingStr(match[5] || 0, 2);
          var second = paddingStr(match[6] || 0, 2);
          // var milliscond = paddingStr(match[7] || 0, 3);

          // 带时区暂不支持毫秒参数
          var datetime = month + '/' + day + '/' + year + ' ' + time + ':' + minute + ':' + second + ' UTC+0800';

          tsObj = new Date(datetime);
        } else {
          // 不是标准日期时间字符串的都给js Date对象去解析
          tsObj = new Date(timestamp);
        }

      } else {
        // UNIX时间戳
        tsObj = new Date(timestamp * 1000);
      }

    } else {
      // 其他
      tsObj = new Date();
    }

    return tsObj;
  };

  /**
   * 将日期时间转成标准日期字符串
   *
   * @param mixed timestamp 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   * @return string 标准日期字符串
   */
  _date.toString = function(timestamp) {

    return _date.format('Y-m-d H:i:s', timestamp);
  };

  /**
   * 将标准日期字符串转为时间戳
   *
   * @param mixed datetime 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   * @param bool  millisecond 是否为毫秒级时间戳(默认值: false)
   * @return integer UNIX秒级时间戳
   */
  _date.toTime = function(datetime, millisecond) {
    if (millisecond !== true) {
      millisecond = false;
    }

    var tsObj = _date.parse(datetime);
    var time = tsObj.getTime();
    if (!millisecond) {
      time /= 1000;
    }

    return parseInt(time);
  };

  /**
   * 比较两个日期的天数差
   *
   * @param mixed datetime1 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   * @param mixed datetime2 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   * @return integer 1 - 2 得到的天数差值
   */
  _date.diffDay = function(datetime1, datetime2) {

    var ts1 = _date.toTime(datetime1);
    var ts2 = _date.toTime(datetime2);

    var diffDay = intval(ts1 / 86400) - intval(ts2 / 86400);

    return diffDay;
  };

  /**
   * 比较两个日期时间的秒数差值
   *
   * @param mixed datetime1 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   * @param mixed datetime2 可以是 UNIX秒级时间戳 或者 标准日期格式 或者 js日期对象
   * @return integer
   */
  _date.diffTime = function(datetime1, datetime2) {

    var ts1 = _date.toTime(datetime1);
    var ts2 = _date.toTime(datetime2);

    var diffDay = intval(ts1 - ts2);

    return diffDay;
  };

  /**
   * 友好化时间
   *
   * @param string datetime 数据日期时间字符串
   * @param string datetimeNow 当前服务器时间字符串
   * @return string 友好化显示的时间
   */
  _date.friendly = function(datetime, datetimeNow, returnMeta) {

    // 日期时间戳转换
    var tsObj = _date.parse(datetime);
    var ts = _date.toTime(tsObj);

    // 当前时间
    var tsNowObj = _date.parse(datetimeNow);
    // 当天开始时间
    var tsToday = _date.toTime(_date.parse(_date.toString(tsNowObj).substring(0, 10)));
    // 昨天开始时间
    var tsYesterday = tsToday - 86400;

    // 当前时间与传入时间的差值
    var tsOffset = _date.diffTime(tsNowObj, tsObj);

    // title上用于显示的时间格式
    var datetimeTitle = _date.format('Y年n月j日 H:i', ts);

    var meta = {
      ts: ts,
      title: datetimeTitle
    };

    var text = '';

    if (ts >= tsToday) {
      // 当天或大于当前时间
      if (tsOffset >= 3600) {
        text = '今天 ' + _date.format('H:i', tsObj);
      } else if (tsOffset >= 60) {
        text = Math.floor(tsOffset / 60) + '分钟前';
      } else if (tsOffset >= -10) {
        text = '刚刚';
      } else {
        // 大于当前时间
        text = datetimeTitle;
      }

    } else if (ts >= tsYesterday) {
      // 昨天
      text = '昨天 ' + _date.format('H:i', tsObj);
    } else if (_date.format('Y', tsNowObj) == _date.format('Y', tsObj)) {
      // 今年
      text = _date.format('n月j日 H:i', tsObj);
    } else {
      // 早于今年
      text = datetimeTitle;
    }

    meta.text = text;

    if (returnMeta) {
      return meta;
    }

    return '<span hb_datetime_friendly="' + meta.ts + '" title="' + meta.title + '">' + meta.text + '</span>';
  };

  /**
   * 按照指定的格式转换日期时间
   *
   * 与PHP的date函数用法基本相同
   *
   * @param string format 日期时间格式，例如 'Y-m-d H:i:s'
   * @param mixed timestamp 可以是 时间戳 或者 标准日期格式 或者 js日期对象
   *
   * @return string 按照指定format得到的字符串
   */
  _date.format = function(format, timestamp) {
    var jsdate,
      f,
      formatChr = /\\?([a-z])/gi,
      formatChrCb,
      _pad = function(n, c) {
        n = n.toString();
        return n.length < c ? _pad('0' + n, c, '0') : n;
      },
      txt_words = ['Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    formatChrCb = function(t, s) {
      return f[t] ? f[t]() : s;
    };

    f = {
      // Day
      d: function() { // Day of month w/leading 0; 01..31
        return _pad(f.j(), 2);
      },
      D: function() { // Shorthand day name; Mon...Sun
        return f.l().slice(0, 3);
      },
      j: function() { // Day of month; 1..31
        return jsdate.getDate();
      },
      l: function() { // Full day name; Monday...Sunday
        return txt_words[f.w()] + 'day';
      },
      N: function() { // ISO-8601 day of week; 1[Mon]..7[Sun]
        return f.w() || 7;
      },
      S: function() { // Ordinal suffix for day of month; st, nd, rd, th
        var j = f.j();
        return j < 4 | j > 20 && (['st', 'nd', 'rd'][j % 10 - 1] || 'th');
      },
      w: function() { // Day of week; 0[Sun]..6[Sat]
        return jsdate.getDay();
      },
      z: function() { // Day of year; 0..365
        var a = new Date(f.Y(), f.n() - 1, f.j()),
          b = new Date(f.Y(), 0, 1);
        return Math.round((a - b) / 864e5);
      },

      // Week
      W: function() { // ISO-8601 week number
        var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3),
          b = new Date(a.getFullYear(), 0, 4);
        return _pad(1 + Math.round((a - b) / 864e5 / 7), 2);
      },

      // Month
      F: function() { // Full month name; January...December
        return txt_words[6 + f.n()];
      },
      m: function() { // Month w/leading 0; 01...12
        return _pad(f.n(), 2);
      },
      M: function() { // Shorthand month name; Jan...Dec
        return f.F().slice(0, 3);
      },
      n: function() { // Month; 1...12
        return jsdate.getMonth() + 1;
      },
      t: function() { // Days in month; 28...31
        return (new Date(f.Y(), f.n(), 0)).getDate();
      },

      // Year
      L: function() { // Is leap year?; 0 or 1
        var j = f.Y();
        return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0;
      },
      o: function() { // ISO-8601 year
        var n = f.n(),
          W = f.W(),
          Y = f.Y();
        return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0);
      },
      Y: function() { // Full year; e.g. 1980...2010
        return jsdate.getFullYear();
      },
      y: function() { // Last two digits of year; 00...99
        return f.Y().toString().slice(-2);
      },

      // Time
      a: function() { // am or pm
        return jsdate.getHours() > 11 ? 'pm' : 'am';
      },
      A: function() { // AM or PM
        return f.a().toUpperCase();
      },
      B: function() { // Swatch Internet time; 000..999
        var H = jsdate.getUTCHours() * 36e2,
          // Hours
          i = jsdate.getUTCMinutes() * 60,
          // Minutes
          s = jsdate.getUTCSeconds(); // Seconds
        return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
      },
      g: function() { // 12-Hours; 1..12
        return f.G() % 12 || 12;
      },
      G: function() { // 24-Hours; 0..23
        return jsdate.getHours();
      },
      h: function() { // 12-Hours w/leading 0; 01..12
        return _pad(f.g(), 2);
      },
      H: function() { // 24-Hours w/leading 0; 00..23
        return _pad(f.G(), 2);
      },
      i: function() { // Minutes w/leading 0; 00..59
        return _pad(jsdate.getMinutes(), 2);
      },
      s: function() { // Seconds w/leading 0; 00..59
        return _pad(jsdate.getSeconds(), 2);
      },
      u: function() { // Microseconds; 000000-999000
        return _pad(jsdate.getMilliseconds() * 1000, 6);
      },

      // Timezone
      e: function() { // Timezone identifier; e.g. Atlantic/Azores, ...
        // The following works, but requires inclusion of the very large
        // timezone_abbreviations_list() function.
        throw 'Not supported (see source code of date() for timezone on how to add support)';
      },
      I: function() { // DST observed?; 0 or 1
        // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
        // If they are not equal, then DST is observed.
        var a = new Date(f.Y(), 0),
          // Jan 1
          c = Date.UTC(f.Y(), 0),
          // Jan 1 UTC
          b = new Date(f.Y(), 6),
          // Jul 1
          d = Date.UTC(f.Y(), 6); // Jul 1 UTC
        return ((a - c) !== (b - d)) ? 1 : 0;
      },
      O: function() { // Difference to GMT in hour format; e.g. +0200
        var tzo = jsdate.getTimezoneOffset(),
          a = Math.abs(tzo);
        return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4);
      },
      P: function() { // Difference to GMT w/colon; e.g. +02:00
        var O = f.O();
        return (O.substr(0, 3) + ':' + O.substr(3, 2));
      },
      T: function() { // Timezone abbreviation; e.g. EST, MDT, ...
        // The following works, but requires inclusion of the very
        // large timezone_abbreviations_list() function.
        return 'UTC';
      },
      Z: function() { // Timezone offset in seconds (-43200...50400)
        return -jsdate.getTimezoneOffset() * 60;
      },

      // Full Date/Time
      c: function() { // ISO-8601 date.
        return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb);
      },
      r: function() { // RFC 2822
        return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
      },
      U: function() { // Seconds since UNIX epoch
        return jsdate / 1000 | 0;
      }
    };

    this.date = function(format1, timestamp1) {
      jsdate = _date.parse(timestamp1);

      return format1.replace(formatChr, formatChrCb);
    };

    return this.date(format, timestamp);
  };

  return _date;

})();