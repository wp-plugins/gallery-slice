jQuery(document).ready(function(a){var b=a("#gallery_slice_downto_global");b.prop("checked")&&(a("#gallery_slice_downto_text_div").hide(),a("#gallery_slice_downto").prop("disabled",!0));b.change(function(){a("#gallery_slice_downto_text_div").toggle(400,"swing");a("#gallery_slice_downto").prop("disabled",b.prop("checked"));b.prop("checked")||""!=a("#gallery_slice_downto").val()||a("#gallery_slice_downto").val(a("#gallery_slice_downto").attr("global-value"))});var c=a("#gallery_slice_link2full_global");
c.prop("checked")&&(a("#gallery_slice_link2full_text_div").hide(),a("#gallery_slice_link2full").prop("disabled",!0));c.change(function(){a("#gallery_slice_link2full_text_div").toggle(400,"swing");a("#gallery_slice_link2full").prop("disabled",c.prop("checked"));c.prop("checked")||""!=a("#gallery_slice_link2full").val()||a("#gallery_slice_link2full").val(a("#gallery_slice_link2full").attr("global-value"))});var d=a("#gallery_noslice");d.prop("checked")&&(a("#gallery_slice_downto_div").hide(),a("#gallery_slice_text2link_div").hide(),
a("#gallery_slice_noslice_div").css("border-bottom-width","0px"));d.change(function(){a("#gallery_slice_downto_div").toggle(400,"swing");a("#gallery_slice_text2link_div").toggle(400,"swing");a("#gallery_slice_noslice_div").css("border-bottom-width",d.prop("checked")?"0px":"1px")})});