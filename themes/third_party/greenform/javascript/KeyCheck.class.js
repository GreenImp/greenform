/**
 * Author: Lee Langley
 * Date Created: 14/03/2012 12:05
 */

function KeyCheck(){
	var getKeyCode = function(event){
		return (event.keyCode ? event.keyCode : event.which);
	};

	this.isNumeric = function(event){
		var keyCode = getKeyCode(event);
		return ((keyCode >= 48) && (keyCode <= 57));
	};

	this.isDirectional = function(event){
		var keyCode = getKeyCode(event);
		return ((keyCode >= 37) && (keyCode <= 40)) || (keyCode == 9);
	};

	this.isDelete = function(event){
		var keyCode = getKeyCode(event);
		return (keyCode == 8) || (keyCode == 46) || (keyCode == 9);
	}
}