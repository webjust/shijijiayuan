/*!
 * jQuery Tools v1.2.7 - The missing UI library for the Web
 *
 * overlay/overlay.js
 * scrollable/scrollable.js
 * tabs/tabs.js
 * tooltip/tooltip.js
 *
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 *
 * http://flowplayer.org/tools/
 *
 */
(function(d) {
	function n(c, a, b) {
		var e = this,
			l = c.add(this),
			g = c.find(b.tabs),
			f = a.jquery ? a : c.children(a),
			i;
		g.length || (g = c.children());
		f.length || (f = c.parent().find(a));
		f.length || (f = d(a));
		d.extend(this, {
			click: function(a, h) {
				var f = g.eq(a),
					j = !c.data("tabs");
				"string" == typeof a && a.replace("#", "") && (f = g.filter('[href*="' + a.replace("#", "") + '"]'), a = Math.max(g.index(f), 0));
				if (b.rotate) {
					var k = g.length - 1;
					if (0 > a) return e.click(k, h);
					if (a > k) return e.click(0, h)
				}
				if (!f.length) {
					if (0 <= i) return e;
					a = b.initialIndex;
					f = g.eq(a)
				}
				if (a === i) return e;
				h = h || d.Event();
				h.type = "onBeforeClick";
				l.trigger(h, [a]);
				if (!h.isDefaultPrevented()) return m[j ? b.initialEffect && b.effect || "default" : b.effect].call(e, a, function() {
					i = a;
					h.type = "onClick";
					l.trigger(h, [a])
				}), g.removeClass(b.current), f.addClass(b.current), e
			},
			getConf: function() {
				return b
			},
			getTabs: function() {
				return g
			},
			getPanes: function() {
				return f
			},
			getCurrentPane: function() {
				return f.eq(i)
			},
			getCurrentTab: function() {
				return g.eq(i)
			},
			getIndex: function() {
				return i
			},
			next: function() {
				return e.click(i + 1)
			},
			prev: function() {
				return e.click(i - 1)
			},
			destroy: function() {
				g.off(b.event).removeClass(b.current);
				f.find('a[href^="#"]').off("click.T");
				return e
			}
		});
		d.each(["onBeforeClick", "onClick"], function(a, c) {
			if (d.isFunction(b[c])) d(e).on(c, b[c]);
			e[c] = function(a) {
				if (a) d(e).on(c, a);
				return e
			}
		});
		b.history && d.fn.history && (d.tools.history.init(g), b.event = "history");
		g.each(function(a) {
			d(this).on(b.event, function(b) {
				e.click(a, b);
				return b.preventDefault()
			})
		});
		f.find('a[href^="#"]').on("click.T", function(a) {
			e.click(d(this).attr("href"), a)
		});
		location.hash && "a" == b.tabs && c.find('[href="' + location.hash + '"]').length ? e.click(location.hash) : (0 === b.initialIndex || 0 < b.initialIndex) && e.click(b.initialIndex)
	}
	d.tools = d.tools || {
		version: "@VERSION"
	};
	d.tools.tabs = {
		conf: {
			tabs: "a",
			current: "current",
			onBeforeClick: null,
			onClick: null,
			effect: "default",
			initialEffect: !1,
			initialIndex: 0,
			event: "click",
			rotate: !1,
			slideUpSpeed: 400,
			slideDownSpeed: 400,
			history: !1
		},
		addEffect: function(c, a) {
			m[c] = a
		}
	};
	var m = {
		"default": function(c, a) {
			this.getPanes().hide().eq(c).show();
			a.call()
		},
		fade: function(c, a) {
			var b = this.getConf(),
				e = b.fadeOutSpeed,
				d = this.getPanes();
			e ? d.fadeOut(e) : d.hide();
			d.eq(c).fadeIn(b.fadeInSpeed, a)
		},
		slide: function(c, a) {
			var b = this.getConf();
			this.getPanes().slideUp(b.slideUpSpeed);
			this.getPanes().eq(c).slideDown(b.slideDownSpeed, a)
		},
		ajax: function(c, a) {
			this.getPanes().eq(0).load(this.getTabs().eq(c).attr("href"), a)
		}
	},
		j, k;
	d.tools.tabs.addEffect("horizontal", function(c, a) {
		if (!j) {
			var b = this.getPanes().eq(c),
				e = this.getCurrentPane();
			k || (k = this.getPanes().eq(0).width());
			j = !0;
			b.show();
			e.animate({
				width: 0
			}, {
				step: function(a) {
					b.css("width", k - a)
				},
				complete: function() {
					d(this).hide();
					a.call();
					j = !1
				}
			});
			e.length || (a.call(), j = !1)
		}
	});
	d.fn.tabs = function(c, a) {
		var b = this.data("tabs");
		b && (b.destroy(), this.removeData("tabs"));
		d.isFunction(a) && (a = {
			onBeforeClick: a
		});
		a = d.extend({}, d.tools.tabs.conf, a);
		this.each(function() {
			b = new n(d(this), c, a);
			d(this).data("tabs", b)
		});
		return a.api ? b : this
	}
})(jQuery);