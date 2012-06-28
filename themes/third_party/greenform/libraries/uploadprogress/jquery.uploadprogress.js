/*
	jQuery uploadprogress v 0.3
	copyright (c) 2008,2009 Jolyon Terwilliger
	website: http://nixboxdesigns.com/demos/jquery-uploadprogress.php

	requires a web server running PHP 5.2.x with the
	PHP uploadprogress module compiled in or enabled as a shared object:
	http://pecl.php.net/package/uploadprogress

	Dual licensed under the MIT and GPL licenses:
	http://www.opensource.org/licenses/mit-license.php
	http://www.gnu.org/licenses/gpl.html

basic description:

	a plugin to augment a standard file upload form with transparent background upload
and add uploadprogress meter to keep client informed of progress.  (see requirements above)

usage:

	jQuery('form#upload_form').uploadProgress({ id:'uniqueid' | keyLength:11 });

	if no id is passed, a key of keyLength or 11 characters is generated and applied to the target form as a hidden field to key the upload session.


parameters:

	id - default: none - optional.  will be generated if omitted.

	keyLength - default: 11 - length of UPLOAD_IDENTIFIER hidden input field key to be generated.

	dataFormat - default: 'json' - only viable option at this point would be jsonp (qv.)

	progressURL - default: none - this is the relative or absolute URL used for the uploadprogress update post request.

	updateDelay - default: 1000 - in milliseconds - regardless how low this value is set, the previous uploadprogress request must finish before the next will be sent. This is how long to wait until the next request is started. If your server is particularly slow or you have high network latency issues, setting a lower value like 200 can simulate faster updates.

	notFoundLimit - default: 5 - how many loops to allow any return value of 'error' before exiting with failed status.  Sometimes the first uploadprogress request is processed by the webserver before the actual upload has been acknowledged and started in the system, potentially returning an 'upload_id not found' error.  This is the threshold value to set for # of error messages to allow before failing the upload.

	waitText - default: false - If set, this text will be used replace form submit button value text.  Note:  a flag is set in within the plugin to prevent double-submit actions on the form.  Once submit is completed, the original text is restored for future submits.

	debugDisplay - default: none - if set, used as a selector for DOM element to display debug output.

	progressDisplay - default: .upload-progress - selector for DOM element to target output container ( used to calculate meter constraints and any displayFields specified return data )

	progressMeter - default: .meter - selector for DOM element that will be horizontally resized against inner width of progressDisplay (minus 20 pixels padding) as upload progress changes. To disable meter updates, set this to false.

	targetUploader - default: jqUploader - id/name for upload target iframe.

	fieldPrefix - default: . (class selector) - selector prefix for jQuery DOM capture of displayField sub-elements of progressDisplay selector.

	displayFields - default (Array): ['est_sec'] - array of fields to parse from return ajax request data and target on to DOM elements prefixed by fieldPrefix.  See demo and example php servlet for details.

	start - default (Function): empty - function to run at beginning of submit request, prior to actual upload submit.

	success - default (Function): empty - function to run upon successful completion of upload

	failed - default (Function): empty - function to run if upload failed

global arrays:

several arrays are populated by upload key with data for interchange between routines at various stages of operation:

uploadProgressSettings - stores the final parameter settings
uploadProgressTimer - used for clearTimeout operation
uploadProgressNotFound - tick timer for 'upload not found' quirk
uploadProgressActive - used to manage several quirks
uploadProgressData - used to hold the last valid set of data
*/

var uploadProgressSettings = new Array();
var uploadProgressTimer = new Array();
var uploadProgressNotFound = new Array();
var uploadProgressActive = new Array();
var uploadProgressData = new Array();

(function($){
	$.fn.extend({
		uploadProgress: function(o) {
			//o = $.extend(o, {});
			o = $.extend({
				dataFormat: 'json',
				updateDelay: 1000,
				notFoundLimit: 5,
				debugDisplay: false,
				progressDisplay: '.upload-progress',
				progressMeter: '.meter',
				targetUploader: 'jqUploader',
				fieldPrefix: '.',
				displayFields: ['est_sec'],
				html5:true,
				start: function(){},
				success: function(){},
				failed: function(){}
			}, o);
			// this stores the formData object
			o.formData = null;

			var $id_field = $('input#uploadID',this);
			if (!o.id && $id_field.length)
				o.id = $id_field.val();
			if (!o.id)
				o.id = genUploadKey(o.keyLength);

			if ($id_field.length){
				$id_field.val(o.id);
			}else{
				$('<input type="hidden" name="UPLOAD_IDENTIFIER" id="uploadID" />').val(o.id).prependTo(this);
			}

			uploadProgressSettings[o.id] = o;

			// post the files out:
			$(this).submit(function () {
				if(uploadProgressActive[o.id]){
					return false;
				}
				uploadProgressActive[o.id] = true;
				uploadProgressSettings[o.id].startTime = (new Date().getTime()) / 1000;

				var theForm = this;

				if(o.debugDisplay && ($(o.debugDisplay).length == 0)){
					// the debug display element doesn't exist
					$(theForm).append('<div id="' + o.debugDisplay.replace('#', '') + '">');
				}

				// call the start function
				if(o.start.call(theForm) === false){
					// the callback returned false - stop processing
					return false;
				}

				// check if we have formData support
				o.formData = o.html5 ? new FormData(theForm) || null : null;
				if(o.html5 && o.formData){
					// HTML5 formData is supported
					var objForm = $(theForm);	// JQuery object of the form

					// submit the form via Ajax
					var xhr = $.ajax({
						url:objForm.attr('action'),
						type:(objForm.attr('method') == 'get') ? 'get' : 'post',
						xhr:function(){
							var myXhr = $.ajaxSettings.xhr();
							if(myXhr.upload){
								myXhr.upload.addEventListener('progress', function(evt){
									$.uploadProgressUpdate(o.id, evt);
								}, false);
							}
							return myXhr;
						},
						data:o.formData,
						cache:false,
						contentType:false,
						processData:false,
						success:function(data){
							var objProgressHolder = $(o.progressDisplay);	// the object that holds the upload progress data
							if(o.progressMeter){
								// ensure that the progress bar is at 100%
								objProgressHolder.find(o.progressMeter).width('100%');
							}

							// loop through the display fields and set all 'uploaded' amounts to full
							if(o.displayFields && (o.displayFields.length > 0)){
								for(var d = 0; d < o.displayFields.length; d++){
									var match = o.displayFields[d].match(/((.*?)_)?total/);
									if(match){
										// this field is a total - set it's 'uploaded' pair to match
										objProgressHolder.find(o.fieldPrefix + (match[1] ? match[1] + '_' : '') + 'uploaded').html(objProgressHolder.find(o.fieldPrefix + o.displayFields[d]).html());
									}else if(o.displayFields[d].match(/(.*?_)?average/)){
										// this field is an average speed
										var obj = objProgressHolder.find(o.fieldPrefix + o.displayFields[d]);
										obj.html(obj.html().replace(/([0-9]+(\.[0-9]+)?)/, '0'));
									}else if(o.displayFields[d].match(/est_(.*?)/)){
										// this field is an estimated time to completion
										var obj = objProgressHolder.find(o.fieldPrefix + o.displayFields[d]);
										obj.html(obj.html().replace(/([0-9]+(\.[0-9]+)?)/, '0'));
									}
								}
							}

							// get the return data
							try{
								data = $.parseJSON(data);
							}catch(e){
								data = {};
							}
							// call the success function
							o.success.call(theForm, o, data);
							
							uploadProgressActive[o.id] = false;
						},
						error:function(xhr, status, error){
							if(o.debugDisplay){
								$(o.debugDisplay).append('<p>XHR: '+error+'</p>');
							}
							o.failed.call(this, {error:'XHR: ' + error});
							uploadProgressActive[id] = false;
						}
					});

					return false;
				}else{
					// No support for HTML5 formdata - use the IFrame method instead

					// set the form's target to the IFrame, so that it loads in that
					$(theForm).attr('target', o.targetUploader);

					// build the IFrame
					var $upload_frame = $('<iframe id="'+o.targetUploader+'" name="'+o.targetUploader+'"></iframe>');

					if(o.debugDisplay){
						// we are debugging- add the IFrame after the debug info
						$('iframe#'+o.targetUploader).remove();
						$(o.debugDisplay).after($upload_frame);
					}else{
						// no debug - ensure that the IFrame is hidden
						$upload_frame.css({position:'absolute',top:'-9999em',left:'-9999em', width:0, height:0}).appendTo('body');
					}

					$upload_frame.load(function(){
						clearTimeout(uploadProgressTimer[o.id]);

						var objProgressHolder = $(o.progressDisplay);	// the object that holds the upload progress data
						if(o.progressMeter){
							// ensure that the progress bar is at 100%
							objProgressHolder.find(o.progressMeter).width('100%');
						}

						// loop through the display fields and set all 'uploaded' amounts to full
						if(o.displayFields && (o.displayFields.length > 0)){
							for(var d = 0; d < o.displayFields.length; d++){
								var match = o.displayFields[d].match(/((.*?)_)?total/);
								if(match){
									// this field is a total - set it's 'uploaded' pair to match
									objProgressHolder.find(o.fieldPrefix + (match[1] ? match[1] + '_' : '') + 'uploaded').html(objProgressHolder.find(o.fieldPrefix + o.displayFields[d]).html());
								}else if(o.displayFields[d].match(/(.*?_)?average/)){
									// this field is an average time
									var obj = objProgressHolder.find(o.fieldPrefix + o.displayFields[d]);
									obj.html(obj.html().replace(/([0-9]+(\.[0-9]+)?)/, '0'));
								}else if(o.displayFields[d] == 'est_sec'){
									objProgressHolder.find(o.fieldPrefix + o.displayFields[d]).html('0');
								}
							}
						}

						// get the return data
						var contents = $upload_frame.contents(),
						data = (contents.find('body').length > 0) ? contents.find('body').text() : contents.text();
						try{
							data = $.parseJSON(data);
						}catch(e){
							data = {};
						}
						// call the success function
						o.success.call(theForm, o, data);

						if(!o.debugDisplay){
							setTimeout(function() {	$upload_frame.remove(); }, 100);
						}

						uploadProgressActive[o.id] = false;
					});

					uploadProgressTimer[o.id] = window.setTimeout("$.uploadProgressUpdate('" + o.id + "')", o.updateDelay);
					uploadProgressNotFound[o.id] = 0;

					return true;
				}
			} );

			return this;

			function genUploadKey(len) {
				if (!len) len = 11;
				var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				var key='';
				for (var i=0;i<len;i++) {
					var charnum = Math.floor(Math.random()*(chars.length+1));
					key += chars.charAt(charnum);
				}
				return key;
			}
		}
	});

	$.extend({
		uploadProgressUpdate:function(id, event){
			if((typeof XMLHttpProgressEvent != 'undefined') && (event instanceof XMLHttpProgressEvent)){
				// the event is set as the XHR progress handler
				if(event.lengthComputable){
					var startTime = uploadProgressSettings[id].startTime,
						currTime = (new Date().getTime()) / 1000,
						data = {
							time_taken:(currTime > startTime) ? currTime - startTime : 0,										// the time taken so far
							bytes_uploaded:event.loaded,																		// the bytes uploaded
							bytes_total:event.total																				// the total file size
						};
					data.speed_average = (data.time_taken == 0) ? data.bytes_uploaded : data.bytes_uploaded / data.time_taken;	// the average upload speed in bytes
					data.est_sec = (event.total - event.loaded) / data.speed_average;											// estimated seconds until complete

					// store the default average speed, total to upload and total uploaded
					data.average = data.speed_average;
					data.total = data.bytes_total;
					data.uploaded = data.bytes_uploaded;

					// we need to get the average, total and uploaded in different denominations types
					var arrDataTypes = ['kb', 'mb', 'gb', 'tb'];
					for(var i = 0; i < arrDataTypes.length; i++){
						// get the previous data type
						var prevT = arrDataTypes[i-1] || 'bytes',
							currentT = arrDataTypes[i];

						// calculate the average speed
						data[currentT + '_average'] = (data[prevT + '_average'] ? data[prevT + '_average'] : data['speed_average']) / 1024;
						if(data[currentT + '_average'] < 100){
							data[currentT + '_average'] = data[currentT + '_average'].toFixed(1).replace(/\.0$/, '');
						}else if(data[currentT + '_average'] < 10){
							data[currentT + '_average'] = data[currentT + '_average'].toFixed(2).replace(/\.00$/, '');
						}else{
							data[currentT + '_average'] = Math.round(data[currentT + '_average']);
						}

						// define the data type total upload size and uploaded data size
						data[currentT + '_total'] = (data[prevT + '_total'] / 1024).toFixed(2).replace(/\.00$/, '');
						data[currentT + '_uploaded'] = (data[prevT + '_uploaded'] / 1024).toFixed(2).replace(/\.00$/, '');

						// if the average is not less than one, we set it to the largest type to use
						if(data[currentT + '_average'] >= 1){
							data['average'] = data[currentT + '_average'] + currentT;
						}
						// if the total is not less than one, we set it to the largest type to use
						if(data[currentT + '_total'] >= 1){
							data['total'] = data[currentT + '_total'] + currentT;
						}
						// if the uploaded amount is not less than one, we set it to the largest type to use
						if(data[currentT + '_uploaded'] >= 1){
							data['uploaded'] = data[currentT + '_uploaded'] + currentT;
						}
					}

					// round up the estimated time to a full second
					data.est_sec = Math.ceil(data['est_sec']);
					data.est_time = data.est_sec + ' seconds';

					// we need to get the time left in the different denominations (minutes, hours, days etc)
					var arrTimeTypes = ['minutes', 'hours'];
					for(i = 0; i < arrTimeTypes.length; i++){
						// get the previous time type
						prevT = arrTimeTypes[i-1] || 'sec';
						currentT = arrTimeTypes[i];

						// calculate the estimated time
						data['est_' + currentT] = ((data['est_' + prevT] ? data['est_' + prevT] : data['est_sec']) / 60).toFixed(2).replace(/\.00$/, '');
						// if we are on the first denominator (minutes), round to a full number
						if(i == 0){
							data['est_' + currentT] = Math.round(data['est_' + currentT]);
						}

						// if the time is not less than one, we set it to the largest type to use
						if(data['est_' + currentT] >= 1){
							data['est_time'] = data['est_' + currentT] + ' ' + currentT;
						}
					}

					uploadProgressData[id] = data;
					displayProgress(id);
				}else{
					// not able to calculate progress
				}
			}else{
				// no event is specified, meaning that we are NOT using HTML5 upload
				var o = uploadProgressSettings[id];
				var stamp = new Date().getTime();   // for IE request cache distinction
				$.ajax({
					url:o.progressURL,
					data:{'upload_id':id, 'stamp':stamp},
					success:function(data) {
						if(!data || data['error']){
							// no data or error returned
							if(o.debugDisplay){
								$(o.debugDisplay).append('<p>UP: '+data['error']+'</p>');
							}
							uploadProgressNotFound[id]++;
							if(uploadProgressNotFound[id] >= o.notFoundLimit){
								uploadProgressActive[id] = false;
								o.failed.call(this, data);
								return false; // cancel timer renewal
							}
						}else{
							// data has been returned
							uploadProgressData[id] = data;

							displayProgress(id);
						}
						if (uploadProgressActive[id])
							uploadProgressTimer[id] = window.setTimeout("$.uploadProgressUpdate('"+id+"')",o.updateDelay);
					},
					dataType:o.dataFormat,
					error:function(xhr, err, et) {
						if(o.debugDisplay){
							$(o.debugDisplay).append('<p>XHR: '+err+'</p>');
						}
						o.failed.call(this, {error:'XHR: ' + err});
						uploadProgressActive[id] = false;
						return false;
					}
				});
			}
		}
	});

	/**
	 * updates the progress bar
	 * 
	 * @param id
	 */
	function displayProgress(id){
		var data = uploadProgressData[id],
			o = uploadProgressSettings[id];

		if(o.debugDisplay){
			// we need to display debug information
			var q = '';
			for (var prop in data) {
				q += prop + ': ' + data[prop] + '<br />';
			}
			$(o.debugDisplay).html(q);
		}

		var objProgressHolder = $(o.progressDisplay);	// the object that holds the upload progress data
		if(o.progressMeter){
			// we need to display the progress bar
			var factor = objProgressHolder.width() / data['bytes_total'];
			objProgressHolder.find(o.progressMeter).width(data['bytes_uploaded']*factor);
		}

		if(o.displayFields && (o.displayFields.length > 0)){
			// we need to display the upload details (kb, percent etc)
			for(var d = 0; d < o.displayFields.length; d++){
				objProgressHolder.find(o.fieldPrefix + o.displayFields[d]).html(data[o.displayFields[d]]);
			}
		}
	}
})(jQuery);