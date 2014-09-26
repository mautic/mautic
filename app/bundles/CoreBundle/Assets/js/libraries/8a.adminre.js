/*! ========================================================================
 * Core v1.2.0
 * Copyright 2014 pampersdry
 * ========================================================================
 *
 * pampersdry@gmail.com
 *
 * This script will be use in my other projects too.
 * Your support ensure the continuity of this script and it projects.
 * ======================================================================== */

if (typeof jQuery === "undefined") { throw new Error("This application requires jQuery"); }

/* ========================================================================
 * BEGIN INCLUDING 3RD PARTY SCRIPT THAT WILL BE UTILIZE BY THIS SCRIPT.
 *
 * IMPORTANT : Do not delete this below script as it will break the template
 * behavior.
 * ========================================================================
 * Placeholders.js v2.0.8
 * Src : https://github.com/mathiasbynens/jquery-placeholder
 * ======================================================================== */
!function(e,a,t){function l(e){var a={},l=/^jQuery\d+$/;return t.each(e.attributes,function(e,t){t.specified&&!l.test(t.name)&&(a[t.name]=t.value)}),a}function r(e,a){var l=this,r=t(l);if(l.value==r.attr("placeholder")&&r.hasClass("placeholder"))if(r.data("placeholder-password")){if(r=r.hide().next().show().attr("id",r.removeAttr("id").data("placeholder-id")),e===!0)return r[0].value=a;r.focus()}else l.value="",r.removeClass("placeholder"),l==d()&&l.select()}function o(){var e,a=this,o=t(a),d=this.id;if(""==a.value){if("password"==a.type){if(!o.data("placeholder-textinput")){try{e=o.clone().attr({type:"text"})}catch(c){e=t("<input>").attr(t.extend(l(this),{type:"text"}))}e.removeAttr("name").data({"placeholder-password":o,"placeholder-id":d}).bind("focus.placeholder",r),o.data({"placeholder-textinput":e,"placeholder-id":d}).before(e)}o=o.removeAttr("id").hide().prev().attr("id",d).show()}o.addClass("placeholder"),o[0].value=o.attr("placeholder")}else o.removeClass("placeholder")}function d(){try{return a.activeElement}catch(e){}}var c,n,i="[object OperaMini]"==Object.prototype.toString.call(e.operamini),p="placeholder"in a.createElement("input")&&!i,u="placeholder"in a.createElement("textarea")&&!i,h=t.fn,s=t.valHooks,v=t.propHooks;p&&u?(n=h.placeholder=function(){return this},n.input=n.textarea=!0):(n=h.placeholder=function(){var e=this;return e.filter((p?"textarea":":input")+"[placeholder]").not(".placeholder").bind({"focus.placeholder":r,"blur.placeholder":o}).data("placeholder-enabled",!0).trigger("blur.placeholder"),e},n.input=p,n.textarea=u,c={get:function(e){var a=t(e),l=a.data("placeholder-password");return l?l[0].value:a.data("placeholder-enabled")&&a.hasClass("placeholder")?"":e.value},set:function(e,a){var l=t(e),c=l.data("placeholder-password");return c?c[0].value=a:l.data("placeholder-enabled")?(""==a?(e.value=a,e!=d()&&o.call(e)):l.hasClass("placeholder")?r.call(e,!0,a)||(e.value=a):e.value=a,l):e.value=a}},p||(s.input=c,v.value=c),u||(s.textarea=c,v.value=c),t(function(){t(a).delegate("form","submit.placeholder",function(){var e=t(".placeholder",this).each(r);setTimeout(function(){e.each(o)},10)})}),t(e).bind("beforeunload.placeholder",function(){t(".placeholder").each(function(){this.value=""})}))}(this,document,jQuery);

/* ========================================================================
 * FastClick.js v1.0.0
 * Src : https://github.com/ftlabs/fastclick
 * ======================================================================== */
function FastClick(t,e){"use strict";function i(t,e){return function(){return t.apply(e,arguments)}}var n;if(e=e||{},this.trackingClick=!1,this.trackingClickStart=0,this.targetElement=null,this.touchStartX=0,this.touchStartY=0,this.lastTouchIdentifier=0,this.touchBoundary=e.touchBoundary||10,this.layer=t,this.tapDelay=e.tapDelay||200,!FastClick.notNeeded(t)){for(var o=["onMouse","onClick","onTouchStart","onTouchMove","onTouchEnd","onTouchCancel"],s=this,r=0,c=o.length;c>r;r++)s[o[r]]=i(s[o[r]],s);deviceIsAndroid&&(t.addEventListener("mouseover",this.onMouse,!0),t.addEventListener("mousedown",this.onMouse,!0),t.addEventListener("mouseup",this.onMouse,!0)),t.addEventListener("click",this.onClick,!0),t.addEventListener("touchstart",this.onTouchStart,!1),t.addEventListener("touchmove",this.onTouchMove,!1),t.addEventListener("touchend",this.onTouchEnd,!1),t.addEventListener("touchcancel",this.onTouchCancel,!1),Event.prototype.stopImmediatePropagation||(t.removeEventListener=function(e,i,n){var o=Node.prototype.removeEventListener;"click"===e?o.call(t,e,i.hijacked||i,n):o.call(t,e,i,n)},t.addEventListener=function(e,i,n){var o=Node.prototype.addEventListener;"click"===e?o.call(t,e,i.hijacked||(i.hijacked=function(t){t.propagationStopped||i(t)}),n):o.call(t,e,i,n)}),"function"==typeof t.onclick&&(n=t.onclick,t.addEventListener("click",function(t){n(t)},!1),t.onclick=null)}}var deviceIsAndroid=navigator.userAgent.indexOf("Android")>0,deviceIsIOS=/iP(ad|hone|od)/.test(navigator.userAgent),deviceIsIOS4=deviceIsIOS&&/OS 4_\d(_\d)?/.test(navigator.userAgent),deviceIsIOSWithBadTarget=deviceIsIOS&&/OS ([6-9]|\d{2})_\d/.test(navigator.userAgent);FastClick.prototype.needsClick=function(t){"use strict";switch(t.nodeName.toLowerCase()){case"button":case"select":case"textarea":if(t.disabled)return!0;break;case"input":if(deviceIsIOS&&"file"===t.type||t.disabled)return!0;break;case"label":case"video":return!0}return/\bneedsclick\b/.test(t.className)},FastClick.prototype.needsFocus=function(t){"use strict";switch(t.nodeName.toLowerCase()){case"textarea":return!0;case"select":return!deviceIsAndroid;case"input":switch(t.type){case"button":case"checkbox":case"file":case"image":case"radio":case"submit":return!1}return!t.disabled&&!t.readOnly;default:return/\bneedsfocus\b/.test(t.className)}},FastClick.prototype.sendClick=function(t,e){"use strict";var i,n;document.activeElement&&document.activeElement!==t&&document.activeElement.blur(),n=e.changedTouches[0],i=document.createEvent("MouseEvents"),i.initMouseEvent(this.determineEventType(t),!0,!0,window,1,n.screenX,n.screenY,n.clientX,n.clientY,!1,!1,!1,!1,0,null),i.forwardedTouchEvent=!0,t.dispatchEvent(i)},FastClick.prototype.determineEventType=function(t){"use strict";return deviceIsAndroid&&"select"===t.tagName.toLowerCase()?"mousedown":"click"},FastClick.prototype.focus=function(t){"use strict";var e;deviceIsIOS&&t.setSelectionRange&&0!==t.type.indexOf("date")&&"time"!==t.type?(e=t.value.length,t.setSelectionRange(e,e)):t.focus()},FastClick.prototype.updateScrollParent=function(t){"use strict";var e,i;if(e=t.fastClickScrollParent,!e||!e.contains(t)){i=t;do{if(i.scrollHeight>i.offsetHeight){e=i,t.fastClickScrollParent=i;break}i=i.parentElement}while(i)}e&&(e.fastClickLastScrollTop=e.scrollTop)},FastClick.prototype.getTargetElementFromEventTarget=function(t){"use strict";return t.nodeType===Node.TEXT_NODE?t.parentNode:t},FastClick.prototype.onTouchStart=function(t){"use strict";var e,i,n;if(t.targetTouches.length>1)return!0;if(e=this.getTargetElementFromEventTarget(t.target),i=t.targetTouches[0],deviceIsIOS){if(n=window.getSelection(),n.rangeCount&&!n.isCollapsed)return!0;if(!deviceIsIOS4){if(i.identifier===this.lastTouchIdentifier)return t.preventDefault(),!1;this.lastTouchIdentifier=i.identifier,this.updateScrollParent(e)}}return this.trackingClick=!0,this.trackingClickStart=t.timeStamp,this.targetElement=e,this.touchStartX=i.pageX,this.touchStartY=i.pageY,t.timeStamp-this.lastClickTime<this.tapDelay&&t.preventDefault(),!0},FastClick.prototype.touchHasMoved=function(t){"use strict";var e=t.changedTouches[0],i=this.touchBoundary;return Math.abs(e.pageX-this.touchStartX)>i||Math.abs(e.pageY-this.touchStartY)>i?!0:!1},FastClick.prototype.onTouchMove=function(t){"use strict";return this.trackingClick?((this.targetElement!==this.getTargetElementFromEventTarget(t.target)||this.touchHasMoved(t))&&(this.trackingClick=!1,this.targetElement=null),!0):!0},FastClick.prototype.findControl=function(t){"use strict";return void 0!==t.control?t.control:t.htmlFor?document.getElementById(t.htmlFor):t.querySelector("button, input:not([type=hidden]), keygen, meter, output, progress, select, textarea")},FastClick.prototype.onTouchEnd=function(t){"use strict";var e,i,n,o,s,r=this.targetElement;if(!this.trackingClick)return!0;if(t.timeStamp-this.lastClickTime<this.tapDelay)return this.cancelNextClick=!0,!0;if(this.cancelNextClick=!1,this.lastClickTime=t.timeStamp,i=this.trackingClickStart,this.trackingClick=!1,this.trackingClickStart=0,deviceIsIOSWithBadTarget&&(s=t.changedTouches[0],r=document.elementFromPoint(s.pageX-window.pageXOffset,s.pageY-window.pageYOffset)||r,r.fastClickScrollParent=this.targetElement.fastClickScrollParent),n=r.tagName.toLowerCase(),"label"===n){if(e=this.findControl(r)){if(this.focus(r),deviceIsAndroid)return!1;r=e}}else if(this.needsFocus(r))return t.timeStamp-i>100||deviceIsIOS&&window.top!==window&&"input"===n?(this.targetElement=null,!1):(this.focus(r),this.sendClick(r,t),deviceIsIOS&&"select"===n||(this.targetElement=null,t.preventDefault()),!1);return deviceIsIOS&&!deviceIsIOS4&&(o=r.fastClickScrollParent,o&&o.fastClickLastScrollTop!==o.scrollTop)?!0:(this.needsClick(r)||(t.preventDefault(),this.sendClick(r,t)),!1)},FastClick.prototype.onTouchCancel=function(){"use strict";this.trackingClick=!1,this.targetElement=null},FastClick.prototype.onMouse=function(t){"use strict";return this.targetElement?t.forwardedTouchEvent?!0:t.cancelable?!this.needsClick(this.targetElement)||this.cancelNextClick?(t.stopImmediatePropagation?t.stopImmediatePropagation():t.propagationStopped=!0,t.stopPropagation(),t.preventDefault(),!1):!0:!0:!0},FastClick.prototype.onClick=function(t){"use strict";var e;return this.trackingClick?(this.targetElement=null,this.trackingClick=!1,!0):"submit"===t.target.type&&0===t.detail?!0:(e=this.onMouse(t),e||(this.targetElement=null),e)},FastClick.prototype.destroy=function(){"use strict";var t=this.layer;deviceIsAndroid&&(t.removeEventListener("mouseover",this.onMouse,!0),t.removeEventListener("mousedown",this.onMouse,!0),t.removeEventListener("mouseup",this.onMouse,!0)),t.removeEventListener("click",this.onClick,!0),t.removeEventListener("touchstart",this.onTouchStart,!1),t.removeEventListener("touchmove",this.onTouchMove,!1),t.removeEventListener("touchend",this.onTouchEnd,!1),t.removeEventListener("touchcancel",this.onTouchCancel,!1)},FastClick.notNeeded=function(t){"use strict";var e,i;if("undefined"==typeof window.ontouchstart)return!0;if(i=+(/Chrome\/([0-9]+)/.exec(navigator.userAgent)||[,0])[1]){if(!deviceIsAndroid)return!0;if(e=document.querySelector("meta[name=viewport]")){if(-1!==e.content.indexOf("user-scalable=no"))return!0;if(i>31&&document.documentElement.scrollWidth<=window.outerWidth)return!0}}return"none"===t.style.msTouchAction?!0:!1},FastClick.attach=function(t,e){"use strict";return new FastClick(t,e)},"undefined"!=typeof define&&define.amd?define(function(){"use strict";return FastClick}):"undefined"!=typeof module&&module.exports?(module.exports=FastClick.attach,module.exports.FastClick=FastClick):window.FastClick=FastClick;

/* ========================================================================
 * SlimScroll.js v1.3.2
 * Src : https://github.com/rochal/jQuery-slimScroll
 * ======================================================================== */
!function(e){jQuery.fn.extend({slimScroll:function(i){var o={width:"auto",height:"250px",size:"7px",color:"#000",position:"right",distance:"1px",start:"top",opacity:.4,alwaysVisible:!1,disableFadeOut:!1,railVisible:!1,railColor:"#333",railOpacity:.2,railDraggable:!0,railClass:"slimScrollRail",barClass:"slimScrollBar",wrapperClass:"slimScrollDiv",allowPageScroll:!1,wheelStep:20,touchScrollStep:200,borderRadius:"7px",railBorderRadius:"7px"},r=e.extend(o,i);return this.each(function(){function o(t){if(h){var t=t||window.event,i=0;t.wheelDelta&&(i=-t.wheelDelta/120),t.detail&&(i=t.detail/3);var o=t.target||t.srcTarget||t.srcElement;e(o).closest("."+r.wrapperClass).is(x.parent())&&s(i,!0),t.preventDefault&&!y&&t.preventDefault(),y||(t.returnValue=!1)}}function s(e,t,i){y=!1;var o=e,s=x.outerHeight()-R.outerHeight();if(t&&(o=parseInt(R.css("top"))+e*parseInt(r.wheelStep)/100*R.outerHeight(),o=Math.min(Math.max(o,0),s),o=e>0?Math.ceil(o):Math.floor(o),R.css({top:o+"px"})),v=parseInt(R.css("top"))/(x.outerHeight()-R.outerHeight()),o=v*(x[0].scrollHeight-x.outerHeight()),i){o=e;var a=o/x[0].scrollHeight*x.outerHeight();a=Math.min(Math.max(a,0),s),R.css({top:a+"px"})}x.scrollTop(o),x.trigger("slimscrolling",~~o),n(),c()}function a(){window.addEventListener?(this.addEventListener("DOMMouseScroll",o,!1),this.addEventListener("mousewheel",o,!1)):document.attachEvent("onmousewheel",o)}function l(){f=Math.max(x.outerHeight()/x[0].scrollHeight*x.outerHeight(),m),R.css({height:f+"px"});var e=f==x.outerHeight()?"none":"block";R.css({display:e})}function n(){if(l(),clearTimeout(p),v==~~v){if(y=r.allowPageScroll,b!=v){var e=0==~~v?"top":"bottom";x.trigger("slimscroll",e)}}else y=!1;return b=v,f>=x.outerHeight()?void(y=!0):(R.stop(!0,!0).fadeIn("fast"),void(r.railVisible&&E.stop(!0,!0).fadeIn("fast")))}function c(){r.alwaysVisible||(p=setTimeout(function(){r.disableFadeOut&&h||u||d||(R.fadeOut("slow"),E.fadeOut("slow"))},1e3))}var h,u,d,p,g,f,v,b,w="<div></div>",m=30,y=!1,x=e(this);if(x.parent().hasClass(r.wrapperClass)){var C=x.scrollTop();if(R=x.parent().find("."+r.barClass),E=x.parent().find("."+r.railClass),l(),e.isPlainObject(i)){if("height"in i&&"auto"==i.height){x.parent().css("height","auto"),x.css("height","auto");var H=x.parent().parent().height();x.parent().css("height",H),x.css("height",H)}if("scrollTo"in i)C=parseInt(r.scrollTo);else if("scrollBy"in i)C+=parseInt(r.scrollBy);else if("destroy"in i)return R.remove(),E.remove(),void x.unwrap();s(C,!1,!0)}}else{r.height="auto"==i.height?x.parent().height():i.height;var S=e(w).addClass(r.wrapperClass).css({position:"relative",overflow:"hidden",width:r.width,height:r.height});x.css({overflow:"hidden",width:r.width,height:r.height});var E=e(w).addClass(r.railClass).css({width:r.size,height:"100%",position:"absolute",top:0,display:r.alwaysVisible&&r.railVisible?"block":"none","border-radius":r.railBorderRadius,background:r.railColor,opacity:r.railOpacity,zIndex:90}),R=e(w).addClass(r.barClass).css({background:r.color,width:r.size,position:"absolute",top:0,opacity:r.opacity,display:r.alwaysVisible?"block":"none","border-radius":r.borderRadius,BorderRadius:r.borderRadius,MozBorderRadius:r.borderRadius,WebkitBorderRadius:r.borderRadius,zIndex:99}),D="right"==r.position?{right:r.distance}:{left:r.distance};E.css(D),R.css(D),x.wrap(S),x.parent().append(R),x.parent().append(E),r.railDraggable&&R.bind("mousedown",function(i){var o=e(document);return d=!0,t=parseFloat(R.css("top")),pageY=i.pageY,o.bind("mousemove.slimscroll",function(e){currTop=t+e.pageY-pageY,R.css("top",currTop),s(0,R.position().top,!1)}),o.bind("mouseup.slimscroll",function(){d=!1,c(),o.unbind(".slimscroll")}),!1}).bind("selectstart.slimscroll",function(e){return e.stopPropagation(),e.preventDefault(),!1}),E.hover(function(){n()},function(){c()}),R.hover(function(){u=!0},function(){u=!1}),x.hover(function(){h=!0,n(),c()},function(){h=!1,c()}),x.bind("touchstart",function(e){e.originalEvent.touches.length&&(g=e.originalEvent.touches[0].pageY)}),x.bind("touchmove",function(e){if(y||e.originalEvent.preventDefault(),e.originalEvent.touches.length){var t=(g-e.originalEvent.touches[0].pageY)/r.touchScrollStep;s(t,!0),g=e.originalEvent.touches[0].pageY}}),l(),"bottom"===r.start?(R.css({top:x.outerHeight()-R.outerHeight()}),s(0,!0)):"top"!==r.start&&(s(e(r.start).position().top,null,!0),r.alwaysVisible||R.hide()),a()}}),this}}),jQuery.fn.extend({slimscroll:jQuery.fn.slimScroll})}(jQuery);

/* ========================================================================
 * Unveil.js v??
 * Src : https://github.com/luis-almeida/unveil
 * ======================================================================== */
;(function($){$.fn.unveil=function(threshold,callback){var $w=$(window),th=threshold||0,retina=window.devicePixelRatio>1,attrib=retina?"data-src-retina":"data-src",images=this,loaded;this.one("unveil",function(){var source=this.getAttribute(attrib);source=source||this.getAttribute("data-src");if(source){this.setAttribute("src",source);if(typeof callback==="function")callback.call(this);}});function unveil(){var inview=images.filter(function(){var $e=$(this),wt=$w.scrollTop(),wb=wt+$w.height(),et=$e.offset().top,eb=et+$e.height();return eb>=wt-th&&et<=wb+th;});loaded=inview.trigger("unveil");images=images.not(loaded);}$w.scroll(unveil);$w.resize(unveil);unveil();return this;};})(window.jQuery||window.Zepto);

/* ========================================================================
 * Waypoints.js v2.0.4
 * Src : https://github.com/imakewebthings/jquery-waypoints
 * ======================================================================== */
(function(){var t=[].indexOf||function(t){for(var e=0,n=this.length;e<n;e++){if(e in this&&this[e]===t)return e}return-1},e=[].slice;(function(t,e){if(typeof define==="function"&&define.amd){return define("waypoints",["jquery"],function(n){return e(n,t)})}else{return e(t.jQuery,t)}})(this,function(n,r){var i,o,l,s,f,u,c,a,h,d,p,y,v,w,g,m;i=n(r);a=t.call(r,"ontouchstart")>=0;s={horizontal:{},vertical:{}};f=1;c={};u="waypoints-context-id";p="resize.waypoints";y="scroll.waypoints";v=1;w="waypoints-waypoint-ids";g="waypoint";m="waypoints";o=function(){function t(t){var e=this;this.$element=t;this.element=t[0];this.didResize=false;this.didScroll=false;this.id="context"+f++;this.oldScroll={x:t.scrollLeft(),y:t.scrollTop()};this.waypoints={horizontal:{},vertical:{}};this.element[u]=this.id;c[this.id]=this;t.bind(y,function(){var t;if(!(e.didScroll||a)){e.didScroll=true;t=function(){e.doScroll();return e.didScroll=false};return r.setTimeout(t,n[m].settings.scrollThrottle)}});t.bind(p,function(){var t;if(!e.didResize){e.didResize=true;t=function(){n[m]("refresh");return e.didResize=false};return r.setTimeout(t,n[m].settings.resizeThrottle)}})}t.prototype.doScroll=function(){var t,e=this;t={horizontal:{newScroll:this.$element.scrollLeft(),oldScroll:this.oldScroll.x,forward:"right",backward:"left"},vertical:{newScroll:this.$element.scrollTop(),oldScroll:this.oldScroll.y,forward:"down",backward:"up"}};if(a&&(!t.vertical.oldScroll||!t.vertical.newScroll)){n[m]("refresh")}n.each(t,function(t,r){var i,o,l;l=[];o=r.newScroll>r.oldScroll;i=o?r.forward:r.backward;n.each(e.waypoints[t],function(t,e){var n,i;if(r.oldScroll<(n=e.offset)&&n<=r.newScroll){return l.push(e)}else if(r.newScroll<(i=e.offset)&&i<=r.oldScroll){return l.push(e)}});l.sort(function(t,e){return t.offset-e.offset});if(!o){l.reverse()}return n.each(l,function(t,e){if(e.options.continuous||t===l.length-1){return e.trigger([i])}})});return this.oldScroll={x:t.horizontal.newScroll,y:t.vertical.newScroll}};t.prototype.refresh=function(){var t,e,r,i=this;r=n.isWindow(this.element);e=this.$element.offset();this.doScroll();t={horizontal:{contextOffset:r?0:e.left,contextScroll:r?0:this.oldScroll.x,contextDimension:this.$element.width(),oldScroll:this.oldScroll.x,forward:"right",backward:"left",offsetProp:"left"},vertical:{contextOffset:r?0:e.top,contextScroll:r?0:this.oldScroll.y,contextDimension:r?n[m]("viewportHeight"):this.$element.height(),oldScroll:this.oldScroll.y,forward:"down",backward:"up",offsetProp:"top"}};return n.each(t,function(t,e){return n.each(i.waypoints[t],function(t,r){var i,o,l,s,f;i=r.options.offset;l=r.offset;o=n.isWindow(r.element)?0:r.$element.offset()[e.offsetProp];if(n.isFunction(i)){i=i.apply(r.element)}else if(typeof i==="string"){i=parseFloat(i);if(r.options.offset.indexOf("%")>-1){i=Math.ceil(e.contextDimension*i/100)}}r.offset=o-e.contextOffset+e.contextScroll-i;if(r.options.onlyOnScroll&&l!=null||!r.enabled){return}if(l!==null&&l<(s=e.oldScroll)&&s<=r.offset){return r.trigger([e.backward])}else if(l!==null&&l>(f=e.oldScroll)&&f>=r.offset){return r.trigger([e.forward])}else if(l===null&&e.oldScroll>=r.offset){return r.trigger([e.forward])}})})};t.prototype.checkEmpty=function(){if(n.isEmptyObject(this.waypoints.horizontal)&&n.isEmptyObject(this.waypoints.vertical)){this.$element.unbind([p,y].join(" "));return delete c[this.id]}};return t}();l=function(){function t(t,e,r){var i,o;r=n.extend({},n.fn[g].defaults,r);if(r.offset==="bottom-in-view"){r.offset=function(){var t;t=n[m]("viewportHeight");if(!n.isWindow(e.element)){t=e.$element.height()}return t-n(this).outerHeight()}}this.$element=t;this.element=t[0];this.axis=r.horizontal?"horizontal":"vertical";this.callback=r.handler;this.context=e;this.enabled=r.enabled;this.id="waypoints"+v++;this.offset=null;this.options=r;e.waypoints[this.axis][this.id]=this;s[this.axis][this.id]=this;i=(o=this.element[w])!=null?o:[];i.push(this.id);this.element[w]=i}t.prototype.trigger=function(t){if(!this.enabled){return}if(this.callback!=null){this.callback.apply(this.element,t)}if(this.options.triggerOnce){return this.destroy()}};t.prototype.disable=function(){return this.enabled=false};t.prototype.enable=function(){this.context.refresh();return this.enabled=true};t.prototype.destroy=function(){delete s[this.axis][this.id];delete this.context.waypoints[this.axis][this.id];return this.context.checkEmpty()};t.getWaypointsByElement=function(t){var e,r;r=t[w];if(!r){return[]}e=n.extend({},s.horizontal,s.vertical);return n.map(r,function(t){return e[t]})};return t}();d={init:function(t,e){var r;if(e==null){e={}}if((r=e.handler)==null){e.handler=t}this.each(function(){var t,r,i,s;t=n(this);i=(s=e.context)!=null?s:n.fn[g].defaults.context;if(!n.isWindow(i)){i=t.closest(i)}i=n(i);r=c[i[0][u]];if(!r){r=new o(i)}return new l(t,r,e)});n[m]("refresh");return this},disable:function(){return d._invoke.call(this,"disable")},enable:function(){return d._invoke.call(this,"enable")},destroy:function(){return d._invoke.call(this,"destroy")},prev:function(t,e){return d._traverse.call(this,t,e,function(t,e,n){if(e>0){return t.push(n[e-1])}})},next:function(t,e){return d._traverse.call(this,t,e,function(t,e,n){if(e<n.length-1){return t.push(n[e+1])}})},_traverse:function(t,e,i){var o,l;if(t==null){t="vertical"}if(e==null){e=r}l=h.aggregate(e);o=[];this.each(function(){var e;e=n.inArray(this,l[t]);return i(o,e,l[t])});return this.pushStack(o)},_invoke:function(t){this.each(function(){var e;e=l.getWaypointsByElement(this);return n.each(e,function(e,n){n[t]();return true})});return this}};n.fn[g]=function(){var t,r;r=arguments[0],t=2<=arguments.length?e.call(arguments,1):[];if(d[r]){return d[r].apply(this,t)}else if(n.isFunction(r)){return d.init.apply(this,arguments)}else if(n.isPlainObject(r)){return d.init.apply(this,[null,r])}else if(!r){return n.error("jQuery Waypoints needs a callback function or handler option.")}else{return n.error("The "+r+" method does not exist in jQuery Waypoints.")}};n.fn[g].defaults={context:r,continuous:true,enabled:true,horizontal:false,offset:0,triggerOnce:false};h={refresh:function(){return n.each(c,function(t,e){return e.refresh()})},viewportHeight:function(){var t;return(t=r.innerHeight)!=null?t:i.height()},aggregate:function(t){var e,r,i;e=s;if(t){e=(i=c[n(t)[0][u]])!=null?i.waypoints:void 0}if(!e){return[]}r={horizontal:[],vertical:[]};n.each(r,function(t,i){n.each(e[t],function(t,e){return i.push(e)});i.sort(function(t,e){return t.offset-e.offset});r[t]=n.map(i,function(t){return t.element});return r[t]=n.unique(r[t])});return r},above:function(t){if(t==null){t=r}return h._filter(t,"vertical",function(t,e){return e.offset<=t.oldScroll.y})},below:function(t){if(t==null){t=r}return h._filter(t,"vertical",function(t,e){return e.offset>t.oldScroll.y})},left:function(t){if(t==null){t=r}return h._filter(t,"horizontal",function(t,e){return e.offset<=t.oldScroll.x})},right:function(t){if(t==null){t=r}return h._filter(t,"horizontal",function(t,e){return e.offset>t.oldScroll.x})},enable:function(){return h._invoke("enable")},disable:function(){return h._invoke("disable")},destroy:function(){return h._invoke("destroy")},extendFn:function(t,e){return d[t]=e},_invoke:function(t){var e;e=n.extend({},s.vertical,s.horizontal);return n.each(e,function(e,n){n[t]();return true})},_filter:function(t,e,r){var i,o;i=c[n(t)[0][u]];if(!i){return[]}o=[];n.each(i.waypoints[e],function(t,e){if(r(i,e)){return o.push(e)}});o.sort(function(t,e){return t.offset-e.offset});return n.map(o,function(t){return t.element})}};n[m]=function(){var t,n;n=arguments[0],t=2<=arguments.length?e.call(arguments,1):[];if(h[n]){return h[n].apply(null,t)}else{return h.aggregate.call(null,n)}};n[m].settings={resizeThrottle:100,scrollThrottle:30};return i.load(function(){return n[m]("refresh")})})}).call(this);

/* ========================================================================
 * NProgress.js v0.1.2
 * Src : http://ricostacruz.com/nprogress
 * ======================================================================== */
!function(n){"function"==typeof module?module.exports=n():"function"==typeof define&&define.amd?define(n):this.NProgress=n()}(function(){function n(n,t,e){return t>n?t:n>e?e:n}function t(n){return 100*(-1+n)}function e(n,e,r){var i;return i="translate3d"===c.positionUsing?{transform:"translate3d("+t(n)+"%,0,0)"}:"translate"===c.positionUsing?{transform:"translate("+t(n)+"%,0)"}:{"margin-left":t(n)+"%"},i.transition="all "+e+"ms "+r,i}function r(n,t){var e="string"==typeof n?n:o(n);return e.indexOf(" "+t+" ")>=0}function i(n,t){var e=o(n),i=e+t;r(e,t)||(n.className=i.substring(1))}function s(n,t){var e,i=o(n);r(n,t)&&(e=i.replace(" "+t+" "," "),n.className=e.substring(1,e.length-1))}function o(n){return(" "+(n.className||"")+" ").replace(/\s+/gi," ")}function a(n){n&&n.parentNode&&n.parentNode.removeChild(n)}var u={};u.version="0.1.3";var c=u.settings={minimum:.08,easing:"ease",positionUsing:"",speed:200,trickle:!0,trickleRate:.02,trickleSpeed:800,showSpinner:!0,barSelector:'[role="bar"]',spinnerSelector:'[role="spinner"]',template:'<div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div>'};u.configure=function(n){var t,e;for(t in n)e=n[t],void 0!==e&&n.hasOwnProperty(t)&&(c[t]=e);return this},u.status=null,u.set=function(t){var r=u.isStarted();t=n(t,c.minimum,1),u.status=1===t?null:t;var i=u.render(!r),s=i.querySelector(c.barSelector),o=c.speed,a=c.easing;return i.offsetWidth,l(function(n){""===c.positionUsing&&(c.positionUsing=u.getPositioningCSS()),f(s,e(t,o,a)),1===t?(f(i,{transition:"none",opacity:1}),i.offsetWidth,setTimeout(function(){f(i,{transition:"all "+o+"ms linear",opacity:0}),setTimeout(function(){u.remove(),n()},o)},o)):setTimeout(n,o)}),this},u.isStarted=function(){return"number"==typeof u.status},u.start=function(){u.status||u.set(0);var n=function(){setTimeout(function(){u.status&&(u.trickle(),n())},c.trickleSpeed)};return c.trickle&&n(),this},u.done=function(n){return n||u.status?u.inc(.3+.5*Math.random()).set(1):this},u.inc=function(t){var e=u.status;return e?("number"!=typeof t&&(t=(1-e)*n(Math.random()*e,.1,.95)),e=n(e+t,0,.994),u.set(e)):u.start()},u.trickle=function(){return u.inc(Math.random()*c.trickleRate)},function(){var n=0,t=0;u.promise=function(e){return e&&"resolved"!=e.state()?(0==t&&u.start(),n++,t++,e.always(function(){t--,0==t?(n=0,u.done()):u.set((n-t)/n)}),this):this}}(),u.render=function(n){if(u.isRendered())return document.getElementById("nprogress");i(document.documentElement,"nprogress-busy");var e=document.createElement("div");e.id="nprogress",e.innerHTML=c.template;var r,s=e.querySelector(c.barSelector),o=n?"-100":t(u.status||0);return f(s,{transition:"all 0 linear",transform:"translate3d("+o+"%,0,0)"}),c.showSpinner||(r=e.querySelector(c.spinnerSelector),r&&a(r)),document.body.appendChild(e),e},u.remove=function(){s(document.documentElement,"nprogress-busy");var n=document.getElementById("nprogress");n&&a(n)},u.isRendered=function(){return!!document.getElementById("nprogress")},u.getPositioningCSS=function(){var n=document.body.style,t="WebkitTransform"in n?"Webkit":"MozTransform"in n?"Moz":"msTransform"in n?"ms":"OTransform"in n?"O":"";return t+"Perspective"in n?"translate3d":t+"Transform"in n?"translate":"margin"};var l=function(){function n(){var e=t.shift();e&&e(n)}var t=[];return function(e){t.push(e),1==t.length&&n()}}(),f=function(){function n(n){return n.replace(/^-ms-/,"ms-").replace(/-([\da-z])/gi,function(n,t){return t.toUpperCase()})}function t(n){var t=document.body.style;if(n in t)return n;for(var e,r=i.length,s=n.charAt(0).toUpperCase()+n.slice(1);r--;)if(e=i[r]+s,e in t)return e;return n}function e(e){return e=n(e),s[e]||(s[e]=t(e))}function r(n,t,r){t=e(t),n.style[t]=r}var i=["Webkit","O","Moz","ms"],s={};return function(n,t){var e,i,s=arguments;if(2==s.length)for(e in t)i=t[e],void 0!==i&&t.hasOwnProperty(e)&&r(n,e,i);else r(n,s[1],s[2])}}();return u});

/* ========================================================================
 * Transit.js v0.9.9
 * Src : https://raw.githubusercontent.com/rstacruz/jquery.transit/
 * ======================================================================== */
(function(k){k.transit={version:"0.9.9",propertyMap:{marginLeft:"margin",marginRight:"margin",marginBottom:"margin",marginTop:"margin",paddingLeft:"padding",paddingRight:"padding",paddingBottom:"padding",paddingTop:"padding"},enabled:true,useTransitionEnd:false};var d=document.createElement("div");var q={};function b(v){if(v in d.style){return v}var u=["Moz","Webkit","O","ms"];var r=v.charAt(0).toUpperCase()+v.substr(1);if(v in d.style){return v}for(var t=0;t<u.length;++t){var s=u[t]+r;if(s in d.style){return s}}}function e(){d.style[q.transform]="";d.style[q.transform]="rotateY(90deg)";return d.style[q.transform]!==""}var a=navigator.userAgent.toLowerCase().indexOf("chrome")>-1;q.transition=b("transition");q.transitionDelay=b("transitionDelay");q.transform=b("transform");q.transformOrigin=b("transformOrigin");q.transform3d=e();var i={transition:"transitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd",WebkitTransition:"webkitTransitionEnd",msTransition:"MSTransitionEnd"};var f=q.transitionEnd=i[q.transition]||null;for(var p in q){if(q.hasOwnProperty(p)&&typeof k.support[p]==="undefined"){k.support[p]=q[p]}}d=null;k.cssEase={_default:"ease","in":"ease-in",out:"ease-out","in-out":"ease-in-out",snap:"cubic-bezier(0,1,.5,1)",easeOutCubic:"cubic-bezier(.215,.61,.355,1)",easeInOutCubic:"cubic-bezier(.645,.045,.355,1)",easeInCirc:"cubic-bezier(.6,.04,.98,.335)",easeOutCirc:"cubic-bezier(.075,.82,.165,1)",easeInOutCirc:"cubic-bezier(.785,.135,.15,.86)",easeInExpo:"cubic-bezier(.95,.05,.795,.035)",easeOutExpo:"cubic-bezier(.19,1,.22,1)",easeInOutExpo:"cubic-bezier(1,0,0,1)",easeInQuad:"cubic-bezier(.55,.085,.68,.53)",easeOutQuad:"cubic-bezier(.25,.46,.45,.94)",easeInOutQuad:"cubic-bezier(.455,.03,.515,.955)",easeInQuart:"cubic-bezier(.895,.03,.685,.22)",easeOutQuart:"cubic-bezier(.165,.84,.44,1)",easeInOutQuart:"cubic-bezier(.77,0,.175,1)",easeInQuint:"cubic-bezier(.755,.05,.855,.06)",easeOutQuint:"cubic-bezier(.23,1,.32,1)",easeInOutQuint:"cubic-bezier(.86,0,.07,1)",easeInSine:"cubic-bezier(.47,0,.745,.715)",easeOutSine:"cubic-bezier(.39,.575,.565,1)",easeInOutSine:"cubic-bezier(.445,.05,.55,.95)",easeInBack:"cubic-bezier(.6,-.28,.735,.045)",easeOutBack:"cubic-bezier(.175, .885,.32,1.275)",easeInOutBack:"cubic-bezier(.68,-.55,.265,1.55)"};k.cssHooks["transit:transform"]={get:function(r){return k(r).data("transform")||new j()},set:function(s,r){var t=r;if(!(t instanceof j)){t=new j(t)}if(q.transform==="WebkitTransform"&&!a){s.style[q.transform]=t.toString(true)}else{s.style[q.transform]=t.toString()}k(s).data("transform",t)}};k.cssHooks.transform={set:k.cssHooks["transit:transform"].set};if(k.fn.jquery<"1.8"){k.cssHooks.transformOrigin={get:function(r){return r.style[q.transformOrigin]},set:function(r,s){r.style[q.transformOrigin]=s}};k.cssHooks.transition={get:function(r){return r.style[q.transition]},set:function(r,s){r.style[q.transition]=s}}}n("scale");n("translate");n("rotate");n("rotateX");n("rotateY");n("rotate3d");n("perspective");n("skewX");n("skewY");n("x",true);n("y",true);function j(r){if(typeof r==="string"){this.parse(r)}return this}j.prototype={setFromString:function(t,s){var r=(typeof s==="string")?s.split(","):(s.constructor===Array)?s:[s];r.unshift(t);j.prototype.set.apply(this,r)},set:function(s){var r=Array.prototype.slice.apply(arguments,[1]);if(this.setter[s]){this.setter[s].apply(this,r)}else{this[s]=r.join(",")}},get:function(r){if(this.getter[r]){return this.getter[r].apply(this)}else{return this[r]||0}},setter:{rotate:function(r){this.rotate=o(r,"deg")},rotateX:function(r){this.rotateX=o(r,"deg")},rotateY:function(r){this.rotateY=o(r,"deg")},scale:function(r,s){if(s===undefined){s=r}this.scale=r+","+s},skewX:function(r){this.skewX=o(r,"deg")},skewY:function(r){this.skewY=o(r,"deg")},perspective:function(r){this.perspective=o(r,"px")},x:function(r){this.set("translate",r,null)},y:function(r){this.set("translate",null,r)},translate:function(r,s){if(this._translateX===undefined){this._translateX=0}if(this._translateY===undefined){this._translateY=0}if(r!==null&&r!==undefined){this._translateX=o(r,"px")}if(s!==null&&s!==undefined){this._translateY=o(s,"px")}this.translate=this._translateX+","+this._translateY}},getter:{x:function(){return this._translateX||0},y:function(){return this._translateY||0},scale:function(){var r=(this.scale||"1,1").split(",");if(r[0]){r[0]=parseFloat(r[0])}if(r[1]){r[1]=parseFloat(r[1])}return(r[0]===r[1])?r[0]:r},rotate3d:function(){var t=(this.rotate3d||"0,0,0,0deg").split(",");for(var r=0;r<=3;++r){if(t[r]){t[r]=parseFloat(t[r])}}if(t[3]){t[3]=o(t[3],"deg")}return t}},parse:function(s){var r=this;s.replace(/([a-zA-Z0-9]+)\((.*?)\)/g,function(t,v,u){r.setFromString(v,u)})},toString:function(t){var s=[];for(var r in this){if(this.hasOwnProperty(r)){if((!q.transform3d)&&((r==="rotateX")||(r==="rotateY")||(r==="perspective")||(r==="transformOrigin"))){continue}if(r[0]!=="_"){if(t&&(r==="scale")){s.push(r+"3d("+this[r]+",1)")}else{if(t&&(r==="translate")){s.push(r+"3d("+this[r]+",0)")}else{s.push(r+"("+this[r]+")")}}}}}return s.join(" ")}};function m(s,r,t){if(r===true){s.queue(t)}else{if(r){s.queue(r,t)}else{t()}}}function h(s){var r=[];k.each(s,function(t){t=k.camelCase(t);t=k.transit.propertyMap[t]||k.cssProps[t]||t;t=c(t);if(k.inArray(t,r)===-1){r.push(t)}});return r}function g(s,v,x,r){var t=h(s);if(k.cssEase[x]){x=k.cssEase[x]}var w=""+l(v)+" "+x;if(parseInt(r,10)>0){w+=" "+l(r)}var u=[];k.each(t,function(z,y){u.push(y+" "+w)});return u.join(", ")}k.fn.transition=k.fn.transit=function(z,s,y,C){var D=this;var u=0;var w=true;if(typeof s==="function"){C=s;s=undefined}if(typeof y==="function"){C=y;y=undefined}if(typeof z.easing!=="undefined"){y=z.easing;delete z.easing}if(typeof z.duration!=="undefined"){s=z.duration;delete z.duration}if(typeof z.complete!=="undefined"){C=z.complete;delete z.complete}if(typeof z.queue!=="undefined"){w=z.queue;delete z.queue}if(typeof z.delay!=="undefined"){u=z.delay;delete z.delay}if(typeof s==="undefined"){s=k.fx.speeds._default}if(typeof y==="undefined"){y=k.cssEase._default}s=l(s);var E=g(z,s,y,u);var B=k.transit.enabled&&q.transition;var t=B?(parseInt(s,10)+parseInt(u,10)):0;if(t===0){var A=function(F){D.css(z);if(C){C.apply(D)}if(F){F()}};m(D,w,A);return D}var x={};var r=function(H){var G=false;var F=function(){if(G){D.unbind(f,F)}if(t>0){D.each(function(){this.style[q.transition]=(x[this]||null)})}if(typeof C==="function"){C.apply(D)}if(typeof H==="function"){H()}};if((t>0)&&(f)&&(k.transit.useTransitionEnd)){G=true;D.bind(f,F)}else{window.setTimeout(F,t)}D.each(function(){if(t>0){this.style[q.transition]=E}k(this).css(z)})};var v=function(F){this.offsetWidth;r(F)};m(D,w,v);return this};function n(s,r){if(!r){k.cssNumber[s]=true}k.transit.propertyMap[s]=q.transform;k.cssHooks[s]={get:function(v){var u=k(v).css("transit:transform");return u.get(s)},set:function(v,w){var u=k(v).css("transit:transform");u.setFromString(s,w);k(v).css({"transit:transform":u})}}}function c(r){return r.replace(/([A-Z])/g,function(s){return"-"+s.toLowerCase()})}function o(s,r){if((typeof s==="string")&&(!s.match(/^[\-0-9\.]+$/))){return s}else{return""+s+r}}function l(s){var r=s;if(k.fx.speeds[r]){r=k.fx.speeds[r]}return o(r,"ms")}k.transit.getTransitionValue=g})(jQuery);

/* ========================================================================
 * Response.js v0.8.0
 * Src : http://responsejs.com/
 * ======================================================================== */
!function(a,b,c){var d=a.jQuery||a.Zepto||a.ender||a.elo;"undefined"!=typeof module&&module.exports?module.exports=c(d):a[b]=c(d)}(this,"Response",function(a){function b(a){throw new TypeError(a?S+"."+a:S)}function c(a){return a===+a}function d(a,b,c){for(var d=[],e=a.length,f=0;e>f;)d[f]=b.call(c,a[f],f++,a);return d}function e(a){return a?h("string"==typeof a?a.split(" "):a):[]}function f(a,b,c){if(null==a)return a;for(var d=a.length,e=0;d>e;)b.call(c||a[e],a[e],e++,a);return a}function g(a,b,c){null==b&&(b=""),null==c&&(c="");for(var d=[],e=a.length,f=0;e>f;f++)null==a[f]||d.push(b+a[f]+c);return d}function h(a,b,c){var d,e,f,g=[],h=0,i=0,j="function"==typeof b,k=!0===c;for(e=a&&a.length,c=k?null:c;e>i;i++)f=a[i],d=j?!b.call(c,f,i,a):b?typeof f!==b:!f,d===k&&(g[h++]=f);return g}function i(a,b){if(null==a||null==b)return a;if("object"==typeof b&&c(b.length))bb.apply(a,h(b,"undefined",!0));else for(var d in b)fb.call(b,d)&&void 0!==b[d]&&(a[d]=b[d]);return a}function j(a,b,d){return null==a?a:("object"==typeof a&&!a.nodeType&&c(a.length)?f(a,b,d):b.call(d||a,a),a)}function k(a){return function(b,c){var d=a();return d>=(b||0)&&(!c||c>=d)}}function l(a){var b=V.devicePixelRatio;return null==a?b||(l(2)?2:l(1.5)?1.5:l(1)?1:0):isFinite(a)?b&&b>0?b>=a:(a="only all and (min--moz-device-pixel-ratio:"+a+")",Cb(a).matches?!0:!!Cb(a.replace("-moz-","")).matches):!1}function m(a){return a.replace(xb,"$1").replace(wb,function(a,b){return b.toUpperCase()})}function n(a){return"data-"+(a?a.replace(xb,"$1").replace(vb,"$1-$2").toLowerCase():a)}function o(a){var b;return"string"==typeof a&&a?"false"===a?!1:"true"===a?!0:"null"===a?null:"undefined"===a||(b=+a)||0===b||"NaN"===a?b:a:a}function p(a){return a?1===a.nodeType?a:a[0]&&1===a[0].nodeType?a[0]:!1:!1}function q(a,b){var c,d=arguments.length,e=p(this),g={},h=!1;if(d){if(gb(a)&&(h=!0,a=a[0]),"string"==typeof a){if(a=n(a),1===d)return g=e.getAttribute(a),h?o(g):g;if(this===e||2>(c=this.length||1))e.setAttribute(a,b);else for(;c--;)c in this&&q.apply(this[c],arguments)}else if(a instanceof Object)for(c in a)a.hasOwnProperty(c)&&q.call(this,c,a[c]);return this}return e.dataset&&"undefined"!=typeof DOMStringMap?e.dataset:(f(e.attributes,function(a){a&&(c=String(a.name).match(xb))&&(g[m(c[1])]=a.value)}),g)}function r(a){return this&&"string"==typeof a&&(a=e(a),j(this,function(b){f(a,function(a){a&&b.removeAttribute(n(a))})})),this}function s(a){return q.apply(a,cb.call(arguments,1))}function t(a,b){return r.call(a,b)}function u(a){for(var b,c=[],d=0,e=a.length;e>d;)(b=a[d++])&&c.push("["+n(b.replace(ub,"").replace(".","\\."))+"]");return c.join()}function v(b){return a(u(e(b)))}function w(){return window.pageXOffset||X.scrollLeft}function x(){return window.pageYOffset||X.scrollTop}function y(a,b){var c=a.getBoundingClientRect?a.getBoundingClientRect():{};return b="number"==typeof b?b||0:0,{top:(c.top||0)-b,left:(c.left||0)-b,bottom:(c.bottom||0)+b,right:(c.right||0)+b}}function z(a,b){var c=y(p(a),b);return!!c&&c.right>=0&&c.left<=Db()}function A(a,b){var c=y(p(a),b);return!!c&&c.bottom>=0&&c.top<=Eb()}function B(a,b){var c=y(p(a),b);return!!c&&c.bottom>=0&&c.top<=Eb()&&c.right>=0&&c.left<=Db()}function C(a){var b={img:1,input:1,source:3,embed:3,track:3,iframe:5,audio:5,video:5,script:5},c=b[a.nodeName.toLowerCase()]||-1;return 4>c?c:null!=a.getAttribute("src")?5:-5}function D(a,c,d){var e;return a&&null!=c||b("store"),d="string"==typeof d&&d,j(a,function(a){e=d?a.getAttribute(d):0<C(a)?a.getAttribute("src"):a.innerHTML,null==e?t(a,c):s(a,c,e)}),N}function E(a,b){var c=[];return a&&b&&f(e(b),function(b){c.push(s(a,b))},a),c}function F(a,b){return"string"==typeof a&&"function"==typeof b&&(jb[a]=b,kb[a]=1),N}function G(a){return Z.on("resize",a),N}function H(a,b){var c,d,e=Ab.crossover;return"function"==typeof a&&(c=b,b=a,a=c),d=a?""+a+e:e,Z.on(d,b),N}function I(a){return j(a,function(a){Y(a),G(a)}),N}function J(a){return j(a,function(a){"object"==typeof a||b("create @args");var c,d=yb(O).configure(a),e=d.verge,g=d.breakpoints,h=zb("scroll"),i=zb("resize");g.length&&(c=g[0]||g[1]||!1,Y(function(){function a(){d.reset(),f(d.$e,function(a,b){d[b].decideValue().updateDOM()}).trigger(g)}function b(){f(d.$e,function(a,b){B(d[b].$e,e)&&d[b].updateDOM()})}var g=Ab.allLoaded,j=!!d.lazy;f(d.target().$e,function(a,b){d[b]=yb(d).prepareData(a),(!j||B(d[b].$e,e))&&d[b].updateDOM()}),d.dynamic&&(d.custom||pb>c)&&G(a,i),j&&(Z.on(h,b),d.$e.one(g,function(){Z.off(h,b)}))}))}),N}function K(a){return R[S]===N&&(R[S]=T),"function"==typeof a&&a.call(R,N),N}function L(a,b){return"function"==typeof a&&a.fn&&((b||void 0===a.fn.dataset)&&(a.fn.dataset=q),(b||void 0===a.fn.deletes)&&(a.fn.deletes=r)),N}if("function"!=typeof a)try{return void console.warn("response.js aborted due to missing dependency")}catch(M){}var N,O,P,Q,R=this,S="Response",T=R[S],U="init"+S,V=window,W=document,X=W.documentElement,Y=a.domReady||a,Z=a(V),$=V.screen,_=Array.prototype,ab=Object.prototype,bb=_.push,cb=_.slice,db=_.concat,eb=ab.toString,fb=ab.hasOwnProperty,gb=Array.isArray||function(a){return"[object Array]"===eb.call(a)},hb={width:[0,320,481,641,961,1025,1281],height:[0,481],ratio:[1,1.5,2]},ib={},jb={},kb={},lb={all:[]},mb=1,nb=$.width,ob=$.height,pb=nb>ob?nb:ob,qb=nb+ob-pb,rb=function(){return nb},sb=function(){return ob},tb=/[^a-z0-9_\-\.]/gi,ub=/^[\W\s]+|[\W\s]+$|/g,vb=/([a-z])([A-Z])/g,wb=/-(.)/g,xb=/^data-(.+)$/,yb=Object.create||function(a){function b(){}return b.prototype=a,new b},zb=function(a,b){return b=b||S,a.replace(ub,"")+"."+b.replace(ub,"")},Ab={allLoaded:zb("allLoaded"),crossover:zb("crossover")},Bb=V.matchMedia||V.msMatchMedia,Cb=Bb||function(){return{}},Db=function(){var a=X.clientWidth,b=V.innerWidth;return b>a?b:a},Eb=function(){var a=X.clientHeight,b=V.innerHeight;return b>a?b:a};return P=k(Db),Q=k(Eb),ib.band=k(rb),ib.wave=k(sb),O=function(){function c(a){return"string"==typeof a?a.toLowerCase().replace(tb,""):""}function j(a,b){return a-b}var k=Ab.crossover,l=Math.min;return{$e:0,mode:0,breakpoints:null,prefix:null,prop:"width",keys:[],dynamic:null,custom:0,values:[],fn:0,verge:null,newValue:0,currValue:1,aka:null,lazy:null,i:0,uid:null,reset:function(){for(var a=this.breakpoints,b=a.length,c=0;!c&&b--;)this.fn(a[b])&&(c=b);return c!==this.i&&(Z.trigger(k).trigger(this.prop+k),this.i=c||0),this},configure:function(a){i(this,a);var k,m,n,o,p,q=!0,r=this.prop;if(this.uid=mb++,null==this.verge&&(this.verge=l(pb,500)),this.fn=jb[r]||b("create @fn"),null==this.dynamic&&(this.dynamic="device"!==r.slice(0,6)),this.custom=kb[r],n=this.prefix?h(d(e(this.prefix),c)):["min-"+r+"-"],o=1<n.length?n.slice(1):0,this.prefix=n[0],m=this.breakpoints,gb(m)?(f(m,function(a){if(!a&&0!==a)throw"invalid breakpoint";q=q&&isFinite(a)}),q&&m.sort(j),m.length||b("create @breakpoints")):m=hb[r]||hb[r.split("-").pop()]||b("create @prop"),this.breakpoints=q?h(m,function(a){return pb>=a}):m,this.keys=g(this.breakpoints,this.prefix),this.aka=null,o){for(p=[],k=o.length;k--;)p.push(g(this.breakpoints,o[k]));this.aka=p,this.keys=db.apply(this.keys,p)}return lb.all=lb.all.concat(lb[this.uid]=this.keys),this},target:function(){return this.$e=a(u(lb[this.uid])),D(this.$e,U),this.keys.push(U),this},decideValue:function(){for(var a=null,b=this.breakpoints,c=b.length,d=c;null==a&&d--;)this.fn(b[d])&&(a=this.values[d]);return this.newValue="string"==typeof a?a:this.values[c],this},prepareData:function(b){if(this.$e=a(b),this.mode=C(b),this.values=E(this.$e,this.keys),this.aka)for(var c=this.aka.length;c--;)this.values=i(this.values,E(this.$e,this.aka[c]));return this.decideValue()},updateDOM:function(){return this.currValue===this.newValue?this:(this.currValue=this.newValue,0<this.mode?this.$e[0].setAttribute("src",this.newValue):null==this.newValue?this.$e.empty&&this.$e.empty():this.$e.html?this.$e.html(this.newValue):(this.$e.empty&&this.$e.empty(),this.$e[0].innerHTML=this.newValue),this)}}}(),jb.width=P,jb.height=Q,jb["device-width"]=ib.band,jb["device-height"]=ib.wave,jb["device-pixel-ratio"]=l,N={deviceMin:function(){return qb},deviceMax:function(){return pb},noConflict:K,bridge:L,create:J,addTest:F,datatize:n,camelize:m,render:o,store:D,access:E,target:v,object:yb,crossover:H,action:I,resize:G,ready:Y,affix:g,sift:h,dpr:l,deletes:t,scrollX:w,scrollY:x,deviceW:rb,deviceH:sb,device:ib,inX:z,inY:A,route:j,merge:i,media:Cb,wave:Q,band:P,map:d,each:f,inViewport:B,dataset:s,viewportH:Eb,viewportW:Db},Y(function(){var b=s(W.body,"responsejs"),c=V.JSON&&JSON.parse||a.parseJSON;b=b&&c?c(b):b,b&&b.create&&J(b.create),X.className=X.className.replace(/(^|\s)(no-)?responsejs(\s|$)/,"$1$3")+" responsejs "}),N});

/* ========================================================================
 * mustache.js v??
 * Src : https://github.com/janl/mustache.js
 * ======================================================================== */
var Mustache;(function(a){if(typeof module!=="undefined"&&module.exports){module.exports=a}else{if(typeof define==="function"){define(a)}else{Mustache=a}}}((function(){var u={};u.name="mustache.js";u.version="0.7.0";u.tags=["{{","}}"];u.Scanner=t;u.Context=r;u.Writer=p;var d=/\s*/;var l=/\s+/;var j=/\S/;var h=/\s*=/;var n=/\s*\}/;var s=/#|\^|\/|>|\{|&|=|!/;function o(x,w){return RegExp.prototype.test.call(x,w)}function g(w){return !o(j,w)}var k=Array.isArray||function(w){return Object.prototype.toString.call(w)==="[object Array]"};function f(w){return w.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g,"\\$&")}var c={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;","/":"&#x2F;"};function m(w){return String(w).replace(/[&<>"'\/]/g,function(x){return c[x]})}u.escape=m;function t(w){this.string=w;this.tail=w;this.pos=0}t.prototype.eos=function(){return this.tail===""};t.prototype.scan=function(x){var w=this.tail.match(x);if(w&&w.index===0){this.tail=this.tail.substring(w[0].length);this.pos+=w[0].length;return w[0]}return""};t.prototype.scanUntil=function(x){var w,y=this.tail.search(x);switch(y){case -1:w=this.tail;this.pos+=this.tail.length;this.tail="";break;case 0:w="";break;default:w=this.tail.substring(0,y);this.tail=this.tail.substring(y);this.pos+=y}return w};function r(w,x){this.view=w;this.parent=x;this.clearCache()}r.make=function(w){return(w instanceof r)?w:new r(w)};r.prototype.clearCache=function(){this._cache={}};r.prototype.push=function(w){return new r(w,this)};r.prototype.lookup=function(w){var z=this._cache[w];if(!z){if(w==="."){z=this.view}else{var y=this;while(y){if(w.indexOf(".")>0){var A=w.split("."),x=0;z=y.view;while(z&&x<A.length){z=z[A[x++]]}}else{z=y.view[w]}if(z!=null){break}y=y.parent}}this._cache[w]=z}if(typeof z==="function"){z=z.call(this.view)}return z};function p(){this.clearCache()}p.prototype.clearCache=function(){this._cache={};this._partialCache={}};p.prototype.compile=function(x,w){return this._compile(this._cache,x,x,w)};p.prototype.compilePartial=function(x,y,w){return this._compile(this._partialCache,x,y,w)};p.prototype.render=function(y,w,x){return this.compile(y)(w,x)};p.prototype._compile=function(x,z,B,y){if(!x[z]){var C=u.parse(B,y);var A=e(C);var w=this;x[z]=function(D,F){if(F){if(typeof F==="function"){w._loadPartial=F}else{for(var E in F){w.compilePartial(E,F[E])}}}return A(w,r.make(D),B)}}return x[z]};p.prototype._section=function(w,x,E,D){var C=x.lookup(w);switch(typeof C){case"object":if(k(C)){var y="";for(var z=0,B=C.length;z<B;++z){y+=D(this,x.push(C[z]))}return y}return C?D(this,x.push(C)):"";case"function":var F=this;var A=function(G){return F.render(G,x)};return C.call(x.view,E,A)||"";default:if(C){return D(this,x)}}return""};p.prototype._inverted=function(w,x,z){var y=x.lookup(w);if(!y||(k(y)&&y.length===0)){return z(this,x)}return""};p.prototype._partial=function(w,x){if(!(w in this._partialCache)&&this._loadPartial){this.compilePartial(w,this._loadPartial(w))}var y=this._partialCache[w];return y?y(x):""};p.prototype._name=function(w,x){var y=x.lookup(w);if(typeof y==="function"){y=y.call(x.view)}return(y==null)?"":String(y)};p.prototype._escaped=function(w,x){return u.escape(this._name(w,x))};function i(x){var z=x[3];var w=z;var y;while((y=x[4])&&y.length){x=y[y.length-1];w=x[3]}return[z,w]}function e(y){var w={};function x(A,D,C){if(!w[A]){var B=e(D);w[A]=function(F,E){return B(F,E,C)}}return w[A]}function z(G,E,F){var B="";var D,H;for(var C=0,A=y.length;C<A;++C){D=y[C];switch(D[0]){case"#":H=F.slice.apply(F,i(D));B+=G._section(D[1],E,H,x(C,D[4],F));break;case"^":B+=G._inverted(D[1],E,x(C,D[4],F));break;case">":B+=G._partial(D[1],E);break;case"&":B+=G._name(D[1],E);break;case"name":B+=G._escaped(D[1],E);break;case"text":B+=D[1];break}}return B}return z}function v(B){var w=[];var A=w;var C=[];var y,z;for(var x=0;x<B.length;++x){y=B[x];switch(y[0]){case"#":case"^":y[4]=[];C.push(y);A.push(y);A=y[4];break;case"/":if(C.length===0){throw new Error("Unopened section: "+y[1])}z=C.pop();if(z[1]!==y[1]){throw new Error("Unclosed section: "+z[1])}if(C.length>0){A=C[C.length-1][4]}else{A=w}break;default:A.push(y)}}z=C.pop();if(z){throw new Error("Unclosed section: "+z[1])}return w}function a(z){var y,w;for(var x=0;x<z.length;++x){y=z[x];if(w&&w[0]==="text"&&y[0]==="text"){w[1]+=y[1];w[3]=y[3];z.splice(x--,1)}else{w=y}}}function q(w){if(w.length!==2){throw new Error("Invalid tags: "+w.join(" "))}return[new RegExp(f(w[0])+"\\s*"),new RegExp("\\s*"+f(w[1]))]}u.parse=function(I,K){K=K||u.tags;var J=q(K);var z=new t(I);var G=[],E=[],C=false,L=false;function x(){if(C&&!L){while(E.length){G.splice(E.pop(),1)}}else{E=[]}C=false;L=false}var w,F,H,A;while(!z.eos()){w=z.pos;H=z.scanUntil(J[0]);if(H){for(var B=0,D=H.length;B<D;++B){A=H.charAt(B);if(g(A)){E.push(G.length)}else{L=true}G.push(["text",A,w,w+1]);w+=1;if(A==="\n"){x()}}}w=z.pos;if(!z.scan(J[0])){break}C=true;F=z.scan(s)||"name";z.scan(d);if(F==="="){H=z.scanUntil(h);z.scan(h);z.scanUntil(J[1])}else{if(F==="{"){var y=new RegExp("\\s*"+f("}"+K[1]));H=z.scanUntil(y);z.scan(n);z.scanUntil(J[1]);F="&"}else{H=z.scanUntil(J[1])}}if(!z.scan(J[1])){throw new Error("Unclosed tag at "+z.pos)}G.push([F,H,w,z.pos]);if(F==="name"||F==="{"||F==="&"){L=true}if(F==="="){K=H.split(l);J=q(K)}}a(G);return v(G)};var b=new p();u.clearCache=function(){return b.clearCache()};u.compile=function(x,w){return b.compile(x,w)};u.compilePartial=function(x,y,w){return b.compilePartial(x,y,w)};u.render=function(y,w,x){return b.render(y,w,x)};u.to_html=function(z,x,y,A){var w=u.render(z,x,y);if(typeof A==="function"){A(w)}else{return w}};return u}())));

/* ========================================================================
 * spin.js v2.0.0
 * Src : http://fgnass.github.io/spin.js/
 * ======================================================================== */
!function(a,b){"object"==typeof exports?module.exports=b():"function"==typeof define&&define.amd?define(b):a.Spinner=b()}(this,function(){"use strict";function a(a,b){var c,d=document.createElement(a||"div");for(c in b)d[c]=b[c];return d}function b(a){for(var b=1,c=arguments.length;c>b;b++)a.appendChild(arguments[b]);return a}function c(a,b,c,d){var e=["opacity",b,~~(100*a),c,d].join("-"),f=.01+c/d*100,g=Math.max(1-(1-a)/b*(100-f),a),h=j.substring(0,j.indexOf("Animation")).toLowerCase(),i=h&&"-"+h+"-"||"";return l[e]||(m.insertRule("@"+i+"keyframes "+e+"{0%{opacity:"+g+"}"+f+"%{opacity:"+a+"}"+(f+.01)+"%{opacity:1}"+(f+b)%100+"%{opacity:"+a+"}100%{opacity:"+g+"}}",m.cssRules.length),l[e]=1),e}function d(a,b){var c,d,e=a.style;if(void 0!==e[b])return b;for(b=b.charAt(0).toUpperCase()+b.slice(1),d=0;d<k.length;d++)if(c=k[d]+b,void 0!==e[c])return c}function e(a,b){for(var c in b)a.style[d(a,c)||c]=b[c];return a}function f(a){for(var b=1;b<arguments.length;b++){var c=arguments[b];for(var d in c)void 0===a[d]&&(a[d]=c[d])}return a}function g(a){for(var b={x:a.offsetLeft,y:a.offsetTop};a=a.offsetParent;)b.x+=a.offsetLeft,b.y+=a.offsetTop;return b}function h(a){return"undefined"==typeof this?new h(a):(this.opts=f(a||{},h.defaults,n),void 0)}function i(){function c(b,c){return a("<"+b+' xmlns="urn:schemas-microsoft.com:vml" class="spin-vml">',c)}m.addRule(".spin-vml","behavior:url(#default#VML)"),h.prototype.lines=function(a,d){function f(){return e(c("group",{coordsize:j+" "+j,coordorigin:-i+" "+-i}),{width:j,height:j})}function g(a,g,h){b(l,b(e(f(),{rotation:360/d.lines*a+"deg",left:~~g}),b(e(c("roundrect",{arcsize:d.corners}),{width:i,height:d.width,left:d.radius,top:-d.width>>1,filter:h}),c("fill",{color:d.color,opacity:d.opacity}),c("stroke",{opacity:0}))))}var h,i=d.length+d.width,j=2*i,k=2*-(d.width+d.length)+"px",l=e(f(),{position:"absolute",top:k,left:k});if(d.shadow)for(h=1;h<=d.lines;h++)g(h,-2,"progid:DXImageTransform.Microsoft.Blur(pixelradius=2,makeshadow=1,shadowopacity=.3)");for(h=1;h<=d.lines;h++)g(h);return b(a,l)},h.prototype.opacity=function(a,b,c,d){var e=a.firstChild;d=d.shadow&&d.lines||0,e&&b+d<e.childNodes.length&&(e=e.childNodes[b+d],e=e&&e.firstChild,e=e&&e.firstChild,e&&(e.opacity=c))}}var j,k=["webkit","Moz","ms","O"],l={},m=function(){var c=a("style",{type:"text/css"});return b(document.getElementsByTagName("head")[0],c),c.sheet||c.styleSheet}(),n={lines:12,length:7,width:5,radius:10,rotate:0,corners:1,color:"#000",direction:1,speed:1,trail:100,opacity:.25,fps:20,zIndex:2e9,className:"spinner",top:"auto",left:"auto",position:"relative"};h.defaults={},f(h.prototype,{spin:function(b){this.stop();var c,d,f=this,h=f.opts,i=f.el=e(a(0,{className:h.className}),{position:h.position,width:0,zIndex:h.zIndex}),k=h.radius+h.length+h.width;if(b&&(b.insertBefore(i,b.firstChild||null),d=g(b),c=g(i),e(i,{left:("auto"==h.left?d.x-c.x+(b.offsetWidth>>1):parseInt(h.left,10)+k)+"px",top:("auto"==h.top?d.y-c.y+(b.offsetHeight>>1):parseInt(h.top,10)+k)+"px"})),i.setAttribute("role","progressbar"),f.lines(i,f.opts),!j){var l,m=0,n=(h.lines-1)*(1-h.direction)/2,o=h.fps,p=o/h.speed,q=(1-h.opacity)/(p*h.trail/100),r=p/h.lines;!function s(){m++;for(var a=0;a<h.lines;a++)l=Math.max(1-(m+(h.lines-a)*r)%p*q,h.opacity),f.opacity(i,a*h.direction+n,l,h);f.timeout=f.el&&setTimeout(s,~~(1e3/o))}()}return f},stop:function(){var a=this.el;return a&&(clearTimeout(this.timeout),a.parentNode&&a.parentNode.removeChild(a),this.el=void 0),this},lines:function(d,f){function g(b,c){return e(a(),{position:"absolute",width:f.length+f.width+"px",height:f.width+"px",background:b,boxShadow:c,transformOrigin:"left",transform:"rotate("+~~(360/f.lines*i+f.rotate)+"deg) translate("+f.radius+"px,0)",borderRadius:(f.corners*f.width>>1)+"px"})}for(var h,i=0,k=(f.lines-1)*(1-f.direction)/2;i<f.lines;i++)h=e(a(),{position:"absolute",top:1+~(f.width/2)+"px",transform:f.hwaccel?"translate3d(0,0,0)":"",opacity:f.opacity,animation:j&&c(f.opacity,f.trail,k+i*f.direction,f.lines)+" "+1/f.speed+"s linear infinite"}),f.shadow&&b(h,e(g("#000","0 0 4px #000"),{top:"2px"})),b(d,b(h,g(f.color,"0 0 1px rgba(0,0,0,.1)")));return d},opacity:function(a,b,c){b<a.childNodes.length&&(a.childNodes[b].style.opacity=c)}});var o=e(a("group"),{behavior:"url(#default#VML)"});return!d(o,"transform")&&o.adj?i():j=d(o,"animation"),h});

/* ========================================================================
 * ladda.js v0.8.0
 * Src : http://msurguy.github.io/ladda-bootstrap/
 * ======================================================================== */
(function(t,e){"object"==typeof exports?module.exports=e():"function"==typeof define&&define.amd?define(["spin"],e):t.Ladda=e(t.Spinner)})(this,function(t){"use strict";function e(t){if(t===void 0)return console.warn("Ladda button target must be defined."),void 0;t.querySelector(".ladda-label")||(t.innerHTML='<span class="ladda-label">'+t.innerHTML+"</span>");var e=i(t),n=document.createElement("span");n.className="ladda-spinner",t.appendChild(n);var r,a={start:function(){return t.setAttribute("disabled",""),t.setAttribute("data-loading",""),clearTimeout(r),e.spin(n),this.setProgress(0),this},startAfter:function(t){return clearTimeout(r),r=setTimeout(function(){a.start()},t),this},stop:function(){return t.removeAttribute("disabled"),t.removeAttribute("data-loading"),clearTimeout(r),r=setTimeout(function(){e.stop()},1e3),this},toggle:function(){return this.isLoading()?this.stop():this.start(),this},setProgress:function(e){e=Math.max(Math.min(e,1),0);var n=t.querySelector(".ladda-progress");0===e&&n&&n.parentNode?n.parentNode.removeChild(n):(n||(n=document.createElement("div"),n.className="ladda-progress",t.appendChild(n)),n.style.width=(e||0)*t.offsetWidth+"px")},enable:function(){return this.stop(),this},disable:function(){return this.stop(),t.setAttribute("disabled",""),this},isLoading:function(){return t.hasAttribute("data-loading")}};return o.push(a),a}function n(t,n){n=n||{};var r=[];"string"==typeof t?r=a(document.querySelectorAll(t)):"object"==typeof t&&"string"==typeof t.nodeName&&(r=[t]);for(var i=0,o=r.length;o>i;i++)(function(){var t=r[i];if("function"==typeof t.addEventListener){var a=e(t),o=-1;t.addEventListener("click",function(){a.startAfter(1),"number"==typeof n.timeout&&(clearTimeout(o),o=setTimeout(a.stop,n.timeout)),"function"==typeof n.callback&&n.callback.apply(null,[a])},!1)}})()}function r(){for(var t=0,e=o.length;e>t;t++)o[t].stop()}function i(e){var n,r=e.offsetHeight;r>32&&(r*=.8),e.hasAttribute("data-spinner-size")&&(r=parseInt(e.getAttribute("data-spinner-size"),10)),e.hasAttribute("data-spinner-color")&&(n=e.getAttribute("data-spinner-color"));var i=12,a=.2*r,o=.6*a,s=7>a?2:3;return new t({color:n||"#fff",lines:i,radius:a,length:o,width:s,zIndex:"auto",top:"auto",left:"auto",className:""})}function a(t){for(var e=[],n=0;t.length>n;n++)e.push(t[n]);return e}var o=[];return{bind:n,create:e,stopAll:r}});

/* ========================================================================
 * imagesLoaded.js v3.1.4
 * Src : http://desandro.github.io/imagesloaded/
 * ======================================================================== */
(function(){function e(){}function t(e,t){for(var n=e.length;n--;)if(e[n].listener===t)return n;return-1}function n(e){return function(){return this[e].apply(this,arguments)}}var i=e.prototype,r=this,o=r.EventEmitter;i.getListeners=function(e){var t,n,i=this._getEvents();if("object"==typeof e){t={};for(n in i)i.hasOwnProperty(n)&&e.test(n)&&(t[n]=i[n])}else t=i[e]||(i[e]=[]);return t},i.flattenListeners=function(e){var t,n=[];for(t=0;e.length>t;t+=1)n.push(e[t].listener);return n},i.getListenersAsObject=function(e){var t,n=this.getListeners(e);return n instanceof Array&&(t={},t[e]=n),t||n},i.addListener=function(e,n){var i,r=this.getListenersAsObject(e),o="object"==typeof n;for(i in r)r.hasOwnProperty(i)&&-1===t(r[i],n)&&r[i].push(o?n:{listener:n,once:!1});return this},i.on=n("addListener"),i.addOnceListener=function(e,t){return this.addListener(e,{listener:t,once:!0})},i.once=n("addOnceListener"),i.defineEvent=function(e){return this.getListeners(e),this},i.defineEvents=function(e){for(var t=0;e.length>t;t+=1)this.defineEvent(e[t]);return this},i.removeListener=function(e,n){var i,r,o=this.getListenersAsObject(e);for(r in o)o.hasOwnProperty(r)&&(i=t(o[r],n),-1!==i&&o[r].splice(i,1));return this},i.off=n("removeListener"),i.addListeners=function(e,t){return this.manipulateListeners(!1,e,t)},i.removeListeners=function(e,t){return this.manipulateListeners(!0,e,t)},i.manipulateListeners=function(e,t,n){var i,r,o=e?this.removeListener:this.addListener,s=e?this.removeListeners:this.addListeners;if("object"!=typeof t||t instanceof RegExp)for(i=n.length;i--;)o.call(this,t,n[i]);else for(i in t)t.hasOwnProperty(i)&&(r=t[i])&&("function"==typeof r?o.call(this,i,r):s.call(this,i,r));return this},i.removeEvent=function(e){var t,n=typeof e,i=this._getEvents();if("string"===n)delete i[e];else if("object"===n)for(t in i)i.hasOwnProperty(t)&&e.test(t)&&delete i[t];else delete this._events;return this},i.removeAllListeners=n("removeEvent"),i.emitEvent=function(e,t){var n,i,r,o,s=this.getListenersAsObject(e);for(r in s)if(s.hasOwnProperty(r))for(i=s[r].length;i--;)n=s[r][i],n.once===!0&&this.removeListener(e,n.listener),o=n.listener.apply(this,t||[]),o===this._getOnceReturnValue()&&this.removeListener(e,n.listener);return this},i.trigger=n("emitEvent"),i.emit=function(e){var t=Array.prototype.slice.call(arguments,1);return this.emitEvent(e,t)},i.setOnceReturnValue=function(e){return this._onceReturnValue=e,this},i._getOnceReturnValue=function(){return this.hasOwnProperty("_onceReturnValue")?this._onceReturnValue:!0},i._getEvents=function(){return this._events||(this._events={})},e.noConflict=function(){return r.EventEmitter=o,e},"function"==typeof define&&define.amd?define("eventEmitter/EventEmitter",[],function(){return e}):"object"==typeof module&&module.exports?module.exports=e:this.EventEmitter=e}).call(this),function(e){function t(t){var n=e.event;return n.target=n.target||n.srcElement||t,n}var n=document.documentElement,i=function(){};n.addEventListener?i=function(e,t,n){e.addEventListener(t,n,!1)}:n.attachEvent&&(i=function(e,n,i){e[n+i]=i.handleEvent?function(){var n=t(e);i.handleEvent.call(i,n)}:function(){var n=t(e);i.call(e,n)},e.attachEvent("on"+n,e[n+i])});var r=function(){};n.removeEventListener?r=function(e,t,n){e.removeEventListener(t,n,!1)}:n.detachEvent&&(r=function(e,t,n){e.detachEvent("on"+t,e[t+n]);try{delete e[t+n]}catch(i){e[t+n]=void 0}});var o={bind:i,unbind:r};"function"==typeof define&&define.amd?define("eventie/eventie",o):e.eventie=o}(this),function(e,t){"function"==typeof define&&define.amd?define(["eventEmitter/EventEmitter","eventie/eventie"],function(n,i){return t(e,n,i)}):"object"==typeof exports?module.exports=t(e,require("eventEmitter"),require("eventie")):e.imagesLoaded=t(e,e.EventEmitter,e.eventie)}(this,function(e,t,n){function i(e,t){for(var n in t)e[n]=t[n];return e}function r(e){return"[object Array]"===d.call(e)}function o(e){var t=[];if(r(e))t=e;else if("number"==typeof e.length)for(var n=0,i=e.length;i>n;n++)t.push(e[n]);else t.push(e);return t}function s(e,t,n){if(!(this instanceof s))return new s(e,t);"string"==typeof e&&(e=document.querySelectorAll(e)),this.elements=o(e),this.options=i({},this.options),"function"==typeof t?n=t:i(this.options,t),n&&this.on("always",n),this.getImages(),a&&(this.jqDeferred=new a.Deferred);var r=this;setTimeout(function(){r.check()})}function c(e){this.img=e}function f(e){this.src=e,v[e]=this}var a=e.jQuery,u=e.console,h=u!==void 0,d=Object.prototype.toString;s.prototype=new t,s.prototype.options={},s.prototype.getImages=function(){this.images=[];for(var e=0,t=this.elements.length;t>e;e++){var n=this.elements[e];"IMG"===n.nodeName&&this.addImage(n);for(var i=n.querySelectorAll("img"),r=0,o=i.length;o>r;r++){var s=i[r];this.addImage(s)}}},s.prototype.addImage=function(e){var t=new c(e);this.images.push(t)},s.prototype.check=function(){function e(e,r){return t.options.debug&&h&&u.log("confirm",e,r),t.progress(e),n++,n===i&&t.complete(),!0}var t=this,n=0,i=this.images.length;if(this.hasAnyBroken=!1,!i)return this.complete(),void 0;for(var r=0;i>r;r++){var o=this.images[r];o.on("confirm",e),o.check()}},s.prototype.progress=function(e){this.hasAnyBroken=this.hasAnyBroken||!e.isLoaded;var t=this;setTimeout(function(){t.emit("progress",t,e),t.jqDeferred&&t.jqDeferred.notify&&t.jqDeferred.notify(t,e)})},s.prototype.complete=function(){var e=this.hasAnyBroken?"fail":"done";this.isComplete=!0;var t=this;setTimeout(function(){if(t.emit(e,t),t.emit("always",t),t.jqDeferred){var n=t.hasAnyBroken?"reject":"resolve";t.jqDeferred[n](t)}})},a&&(a.fn.imagesLoaded=function(e,t){var n=new s(this,e,t);return n.jqDeferred.promise(a(this))}),c.prototype=new t,c.prototype.check=function(){var e=v[this.img.src]||new f(this.img.src);if(e.isConfirmed)return this.confirm(e.isLoaded,"cached was confirmed"),void 0;if(this.img.complete&&void 0!==this.img.naturalWidth)return this.confirm(0!==this.img.naturalWidth,"naturalWidth"),void 0;var t=this;e.on("confirm",function(e,n){return t.confirm(e.isLoaded,n),!0}),e.check()},c.prototype.confirm=function(e,t){this.isLoaded=e,this.emit("confirm",this,t)};var v={};return f.prototype=new t,f.prototype.check=function(){if(!this.isChecked){var e=new Image;n.bind(e,"load",this),n.bind(e,"error",this),e.src=this.src,this.isChecked=!0}},f.prototype.handleEvent=function(e){var t="on"+e.type;this[t]&&this[t](e)},f.prototype.onload=function(e){this.confirm(!0,"onload"),this.unbindProxyEvents(e)},f.prototype.onerror=function(e){this.confirm(!1,"onerror"),this.unbindProxyEvents(e)},f.prototype.confirm=function(e,t){this.isConfirmed=!0,this.isLoaded=e,this.emit("confirm",this,t)},f.prototype.unbindProxyEvents=function(e){n.unbind(e.target,"load",this),n.unbind(e.target,"error",this)},s});

/* ========================================================================
 * stellar.js v0.6.2
 * Src : https://github.com/markdalgleish/stellar.js
 * ======================================================================== */
!function(t,e,i,s){function o(e,i){this.element=e,this.options=t.extend({},r,i),this._defaults=r,this._name=n,this.init()}var n="stellar",r={scrollProperty:"scroll",positionProperty:"position",horizontalScrolling:!0,verticalScrolling:!0,horizontalOffset:0,verticalOffset:0,responsive:!1,parallaxBackgrounds:!0,parallaxElements:!0,hideDistantElements:!0,hideElement:function(t){t.hide()},showElement:function(t){t.show()}},a={scroll:{getLeft:function(t){return t.scrollLeft()},setLeft:function(t,e){t.scrollLeft(e)},getTop:function(t){return t.scrollTop()},setTop:function(t,e){t.scrollTop(e)}},position:{getLeft:function(t){return-1*parseInt(t.css("left"),10)},getTop:function(t){return-1*parseInt(t.css("top"),10)}},margin:{getLeft:function(t){return-1*parseInt(t.css("margin-left"),10)},getTop:function(t){return-1*parseInt(t.css("margin-top"),10)}},transform:{getLeft:function(t){var e=getComputedStyle(t[0])[c];return"none"!==e?-1*parseInt(e.match(/(-?[0-9]+)/g)[4],10):0},getTop:function(t){var e=getComputedStyle(t[0])[c];return"none"!==e?-1*parseInt(e.match(/(-?[0-9]+)/g)[5],10):0}}},l={position:{setLeft:function(t,e){t.css("left",e)},setTop:function(t,e){t.css("top",e)}},transform:{setPosition:function(t,e,i,s,o){t[0].style[c]="translate3d("+(e-i)+"px, "+(s-o)+"px, 0)"}}},f=function(){var e,i=/^(Moz|Webkit|Khtml|O|ms|Icab)(?=[A-Z])/,s=t("script")[0].style,o="";for(e in s)if(i.test(e)){o=e.match(i)[0];break}return"WebkitOpacity"in s&&(o="Webkit"),"KhtmlOpacity"in s&&(o="Khtml"),function(t){return o+(o.length>0?t.charAt(0).toUpperCase()+t.slice(1):t)}}(),c=f("transform"),h=t("<div />",{style:"background:#fff"}).css("background-position-x")!==s,p=h?function(t,e,i){t.css({"background-position-x":e,"background-position-y":i})}:function(t,e,i){t.css("background-position",e+" "+i)},d=h?function(t){return[t.css("background-position-x"),t.css("background-position-y")]}:function(t){return t.css("background-position").split(" ")},u=e.requestAnimationFrame||e.webkitRequestAnimationFrame||e.mozRequestAnimationFrame||e.oRequestAnimationFrame||e.msRequestAnimationFrame||function(t){setTimeout(t,1e3/60)};o.prototype={init:function(){this.options.name=n+"_"+Math.floor(1e9*Math.random()),this._defineElements(),this._defineGetters(),this._defineSetters(),this._handleWindowLoadAndResize(),this._detectViewport(),this.refresh({firstLoad:!0}),"scroll"===this.options.scrollProperty?this._handleScrollEvent():this._startAnimationLoop()},_defineElements:function(){this.element===i.body&&(this.element=e),this.$scrollElement=t(this.element),this.$element=this.element===e?t("body"):this.$scrollElement,this.$viewportElement=this.options.viewportElement!==s?t(this.options.viewportElement):this.$scrollElement[0]===e||"scroll"===this.options.scrollProperty?this.$scrollElement:this.$scrollElement.parent()},_defineGetters:function(){var t=this,e=a[t.options.scrollProperty];this._getScrollLeft=function(){return e.getLeft(t.$scrollElement)},this._getScrollTop=function(){return e.getTop(t.$scrollElement)}},_defineSetters:function(){var e=this,i=a[e.options.scrollProperty],s=l[e.options.positionProperty],o=i.setLeft,n=i.setTop;this._setScrollLeft="function"==typeof o?function(t){o(e.$scrollElement,t)}:t.noop,this._setScrollTop="function"==typeof n?function(t){n(e.$scrollElement,t)}:t.noop,this._setPosition=s.setPosition||function(t,i,o,n,r){e.options.horizontalScrolling&&s.setLeft(t,i,o),e.options.verticalScrolling&&s.setTop(t,n,r)}},_handleWindowLoadAndResize:function(){var i=this,s=t(e);i.options.responsive&&s.bind("load."+this.name,function(){i.refresh()}),s.bind("resize."+this.name,function(){i._detectViewport(),i.options.responsive&&i.refresh()})},refresh:function(i){var s=this,o=s._getScrollLeft(),n=s._getScrollTop();i&&i.firstLoad||this._reset(),this._setScrollLeft(0),this._setScrollTop(0),this._setOffsets(),this._findParticles(),this._findBackgrounds(),i&&i.firstLoad&&/WebKit/.test(navigator.userAgent)&&t(e).load(function(){var t=s._getScrollLeft(),e=s._getScrollTop();s._setScrollLeft(t+1),s._setScrollTop(e+1),s._setScrollLeft(t),s._setScrollTop(e)}),this._setScrollLeft(o),this._setScrollTop(n)},_detectViewport:function(){var t=this.$viewportElement.offset(),e=null!==t&&t!==s;this.viewportWidth=this.$viewportElement.width(),this.viewportHeight=this.$viewportElement.height(),this.viewportOffsetTop=e?t.top:0,this.viewportOffsetLeft=e?t.left:0},_findParticles:function(){{var e=this;this._getScrollLeft(),this._getScrollTop()}if(this.particles!==s)for(var i=this.particles.length-1;i>=0;i--)this.particles[i].$element.data("stellar-elementIsActive",s);this.particles=[],this.options.parallaxElements&&this.$element.find("[data-stellar-ratio]").each(function(){var i,o,n,r,a,l,f,c,h,p=t(this),d=0,u=0,g=0,m=0;if(p.data("stellar-elementIsActive")){if(p.data("stellar-elementIsActive")!==this)return}else p.data("stellar-elementIsActive",this);e.options.showElement(p),p.data("stellar-startingLeft")?(p.css("left",p.data("stellar-startingLeft")),p.css("top",p.data("stellar-startingTop"))):(p.data("stellar-startingLeft",p.css("left")),p.data("stellar-startingTop",p.css("top"))),n=p.position().left,r=p.position().top,a="auto"===p.css("margin-left")?0:parseInt(p.css("margin-left"),10),l="auto"===p.css("margin-top")?0:parseInt(p.css("margin-top"),10),c=p.offset().left-a,h=p.offset().top-l,p.parents().each(function(){var e=t(this);return e.data("stellar-offset-parent")===!0?(d=g,u=m,f=e,!1):(g+=e.position().left,void(m+=e.position().top))}),i=p.data("stellar-horizontal-offset")!==s?p.data("stellar-horizontal-offset"):f!==s&&f.data("stellar-horizontal-offset")!==s?f.data("stellar-horizontal-offset"):e.horizontalOffset,o=p.data("stellar-vertical-offset")!==s?p.data("stellar-vertical-offset"):f!==s&&f.data("stellar-vertical-offset")!==s?f.data("stellar-vertical-offset"):e.verticalOffset,e.particles.push({$element:p,$offsetParent:f,isFixed:"fixed"===p.css("position"),horizontalOffset:i,verticalOffset:o,startingPositionLeft:n,startingPositionTop:r,startingOffsetLeft:c,startingOffsetTop:h,parentOffsetLeft:d,parentOffsetTop:u,stellarRatio:p.data("stellar-ratio")!==s?p.data("stellar-ratio"):1,width:p.outerWidth(!0),height:p.outerHeight(!0),isHidden:!1})})},_findBackgrounds:function(){var e,i=this,o=this._getScrollLeft(),n=this._getScrollTop();this.backgrounds=[],this.options.parallaxBackgrounds&&(e=this.$element.find("[data-stellar-background-ratio]"),this.$element.data("stellar-background-ratio")&&(e=e.add(this.$element)),e.each(function(){var e,r,a,l,f,c,h,u=t(this),g=d(u),m=0,v=0,L=0,_=0;if(u.data("stellar-backgroundIsActive")){if(u.data("stellar-backgroundIsActive")!==this)return}else u.data("stellar-backgroundIsActive",this);u.data("stellar-backgroundStartingLeft")?p(u,u.data("stellar-backgroundStartingLeft"),u.data("stellar-backgroundStartingTop")):(u.data("stellar-backgroundStartingLeft",g[0]),u.data("stellar-backgroundStartingTop",g[1])),a="auto"===u.css("margin-left")?0:parseInt(u.css("margin-left"),10),l="auto"===u.css("margin-top")?0:parseInt(u.css("margin-top"),10),f=u.offset().left-a-o,c=u.offset().top-l-n,u.parents().each(function(){var e=t(this);return e.data("stellar-offset-parent")===!0?(m=L,v=_,h=e,!1):(L+=e.position().left,void(_+=e.position().top))}),e=u.data("stellar-horizontal-offset")!==s?u.data("stellar-horizontal-offset"):h!==s&&h.data("stellar-horizontal-offset")!==s?h.data("stellar-horizontal-offset"):i.horizontalOffset,r=u.data("stellar-vertical-offset")!==s?u.data("stellar-vertical-offset"):h!==s&&h.data("stellar-vertical-offset")!==s?h.data("stellar-vertical-offset"):i.verticalOffset,i.backgrounds.push({$element:u,$offsetParent:h,isFixed:"fixed"===u.css("background-attachment"),horizontalOffset:e,verticalOffset:r,startingValueLeft:g[0],startingValueTop:g[1],startingBackgroundPositionLeft:isNaN(parseInt(g[0],10))?0:parseInt(g[0],10),startingBackgroundPositionTop:isNaN(parseInt(g[1],10))?0:parseInt(g[1],10),startingPositionLeft:u.position().left,startingPositionTop:u.position().top,startingOffsetLeft:f,startingOffsetTop:c,parentOffsetLeft:m,parentOffsetTop:v,stellarRatio:u.data("stellar-background-ratio")===s?1:u.data("stellar-background-ratio")})}))},_reset:function(){var t,e,i,s,o;for(o=this.particles.length-1;o>=0;o--)t=this.particles[o],e=t.$element.data("stellar-startingLeft"),i=t.$element.data("stellar-startingTop"),this._setPosition(t.$element,e,e,i,i),this.options.showElement(t.$element),t.$element.data("stellar-startingLeft",null).data("stellar-elementIsActive",null).data("stellar-backgroundIsActive",null);for(o=this.backgrounds.length-1;o>=0;o--)s=this.backgrounds[o],s.$element.data("stellar-backgroundStartingLeft",null).data("stellar-backgroundStartingTop",null),p(s.$element,s.startingValueLeft,s.startingValueTop)},destroy:function(){this._reset(),this.$scrollElement.unbind("resize."+this.name).unbind("scroll."+this.name),this._animationLoop=t.noop,t(e).unbind("load."+this.name).unbind("resize."+this.name)},_setOffsets:function(){var i=this,s=t(e);s.unbind("resize.horizontal-"+this.name).unbind("resize.vertical-"+this.name),"function"==typeof this.options.horizontalOffset?(this.horizontalOffset=this.options.horizontalOffset(),s.bind("resize.horizontal-"+this.name,function(){i.horizontalOffset=i.options.horizontalOffset()})):this.horizontalOffset=this.options.horizontalOffset,"function"==typeof this.options.verticalOffset?(this.verticalOffset=this.options.verticalOffset(),s.bind("resize.vertical-"+this.name,function(){i.verticalOffset=i.options.verticalOffset()})):this.verticalOffset=this.options.verticalOffset},_repositionElements:function(){var t,e,i,s,o,n,r,a,l,f,c=this._getScrollLeft(),h=this._getScrollTop(),d=!0,u=!0;if(this.currentScrollLeft!==c||this.currentScrollTop!==h||this.currentWidth!==this.viewportWidth||this.currentHeight!==this.viewportHeight){for(this.currentScrollLeft=c,this.currentScrollTop=h,this.currentWidth=this.viewportWidth,this.currentHeight=this.viewportHeight,f=this.particles.length-1;f>=0;f--)t=this.particles[f],e=t.isFixed?1:0,this.options.horizontalScrolling?(n=(c+t.horizontalOffset+this.viewportOffsetLeft+t.startingPositionLeft-t.startingOffsetLeft+t.parentOffsetLeft)*-(t.stellarRatio+e-1)+t.startingPositionLeft,a=n-t.startingPositionLeft+t.startingOffsetLeft):(n=t.startingPositionLeft,a=t.startingOffsetLeft),this.options.verticalScrolling?(r=(h+t.verticalOffset+this.viewportOffsetTop+t.startingPositionTop-t.startingOffsetTop+t.parentOffsetTop)*-(t.stellarRatio+e-1)+t.startingPositionTop,l=r-t.startingPositionTop+t.startingOffsetTop):(r=t.startingPositionTop,l=t.startingOffsetTop),this.options.hideDistantElements&&(u=!this.options.horizontalScrolling||a+t.width>(t.isFixed?0:c)&&a<(t.isFixed?0:c)+this.viewportWidth+this.viewportOffsetLeft,d=!this.options.verticalScrolling||l+t.height>(t.isFixed?0:h)&&l<(t.isFixed?0:h)+this.viewportHeight+this.viewportOffsetTop),u&&d?(t.isHidden&&(this.options.showElement(t.$element),t.isHidden=!1),this._setPosition(t.$element,n,t.startingPositionLeft,r,t.startingPositionTop)):t.isHidden||(this.options.hideElement(t.$element),t.isHidden=!0);for(f=this.backgrounds.length-1;f>=0;f--)i=this.backgrounds[f],e=i.isFixed?0:1,s=this.options.horizontalScrolling?(c+i.horizontalOffset-this.viewportOffsetLeft-i.startingOffsetLeft+i.parentOffsetLeft-i.startingBackgroundPositionLeft)*(e-i.stellarRatio)+"px":i.startingValueLeft,o=this.options.verticalScrolling?(h+i.verticalOffset-this.viewportOffsetTop-i.startingOffsetTop+i.parentOffsetTop-i.startingBackgroundPositionTop)*(e-i.stellarRatio)+"px":i.startingValueTop,p(i.$element,s,o)}},_handleScrollEvent:function(){var t=this,e=!1,i=function(){t._repositionElements(),e=!1},s=function(){e||(u(i),e=!0)};this.$scrollElement.bind("scroll."+this.name,s),s()},_startAnimationLoop:function(){var t=this;this._animationLoop=function(){u(t._animationLoop),t._repositionElements()},this._animationLoop()}},t.fn[n]=function(e){var i=arguments;return e===s||"object"==typeof e?this.each(function(){t.data(this,"plugin_"+n)||t.data(this,"plugin_"+n,new o(this,e))}):"string"==typeof e&&"_"!==e[0]&&"init"!==e?this.each(function(){var s=t.data(this,"plugin_"+n);s instanceof o&&"function"==typeof s[e]&&s[e].apply(s,Array.prototype.slice.call(i,1)),"destroy"===e&&t.data(this,"plugin_"+n,null)}):void 0},t[n]=function(){var i=t(e);return i.stellar.apply(i,Array.prototype.slice.call(arguments,0))},t[n].scrollProperty=a,t[n].positionProperty=l,e.Stellar=o}(jQuery,this,document);

/* ========================================================================
 * jquery.counterup.js 1.0
 * Src : https://github.com/bfintal/Counter-Up
 * ======================================================================== */
(function(e){"use strict";e.fn.counterUp=function(t){var n=e.extend({time:400,delay:10},t);return this.each(function(){var t=e(this),r=n,i=function(){var e=[],n=r.time/r.delay,i=t.text(),s=/[0-9]+,[0-9]+/.test(i);i=i.replace(/,/g,"");var o=/^[0-9]+$/.test(i),u=/^[0-9]+\.[0-9]+$/.test(i),a=u?(i.split(".")[1]||[]).length:0;for(var f=n;f>=1;f--){var l=parseInt(i/n*f);u&&(l=parseFloat(i/n*f).toFixed(a));if(s)while(/(\d+)(\d{3})/.test(l.toString()))l=l.toString().replace(/(\d+)(\d{3})/,"$1,$2");e.unshift(l)}t.data("counterup-nums",e);t.text("0");var c=function(){t.text(t.data("counterup-nums").shift());if(t.data("counterup-nums").length)setTimeout(t.data("counterup-func"),r.delay);else{delete t.data("counterup-nums");t.data("counterup-nums",null);t.data("counterup-func",null)}};t.data("counterup-func",c);setTimeout(t.data("counterup-func"),r.delay)};t.waypoint(i,{offset:"100%",triggerOnce:!0})})}})(jQuery);

/* ========================================================================
 * moment.js v2.6.0
 * Src : http://momentjs.com/
 * ======================================================================== */
(function(a){function b(){return{empty:!1,unusedTokens:[],unusedInput:[],overflow:-2,charsLeftOver:0,nullInput:!1,invalidMonth:null,invalidFormat:!1,userInvalidated:!1,iso:!1}}function c(a,b){function c(){ib.suppressDeprecationWarnings===!1&&"undefined"!=typeof console&&console.warn&&console.warn("Deprecation warning: "+a)}var d=!0;return i(function(){return d&&(c(),d=!1),b.apply(this,arguments)},b)}function d(a,b){return function(c){return l(a.call(this,c),b)}}function e(a,b){return function(c){return this.lang().ordinal(a.call(this,c),b)}}function f(){}function g(a){y(a),i(this,a)}function h(a){var b=r(a),c=b.year||0,d=b.quarter||0,e=b.month||0,f=b.week||0,g=b.day||0,h=b.hour||0,i=b.minute||0,j=b.second||0,k=b.millisecond||0;this._milliseconds=+k+1e3*j+6e4*i+36e5*h,this._days=+g+7*f,this._months=+e+3*d+12*c,this._data={},this._bubble()}function i(a,b){for(var c in b)b.hasOwnProperty(c)&&(a[c]=b[c]);return b.hasOwnProperty("toString")&&(a.toString=b.toString),b.hasOwnProperty("valueOf")&&(a.valueOf=b.valueOf),a}function j(a){var b,c={};for(b in a)a.hasOwnProperty(b)&&wb.hasOwnProperty(b)&&(c[b]=a[b]);return c}function k(a){return 0>a?Math.ceil(a):Math.floor(a)}function l(a,b,c){for(var d=""+Math.abs(a),e=a>=0;d.length<b;)d="0"+d;return(e?c?"+":"":"-")+d}function m(a,b,c,d){var e=b._milliseconds,f=b._days,g=b._months;d=null==d?!0:d,e&&a._d.setTime(+a._d+e*c),f&&db(a,"Date",cb(a,"Date")+f*c),g&&bb(a,cb(a,"Month")+g*c),d&&ib.updateOffset(a,f||g)}function n(a){return"[object Array]"===Object.prototype.toString.call(a)}function o(a){return"[object Date]"===Object.prototype.toString.call(a)||a instanceof Date}function p(a,b,c){var d,e=Math.min(a.length,b.length),f=Math.abs(a.length-b.length),g=0;for(d=0;e>d;d++)(c&&a[d]!==b[d]||!c&&t(a[d])!==t(b[d]))&&g++;return g+f}function q(a){if(a){var b=a.toLowerCase().replace(/(.)s$/,"$1");a=Zb[a]||$b[b]||b}return a}function r(a){var b,c,d={};for(c in a)a.hasOwnProperty(c)&&(b=q(c),b&&(d[b]=a[c]));return d}function s(b){var c,d;if(0===b.indexOf("week"))c=7,d="day";else{if(0!==b.indexOf("month"))return;c=12,d="month"}ib[b]=function(e,f){var g,h,i=ib.fn._lang[b],j=[];if("number"==typeof e&&(f=e,e=a),h=function(a){var b=ib().utc().set(d,a);return i.call(ib.fn._lang,b,e||"")},null!=f)return h(f);for(g=0;c>g;g++)j.push(h(g));return j}}function t(a){var b=+a,c=0;return 0!==b&&isFinite(b)&&(c=b>=0?Math.floor(b):Math.ceil(b)),c}function u(a,b){return new Date(Date.UTC(a,b+1,0)).getUTCDate()}function v(a,b,c){return $(ib([a,11,31+b-c]),b,c).week}function w(a){return x(a)?366:365}function x(a){return a%4===0&&a%100!==0||a%400===0}function y(a){var b;a._a&&-2===a._pf.overflow&&(b=a._a[pb]<0||a._a[pb]>11?pb:a._a[qb]<1||a._a[qb]>u(a._a[ob],a._a[pb])?qb:a._a[rb]<0||a._a[rb]>23?rb:a._a[sb]<0||a._a[sb]>59?sb:a._a[tb]<0||a._a[tb]>59?tb:a._a[ub]<0||a._a[ub]>999?ub:-1,a._pf._overflowDayOfYear&&(ob>b||b>qb)&&(b=qb),a._pf.overflow=b)}function z(a){return null==a._isValid&&(a._isValid=!isNaN(a._d.getTime())&&a._pf.overflow<0&&!a._pf.empty&&!a._pf.invalidMonth&&!a._pf.nullInput&&!a._pf.invalidFormat&&!a._pf.userInvalidated,a._strict&&(a._isValid=a._isValid&&0===a._pf.charsLeftOver&&0===a._pf.unusedTokens.length)),a._isValid}function A(a){return a?a.toLowerCase().replace("_","-"):a}function B(a,b){return b._isUTC?ib(a).zone(b._offset||0):ib(a).local()}function C(a,b){return b.abbr=a,vb[a]||(vb[a]=new f),vb[a].set(b),vb[a]}function D(a){delete vb[a]}function E(a){var b,c,d,e,f=0,g=function(a){if(!vb[a]&&xb)try{require("./lang/"+a)}catch(b){}return vb[a]};if(!a)return ib.fn._lang;if(!n(a)){if(c=g(a))return c;a=[a]}for(;f<a.length;){for(e=A(a[f]).split("-"),b=e.length,d=A(a[f+1]),d=d?d.split("-"):null;b>0;){if(c=g(e.slice(0,b).join("-")))return c;if(d&&d.length>=b&&p(e,d,!0)>=b-1)break;b--}f++}return ib.fn._lang}function F(a){return a.match(/\[[\s\S]/)?a.replace(/^\[|\]$/g,""):a.replace(/\\/g,"")}function G(a){var b,c,d=a.match(Bb);for(b=0,c=d.length;c>b;b++)d[b]=cc[d[b]]?cc[d[b]]:F(d[b]);return function(e){var f="";for(b=0;c>b;b++)f+=d[b]instanceof Function?d[b].call(e,a):d[b];return f}}function H(a,b){return a.isValid()?(b=I(b,a.lang()),_b[b]||(_b[b]=G(b)),_b[b](a)):a.lang().invalidDate()}function I(a,b){function c(a){return b.longDateFormat(a)||a}var d=5;for(Cb.lastIndex=0;d>=0&&Cb.test(a);)a=a.replace(Cb,c),Cb.lastIndex=0,d-=1;return a}function J(a,b){var c,d=b._strict;switch(a){case"Q":return Nb;case"DDDD":return Pb;case"YYYY":case"GGGG":case"gggg":return d?Qb:Fb;case"Y":case"G":case"g":return Sb;case"YYYYYY":case"YYYYY":case"GGGGG":case"ggggg":return d?Rb:Gb;case"S":if(d)return Nb;case"SS":if(d)return Ob;case"SSS":if(d)return Pb;case"DDD":return Eb;case"MMM":case"MMMM":case"dd":case"ddd":case"dddd":return Ib;case"a":case"A":return E(b._l)._meridiemParse;case"X":return Lb;case"Z":case"ZZ":return Jb;case"T":return Kb;case"SSSS":return Hb;case"MM":case"DD":case"YY":case"GG":case"gg":case"HH":case"hh":case"mm":case"ss":case"ww":case"WW":return d?Ob:Db;case"M":case"D":case"d":case"H":case"h":case"m":case"s":case"w":case"W":case"e":case"E":return Db;case"Do":return Mb;default:return c=new RegExp(R(Q(a.replace("\\","")),"i"))}}function K(a){a=a||"";var b=a.match(Jb)||[],c=b[b.length-1]||[],d=(c+"").match(Xb)||["-",0,0],e=+(60*d[1])+t(d[2]);return"+"===d[0]?-e:e}function L(a,b,c){var d,e=c._a;switch(a){case"Q":null!=b&&(e[pb]=3*(t(b)-1));break;case"M":case"MM":null!=b&&(e[pb]=t(b)-1);break;case"MMM":case"MMMM":d=E(c._l).monthsParse(b),null!=d?e[pb]=d:c._pf.invalidMonth=b;break;case"D":case"DD":null!=b&&(e[qb]=t(b));break;case"Do":null!=b&&(e[qb]=t(parseInt(b,10)));break;case"DDD":case"DDDD":null!=b&&(c._dayOfYear=t(b));break;case"YY":e[ob]=ib.parseTwoDigitYear(b);break;case"YYYY":case"YYYYY":case"YYYYYY":e[ob]=t(b);break;case"a":case"A":c._isPm=E(c._l).isPM(b);break;case"H":case"HH":case"h":case"hh":e[rb]=t(b);break;case"m":case"mm":e[sb]=t(b);break;case"s":case"ss":e[tb]=t(b);break;case"S":case"SS":case"SSS":case"SSSS":e[ub]=t(1e3*("0."+b));break;case"X":c._d=new Date(1e3*parseFloat(b));break;case"Z":case"ZZ":c._useUTC=!0,c._tzm=K(b);break;case"w":case"ww":case"W":case"WW":case"d":case"dd":case"ddd":case"dddd":case"e":case"E":a=a.substr(0,1);case"gg":case"gggg":case"GG":case"GGGG":case"GGGGG":a=a.substr(0,2),b&&(c._w=c._w||{},c._w[a]=b)}}function M(a){var b,c,d,e,f,g,h,i,j,k,l=[];if(!a._d){for(d=O(a),a._w&&null==a._a[qb]&&null==a._a[pb]&&(f=function(b){var c=parseInt(b,10);return b?b.length<3?c>68?1900+c:2e3+c:c:null==a._a[ob]?ib().weekYear():a._a[ob]},g=a._w,null!=g.GG||null!=g.W||null!=g.E?h=_(f(g.GG),g.W||1,g.E,4,1):(i=E(a._l),j=null!=g.d?X(g.d,i):null!=g.e?parseInt(g.e,10)+i._week.dow:0,k=parseInt(g.w,10)||1,null!=g.d&&j<i._week.dow&&k++,h=_(f(g.gg),k,j,i._week.doy,i._week.dow)),a._a[ob]=h.year,a._dayOfYear=h.dayOfYear),a._dayOfYear&&(e=null==a._a[ob]?d[ob]:a._a[ob],a._dayOfYear>w(e)&&(a._pf._overflowDayOfYear=!0),c=W(e,0,a._dayOfYear),a._a[pb]=c.getUTCMonth(),a._a[qb]=c.getUTCDate()),b=0;3>b&&null==a._a[b];++b)a._a[b]=l[b]=d[b];for(;7>b;b++)a._a[b]=l[b]=null==a._a[b]?2===b?1:0:a._a[b];l[rb]+=t((a._tzm||0)/60),l[sb]+=t((a._tzm||0)%60),a._d=(a._useUTC?W:V).apply(null,l)}}function N(a){var b;a._d||(b=r(a._i),a._a=[b.year,b.month,b.day,b.hour,b.minute,b.second,b.millisecond],M(a))}function O(a){var b=new Date;return a._useUTC?[b.getUTCFullYear(),b.getUTCMonth(),b.getUTCDate()]:[b.getFullYear(),b.getMonth(),b.getDate()]}function P(a){a._a=[],a._pf.empty=!0;var b,c,d,e,f,g=E(a._l),h=""+a._i,i=h.length,j=0;for(d=I(a._f,g).match(Bb)||[],b=0;b<d.length;b++)e=d[b],c=(h.match(J(e,a))||[])[0],c&&(f=h.substr(0,h.indexOf(c)),f.length>0&&a._pf.unusedInput.push(f),h=h.slice(h.indexOf(c)+c.length),j+=c.length),cc[e]?(c?a._pf.empty=!1:a._pf.unusedTokens.push(e),L(e,c,a)):a._strict&&!c&&a._pf.unusedTokens.push(e);a._pf.charsLeftOver=i-j,h.length>0&&a._pf.unusedInput.push(h),a._isPm&&a._a[rb]<12&&(a._a[rb]+=12),a._isPm===!1&&12===a._a[rb]&&(a._a[rb]=0),M(a),y(a)}function Q(a){return a.replace(/\\(\[)|\\(\])|\[([^\]\[]*)\]|\\(.)/g,function(a,b,c,d,e){return b||c||d||e})}function R(a){return a.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")}function S(a){var c,d,e,f,g;if(0===a._f.length)return a._pf.invalidFormat=!0,void(a._d=new Date(0/0));for(f=0;f<a._f.length;f++)g=0,c=i({},a),c._pf=b(),c._f=a._f[f],P(c),z(c)&&(g+=c._pf.charsLeftOver,g+=10*c._pf.unusedTokens.length,c._pf.score=g,(null==e||e>g)&&(e=g,d=c));i(a,d||c)}function T(a){var b,c,d=a._i,e=Tb.exec(d);if(e){for(a._pf.iso=!0,b=0,c=Vb.length;c>b;b++)if(Vb[b][1].exec(d)){a._f=Vb[b][0]+(e[6]||" ");break}for(b=0,c=Wb.length;c>b;b++)if(Wb[b][1].exec(d)){a._f+=Wb[b][0];break}d.match(Jb)&&(a._f+="Z"),P(a)}else ib.createFromInputFallback(a)}function U(b){var c=b._i,d=yb.exec(c);c===a?b._d=new Date:d?b._d=new Date(+d[1]):"string"==typeof c?T(b):n(c)?(b._a=c.slice(0),M(b)):o(c)?b._d=new Date(+c):"object"==typeof c?N(b):"number"==typeof c?b._d=new Date(c):ib.createFromInputFallback(b)}function V(a,b,c,d,e,f,g){var h=new Date(a,b,c,d,e,f,g);return 1970>a&&h.setFullYear(a),h}function W(a){var b=new Date(Date.UTC.apply(null,arguments));return 1970>a&&b.setUTCFullYear(a),b}function X(a,b){if("string"==typeof a)if(isNaN(a)){if(a=b.weekdaysParse(a),"number"!=typeof a)return null}else a=parseInt(a,10);return a}function Y(a,b,c,d,e){return e.relativeTime(b||1,!!c,a,d)}function Z(a,b,c){var d=nb(Math.abs(a)/1e3),e=nb(d/60),f=nb(e/60),g=nb(f/24),h=nb(g/365),i=45>d&&["s",d]||1===e&&["m"]||45>e&&["mm",e]||1===f&&["h"]||22>f&&["hh",f]||1===g&&["d"]||25>=g&&["dd",g]||45>=g&&["M"]||345>g&&["MM",nb(g/30)]||1===h&&["y"]||["yy",h];return i[2]=b,i[3]=a>0,i[4]=c,Y.apply({},i)}function $(a,b,c){var d,e=c-b,f=c-a.day();return f>e&&(f-=7),e-7>f&&(f+=7),d=ib(a).add("d",f),{week:Math.ceil(d.dayOfYear()/7),year:d.year()}}function _(a,b,c,d,e){var f,g,h=W(a,0,1).getUTCDay();return c=null!=c?c:e,f=e-h+(h>d?7:0)-(e>h?7:0),g=7*(b-1)+(c-e)+f+1,{year:g>0?a:a-1,dayOfYear:g>0?g:w(a-1)+g}}function ab(b){var c=b._i,d=b._f;return null===c||d===a&&""===c?ib.invalid({nullInput:!0}):("string"==typeof c&&(b._i=c=E().preparse(c)),ib.isMoment(c)?(b=j(c),b._d=new Date(+c._d)):d?n(d)?S(b):P(b):U(b),new g(b))}function bb(a,b){var c;return"string"==typeof b&&(b=a.lang().monthsParse(b),"number"!=typeof b)?a:(c=Math.min(a.date(),u(a.year(),b)),a._d["set"+(a._isUTC?"UTC":"")+"Month"](b,c),a)}function cb(a,b){return a._d["get"+(a._isUTC?"UTC":"")+b]()}function db(a,b,c){return"Month"===b?bb(a,c):a._d["set"+(a._isUTC?"UTC":"")+b](c)}function eb(a,b){return function(c){return null!=c?(db(this,a,c),ib.updateOffset(this,b),this):cb(this,a)}}function fb(a){ib.duration.fn[a]=function(){return this._data[a]}}function gb(a,b){ib.duration.fn["as"+a]=function(){return+this/b}}function hb(a){"undefined"==typeof ender&&(jb=mb.moment,mb.moment=a?c("Accessing Moment through the global scope is deprecated, and will be removed in an upcoming release.",ib):ib)}for(var ib,jb,kb,lb="2.6.0",mb="undefined"!=typeof global?global:this,nb=Math.round,ob=0,pb=1,qb=2,rb=3,sb=4,tb=5,ub=6,vb={},wb={_isAMomentObject:null,_i:null,_f:null,_l:null,_strict:null,_isUTC:null,_offset:null,_pf:null,_lang:null},xb="undefined"!=typeof module&&module.exports,yb=/^\/?Date\((\-?\d+)/i,zb=/(\-)?(?:(\d*)\.)?(\d+)\:(\d+)(?:\:(\d+)\.?(\d{3})?)?/,Ab=/^(-)?P(?:(?:([0-9,.]*)Y)?(?:([0-9,.]*)M)?(?:([0-9,.]*)D)?(?:T(?:([0-9,.]*)H)?(?:([0-9,.]*)M)?(?:([0-9,.]*)S)?)?|([0-9,.]*)W)$/,Bb=/(\[[^\[]*\])|(\\)?(Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Q|YYYYYY|YYYYY|YYYY|YY|gg(ggg?)?|GG(GGG?)?|e|E|a|A|hh?|HH?|mm?|ss?|S{1,4}|X|zz?|ZZ?|.)/g,Cb=/(\[[^\[]*\])|(\\)?(LT|LL?L?L?|l{1,4})/g,Db=/\d\d?/,Eb=/\d{1,3}/,Fb=/\d{1,4}/,Gb=/[+\-]?\d{1,6}/,Hb=/\d+/,Ib=/[0-9]*['a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF\/]+(\s*?[\u0600-\u06FF]+){1,2}/i,Jb=/Z|[\+\-]\d\d:?\d\d/gi,Kb=/T/i,Lb=/[\+\-]?\d+(\.\d{1,3})?/,Mb=/\d{1,2}/,Nb=/\d/,Ob=/\d\d/,Pb=/\d{3}/,Qb=/\d{4}/,Rb=/[+-]?\d{6}/,Sb=/[+-]?\d+/,Tb=/^\s*(?:[+-]\d{6}|\d{4})-(?:(\d\d-\d\d)|(W\d\d$)|(W\d\d-\d)|(\d\d\d))((T| )(\d\d(:\d\d(:\d\d(\.\d+)?)?)?)?([\+\-]\d\d(?::?\d\d)?|\s*Z)?)?$/,Ub="YYYY-MM-DDTHH:mm:ssZ",Vb=[["YYYYYY-MM-DD",/[+-]\d{6}-\d{2}-\d{2}/],["YYYY-MM-DD",/\d{4}-\d{2}-\d{2}/],["GGGG-[W]WW-E",/\d{4}-W\d{2}-\d/],["GGGG-[W]WW",/\d{4}-W\d{2}/],["YYYY-DDD",/\d{4}-\d{3}/]],Wb=[["HH:mm:ss.SSSS",/(T| )\d\d:\d\d:\d\d\.\d+/],["HH:mm:ss",/(T| )\d\d:\d\d:\d\d/],["HH:mm",/(T| )\d\d:\d\d/],["HH",/(T| )\d\d/]],Xb=/([\+\-]|\d\d)/gi,Yb=("Date|Hours|Minutes|Seconds|Milliseconds".split("|"),{Milliseconds:1,Seconds:1e3,Minutes:6e4,Hours:36e5,Days:864e5,Months:2592e6,Years:31536e6}),Zb={ms:"millisecond",s:"second",m:"minute",h:"hour",d:"day",D:"date",w:"week",W:"isoWeek",M:"month",Q:"quarter",y:"year",DDD:"dayOfYear",e:"weekday",E:"isoWeekday",gg:"weekYear",GG:"isoWeekYear"},$b={dayofyear:"dayOfYear",isoweekday:"isoWeekday",isoweek:"isoWeek",weekyear:"weekYear",isoweekyear:"isoWeekYear"},_b={},ac="DDD w W M D d".split(" "),bc="M D H h m s w W".split(" "),cc={M:function(){return this.month()+1},MMM:function(a){return this.lang().monthsShort(this,a)},MMMM:function(a){return this.lang().months(this,a)},D:function(){return this.date()},DDD:function(){return this.dayOfYear()},d:function(){return this.day()},dd:function(a){return this.lang().weekdaysMin(this,a)},ddd:function(a){return this.lang().weekdaysShort(this,a)},dddd:function(a){return this.lang().weekdays(this,a)},w:function(){return this.week()},W:function(){return this.isoWeek()},YY:function(){return l(this.year()%100,2)},YYYY:function(){return l(this.year(),4)},YYYYY:function(){return l(this.year(),5)},YYYYYY:function(){var a=this.year(),b=a>=0?"+":"-";return b+l(Math.abs(a),6)},gg:function(){return l(this.weekYear()%100,2)},gggg:function(){return l(this.weekYear(),4)},ggggg:function(){return l(this.weekYear(),5)},GG:function(){return l(this.isoWeekYear()%100,2)},GGGG:function(){return l(this.isoWeekYear(),4)},GGGGG:function(){return l(this.isoWeekYear(),5)},e:function(){return this.weekday()},E:function(){return this.isoWeekday()},a:function(){return this.lang().meridiem(this.hours(),this.minutes(),!0)},A:function(){return this.lang().meridiem(this.hours(),this.minutes(),!1)},H:function(){return this.hours()},h:function(){return this.hours()%12||12},m:function(){return this.minutes()},s:function(){return this.seconds()},S:function(){return t(this.milliseconds()/100)},SS:function(){return l(t(this.milliseconds()/10),2)},SSS:function(){return l(this.milliseconds(),3)},SSSS:function(){return l(this.milliseconds(),3)},Z:function(){var a=-this.zone(),b="+";return 0>a&&(a=-a,b="-"),b+l(t(a/60),2)+":"+l(t(a)%60,2)},ZZ:function(){var a=-this.zone(),b="+";return 0>a&&(a=-a,b="-"),b+l(t(a/60),2)+l(t(a)%60,2)},z:function(){return this.zoneAbbr()},zz:function(){return this.zoneName()},X:function(){return this.unix()},Q:function(){return this.quarter()}},dc=["months","monthsShort","weekdays","weekdaysShort","weekdaysMin"];ac.length;)kb=ac.pop(),cc[kb+"o"]=e(cc[kb],kb);for(;bc.length;)kb=bc.pop(),cc[kb+kb]=d(cc[kb],2);for(cc.DDDD=d(cc.DDD,3),i(f.prototype,{set:function(a){var b,c;for(c in a)b=a[c],"function"==typeof b?this[c]=b:this["_"+c]=b},_months:"January_February_March_April_May_June_July_August_September_October_November_December".split("_"),months:function(a){return this._months[a.month()]},_monthsShort:"Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec".split("_"),monthsShort:function(a){return this._monthsShort[a.month()]},monthsParse:function(a){var b,c,d;for(this._monthsParse||(this._monthsParse=[]),b=0;12>b;b++)if(this._monthsParse[b]||(c=ib.utc([2e3,b]),d="^"+this.months(c,"")+"|^"+this.monthsShort(c,""),this._monthsParse[b]=new RegExp(d.replace(".",""),"i")),this._monthsParse[b].test(a))return b},_weekdays:"Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),weekdays:function(a){return this._weekdays[a.day()]},_weekdaysShort:"Sun_Mon_Tue_Wed_Thu_Fri_Sat".split("_"),weekdaysShort:function(a){return this._weekdaysShort[a.day()]},_weekdaysMin:"Su_Mo_Tu_We_Th_Fr_Sa".split("_"),weekdaysMin:function(a){return this._weekdaysMin[a.day()]},weekdaysParse:function(a){var b,c,d;for(this._weekdaysParse||(this._weekdaysParse=[]),b=0;7>b;b++)if(this._weekdaysParse[b]||(c=ib([2e3,1]).day(b),d="^"+this.weekdays(c,"")+"|^"+this.weekdaysShort(c,"")+"|^"+this.weekdaysMin(c,""),this._weekdaysParse[b]=new RegExp(d.replace(".",""),"i")),this._weekdaysParse[b].test(a))return b},_longDateFormat:{LT:"h:mm A",L:"MM/DD/YYYY",LL:"MMMM D YYYY",LLL:"MMMM D YYYY LT",LLLL:"dddd, MMMM D YYYY LT"},longDateFormat:function(a){var b=this._longDateFormat[a];return!b&&this._longDateFormat[a.toUpperCase()]&&(b=this._longDateFormat[a.toUpperCase()].replace(/MMMM|MM|DD|dddd/g,function(a){return a.slice(1)}),this._longDateFormat[a]=b),b},isPM:function(a){return"p"===(a+"").toLowerCase().charAt(0)},_meridiemParse:/[ap]\.?m?\.?/i,meridiem:function(a,b,c){return a>11?c?"pm":"PM":c?"am":"AM"},_calendar:{sameDay:"[Today at] LT",nextDay:"[Tomorrow at] LT",nextWeek:"dddd [at] LT",lastDay:"[Yesterday at] LT",lastWeek:"[Last] dddd [at] LT",sameElse:"L"},calendar:function(a,b){var c=this._calendar[a];return"function"==typeof c?c.apply(b):c},_relativeTime:{future:"in %s",past:"%s ago",s:"a few seconds",m:"a minute",mm:"%d minutes",h:"an hour",hh:"%d hours",d:"a day",dd:"%d days",M:"a month",MM:"%d months",y:"a year",yy:"%d years"},relativeTime:function(a,b,c,d){var e=this._relativeTime[c];return"function"==typeof e?e(a,b,c,d):e.replace(/%d/i,a)},pastFuture:function(a,b){var c=this._relativeTime[a>0?"future":"past"];return"function"==typeof c?c(b):c.replace(/%s/i,b)},ordinal:function(a){return this._ordinal.replace("%d",a)},_ordinal:"%d",preparse:function(a){return a},postformat:function(a){return a},week:function(a){return $(a,this._week.dow,this._week.doy).week},_week:{dow:0,doy:6},_invalidDate:"Invalid date",invalidDate:function(){return this._invalidDate}}),ib=function(c,d,e,f){var g;return"boolean"==typeof e&&(f=e,e=a),g={},g._isAMomentObject=!0,g._i=c,g._f=d,g._l=e,g._strict=f,g._isUTC=!1,g._pf=b(),ab(g)},ib.suppressDeprecationWarnings=!1,ib.createFromInputFallback=c("moment construction falls back to js Date. This is discouraged and will be removed in upcoming major release. Please refer to https://github.com/moment/moment/issues/1407 for more info.",function(a){a._d=new Date(a._i)}),ib.utc=function(c,d,e,f){var g;return"boolean"==typeof e&&(f=e,e=a),g={},g._isAMomentObject=!0,g._useUTC=!0,g._isUTC=!0,g._l=e,g._i=c,g._f=d,g._strict=f,g._pf=b(),ab(g).utc()},ib.unix=function(a){return ib(1e3*a)},ib.duration=function(a,b){var c,d,e,f=a,g=null;return ib.isDuration(a)?f={ms:a._milliseconds,d:a._days,M:a._months}:"number"==typeof a?(f={},b?f[b]=a:f.milliseconds=a):(g=zb.exec(a))?(c="-"===g[1]?-1:1,f={y:0,d:t(g[qb])*c,h:t(g[rb])*c,m:t(g[sb])*c,s:t(g[tb])*c,ms:t(g[ub])*c}):(g=Ab.exec(a))&&(c="-"===g[1]?-1:1,e=function(a){var b=a&&parseFloat(a.replace(",","."));return(isNaN(b)?0:b)*c},f={y:e(g[2]),M:e(g[3]),d:e(g[4]),h:e(g[5]),m:e(g[6]),s:e(g[7]),w:e(g[8])}),d=new h(f),ib.isDuration(a)&&a.hasOwnProperty("_lang")&&(d._lang=a._lang),d},ib.version=lb,ib.defaultFormat=Ub,ib.momentProperties=wb,ib.updateOffset=function(){},ib.lang=function(a,b){var c;return a?(b?C(A(a),b):null===b?(D(a),a="en"):vb[a]||E(a),c=ib.duration.fn._lang=ib.fn._lang=E(a),c._abbr):ib.fn._lang._abbr},ib.langData=function(a){return a&&a._lang&&a._lang._abbr&&(a=a._lang._abbr),E(a)},ib.isMoment=function(a){return a instanceof g||null!=a&&a.hasOwnProperty("_isAMomentObject")},ib.isDuration=function(a){return a instanceof h},kb=dc.length-1;kb>=0;--kb)s(dc[kb]);ib.normalizeUnits=function(a){return q(a)},ib.invalid=function(a){var b=ib.utc(0/0);return null!=a?i(b._pf,a):b._pf.userInvalidated=!0,b},ib.parseZone=function(){return ib.apply(null,arguments).parseZone()},ib.parseTwoDigitYear=function(a){return t(a)+(t(a)>68?1900:2e3)},i(ib.fn=g.prototype,{clone:function(){return ib(this)},valueOf:function(){return+this._d+6e4*(this._offset||0)},unix:function(){return Math.floor(+this/1e3)},toString:function(){return this.clone().lang("en").format("ddd MMM DD YYYY HH:mm:ss [GMT]ZZ")},toDate:function(){return this._offset?new Date(+this):this._d},toISOString:function(){var a=ib(this).utc();return 0<a.year()&&a.year()<=9999?H(a,"YYYY-MM-DD[T]HH:mm:ss.SSS[Z]"):H(a,"YYYYYY-MM-DD[T]HH:mm:ss.SSS[Z]")},toArray:function(){var a=this;return[a.year(),a.month(),a.date(),a.hours(),a.minutes(),a.seconds(),a.milliseconds()]},isValid:function(){return z(this)},isDSTShifted:function(){return this._a?this.isValid()&&p(this._a,(this._isUTC?ib.utc(this._a):ib(this._a)).toArray())>0:!1},parsingFlags:function(){return i({},this._pf)},invalidAt:function(){return this._pf.overflow},utc:function(){return this.zone(0)},local:function(){return this.zone(0),this._isUTC=!1,this},format:function(a){var b=H(this,a||ib.defaultFormat);return this.lang().postformat(b)},add:function(a,b){var c;return c="string"==typeof a?ib.duration(+b,a):ib.duration(a,b),m(this,c,1),this},subtract:function(a,b){var c;return c="string"==typeof a?ib.duration(+b,a):ib.duration(a,b),m(this,c,-1),this},diff:function(a,b,c){var d,e,f=B(a,this),g=6e4*(this.zone()-f.zone());return b=q(b),"year"===b||"month"===b?(d=432e5*(this.daysInMonth()+f.daysInMonth()),e=12*(this.year()-f.year())+(this.month()-f.month()),e+=(this-ib(this).startOf("month")-(f-ib(f).startOf("month")))/d,e-=6e4*(this.zone()-ib(this).startOf("month").zone()-(f.zone()-ib(f).startOf("month").zone()))/d,"year"===b&&(e/=12)):(d=this-f,e="second"===b?d/1e3:"minute"===b?d/6e4:"hour"===b?d/36e5:"day"===b?(d-g)/864e5:"week"===b?(d-g)/6048e5:d),c?e:k(e)},from:function(a,b){return ib.duration(this.diff(a)).lang(this.lang()._abbr).humanize(!b)},fromNow:function(a){return this.from(ib(),a)},calendar:function(){var a=B(ib(),this).startOf("day"),b=this.diff(a,"days",!0),c=-6>b?"sameElse":-1>b?"lastWeek":0>b?"lastDay":1>b?"sameDay":2>b?"nextDay":7>b?"nextWeek":"sameElse";return this.format(this.lang().calendar(c,this))},isLeapYear:function(){return x(this.year())},isDST:function(){return this.zone()<this.clone().month(0).zone()||this.zone()<this.clone().month(5).zone()},day:function(a){var b=this._isUTC?this._d.getUTCDay():this._d.getDay();return null!=a?(a=X(a,this.lang()),this.add({d:a-b})):b},month:eb("Month",!0),startOf:function(a){switch(a=q(a)){case"year":this.month(0);case"quarter":case"month":this.date(1);case"week":case"isoWeek":case"day":this.hours(0);case"hour":this.minutes(0);case"minute":this.seconds(0);case"second":this.milliseconds(0)}return"week"===a?this.weekday(0):"isoWeek"===a&&this.isoWeekday(1),"quarter"===a&&this.month(3*Math.floor(this.month()/3)),this},endOf:function(a){return a=q(a),this.startOf(a).add("isoWeek"===a?"week":a,1).subtract("ms",1)},isAfter:function(a,b){return b="undefined"!=typeof b?b:"millisecond",+this.clone().startOf(b)>+ib(a).startOf(b)},isBefore:function(a,b){return b="undefined"!=typeof b?b:"millisecond",+this.clone().startOf(b)<+ib(a).startOf(b)},isSame:function(a,b){return b=b||"ms",+this.clone().startOf(b)===+B(a,this).startOf(b)},min:function(a){return a=ib.apply(null,arguments),this>a?this:a},max:function(a){return a=ib.apply(null,arguments),a>this?this:a},zone:function(a,b){var c=this._offset||0;return null==a?this._isUTC?c:this._d.getTimezoneOffset():("string"==typeof a&&(a=K(a)),Math.abs(a)<16&&(a=60*a),this._offset=a,this._isUTC=!0,c!==a&&(!b||this._changeInProgress?m(this,ib.duration(c-a,"m"),1,!1):this._changeInProgress||(this._changeInProgress=!0,ib.updateOffset(this,!0),this._changeInProgress=null)),this)},zoneAbbr:function(){return this._isUTC?"UTC":""},zoneName:function(){return this._isUTC?"Coordinated Universal Time":""},parseZone:function(){return this._tzm?this.zone(this._tzm):"string"==typeof this._i&&this.zone(this._i),this},hasAlignedHourOffset:function(a){return a=a?ib(a).zone():0,(this.zone()-a)%60===0},daysInMonth:function(){return u(this.year(),this.month())},dayOfYear:function(a){var b=nb((ib(this).startOf("day")-ib(this).startOf("year"))/864e5)+1;return null==a?b:this.add("d",a-b)},quarter:function(a){return null==a?Math.ceil((this.month()+1)/3):this.month(3*(a-1)+this.month()%3)},weekYear:function(a){var b=$(this,this.lang()._week.dow,this.lang()._week.doy).year;return null==a?b:this.add("y",a-b)},isoWeekYear:function(a){var b=$(this,1,4).year;return null==a?b:this.add("y",a-b)},week:function(a){var b=this.lang().week(this);return null==a?b:this.add("d",7*(a-b))},isoWeek:function(a){var b=$(this,1,4).week;return null==a?b:this.add("d",7*(a-b))},weekday:function(a){var b=(this.day()+7-this.lang()._week.dow)%7;return null==a?b:this.add("d",a-b)},isoWeekday:function(a){return null==a?this.day()||7:this.day(this.day()%7?a:a-7)},isoWeeksInYear:function(){return v(this.year(),1,4)},weeksInYear:function(){var a=this._lang._week;return v(this.year(),a.dow,a.doy)},get:function(a){return a=q(a),this[a]()},set:function(a,b){return a=q(a),"function"==typeof this[a]&&this[a](b),this},lang:function(b){return b===a?this._lang:(this._lang=E(b),this)}}),ib.fn.millisecond=ib.fn.milliseconds=eb("Milliseconds",!1),ib.fn.second=ib.fn.seconds=eb("Seconds",!1),ib.fn.minute=ib.fn.minutes=eb("Minutes",!1),ib.fn.hour=ib.fn.hours=eb("Hours",!0),ib.fn.date=eb("Date",!0),ib.fn.dates=c("dates accessor is deprecated. Use date instead.",eb("Date",!0)),ib.fn.year=eb("FullYear",!0),ib.fn.years=c("years accessor is deprecated. Use year instead.",eb("FullYear",!0)),ib.fn.days=ib.fn.day,ib.fn.months=ib.fn.month,ib.fn.weeks=ib.fn.week,ib.fn.isoWeeks=ib.fn.isoWeek,ib.fn.quarters=ib.fn.quarter,ib.fn.toJSON=ib.fn.toISOString,i(ib.duration.fn=h.prototype,{_bubble:function(){var a,b,c,d,e=this._milliseconds,f=this._days,g=this._months,h=this._data;h.milliseconds=e%1e3,a=k(e/1e3),h.seconds=a%60,b=k(a/60),h.minutes=b%60,c=k(b/60),h.hours=c%24,f+=k(c/24),h.days=f%30,g+=k(f/30),h.months=g%12,d=k(g/12),h.years=d},weeks:function(){return k(this.days()/7)},valueOf:function(){return this._milliseconds+864e5*this._days+this._months%12*2592e6+31536e6*t(this._months/12)},humanize:function(a){var b=+this,c=Z(b,!a,this.lang());return a&&(c=this.lang().pastFuture(b,c)),this.lang().postformat(c)},add:function(a,b){var c=ib.duration(a,b);return this._milliseconds+=c._milliseconds,this._days+=c._days,this._months+=c._months,this._bubble(),this},subtract:function(a,b){var c=ib.duration(a,b);return this._milliseconds-=c._milliseconds,this._days-=c._days,this._months-=c._months,this._bubble(),this},get:function(a){return a=q(a),this[a.toLowerCase()+"s"]()},as:function(a){return a=q(a),this["as"+a.charAt(0).toUpperCase()+a.slice(1)+"s"]()},lang:ib.fn.lang,toIsoString:function(){var a=Math.abs(this.years()),b=Math.abs(this.months()),c=Math.abs(this.days()),d=Math.abs(this.hours()),e=Math.abs(this.minutes()),f=Math.abs(this.seconds()+this.milliseconds()/1e3);return this.asSeconds()?(this.asSeconds()<0?"-":"")+"P"+(a?a+"Y":"")+(b?b+"M":"")+(c?c+"D":"")+(d||e||f?"T":"")+(d?d+"H":"")+(e?e+"M":"")+(f?f+"S":""):"P0D"}});for(kb in Yb)Yb.hasOwnProperty(kb)&&(gb(kb,Yb[kb]),fb(kb.toLowerCase()));gb("Weeks",6048e5),ib.duration.fn.asMonths=function(){return(+this-31536e6*this.years())/2592e6+12*this.years()},ib.lang("en",{ordinal:function(a){var b=a%10,c=1===t(a%100/10)?"th":1===b?"st":2===b?"nd":3===b?"rd":"th";return a+c}}),xb?module.exports=ib:"function"==typeof define&&define.amd?(define("moment",function(a,b,c){return c.config&&c.config()&&c.config().noGlobal===!0&&(mb.moment=jb),ib}),hb(!0)):hb()}).call(this);

/* ========================================================================
 * MockJax.js v1.5.3
 * https://github.com/appendto/jquery-mockjax
 * ======================================================================== */
!function(a){function g(b){void 0==window.DOMParser&&window.ActiveXObject&&(DOMParser=function(){},DOMParser.prototype.parseFromString=function(a){var b=new ActiveXObject("Microsoft.XMLDOM");return b.async="false",b.loadXML(a),b});try{var c=(new DOMParser).parseFromString(b,"text/xml");if(!a.isXMLDoc(c))throw"Unable to parse XML";var d=a("parsererror",c);if(1==d.length)throw"Error: "+a(c).text();return c}catch(e){var f=void 0==e.name?e:e.name+": "+e.message;return a(document).trigger("xmlParseError",[f]),void 0}}function h(b,c,d){(b.context?a(b.context):a.event).trigger(c,d)}function i(b,c){var d=!0;return"string"==typeof c?a.isFunction(b.test)?b.test(c):b==c:(a.each(b,function(e){return void 0===c[e]?d=!1:(d="object"==typeof c[e]&&null!==c[e]?d&&i(b[e],c[e]):b[e]&&a.isFunction(b[e].test)?d&&b[e].test(c[e]):d&&b[e]==c[e],void 0)}),d)}function j(b,c){return b[c]===a.mockjaxSettings[c]}function k(b,c){if(a.isFunction(b))return b(c);if(a.isFunction(b.url.test)){if(!b.url.test(c.url))return null}else{var d=b.url.indexOf("*");if(b.url!==c.url&&-1===d||!new RegExp(b.url.replace(/[-[\]{}()+?.,\\^$|#\s]/g,"\\$&").replace(/\*/g,".+")).test(c.url))return null}return b.data&&c.data&&!i(b.data,c.data)?null:b&&b.type&&b.type.toLowerCase()!=c.type.toLowerCase()?null:b}function l(c,d,e){var f=function(b){return function(){return function(){var b;this.status=c.status,this.statusText=c.statusText,this.readyState=4,a.isFunction(c.response)&&c.response(e),"json"==d.dataType&&"object"==typeof c.responseText?this.responseText=JSON.stringify(c.responseText):"xml"==d.dataType?"string"==typeof c.responseXML?(this.responseXML=g(c.responseXML),this.responseText=c.responseXML):this.responseXML=c.responseXML:this.responseText=c.responseText,("number"==typeof c.status||"string"==typeof c.status)&&(this.status=c.status),"string"==typeof c.statusText&&(this.statusText=c.statusText),b=this.onreadystatechange||this.onload,a.isFunction(b)?(c.isTimeout&&(this.status=-1),b.call(this,c.isTimeout?"timeout":void 0)):c.isTimeout&&(this.status=-1)}.apply(b)}}(this);c.proxy?b({global:!1,url:c.proxy,type:c.proxyType,data:c.data,dataType:"script"===d.dataType?"text/plain":d.dataType,complete:function(a){c.responseXML=a.responseXML,c.responseText=a.responseText,j(c,"status")&&(c.status=a.status),j(c,"statusText")&&(c.statusText=a.statusText),this.responseTimer=setTimeout(f,c.responseTime||0)}}):d.async===!1?f():this.responseTimer=setTimeout(f,c.responseTime||50)}function m(b,c,d,e){return b=a.extend(!0,{},a.mockjaxSettings,b),"undefined"==typeof b.headers&&(b.headers={}),b.contentType&&(b.headers["content-type"]=b.contentType),{status:b.status,statusText:b.statusText,readyState:1,open:function(){},send:function(){e.fired=!0,l.call(this,b,c,d)},abort:function(){clearTimeout(this.responseTimer)},setRequestHeader:function(a,c){b.headers[a]=c},getResponseHeader:function(a){return b.headers&&b.headers[a]?b.headers[a]:"last-modified"==a.toLowerCase()?b.lastModified||(new Date).toString():"etag"==a.toLowerCase()?b.etag||"":"content-type"==a.toLowerCase()?b.contentType||"text/plain":void 0},getAllResponseHeaders:function(){var c="";return a.each(b.headers,function(a,b){c+=a+": "+b+"\n"}),c}}}function n(a,b,c){if(o(a),a.dataType="json",a.data&&e.test(a.data)||e.test(a.url)){q(a,b,c);var d=/^(\w+:)?\/\/([^\/?#]+)/,f=d.exec(a.url),g=f&&(f[1]&&f[1]!==location.protocol||f[2]!==location.host);if(a.dataType="script","GET"===a.type.toUpperCase()&&g){var h=p(a,b,c);return h?h:!0}}return null}function o(a){"GET"===a.type.toUpperCase()?e.test(a.url)||(a.url+=(/\?/.test(a.url)?"&":"?")+(a.jsonp||"callback")+"=?"):a.data&&e.test(a.data)||(a.data=(a.data?a.data+"&":"")+(a.jsonp||"callback")+"=?")}function p(b,c,d){var e=d&&d.context||b,f=null;return c.response&&a.isFunction(c.response)?c.response(d):"object"==typeof c.responseText?a.globalEval("("+JSON.stringify(c.responseText)+")"):a.globalEval("("+c.responseText+")"),r(b,e,c),s(b,e,c),a.Deferred&&(f=new a.Deferred,"object"==typeof c.responseText?f.resolveWith(e,[c.responseText]):f.resolveWith(e,[a.parseJSON(c.responseText)])),f}function q(a,b,c){var d=c&&c.context||a,g=a.jsonpCallback||"jsonp"+f++;a.data&&(a.data=(a.data+"").replace(e,"="+g+"$1")),a.url=a.url.replace(e,"="+g+"$1"),window[g]=window[g]||function(c){data=c,r(a,d,b),s(a,d,b),window[g]=void 0;try{delete window[g]}catch(e){}head&&head.removeChild(script)}}function r(a,b,c){a.success&&a.success.call(b,c.responseText||"",status,{}),a.global&&h(a,"ajaxSuccess",[{},a])}function s(b,c){b.complete&&b.complete.call(c,{},status),b.global&&h("ajaxComplete",[{},b]),b.global&&!--a.active&&a.event.trigger("ajaxStop")}function t(e,f){var g,h,i;"object"==typeof e?(f=e,e=void 0):f.url=e,h=a.extend(!0,{},a.ajaxSettings,f);for(var j=0;j<c.length;j++)if(c[j]&&(i=k(c[j],h)))return d.push(h),a.mockjaxSettings.log(i,h),"jsonp"===h.dataType&&(g=n(h,i,f))?g:(i.cache=h.cache,i.timeout=h.timeout,i.global=h.global,u(i,f),function(c,d,e,f){g=b.call(a,a.extend(!0,{},e,{xhr:function(){return m(c,d,e,f)}}))}(i,h,f,c[j]),g);if(a.mockjaxSettings.throwUnmocked===!0)throw"AJAX not mocked: "+f.url;return b.apply(a,[f])}function u(a,b){if(a.url instanceof RegExp&&a.hasOwnProperty("urlParams")){var c=a.url.exec(b.url);if(1!==c.length){c.shift();var d=0,e=c.length,f=a.urlParams.length,g=Math.min(e,f),h={};for(d;g>d;d++){var i=a.urlParams[d];h[i]=c[d]}b.urlParams=h}}}var b=a.ajax,c=[],d=[],e=/=\?(&|$)/,f=(new Date).getTime();a.extend({ajax:t}),a.mockjaxSettings={log:function(b,c){if(b.logging!==!1&&("undefined"!=typeof b.logging||a.mockjaxSettings.logging!==!1)&&window.console&&console.log){var d="MOCK "+c.type.toUpperCase()+": "+c.url,e=a.extend({},c);if("function"==typeof console.log)console.log(d,e);else try{console.log(d+" "+JSON.stringify(e))}catch(f){console.log(d)}}},logging:!0,status:200,statusText:"OK",responseTime:500,isTimeout:!1,throwUnmocked:!1,contentType:"text/plain",response:"",responseText:"",responseXML:"",proxy:"",proxyType:"GET",lastModified:null,etag:"",headers:{etag:"IJF@H#@923uf8023hFO@I#H#","content-type":"text/plain"}},a.mockjax=function(a){var b=c.length;return c[b]=a,b},a.mockjaxClear=function(a){1==arguments.length?c[a]=null:c=[],d=[]},a.mockjax.handler=function(a){return 1==arguments.length?c[a]:void 0},a.mockjax.mockedAjaxCalls=function(){return d}}(jQuery);

/* ========================================================================
 * BEGIN CORE SCRIPT
 *
 * IMPORTANT : This script will utilize all the above script. All this
 * template behavior and function depends on the above script
 * ======================================================================== */
;(function ( $, window, document, undefined ) {
//$(function () {

    // Create the defaults once
    // ================================
    var pluginName  = "Core",
        isMinimize  = false,
        isScreenlg  = false,
        isScreenmd  = false,
        isScreensm  = false,
        isScreenxs  = false,
        defaults    = {
            console: false,
            loader: true,
            eventPrefix: "fa",
            breakpoint: {
                "lg": 1200,
                "md": 992,
                "sm": 768,
                "xs": 480
            }
        };

    // Core MAIN function
    // ================================
    function MAIN(element, options) {
        this.element    = element;
        this.settings   = $.extend({}, defaults, options);
        this._defaults  = defaults;
        this._name      = pluginName;
        this.init();
    }

    // Core MAIN function prototype
    // ================================
    MAIN.prototype = {
        init: function () {
            this.MISC.Init();
            this.PLUGINS();
            this.VIEWPORTWATCH();
            this.WINLOADER();
        },

        // Helper
        // ================================
        HELPER: {
            // @Helper: Console
            // Per call
            // ================================
            Console: function (cevent) {
                if(settings.console) {
                    $(element).on(cevent, function (e, o) {
                        console.log("----- "+cevent+" -----");
                        console.log(o.element);
                    });
                }
            }
        },

        // Window Loader
        // ================================
        WINLOADER: function () {
            // access MAIN variable
            element     = this.element;
            settings    = this.settings;

            if(settings.loader) {
                // start nprogress bar
                NProgress.start();

                // remove the loading class on window loaded
                $(window).load(function () {
                    // start nprogress bar
                    NProgress.done();
                });
            }
        },

        // Viewport watcher
        // ================================
        VIEWPORTWATCH: function () {
            // access MAIN variable
            element     = this.element;
            settings    = this.settings;

            Response.action(function () {
                // screen-lg
                if(Response.band(settings.breakpoint.lg)) {
                    isScreenlg  = true;
                    isScreenmd  = false;
                    isScreensm  = false;
                    isScreenxs  = false;

                    // reset sidebar minimize
                    isMinimize = !!$(element).hasClass("sidebar-minimized");
                }

                // screen-md
                if(Response.band(settings.breakpoint.md, settings.breakpoint.lg-1)) {
                    isScreenlg  = false;
                    isScreenmd  = true;
                    isScreensm  = false;
                    isScreenxs  = false;

                    // reset sidebar minimize
                    isMinimize = !!$(element).hasClass("sidebar-minimized");
                }

                // screen-sm
                if(Response.band(settings.breakpoint.sm, settings.breakpoint.md-1)) {
                    isScreenlg  = false;
                    isScreenmd  = false;
                    isScreensm  = true;
                    isScreenxs  = false;

                    // reset sidebar minimize
                    isMinimize = false;
                }

                // screen-xs
                if(Response.band(0, settings.breakpoint.xs)) {
                    isScreenlg  = false;
                    isScreenmd  = false;
                    isScreensm  = false;
                    isScreenxs  = true;

                    // reset sidebar minimize
                    isMinimize = false;
                }
            });
        },

        // Misc
        // ================================
        MISC: {
            // @MISC: Init
            Init: function () {
                this.ConsoleFix();
                this.Scrollbar(".slimscroll");
                this.Fastclick();
                this.Unveil();
                this.BsTooltip();
                this.BsPopover();
                this.Stellar();
                this.InputPlaceholder();
            },

            // @MISC: ConsoleFix
            // Per call
            // ================================
            ConsoleFix: function () {
                var method,
                    noop = function () {},
                    methods = [
                        "assert", "clear", "count", "debug", "dir", "dirxml", "error",
                        "exception", "group", "groupCollapsed", "groupEnd", "info", "log",
                        "markTimeline", "profile", "profileEnd", "table", "time", "timeEnd",
                        "timeStamp", "trace", "warn"
                    ],
                    length = methods.length,
                    console = (window.console = window.console || {});

                while (length--) {
                    method = methods[length];

                    // Only stub undefined methods.
                    if (!console[method]) {
                        console[method] = noop;
                    }
                }
            },

            // @MISC: Scrollbar
            // Per call
            // ================================
            Scrollbar: function (elem) {
                $(".no-touch "+elem).each(function (index, value) {
                    $(value).slimScroll({
                        size: "6px",
                        distance: "0px",
                        wrapperClass: $(value).data("wrapper") || "scroll-wrapper",
                        railClass: "scroll-rail",
                        barClass: "scroll-bar",
                        wheelStep: 10,
                        railVisible: true,
                        alwaysVisible: false
                    });
                });
            },

            // @MISC: Fastclick
            // Per call
            // ================================
            Fastclick: function () {
                FastClick.attach(document.body);
            },

            // @MISC: Unveil - lazyload images
            // Per call
            // ================================
            Unveil: function () {
                $("[data-toggle~=unveil]").unveil(200, function () {
                    $(this).load(function () {
                        $(this).addClass("unveiled");
                    });
                });
            },

            // @MISC: BsTooltip - Bootstrap tooltip
            // Per call
            // ================================
            BsTooltip: function () {
                $("[data-toggle~=tooltip]").tooltip();
            },

            // @MISC: BsPopover - Bootstrap popover
            // Per call
            // ================================
            BsPopover: function () {
                $("[data-toggle~=popover]").popover();
            },

            // @MISC: IE9 input placeholder support
            // Per call
            // ================================
            Stellar: function () {
                $(window).stellar({
                    horizontalScrolling: false
                });
            },

            // @MISC: Stellar Background
            // Per call
            // ================================
            InputPlaceholder: function () {
                $("input, textarea").placeholder();
            }
        },

        // Custom Mini Plugins
        // ================================
        PLUGINS: function () {
            // access MAIN variable
            element     = this.element;
            settings    = this.settings;

            // @PLUGIN: ToTop
            // Self invoking
            // ================================
            (function () {
                var toggler     = "[data-toggle~=totop]";

                // toggler
                $(element).on("click", toggler, function (e) {
                    $("html, body").animate({
                        scrollTop: 0
                    }, 200);

                    e.preventDefault();
                });
            })();

            // @PLUGIN: WayPoints
            // Self invoking
            // TODO: add custom event
            // ================================
            (function () {
                var toggler     = "[data-toggle~=waypoints]";

                $(toggler).each(function () {
                    var wayShowAnimation,
                        wayHideAnimation,
                        wayOffset,
                        wayMarker,
                        triggerOnce;

                    // check if marker is define or not
                    !!$(this).data("marker") ? wayMarker = $(this).data("marker") : wayMarker = this;

                    // check if offset is define or not
                    !!$(this).data("offset") ? wayOffset = $(this).data("offset") : wayOffset = "80%";

                    // check if show animation is define or not
                    !!$(this).data("showanim") ? wayShowAnimation = $(this).data("showanim") : wayShowAnimation = "fadeIn";

                    // check if hide animation is define or not
                    !!$(this).data("hideanim") ? wayHideAnimation = $(this).data("hideanim") : wayHideAnimation = false;

                    // check if trigger once is define or not
                    !!$(this).data("trigger-once") ? triggerOnce = $(this).data("trigger-once") : triggerOnce = false;

                    // waypoints core
                    $(wayMarker).waypoint(function (direction) {
                        if(direction === "down") {
                            $(this)
                                .removeClass(wayHideAnimation + " animated")
                                .addClass(wayShowAnimation + " animating")
                                .on('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                                    $(this).removeClass("animating").addClass("animated").removeClass(wayShowAnimation);;
                                });
                        }
                        if( (direction === "up") && (wayHideAnimation !== false)) {
                            $(this)
                                .removeClass(wayShowAnimation + " animated")
                                .addClass(wayHideAnimation + " animating")
                                .on('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                                    $(this).removeClass("animating").removeClass("animated").removeClass(wayHideAnimation);
                                });
                        }
                    }, {
                        offset: wayOffset,
                        triggerOnce: triggerOnce,
                        continuous: true
                    });
                });
            })();

            // @PLUGIN: SelectRow
            // Self invoking
            // ================================
            (function () {
                var contextual,
                    toggler     = "[data-toggle~=selectrow]",
                    target      = $(toggler).data("target");

                // check on DOM ready
                $(toggler).each(function () {
                    if($(this).is(":checked")) {
                        selectrow(this, "checked");
                    }
                });

                // clicker
                $(document).on("change", toggler, function () {
                    // checked / unchecked
                    if($(this).is(":checked")) {
                        selectrow(this, "checked");
                    } else {
                        selectrow(this, "unchecked");
                    }
                });

                // Core SelectRow function
                // state: checked/unchecked
                function selectrow ($this, state) {
                    // contextual
                    !!$($this).data("contextual") ? contextual = $($this).data("contextual") : contextual = "active";

                    if(state === "checked") {
                        // add contextual class
                        $($this).parents(target).addClass(contextual);

                        // publish event
                        $(element).trigger(settings.eventPrefix+".selectrow.selected", { "element": $($this).parents(target) });
                    } else {
                        // remove contextual class
                        $($this).parents(target).removeClass(contextual);

                        // publish event
                        $(element).trigger(settings.eventPrefix+".selectrow.unselected", { "element": $($this).parents(target) });
                    }
                }

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".selectrow.selected");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".selectrow.unselected");
            })();

            // @PLUGIN: CheckAll
            // Self invoking
            // ================================
            (function () {
                var contextual,
                    toggler     = "[data-toggle~=checkall]";

                // check on DOM ready
                $(toggler).each(function () {
                    if($(this).is(":checked")) {
                        checked();
                    }
                });

                // clicker
                $(document).on("change", toggler, function () {
                    var target      = $(this).data("target");

                    // checked / unchecked
                    if($(this).is(":checked")) {
                        checked(target);
                    } else {
                        unchecked(target);
                    }
                });

                // Core CheckAll function
                function checked (target) {
                    // find checkbox
                    $(target).find("input[type=checkbox]").each(function () {
                        // select row
                        if($(this).data("toggle") === "selectrow") {
                            // trigger change event
                            if(!$(this).is(":checked")) {
                                $(this)
                                    .prop("checked", true)
                                    .trigger("change");
                            }
                        }
                    });

                    // publish event
                    $(element).trigger(settings.eventPrefix+".checkall.checked", { "element": $(target) });
                }

                function unchecked (target) {
                    // find checkbox
                    $(target).find("input[type=checkbox]").each(function () {
                        // select row
                        if($(this).data("toggle") === "selectrow") {
                            // trigger change event
                            if($(this).is(":checked")) {
                                $(this)
                                    .prop("checked", false)
                                    .trigger("change");
                            }
                        }
                    });

                    // publish event
                    $(element).trigger(settings.eventPrefix+".checkall.unchecked", { "element": $(target) });
                }

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".checkall.checked");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".checkall.unchecked");
            })();

            // @PLUGIN: Panel Refresh
            // Self invoking
            // ================================
            (function () {
                var isDemo          = false,
                    indicatorClass  = "indicator",
                    toggler         = "[data-toggle~=panelrefresh]";

                // clicker
                $(element).on("click", toggler, function (e) {
                    // find panel element
                    var panel       = $(this).parents(".panel"),
                        indicator   = panel.find("."+indicatorClass);

                    // check if demo or not
                    !!$(this).hasClass("demo") ? isDemo = true : isDemo = false;

                    // check indicator
                    if(indicator.length !== 0) {
                        indicator.addClass("show");

                        // check if demo or not
                        if(isDemo) {
                            setTimeout(function () {
                                indicator.removeClass("show");
                            }, 2000);
                        }

                        // publish event
                        $(element).trigger(settings.eventPrefix+".panelrefresh.refresh", { "element": $(panel) });
                    } else {
                        $.error("There is no `indicator` element inside this panel.");
                    }

                    // prevent default
                    e.preventDefault();
                });

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".panelrefresh.refresh");
            })();

            // @PLUGIN: Panel Collapse
            // Self invoking
            // ================================
            (function () {
                var toggler   = "[data-toggle~=panelcollapse]";

                // clicker
                $(element).on("click", toggler, function (e) {
                    // find panel element
                    var panel   = $(this).parents(".panel"),
                        target  = panel.children(".panel-collapse"),
                        height  = target.height();

                    // error handling
                    if(target.length === 0) {
                        $.error("collapsable element need to be wrap inside '.panel-collapse'");
                    }

                    // collapse the element
                    $(target).hasClass("out") ? close(this) : open(this);

                    function open (toggler) {
                        $(toggler).removeClass("down").addClass("up");
                        $(target)
                            .removeClass("pull").addClass("pulling")
                            .css("height", "0px")
                            .transition({ height: height }, function() {
                                $(this).removeClass("pulling").addClass("pull out");
                                $(this).css({ "height": "" });
                            });

                        // publish event
                        $(element).trigger(settings.eventPrefix+".panelcollapse.open", { "element": $(panel) });
                    }
                    function close (toggler) {
                        $(toggler).removeClass("up").addClass("down");
                        $(target)
                            .removeClass("pull out").addClass("pulling")
                            .css("height", height)
                            .transition({ height: "0px" }, function() {
                                $(this).removeClass("pulling").addClass("pull");
                                $(this).css({ "height": "" });
                            });

                        // publish event
                        $(element).trigger(settings.eventPrefix+".panelcollapse.close", { "element": $(panel) });
                    }

                    // prevent default
                    e.preventDefault();
                });

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".panelcollapse.open");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".panelcollapse.close");
            })();

            // @PLUGIN: Panel Remove
            // Self invoking
            // ================================
            (function () {
                var panel,
                    parent,
                    handler   = "[data-toggle~=panelremove]";

                // clicker
                $(element).on("click", handler, function (e) {
                    // find panel element
                    panel   = $(this).parents(".panel");
                    parent  = $(this).data("parent");

                    // remove panel
                    panel.transition({ scale: 0 }, function () {
                        //remove
                        if(parent) {
                            $(this).parents(parent).remove();
                        } else {
                            $(this).remove();
                        }

                        // publish event
                        $(element).trigger(settings.eventPrefix+".panelcollapse.remove", { "element": $(panel) });
                    });

                    // prevent default
                    e.preventDefault();
                });

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".panelremove.remove");
            })();

            // @PLUGIN: SidebarMinimize
            // Self invoking
            // ================================
            (function () {
                // define variable
                var minimizeHandler   = "[data-toggle~=minimize]";

                // core minimize function
                function toggleMinimize (e) {
                    // toggle class
                    if($(element).hasClass("sidebar-minimized")) {
                        isMinimize = false;
                        $(element).removeClass("sidebar-minimized");
                        $(this).removeClass("minimized");

                        // publish event
                        $(element).trigger(settings.eventPrefix+".sidebar.maximize", { "element": $(element) });
                    } else {
                        isMinimize = true;
                        $(element).addClass("sidebar-minimized");
                        $(this).addClass("minimized");

                        // publish event
                        $(element).trigger(settings.eventPrefix+".sidebar.minimize", { "element": $(element) });
                    }

                    // prevent default
                    e.preventDefault();
                }

                $(element).on("click", minimizeHandler, toggleMinimize);

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".sidebar.minimize");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".sidebar.maximize");
            })();

            // @PLUGIN: SidebarMenu
            // Self invoking
            // utilize bootstrap collapse
            // TODO: add function custom event
            // ================================
            (function () {
                // define variable
                var menuHandler     = "[data-toggle~=menu]",
                    submenuHandler  = "[data-toggle~=submenu]";
                // core toggle collapse
                function handleClick (e) {
                    var $this       = $(this),
                        parent      = $this.data("parent"),
                        target      = $this.data("target");

                    // default click event handler
                    if(e.type === "click") {
                        // toggle hide and show
                        if($(target).hasClass("in")) {
                            // hide the submenu
                            $(target).collapse("hide");
                            $this.parent().removeClass("open");
                        } else {
                            // hide other showed target if parent is defined
                            if(!!parent) {
                                $(parent+" .in").each(function () {
                                    $(this).collapse("hide");
                                    $(this).parent().removeClass("open");
                                });
                            }

                            // show the submenu
                            $(target).collapse("show");
                            $this.parent().addClass("open");
                        }
                    }

                    // run only on tablet view and sidebar-menu collapse
                    if((isScreensm) || (isMinimize)) {
                        // if have target
                        if(!!target === true) {
                            // touch devices
                            if($(element).hasClass("touch")) {
                                // click event handler
                                if(e.type === "click") {
                                    if($this.parent().hasClass("hover")) {
                                        // remove hover class and clear the `top` css attr val
                                        $this.parent().removeClass("hover");
                                        $(target).css("top", "");
                                    } else {
                                        // remove other opened submenus
                                        if(!!parent) {
                                            $(parent+" .hover").each(function (index, elem) {
                                                $(elem).removeClass("hover");
                                            });
                                        }

                                        // add hover class and calculate submenu offset
                                        $this.parent().addClass("hover");
                                        if($(target)[0].getBoundingClientRect().bottom >= Response.viewportH()) {
                                            $(target).css("top", "-"+($(target)[0].getBoundingClientRect().bottom-Response.viewportH()+2)+"px");
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // core preserveSubmenu function
                function handleHover (e) {
                    var $this       = $(this),
                        parent      = $this.children(submenuHandler).data("parent"),
                        target      = $this.children(submenuHandler).data("target");

                    // run only on tablet view and sidebar-menu collapse
                    if((isScreensm) || (isMinimize)) {
                        // if have target
                        if(!!target === true) {
                            // touch devices
                            if(!$(element).hasClass("touch")) {

                                // mouseenter event handler
                                if(e.type === "mouseenter") {
                                    // add hover class and calculate submenu offset
                                    $this.addClass("hover");
                                    if($(target)[0].getBoundingClientRect().bottom >= Response.viewportH()) {
                                        $(target).css("top", "-"+($(target)[0].getBoundingClientRect().bottom-Response.viewportH()+2)+"px");
                                    }
                                }

                                // mouseleave event handler
                                if(e.type === "mouseleave") {
                                    // remove hover class and clear the `top` css attr val
                                    $this.removeClass("hover");
                                    $(target).css("top", "");
                                }

                            }
                        }
                    }
                }

                $(document)
                    .on("click", submenuHandler, handleClick)
                    .on("mouseenter mouseleave", menuHandler+" > li", handleHover);
            })();

            // @PLUGIN: SideBar
            // Self invoking
            // ================================
            (function () {
                var direction,
                    sidebar,
                    toggler      = "[data-toggle~=sidebar]",
                    openClass    = "sidebar-open";

                // sidebar toggler
                function toggle () {
                    // get direction
                    direction = $(this).data("direction");
                    direction === "ltr" ? sidebar = ".sidebar-left" : sidebar = ".sidebar-right";

                    // trigger error if `data-direction` is not set
                    if((direction === false)||(direction === "")) {
                        $.error("missing `data-direction` value (ltr or rtl)");
                    }

                    // open/close sidebar
                    !$(element).hasClass(openClass+"-"+direction) ? open() : close();
                    return false;
                }

                function open () {
                    $(element).addClass(openClass+"-"+direction);
                    $(element).trigger(settings.eventPrefix+".sidebar.open", { "element": $(sidebar) });
                }
                function close () {
                    if ($(element).hasClass(openClass+"-"+direction)) {
                        $(element).removeClass(openClass+"-"+direction);
                        $(element).trigger(settings.eventPrefix+".sidebar.close", { "element": $(sidebar) });
                    }
                }

                $(document)
                    //.on("click", close)
                    .on("click", ".sidebar,"+ toggler, function (e) { e.stopPropagation(); })
                    .on("click", toggler, toggle);

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".sidebar.open");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".sidebar.close");
            })();

            // @PLUGIN: FormAjax
            // Self invoking
            // ================================
            (function () {
                // define variable
                var handler         = "[data-toggle~=formajax]",
                    pluginErrors    = [];

                // core ajaxForm function
                function ajaxForm () {
                    var that        = this,
                        $form       = $(this).parents(handler),
                        options     = $form.data("options");

                    // check for valid options object
                    if(typeof options !== "object") {
                        pluginErrors.push("`data-options` need to be a valid javascript object!");
                    }
                    // check for parsley plugin
                    if(options.validate && !jQuery().parsley) {
                        pluginErrors.push("please include `parsley` plugin for form validation!");
                    }

                    // check for errors
                    if (pluginErrors.length <= 0) {

                        // core ajax function
                        function jqxhr () {
                            // core ajax
                            var jxhr = $.ajax({
                                type: options.method || "post",
                                url: options.url,
                                dataType: "json",
                                data: $form.serialize()
                            });

                            // button interaction
                            if($(that).hasClass("ladda-button")) {
                                var ladda = Ladda.create(that).start();
                            } else {
                                $(that).prop("disabled", true);
                            }

                            // handle done
                            jxhr.done(function (data) {
                                // button interaction
                                !!$(that).hasClass("ladda-button") ? ladda.stop() : $(that).prop("disabled", false);

                                // trigger custom event
                                $(element).trigger(settings.eventPrefix+".formajax.done", { "element": $form, "response": data });
                            });

                            // handle fail
                            jxhr.fail(function (data) {
                                // button interaction
                                !!$(that).hasClass("ladda-button") ? ladda.stop() : $(that).prop("disabled", false);

                                // trigger custom event
                                $(element).trigger(settings.eventPrefix+".formajax.fail", { "element": $form, "response": data });
                            });
                        }

                        // ajax with validation enable
                        if(options.validate === true) {
                            if ($form.parsley().validate()) {
                                jqxhr();
                            }
                        } else {
                            jqxhr();
                        }

                    } else {
                        $.each(pluginErrors, function (index, value) {
                            $.error(value);
                        });
                    }
                }

                //
                $(document)
                    .on("submit", handler, function (e) { e.preventDefault() })
                    .on("click", handler+" button[type=submit]", ajaxForm);

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".formajax.always");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".formajax.done");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".formajax.fail");
            })();

            // @PLUGIN: CounterUp
            // Self invoking
            // ================================
            $(function () {
                // define variable
                var toggler         = "[data-toggle~=counterup]",
                    pluginErrors    = [];

                $(toggler).each(function (index, value) {
                    // define variable
                    options     = $(value).data("options");

                    // check for valid options object
                    if(options !== "undefined") {
                        //console.log(options);
                    } else {
                        //console.log(options);
                    }

                    // check for errors
                    if (pluginErrors.length <= 0) {
                        // core counterup plugin function
                        $(value).counterUp({
                            delay: 10,
                            time: 1000
                        });
                    } else {
                        $.each(pluginErrors, function (index, value) {
                            $.error(value);
                        });
                    }
                });
            });

            // @PLUGIN: OffCanvas
            // Self invoking
            // ================================
            $(function () {
                // define variable
                var container       = "[data-toggle~=offcanvas]",
                    pluginErrors    = [];

                $(container).each(function (index, value) {
                    // define variable
                    var options         = $(value).data("options");

                    // check for valid options object
                    if(options !== undefined) {
                        if(typeof options !== "object") {
                            pluginErrors.push("OffCanvas: `data-options` need to be a valid javascript object!");
                        } else {
                            // set value
                            optOpenerClass  = options.openerClass || "offcanvas-opener",
                            optCloserClass  = options.closerClass || "offcanvas-closer";
                        }
                    } else {
                        // set default value
                        optOpenerClass      = "offcanvas-opener",
                        optCloserClass      = "offcanvas-closer";
                    }

                    // check for errors
                    if (pluginErrors.length <= 0) {
                        $(value)
                            .on("click", "."+optOpenerClass, function (e) {
                                // get direction
                                var direction = !!$(this).hasClass("offcanvas-open-rtl") ? "offcanvas-open-rtl" : "offcanvas-open-ltr";

                                $(value)
                                    .removeClass("offcanvas-open-ltr offcanvas-open-rtl")
                                    .addClass(direction);

                                // trigger custom event
                                $(element).trigger(settings.eventPrefix+".offcanvas.open", { "element": $(value) });

                                // prevent default
                                e.preventDefault();
                            }).on("click", "."+optCloserClass, function (e) {
                                $(value)
                                    .removeClass("offcanvas-open-ltr offcanvas-open-rtl");

                                // trigger custom event
                                $(element).trigger(settings.eventPrefix+".offcanvas.close", { "element": $(value) });

                                // prevent default
                                e.preventDefault();
                            });
                    } else {
                        $.each(pluginErrors, function (index, value) {
                            $.error(value);
                        });
                    }
                });

                // Event console
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".offcanvas.open");
                MAIN.prototype.HELPER.Console(settings.eventPrefix+".offcanvas.close");
            });
        }
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new MAIN(this, options));
            }
        });
    };
//});
})( jQuery, window, document );