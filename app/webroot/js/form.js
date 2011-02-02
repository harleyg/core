if (CORE == undefined) {	throw 'CORE global.js needs to be imported first!';}/** * Shows validation errors * * Use the form parameter if you have more than one model with more than * one form per page, and just wish to show errors on that form. * * * @param string form The id of the form to search. If none, it searches all * @return boolean Whether or not there were any errors */CORE.showValidationErrors = function(form) {	var passed = true;		if (form == undefined) {		form = '';	} else {		form = '#'+form;	}		// get list of errors	$('#content '+form+' div.error-message').each(function() {		passed = false;		CORE.showValidationError($(this));	});	return passed;}/** * Handles any setup before submitting a form * * @param object event The event for the click * @param object XMLHttpRequest The XMLHttpRequest * @return boolean If the request will continue */CORE.beforeForm = function(event, XMLHttpRequest) {		// stop the request if this button has been clicked	if ($(event.originalTarget).data('disabled')) {		XMLHttpRequest.abort();		return false;	}		XMLHttpRequest.async = false;		$('div.error-message').each(function(i) {$(this).fadeOut()});		$('.tabs a').each(function(i) {$(this).removeClass('error');});		$(event.originalTarget).addClass('loading');	$(event.originalTarget).data('disabled', true);	return true;}/** * Handles actions after a form has been submitted *  * @param object event The event for the click * @param object XMLHttpRequest The XMLHttpRequest * @param object textStatus The textStatus */CORE.completeForm = function(event, XMLHttpRequest, textStatus) {	// scroll to top	$('html, body').animate({scrollTop:0}, 'slow');		// allow submit to be clicked again	$(event.originalTarget).data('disabled', false);	$(event.originalTarget).removeClass('loading');}/** * Handles actions after successful form submission * * #### Options: *		- function success Callback for a successful (validated) form (Default none) *		- function failure Callback for an unsuccessful form (Default none) *		- boolean autoUpdate Whether to update the content (Default true) *		- boolean closeModals Whether to close the modal (Default false) * * @param object event The event for the click * @param object data The returned data * @param object textStatus The ajax options * @param object options Success options */CORE.successForm = function(event, data, textStatus, options) {	var _defaultOptions = {		success: false,		failure: false,		autoUpdate: true,		closeModals: false	};		options = $.extend(_defaultOptions, options);		// check to see if it validates	$('#content').append('<div id="temp"></div>');	$('#temp').html(data).css('display', 'none');	validates = CORE.showValidationErrors('temp');	$('#temp').remove();		// update the content	switch (options.autoUpdate) {		case 'failure':					if (!validates) {				$('#content').html(data);				CORE.showValidationErrors($(event.currentTarget).closest('form').attr('id'));			}		break;		case 'success':			if (validates) {				$('#content').html(data);				CORE.showValidationErrors($(event.currentTarget).closest('form').attr('id'));			}		break;		default:			$('#content').html(data);			CORE.showValidationErrors($(event.currentTarget).closest('form').attr('id'));		break;	} 		if (validates) {		if (options.success != false) {			options.success();		} 		if (options.closeModals) {			CORE.closeModals();		}	} else {		if (options.failure != false) {			options.failure();		}	}}/** * Handles actions after failed form submission *  * @param object event The event for the click * @param object XMLHttpRequest The XMLHttpRequest * @param object textStatus The error settings * @param object errorThrown The error */CORE.errorForm = function(event, XMLHttpRequest, textStatus, errorThrown) {	redirect('/pages/form_error');}/** * Shows a validation error on a field (built on * Cake's FormHelper output) *  * @param object field The error field */CORE.showValidationError = function(field) {	$('.tabs').each(function() {		// get fieldset that this field belongs to		var fieldset = $(field).parent('div').parent('fieldset').attr('id');				// add error class to fieldset		if (fieldset != '') {			$('.tabs a[href$='+fieldset+']').each(function() {				$(this).addClass('error');			});		}	});	}/** * Only allows one checkbox per value to be checked. * * This will iterate through all radio and checkboxes and automatically * set `change` events to detect if the checkbox/radio is checked  * and disable similar checkboxes (useful when selecting multiple users * when they may appear more than once on screen) *  * @param string fieldset ID of the fieldset to  */CORE.noDuplicateCheckboxes = function(fieldset) {	$('#'+fieldset+' input[type=checkbox], #'+fieldset+' input[type=radio]').each(function() {		$(this).bind('change', function() {			if (this.checked) {				$('#'+fieldset+' input[value='+this.value+'][type=checkbox], #'+fieldset+' input[value='+this.value+'][type=radio]').attr('disabled', 'disabled');				$('#'+fieldset+' input[value='+this.value+'][type=checkbox], #'+fieldset+' input[value='+this.value+'][type=radio]').removeAttr('checked');				$(this).removeAttr('disabled');				$(this).attr('checked', 'checked');			} else {				$('#'+fieldset+' input[value='+this.value+'][type=checkbox], #'+fieldset+' input[value='+this.value+'][type=radio]').removeAttr('disabled');			}					});	});}/** * Initializes special form elements */CORE.initFormUI = function() {	// create buttons on proper elements	$('button, input:submit, a.button, span.button').button();	$('button.disabled, input:submit.disabled, a.button.disabled, span.button.disabled').button({disabled:true});	$('input.toggle').button();	$('span.toggle input').button();	$('.toggleset').buttonset();	// checkboxes are a little more complicated	$('input[type=checkbox]:not(.ui-helper-hidden-accessible, .core-checkbox-hidden)').each(function() {		$(this).css({opacity:0, margin:0}).addClass('core-checkbox-hidden');		$(this).wrap(function() {			return ($(this).is(':checked')) ? '<div class="core-checkbox selected" />' : '<div class="core-checkbox" />';		});	}).change(function () {		this.checked ?	$(this).parent().addClass('selected') : $(this).parent().removeClass('selected');	});	// set up filter forms	$('.core-filter-form').each(function() {		if ($(this).data('configured') == true) {			return;		}		$(this).data('configured', true);		$('input:submit', this).hide();		var classList = $(this).attr('class').split(/\s+/);		var updateable;		for (var c in classList) {			if (classList[c].indexOf('update-') != -1) {				var updateSplit = classList[c].split('-');				updateSplit.shift();				updateable = updateSplit.join('-');				break;			}		}		var options = {updateHtml: updateable};		if (CORE.updateables[updateable] != undefined) {			for (div in CORE.updateables[updateable]) {				options = {updateHtml: div};			}		}		var form = $(this);		$('input', form).click(function() {			CORE.request(form.attr('action'), options, form.serialize());		});	});		// display any validation errors	CORE.showValidationErrors();}