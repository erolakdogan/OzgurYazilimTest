jQuery(document).ready(function() { 
  jQuery( ".ozguryazilim-container" ).each(function( index ) {

    var content_id = jQuery(this).data('content-id');
    var itemName = "ozguryazilim"+content_id;
    jQuery(this).find('.ozguryazilim-up').click(function () {
      if (localStorage.getItem(itemName)) {
        var vote = localStorage.getItem(itemName);
        ozguryazilim_vote(content_id, -vote);
      } else {
        var vote = jQuery(this).data('vote');
        ozguryazilim_vote(content_id, vote);
      }
    })
  });
});


function ozguryazilim_vote(ID, type) {
	const itemName = "ozguryazilim" + ID;
	
	const typeItemName = "ozguryazilim" + ID;
	localStorage.setItem(typeItemName, type);

	var data = {
		action: 'ozguryazilim_add_vote',
		postid: ID,
		type: type,
		nonce: ozguryazilim_ajax.nonce
	};
		
	jQuery.post(ozguryazilim_ajax.ajax_url, data, function(response) {		
		jQuery('#ozguryazilim-' + ID).find('.ozguryazilim-up strong').text(parseInt(jQuery('.ozguryazilim-up strong').text()) + type);
	});
}