jQuery(document).ready(function($){
	var gallery_slice_downto_global = $("#gallery_slice_downto_global");
	if (gallery_slice_downto_global.prop('checked')) {
		$("#gallery_slice_downto_text_div").hide();
		$("#gallery_slice_downto").prop('disabled', true);
	}
	gallery_slice_downto_global.change(function() {
		$("#gallery_slice_downto_text_div").toggle(400, "swing");
		$("#gallery_slice_downto").prop('disabled', gallery_slice_downto_global.prop('checked'));
		if (!gallery_slice_downto_global.prop('checked') && $("#gallery_slice_downto").val() == "") {
			$("#gallery_slice_downto").val($("#gallery_slice_downto").attr("global-value"));
		}
	});
	var gallery_slice_link2full_global = $("#gallery_slice_link2full_global");
	if (gallery_slice_link2full_global.prop('checked')) {
		$("#gallery_slice_link2full_text_div").hide();
		$("#gallery_slice_link2full").prop('disabled', true);
	}
	gallery_slice_link2full_global.change(function() {
		$("#gallery_slice_link2full_text_div").toggle(400, "swing");
		$("#gallery_slice_link2full").prop('disabled', gallery_slice_link2full_global.prop('checked'));
		if (!gallery_slice_link2full_global.prop('checked') && $("#gallery_slice_link2full").val() == "") {
			$("#gallery_slice_link2full").val($("#gallery_slice_link2full").attr("global-value"));
		}
	});
	var gallery_noslice = $("#gallery_noslice");
	if (gallery_noslice.prop('checked')) {
		$("#gallery_slice_downto_div").hide();
		$("#gallery_slice_text2link_div").hide();
		$("#gallery_slice_noslice_div").css("border-bottom-width", "0px");
	}
	gallery_noslice.change(function() {
		$("#gallery_slice_downto_div").toggle(400, "swing");
		$("#gallery_slice_text2link_div").toggle(400, "swing");
		$("#gallery_slice_noslice_div").css("border-bottom-width", gallery_noslice.prop('checked') ? "0px" : "1px");
	});
  $("input#gallery_slice_waiting_img").focusout(function() {
    $("img#gallery_slice_waiting_img_preview").attr("src", $(this).attr("value"));
  });
  $("a#gallery_slice_waiting_img_set_default").click(function() {
    $('input#gallery_slice_waiting_img').attr('value', $(this).attr('defaultvalue'));
    $('img#gallery_slice_waiting_img_preview').attr('src', $('input#gallery_slice_waiting_img').attr('value'));
  });
  $('#gallery_slice_waiting_img_media_library_button').click(function() {
    var custom_file_frame;
    event.preventDefault();
    custom_file_frame = wp.media.frames.customHeader = wp.media({
       title: $(this).attr("selecttext"),
       library: {
          type: 'image'
       },
       multiple: false
    });
    
    custom_file_frame.on('select', function() {
       var attachment = custom_file_frame.state().get('selection').first().toJSON();
       $('input#gallery_slice_waiting_img').attr('value', attachment.url);
       $('img#gallery_slice_waiting_img_preview').attr('src', attachment.url);
    });

    //Open modal
    custom_file_frame.open();
  }); 
});