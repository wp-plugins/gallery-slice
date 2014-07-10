/* ajax for gallery slice */

jQuery(document).ready(function($){
	$(".unsliced-gallery-link a").click(function(){
		var hyperlink_div = $(this).parent();
		var animation_div = hyperlink_div.next();
		var gallery = hyperlink_div.prev();
		var rel_attr = gallery.find("a").first().attr("rel");

    hyperlink_div.hide();
    animation_div.show();
    
		$.post(
			GallerySliceAjax.ajaxurl,
			{
				action : gallery.hasClass("gallery-embed-rajce") ? 'gallery_slice-full_rajce_gallery' : 'gallery_slice-full_gallery',
				postID : $(this).attr("post_id"),
				origAttrs: $(this).attr("orig_gallery_attrs"),
				link_to_file: /\.(jpe?g|png|gif)$/i.exec(gallery.find("a").first().attr("href"))
			},
			function(response) {
				if (!response || !response.gallery) {
					// no reasonable data retrieved via ajax => fallback to opening post page
					return window.location.href = gallery.parents(".post").find(".entry-title a").attr("href");
				}
				hyperlink_div.after('<div style="display:none">' + response.gallery + '</div>');
				var temp_gallery = hyperlink_div.next().find("div.gallery");
				var temp_gallery_children_length = temp_gallery.children().length;
				
				// remove pictures (tags) that are already contained in gallery preview
				for (i = 0; i < temp_gallery_children_length; i++) {
					if (gallery.children().eq(i).prop("tagName") == temp_gallery.children().first().prop("tagName")) {
						temp_gallery.children().first().remove();
					} else {
						break;
					}
				}
				
				// support for various lightbox plugins
				
				if (typeof($.fn.lightbox) == "function" && /lightbox/i.exec(rel_attr)) {
					// lightbox 2 http://lokeshdhakar.com/projects/lightbox2/
					temp_gallery.find("a").attr("rel", rel_attr)
																.lightbox({title: function(){return $(this).children().attr("alt")}});
																
				} else if ($.colorbox && rel_attr != '') {
					// colorbox http://colorpowered.com/colorbox/
					temp_gallery.find("a").attr("rel", rel_attr)
					                      .attr("class", "cboxElement")
					                      .colorbox({title: function(){ return $(this).children().attr("alt");}, 
					                      	         maxWidth: "100%", 
					                      	         maxHeight: $(window).height() - 2 * ($('#wpadminbar').height() || 0)});

				} else if (typeof($.fn.fancybox) == "function" && /fancybox/i.exec(rel_attr)) {
					// FancyBox http://fancybox.net/
					temp_gallery.find("a").attr("rel", rel_attr)
																.fancybox({title: function(){return $(this).children().attr("alt")}});
																
				} else if (typeof Shadowbox !== 'undefined' && /(light|shadow)box/i.exec(rel_attr)) {
					// Shadowbox JS http://wordpress.org/plugins/shadowbox-js/
					var rel_array = rel_attr.split(";");
					var shadowbox_options = [];
					var match;
					for (var i = 0; i < rel_array.length; i++) {
						if ((match = /shadowbox\[(.*)\]/i.exec(rel_array[i])) != null) {
							shadowbox_options["gallery"] = match[1];
						} else if ((match = /(.*)=(.*)/i.exec(rel_array[i])) != null) {
							shadowbox_options[match[1]] = match[2];
						}
					}
					Shadowbox.setup(temp_gallery.find("a").get(), shadowbox_options);
					
				} else if (typeof(doLightBox) == "function" && /lightbox/i.exec(rel_attr)) {
					// wp-jquery-lightbox http://wordpress.org/extend/plugins/wp-jquery-lightbox/
					temp_gallery.find("a").attr("rel", rel_attr);
					doLightBox();
					
				} else if (typeof($.fn.lightBox) == "function") {
					// lightBox gallery http://wordpress.org/plugins/lightbox-gallery/
					temp_gallery.find("a").lightBox({captionPosition:"gallery"});
				}

				if (gallery.children().last().prop("tagName").toUpperCase() == "BR" && gallery.children().eq(gallery.children().length - 2).prop("tagName").toUpperCase() == "BR") {
					// if there are two <br>'s at the end of gallery, remove the last one
					gallery.children().last().remove();
				}

				// move the pictures from the hidden galery to the real one
				temp_gallery.children().appendTo(gallery).hide().fadeIn(300);
				
				// delete hidden gallery and delete full gallery link
				hyperlink_div.next().remove();
				hyperlink_div.remove();
				animation_div.remove();
			}
		);
		return false;
	});
});
