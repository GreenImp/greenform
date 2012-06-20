/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

var selField=!1,selMode="normal";function setFieldName(f){f!=selField&&(selField=f,clear_state(),tagarray=[],usedarray=[],running=0)}
function taginsert(f,g,a){var d=eval("item.name");if(!selField)return $.ee_notice(no_cursor),!1;var c=!1,e=!1,b=document.getElementById("entryform")[selField];"guided"==selMode&&(data=prompt(enter_text,""),null!=data&&""!=data&&(e=g+data+a));if(document.selection)c=document.selection.createRange().text,b.focus(),c?document.selection.createRange().text=!1==e?g+c+a:e:document.selection.createRange().text=!1==e?g+a:e,b.blur(),b.focus();else if(isNaN(b.selectionEnd)){if("guided"==selMode)curField=document.submit_post[selfField],
curField.value+=e;else{if("other"==f)eval("document.getElementById('entryform')."+selField+".value += tagOpen");else if(0==eval(d))eval("document.getElementById('entryform')."+selField+".value += result"),eval(d+" = 1"),arraypush(tagarray,a),arraypush(usedarray,d),running++,styleswap(d);else{for(i=n=0;i<tagarray.length;i++)if(tagarray[i]==a){n=i;for(running--;tagarray[n];)closeTag=arraypop(tagarray),eval("document.getElementById('entryform')."+selField+".value += closeTag");for(;usedarray[n];)clearState=
arraypop(usedarray),eval(clearState+" = 0"),document.getElementById(clearState).className="htmlButtonA"}if(0>=running&&"htmlButtonB"==document.getElementById("close_all").className)document.getElementById("close_all").className="htmlButtonA"}curField=eval("document.getElementById('entryform')."+selField)}curField.blur();curField.focus()}else{var f=b.scrollTop,h=b.textLength,c=b.selectionStart,j=b.selectionEnd;2>=j&&"undefined"!=typeof h&&(j=h);d=b.value.substring(0,c);h=b.value.substring(c,j).s3=
b.value.substring(j,h);!1==e?(c=c+g.length+h.length+a.length,b.value=!1==e?d+g+h+a+s3:e):(c+=e.length,b.value=d+e+s3);b.focus();b.selectionStart=c;b.selectionEnd=c;b.scrollTop=f}}
$(document).ready(function(){function f(a,d){var c=$("input[name="+d+"]").closest(".publish_field");!1==a.is_image?c.find(".file_set").show().find(".filename").html('<img src="'+EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+a.name):c.find(".file_set").show().find(".filename").html('<img src="'+a.thumb+'" alt="'+a.name+'" /><br />'+a.name);$("input[name="+d+"_hidden]").val(a.name);$("select[name="+d+"_directory]").val(a.directory);$.ee_filebrowser.reset()}$(".js_show").show();
$(".js_hide").hide();void 0!==EE.publish.markitup&&void 0!==EE.publish.markitup.fields&&$.each(EE.publish.markitup.fields,function(a){$("#"+a).markItUp(mySettings)});!0===EE.publish.smileys&&($("a.glossary_link").click(function(){$(this).parent().siblings(".glossary_content").slideToggle("fast");$(this).parent().siblings(".smileyContent .spellcheck_content").hide();return!1}),$("a.smiley_link").toggle(function(){which=$(this).attr("id").substr(12);$("#smiley_table_"+which).slideDown("fast",function(){$(this).css("display",
"")})},function(){$("#smiley_table_"+which).slideUp("fast")}),$(this).parent().siblings(".glossary_content, .spellcheck_content").hide(),$(".glossary_content a").click(function(){$.markItUp({replaceWith:$(this).attr("title")});return!1}));$(".btn_plus a").click(function(){return confirm(EE.lang.confirm_exit,"")});$(".markItUpHeader ul").prepend('<li class="close_formatting_buttons"><a href="#"><img width="10" height="10" src="'+EE.THEME_URL+'images/publish_minus.gif" alt="Close Formatting Buttons"/></a></li>');
$(".close_formatting_buttons a").toggle(function(){$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();$(this).parent().parent().css("height","13px");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_plus.png")},function(){$(this).parent().parent().children().show();$(this).parent().parent().css("height","auto");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_minus.gif")});$.ee_filebrowser();var g="";!0===EE.publish.show_write_mode&&$("#write_mode_textarea").markItUp(myWritemodeSettings);
$(".write_mode_trigger").click(function(){g=$(this).attr("id").match(/^id_\d+$/)?"field_"+$(this).attr("id"):$(this).attr("id").replace(/id_/,"");$("#write_mode_textarea").val($("#"+g).val());$("#write_mode_textarea").focus();return!1});$(".btn_img a, .file_manipulate").click(function(){window.file_manager_context=-1==$(this).parent().attr("class").indexOf("markItUpButton")?$(this).closest("div").find("input").attr("id"):"textarea_a8LogxV4eFdcbC"});$.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate",
function(a){"textarea_a8LogxV4eFdcbC"==window.file_manager_context?a.is_image?$.markItUp({replaceWith:'<img src="{filedir_'+a.directory+"}"+a.name+'" alt="[![Alternative text]!]" '+a.dimensions+"/>"}):$.markItUp({name:"Link",key:"L",openWith:'<a href="{filedir_'+a.directory+"}"+a.name+'">',closeWith:"</a>",placeHolder:a.name}):$("#"+window.file_manager_context).val("{filedir_"+a.directory+"}"+a.name)});$("input[type=file]","#publishForm").each(function(){var a=$(this).closest(".publish_field"),d=
a.find(".choose_file");$.ee_filebrowser.add_trigger(d,$(this).attr("name"),f);a.find(".remove_file").click(function(){a.find("input[type=hidden]").val("");a.find(".file_set").hide();return!1})})});