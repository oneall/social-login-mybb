(function($) {
	$(function() {
		$(document).ready(function($) {

			/* Autodetect API Connection Handler */
			$('#oa_social_login_autodetect_api_connection_handler').click(function() {
				var button = this;
				if ($(button).hasClass('working') === false) {
					$(button).addClass('working');

					var message_string;
					var message_container;

					message_container = $('#oa_social_login_api_connection_handler_result');
					message_container.removeClass('success_message error_message').addClass('working_message');
					message_container.html(oneall_social_login_lang.loading);

					$.get(window.location.href, {'oa_social_login_task': 'autodetect'}, function(response_string) {
					    
					    /* Not logged in */
					    if(response_string.indexOf('value="login"') != -1) {
					        location.reload(); 
					    }
					    
						var response_parts = response_string.split('|');
						var response_status = response_parts[0];
						var response_flag = response_parts[1];
						var response_text = response_parts[2];

						/* CURL/FSOCKOPEN Selects Box */
						var select_connection_handler = jQuery("#setting_oa_social_login_connection_handler");
	                    var select_connection_port = jQuery("#setting_oa_social_login_connection_port");

						/* CURL detected, HTTPS */
						if (response_flag == "curl_443") {
						    select_connection_handler.val("cr");
	                        select_connection_port.val("443");
						}
						/* CURL detected, HTTP */
						else if (response_flag == "curl_80") {
						    select_connection_handler.val("cr");
	                        select_connection_port.val("80");
						}										
						/* FSOCKOPEN detected, HTTPS */
						else if (response_flag == "fsockopen_443") {
						    select_connection_handler.val("fr");
	                        select_connection_port.val("443");
						}
						/* FSOCKOPEN detected, HTTP */
						else if (response_flag == "fsockopen_80") {
						    select_connection_handler.val("fr");
	                        select_connection_port.val("80");
						}
					
						message_container.removeClass("working_message");
						message_container.html(response_text);

						if (response_status == "success") {
							message_container.addClass("success_message");
						} else {
							message_container.addClass("error_message");
						}
						$(button).removeClass("working");
					});
				}
				return false;
			});

			/* Verify API Settings */
			$('#oa_social_login_test_api_settings').click(function() {
				var button = this;
				if ($(button).hasClass('working') === false) {
					$(button).addClass('working');
					
					var message_string;
					var message_container;

				    var handler = ($("#setting_oa_social_login_connection_handler").val() == 'fs' ? 'fs' : 'cr');
				    var port = ($("#setting_oa_social_login_connection_port").val() == 443 ? 443 : 80);

					var subdomain = $('#setting_oa_social_login_subdomain').val();
					var key = $('#setting_oa_social_login_public_key').val();
					var secret = $('#setting_oa_social_login_private_key').val();
	
					var data = {
					  'api_subdomain' : subdomain,
					  'api_key' : key,
					  'api_secret' : secret,
					  'api_connection_port': port,
					  'api_connection_handler' : handler,
					  'oa_social_login_task' : 'verify'
					};

					message_container = $('#oa_social_login_api_test_result');
					message_container.removeClass('success_message error_message').addClass('working_message');
					message_container.html(oneall_social_login_lang.loading);

					$.post(window.location.href, data, function(response_string) {
					    
					    /* Not logged in */
                        if(response_string.indexOf('value="login"') != -1) {
                            location.reload(); 
                        }

						var response_parts = response_string.split('|');
						var response_status = response_parts[0];
						var response_text = response_parts[1];

						message_container.removeClass('working_message');
						message_container.html(response_text);

						if (response_status == "success") {
							message_container.addClass('success_message');
						} else {
							message_container.addClass('error_message');
						}
						$(button).removeClass('working');
					});
				}
				return false;
			});
		});
	});
})(jQuery);
