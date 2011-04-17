tinyMCEPopup.requireLangPack();

var ImgUploadDialog = {
	plugin_url: tinyMCEPopup.getWindowArg('plugin_url'),
	jQuery_obj : tinyMCEPopup.getWindowArg('jQuery'),
	frm_elts: null,
	
	init : function() {
		if (ImgUploadDialog.jQuery_obj) {
			var jQuery = ImgUploadDialog.jQuery_obj;
			ImgUploadDialog.frm_elts = {
				cancel_button: jQuery('#imgupload_cancel_button', document),
				dest_fname: jQuery('#imgupload_dest_fname', document),
				dest_fname_wrapper: jQuery('#imgupload_dest_fname_wrapper', document),
				do_button: jQuery('#imgupload_do_button', document),
				form: jQuery('#imgupload_upload_form', document),
				iframe_target: jQuery('#imgupload_iframe_target', document),
				legal_exts: jQuery('#imgupload_legal_exts', document),
				message_target: jQuery('#imgupload_message_target', document),
				upload_file: jQuery('#imgupload_upload_file', document),
				upload_file_wrapper: jQuery('#imgupload_upload_file_wrapper', document),
			};
			
			ImgUploadDialog.frm_elts.upload_file.val('');
			ImgUploadDialog.frm_elts.dest_fname_wrapper.val('').css('visibility', 'hidden');
			ImgUploadDialog.frm_elts.do_button.css('visibility', 'hidden');
			ImgUploadDialog.frm_elts.message_target.html(tinyMCEPopup.getLang('imgupload_dlg.select_file_msg', 'Please Select File to Upload'));

			ImgUploadDialog.frm_elts.upload_file.change(ImgUploadDialog.upload_file_change);
			// ImgUploadDialog.frm_elts.form.submit(ImgUploadDialog.upload);
			ImgUploadDialog.frm_elts.form.attr('target', 'imgupload_iframe_target');
		} else {
			ImgUploadDialog.frm_elts = {
				cancel_button: document.getElementById('imgupload_cancel_button'),
				dest_fname: document.getElementById('imgupload_dest_fname'),
				dest_fname_wrapper: document.getElementById('imgupload_dest_fname_wrapper'),
				do_button: document.getElementById('imgupload_do_button'),
				form: document.getElementById('imgupload_upload_form'),
				iframe_target: document.getElementById('imgupload_iframe_target'),
				message_target: document.getElementById('imgupload_message_target'),
				upload_file: document.getElementById('imgupload_upload_file'),
				upload_file_wrapper: document.getElementById('imgupload_upload_file_wrapper'),
			};
			// var f = document.forms[0];

			ImgUploadDialog.frm_elts.upload_file.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
			ImgUploadDialog.frm_elts.dest_fname.value = '';
			ImgUploadDialog.frm_elts.dest_fname_wrapper.style.visibility = 'hidden';
			ImgUploadDialog.frm_elts.do_button.style.visibility = 'hidden';
			ImgUploadDialog.frm_elts.message_target.innerHTML = tinyMCEPopup.editor.getLang('imgupload_dlg.select_file_msg', 'Please Select File to Upload');
		
			ImgUploadDialog.frm_elts.upload_file.onchange = ImgUploadDialog.upload_file_change;
			ImgUploadDialog.frm_elts.form.target = 'imgupload_iframe_target';
		}
	},
	
	test_on_server: function() {
		if (ImgUploadDialog.jQuery_obj) {
			var json_rsp = false;
			ImgUploadDialog.jQuery_obj.ajax( {
				// Configuration: Change this to point to where the actual upload server code is
				// Both Here and in the ELSE part of this function
				// url: ImgUploadDialog.plugin_url + '/upload-file.php',
				url: '/ajax/json/imgupload-service.php',
				type: 'POST',
				dataType: 'json',
				async: false,
				data: {
					 imgupload_command:'test',
					 imgupload_upload_file: ImgUploadDialog.frm_elts.upload_file.val(),
					 imgupload_dest_fname: ImgUploadDialog.frm_elts.dest_fname.val(),
					},
				success: function(data, textStatus, xmlreq) {
					json_rsp = data;
				},
				// complete: function(xmlreq, textStatus) {
				// 	alert('ajax complete response: ' + textStatus);
				// },
				error: function(xmlreq, textStatus, errorThrown) {
					// alert('ajax error response: ' + textStatus + ':' + xmlreq.statusText);
					json_rsp = {result:'failure', result_code: 'ajax_failure', explanation: xmlreq.statusText}
				}
			});
			
			// str = '';
			// for (x in json_rsp) {
			// 	str += x + ': ' + json_rsp[x] + ' / ';
			// }
			// alert(str);
			// adjust form on basis of test result
			if (json_rsp) {
				switch (json_rsp.result) {
					case 'success':
						switch (json_rsp.result_code) {
							case 'dest_file_exists':
								ImgUploadDialog.frm_elts.do_button.val(tinyMCEPopup.editor.getLang('imgupload_dlg.replace'));
								ImgUploadDialog.frm_elts.message_target.html('<span class="imgupload_warning">Warning: Destination File Will Be Overwritten</span>');
							break;
							case 'dest_file_not_exist':
								ImgUploadDialog.frm_elts.do_button.val(tinyMCEPopup.editor.getLang('imgupload_dlg.upload'));
								ImgUploadDialog.frm_elts.message_target.html('<span class="imgupload_ok">Click to Upload File</span>');
							break;
							default:
							ImgUploadDialog.frm_elts.message_target.html('<span class="imgupload_warning">Error: illegal result code:'
								+ tinyMCEPopup.editor.getLang('imgupload_dlg.' + json_rsp.result_code)
								+ (json_rsp.explanation ? ' ( ' + json_rsp.explanation + ' )' : '')
 								+ "</span>");
						}
						ImgUploadDialog.frm_elts.dest_fname_wrapper.css('visibility', 'visible');
						ImgUploadDialog.frm_elts.do_button
							.css('visibility', 'visible')
							.click(ImgUploadDialog.upload);
						ImgUploadDialog.frm_elts.form.submit(ImgUploadDialog.upload);
						ImgUploadDialog.frm_elts.dest_fname
							.change(ImgUploadDialog.dest_fname_change)
							.focus()
							.val(json_rsp.dest_fname);
						break;
					case 'failure':
						ImgUploadDialog.frm_elts.message_target.html('<span class="imgupload_error">Error: '
							+ tinyMCEPopup.editor.getLang('imgupload_dlg.' + json_rsp.result_code)
							+ (json_rsp.explanation ? ' ( ' + json_rsp.explanation + ' )' : '')
							+ '</span>');
						ImgUploadDialog.frm_elts.dest_fname_wrapper.css('visibility', 'hidden');
						ImgUploadDialog.frm_elts.dest_fname.unbind();
						ImgUploadDialog.frm_elts.upload_file.focus();
						ImgUploadDialog.frm_elts.do_button
							.css('visibility', 'hidden')
							.unbind();
						ImgUploadDialog.frm_elts.do_button.onclick = null;
						ImgUploadDialog.frm_elts.form.unbind();
						break;
				}
			} else {
				
			}
			
			// return test result
			return json_rsp;
		} else {  // no jQuery
			// stolen from w3schools.com/ajax/ajax_xmlhttprequest_create.asp
			// True side is for for IE7+, Firefox, Chrome, Opera, Safari; false for IE <= 6
			var xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
			var json_rsp = null;
			xmlhttp.ontimeout = function() {
					json_rsp = 'timeout';
				}
			// open a synchronous, POST request
			// Configuration: Change this to point to where the actual upload server code is
			// Both Here and in the IF part of this function
			// xmlhttp.open("POST", ImgUploadDialog.plugin_url + '/upload-file.php', false);
			xmlhttp.open("POST", '/ajax/json/imgupload-service.php', false);
			xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xmlhttp.send(encodeURI('imgupload_command=test'
					+ '&imgupload_upload_file=' + ImgUploadDialog.frm_elts.upload_file.value
					+ '&imgupload_dest_fname=' + ImgUploadDialog.frm_elts.dest_fname.value)
			);
			json_rsp = json_rsp !== 'timeout' ? tinymce.util.JSON.parse(xmlhttp.responseText)
				: {result:'failure', result_code: 'ajax_timeout'};

			// adjust form on basis of test result
			if (json_rsp) {
				switch (json_rsp.result) {
					case 'success':
						switch (json_rsp.result_code) {
							case 'dest_file_exists':
								ImgUploadDialog.frm_elts.do_button.value = tinyMCEPopup.editor.getLang('imgupload_dlg.replace');
								ImgUploadDialog.frm_elts.message_target.innerHTML = '<span class="imgupload_warning">Warning: Destination File Will Be Overwritten</span>';
							break;
							case 'dest_file_not_exist':
								ImgUploadDialog.frm_elts.do_button.value = tinyMCEPopup.editor.getLang('imgupload_dlg.upload');
								ImgUploadDialog.frm_elts.message_target.innerHTML = '<span class="imgupload_ok">Click to Upload File</span>';
							break;
							default:
							ImgUploadDialog.frm_elts.message_target.innerHTML = '<span class="imgupload_warning">Error: illegal result code:'
								+ tinyMCEPopup.editor.getLang('imgupload_dlg.' + json_rsp.result_code)
								+ (json_rsp.explanation ? ' ( ' + json_rsp.explanation + ' )' : '')
 								+ "</span>";
						}
						ImgUploadDialog.frm_elts.dest_fname_wrapper.style.visibility = 'visible';
						ImgUploadDialog.frm_elts.do_button.style.visibility = 'visible';
						ImgUploadDialog.frm_elts.do_button.onclick = ImgUploadDialog.upload;
						ImgUploadDialog.frm_elts.dest_fname.value = json_rsp.dest_fname;
						ImgUploadDialog.frm_elts.dest_fname.focus();
						ImgUploadDialog.frm_elts.dest_fname.onchange = ImgUploadDialog.dest_fname_change;
						ImgUploadDialog.frm_elts.form.onsubmit = ImgUploadDialog.upload;
						break;
					case 'failure':
						ImgUploadDialog.frm_elts.message_target.innerHTML = '<span class="imgupload_error">Error: '
							+ tinyMCEPopup.editor.getLang('imgupload_dlg.' + json_rsp.result_code)
							+ (json_rsp.explanation ? ' ( ' + json_rsp.explanation + ' )' : '')
							+ '</span>';
						ImgUploadDialog.frm_elts.dest_fname_wrapper.style.visibility = 'hidden';
						ImgUploadDialog.frm_elts.dest_fname.onchange = null;
						ImgUploadDialog.frm_elts.upload_file.focus();
						ImgUploadDialog.frm_elts.do_button.style.visibility = 'hidden';
						ImgUploadDialog.frm_elts.do_button.onclick = null;
						ImgUploadDialog.frm_elts.form.onsubmit = null;
						break;
				}
			}
			
			// return test result
			return json_rsp;
		}
	},

	upload_file_change : function() {
		if (ImgUploadDialog.jQuery_obj) {
			ImgUploadDialog.frm_elts.dest_fname
			.val(ImgUploadDialog.frm_elts.upload_file.val());
		} else {
			ImgUploadDialog.frm_elts.dest_fname.value = ImgUploadDialog.frm_elts.upload_file.value;
		}

		// common code
		ImgUploadDialog.test_on_server();
	},
	
	dest_fname_change : function() {
		ImgUploadDialog.test_on_server();
	},

	upload : function() {
		if (ImgUploadDialog.jQuery_obj) {
			// jquery version
			ImgUploadDialog.frm_elts.iframe_target.load(ImgUploadDialog.upload_finished);
			ImgUploadDialog.frm_elts.form.unbind().submit();
			ImgUploadDialog.frm_elts.message_target.html('<span class="imgupload_info">'
				+ tinyMCEPopup.editor.getLang('imgupload_dlg.uploading')
				+ ': ' + ImgUploadDialog.frm_elts.upload_file.val()
				+ ' -> ' + ImgUploadDialog.frm_elts.dest_fname.val()
				+ '</span>');

			// shut down interactivity with form
			ImgUploadDialog.frm_elts.upload_file_wrapper.css('display', 'none');
			ImgUploadDialog.frm_elts.dest_fname_wrapper.css('display', 'none');
			ImgUploadDialog.frm_elts.do_button.unbind().css('visibility', 'hidden');
			ImgUploadDialog.frm_elts.cancel_button.unbind().css('display', 'none');
			return false;
		} else {
			// straight javascript
			ImgUploadDialog.frm_elts.iframe_target.onload = ImgUploadDialog.upload_finished;
			ImgUploadDialog.frm_elts.form.onsubmit = null;
			ImgUploadDialog.frm_elts.form.submit();

			ImgUploadDialog.frm_elts.message_target.innerHTML = '<span class="imgupload_info">'
				+ tinyMCEPopup.editor.getLang('imgupload_dlg.uploading')
				+ ': ' + ImgUploadDialog.frm_elts.upload_file.value
				+ ' -> ' + ImgUploadDialog.frm_elts.dest_fname.value
				+ '</span>';
			ImgUploadDialog.frm_elts.upload_file_wrapper.style.display = 'none';
			ImgUploadDialog.frm_elts.dest_fname_wrapper.style.display = 'none';
			ImgUploadDialog.frm_elts.do_button.onclick = null;
			ImgUploadDialog.frm_elts.do_button.style.visibility = 'hidden';
			ImgUploadDialog.frm_elts.cancel_button.onclick = null;
			ImgUploadDialog.frm_elts.cancel_button.style.display = 'none';
			return false;
		}
	},
	
	upload_finished : function() {
		if (ImgUploadDialog.jQuery_obj) {
			// get DOM element of iframe_target
			var itmp = ImgUploadDialog.frm_elts.iframe_target.get()[0];
			var iframe_target_document = ImgUploadDialog.jQuery_obj('#imgupload_iframe_target', document);
			
			// borrowed/stolen from http://www.webtoolkit.info/ajax-file-upload.html
			// var i = document.getElementById(id);
			if (itmp.contentDocument) {
				var d = itmp.contentDocument;
			} else if (itmp.contentWindow) {
				var d = itmp.contentWindow.document;
			} else {
				var d = window.frames[0].document;
			}
			// if (d.location.href == "about:blank") {
			// 	return;
			// }

			// if (typeof(itmp.onComplete) == 'function') {
			// 	i.onComplete(d.body.innerHTML);
			// 	"foo bar";
			// }
			

			// iframe_target.unbind();
			ImgUploadDialog.frm_elts.iframe_target.unbind();
			// the following binding doesn't work. Why? I don't know
			// ImgUploadDialog.frm_elts.form
			// 	.unbind()
			// 	.keypress(ImgUploadDialog.user_finished)
			// 	.submit(ImgUploadDialog.user_finished);
			ImgUploadDialog.frm_elts.do_button.css('visibility', 'visible').val('Close');
			ImgUploadDialog.frm_elts.do_button
				.unbind()
				.click(ImgUploadDialog.user_finished)
				.submit(ImgUploadDialog.user_finished);
			ImgUploadDialog.frm_elts.message_target.html(d.body.innerHTML);
		} else {
			// straight javascript
			// var f = document.getElementById('imgupload_upload_form');
			document.getElementById('imgupload_upload_form').onsubmit = ImgUploadDialog.user_finished;
			document.getElementById('imgupload_iframe_target').onload = null;
			var do_button = document.getElementById('imgupload_do_button');
			do_button.style.visibility = 'visible';
			do_button.onclick = ImgUploadDialog.user_finished;
			do_button.onsubmit = ImgUploadDialog.user_finished;
			do_button.value = 'Close';

			document.getElementById('imgupload_cancel_button').style.display = 'none';
			
			var itmp = document.getElementById('imgupload_iframe_target');
			if (itmp.contentDocument) {
				var d = itmp.contentDocument;
			} else if (itmp.contentWindow) {
				var d = itmp.contentWindow.document;
			} else {
				var d = window.frames[0].document;
			}
			var message_target = document.getElementById('imgupload_message_target');
			message_target.innerHTML = d.body.innerHTML;
		}
		
		return false;
	},
	
	user_finished: function () {
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(ImgUploadDialog.init, ImgUploadDialog);
