jQuery(document).ready(function(a){a(".unsliced-gallery-link a").click(function(){var f=a(this).parent(),l=f.next(),d=f.prev(),c=d.find("a").first().attr("rel");f.hide();l.show();a.post(GallerySliceAjax.ajaxurl,{action:"gallery_slice-full_gallery",postID:a(this).attr("post_id"),origAttrs:a(this).attr("orig_gallery_attrs"),link_to_file:/\.(jpe?g|png|gif)$/i.exec(d.find("a").first().attr("href"))},function(b){if(!b||!b.gallery)return window.location.href=d.parents(".post").find(".entry-title a").attr("href");f.after('<div style="display:none">'+b.gallery+"</div>");b=f.next().children().first();for(var g=b.children().length,e=0;e<g;e++)if(d.children().eq(e).prop("tagName")==b.children().first().prop("tagName"))b.children().first().remove();else break;if("function"==typeof a.fn.lightbox&&/lightbox/i.exec(c))b.find("a").attr("rel",c).lightbox({title:function(){return a(this).children().attr("alt")}});else if(a.colorbox&&""!=c)b.find("a").attr("rel",c).attr("class","cboxElement").colorbox({title:function(){return a(this).children().attr("alt")},maxWidth:"100%",maxHeight:a(window).height()-2*(a("#wpadminbar").height()||0)});else if("function"==typeof a.fn.fancybox&&/fancybox/i.exec(c))b.find("a").attr("rel",c).fancybox({title:function(){return a(this).children().attr("alt")}});else if("undefined"!==typeof Shadowbox&&/(light|shadow)box/i.exec(c)){for(var g=c.split(";"),k=[],h,e=0;e<g.length;e++)null!=(h=/shadowbox\[(.*)\]/i.exec(g[e]))?k.gallery=h[1]:null!=(h=/(.*)=(.*)/i.exec(g[e]))&&(k[h[1]]=h[2]);Shadowbox.setup(b.find("a").get(),k)}else"function"==typeof doLightBox&&/lightbox/i.exec(c)?(b.find("a").attr("rel",c),doLightBox()):"function"==typeof a.fn.lightBox&&b.find("a").lightBox({captionPosition:"gallery"});"BR"==d.children().last().prop("tagName").toUpperCase()&&"BR"==d.children().eq(d.children().length-2).prop("tagName").toUpperCase()&&d.children().last().remove();b.children().appendTo(d).hide().fadeIn(300);f.next().remove();f.remove();l.remove()});return!1})});