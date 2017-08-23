// JavaScript Document
define('zenjs/ui/anchor', [
  'zenjs/class'
], function (t) {
  var i = t.extend({
    init: function (t) {
      this.options = t,
      this.el = t.el.find('a'),
      this.offsetY = t.offsetY || 0,
      this.currentClass = t.currentClass || 'current',
      this.scroller = $('html, body'),
      this.anchorParentNode = this.el.parent(),
      this.offsetTopList = [
      ];
      var i = $(window),
      o = this;
      i.scroll(function () {
        o.checkoutCurrentAnchor(i.scrollTop()),
        o.el.each(function () {
          o.offsetTopList.push($(this.hash).offset().top)
        })
      }),
      this.onClickedAnchor()
    },
    checkoutCurrentAnchor: function (t) {
      var i = this,
      o = 0;
      this.el.each(function (n) {
        var s = i.offsetTopList[n] + i.offsetY;
        Math.abs(t - s);
        t >= s && (o = n)
      }),
      this.anchorParentNode.removeClass(this.currentClass).eq(o).addClass(this.currentClass)
    },
    onClickedAnchor: function () {
      var t = this;
      this.el.on('click', function () {
        var i = $(this).parent(),
        o = $(this.hash);
        return setTimeout(function () {
          i.addClass(t.currentClass),
          i.siblings().removeClass(t.currentClass)
        }, 20),
        t.scroller.scrollTop(o.offset().top + t.options.offsetY),
        !1
      })
    }
  });
  return i
}),
window.Utils = window.Utils || {
},
$.extend(window.Utils, {
  makeRandomString: function (t) {
    var i = '',
    o = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    t = t || 10;
    for (var n = 0; t > n; n++) i += o.charAt(Math.floor(Math.random() * o.length));
    return i
  }
}),
define('wap/components/util/number', function () {
}),
define('wap/components/pop', [
  'zenjs/events',
  'wap/components/util/number'
], function (t) {
  var i = function () {
  };
  window.zenjs = window.zenjs || {
  };
  var o = t.extend({
    init: function (t) {
      this._window = $(window);
      var o = window.Utils.makeRandomString();
      $('body').append('<div id="' + o + '"                 style="display:none; height: 100%;                 position: fixed; top: 0; left: 0; right: 0;                background-color: rgba(0, 0, 0, ' + (t.transparent || '.9') + ');z-index:1000;opacity:0;transition: opacity ease 0.2s;"></div>'),
      this.nBg = $('#' + o),
      this.nBg.on('click', $.proxy(function () {
        this.isCanNotHide || this.hide()
      }, this));
      var n = window.Utils.makeRandomString();
      $('body').append('<div id="' + n + '" class="' + (t.className || '') + '" style="overflow:hidden;visibility: hidden;"></div>'),
      this.nPopContainer = $('#' + n),
      this.nPopContainer.hide(),
      t.contentViewClass && (this.contentViewClass = t.contentViewClass, this.contentViewOptions = $.extend({
        el: this.nPopContainer
      }, t.contentViewOptions || {
      }), this.contentView = new this.contentViewClass($.extend({
        onHide: $.proxy(this.hide, this)
      }, this.contentViewOptions)), this.contentView.onHide = $.proxy(this.hide, this)),
      this.animationTime = t.animationTime || 300,
      this.isCanNotHide = t.isCanNotHide,
      this.doNotRemoveOnHide = t.doNotRemoveOnHide || !1,
      this.onShow = t.onShow || i,
      this.onHide = t.onHide || i,
      this.onFinishHide = t.onFinishHide || i,
      this.html = t.html
    },
    render: function (t) {
      return this.renderOptions = t || {
      },
      this.contentViewClass ? this.contentView.render(this.renderOptions)  : this.html && this.nPopContainer.html(this.html),
      this
    },
    show: function () {
      return this.top = this._window.scrollTop(),
      this.nBg.show().css({
        opacity: '1',
        'transition-property': 'none'
      }),
      this.nPopContainer.show(),
      setTimeout($.proxy(function () {
        this._window.scrollTop(0),
        this.startShow(),
        this.nPopContainer.show().css('visibility', 'visible'),
        this._doShow && this._doShow(),
        this.onShow()
      }, this), 200),
      this
    },
    hide: function () {
      var t,
      i = function () {
        return t !== this._window.scrollTop() ? (this._window.scrollTop(t), void setTimeout($.proxy(i, this)))  : void setTimeout($.proxy(this.onFinishHide, this), 50)
      };
      return function (o) {
        o = o || {
        };
        var n = o.doNotRemove || this.doNotRemoveOnHide || !1;
        this._doHide && this._doHide(),
        setTimeout($.proxy(function () {
          this.startHide(),
          t = this.top,
          this._window.scrollTop(t),
          $.proxy(i, this) (),
          this.nBg.css({
            opacity: 0,
            'transition-property': 'opacity'
          }),
          setTimeout($.proxy(function () {
            this.nBg.hide(),
            this.nPopContainer.hide(),
            n || this.destroy(),
            window.zenjs.popList.length < 1 && $('html').css('position', this.htmlPosition)
          }, this), 200)
        }, this), this.animationTime),
        this.onHide()
      }
    }(),
    destroy: function () {
      return this.nPopContainer.remove(),
      this.nBg.remove(),
      this.contentView && this.contentView.remove(),
      this
    },
    startShow: function () {
      var t = window.zenjs.popList;
      if (t || (t = window.zenjs.popList = [
      ]), 0 === t.length) {
        var i = $('body'),
        o = $('html');
        this.htmlPosition = o.css('position'),
        o.css('position', 'relative'),
        this.bodyCss = (i.attr('style') || {
        }).cssText,
        this.htmlCss = (o.attr('style') || {
        }).cssText,
        $('body,html').css({
          overflow: 'hidden',
          height: this._window.height()
        })
      }
      t.indexOf(this) < 0 && t.push(this)
    },
    startHide: function () {
      var t = window.zenjs.popList,
      i = t.indexOf(this);
      i > - 1 && t.splice(i, 1),
      t.length < 1 && ($('html').attr('style', this.htmlCss || ''), $('body').attr('style', this.bodyCss || ''))
    }
  });
  return o
}),
define('wap/components/popup', [
  'wap/components/pop'
], function (t) {
  var i = t.extend({
    init: function (t) {
      this._super(t),
      this.onClickBg = t.onClickBg || function () {
      },
      this.onBeforePopupShow = t.onBeforePopupShow || function () {
      },
      this.onAfterPopupHide = t.onAfterPopupHide || function () {
      },
      this.nPopContainer.css(_.extend({
        left: 0,
        right: 0,
        bottom: 0,
        background: 'white'
      }, t.containerCss || {
      })),
      this.nPopContainer.css('opacity', '0')
    },
    _doShow: function () {
      this.contentView && this.contentView.height ? this.height = this.contentView.height()  : this.contentView || (this.height = this.nPopContainer.height()),
      this.onBeforePopupShow(),
      $('.js-close').click($.proxy(function (t) {
        this.hide()
      }, this)),
      this.nPopContainer.css({
        height: this.height + 'px',
        transform: 'translate3d(0,100%,0)',
        '-webkit-transform': 'translate3d(0,100%,0)',
        opacity: 0,
        position: 'absolute',
        'z-index': 1000
      }),
      this.bodyPadding = $('body').css('padding'),
      $('body').css('padding', '0px'),
      setTimeout($.proxy(function () {
        this.nPopContainer.css({
          transform: 'translate3d(0,0,0)',
          '-webkit-transform': 'translate3d(0,0,0)',
          '-webkit-transition': 'all ease ' + this.animationTime + 'ms',
          transition: 'all ease ' + this.animationTime + 'ms',
          opacity: 1
        })
      }, this)),
      setTimeout($.proxy(function () {
        this.contentView && this.contentView.onAfterPopupShow && this.contentView.onAfterPopupShow()
      }, this), this.animationTime)
    },
    _doHide: function (t) {
      this.nPopContainer.css({
        transform: 'translate3d(0,100%,0)',
        '-webkit-transform': 'translate3d(0,100%,0)',
        opacity: 0
      }),
      setTimeout($.proxy(function () {
        $('body').css('padding', this.bodyPadding),
        this.onAfterPopupHide()
      }, this), this.animationTime)
    }
  });
  return i
}),
define('zenjs/ui/scroll_to_fixed', [
  'zenjs/class'
], function (t) {
  var i = t.extend({
    init: function (t) {
      if (this.fixedElem = t.fixedElem, this.hiddenBox = t.hiddenBox || null, this.offsetToTop = t.offsetToTop || 0, this.hiddenBox) {
        var i = this.fixedElem.height(),
        o = this.hiddenBox.height();
        this.disH = o - i
      }
      var n = this;
      $(document).on('touchmove scroll', function () {
        n.scrollOnFix()
      })
    },
    scrollOnFix: function () {
      var t = $('body').scrollTop(),
      i = this.fixedElem.parent().offset().top,
      o = t - i,
      n = {
        position: 'fixed',
        top: this.offsetToTop,
		borderRight:'1px solid #dcdcdc'
      },
      s = {
        position: 'absolute',
        bottom: 0,
		borderRight:'none'
      };
      if (o >= 0) {
        if (this.fixedElem.css(n), !this.hiddenBox) return;
        this.fixedElem.removeAttr('style'),
        this.fixedElem.css(o >= this.disH ? s : n)
      } else this.fixedElem.css({
        position: 'static',
		borderRight:'none'
      })
    }
  });
  return i
}),
require(['zenjs/ui/anchor',
'wap/components/popup',
'zenjs/ui/scroll_to_fixed',
'vendor/zepto/outer'], function (t, i, o) {
  !function (n) {
    var s = n('.proListT'),
    e = s.find('.proListT-menuL');
    n.each(e, function (t, i) {
      var o = n(i),
      s = n(i).parents('.proListT').height();
      o.parent().css('height', s)
    }),
    n.each(e, function (i, s) {
      var e = n(s);
      new o({
        fixedElem: e,
        hiddenBox: e.parents('.proListT'),
        offsetToTop: 40
      }),
      new t({
        el: e,
        offsetY: 40,
        currentClass: 'current'
      })
    });

  }(window.$)
}),
define('main', function () {
});
