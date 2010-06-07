if (CORE == undefined) {
	throw 'CORE global.js needs to be imported first!';
} 

/**
 * Attaches modal behavior. 
 *
 * Modal will not open until the link is clicked.
 *
 * @param string id The id of the element
 * @param object options Options for creating the Modal Window.
 * @see jQuery UI dialog options
 * @return mixed Modal response
 */
CORE.modal = function(id, options) {
	var _defaultOptions = {  
		modal: true,
		width: 700,
		autoOpen: false,
		height: 'auto'
	}
	
	// use user defined options if defined
	var useOptions;
	if (options != undefined) {
		useOptions = $.extend(_defaultOptions, options);
	} else {
		useOptions = _defaultOptions;
	}

	useOptions.close = function(event, ui) {
		// rewrite the id's
		$('#content').attr('id', 'modal');		
		$('#content-reserved').attr('id', 'content');
		if ($(this).dialog('option', 'update') != undefined) {
			CORE.update($(this).dialog('option', 'update'));
		}
	};
	
	useOptions.open = function(event, ui) {
		// rename content so ajax updates will update content in modal
		$('#content').attr('id', 'content-reserved');
		$('#modal').attr('id', 'content');
	}
	
	$('#'+id).click(function(event) {
		// set to update what this links says to
		$('#modal').dialog('option', 'update', $(this).data('update'))
		
		// remove old settings (from confirmation, other modals, etc
		$('#modal').dialog('option', 'buttons', {});
		$('#modal').dialog('option', 'width', 700);
		$('#modal').dialog('option', 'height', 'auto');
		
		// set options
		modalOptions = $(this).data('modalOptions');
		for (var o in modalOptions) {
			$('#modal').dialog('option', modalOptions, modalOptions[o]);
		}
		
		// stop link
		event.preventDefault();
		
		// load the link into the modal
		$('#modal').load(this.href, function() {
			$('#modal').dialog('open');
		});
		
		// stop href
		return false;
	});
	
	$('#'+id).data('modalOptions', useOptions);
	$('#'+id).data('update', useOptions.update);
	$('#modal').dialog({autoOpen:false});
}

/**
 * Attaches modal behaviors to appropriate ids
 *
 * Makes everything with a `rel` property "modal-X" a modal
 * where the X is the to-be-updated registered updateable (optional)
 *
 * @return boolean True
 */
CORE.attachModalBehavior = function() {
	$("[rel|=modal]").each(function() {
		if ($(this).data('hasModal') == undefined) {	
			// get updateable, if any
			var rel = $(this).attr("rel");
			var update = rel.split("-");
			
			if ($(this).attr('id') == '') {
				$(this).attr('id', 'link-'+new Date().getTime());
			}
			
			if (update[1] != undefined) {
				CORE.modal($(this).attr('id'), {update:update[1]});
			} else {
				CORE.modal($(this).attr('id'));
			}
			
			$(this).data('hasModal', true);			
		}
	});
	
	return true;
}

/**
 * Creates tabs from <li> tags
 *
 * ####Options:
 * - `next` string Id of the "next" button in the wizard, if any
 * - `previous` string Id of the "previous" button in the wizard, if any
 * - `submit` string Id of the final submit button in the wizard, if any
 * - `alwaysAllowSubmit` boolean True to always show submit button (default true)
 *
 * @param string id The id of the <ul> container
 * @param object taboptions The options for the tabs ui object
 * @param object options Options for tabs, for treatment as a faux-wizard
 * @return object The tab ui object
 */
CORE.tabs = function(id, taboptions, options) {
	var _defaultOptions = {  
		cookie: {
			expires: 30
		}
	}
	
	// use user defined options if defined
	var useOptions;
	if (taboptions != undefined) {
		useOptions = $.extend(_defaultOptions, taboptions);
	} else {
		useOptions = _defaultOptions;
	}
	
	if (useOptions.cookie == false) {
		delete useOptions.cookie;
	}
	
	var tabbed = $('#'+id);
	tabbed.tabs(useOptions)
	
	// check to see if this is a "wizard"
	if (options != undefined) {		
		if (options.next != undefined) {
			// hide next button if it automatically was selected (cookie)
			if (tabbed.tabs('option', 'selected')+1 == tabbed.tabs('length')) {
				$('#'+options.next).hide();
			}
			
			$('#'+options.next).bind('click', function(event, ui) {
				var btn = $(this);
				var length = tabbed.tabs('length');
				var selected = tabbed.tabs('option', 'selected');
				if (selected+1 < length) {
					tabbed.tabs('select', selected+1);
				}
			});
		}
		
		if (options.previous != undefined) {
			// hide prev button if it automatically was selected (cookie)
			if (tabbed.tabs('option', 'selected') == 0) {
				$('#'+options.previous).hide();
			}
			
			$('#'+options.previous).bind('click', function(event, ui) {
				var btn = $(this);
				var length = tabbed.tabs('length');
				var selected = tabbed.tabs('option', 'selected');
				
				if (selected > 0) {
					tabbed.tabs('select', selected-1);
				}
			});
		}
		
		if (options.submit != undefined) {
			if (options.alwaysAllowSubmit == undefined) {
				options.alwaysAllowSubmit = true;
			}
			
			if (!options.alwaysAllowSubmit && tabbed.tabs('option', 'selected')+1 == tabbed.tabs('length')) {
				$('#'+options.submit).hide();
			}
		}
		
		// bind all button actions to one select event
		if (options.next != undefined || options.previous != undefined || options.submit != undefined) {
			tabbed.bind('tabsselect', function(event, ui) {
				var next = $('#'+options.next);
				var previous = $('#'+options.previous);
				var submit = $('#'+options.submit);
				var length = tabbed.tabs('length');
				var selected = ui.index;
				if (selected+1 == length) {
					next.hide();
					submit.show();
					previous.show();
				} else {				
					next.show();
					submit.hide();
					previous.show();
					
					if (selected == 0) {
						previous.hide();
					}
				}
				
				$.scrollTo('#container', 500);
			});
		}
	}
	
	return tabbed;
}


/**
 * Attaches a confirmation dialog behavior
 *
 * #### Options:
 *
 * - `update` An "updatabale" to automatically update on close
 * - `onYes` Js function to call on confirmation, in addition to calling the href
 * - `yesTitle` Yes button title
 * - `onNo` Js function to call on cancellation
 * - `noTitle` No button title
 *
 * @param string id The id of the element to attach the behavior to
 * @param string message The message to display
 * @param object options Customizable options
 * @return boolean
 * @see CORE.updateables
 */ 
CORE.confirmation = function(id, message, options) {	
	if (id == undefined || message == undefined) {
		return false;
	}
	
	var el = $('#'+id);	
	
	// extract controller from url 
	var href = el.attr('href');
	
	var _defaultOptions = {
		update: '',
		yesTitle: 'Yes',
		onNo: 'CORE.closeModals();',
		noTitle: 'Cancel',
		onYes: ''
	};
		
	var useOptions;
	if (options != undefined) {
		useOptions = $.extend(_defaultOptions, options);
	} else {
		useOptions = _defaultOptions;
	}
	
	if (useOptions.update != '') {
		useOptions.onYes = 'CORE.request(\''+href+'\', {update:"'+useOptions.update+'"});CORE.closeModals();'+useOptions.onYes;
	} else {
		useOptions.onYes = 'CORE.request(\''+href+'\');CORE.closeModals();'+useOptions.onYes
	}	

	el.click(function(event) {
		// stop href
		event.preventDefault();
		
		var extraButtons = {};
		extraButtons[useOptions.yesTitle] = function () {eval(useOptions.onYes)};
		extraButtons[useOptions.noTitle] = function () {eval(useOptions.onNo)};

		$('#modal').dialog('option', 'width', 300);
		$('#modal').dialog('option', 'buttons', extraButtons);
		$('#modal').dialog('option', 'title', 'Confirmation');
		$('#modal').dialog('option', 'update', 'none');
		$('#modal').html(message);
		$('#modal').dialog('open');		
		
		// stop href
		return false;
	});
	
	return true;	
}

/**
 * Attaches WYSIWYG behavior to text area
 *
 * @param string id The Id of the element to attach it to
 * @return boolean True
 */
CORE.wysiwyg = function(id) {
	delayWysiwyg = function() {
		$('#'+id).wysiwyg({
			controls: {
				html : { visible : true },
				strikeThrough : { visible : true },
				underline     : { visible : true },
				separator00 : { visible : true },
				justifyLeft   : { visible : false },
				justifyCenter : { visible : false },
				justifyRight  : { visible : false },
				justifyFull   : { visible : false },
				separator01 : { visible : false },
				indent  : { visible : false },
				outdent : { visible : false },
				separator02 : { visible : true },
				subscript   : { visible : true },
				superscript : { visible : true },
				separator03 : { visible : true },
				undo : { visible : true },
				redo : { visible : true },
				separator04 : { visible : true },
				insertOrderedList    : { visible : true },
				insertUnorderedList  : { visible : true },
				insertHorizontalRule : { visible : true },
				createLink: { visible:false},
				insertImage: { visible:false},
				separator05 : { visible : true },
				separator06 : { visible : false },
				separator07 : { visible : false },
				header1 : { visible : false },
				h2 : { visible : false },
				h3 : { visible : false },
				cut   : { visible : false },
				copy  : { visible : false },
				paste : { visible : false }
			}		
		});
	}
	
	// delay applying the wysiwyg for a bit
	// in case of ajax, tabs, etc.
	setTimeout('delayWysiwyg()', 10);

	return true;	
}

/**
 * Attaches AutoComplete behavior to text field
 *
 * @param string id The Id of the element to attach it to
 * @param string datasource A url to a json datasource
 * @param function onSelect The JavaScript function to call when an item is selected. Item is passed as the first argument.
 * @return boolean True
 */
CORE.autoComplete = function(id, datasource, onSelect) {
	$('#'+id).autocomplete({
		source: function(request, response) {
			$.ajax({
				url: datasource,
				success: function(data) {
					response(data);
				},
				data: $('#'+id).attr('name')+'='+$('#'+id).val(),
				type: 'post'
			});
		},
		minLength: 3,
		select: function (event, ui) {
			if (onSelect != undefined) {
				onSelect(ui.item);
			}
		}
	});
	
	return true;
}


/**
 * Closes all modals and popups
 */
CORE.closeModals = function() {
	$('#content').dialog('close');
	$('#modal').dialog('close');
}