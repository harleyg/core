/**
 * Js needed for the main navigation area
 *
 * @todo remove default text swap on the search bar when HTML5 comes in to play
 */

CORE.initNavigation = function() {
	// attach auto complete
	CORE.autoComplete("SearchQuery", $('#nav-search form').attr('action')+'.json', function(item) {
		redirect(item.action);
	});
	CORE.defaultSearchText = $("#SearchQuery").val();
	$("#SearchQuery").focus(function() {if ($(this).val() == CORE.defaultSearchText) {
		$(this).val("");
		$(this).attr('class', 'search-over');
	}});
	$("#SearchQuery").blur(function() {if ($(this).val() == "") {
		$(this).val(CORE.defaultSearchText);
		$(this).attr('class', 'search-out');
	}});

	$('#nav-ministries .campuses input:radio').change(function() {
		$('#nav-ministries li[id^=campus]').hide();
		$('#nav-ministries li#campus-'+$(this).val()).show();
	});
	$('#nav-ministries .campuses input:radio:first').change();

	$('#nav-notifications ul li.notification').mouseenter(function() {
		var name = $(this).attr('id').split('-');
		var id = name[1];
		if ($(this).children('p').hasClass('unread')) {
			CORE.readNotification(id, this);
		}
		$(this).children('a.delete').show();
	});
	$('#nav-notifications ul li.notification').mouseleave(function() {
		$(this).children('a.delete').hide();
	});
	$('#nav-notifications ul li.notification a.delete').click(function(event) {
		event.preventDefault();
		CORE.request(this.href);
		$(this).parent().fadeOut('fast');
		return false;
	});

	$('#nav-calendar').one('mouseover', function() {
		var cal = $(this).find('.fc')
		cal.fullCalendar('render');
	});
}

/**
 * Marks a notification as read
 *
 * @param id integer The id of the notification to read
 * @param ele Element The notification element
 */
CORE.readNotification = function(id, ele) {
	CORE.request('/notifications/read/'+id);
	$(ele).children('p').animate({borderLeftColor:'#fff'}, {
		duration: 'slow',
		complete: function() { $(this).removeClass('unread').addClass('read'); }
	});
	var count = Number($('.notification-count').text()) - 1;
	if (count == 0) {
		$('.notification-count').fadeOut('fast');
	} else {
		$('.notification-count').fadeOut('fast').text(count).fadeIn('fast');
	}
}

/**
 * Attaches tooltips to event dates
 *
 * @param ele Element The id attribute of the calendar
 */
CORE.createEventTooltips = function(ele) {
	$('#'+ele+' .fc-event').each(function() {
		var classes = $(this).attr('class').split(/\s+/);
		for (var c in classes) {
			if (classes[c].match(/(\d{4})-(\d{1,2})-(\d{1,2})/)) {
				var html = $(this).html();
				$('.event.'+classes[c]).children('.fc-day-content').children('div').filter(function() {
					// don't add duplicate events
					return $(this).html().indexOf(html) == -1;
				}).append(html);
			}
		}
	});
	$('#'+ele+' .event').each(function() {
		CORE.tooltip(this, $(this).children('.fc-day-content').children('div'), false);
	});
}

/**
 * Removes tooltips to event dates, event styling and data
 *
 * @param ele Element The id attribute of the calendar
 */
CORE.removeEventTooltips = function(ele) {
	$('#'+ele+' .event').each(function() {
		$(this).qtip('destroy');
		$(this).children('.fc-day-content').children('div').html('');
		$(this).removeClass('event');
		var dates = $(this).data('dates');
		for (var d in dates) {
			$(this).removeClass(dates[d]);
		}
		$(this).removeData('dates');
	});
}