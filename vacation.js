/* Vacation Javascript */

if (window.rcmail) {

	// Updates aliases 
  	rcmail.addEventListener('plugin.alias_callback', function(evt) {
		$('#vacationAliases').val(evt.aliases);	
	});

    rcmail.addEventListener('init', function(evt) {
	    rcmail.register_command('plugin.vacationSave', function() { 
		    document.forms.vacationform.submit();
	    }, true);
    
	    // Invoke vacationdriver.class.php's methods
	    rcmail.register_command('getVacationAliases', function() { 
	    	rcmail.http_post('plugin.vacationAliases', 'a=1');
	    }, true);
  
    var tab = $('<li>').attr('id', 'settingstabpluginvacation').addClass('listitem preferences');
    rcmail.add_element(tab, 'tabs');

    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.vacation').html(rcmail.gettext('vacation.vacation')).appendTo(tab);
    button.bind('click', function(e) { return rcmail.command('plugin.vacation', this); });
    
    // Only enable the button if the element exists
    if ($("#aliasLink").length) {
	    rcmail.register_button('getVacationAliases','aliasLink','input');
	    $("#aliasLink").bind('click', function(e) {
	    	return rcmail.command('getVacationAliases', this); });
    }


    // add button and register command
    rcmail.register_command('plugin.vacation', function() { rcmail.goto_url('plugin.vacation') }, true);

    $('#vacation_activefrom').flatpickr({
	plugins: [new rangePlugin({ input: '#vacation_activeuntil'})],
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i:S'
    });

});
}
