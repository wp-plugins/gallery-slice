jQuery(document).ready(function(a){a(".unsliced-gallery-link a").click(function(){var f=a(this).parent(),l=f.next(),c=f.prev(),d=c.find("a").first().attr("rel");f.hide();l.show();a.post(GallerySliceAjax.ajaxurl,{action:c.hasClass("gallery-embed-rajce")?"gallery_slice-full_rajce_gallery":"gallery_slice-full_gallery",postID:a(this).attr("post_id"),origAttrs:a(this).attr("orig_gallery_attrs"),link_to_file:/\.(jpe?g|png|gif)$/i.exec(c.find("a").first().attr("href"))},function(b){if(!b||!b.gallery)return window.location.href=c.parents(".post").find(".entry-title a").attr("href");f.after('<div style="display:none">'+b.gallery+"</div>");b=f.next().find("div.gallery");for(var g=b.children().length,e=0;e<g;e++)if(c.children().eq(e).prop("tagName")==b.children().first().prop("tagName"))b.children().first().remove();else break;if("function"==typeof a.fn.lightbox&&/lightbox/i.exec(d))b.find("a").attr("rel",d).lightbox({title:function(){return a(this).children().attr("alt")}});else if(a.colorbox&&""!=d)b.find("a").attr("rel",d).attr("class","cboxElement").colorbox({title:function(){return a(this).children().attr("alt")},maxWidth:"100%",maxHeight:a(window).height()-2*(a("#wpadminbar").height()||0)});else if("function"==typeof a.fn.fancybox&&/fancybox/i.exec(d))b.find("a").attr("rel",d).fancybox({title:function(){return a(this).children().attr("alt")}});else if("undefined"!==typeof Shadowbox&&/(light|shadow)box/i.exec(d)){for(var g=d.split(";"),k=[],h,e=0;e<g.length;e++)null!=(h=/shadowbox\[(.*)\]/i.exec(g[e]))?k.gallery=h[1]:null!=(h=/(.*)=(.*)/i.exec(g[e]))&&(k[h[1]]=h[2]);Shadowbox.setup(b.find("a").get(),k)}else"function"==typeof doLightBox&&/lightbox/i.exec(d)?(b.find("a").attr("rel",d),doLightBox()):"function"==typeof a.fn.lightBox&&b.find("a").lightBox({captionPosition:"gallery"});"BR"==c.children().last().prop("tagName").toUpperCase()&&"BR"==c.children().eq(c.children().length-2).prop("tagName").toUpperCase()&&c.children().last().remove();b.children().appendTo(c).hide().fadeIn(300);f.next().remove();f.remove();l.remove()});return!1})});