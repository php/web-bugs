var reEscape = new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g');
var fnFormatSearchResult = function(value, data, currentValue) {
	var pattern = '(' + currentValue.replace(reEscape, '\\$1') + ')';
	var listing = users[value]["name"] + " (" + users[value]["username"] + ")";

	listing = listing.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
	return '<img src="https://secure.gravatar.com/avatar/' + users[value]["email"] + '.jpg?s=25"> ' + listing;
};

$('#assigned_user').autocomplete({
	minChars:2,
	maxHeight:400,
	width:300,
	fnFormatResult: fnFormatSearchResult,
	onSelect: function(value, data){ $('#assigned_user').val(users[value]["username"]); },
	lookup: lookup
});
