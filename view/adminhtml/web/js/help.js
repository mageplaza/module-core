
var mpHelpDb = {
	1: {
		'path': 'admin/system_config/index',
		'css_selector': '#general_locale_weekend',
		'type': 'link',
		'text': 'How to install an extension, {link}.',
		'url': 'https://www.mageplaza.com/install-magento-2-extension/',
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

