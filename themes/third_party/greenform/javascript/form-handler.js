/**
 * Author: Lee Langley
 * Date Created: 08/03/2012 13:02
 */

if(typeof jQuery != 'undefined'){
	(function($){
		/**
		 * Object for basic form validation techniques
		 */
		var Validation = function(){
			this.isAlpha = function(strVal){
				var strPattern = /^[a-z]+$/i;
				return (typeof strVal == 'string') && strPattern.test(strVal);
			};

			this.isAlphaNumeric = function(strVal){
				var strPattern = /^[a-z0-9]+$/i;
				return (typeof strVal == 'string') && strPattern.test(strVal);
			};

			this.isAlphaDash = function(strVal){
				var strPattern = /^[a-z0-9\-_]+$/i;
				return (typeof strVal == 'string') && strPattern.test(strVal);
			};

			this.isNumeric = function(strVal){
				return !isNaN(parseFloat(strVal)) && isFinite(strVal);
			};

			this.isInt = function(strVal){
				var strPattern = /^\-?[0-9]+$/i;
				return (typeof strVal == 'string') && strPattern.test(strVal);
			};

			this.isSame = function(strVal1, strVal2){
				return strVal1 == strVal2;
			};

			this.isEmail = function(strEmail){
				var strPattern = /^[A-Z0-9._+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i;
				return (typeof strEmail == 'string') && strPattern.test(strEmail);
			};

			this.isEmails = function(strEmails){
				var bolValid = true;
				$.each(strEmails.split(','), function(i, e){
					if(!this.isEmail(e)){
						bolValid = false;
						return false;
					}
				});
				return bolValid;
			};

			this.isIP = function(strVal){
				var strPattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/i;
				return (typeof strVal == 'string') && strPattern.test(strVal);
			};

		};

		/**
		 * Object for language strings
		 */
		var Language = function(){
			// define the language lines
			this.lines = (typeof validateLanguage == 'object') ? $.extend(validateLanguage, {}) : {};

			/**
			 * takes a line 'name' and returns the value.
			 * If the line isn't found and empty string is returned.
			 *
			 * Any extra arguments defined are used in a sprintf style replacement of the string.
			 *
			 * @param line
			 */
			this.getLine = function(line){
				if((typeof line == 'string') && (typeof this.lines[line] == 'string')){
					// language line found
					if(arguments.length > 1){
						// some replacement variables were found

						var args = [];
						// define the first argument as the language line, to be processed
						args[0] = this.lines[line];
						// loop through the arguments and add them to the list
						for(i = 1; i < arguments.length; i++){
							if($.isArray(arguments[i])){
								// the argument is an array - loop through and add each element as a separate argument
								$.each(arguments[i], function(){
									args[args.length] = this;
								});
							}else{
								// the argument isn't an array - add it to the list
								args[args.length] = arguments[i];
							}
						}

						// return the replaced string
						return sprintf.apply(this, args);
					}else{
						// no arguments specified - return the string as-is
						return this.lines[line];
					}
				}else{
					// the language line wasn't found
					return '';
				}
			}
		};

		// define the variables
		var objForm = $('#' + strFormID),																		// the form element
			errorBox = (typeof errorBoxClass == 'string') ? objForm.find('.' + errorBoxClass) : null,			// the element that holds error messages
			hasErrorBox = (errorBox !== null) && (errorBox.length > 0),											// boolean flag whether the error box exists or not
			msgBox = (typeof successBoxClass == 'string') ? objForm.find('.' + successBoxClass) : null,			// the element that hold success messages
			hasMessageBox = (msgBox !== null) && (msgBox.length > 0),											// boolean flag whether the message box exists or not
			errorClass = (typeof errorClass == 'string') ? errorClass : 'error',								// the class to assign input fields with errors
			fileInputDataClass = 'fileList',																	// the class to apply to file input data boxes
			objLanguage = new Language(),																		// the language object
			validation = new Validation(),																		// the validation object
			maxSize = (typeof formMaxSize != 'undefined') ? formMaxSize : $('input[name=MAX_FILE_SIZE]').val();	// the maximum file upload size, in bytes
			maxSize = maxSize ? parseInt(maxSize) : null;

		/**
		 * Adds and carries out the form submit catching
		 */
		function catchSubmit(){
			// ensure that the submit button is enabled (if the page was refreshed when the button was disabled, some browsers remember it's disabled state)
			objForm.find('input[type=submit]').removeAttr('disabled');

			// check if we are validating/submutting the form via ajax
			if((typeof formValidate == 'boolean') && (formValidate === true) && (typeof strFormID == 'string') && $.isArray(ruleList) && (ruleList.length > 0)){
				// we need to carry out Javascript form validation

				/**
				 * Catch the submission of the form
				 */
				objForm.off('submit');	// remove the current onsubmit event (if one exists)
				objForm.on('submit', function(){
					var errorList = {};	// contains a list of errors

					if(hasErrorBox){
						errorBox.empty();
					}
					objForm.find('input, textarea, select').removeClass(errorClass);

					// loop through the validation rules and check the input fields
					$.each(ruleList, function(i, e){
						var objInput = objForm.find('input[name=' + e.field + '], textarea[name=' + e.field + '], select[name=' + e.field + ']'),
							bolIsSet = ((objInput.length > 0) && ($.trim(objInput.val()) != ''));

						$.each(e.rules.split('|'), function(i, rule){
							rule = rule.toLowerCase();
							switch(rule){
								case 'required':
									if(!bolIsSet){
										errorList[e.field] = objLanguage.getLine('validation_required', e.label);
									}
								break;
								case 'valid_email':
									if(bolIsSet && !validation.isEmail(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_email', e.label);
									}
								break;
								case 'valid_emails':
									if(bolIsSet && !validation.isEmails(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_email', e.label);
									}
								break;
								case 'alpha':
									if(bolIsSet && !validation.isAlpha(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_alpha', e.label);
									}
								break;
								case 'alpha_numeric':
									if(bolIsSet && !validation.isAlphaNumeric(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_alpha_numeric', e.label);
									}
								break;
								case 'alpha_dash':
									if(bolIsSet && !validation.isAlphaDash(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_alpha_dash', e.label);
									}
								break;
								case 'numeric':
									if(bolIsSet && !validation.isNumeric(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_numeric', e.label);
									}
								break;
								case 'integer':
									if(bolIsSet && !validation.isInt(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_integer', e.label);
									}
								break;
								case 'valid_ip':
									if(bolIsSet && !validation.isIP(objInput.val())){
										errorList[e.field] = objLanguage.getLine('validation_ip', e.label);
									}
								break;
								default:
									var matches = null;
									if((matches = rule.match(new RegExp(/^(min|max|exact)_length\[([\d]+)\]$/))) !== null){
										if(bolIsSet && (((matches[1] == 'min') && (objInput.val().length < matches[2])) || ((matches[1] == 'max') && (objInput.val().length > matches[2])) || ((matches[1] == 'exact') && (objInput.val().length != matches[2])))){
											errorList[e.field] = objLanguage.getLine('validation_length', e.label, ((matches[1] == 'min') ? 'more than' : ((matches[1] == 'max') ? 'less than' : 'equal too')), matches[2]);
										}
									}else if((matches = rule.match(new RegExp(/^(greater|less)_than\[([\d]+)\]$/))) !== null){
										if(bolIsSet && (!validation.isNumeric(objInput.val()) || (((matches[1] == 'greater') && (parseInt(objInput.val()) < matches[2])) || ((matches[1] == 'less') && (parseInt(objInput.val()) > matches[2]))))){
											errorList[e.field] = objLanguage.getLine('validation_numeric_value', e.label, ((matches[1] == 'greater') ? 'greater than' : 'less than'), matches[2]);
										}
									}else if((matches = rule.match(new RegExp(/^matches\[([^\]]+)\]$/))) !== null){
										if(bolIsSet && !validation.isSame(objInput.val(), objForm.find('input[name=' + matches[1] + '], textarea[name=' + matches[1] + '], select[name=' + matches[1] + ']').val())){
											errorList[e.field] = objLanguage.getLine('validation_match', e.label, matches[1]);
										}
									}
								break;
							}
						});
					});

					if($.isEmptyObject(errorList)){
						return (formCatch === true) ? submitForm() : true;
					}else{
						var arrErrors = [];
						$.each(errorList, function(field, error){
							objForm.find('input[name=' + field + '], textarea[name=' + field + '], select[name=' + field + ']').addClass(errorClass);
							arrErrors[arrErrors.length] = error;
						});
						displayErrors(arrErrors);

						reloadCaptcha();

						return false;
					}
				});
			}else if((typeof formCatch == 'boolean') && (formCatch === true) && (typeof strFormID == 'string')){
				// no form validation, but we are submitting the form via ajax
				$('#' + strFormID).on('submit', function(){
					return submitForm();
				});
			}
		}

		/**
		 * Carries out Ajax submission of the form
		 */
		function submitForm(){
			if(hasErrorBox){
				errorBox.stop(true, true).hide().empty();
			}
			if(hasMessageBox){
				msgBox.stop(true, true).hide().empty();
			}

			if(objForm.find('input[type=file]').length > 0){
				// file input exist - upload them, then post the form

				// define the unique ID for the upload
				var uploadID = $('input#uploadID').val();
				if(uploadID == ''){
					// no ID is set
					uploadID = new Date().getTime();
				}


				var strUploadProgressClass = 'uploadProgress',									// the class to attach to the upload progress display box
					strUploadProgressHTML = '<div class="' + strUploadProgressClass + '">' +	// string contains the markup for displaying the upload progress information
							'<div class="info">' +
								'<div class="data">' +
									'<span class="uploaded">0</span> of <span class="total">0</span> - <span class="average">0</span>/sec' +
								'</div>' +
								'<span class="est_time">0</span> remaining' +
							'</div>' +
							'<div class="barHolder">' +
								'<div class="progressBar"></div>' +
							'</div>' +
						'</div>';

				objForm.off('submit');	// remove the current onsubmit event (if one exists)
				objForm.uploadProgress(	// define the file upload properties
					{
						progressURL:objForm.attr('action'),
						//debugDisplay:'#debugDisplay',	// uncomment for debugging
						displayFields:['total', 'uploaded', 'average', 'est_time'],
						targetUploader:'jqUploader',
						progressDisplay:'.' + strUploadProgressClass,
						progressMeter:'.progressBar',
						id:uploadID,
						updateDelay:100,
						html5:true,	// uncomment to NOT use HTML5
						start:function(){
							// check for any file errors before proceeding with the upload
							// only in HTML5
							if(maxSize){
								var errorList = [];
								objForm.find('input[type=file]').each(function(){
									var objInput = $(this),
										files = this.files;
									if(files && files.length){
										for(var i = 0, f; f = files[i]; i++){
											// get the filesize
											var fileSize = parseFloat(f.size);

											// check that the file size isn't over our max
											if(maxSize && (maxSize < fileSize)){
												// the file breaches our max size limit
												errorList[errorList.length] = objLanguage.getLine('validation_file_too_large', f.name);
											}
										}
									}
								});
							}

							if(maxSize && (errorList.length > 0)){
								// some file errors exist
								displayErrors(errorList);

								// cancel the upload
								return false;
							}else{
								// no file errors exist - carry on

								// get a jQuery object of the upload progress HTML
								var objProgressDisplay = $(strUploadProgressHTML);

								// remove any old progress upload box that may exist
								objForm.find('.' + strUploadProgressClass).remove();

								objForm
										.prepend('<input type="hidden" name="fileUploadAjax" value="1" id="fileUploadAjax">')	// add the input to mark it as a file upload
										// find the submit button
										.find('input[type=submit]')
											.attr('disabled', true)																// disable the submit button
											.before(objProgressDisplay);														// add the progress display box
								// hide the progress display box and fade it in
								objProgressDisplay.hide().fadeIn('fast');
							}
						},
						success:function(options, data){
							// get the upload response - this gives us an array of the $_FILE variable from the upload
							$('iframe#jqUploader').remove();							// remove the IFrame, if it exists
							objForm.find('#fileUploadAjax').remove();					// remove the input, if it exists
							objForm.find('input[type=submit]').removeAttr('disabled');	// re-enable the submit button

							if(!$.isEmptyObject(data)){
								// data is valid - post the form via Ajax
								submitPost(data);
							}else{
								// data is invalid
								displayErrors(objLanguage.getLine('error_parse'));
								reloadCaptcha();
							}

							// reset the submit functionality, because uploadProgress overwrites it
							catchSubmit();
						},
						failed:function(data){
							$('iframe#jqUploader').remove();							// remove the IFrame, if it exists
							objForm.find('#fileUploadAjax').remove();					// remove the input, if it exists
							objForm.find('input[type=submit]').removeAttr('disabled');	// re-enable the submit button

							// define the error message
							displayErrors(data.error || objLanguage.getLine('error_file_upload'));
							reloadCaptcha();

							// reset the submit functionality, because uploadProgress overwrites it
							catchSubmit();
						}
					}
				);

				// call the form submission, which triggers the file upload
				objForm.submit();
			}else{
				// no file inputs exist - just post the form
				submitPost();
			}

			return false;
		}

		/**
		 * This actually carries out the form post via Ajax
		 *
		 * @param fileUploads
		 */
		function submitPost(fileUploads){
			// define the variables to post
			var postVariables = '';
			if((typeof fileUploads != 'undefined') && (fileUploads !== null)){
				// some file uploads have been specified - add them to the post variables
				$.each(fileUploads, function(i, file){
					$.each(file, function(key, val){
						postVariables += 'CUSTOM_UPLOAD_FILES[' + i + '][' + encodeURIComponent(key) + ']=' + encodeURIComponent(val) + '&';
					});
					postVariables += 'CUSTOM_UPLOAD_FILES[' + i + '][label]=&';
				});
			}
			postVariables += objForm.serialize();

			$.post(objForm.attr('action'), postVariables, function(data){
				if(hasErrorBox){
					errorBox.stop(true, true).hide().empty();
				}
				if(hasMessageBox){
					msgBox.stop(true, true).hide().empty();
				}

				if((typeof data == 'undefined') || (data == '')){
					// an un-known error has occurred
					displayErrors(objLanguage.getLine('error_unknown'));
				}else{
					var dataDecoded = {};
					try{
						dataDecoded = $.parseJSON(data);
					}catch(e){
						dataDecoded = {
							status:'error',
							returnHTML:[]
						};
					}

					if(dataDecoded.status == 'error'){
						// an error has occurred

						// try and get the error message
						var objErrors = $(dataDecoded.returnHTML),
							mixErrors = null;
						if(objErrors.length == 0){
							// no error message found so output a default one
							mixErrors = objLanguage.getLine('error_general');
						}else if(objErrors.find('li').length > 0){
							mixErrors = [];
							$(objErrors.find('li')).each(function(){
								mixErrors[mixErrors.length] = $(this).text();
							});
						}else{
							mixErrors = objErrors;
						}
						displayErrors(mixErrors);
					}else{
						// success
						var successMessage = $(dataDecoded.returnHTML);

						if(hasMessageBox){
							msgBox.html(successMessage).fadeIn('slow');
						}else{
							alert(successMessage.text());
						}
						objForm.get(0).reset();	// clear the form

						// call the showFileInfo function, to hide the file data
						showFileInfo(objForm.find('input[type=file]'));
					}
				}

				reloadCaptcha();
			});
		}

		/**
		 * Attempts to reload ReCaptcha, if it exists
		 */
		function reloadCaptcha(){
			try{
				Recaptcha.reload();
			}catch(e){}
		}

		/**
		 * Displays the information of selected files for a file input field.
		 * ObjInputs can be a single file input or JQuery selection of file inputs.
		 * Only available in HTML5.
		 *
		 * @param objInputs
		 * @param bolAnimate
		 */
		function showFileInfo(objInputs, bolAnimate){
			objInputs = (objInputs instanceof jQuery) ? objInputs : $(objInputs);
			bolAnimate = !(bolAnimate === false);

			objInputs.each(function(){
				var objInput = $(this),
					files = this.files,
					objListBox = objInput.siblings('.' + fileInputDataClass);

				if(files && files.length){
					if(objListBox.length == 0){
						// no data box - add one
						objListBox = $('<div class="' + fileInputDataClass + '"><ul></ul></div>');

						var objDataBox = objInput.siblings('.fileData:first');
						if(objDataBox.length > 0){
							// the input field has a data box (list of attributes (max size allowed file types etc)
							// append the file list after the data box
							objDataBox.after(objListBox);
						}else{
							// no data box, just append the file list
							objInput.after(objListBox);
						}
					}else{
						// data box found - empty it
						objListBox.html('<ul></ul>');
					}

					var arrDenominators = ['kb', 'mb', 'gb', 'tb'],
						fileAnimSpeed = 600;

					// loop through each file for this input and display it's information
					for(var i = 0, f; f = files[i]; i++){
						var fileSize = parseFloat(f.size),	// the file size in bytes
							denominator = 'b',				// the file size denominator
							arrErrors = [];					// holds a list of any errors for the file

						// check that the file size isn't over our max
						if(maxSize && (maxSize < fileSize)){
							// the file breaches our max size limit
							arrErrors[arrErrors.length] = objLanguage.getLine('validation_file_too_large', f.name);
						}

						// get the file size in the biggest denominator
						for(var d = 0; d < arrDenominators.length; d++){
							if((fileSize / 1024) >= 1){
								fileSize = fileSize / 1024;
								denominator = arrDenominators[d];
							}else{
								d = arrDenominators.length;
							}
						}

						// output the file information
						var strClass = (f.type ? htmlentities(f.type.toLowerCase().replace(/\//g, ' ').replace(/\./g, '-')) : 'unknown') + ((arrErrors.length > 0) ? ' error' : ''),
							objData = $('<li class="' + strClass + '">' +
							'<span class="fileInfo">' +
								'<strong>' + htmlentities(f.name) + '</strong>' +
								(f.type ? ' (' + htmlentities(f.type) + ')' : '') +
								' - ' + fileSize.toFixed(2).replace(/\.00$/, '') + denominator +
							'</span>' +
						'</li>');

						// check for errors
						if(arrErrors.length > 0){
							// errors exist for this file - append the error list
							var errorHTML = '';
							for(var j = 0; j < arrErrors.length; j++){
								errorHTML += '<li>' + arrErrors[j] + '</li>';
							}
							objData.append('<span class="errorInfo"><ul>' + errorHTML + '</ul></span>');
						}

						// add the data to the page
						objListBox.children('ul').append(objData);
						if(bolAnimate){
							objData.hide().delay((fileAnimSpeed-100)*i).fadeIn(fileAnimSpeed);
						}
					}

					if(objListBox.children('ul').is(':empty')){
						// no files were added
						objInput.val('');		// empty the input field
						objListBox.remove();	// remove the data box
					}
				}else{
					// no file has been selected - remove the box
					objListBox.stop(true).show().slideUp(function(){
						$(this).remove()
					});
				}
			});
		}

		/**
		 * Converts a string to it's html entity equivalents
		 * 
		 * @param str
		 */
		function htmlentities(str){
			return $('<div/>').text(str).html();
		}

		function displayErrors(arrErrors){
			arrErrors = (typeof arrErrors == 'string') ? [arrErrors] : arrErrors;

			if(hasErrorBox){
				// add the error message
				var strMessage = '<h1>' + objLanguage.getLine('heading_error') + '</h1><ul>';
				$.each(arrErrors, function(){
					strMessage += '<li>' + this + '</li>';
				});
				strMessage += '</ul>';
				$(errorBox).stop(true, true).hide().html(strMessage).fadeIn('slow');
			}else{
				alert(objLanguage.getLine('heading_error') + '\n\n' + arrErrors.join('\n'));
			}
		}

		/**
		 * Handles HTML5 file info on selection of file to upload
		 */
		objForm.find('input[type=file]')
			// displays any relevant file info on page load
			.each(function(){
				showFileInfo(this, false);
			})
			// displays file info when a file is selected
			.on('change', function(event){
				showFileInfo(event.target);
			});

		// run the form functionality
		catchSubmit();
	})(jQuery);
}