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
});
