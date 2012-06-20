/**
 * Author: Lee Langley
 * Date Created: 13/03/2012 12:57
 */

$(document).ready(function(){
	/**
	 * Add the sorting/searching functionality to the tables
	 */
	$('.sortable table.mainTable').attr('id', 'sortMe').dataTable({
		bJQueryUI:true,
		sPaginationType:'full_numbers',
		aaSorting:[[1,'desc']],
		oLanguage:{
			'sUrl':tableLangURL
		}
	});


	/**
	 * adds the handle for sorting the rows
	 */
	$('table#ruleTable tbody tr td:nth-child(1):not(.blank)').each(function(){
		addSortHandle(this);
	});

	/**
	 * Displays/hides the handles when hovering over/off the rows
	 */
	$('table#ruleTable tbody').on('hover', 'tr', function(event){
		if(event.type == 'mouseenter'){
			$(this).find('.sort').stop(true).animate({width:'20px', left:'-30px'}, 200);
		}else{
			$(this).find('.sort').stop(true).animate({width:'8px', left:'-18px'}, 200);
		}
	});

	/**
	 * Add the sortable functionality to the rule set input fields
	 */
	$('table#ruleTable tbody').sortable({
		axis:'y',
		cancel:'input,select,textarea,button',
		containment:'parent',
		cursor:'pointer',
		cursorAt:{left:50},
		handle:'.sort',
		// cause the helper to be a clone of the original -
		// then set the cell widths so that it doesn't shrink
		helper:function(e, obj){
			var originals = obj.children(),
				helper = obj.clone();
			helper.children().each(function(index){
				// Set helper cell sizes to match the original sizes
				$(this).width(originals.eq(index).width())
			});
			return helper;
		},
		items:'> tr',
		opacity:0.8,
		revert:true,
		scrollSensitivity:100,
		scrollSpeed:20,
		tolerance:'pointer'
	});


	/**
	 * handles flagging submissions as read/unread
	 */
	$('table.mainTable .flagRead input[type=checkbox]').on('click', function(){
		var objThis = $(this),
			bolIsRead = objThis.is(':checked'),
			strPost = '';

		if(bolIsRead){
			// the submission has been flagged as read
			objThis.parents('tr:first').addClass('read');
			strPost += 'status=read';
		}else{
			// the submission has been flagged as unread
			objThis.parents('tr:first')
				.removeClass('read')
				.find('> td')
					.removeClass('read');
			strPost += 'status=unread';
		}
		strPost += '&' + objThis.attr('name') + '=' + objThis.val();
		strPost += '&single=1';
		strPost += '&XID=' + $('input[name=XID]').val();

		$.post(objThis.parents('form:first').attr('action'), strPost);
	});


	// shows/hides password field values
	$('table.mainTable .passwordBox').each(function(){
		var objBox = $(this),
			password = objBox.text(),
			mask = '';

		for(var i = 0; i < password.length; i++){
			mask += '&bull;';
		}

		objBox
			.html(mask)						// set the password to the mask
			.css('font-size', '13px')		// ensure that the field text is invisible
			.attr('title', 'Click to view')	// define the title
			.on({							// set the onclick event
				click:function(){
					if(objBox.text() == password){
						// the password is already visible, hide it
						objBox.html(mask).attr('title', 'Click to view');
					}else{
						// the password is not visible - show it
						objBox.text(password).attr('title', 'Click to hide');
					}
				}
			});
	});

	var validationForm = $('form#validationForm'),			// form object
		ruleTable = validationForm.find('table#ruleTable'),	// rule table object
		tableBody = ruleTable.find('tbody'),				// table body (if exists)
		bolHasBody = tableBody.length > 0,					// boolean whether the table body exists
		rowEmptyClass = 'blank',							// class to assign blank rows
		rowDeleteClass = 'delete',							// class to assign to rows marked for deletion
		inputErrorClass = 'error',							// class to assign to erroneous areas
		bolIsAdding = false,								// boolean - tracks whether we are in the process of adding a row (stops multiple clicks)
		rowHTML = '';										// string containing HTML for the form row

	if((validationForm.length > 0) && (ruleTable.length > 0)){
		showFieldExtras();
		validationForm.on('change', 'select.fieldType', function(){
			showFieldExtras(true);
		});

		/**
		 * Adds the onclick event for adding a rule to a validation type
		 */
		validationForm.on('click', '.addRuleBtn', function(){
			if(!bolIsAdding){
				bolIsAdding = true;

				var intRowNum = 0,
					blankRows = {length:0},
					fullRows = {length:0};
				if(bolHasBody){
					blankRows = tableBody.children('tr.' + rowEmptyClass);

					tableBody.children('tr:not(.' + rowEmptyClass + ')').each(function(){
						if($(this).children('td.' + rowEmptyClass).length > 0){
							blankRows = $.extend(blankRows, $(this));
						}else{
							intRowNum += 1;

							fullRows = $.extend(fullRows, $(this));
						}
					});
				}else{
					blankRows = ruleTable.children('tr.' + rowEmptyClass);

					ruleTable.children('tr:not(.' + rowEmptyClass + ')').each(function(){
						if($(this).children('td.' + rowEmptyClass).length > 0){
							blankRows = $.extend(blankRows, $(this));
						}else{
							intRowNum += 1;

							fullRows = $.extend(fullRows, $(this));
						}
					});
				}

				if(rowHTML == ''){
					// no HTML has been cached - load it
					$.get(ruleRowURL, function(data){
						rowHTML = data;

						// remove any currently blank rows
						blankRows.hide().remove();

						var newRow = $(rowHTML.replace(/%(1\$)?d/g, intRowNum).replace(/%([0-9]+\$)?(d|s|b)/g, ''));

						if((fullRows.length == 0) || fullRows.last().hasClass('odd')){
							newRow.removeClass('odd').addClass('even');
						}else{
							newRow.removeClass('even').addClass('odd');
						}

						if(bolHasBody){
							tableBody.append(newRow)
						}else{
							ruleTable.append(newRow);
						}
						// hide/show the 'accept' input field
						showFieldExtras();
						addSortHandle(newRow.find('td:first'));
						// now display the row
						newRow.hide().fadeIn('slow');
						bolIsAdding = false;
					}).error(function(){
						$.ee_notice(
							'There was an error collecting the row information, please try again.',
							{type:'error'}
						);
					});
				}else{
					// we already have the HTML cached

					// remove any currently blank rows
					blankRows.hide().remove();

					var newRow = $(rowHTML.replace(/%(1\$)?d/g, intRowNum).replace(/%([0-9]+\$)?(d|s|b)/g, ''));

					if((fullRows.length == 0) || fullRows.last().hasClass('odd')){
						newRow.removeClass('odd').addClass('even');
					}else{
						newRow.removeClass('even').addClass('odd');
					}

					if(bolHasBody){
						tableBody.append(newRow)
					}else{
						ruleTable.append(newRow);
					}
					// hide/show the 'accept' input field
					showFieldExtras();
					// now display the row
					newRow.hide().fadeIn('slow');
					bolIsAdding = false;
				}
			}

			return false;
		});

		/**
		 * adds the onclick event for highlighting validation rules marked for deletion
		 */
		validationForm.on('click', 'input.deleteCheck', function(){
			var objHolder = $(this).parents('tr:first'),
				objInputs = objHolder.find('input:not(.deleteCheck), textarea, select');

			if(this.checked){
				// the row has been selected for removal

				// check if any fields have been filled out in the row
				var bolIsFilled = false;
				objInputs.each(function(){
					var objInput = $(this);

					if(((objInput.attr('type') == 'checkbox') || (objInput.attr('type') == 'radio'))){
						if(objInput.is(':checked')){
							bolIsFilled = true;
							return false;
						}
					}else if(($(this).val() != '') && (!objInput.hasClass('fieldType'))){
						bolIsFilled = true;
						return false;
					}
				});

				if(bolIsFilled){
					// highlight the row
					objHolder.addClass(rowDeleteClass);
					// disable it's form elements
					objInputs.attr('disabled', true);
				}else{
					objHolder.fadeOut('fast', function(){
						// remove the element
						$(this).remove();

						// check if this was the last row
						var hasRows = false;
						if(bolHasBody){
							hasRows = tableBody.children('tr').length > 0;
						}else{
							hasRows = ruleTable.children('tr').length > 0;
						}

						if(!hasRows){
							// no rows were found - add the default
							var newRow = $('<tr class="odd"><td colspan="8" align="center" class="blank"><a class="addRuleBtn" title="Add a new rule" href="#">Add Rule</a></td></tr>');

							if(bolHasBody){
								tableBody.append(newRow)
							}else{
								ruleTable.append(newRow);
							}
							newRow.hide().fadeIn('slow');
						}
					});
				}
			}else{
				// the form has been de-selected for removal
				// remove the row highlighting
				objHolder.removeClass(rowDeleteClass);
				// enable the form elements
				objInputs.removeAttr('disabled');
			}
		});

		/**
		 * This empties the length fields when one changes.
		 * If the user enters something into either min or max length, the exact length is emptied.
		 * If the users enters something into the exact length, the min and max are emptied
		 */
		validationForm.on('keydown', 'input.fieldLength', function(e){
			var keyChecker = new KeyCheck();
			console.log(e);
			if(keyChecker.isNumeric(e) || keyChecker.isDelete(e)){
				var objInput = $(this);

				if(objInput.hasClass('exact')){
					objInput
						.siblings('input.fieldLength.min, input.fieldLength.max')
							.val('')
							.removeClass(inputErrorClass)
							.addClass(rowDeleteClass);

					objInput.removeClass(rowDeleteClass);
				}else{
					objInput
						.siblings('input.fieldLength.exact')
							.val('')
							.removeClass(inputErrorClass)
							.addClass(rowDeleteClass);
					objInput.removeClass(rowDeleteClass);
					objInput
						.siblings('input.fieldLength.min, input.fieldLength.max')
							.removeClass(rowDeleteClass);
				}
			}else if(!keyChecker.isDirectional(e) && !keyChecker.isDelete(e)){
				return false;
			}
		});

		/**
		 * carries out validation of min/max values, ensuring the the min is not greater than the max
		 */
		validationForm.on('keyup', 'input.fieldLength.min, input.fieldLength.max', function(e){
			var keyChecker = new KeyCheck();
			if(keyChecker.isNumeric(e) || keyChecker.isDelete(e)){
				var objInput = $(this);

				objInput.removeClass(inputErrorClass);
				objInput.siblings('input.fieldLength').removeClass(inputErrorClass);

				if(objInput.val() != ''){
					var siblingVal = 0;

					if(objInput.hasClass('min')){
						siblingVal = objInput.siblings('input.fieldLength.max').val();
						if((siblingVal != '') && (parseInt(objInput.val()) >= parseInt(siblingVal))){
							objInput.addClass(inputErrorClass);
						}
					}else{
						siblingVal = objInput.siblings('input.fieldLength.min').val();
						if((siblingVal != '') && (parseInt(objInput.val()) <= parseInt(siblingVal))){
							objInput.addClass(inputErrorClass);
						}
					}
				}
			}
		});


		/**
		 * Adds the modal dialog functionality to the dialog boxes
		 */
		$('div.dialog').dialog({
			autoOpen:false,
			modal:true,
			width:'auto'
		});

		// define the dialog boxes
		var objMultiDialog = $('#multiPop'),
			objAcceptDialog = $('#acceptPop');
		objMultiDialog.find('.optionsBox')
			/**
			 * shows the editing of option label/values
			 */
			.on('focus', 'input', function(){
				$(this).parent().addClass('active');
			})
			/**
			 * hides the editing of option label/values
			 */
			.on('blur', 'input', function(){
				$(this).parent().removeClass('active');
			})
			/**
			 * handles deletion of options
			 */
			.on('click', '.delete', function(){
				$(this).parents('li:first').remove();
			})
			/**
			 * add sortable to the options list, to enable re-ordering
			 */
			.sortable({
				containment:'parent',
				tolerance:'pointer',
				start:function(){
					$(this).children().removeClass('active');
				}
			});

		/**
		 * handles button clicks for the option dialog
		 */
		objMultiDialog.find('.btnOptions button').on('click', function(){
			var objThis = $(this);
			if(objThis.hasClass('close')){
				// user has pressed the cancel button
				objMultiDialog.dialog('close');
			}else if(objThis.hasClass('save')){
				// the user has clicked the save button

				var val = '';
				$(objMultiDialog.find('.optionsBox li')).each(function(){
					var objThis = $(this);
					val += objThis.find('input.label:first').val() + ':' + objThis.find('input.value:first').val() + ',';
				});
				validationForm.find('input.value#' + objMultiDialog.attr('rel')).val(val);

				objMultiDialog.dialog('close');
			}else if(objThis.hasClass('add')){
				// the user is adding a new option
				$(objMultiDialog.find('.optionsBox')).append(
						'<li>' +
							'<input name="label" value="Label" title="Label" class="label">' +
							'<input name="value" value="Value" title="Value" class="value">' +
							'<span title="delete this option" class="delete">delete</span>' +
						'</li>'
				);
			}
		});

		/**
		 * runs the listing of extensions from the custom mime type search
		 */
		objAcceptDialog.find('input.customExt').on({
			focus:function(){
				var objThis = $(this),
					objResultsBox = objThis.siblings('.resultBox:first');
				if((objThis.val() != '') && !objResultsBox.is(':empty')){
					objResultsBox.show();
					objResultsBox.siblings('.resultCloseBtn:first').show();
				}
			},
			keyup:function(){
				var objThis = $(this),
					strExt = objThis.val(),
					objResultsBox = objThis.siblings('.resultBox:first'),
					objCloseBtn = objThis.siblings('.resultCloseBtn:first');

				if(strExt == ''){
					// no extension has been specified - clear the results
					objResultsBox.hide().empty();
					objCloseBtn.hide();
				}else if(strExt.length > 1){
					// an extension is being searched for
					$.get(mimetypeURL, {type:'mime', value:strExt, partial:true, limit:200}, function(data){
						if(data && data.length > 0){
							// loop through each result and display it
							var strHTML = '';
							$.each(data, function(){
								var ext = this.ext,
									mimeID = ext + '-' + (new Date().getTime()) + '-' + Math.random(),
									bolExists = false;

								// loop through the existing extensions and see if they exist
								objThis.parents('.listBlock.custom:first').find('ul.list li label').each(function(){
									if('.' + ext == $.trim($(this).text())){
										// the extension already exists - quite the loop
										bolExists = true;
										return false;
									}
								});

								strHTML += '<li' + (bolExists ? ' class="selected"' : '') + '>\n' +
									'<label title=".' + ext + ' (' + this.mime.join(', ') + ')" for="' + mimeID + '">\n' +
										'<input type="checkbox" value="' + this.mime.join(',') + '" name="accepts[]" id="' + mimeID + '">\n' +
										'.' + ext +
									'\n</label>' +
								'\n</li>'
							});

							objResultsBox
								.css({
									top:(objThis.position().top + objThis.innerHeight() + parseInt(objThis.css('margin-top').replace('px', '')) + parseInt(objThis.css('border-top-width').replace('px', ''))) + 'px',
									left:objThis.position().left + 'px',
									width:objThis.innerWidth() + 'px'
								})
								.show()
								.html(strHTML);

							objCloseBtn
								.show()
								.css({
									top:(objThis.position().top + parseInt(objThis.css('margin-top').replace('px', '')) + parseInt(objThis.css('border-top-width').replace('px', ''))) + 'px',
									left:(objThis.position().left + objThis.innerWidth() - objCloseBtn.width()) + 'px'
								});
						}else{
							objResultsBox.hide().empty();
							objCloseBtn.hide();
						}
					}, 'json');
				}
			}
		});

		/**
		 * Handles selecting of custom mime types
		 */
		objAcceptDialog.find('.listBlock.custom .resultBox:first').on('click', 'label', function(){
			var objItem = $(this).parent('li');
			
			if(!objItem.hasClass('selected')){
				objItem.addClass('selected').find('input[type=checkbox]').attr('checked', true);
				objItem.parents('.listBlock.custom:first').find('ul.list').append(objItem.clone());
			}

			return false;
		});

		/**
		 * handles hiding of the custom file types search results
		 */
		objAcceptDialog.find('.listBlock.custom .resultCloseBtn').on('click', function(){
			$(this).hide().siblings('.resultBox:first').hide();
		});

		/**
		 * handles button clicks for the filetype dialog
		 */
		objAcceptDialog.find('.btnOptions button').on('click', function(){
			var objThis = $(this);
			if(objThis.hasClass('close')){
				// user has pressed the cancel button
				objAcceptDialog.dialog('close');
			}else if(objThis.hasClass('save')){
				// the user has clicked the save button

				var bolHasError;

				// collect all of the mime types
				var objMimes = objAcceptDialog.find('.listBlock.all ul.list input[type=checkbox]:checked'),	// the catch-all mime type fields
					arrMimesAll = [];																			// list of found catch-all mime types

				// loop through and collect all of the catch-all mime types (images/*, audio/* etc)
				objMimes.each(function(){
					arrMimesAll[arrMimesAll.length] = ($(this).val().split('/'))[0];
				});

				objMimes = objAcceptDialog.find('.listBlock:not(.all) ul.list input[type=checkbox]:checked');
				var arrMimes = [];
				objMimes.each(function(){
					return $.each($(this).val().split(','), function(){
						var mimeParts = $.trim(this).split('/');
						if(mimeParts.length == 2){
							// the mime type has enough parts
							if($.inArray(mimeParts[0], arrMimesAll) >= 0){
								// the first part of the mime type matches a catch-all
								$.ee_notice(
									'A selected extension conflicts with a general mime type.<br />' +
									'Only select a General type if you are not specifying an extension of the same type (ie; selecting \'All Images\' as well as \'.jpg\').',
									{type:'error'}
								);
								bolHasError = true;
								return false;
							}else{
								// the first part doesn't match a catch-all - add it to the list
								arrMimes[arrMimes.length] = mimeParts.join('/');
							}
						}

						return true;
					});
				});

				if(!bolHasError){
					// all mime types are okay
					// loop through the catch-all types and prepend the '/*'
					for(var i = 0; i < arrMimesAll.length; i++){
						arrMimesAll[i] += '/*';
					}
					// merge the two arrays
					$.merge(arrMimes, arrMimesAll);

					// store the values in the input field
					validationForm.find('input.fieldAccept#' + objAcceptDialog.attr('rel')).val(arrMimes.join(','));

					$.ee_notice(
						'The mime types have been updated',
						{type:'success'}
					);
					objAcceptDialog.dialog('close');
				}
			}
		});

		/**
		 * displays the modal box for setting select/radio/check options
		 */
		validationForm.on('click', '.multiplePop', function(){
			var objThis = $(this),
				type = objThis.attr('rel');
			if(!type || (type == '')){
				// the type isn't set
				return false;
			}else{
				// the type is defined

				var objOptionsBox = objMultiDialog.find('.optionsBox'),							// the options holder
					objInput = objThis.parents('tr:first').find('.valueBox input.value:first');	// the actual input holding the current option values

				// loop through each currently defined option and output it
				var optionsHTML = '';
				$.each(objInput.val().split(','), function(){
					var val = this.split(':');
					if(val[0] && val[1]){
						optionsHTML += '<li>' +
							'<input name="label" value="' + val[0] + '" title="Label" class="label">' +
							'<input name="value" value="' + val[1] + '" title="Value" class="value">' +
							'<span title="delete this option" class="delete">delete</span>' +
						'</li>';
					}
				});
				// add the type as a class name to the options box so we know how to display the options and set the HTML
				objOptionsBox.removeClass('checkbox radio select').addClass(type).html(optionsHTML);

				// ensure that a title is defined
				if(objMultiDialog.dialog('option', 'title') == ''){
					objMultiDialog
							.dialog('option', 'title', objMultiDialog.find('h1:first').html())
							.find('h1:first').remove();
				}
				// show the dialogue box
				objMultiDialog.attr('rel', objInput.attr('id')).dialog('open');
			}

			return false;
		});

		/**
		 * displays the modal box for setting the allowed file types for file inputs
		 */
		validationForm.on('click', '.typeBlock.show-file label', function(){
			var objAcceptInput = $(this).siblings('input.fieldAccept'),
				objCustomList = objAcceptDialog.find('.listBlock.custom ul.list');

			// remove any custom mime types
			objCustomList.empty();
			// empty the custom extension search box
			objAcceptDialog.find('.listBlock.custom input.customExt').val('');
			// un-check any selected input field
			objAcceptDialog.find('.listBlock:not(.custom) input[type=checkbox]').removeAttr('checked');

			// list of pre-built typical mime type selection
			var objCheckboxes = objAcceptDialog.find('.listBlock:not(.custom,.all) ul.list input[type=checkbox]'),
				arrChecks = [];
			objCheckboxes.each(function(){
				arrChecks[arrChecks.length] = $(this).val().split(',');
			});

			// loop through all currently defined mime types and check them (or create a custom checkbox for them)
			var arrMimes = objAcceptInput.val().split(',');
			$.each(arrMimes, function(count){
				if(this !== null){
					var strMime = this.toString();
					if(!strMime || (strMime == '')){
						// mime type is empty - skip it
						return true;
					}else{
						// mime type is set - check if it exists in any pre-existing lists

						// check if the mime type is a typical catch-all
						if((strMime.split('/'))[1] == '*'){
							// the mime type is a catch-all
							objAcceptDialog.find('.listBlock.all ul.list input[type=checkbox]').each(function(){
								var objThis = $(this);
								if(objThis.val() == strMime){
									// the mime type matches
									objThis.attr('checked', true);
									return false;
								}
							});
						}else{
							// the mime type isn't a catch-all

							var bolIsFound = false;
							// loop through the typical mime groups and see if they match
							$.each(arrChecks, function(i){
								if($.inArray(strMime, this) >= 0){
									// the mime type exists in a mime group - check if all the others do too
									var found = [];
									$.each(this, function(i, e){
										var pos = $.inArray(this.toString(), arrMimes);
										if(pos >= 0){
											// the mime type is found
											found[found.length] = pos;
										}
									});

									if(found.length == this.length){
										// all of the mimes were found for the mime group

										// check the mime group
										objCheckboxes.get(i).checked = true;

										// remove the mimes from the loop
										for(var f = 0; f < found.length; f++){
											arrMimes[found[f]] = null;
										}

										bolIsFound = true;
									}
								}
							});

							if(!bolIsFound){
								// it still wasn't found - create it
								$.get(mimetypeURL, {type:'ext', value:strMime}, function(data){
									if(data && data.ext && data.mime){
										var mimeID = data.ext + '-' + (new Date().getTime()) + '-' + Math.random();
										objCustomList.append('<li>\n' +
											'<label title=".' + data.ext + ' (' + data.mime.join(', ') + ')" for="' + mimeID + '">\n' +
												'<input type="checkbox" value="' + data.mime.join(',') + '" name="accepts[]" checked id="' + mimeID + '">\n' +
												'.' + data.ext +
											'\n</label>' +
										'\n</li>');
									}
								}, 'json');
							}
						}

						/*var objChecks = objAcceptDialog.find('.listBlock:not(.custom) ul.list input[type=checkbox][value="' + strMime + '"]');
						if(objChecks.length > 0){
							// it was found
							objChecks.attr('checked', true);
						}else{
							// it wasn't found - create it
							$.get(mimetypeURL, {type:'ext', value:strMime}, function(data){
								if(data && data.ext && data.mime){
									var mimeID = data.ext + '-' + (new Date().getTime()) + '-' + Math.random();
									objCustomList.append('<li>\n' +
										'<label title=".' + data.ext + ' (' + data.mime.join(', ') + ')" for="' + mimeID + '">\n' +
											'<input type="checkbox" value="' + data.mime.join(',') + '" name="accepts[]" checked id="' + mimeID + '">\n' +
											'.' + data.ext +
										'\n</label>' +
									'\n</li>');
								}
							}, 'json');
						}*/
					}
				}
			});

			// ensure that a title is defined
			if(objAcceptDialog.dialog('option', 'title') == ''){
				objAcceptDialog
						.dialog('option', 'title', objAcceptDialog.find('h1:first').html())
						.find('h1:first').remove();
			}
			objAcceptDialog.attr('rel', objAcceptInput.attr('id')).dialog('open');
		});

		/**
		 * ensures that no rules are for the same field name
		 */
		validationForm.on('keyup', 'input.fieldName', function(){
			var existingVals = [];

			validationForm.find('input.fieldName').each(function(){
				var objInput = $(this);
				if($.inArray(objInput.val(), existingVals) >= 0){
					objInput.addClass(inputErrorClass);
				}else{
					objInput.removeClass(inputErrorClass);
					existingVals[existingVals.length] = objInput.val();
				}
			});
		});

		/**
		 * Allows the de-selecting of the email address recipient radio box
		 */
		validationForm.on('click mousedown', 'input.fieldIsRecipient', function(e){
			var objInput = $(this);
			if(e.type == 'mousedown'){
				objInput.data('check', !objInput.is(':checked'));
			}else{
				validationForm.find('input.fieldIsRecipient').removeAttr('checked');

				var objSelect = objInput.parents('tr:first').find('select.fieldValType:first');

				if(objInput.data('check')){
					// check the radio box
					objInput.attr('checked', true);

					// auto-select the 'valid_email' verification type
					objSelect.val('valid_email');
					// disable all other options, to stop the validation type from being changed
					objSelect.children('option').attr('disabled', true);
				}else{
					// re-enable the validation types to allow it to be changed
					objSelect.children('option').removeAttr('disabled');
					objInput.removeAttr('checked');
				}
			}
		});

		/**
		 * handles submitting of the form
		 */
		validationForm.on('submit', function(){
			if(validationForm.find('tr:not(.' + rowDeleteClass + ') input.fieldName').length == 0){
				// no rules have been defined
				$.ee_notice(
					'No input fields have been defined. Please define at least one input field row.<br />' +
						'Remember that every input needs to be defined, even if it has no rules associated with it, otherwise it won\'t be included when the form is submitted.',
					{type:'error'}
				);
			}else{
				// no errors have been highlighted - post the form via Ajax and return the result
				$.post(validationForm.attr('action'), validationForm.serialize() + '&ruleSubmit=1', function(data){
					validationForm.find('input, select, textarea').removeClass('error');

					// attempt to parse the JSON response
					var dataDecoded = {};
					try{
						dataDecoded = $.parseJSON(data);
					}catch(e){
						dataDecoded = {
							status:'error',
							data:{}
						};
					}

					if(dataDecoded.status == 'error'){
						// an error has occurred - notify the user
						if($.isEmptyObject(dataDecoded.data)){
							// the error is un-recognised
							$.ee_notice(
								'An unknown error has occurred, please try again',
								{type:'error'}
							);
						}else{
							// the return contains a list of errors to display
							// loop through each and output the error
							$.each(dataDecoded.data, function(){
								if(this.fieldName != ''){
									validationForm
										.find('input[name="' + this.fieldName + '"], select[name="' + this.fieldName + '"], textarea[name="' + this.fieldName + '"]')
										.addClass('error');
								}

								$.ee_notice(
									this.error,
									{type:'error'}
								);
							});
						}
					}else{
						// form submitted successfully

						// notify the user of the success
						$.ee_notice(
							dataDecoded.data.message,
							{type:'success'}
						);

						// add the hidden input field containing the rule set ID
						var objInputID = validationForm.find('input[name=id]');
						if(objInputID.length == 0){
							objInputID = $('<input type="hidden" name="id" value="" />');
							validationForm.append(objInputID);
						}
						objInputID.val(dataDecoded.data.id);

						// hide and remove any entries that were marked for deletion
						validationForm.find('tr.' + rowDeleteClass).fadeOut('fast', function(){
							$(this).remove();
						});
					}
				});
			}

			return false;
		});


		/**
		 * Initialise the form
		 */
		// set the user email input
		validationForm.find('input.fieldIsRecipient:checked').each(function(){
			var objInput = $(this);
			objInput.data('check', true);

			// check the radio box
			objInput.attr('checked', true);

			objInput.parents('tr:first').find('select.fieldValType:first')
				// auto-select the 'valid_email' verification type
				.val('valid_email')
				// disable all other options, to stop the validation type from being changed
				.children('option').attr('disabled', true);
		});
		// highlight etc the input limit fields
		validationForm.find('input.fieldLength').each(function(){
			var objThis = $(this),
				strVal = objThis.val();
			if(strVal != ''){
				var charKey = strVal.substr(strVal.length-1, strVal.length).charCodeAt();
				objThis.trigger($.Event('keydown', {keyCode:charKey}));
				objThis.trigger($.Event('keyup', {keyCode:charKey}));
			}
		});
	}

	/**
	 * Carries out the deletion of rule sets
	 */
	$('.formDeleteBtn').on('click', function(){
		// a delete button has been clicked

		if(confirm('Are you sure that you wish to remove this rule set?\nThis cannot be undone.')){
			var objBtn = $(this);

			$.get(objBtn.attr('href'), function(data){
				// attempt to parse the JSON response
				var dataDecoded = {};
				try{
					dataDecoded = $.parseJSON(data);
				}catch(e){
					dataDecoded = {
						status:'error',
						data:''
					};
				}

				if(dataDecoded.status == 'error'){
					// an error has occurred - notify the user
					if(dataDecoded.data != ''){
						// the error is un-recognised
						$.ee_notice(
							'An unknown error has occurred, please try again',
							{type:'error'}
						);
					}else{
						$.ee_notice(
							dataDecoded.data,
							{type:'error'}
						);
					}
				}else{
					// entry successfully removed

					// notify the user
					$.ee_notice(
						dataDecoded.data,
						{type:'success'}
					);

					// remove the set from the page
					objBtn.parents('tr:first').fadeOut('slow', function(){
						$(this).remove();
					});
				}
			});
		}

		return false;
	});

	/**
	 * Shows/hides extra information/boxes for field rows, dependant on their type
	 * ie; the file 'accept' fields
	 */
	function showFieldExtras(animate){
		var animSpeed = 600;

		validationForm.find('select.fieldType').each(function(){
			var objType = $(this),
				objParent = objType.parents('tr:first'),
				type = objType.val();

			// ensure that the 'multiPop' type reference is correct - only used for fields with multiple values (select, checkbox etc)
			objParent.find('.multiplePop').attr('rel', type);

			// hide the 'accept' mime type box
			objParent.find('.typeBlock.show-file input.fieldAccept').hide();

			// loop through each extra info blocks and show/hide the relevant elements
			objParent.find('.extraInfo').each(function(){
				var objThis = $(this),
					bolShow = !objThis.hasClass('hide-' + type) && (objThis.hasClass('show-all') || objThis.hasClass('show-' + type));
				if(bolShow){
					// the type matches - show the element
					if(animate){
						objThis.slideDown(animSpeed);
					}else{
						objThis.show();
					}
				}else{
					// the type doesn't match - hide the element
					if(animate){
						objThis.slideUp(animSpeed);
					}else{
						objThis.hide();
					}
				}
			});
		});
	}

	function addSortHandle(obj){
		var objThis = $(obj),
			objSort = $('<span title="drag to re-order" class="sort">sort</span>'),
			intPos = objThis.innerWidth() - objThis.css('padding-right').replace('px', '');
		objThis.wrapInner('<div class="surround"></div>').find('> .surround').append(objSort);
		objSort.css('right', intPos + 'px');
	}
});