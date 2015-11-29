/*
	Class:     countDown
	Author:    David Walsh
	Website:   http://davidwalsh.name
	Version:   1.0.0
	Date:      11/30/2008
	Built For: jQuery 1.2.6
*/

jQuery.fn.countDown = function(settings,to) {
	settings = jQuery.extend({
		startFontSize: '24px',
		endFontSize: '24px',
		duration: 1000,
		startNumber: 50,
		endNumber: 0,
		callBack: function() { }
	}, settings);
	return this.each(function() {

		//where do we start?
		if(!to && to != settings.endNumber) { to = settings.startNumber; }

		//set the countdown to the starting number
		jQuery(this).text(to).css('fontSize',settings.startFontSize);

		//loopage
		jQuery(this).animate({
			'fontSize': settings.endFontSize
		},settings.duration,'',function() {
			if(to > settings.endNumber + 1) {
				jQuery(this).css('fontSize',settings.startFontSize).text(to - 1).countDown(settings,to - 1);
			}
			else
			{
				settings.callBack(this);
			}
		});
	});
};

/* sample usage

jQuery('#countdown').countDown({
	startNumber: 10,
	callBack: function(me) {
		jQuery(me).text('All done! This is where you give the reward!').css('color','#090');
	}
});

*/
