
var mpHelpDb = {
	1: {
		'path': 'admin/system_config/index',
		'css_selector': '#general_single_store_mode_enabled',
		'type': 'link',
		'text': 'How to enable Single Store Mode, {link}.',
		'url': 'https://www.mageplaza.com/kb/how-enable-single-store-mode-magento-2.html',
		'anchor': 'learn more',
	}
}


function buildHtml(data){
	var html = '';
	switch(data.type) {
	    default:
	    	var text = data.text.replace('{link}', '<a href="'+ data.url +'" target="_blank">'+ data.anchor +'</a>');
	        var html = '<p class="note">'+ text +'</p>';
	}
	return html;
}

var url = window.location.href;

for (var key in mpHelpDb){
	data = mpHelpDb[key];
	if (mpHelpDb.hasOwnProperty(key) && url.includes(data.path)) {
		var html = buildHtml(data);

		jQuery(html).insertAfter(data.css_selector);
	}
}

