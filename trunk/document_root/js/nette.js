/**
 * AJAX Nette Framwork plugin for jQuery
 *
 * @copyright  Copyright (c) 2009 Jan Marek
 * @license    MIT
 * @link       http://nettephp.com/cs/extras/jquery-ajax
 * @version    0.1
 */

// jQuery.extend({
// 	updateSnippet: function (id, html) {
// 		$("#" + id).html(html);
// 	},
// 
// 	netteCallback: function (data) {
// 		// redirect
// 		if (data.redirect) {
// 			window.location.href = data.redirect;
// 		}
// 
// 		// snippets
// 		if (data.snippets) {
// 			for (var i in data.snippets) {
// 				jQuery.updateSnippet(i, data.snippets[i]);
// 			}
// 		}
// 	}
// });
// 
// jQuery.ajaxSetup({
// 	success: function (data) {
// 		jQuery.netteCallback(data);
// 	},
// 	dataType: "json"
// });


/**
 * AJAX form plugin for jQuery
 *
 * @copyright  Copyright (c) 2009 Jan Marek
 * @license    MIT
 * @link       http://nettephp.com/cs/extras/ajax-form
 * @version    0.1
 */

jQuery.fn.extend({
	ajaxSubmit: function (callback) {
		var form;
		var sendValues = {};
	
		// odesláno na tlačítku
		if (this.is(":submit")) {
			form = this.parents("form");
			sendValues[this.attr("name")] = this.val() || "";
	
		// odesláno na formuláři
		} else if (this.is("form")) {
			form = this;
	
		// neplatný element, nic nedělat
		} else {
			return null;
		}
	
		// validace
		if (form.get(0).onsubmit && !form.get(0).onsubmit()) return;
	
		var values = form.serializeArray();
	
		for (var i = 0; i < values.length; i++) {
			var name = values[i].name;
	
			// multi
			if (name in sendValues) {
				var val = sendValues[name];
	
				if (!(val instanceof Array)) {
					val = [val];
				}
	
				val.push(values[i].value);
				sendValues[name] = val;
			} else {
				sendValues[name] = values[i].value;
			}
		}
	
		// odeslat ajaxový požadavek
		return jQuery.ajax({
			url: form.attr("action"),
			data: sendValues,
			type: form.attr("method") || "get",
	    success: callback || null
		});
	}
});