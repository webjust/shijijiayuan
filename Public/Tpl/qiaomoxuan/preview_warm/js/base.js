function FastClick(e, t) {
  function n(e, t) {
    return function () {
      return e.apply(t, arguments)
    }
  }
  var i;
  if (t = t || {
  }, this.trackingClick = !1, this.trackingClickStart = 0, this.targetElement = null, this.touchStartX = 0, this.touchStartY = 0, this.lastTouchIdentifier = 0, this.touchBoundary = t.touchBoundary || 10, this.layer = e, this.tapDelay = t.tapDelay || 200, !FastClick.notNeeded(e)) {
    for (var o = [
      'onMouse',
      'onClick',
      'onTouchStart',
      'onTouchMove',
      'onTouchEnd',
      'onTouchCancel'
    ], r = this, a = 0, s = o.length; s > a; a++) r[o[a]] = n(r[o[a]], r);
    deviceIsAndroid && (e.addEventListener('mouseover', this.onMouse, !0), e.addEventListener('mousedown', this.onMouse, !0), e.addEventListener('mouseup', this.onMouse, !0)),
    e.addEventListener('click', this.onClick, !0),
    e.addEventListener('touchstart', this.onTouchStart, !1),
    e.addEventListener('touchmove', this.onTouchMove, !1),
    e.addEventListener('touchend', this.onTouchEnd, !1),
    e.addEventListener('touchcancel', this.onTouchCancel, !1),
    Event.prototype.stopImmediatePropagation || (e.removeEventListener = function (t, n, i) {
      var o = Node.prototype.removeEventListener;
      'click' === t ? o.call(e, t, n.hijacked || n, i)  : o.call(e, t, n, i)
    }, e.addEventListener = function (t, n, i) {
      var o = Node.prototype.addEventListener;
      'click' === t ? o.call(e, t, n.hijacked || (n.hijacked = function (e) {
        e.propagationStopped || n(e)
      }), i)  : o.call(e, t, n, i)
    }),
    'function' == typeof e.onclick && (i = e.onclick, e.addEventListener('click', function (e) {
      i(e)
    }, !1), e.onclick = null)
  }
}
define('zenjs/util/ua', [
], function () {
  window.zenjs = window.zenjs || {
  };
  var e = navigator.userAgent.toLowerCase(),
  t = {
    isIOS: function () {
      return 'ios' == window._global.mobile_system
    },
    getIOSVersion: function () {
      return parseFloat(('' + (/CPU.*OS ([0-9_]{1,5})|(CPU like).*AppleWebKit.*Mobile/i.exec(navigator.userAgent) || [0,
      '']) [1]).replace('undefined', '3_2').replace('_', '.').replace('_', '')) || !1
    },
    isAndroid: function () {
      return 'android' == window._global.mobile_system
    },
    isAndroidOld: function () {
      return /android 2.3/gi.test(e) || /android 2.2/gi.test(e)
    },
    getAndroidVersion: function () {
      var t = e.match(/android\s([0-9\.]*)/);
      return t ? t[1] : !1
    },
    isWeixin: function () {
      return 'weixin' == window._global.platform
    },
    isIPad: function () {
      return /ipad/gi.test(e)
    },
    isMobile: function () {
      return window._global.is_mobile
    },
    isSafari: function () {
      return /safari/gi.test(e) && !/chrome/gi.test(e)
    },
    getSafariVersion: function () {
      var t = /safari\/(\S*)/i;
      return t.test(e) ? e.match(t) [1] : '0'
    },
    isChrome: function () {
      return /chrome/gi.test(e)
    },
    getChromeVersion: function () {
      var t = /chrome\/(\S*)/i;
      return t.test(e) ? e.match(t) [1] : '0'
    },
    isUC: function () {
      return /ucbrowser/gi.test(e)
    },
    getUCVersion: function () {
      var t = /ucbrowser\/(\S*)/i;
      return t.test(e) ? e.match(t) [1] : '0'
    },
    isWxd: function () {
      return 'youzanwxd' === _global.platform
    },
    getPlatformVersion: function () {
      return _global.platform_version
    }
  };
  return window.zenjs.UA = t,
  t
}),
define('wap/base/fullguide', [
  'zenjs/util/ua'
], function (e) {
  var t = window.Zepto || window.jQuery || t,
  n = window._global,
  i = t('body'),
  o = zenjs.UA.isWeixin() && n.mp_data && + n.mp_data.quick_subscribe && n.mp_data.quick_subscribe_url,
  a = {
    follow: '#js-follow-guide',
    fav: '#js-fav-guide',
    share: '#js-share-guide'
  },
  s = function (e, n) {
    var i,
    o;
    t(a[e]).length ? o = t(a[e])  : (i = r[e](n || {
    }), o = t(i).appendTo('body')),
    o.removeClass('hide')
  },
  l = {
    fav: function () {
      s('fav')
    },
    share: function () {
      s('share')
    },
    follow: function (e) {
      var t = n.mp_data;
      if (t) return !(e || {
      }).goods && o ? void (window.location.href = t.quick_subscribe_url)  : void s('follow', t)
    },
    browser: function (e) {
      zenjs.UA.isWeixin() && s('browser', e)
    }
  },
  c = function (e, n) {
    var i = t(a[e]);
    i && 0 != i.length ? i.removeClass('hide')  : l[e](n)
  };
  n.is_mobile ? n && 'Showcase_Goods_Controller' === n.controller && (o ? r.follow = r.goodsQuickSubscribe : r.follow = r.goodsFollow)  : r.follow = r.pc,
  i.on('click', '.wxid', function (e) {
    e.stopPropagation()
  }),
  i.on('click', '.js-open-follow', function (e) {
    e.preventDefault(),
    c('follow')
  }),
  i.on('click', '.js-open-browser', function (e) {
    e.preventDefault(),
    c('browser')
  }),
  i.on('click', '.js-open-fav', function (e) {
    e.preventDefault(),
    c('fav')
  }),
  i.on('click', '.js-open-share', function (e) {
    e.preventDefault(),
    window._global && window._global.wuxi1_0_0 && window.shareHook ? window.shareHook()  : window.YouzanJSBridge ? window.YouzanJSBridge.doShare()  : c('share')
  }),
  t(document).on('click', '.js-fullguide', function () {
    t(this).addClass('hide')
  }),
  i.on('click', '.js-quick-subscribe', function (e) {
    e.stopPropagation()
  }),
  window.showGuide = c
}),
define('zenjs/util/args', [
  'jquery'
], function (e) {
  window.zenjs = window.zenjs || {
  };
  var t = {
    getParameterByName: function (e, t) {
      e = e.replace(/[[]/, '\\[').replace(/[]]/, '\\]'),
      t = t ? '?' + t.split('#') [0].split('?') [1] : window.location.search;
      var n = RegExp('[?&]' + e + '=([^&#]*)').exec(t);
      return n ? decodeURIComponent(n[1].replace(/\+/g, ' '))  : ''
    },
    removeParameter: function (e, t) {
      var n = e.split('?');
      if (n.length >= 2) {
        for (var i = encodeURIComponent(t) + '=', o = n[1].split(/[&;]/g), r = o.length; r-- > 0; ) - 1 !== o[r].lastIndexOf(i, 0) && o.splice(r, 1);
        return e = n[0] + '?' + o.join('&')
      }
      return e
    },
    addParameter: function () {
      var t = function (t) {
        var n = '';
        for (var i in t) '' !== t[i] && (n += e.trim(i) + '=' + t[i] + '&');
        return n ? '?' + n.slice(0, n.length - 1)  : ''
      };
      return function (n, i) {
        if (!n || 0 === n.length || 0 === e.trim(n).indexOf('javascript')) return '';
        var o = n.split('#'),
        r = o[0].split('?'),
        a = {
        };
        return r[1] && e.each(r[1].split('&'), function (e, t) {
          var n;
          n = t.split('='),
          a[n[0]] = n.slice(1).join('=')
        }),
        e.each(i || {
        }, function (t, n) {
          a[e.trim(t)] = encodeURIComponent(n)
        }),
        n = r[0] + t(a),
        o[1] ? n += '#' + o[1] : n
      }
    }()
  };
  return t.get = t.getParameterByName,
  t.remove = t.removeParameter,
  t.add = t.addParameter,
  window.zenjs.Args = t,
  t
}),
define('zenjs/util/cookie', [
], function () {
  window.zenjs = window.zenjs || {
  };
  var e = {
    cookie: function () {
      var e = new Date,
      t = + e,
      n = 86400000,
      i = function (e) {
        var t = document.cookie,
        n = '\\b' + e + '=',
        i = t.search(n);
        if (0 > i) return '';
        i += n.length - 2;
        var o = t.indexOf(';', i);
        return 0 > o && (o = t.length),
        t.substring(i, o) || ''
      },
      o = function (e, t, n) {
        if (!e) return '';
        var i = [
        ];
        for (var o in e) i.push(encodeURIComponent(o) + '=' + (n ? encodeURIComponent(e[o])  : e[o]));
        return i.join(t || ',')
      };
      return function (r, a) {
        if (void 0 === a) return i(r);
        if ('string' == typeof a || a instanceof String) {
          if (a) return document.cookie = r + '=' + a + ';',
          a;
          a = {
            expires: - 100
          }
        }
        a = a || {
        };
        var s = r + '=' + (a.value || '') + ';';
        delete a.value,
        void 0 !== a.expires && (e.setTime(t + a.expires * n), a.expires = e.toGMTString()),
        s += o(a, ';'),
        document.cookie = s
      }
    }()
  };
  return window.zenjs.Browser = e,
  e
}),
define('wap/base/webpinfo', [
  'zenjs/util/ua',
  'zenjs/util/cookie'
], function (e, t) {
  function n() {
    if (1 === parseInt(t.cookie('_canwebp'), 10)) return s;
    if (2 === parseInt(t.cookie('_canwebp'), 10)) return a;
    var e;
    if (window.localStorage) try {
      'ok' === localStorage.getItem('canwebp') ? e = s : 'no' === localStorage.getItem('canwebp') && (e = a)
    } catch (n) {
      e = r
    }
    return e
  }
  function i() {
    return e.isWeixin() ? 'weixin' : e.isWxd() ? 'wxd' : e.isUC() ? 'uc-' + e.getUCVersion()  : e.isChrome() ? 'chrome-' + e.getChromeVersion()  : e.isSafari() ? 'safari-' + e.getSafariVersion()  : 'unknow'
  }
  function o() {
    return e.isIOS() ? 'ios-' + e.getIOSVersion()  : e.isAndroid() ? 'android-' + e.getAndroidVersion()  : void 0
  }
  var r = 2,
  a = 1,
  s = 0;
  return {
    canWebp: n(),
    browser: i(),
    system: o()
  }
}),
define('wap/base/log', [
  'zenjs/util/args',
  'wap/base/webpinfo'
], function (e, t) {
  var n = window.Zepto || window.jQuery || n,
  i = {
  };
  _global.spm = _global.spm || {
  };
  var o = function () {
    var t = function () {
      return _global.spm.logType + _global.spm.logId || 'fake' + _global.kdt_id
    };
    return function () {
      var i = e.get('spm');
      if (i = n.trim(i), '' !== i) {
        var o = i.split('_');
        o.length > 2 && (i = o[0] + '_' + o[o.length - 1]),
        i += '_' + t()
      } else i = t();
      return i
    }
  }(),
  r = function (t, i, o) {
    var r = new Image,
    a = Math.floor(2147483648 * Math.random()).toString(36),
    s = 'log_' + a,
    l = new n.Deferred;
    return window[s] = r,
    r.onload = r.onerror = r.onabort = function () {
      r.onload = r.onerror = r.onabort = null,
      window[s] = null,
      r = null,
      l.resolve()
    },
    i.link = window.location.href,
    i.time = (new Date).getTime(),
    r.src = e.add(t, i),
    window.setTimeout(l.resolve, 1500),
    l.promise()
  },
  a = function (e) {
    e = e || 'default';
    var t = {
      wxd: '//fx.tj.youzan.com/3.gif',
      wxdapp: '//app.tj.koudaitong.com/1.gif',
      'default': '//tj.koudaitong.com/1.gif',
      ua: '//tj.koudaitong.com/v1/ua'
    };
    return t[e]
  };
  i.log = function (e, t) {
    e.spm || (e.spm = i.getSpm()),
    e.referer_url || (e.referer_url = encodeURIComponent(document.referrer)),
    e.title || (e.title = _global.title || n.trim(document.title));
    var o = a(e.target);
    return delete e.target,
    r(o, e, t)
  },
  i.uaLog = function () {
    r(a('ua'), t)
  },
  i.getSpm = function () {
    return i.spm || (i.spm = o()),
    i.spm
  },
  window.Logger = i;
  var s = window.__logs;
  return s && s.length > 0 && s.forEach(i.log),
  i
}),
define('wap/base/logv2', [
  'zenjs/util/args'
], function () {
  var e = window.Zepto || window.jQuery || e,
  t = _global.kdt_id,
  n = function (n) {
    var o = _global.spm || {
    },
    r = {
      kdt_id: t,
      sf: i(),
      spm: (o.logType || '') + (o.logId || '')
    };
    n = e.extend(r, n);
    for (var a in n) '' === n[a] && delete n[a];
    'share' === n.fm && (n.url = window.location.href),
    e.ajax({
      url: 'http://tj.koudaitong.com/v1/fm',
      data: n
    })
  },
  i = function () {
    return zenjs.Args.get('sf')
  };
  return {
    log: n
  }
}),
define('zenjs/util/image', [
], function () {
  window.zenjs = window.zenjs || {
  },
  window.zenjs.Image = window.zenjs.Image || {
  };
  var e = {
  };
  return e.toWebp = function () {
    var e = /\.([^.!]+)\!([0-9]{1,4})x([0-9]{1,4})(\+2x)?\..+/,
    t = !1;
    try {
      t = 'ok' === window.localStorage.getItem('canwebp')
    } catch (n) {
    }
    return function (n) {
      var i = n,
      o = 1;
      if (t) {
        var r = i.match(e);
        r && r.length >= 4 && ('+2x' == r[4] && (o = 2), i = i.replace(e, '.') + r[1] + '?imageView2/2/w/' + parseInt(r[2]) * o + '/h/' + parseInt(r[3]) * o + '/q/75/format/' + ('gif' == r[1] ? 'gif' : 'webp'))
      } else {
        var r = i.match(e);
        r && r.length >= 4 && ('+2x' == r[4] && (o = 2), i = i.replace(e, '.') + r[1] + '?imageView2/2/w/' + parseInt(r[2]) * o + '/h/' + parseInt(r[3]) * o + '/q/75/format/' + r[1])
      }
      return i
    }
  }(),
  e.checkCanWebp = function () {
    var e = function (e) {
      var t = new Image;
      t.onload = t.onerror = function () {
        e(2 == t.height)
      },
      t.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA'
    };
    return function (t) {
      if ('object' == typeof window.localStorage) try {
        var n = localStorage.getItem('canwebp');
        'ok' == n ? zenjs.Browser.cookie('_canwebp', {
          value: '1',
          path: '/',
          domain: location.hostname,
          expires: 3650
        })  : 'no' != n && e(function (e) {
          localStorage.setItem('canwebp', e ? 'ok' : 'no'),
          e && zenjs.Browser.cookie('_canwebp', {
            value: '1',
            path: '/',
            domain: location.hostname,
            expires: 3650
          })
        })
      } catch (i) {
      }
    }
  }(),
  window.zenjs.Image = e,
  e
}),
window.zenjs = window.zenjs || {
},
function () {
  if (!window.zenjs.ready) {
    var e = /complete|loaded/;
    window.zenjs.ready = function (t) {
      e.test(document.readyState) && document.body ? setTimeout(t)  : window.addEventListener('load', t, !1)
    }
  }
}(),
define('zenjs/util/ready', function () {
}),
define('wap/base/lazy_load', [
  'wap/base/logv2',
  'wap/base/log',
  'zenjs/util/image',
  'zenjs/util/ready'
], function (e) {
  var t = window.Zepto || window.jQuery || t,
  n = t(window),
  i = Logger && Logger.getSpm() || '';
  t.fn.lazyload = function (e) {
    function i() {
      var e = 0;
      r.each(function () {
        var n = t(this);
        if (!a.skip_invisible || n.is(':visible')) if (t.abovethetop(this, a) || t.leftofbegin(this, a));
         else if (t.belowthefold(this, a) || t.rightoffold(this, a)) {
          if (++e > a.failure_limit) return !1
        } else n.trigger('appear'),
        e = 0
      })
    }
    var o,
    r = this,
    a = {
      threshold: 200,
      failure_limit: 0,
      event: 'scroll',
      effect: 'show',
      container: window,
      data_attribute: 'src',
      skip_invisible: !1,
      appear: null,
      load: null,
      placeholder: null
    };
    return e && (void 0 !== e.failurelimit && (e.failure_limit = e.failurelimit, delete e.failurelimit), void 0 !== e.effectspeed && (e.effect_speed = e.effectspeed, delete e.effectspeed), t.extend(a, e)),
    o = void 0 === a.container || a.container === window ? n : t(a.container),
    0 === a.event.indexOf('scroll') && o.bind(a.event, function () {
      return i()
    }),
    this.each(function () {
      var e = this,
      n = t(e),
      i = n[0].nodeName.toLowerCase();
      e.loaded = !1,
      'img' === i && (void 0 === n.attr('src') || n.attr('src') === !1) && n.is('img') && a.placeholder && n.attr('src', a.placeholder),
      n.one('appear', function () {
        if (!this.loaded) {
          if (a.appear) {
            var o = r.length;
            a.appear.call(e, o, a)
          }
          if ('img' === i) {
            var s = n.attr('data-' + a.data_attribute);
            s = zenjs.Image.toWebp(s),
            s = s.replace(/http:\/\/imgqn.koudaitong.com/gi, 'http://img.yzcdn.cn'),
            t('<img />').bind('load', function () {
              n.hide(),
              n.is('img') ? n.attr('src', s)  : n.css('background-image', 'url("' + s + '")'),
              n[a.effect](),
              e.loaded = !0;
              var i = t(e).parent();
              i.hasClass('photo-block') && i.css('background-color', '#fff');
              var o = t.grep(r, function (e) {
                return !e.loaded
              });
              if (r = t(o), a.load) {
                var l = r.length;
                a.load.call(e, l, a)
              }
            }).attr('src', s)
          } else if ('textarea' === i) {
            var l = n.parent();
            n.after(n.val()).remove(),
            t('.js-lazy', l).lazyload(),
            a.load && a.load.call(e, l, a)
          }
        }
      }),
      0 !== a.event.indexOf('scroll') && n.bind(a.event, function () {
        e.loaded || n.trigger('appear')
      })
    }),
    n.bind('resize', function () {
      i()
    }),
    /(?:iphone|ipod|ipad).*os 5/gi.test(navigator.appVersion) && n.bind('pageshow', function (e) {
      e.originalEvent && e.originalEvent.persisted && r.each(function () {
        t(this).trigger('appear')
      })
    }),
    t(document).ready(function () {
      i()
    }),
    this
  },
  t.fn.goodsLazyLoad = function () {
    this.lazyload({
      appear: function () {
        var n,
        o = t(this).parents('.js-goods').first().data('goods-id');
        n = i.lastIndexOf('_') === i.length - 1 ? i + 'SI' + o : i + '_SI' + o,
        window.Logger && Logger.log({
          spm: n,
          fm: 'display'
        }),
        e.log({
          fm: 'view',
          display_goods: o
        })
      }
    })
  },
  t.belowthefold = function (e, i) {
    var o;
    return o = void 0 === i.container || i.container === window ? (window.innerHeight ? window.innerHeight : n.height()) + n.scrollTop()  : t(i.container).offset().top + t(i.container).height(),
    o <= t(e).offset().top - i.threshold
  },
  t.rightoffold = function (e, i) {
    var o;
    return o = void 0 === i.container || i.container === window ? n.width() + n.scrollLeft()  : t(i.container).offset().left + t(i.container).width(),
    o <= t(e).offset().left - i.threshold
  },
  t.abovethetop = function (e, i) {
    var o;
    return o = void 0 === i.container || i.container === window ? n.scrollTop()  : t(i.container).offset().top,
    o >= t(e).offset().top + i.threshold + t(e).height()
  },
  t.leftofbegin = function (e, i) {
    var o;
    return o = void 0 === i.container || i.container === window ? n.scrollLeft()  : t(i.container).offset().left,
    o >= t(e).offset().left + i.threshold + t(e).width()
  },
  t.inviewport = function (e, n) {
    return !(t.rightoffold(e, n) || t.leftofbegin(e, n) || t.belowthefold(e, n) || t.abovethetop(e, n))
  },
  window.zenjs.ready && window.zenjs.ready(function () {
    t('.js-lazy').lazyload(),
    t('.js-goods-lazy').goodsLazyLoad()
  })
}),
define('zenjs/util/str/unescape', [
], function () {
  window.zenjs = window.zenjs || {
  };
  var e = window.zenjs;
  e.Str = e.Str || {
  };
  var t = function (e) {
    var t = {
      '&amp;': '&',
      '&lt;': '<',
      '&gt;': '>',
      '&quot;': '"',
      '&#x27;': '\''
    },
    n = /(\&amp;|\&lt;|\&gt;|\&quot;|\&#x27;)/g;
    return ('' + e).replace(n, function (e) {
      return t[e]
    })
  };
  return e.Str.unescape = t,
  {
    Str: {
      unescape: t
    }
  }
}),
define('wap/base/share', [
  'zenjs/util/args',
  'zenjs/util/str/unescape'
], function () {
  var e = window.Zepto || window.jQuery || e,
  t = window._global || {
  },
  n = t.share || {
  },
  i = function (e) {
    return e = e.replace(/http:\/\/imgqn.koudaitong.com/gi, 'http://img.yzcdn.cn'),
    0 === e.indexOf('http://img.yzcdn.cn') || 0 === e.indexOf('http://imgqntest.koudaitong.com') ? e.replace(/(\![0-9]+x[0-9]+.+)/g, '') + '!200x200.jpg' : e
  },
  o = function () {
    var t = 'http://static.koudaitong.com/v2/image/youzan_mall_logo.jpg',
    n = e('#wxcover'),
    i = null;
    return n && n.length > 0 ? (i = n.data('wxcover'), i && 0 !== i.length || (i = n.css('background-image'), i && 'none' != i ? (i = /^url\((['"]?)(.*)\1\)$/.exec(i), i = i ? i[2] : null)  : i = null))  : (n = null, e('.content img').each(function (t, i) {
      return e(i).hasClass('js-not-share') ? void 0 : (n = e(i), !1)
    }), n && n.length > 0 && (i = n[0].getAttribute('src') || n[0].getAttribute('data-src'))),
    i || (_global.mp_data || {
    }).logo || t
  },
  r = function (e) {
    e = e || document.documentURI;
    var t,
    n = Number(_global.kdt_id) || 0,
    i = [
      2737501,
      618192,
      618242,
      371189,
      1
    ],
    o = _global.youzan_share,
    r = Math.floor(9000 * Math.random()) + 1000;
    return i.indexOf(n) >= 0 && (n = 0),
    o ? (t = r + '.' + o, e = e.replace(/:\/\/.*\.koudaitong\.com/g, '://' + t + '.koudaitong.com'))  : (t = 0 === n ? '192168-' + r : 192168 + n, e = e.replace('://wap.', '://shop' + t + '.')),
    e = zenjs.Args.remove(e, 'redirect_count')
  },
  a = function () {
    var t = n.title || _global.title || e('#wxtitle').text() || document.title,
    a = n.link || r(),
    s = i(n.cover || o()),
    l = ((n.desc || e('#wxdesc').val() || e('#wxdesc').text() || e('.custom-richtext').text() || e('.content-body').text() || e('.content').text() || t || '') + '').replace(/\s*/g, '');
    return function () {
      t = window.__title || t,
      a = window.__link || a,
      s = window.__cover || s,
      l = window.__desc || l;
      var i,
      o = e('.time-line-title');
      return i = o.length > 0 ? o.val() || o.text()  : n.timeline_title,
      {
        title: zenjs.Str.unescape(t),
        link: a,
        img_url: s,
        desc: zenjs.Str.unescape(l).substring(0, 80),
        timeLineTitle: zenjs.Str.unescape((i || '').trim())
      }
    }
  }(),
  s = function () {
    var e = a(),
    t = window.zenjs.UA;
    if (t) if (t.isIOS()) {
      var n = '#func=sharePlatsAction&content=' + e.title + e.desc + '&content_url=' + e.link + '&pic=' + e.img_url;
      window.location.hash = '',
      window.location.href = n
    } else t.isAndroid() && window.android && window.android.sharePlatsAction && window.android.sharePlatsAction(e.title, e.link, e.img_url)
  };
  window.shareHook = s,
  window.getShareLink = r,
  window.getShareData = window.getShareData || a
}),
define('zenjs/class', [
  'require',
  'exports',
  'module'
], function (e, t, n) {
  var i = !1,
  o = /\b_super\b/,
  r = function () {
  };
  r.extend = function (e) {
    function t() {
      !i && this.init && this.init.apply(this, arguments)
    }
    var n = this.prototype;
    i = !0;
    var r = new this;
    i = !1;
    for (var a in e) r[a] = 'function' == typeof e[a] && 'function' == typeof n[a] && o.test(e[a]) ? function (e, t) {
      return function () {
        var i = this._super;
        this._super = n[e];
        var o = t.apply(this, arguments);
        return this._super = i,
        o
      }
    }(a, e[a])  : e[a];
    return t.prototype = r,
    t.prototype.constructor = t,
    t.extend = arguments.callee,
    t
  },
  n.exports = r
}),
define('zenjs/core/trigger_method', [
], function () {
  var e = function () {
    function e(e, t, n) {
      return n.toUpperCase()
    }
    function t(e, t, n) {
      return [].slice.call(e, null == t || n ? 1 : t)
    }
    var n = /(^|:)(\w)/gi;
    return function (i) {
      var o = 'on' + i.replace(n, e),
      r = this[o];
      return 'function' == typeof this.trigger && this.trigger.apply(this, arguments),
      'function' == typeof r ? r.apply(this, t(arguments))  : void 0
    }
  }();
  return e
}),
define('zenjs/events', [
  'require',
  'exports',
  'module',
  'zenjs/class',
  'zenjs/core/trigger_method'
], function (e, t, n) {
  var i = e('zenjs/class'),
  o = e('zenjs/core/trigger_method'),
  r = i.extend({
    on: function (e, t, n) {
      return this._events = this._events || {
      },
      this._events[e] = this._events[e] || [],
      this._events[e].push({
        callback: t,
        context: n,
        ctx: n || this
      }),
      this
    },
    off: function (e, t, n) {
      var i,
      o,
      r,
      a,
      s,
      l,
      c,
      d;
      if (!e && !t && !n) return this._events = {
      },
      this;
      for (a = e ? [
        e
      ] : Object.keys(this._events), s = 0, l = a.length; l > s; s++) if (e = a[s], r = this._events[e]) {
        if (this._events[e] = i = [
        ], t || n) for (c = 0, d = r.length; d > c; c++) o = r[c],
        (t && t !== o.callback && t !== o.callback._callback || n && n !== o.context) && i.push(o);
        i.length || delete this._events[e]
      }
      return this
    },
    trigger: function (e) {
      if (!this._events) return this;
      var t = [
      ].slice.call(arguments, 1),
      n = this._events[e];
      if (n) for (var i, o = - 1; ++o < n.length; ) (i = n[o]).callback.apply(i.ctx, t)
    },
    triggerMethod: o
  });
  n.exports = r
}),
define('wap/base/js_bridge', [
  'require',
  'zenjs/util/args',
  'zenjs/util/ua',
  'wap/base/share',
  'zenjs/events'
], function (e) {
  var t = window.Zepto || window.jQuery || t;
  e('zenjs/util/args'),
  e('zenjs/util/ua'),
  e('wap/base/share');
  var n = e('zenjs/events'),
  i = window.zenjs.UA,
  o = window.zenjs.Args,
  r = window.getShareData,
  a = n.extend({
    init: function (e) {
      if (this.on('share', this.doShare), this.doCall('webReady'), e.check_login) {
        this.on('userInfoReady', function (n) {
          n && n.user_id && n.user_id != e.fans_token && t.post(e.kdtunionUrl || '/v2/buyer/kdtunion/index.json', n).done(function (t) {
            t && 0 === t.code ? e.redirectUrl ? window.location.href = e.redirectUrl : window.location.reload()  : alert('登录失败请重试！')
          })
        });
        var n = this;
        setTimeout(function () {
          n.doCall('getData', {
            datatype: 'userInfo'
          })
        }, 50),
        setTimeout(function () {
          n.doCall('getUserInfo')
        }, 100)
      }
    },
    doCall: function (e, n) {
      if (i) if (i.isIOS()) {
        n = n || {
        },
        t.each(n, function (e, i) {
          (t.isPlainObject(i) || t.isArray(i)) && (n[e] = JSON.stringify(i))
        });
        var r = o.addParameter('youzanjs://' + e, n),
        a = document.createElement('iframe');
        a.style.width = '1px',
        a.style.height = '1px',
        a.style.display = 'none',
        a.src = r,
        document.body.appendChild(a),
        a.remove()
      } else i.isAndroid() && window.androidJS && window.androidJS[e] && window.androidJS[e](JSON.stringify(n))
    },
    doShare: function (e) {
      this.doCall('returnShareData', e || r())
    }
  });
  window.onReady('isReadyForYouZanJSBridge', function () {
    var e = window.YouzanJSBridgeOptions || {
    };
    window.YouzanJSBridge = new a({
      check_login: _global.ajax_acl_check || e.isNeedCheckLogin,
      fans_token: _global.fans_token,
      redirectUrl: e.redirectUrl,
      kdtunionUrl: _global.kdt_union_url
    })
  })
}),
define('wap/base/wx', [
  'wap/base/logv2',
  'zenjs/util/args',
  'wap/base/share'
], function (e) {
  window.wxReady = function (e) {
    if (window.WeixinJSBridge) e && e();
     else {
      var t = setTimeout(function () {
        window.WeixinJSBridge && e && e()
      }, 1000);
      document.addEventListener('WeixinJSBridgeReady', function () {
        clearTimeout(t),
        e && e()
      })
    }
  };
  var t = window._global || {
  },
  n = t.share || {
  },
  i = function (t) {
    t.fm = 'share',
    e.log(t)
  },
  o = function (e, t) {
    window.Logger && Logger.log({
      fm: 'share',
      title: e.title,
      link: encodeURIComponent(e.link),
      from: t
    })
  },
  r = function (e, t) {
    e.link = zenjs.Args.add(e.link, {
      sf: t
    })
  };
  wxReady(function () {
    var e = window.WeixinJSBridge;
    e && e.on && (e.call(n.notShare ? 'hideOptionMenu' : 'showOptionMenu'), e.on('menu:share:timeline', function () {
      if (!n.notShare) {
        window.doWhileShare && window.doWhileShare();
        var t = window.getShareData();
        t.timeLineTitle && (t.title = t.timeLineTitle);
        var a = 'wx_tl';
        r(t, a),
        e.invoke('shareTimeline', t, function (e) {
          window.__onShareTimeline && window.__onShareTimeline(e)
        }),
        o(t, 'timeline'),
        i({
          sf: a
        })
      }
    }), e.on('menu:share:appmessage', function () {
      if (!n.notShare) {
        window.doWhileShare && window.doWhileShare();
        var t = window.getShareData(),
        a = 'wx_sm';
        r(t, a),
        e.invoke('sendAppMessage', t, function () {
        }),
        o(t, 'appmessage'),
        i({
          sf: a
        })
      }
    }), e.on('menu:share:qq', function () {
      if (!n.notShare) {
        var t = 'qq_sm',
        o = window.getShareData();
        r(o, t),
        e.invoke('shareQQ', o, function () {
        }),
        i({
          sf: t
        })
      }
    }), e.on('menu::share:qzone', function () {
      if (!n.notShare) {
        var t = 'qq_zone',
        o = window.getShareData();
        r(o, t),
        e.invoke('shareQZone', o, function () {
        }),
        i({
          sf: t
        })
      }
    }))
  }),
  function () {
    var e = {
    };
    e.on = function () {
    },
    window.wx = e
  }()
}),
function (e, t) {
  function n() {
    return i ? i : (i = e('<div class="motify"><div class="motify-inner"></div></div>'), e('body').append(i), i)
  }
  var i,
  o,
  r = t.motify = t.motify || {
  };
  r.log = function (e, i, r) {
    var a = n(),
    s = this;
    'number' != typeof i && (i = 2000),
    a.show().find('.motify-inner').html(e || ' '),
    i > 0 && (t.clearTimeout(o), o = t.setTimeout(function () {
      r && r.apply(null),
      s.clear()
    }, 'function' != typeof r ? i : i + 300))
  },
  r.clear = function () {
    var e = n();
    e.hide()
  }
}(window.Zepto || window.jQuery || $, window),
define('wap/base/motify', function () {
}),
window.Zepto && function (e) {
  [
    'width',
    'height'
  ].forEach(function (t) {
    var n = t.replace(/./, function (e) {
      return e[0].toUpperCase()
    });
    e.fn['outer' + n] = function (e) {
      var n = this;
      if (n && n.length > 0) {
        var i = n[t](),
        o = {
          width: [
            'left',
            'right'
          ],
          height: [
            'top',
            'bottom'
          ]
        };
        return o[t].forEach(function (t) {
          e && (i += parseInt(n.css('margin-' + t), 10))
        }),
        i
      }
      return null
    }
  })
}(Zepto),
define('vendor/zepto/outer', function () {
}),
define('wap/components/footer_auto', [
  'vendor/zepto/outer'
], function (e) {
  var t = navigator.userAgent,
  n = [
    'MI',
    'NX507J',
    'SM701',
    'Coolpad'
  ],
  i = function () {
    for (var e = n.length - 1; e >= 0; e--) if (t.indexOf(n[e]) > - 1) return !0;
    return !1
  }(),
  o = 0 === $('.auto-footer-off').length ? !1 : !0;
  if (!o && !i) {
    var r = $(window).height(),
    a = $('.container'),
    s = $('.footer').length && $('.footer').outerHeight(!0) || 0,
    l = $('.js-footer-auto-ele'),
    c = r;
    if (0 === a.length) return;
    c -= s,
    l.length > 0 && (c -= l.outerHeight(!0)),
    a.css('min-height', c + 'px')
  }
}),
define('wap/base/make_url_log', [
  'zenjs/util/args',
  'wap/base/log'
], function (e) {
  var t = function (e) {
    return '' === e ? (new Date).getTime()  : e.indexOf('_') < 0 ? e + '_' + (new Date).getTime()  : (e = e.split('_'), e[1] + '_' + (new Date).getTime())
  },
  n = function (e) {
    if ('' === e) return '';
    var e = e.split('.'),
    t = (new Date).getTime(),
    n = _global.spm.logType + _global.spm.logId || 'fake' + _global.kdt_id;
    switch (e.length) {
      case 1:
        e.push(t);
      case 2:
        e.push(n);
        break;
      case 3:
        e.pop(),
        e.push(n)
    }
    return e.join('.')
  },
  i = zenjs.Args.get('mf'),
  o = zenjs.Args.get('sf'),
  r = zenjs.Args.get('reft') || '',
  a = n(zenjs.Args.get('track')),
  s = '';
  return window.Logger && (s = Logger.getSpm()),
  function (e) {
    return zenjs.Args.add(e, {
      reft: t(r),
      spm: s,
      sf: o,
      mf: i,
      track: a
    })
  }
}); var deviceIsAndroid = navigator.userAgent.indexOf('Android') > 0, deviceIsIOS = /iP(ad|hone|od)/.test(navigator.userAgent), deviceIsIOS4 = deviceIsIOS && /OS 4_\d(_\d)?/.test(navigator.userAgent), deviceIsIOSWithBadTarget = deviceIsIOS && /OS ([6-9]|\d{2})_\d/.test(navigator.userAgent), deviceIsBlackBerry10 = navigator.userAgent.indexOf('BB10') > 0; FastClick.prototype.needsClick = function (e) {
  switch (e.nodeName.toLowerCase()) {
    case 'button':
    case 'select':
    case 'textarea':
      if (e.disabled) return !0;
      break;
    case 'input':
      if (deviceIsIOS && 'file' === e.type || e.disabled) return !0;
      break;
    case 'label':
    case 'video':
      return !0
  }
  return /\bneedsclick\b/.test(e.className)
},
FastClick.prototype.needsFocus = function (e) {
  switch (e.nodeName.toLowerCase()) {
    case 'textarea':
      return !0;
    case 'select':
      return !deviceIsAndroid;
    case 'input':
      switch (e.type) {
        case 'button':
        case 'checkbox':
        case 'file':
        case 'image':
        case 'radio':
        case 'submit':
          return !1
      }
      return !e.disabled && !e.readOnly;
    default:
      return /\bneedsfocus\b/.test(e.className)
    }
},
FastClick.prototype.sendClick = function (e, t) {
  var n,
  i;
  document.activeElement && document.activeElement !== e && document.activeElement.blur(),
  i = t.changedTouches[0],
  n = document.createEvent('MouseEvents'),
  n.initMouseEvent(this.determineEventType(e), !0, !0, window, 1, i.screenX, i.screenY, i.clientX, i.clientY, !1, !1, !1, !1, 0, null),
  n.forwardedTouchEvent = !0,
  e.dispatchEvent(n)
},
FastClick.prototype.determineEventType = function (e) {
return deviceIsAndroid && 'select' === e.tagName.toLowerCase() ? 'mousedown' : 'click'
},
FastClick.prototype.focus = function (e) {
var t;
deviceIsIOS && e.setSelectionRange && 0 !== e.type.indexOf('date') && 'time' !== e.type ? (t = e.value.length, e.setSelectionRange(t, t))  : e.focus()
},
FastClick.prototype.updateScrollParent = function (e) {
var t,
n;
if (t = e.fastClickScrollParent, !t || !t.contains(e)) {
n = e;
do {
if (n.scrollHeight > n.offsetHeight) {
  t = n,
  e.fastClickScrollParent = n;
  break
}
n = n.parentElement
} while (n)
}
t && (t.fastClickLastScrollTop = t.scrollTop)
},
FastClick.prototype.getTargetElementFromEventTarget = function (e) {
return e.nodeType === Node.TEXT_NODE ? e.parentNode : e
},
FastClick.prototype.onTouchStart = function (e) {
var t,
n,
i;
if (e.targetTouches.length > 1) return !0;
if (t = this.getTargetElementFromEventTarget(e.target), n = e.targetTouches[0], deviceIsIOS) {
if (i = window.getSelection(), i.rangeCount && !i.isCollapsed) return !0;
if (!deviceIsIOS4) {
if (n.identifier === this.lastTouchIdentifier) return e.preventDefault(),
!1;
this.lastTouchIdentifier = n.identifier,
this.updateScrollParent(t)
}
}
return this.trackingClick = !0,
this.trackingClickStart = e.timeStamp,
this.targetElement = t,
this.touchStartX = n.pageX,
this.touchStartY = n.pageY,
e.timeStamp - this.lastClickTime < this.tapDelay && e.preventDefault(),
!0
},
FastClick.prototype.touchHasMoved = function (e) {
var t = e.changedTouches[0],
n = this.touchBoundary;
return Math.abs(t.pageX - this.touchStartX) > n || Math.abs(t.pageY - this.touchStartY) > n ? !0 : !1
},
FastClick.prototype.onTouchMove = function (e) {
return this.trackingClick ? ((this.targetElement !== this.getTargetElementFromEventTarget(e.target) || this.touchHasMoved(e)) && (this.trackingClick = !1, this.targetElement = null), !0)  : !0
},
FastClick.prototype.findControl = function (e) {
return void 0 !== e.control ? e.control : e.htmlFor ? document.getElementById(e.htmlFor)  : e.querySelector('button, input:not([type=hidden]), keygen, meter, output, progress, select, textarea')
},
FastClick.prototype.onTouchEnd = function (e) {
var t,
n,
i,
o,
r,
a = this.targetElement;
if (!this.trackingClick) return !0;
if (e.timeStamp - this.lastClickTime < this.tapDelay) return this.cancelNextClick = !0,
!0;
if (this.cancelNextClick = !1, this.lastClickTime = e.timeStamp, n = this.trackingClickStart, this.trackingClick = !1, this.trackingClickStart = 0, deviceIsIOSWithBadTarget && (r = e.changedTouches[0], a = document.elementFromPoint(r.pageX - window.pageXOffset, r.pageY - window.pageYOffset) || a, a.fastClickScrollParent = this.targetElement.fastClickScrollParent), i = a.tagName.toLowerCase(), 'label' === i) {
if (t = this.findControl(a)) {
if (this.focus(a), deviceIsAndroid) return !1;
a = t
}
} else if (this.needsFocus(a)) return e.timeStamp - n > 100 || deviceIsIOS && window.top !== window && 'input' === i ? (this.targetElement = null, !1)  : (this.focus(a), this.sendClick(a, e), deviceIsIOS && 'select' === i || (this.targetElement = null, e.preventDefault()), !1);
return deviceIsIOS && !deviceIsIOS4 && (o = a.fastClickScrollParent, o && o.fastClickLastScrollTop !== o.scrollTop) ? !0 : (this.needsClick(a) || (e.preventDefault(), this.sendClick(a, e)), !1)
},
FastClick.prototype.onTouchCancel = function () {
this.trackingClick = !1,
this.targetElement = null
},
FastClick.prototype.onMouse = function (e) {
return this.targetElement ? e.forwardedTouchEvent ? !0 : e.cancelable && (!this.needsClick(this.targetElement) || this.cancelNextClick) ? (e.stopImmediatePropagation ? e.stopImmediatePropagation()  : e.propagationStopped = !0, e.stopPropagation(), e.preventDefault(), !1)  : !0 : !0
},
FastClick.prototype.onClick = function (e) {
var t;
return this.trackingClick ? (this.targetElement = null, this.trackingClick = !1, !0)  : 'submit' === e.target.type && 0 === e.detail ? !0 : (t = this.onMouse(e), t || (this.targetElement = null), t)
},
FastClick.prototype.destroy = function () {
var e = this.layer;
deviceIsAndroid && (e.removeEventListener('mouseover', this.onMouse, !0), e.removeEventListener('mousedown', this.onMouse, !0), e.removeEventListener('mouseup', this.onMouse, !0)),
e.removeEventListener('click', this.onClick, !0),
e.removeEventListener('touchstart', this.onTouchStart, !1),
e.removeEventListener('touchmove', this.onTouchMove, !1),
e.removeEventListener('touchend', this.onTouchEnd, !1),
e.removeEventListener('touchcancel', this.onTouchCancel, !1)
},
FastClick.notNeeded = function (e) {
var t,
n,
i;
if ('undefined' == typeof window.ontouchstart) return !0;
if (n = + (/Chrome\/([0-9]+)/.exec(navigator.userAgent) || [,
0]) [1]) {
if (!deviceIsAndroid) return !0;
if (t = document.querySelector('meta[name=viewport]')) {
if ( - 1 !== t.content.indexOf('user-scalable=no')) return !0;
if (n > 31 && document.documentElement.scrollWidth <= window.outerWidth) return !0
}
}
if (deviceIsBlackBerry10 && (i = navigator.userAgent.match(/Version\/([0-9]*)\.([0-9]*)/), i[1] >= 10 && i[2] >= 3 && (t = document.querySelector('meta[name=viewport]')))) {
if ( - 1 !== t.content.indexOf('user-scalable=no')) return !0;
if (document.documentElement.scrollWidth <= window.outerWidth) return !0
}
return 'none' === e.style.msTouchAction ? !0 : !1;
},
FastClick.attach = function (e, t) {
return new FastClick(e, t)
},
window.FastClick = FastClick,
define('vendor/fastclick_release', function () {
}),
define('wap/base/base', [
'wap/base/logv2',
'wap/base/make_url_log',
'wap/base/log',
'zenjs/util/ua',
'vendor/fastclick_release',
'zenjs/util/cookie',
'zenjs/util/ready'
], function (e, t) {
document.addEventListener('click', function () {
}, !0);
var n = window.Zepto || window.jQuery || n;
n.kdt = n.kdt || {
};
window.zenjs.UA;
n.extend(n.kdt, {
openLink: function (e, t) {
if (void 0 !== e && null !== e) if (t = t || !1) {
var n = window.open(e, '_blank');
n.focus()
} else location.href = e
}
});
var i = function (t) {
var n = t.attr('class') || '';
n.indexOf('js-') < 0 && (t = t.closest('[class*=js-]'));
var i = /js-(\S+)/,
o = t.attr('class').match(i),
r = t.data('title') || t.text().trim();
o && o.length > 0 && e.log({
fm: 'click',
ck: o[1],
title: r
})
};
n('body').on('click', '[class*=js-]', function (e) {
i(n(e.target))
});
var o = !0;
n(document).on('click', 'a', function (e) {
o = !1;
var i = n(this),
r = i.attr('href'),
a = '_blank' === i.attr('target'),
s = i.data('goods-id'),
l = i.prop('title') || i.text(),
c = n.trim(r);
if (0 !== r.indexOf('#') && '' !== c && 0 !== c.indexOf('javascript') && 0 !== c.indexOf('tel') && !i.hasClass('js-no-follow')) {
var d = zenjs.Args.get('kdtfrom'),
u = zenjs.Args.get('from');
(d || u) && (r = zenjs.Args.add(r, {
kdtfrom: d,
from: u
}));
var f = r;
r.match(/^https?:\/\/\S*\.?(koudaitong\.com|kdt\.im|youzan\.com)/) && (f = t(r));
var g = {
fm: 'click',
url: r,
title: n.trim(l)
};
e.fromMenu && n.extend(g, {
click_type: 'menu'
}),
null !== s && void 0 !== s && n.extend(g, {
click_id: s
}),
window.Logger && Logger.log(g).then(function () {
(zenjs.UA.isMobile() || !a) && n.kdt.openLink(f)
}),
zenjs.UA.isMobile() || !a ? e.preventDefault()  : i.attr('href', f)
}
}),
window.Logger && Logger.log({
fm: 'display',
display_goods: ''
});
var r = (n(document.documentElement), n('.js-mp-info')),
a = window.navigator.userAgent,
s = a.match(/MicroMessenger\/(\d+(\.\d+)*)/),
l = null !== s && s.length,
c = l ? s[1] : '',
d = c.split('.'),
u = [
5,
2,
0
],
f = !0;
for (var g in u) {
if (!d[g]) break;
if (parseInt(d[g]) < u[g]) {
f = !0;
break
}
if (parseInt(d[g]) > u[g]) {
f = !1;
break
}
}
var p = zenjs.UA.isAndroid() && zenjs.UA.isWeixin() && f;
p || r.on('click', '.js-follow-mp', function () {
return window.showGuide && window.showGuide('follow'),
!1
});
var h = zenjs.Args.get('promote'),
w = zenjs.Args.get('from'),
v = n('a');
h && v.each(function () {
var e = n(this),
t = e.attr('href');
t = zenjs.Args.add(t, {
promote: h
}),
t && 0 !== t.indexOf('tel') && e.attr('href', t)
}),
w && v.each(function () {
var e = n(this),
t = e.attr('href');
t = zenjs.Args.add(t, {
from: w
}),
t && 0 !== t.indexOf('tel') && e.attr('href', t)
}),
window.onbeforeunload = function (t) {
o !== !1 && e.log({
fm: 'close'
})
},
window.zenjs.ready && window.zenjs.ready(function () {
var e = document.getElementById('footer-delay');
e && (e.parentNode.innerHTML += e.value),
document.getElementsByClassName('vote-page').length || FastClick && FastClick.attach(document.body),
window.Logger && window.Logger.uaLog()
});
var m = function (e) {
var t = new Image;
t.onload = t.onerror = function () {
e(2 == t.height)
},
t.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA'
};
if (window.localStorage) try {
var k = localStorage.getItem('canwebp');
'ok' == k ? zenjs.Browser.cookie('_canwebp', {
value: '1',
path: '/',
domain: 'koudaitong.com',
expires: 3650
})  : 'no' != k && m(function (e) {
localStorage.setItem('canwebp', e ? 'ok' : 'no'),
e && zenjs.Browser.cookie('_canwebp', {
value: '1',
path: '/',
domain: 'koudaitong.com',
expires: 3650
})
})
} catch (b) {
}
}),

define('main', function () {
});
$(function(){
$(".nav-item .mainmenu").click(function(){
	$(this).siblings(".submenu").toggle();})
$(".proListT-pro-buy").click(function(){
	//$(".addcart").show(300);
	$("#right-icon .icon").addClass("s0")
	})
	//$(".closeit,.confirm_addr").click(function(){
	//	$(this).parent().parent().parent().parent().animate({height:'hide',speed:300});
	//	})
      $(".closeit").click(function(){
        $(this).parent().parent().parent().parent().animate({height:'hide',speed:300});
      })
		$(".addmessage").click(function(){
			$(this).parent().parent().parent().parent().parent().hide();
			$(".addressEdit1").show(300);

			})
		$(".addressEdit .default").click(function(){
			$(".by_confirm").hide();
		})
	$(".orderEdit .scMeCon").click(function(){
	$(".addressEdit").show(300);

	$("#right-icon .icon").addClass("s0")
	})

	$(".addCart,.sku-cancel").click(function(){
		$(".addcart").hide(300);
		})
		})
