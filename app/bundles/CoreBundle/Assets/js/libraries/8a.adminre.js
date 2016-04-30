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
 * SlimScroll.js v1.3.2
 * Src : https://github.com/rochal/jQuery-slimScroll
 * ======================================================================== */
!function(e){jQuery.fn.extend({slimScroll:function(i){var o={width:"auto",height:"250px",size:"7px",color:"#000",position:"right",distance:"1px",start:"top",opacity:.4,alwaysVisible:!1,disableFadeOut:!1,railVisible:!1,railColor:"#333",railOpacity:.2,railDraggable:!0,railClass:"slimScrollRail",barClass:"slimScrollBar",wrapperClass:"slimScrollDiv",allowPageScroll:!1,wheelStep:20,touchScrollStep:200,borderRadius:"7px",railBorderRadius:"7px"},r=e.extend(o,i);return this.each(function(){function o(t){if(h){var t=t||window.event,i=0;t.wheelDelta&&(i=-t.wheelDelta/120),t.detail&&(i=t.detail/3);var o=t.target||t.srcTarget||t.srcElement;e(o).closest("."+r.wrapperClass).is(x.parent())&&s(i,!0),t.preventDefault&&!y&&t.preventDefault(),y||(t.returnValue=!1)}}function s(e,t,i){y=!1;var o=e,s=x.outerHeight()-R.outerHeight();if(t&&(o=parseInt(R.css("top"))+e*parseInt(r.wheelStep)/100*R.outerHeight(),o=Math.min(Math.max(o,0),s),o=e>0?Math.ceil(o):Math.floor(o),R.css({top:o+"px"})),v=parseInt(R.css("top"))/(x.outerHeight()-R.outerHeight()),o=v*(x[0].scrollHeight-x.outerHeight()),i){o=e;var a=o/x[0].scrollHeight*x.outerHeight();a=Math.min(Math.max(a,0),s),R.css({top:a+"px"})}x.scrollTop(o),x.trigger("slimscrolling",~~o),n(),c()}function a(){window.addEventListener?(this.addEventListener("DOMMouseScroll",o,!1),this.addEventListener("mousewheel",o,!1)):document.attachEvent("onmousewheel",o)}function l(){f=Math.max(x.outerHeight()/x[0].scrollHeight*x.outerHeight(),m),R.css({height:f+"px"});var e=f==x.outerHeight()?"none":"block";R.css({display:e})}function n(){if(l(),clearTimeout(p),v==~~v){if(y=r.allowPageScroll,b!=v){var e=0==~~v?"top":"bottom";x.trigger("slimscroll",e)}}else y=!1;return b=v,f>=x.outerHeight()?void(y=!0):(R.stop(!0,!0).fadeIn("fast"),void(r.railVisible&&E.stop(!0,!0).fadeIn("fast")))}function c(){r.alwaysVisible||(p=setTimeout(function(){r.disableFadeOut&&h||u||d||(R.fadeOut("slow"),E.fadeOut("slow"))},1e3))}var h,u,d,p,g,f,v,b,w="<div></div>",m=30,y=!1,x=e(this);if(x.parent().hasClass(r.wrapperClass)){var C=x.scrollTop();if(R=x.parent().find("."+r.barClass),E=x.parent().find("."+r.railClass),l(),e.isPlainObject(i)){if("height"in i&&"auto"==i.height){x.parent().css("height","auto"),x.css("height","auto");var H=x.parent().parent().height();x.parent().css("height",H),x.css("height",H)}if("scrollTo"in i)C=parseInt(r.scrollTo);else if("scrollBy"in i)C+=parseInt(r.scrollBy);else if("destroy"in i)return R.remove(),E.remove(),void x.unwrap();s(C,!1,!0)}}else{r.height="auto"==i.height?x.parent().height():i.height;var S=e(w).addClass(r.wrapperClass).css({position:"relative",overflow:"hidden",width:r.width,height:r.height});x.css({overflow:"hidden",width:r.width,height:r.height});var E=e(w).addClass(r.railClass).css({width:r.size,height:"100%",position:"absolute",top:0,display:r.alwaysVisible&&r.railVisible?"block":"none","border-radius":r.railBorderRadius,background:r.railColor,opacity:r.railOpacity,zIndex:90}),R=e(w).addClass(r.barClass).css({background:r.color,width:r.size,position:"absolute",top:0,opacity:r.opacity,display:r.alwaysVisible?"block":"none","border-radius":r.borderRadius,BorderRadius:r.borderRadius,MozBorderRadius:r.borderRadius,WebkitBorderRadius:r.borderRadius,zIndex:99}),D="right"==r.position?{right:r.distance}:{left:r.distance};E.css(D),R.css(D),x.wrap(S),x.parent().append(R),x.parent().append(E),r.railDraggable&&R.bind("mousedown",function(i){var o=e(document);return d=!0,t=parseFloat(R.css("top")),pageY=i.pageY,o.bind("mousemove.slimscroll",function(e){currTop=t+e.pageY-pageY,R.css("top",currTop),s(0,R.position().top,!1)}),o.bind("mouseup.slimscroll",function(){d=!1,c(),o.unbind(".slimscroll")}),!1}).bind("selectstart.slimscroll",function(e){return e.stopPropagation(),e.preventDefault(),!1}),E.hover(function(){n()},function(){c()}),R.hover(function(){u=!0},function(){u=!1}),x.hover(function(){h=!0,n(),c()},function(){h=!1,c()}),x.bind("touchstart",function(e){e.originalEvent.touches.length&&(g=e.originalEvent.touches[0].pageY)}),x.bind("touchmove",function(e){if(y||e.originalEvent.preventDefault(),e.originalEvent.touches.length){var t=(g-e.originalEvent.touches[0].pageY)/r.touchScrollStep;s(t,!0),g=e.originalEvent.touches[0].pageY}}),l(),"bottom"===r.start?(R.css({top:x.outerHeight()-R.outerHeight()}),s(0,!0)):"top"!==r.start&&(s(e(r.start).position().top,null,!0),r.alwaysVisible||R.hide()),a()}}),this}}),jQuery.fn.extend({slimscroll:jQuery.fn.slimScroll})}(jQuery);

/* ========================================================================
 * Response.js v0.8.0
 * Src : http://responsejs.com/
 * ======================================================================== */
 !function(a,b,c){var d=a.jQuery||a.Zepto||a.ender||a.elo;"undefined"!=typeof module&&module.exports?module.exports=c(d):a[b]=c(d)}(this,"Response",function(a){function b(a){throw new TypeError(a?S+"."+a:S)}function c(a){return a===+a}function d(a,b,c){for(var d=[],e=a.length,f=0;e>f;)d[f]=b.call(c,a[f],f++,a);return d}function e(a){return a?h("string"==typeof a?a.split(" "):a):[]}function f(a,b,c){if(null==a)return a;for(var d=a.length,e=0;d>e;)b.call(c||a[e],a[e],e++,a);return a}function g(a,b,c){null==b&&(b=""),null==c&&(c="");for(var d=[],e=a.length,f=0;e>f;f++)null==a[f]||d.push(b+a[f]+c);return d}function h(a,b,c){var d,e,f,g=[],h=0,i=0,j="function"==typeof b,k=!0===c;for(e=a&&a.length,c=k?null:c;e>i;i++)f=a[i],d=j?!b.call(c,f,i,a):b?typeof f!==b:!f,d===k&&(g[h++]=f);return g}function i(a,b){if(null==a||null==b)return a;if("object"==typeof b&&c(b.length))bb.apply(a,h(b,"undefined",!0));else for(var d in b)fb.call(b,d)&&void 0!==b[d]&&(a[d]=b[d]);return a}function j(a,b,d){return null==a?a:("object"==typeof a&&!a.nodeType&&c(a.length)?f(a,b,d):b.call(d||a,a),a)}function k(a){return function(b,c){var d=a();return d>=(b||0)&&(!c||c>=d)}}function l(a){var b=V.devicePixelRatio;return null==a?b||(l(2)?2:l(1.5)?1.5:l(1)?1:0):isFinite(a)?b&&b>0?b>=a:(a="only all and (min--moz-device-pixel-ratio:"+a+")",Cb(a).matches?!0:!!Cb(a.replace("-moz-","")).matches):!1}function m(a){return a.replace(xb,"$1").replace(wb,function(a,b){return b.toUpperCase()})}function n(a){return"data-"+(a?a.replace(xb,"$1").replace(vb,"$1-$2").toLowerCase():a)}function o(a){var b;return"string"==typeof a&&a?"false"===a?!1:"true"===a?!0:"null"===a?null:"undefined"===a||(b=+a)||0===b||"NaN"===a?b:a:a}function p(a){return a?1===a.nodeType?a:a[0]&&1===a[0].nodeType?a[0]:!1:!1}function q(a,b){var c,d=arguments.length,e=p(this),g={},h=!1;if(d){if(gb(a)&&(h=!0,a=a[0]),"string"==typeof a){if(a=n(a),1===d)return g=e.getAttribute(a),h?o(g):g;if(this===e||2>(c=this.length||1))e.setAttribute(a,b);else for(;c--;)c in this&&q.apply(this[c],arguments)}else if(a instanceof Object)for(c in a)a.hasOwnProperty(c)&&q.call(this,c,a[c]);return this}return e.dataset&&"undefined"!=typeof DOMStringMap?e.dataset:(f(e.attributes,function(a){a&&(c=String(a.name).match(xb))&&(g[m(c[1])]=a.value)}),g)}function r(a){return this&&"string"==typeof a&&(a=e(a),j(this,function(b){f(a,function(a){a&&b.removeAttribute(n(a))})})),this}function s(a){return q.apply(a,cb.call(arguments,1))}function t(a,b){return r.call(a,b)}function u(a){for(var b,c=[],d=0,e=a.length;e>d;)(b=a[d++])&&c.push("["+n(b.replace(ub,"").replace(".","\\."))+"]");return c.join()}function v(b){return a(u(e(b)))}function w(){return window.pageXOffset||X.scrollLeft}function x(){return window.pageYOffset||X.scrollTop}function y(a,b){var c=a.getBoundingClientRect?a.getBoundingClientRect():{};return b="number"==typeof b?b||0:0,{top:(c.top||0)-b,left:(c.left||0)-b,bottom:(c.bottom||0)+b,right:(c.right||0)+b}}function z(a,b){var c=y(p(a),b);return!!c&&c.right>=0&&c.left<=Db()}function A(a,b){var c=y(p(a),b);return!!c&&c.bottom>=0&&c.top<=Eb()}function B(a,b){var c=y(p(a),b);return!!c&&c.bottom>=0&&c.top<=Eb()&&c.right>=0&&c.left<=Db()}function C(a){var b={img:1,input:1,source:3,embed:3,track:3,iframe:5,audio:5,video:5,script:5},c=b[a.nodeName.toLowerCase()]||-1;return 4>c?c:null!=a.getAttribute("src")?5:-5}function D(a,c,d){var e;return a&&null!=c||b("store"),d="string"==typeof d&&d,j(a,function(a){e=d?a.getAttribute(d):0<C(a)?a.getAttribute("src"):a.innerHTML,null==e?t(a,c):s(a,c,e)}),N}function E(a,b){var c=[];return a&&b&&f(e(b),function(b){c.push(s(a,b))},a),c}function F(a,b){return"string"==typeof a&&"function"==typeof b&&(jb[a]=b,kb[a]=1),N}function G(a){return Z.on("resize",a),N}function H(a,b){var c,d,e=Ab.crossover;return"function"==typeof a&&(c=b,b=a,a=c),d=a?""+a+e:e,Z.on(d,b),N}function I(a){return j(a,function(a){Y(a),G(a)}),N}function J(a){return j(a,function(a){"object"==typeof a||b("create @args");var c,d=yb(O).configure(a),e=d.verge,g=d.breakpoints,h=zb("scroll"),i=zb("resize");g.length&&(c=g[0]||g[1]||!1,Y(function(){function a(){d.reset(),f(d.$e,function(a,b){d[b].decideValue().updateDOM()}).trigger(g)}function b(){f(d.$e,function(a,b){B(d[b].$e,e)&&d[b].updateDOM()})}var g=Ab.allLoaded,j=!!d.lazy;f(d.target().$e,function(a,b){d[b]=yb(d).prepareData(a),(!j||B(d[b].$e,e))&&d[b].updateDOM()}),d.dynamic&&(d.custom||pb>c)&&G(a,i),j&&(Z.on(h,b),d.$e.one(g,function(){Z.off(h,b)}))}))}),N}function K(a){return R[S]===N&&(R[S]=T),"function"==typeof a&&a.call(R,N),N}function L(a,b){return"function"==typeof a&&a.fn&&((b||void 0===a.fn.dataset)&&(a.fn.dataset=q),(b||void 0===a.fn.deletes)&&(a.fn.deletes=r)),N}if("function"!=typeof a)try{return void console.warn("response.js aborted due to missing dependency")}catch(M){}var N,O,P,Q,R=this,S="Response",T=R[S],U="init"+S,V=window,W=document,X=W.documentElement,Y=a.domReady||a,Z=a(V),$=V.screen,_=Array.prototype,ab=Object.prototype,bb=_.push,cb=_.slice,db=_.concat,eb=ab.toString,fb=ab.hasOwnProperty,gb=Array.isArray||function(a){return"[object Array]"===eb.call(a)},hb={width:[0,320,481,641,961,1025,1281],height:[0,481],ratio:[1,1.5,2]},ib={},jb={},kb={},lb={all:[]},mb=1,nb=$.width,ob=$.height,pb=nb>ob?nb:ob,qb=nb+ob-pb,rb=function(){return nb},sb=function(){return ob},tb=/[^a-z0-9_\-\.]/gi,ub=/^[\W\s]+|[\W\s]+$|/g,vb=/([a-z])([A-Z])/g,wb=/-(.)/g,xb=/^data-(.+)$/,yb=Object.create||function(a){function b(){}return b.prototype=a,new b},zb=function(a,b){return b=b||S,a.replace(ub,"")+"."+b.replace(ub,"")},Ab={allLoaded:zb("allLoaded"),crossover:zb("crossover")},Bb=V.matchMedia||V.msMatchMedia,Cb=Bb||function(){return{}},Db=function(){var a=X.clientWidth,b=V.innerWidth;return b>a?b:a},Eb=function(){var a=X.clientHeight,b=V.innerHeight;return b>a?b:a};return P=k(Db),Q=k(Eb),ib.band=k(rb),ib.wave=k(sb),O=function(){function c(a){return"string"==typeof a?a.toLowerCase().replace(tb,""):""}function j(a,b){return a-b}var k=Ab.crossover,l=Math.min;return{$e:0,mode:0,breakpoints:null,prefix:null,prop:"width",keys:[],dynamic:null,custom:0,values:[],fn:0,verge:null,newValue:0,currValue:1,aka:null,lazy:null,i:0,uid:null,reset:function(){for(var a=this.breakpoints,b=a.length,c=0;!c&&b--;)this.fn(a[b])&&(c=b);return c!==this.i&&(Z.trigger(k).trigger(this.prop+k),this.i=c||0),this},configure:function(a){i(this,a);var k,m,n,o,p,q=!0,r=this.prop;if(this.uid=mb++,null==this.verge&&(this.verge=l(pb,500)),this.fn=jb[r]||b("create @fn"),null==this.dynamic&&(this.dynamic="device"!==r.slice(0,6)),this.custom=kb[r],n=this.prefix?h(d(e(this.prefix),c)):["min-"+r+"-"],o=1<n.length?n.slice(1):0,this.prefix=n[0],m=this.breakpoints,gb(m)?(f(m,function(a){if(!a&&0!==a)throw"invalid breakpoint";q=q&&isFinite(a)}),q&&m.sort(j),m.length||b("create @breakpoints")):m=hb[r]||hb[r.split("-").pop()]||b("create @prop"),this.breakpoints=q?h(m,function(a){return pb>=a}):m,this.keys=g(this.breakpoints,this.prefix),this.aka=null,o){for(p=[],k=o.length;k--;)p.push(g(this.breakpoints,o[k]));this.aka=p,this.keys=db.apply(this.keys,p)}return lb.all=lb.all.concat(lb[this.uid]=this.keys),this},target:function(){return this.$e=a(u(lb[this.uid])),D(this.$e,U),this.keys.push(U),this},decideValue:function(){for(var a=null,b=this.breakpoints,c=b.length,d=c;null==a&&d--;)this.fn(b[d])&&(a=this.values[d]);return this.newValue="string"==typeof a?a:this.values[c],this},prepareData:function(b){if(this.$e=a(b),this.mode=C(b),this.values=E(this.$e,this.keys),this.aka)for(var c=this.aka.length;c--;)this.values=i(this.values,E(this.$e,this.aka[c]));return this.decideValue()},updateDOM:function(){return this.currValue===this.newValue?this:(this.currValue=this.newValue,0<this.mode?this.$e[0].setAttribute("src",this.newValue):null==this.newValue?this.$e.empty&&this.$e.empty():this.$e.html?this.$e.html(this.newValue):(this.$e.empty&&this.$e.empty(),this.$e[0].innerHTML=this.newValue),this)}}}(),jb.width=P,jb.height=Q,jb["device-width"]=ib.band,jb["device-height"]=ib.wave,jb["device-pixel-ratio"]=l,N={deviceMin:function(){return qb},deviceMax:function(){return pb},noConflict:K,bridge:L,create:J,addTest:F,datatize:n,camelize:m,render:o,store:D,access:E,target:v,object:yb,crossover:H,action:I,resize:G,ready:Y,affix:g,sift:h,dpr:l,deletes:t,scrollX:w,scrollY:x,deviceW:rb,deviceH:sb,device:ib,inX:z,inY:A,route:j,merge:i,media:Cb,wave:Q,band:P,map:d,each:f,inViewport:B,dataset:s,viewportH:Eb,viewportW:Db},Y(function(){var b=s(W.body,"responsejs"),c=V.JSON&&JSON.parse||a.parseJSON;b=b&&c?c(b):b,b&&b.create&&J(b.create),X.className=X.className.replace(/(^|\s)(no-)?responsejs(\s|$)/,"$1$3")+" responsejs "}),N});

/* ========================================================================
 * Transit.js v0.9.9
 * Src : https://raw.githubusercontent.com/rstacruz/jquery.transit/
 * ======================================================================== */
(function(k){k.transit={version:"0.9.9",propertyMap:{marginLeft:"margin",marginRight:"margin",marginBottom:"margin",marginTop:"margin",paddingLeft:"padding",paddingRight:"padding",paddingBottom:"padding",paddingTop:"padding"},enabled:true,useTransitionEnd:false};var d=document.createElement("div");var q={};function b(v){if(v in d.style){return v}var u=["Moz","Webkit","O","ms"];var r=v.charAt(0).toUpperCase()+v.substr(1);if(v in d.style){return v}for(var t=0;t<u.length;++t){var s=u[t]+r;if(s in d.style){return s}}}function e(){d.style[q.transform]="";d.style[q.transform]="rotateY(90deg)";return d.style[q.transform]!==""}var a=navigator.userAgent.toLowerCase().indexOf("chrome")>-1;q.transition=b("transition");q.transitionDelay=b("transitionDelay");q.transform=b("transform");q.transformOrigin=b("transformOrigin");q.transform3d=e();var i={transition:"transitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd",WebkitTransition:"webkitTransitionEnd",msTransition:"MSTransitionEnd"};var f=q.transitionEnd=i[q.transition]||null;for(var p in q){if(q.hasOwnProperty(p)&&typeof k.support[p]==="undefined"){k.support[p]=q[p]}}d=null;k.cssEase={_default:"ease","in":"ease-in",out:"ease-out","in-out":"ease-in-out",snap:"cubic-bezier(0,1,.5,1)",easeOutCubic:"cubic-bezier(.215,.61,.355,1)",easeInOutCubic:"cubic-bezier(.645,.045,.355,1)",easeInCirc:"cubic-bezier(.6,.04,.98,.335)",easeOutCirc:"cubic-bezier(.075,.82,.165,1)",easeInOutCirc:"cubic-bezier(.785,.135,.15,.86)",easeInExpo:"cubic-bezier(.95,.05,.795,.035)",easeOutExpo:"cubic-bezier(.19,1,.22,1)",easeInOutExpo:"cubic-bezier(1,0,0,1)",easeInQuad:"cubic-bezier(.55,.085,.68,.53)",easeOutQuad:"cubic-bezier(.25,.46,.45,.94)",easeInOutQuad:"cubic-bezier(.455,.03,.515,.955)",easeInQuart:"cubic-bezier(.895,.03,.685,.22)",easeOutQuart:"cubic-bezier(.165,.84,.44,1)",easeInOutQuart:"cubic-bezier(.77,0,.175,1)",easeInQuint:"cubic-bezier(.755,.05,.855,.06)",easeOutQuint:"cubic-bezier(.23,1,.32,1)",easeInOutQuint:"cubic-bezier(.86,0,.07,1)",easeInSine:"cubic-bezier(.47,0,.745,.715)",easeOutSine:"cubic-bezier(.39,.575,.565,1)",easeInOutSine:"cubic-bezier(.445,.05,.55,.95)",easeInBack:"cubic-bezier(.6,-.28,.735,.045)",easeOutBack:"cubic-bezier(.175, .885,.32,1.275)",easeInOutBack:"cubic-bezier(.68,-.55,.265,1.55)"};k.cssHooks["transit:transform"]={get:function(r){return k(r).data("transform")||new j()},set:function(s,r){var t=r;if(!(t instanceof j)){t=new j(t)}if(q.transform==="WebkitTransform"&&!a){s.style[q.transform]=t.toString(true)}else{s.style[q.transform]=t.toString()}k(s).data("transform",t)}};k.cssHooks.transform={set:k.cssHooks["transit:transform"].set};if(k.fn.jquery<"1.8"){k.cssHooks.transformOrigin={get:function(r){return r.style[q.transformOrigin]},set:function(r,s){r.style[q.transformOrigin]=s}};k.cssHooks.transition={get:function(r){return r.style[q.transition]},set:function(r,s){r.style[q.transition]=s}}}n("scale");n("translate");n("rotate");n("rotateX");n("rotateY");n("rotate3d");n("perspective");n("skewX");n("skewY");n("x",true);n("y",true);function j(r){if(typeof r==="string"){this.parse(r)}return this}j.prototype={setFromString:function(t,s){var r=(typeof s==="string")?s.split(","):(s.constructor===Array)?s:[s];r.unshift(t);j.prototype.set.apply(this,r)},set:function(s){var r=Array.prototype.slice.apply(arguments,[1]);if(this.setter[s]){this.setter[s].apply(this,r)}else{this[s]=r.join(",")}},get:function(r){if(this.getter[r]){return this.getter[r].apply(this)}else{return this[r]||0}},setter:{rotate:function(r){this.rotate=o(r,"deg")},rotateX:function(r){this.rotateX=o(r,"deg")},rotateY:function(r){this.rotateY=o(r,"deg")},scale:function(r,s){if(s===undefined){s=r}this.scale=r+","+s},skewX:function(r){this.skewX=o(r,"deg")},skewY:function(r){this.skewY=o(r,"deg")},perspective:function(r){this.perspective=o(r,"px")},x:function(r){this.set("translate",r,null)},y:function(r){this.set("translate",null,r)},translate:function(r,s){if(this._translateX===undefined){this._translateX=0}if(this._translateY===undefined){this._translateY=0}if(r!==null&&r!==undefined){this._translateX=o(r,"px")}if(s!==null&&s!==undefined){this._translateY=o(s,"px")}this.translate=this._translateX+","+this._translateY}},getter:{x:function(){return this._translateX||0},y:function(){return this._translateY||0},scale:function(){var r=(this.scale||"1,1").split(",");if(r[0]){r[0]=parseFloat(r[0])}if(r[1]){r[1]=parseFloat(r[1])}return(r[0]===r[1])?r[0]:r},rotate3d:function(){var t=(this.rotate3d||"0,0,0,0deg").split(",");for(var r=0;r<=3;++r){if(t[r]){t[r]=parseFloat(t[r])}}if(t[3]){t[3]=o(t[3],"deg")}return t}},parse:function(s){var r=this;s.replace(/([a-zA-Z0-9]+)\((.*?)\)/g,function(t,v,u){r.setFromString(v,u)})},toString:function(t){var s=[];for(var r in this){if(this.hasOwnProperty(r)){if((!q.transform3d)&&((r==="rotateX")||(r==="rotateY")||(r==="perspective")||(r==="transformOrigin"))){continue}if(r[0]!=="_"){if(t&&(r==="scale")){s.push(r+"3d("+this[r]+",1)")}else{if(t&&(r==="translate")){s.push(r+"3d("+this[r]+",0)")}else{s.push(r+"("+this[r]+")")}}}}}return s.join(" ")}};function m(s,r,t){if(r===true){s.queue(t)}else{if(r){s.queue(r,t)}else{t()}}}function h(s){var r=[];k.each(s,function(t){t=k.camelCase(t);t=k.transit.propertyMap[t]||k.cssProps[t]||t;t=c(t);if(k.inArray(t,r)===-1){r.push(t)}});return r}function g(s,v,x,r){var t=h(s);if(k.cssEase[x]){x=k.cssEase[x]}var w=""+l(v)+" "+x;if(parseInt(r,10)>0){w+=" "+l(r)}var u=[];k.each(t,function(z,y){u.push(y+" "+w)});return u.join(", ")}k.fn.transition=k.fn.transit=function(z,s,y,C){var D=this;var u=0;var w=true;if(typeof s==="function"){C=s;s=undefined}if(typeof y==="function"){C=y;y=undefined}if(typeof z.easing!=="undefined"){y=z.easing;delete z.easing}if(typeof z.duration!=="undefined"){s=z.duration;delete z.duration}if(typeof z.complete!=="undefined"){C=z.complete;delete z.complete}if(typeof z.queue!=="undefined"){w=z.queue;delete z.queue}if(typeof z.delay!=="undefined"){u=z.delay;delete z.delay}if(typeof s==="undefined"){s=k.fx.speeds._default}if(typeof y==="undefined"){y=k.cssEase._default}s=l(s);var E=g(z,s,y,u);var B=k.transit.enabled&&q.transition;var t=B?(parseInt(s,10)+parseInt(u,10)):0;if(t===0){var A=function(F){D.css(z);if(C){C.apply(D)}if(F){F()}};m(D,w,A);return D}var x={};var r=function(H){var G=false;var F=function(){if(G){D.unbind(f,F)}if(t>0){D.each(function(){this.style[q.transition]=(x[this]||null)})}if(typeof C==="function"){C.apply(D)}if(typeof H==="function"){H()}};if((t>0)&&(f)&&(k.transit.useTransitionEnd)){G=true;D.bind(f,F)}else{window.setTimeout(F,t)}D.each(function(){if(t>0){this.style[q.transition]=E}k(this).css(z)})};var v=function(F){this.offsetWidth;r(F)};m(D,w,v);return this};function n(s,r){if(!r){k.cssNumber[s]=true}k.transit.propertyMap[s]=q.transform;k.cssHooks[s]={get:function(v){var u=k(v).css("transit:transform");return u.get(s)},set:function(v,w){var u=k(v).css("transit:transform");u.setFromString(s,w);k(v).css({"transit:transform":u})}}}function c(r){return r.replace(/([A-Z])/g,function(s){return"-"+s.toLowerCase()})}function o(s,r){if((typeof s==="string")&&(!s.match(/^[\-0-9\.]+$/))){return s}else{return""+s+r}}function l(s){var r=s;if(k.fx.speeds[r]){r=k.fx.speeds[r]}return o(r,"ms")}k.transit.getTransitionValue=g})(jQuery);

/* ========================================================================
 * ladda.js v0.8.0
 * Src : http://msurguy.github.io/ladda-bootstrap/
 * ======================================================================== */
(function(t,e){"object"==typeof exports?module.exports=e():"function"==typeof define&&define.amd?define(["spin"],e):t.Ladda=e(t.Spinner)})(this,function(t){"use strict";function e(t){if(t===void 0)return console.warn("Ladda button target must be defined."),void 0;t.querySelector(".ladda-label")||(t.innerHTML='<span class="ladda-label">'+t.innerHTML+"</span>");var e=i(t),n=document.createElement("span");n.className="ladda-spinner",t.appendChild(n);var r,a={start:function(){return t.setAttribute("disabled",""),t.setAttribute("data-loading",""),clearTimeout(r),e.spin(n),this.setProgress(0),this},startAfter:function(t){return clearTimeout(r),r=setTimeout(function(){a.start()},t),this},stop:function(){return t.removeAttribute("disabled"),t.removeAttribute("data-loading"),clearTimeout(r),r=setTimeout(function(){e.stop()},1e3),this},toggle:function(){return this.isLoading()?this.stop():this.start(),this},setProgress:function(e){e=Math.max(Math.min(e,1),0);var n=t.querySelector(".ladda-progress");0===e&&n&&n.parentNode?n.parentNode.removeChild(n):(n||(n=document.createElement("div"),n.className="ladda-progress",t.appendChild(n)),n.style.width=(e||0)*t.offsetWidth+"px")},enable:function(){return this.stop(),this},disable:function(){return this.stop(),t.setAttribute("disabled",""),this},isLoading:function(){return t.hasAttribute("data-loading")}};return o.push(a),a}function n(t,n){n=n||{};var r=[];"string"==typeof t?r=a(document.querySelectorAll(t)):"object"==typeof t&&"string"==typeof t.nodeName&&(r=[t]);for(var i=0,o=r.length;o>i;i++)(function(){var t=r[i];if("function"==typeof t.addEventListener){var a=e(t),o=-1;t.addEventListener("click",function(){a.startAfter(1),"number"==typeof n.timeout&&(clearTimeout(o),o=setTimeout(a.stop,n.timeout)),"function"==typeof n.callback&&n.callback.apply(null,[a])},!1)}})()}function r(){for(var t=0,e=o.length;e>t;t++)o[t].stop()}function i(e){var n,r=e.offsetHeight;r>32&&(r*=.8),e.hasAttribute("data-spinner-size")&&(r=parseInt(e.getAttribute("data-spinner-size"),10)),e.hasAttribute("data-spinner-color")&&(n=e.getAttribute("data-spinner-color"));var i=12,a=.2*r,o=.6*a,s=7>a?2:3;return new t({color:n||"#fff",lines:i,radius:a,length:o,width:s,zIndex:"auto",top:"auto",left:"auto",className:""})}function a(t){for(var e=[],n=0;t.length>n;n++)e.push(t[n]);return e}var o=[];return{bind:n,create:e,stopAll:r}});

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
            loader: false,
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
                //this.Fastclick();
                //this.Unveil();
                this.BsTooltip();
                this.BsPopover();
                //this.Stellar();
                //this.InputPlaceholder();
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
                        $($this).parentsUntil(target).addClass(contextual);

                        // publish event
                        $(element).trigger(settings.eventPrefix+".selectrow.selected", { "element": $($this).parentsUntil(target) });
                    } else {
                        // remove contextual class
                        $($this).parentsUntil(target).removeClass(contextual);

                        // publish event
                        $(element).trigger(settings.eventPrefix+".selectrow.unselected", { "element": $($this).parentsUntil(target) });
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
                        $(this).removeClass("active");

                        // publish event
                        $(element).trigger(settings.eventPrefix+".sidebar.maximize", { "element": $(element) });
                    } else {
                        isMinimize = true;
                        $(element).addClass("sidebar-minimized");
                        $(this).addClass("active");

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