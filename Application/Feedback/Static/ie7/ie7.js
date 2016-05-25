/* To avoid CSS expressions while still supporting IE 7 and IE 6, use this script */
/* The script tag referencing this file must be placed before the ending body tag. */

/* Use conditional comments in order to target IE 7 and older:
	<!--[if lt IE 8]><!-->
	<script src="ie7/ie7.js"></script>
	<!--<![endif]-->
*/

(function() {
	function addIcon(el, entity) {
		var html = el.innerHTML;
		el.innerHTML = '<span style="font-family: \'dreamicon\'">' + entity + '</span>' + html;
	}
	var icons = {
		'dream-back': '&#xe64e;',
		'dream-forward': '&#xe64f;',
		'dream-add': '&#xe661;',
		'dream-sub': '&#xe662;',
		'dream-up': '&#xe670;',
		'dream-down': '&#xe671;',
		'dream-dropdown': '&#xe6b8;',
		'dream-back2': '&#xe6e0;',
		'dream-home3': '&#xe90c;',
		'dream-office': '&#xe900;',
		'dream-phone': '&#xe901;',
		'dream-phone-hang-up': '&#xe902;',
		'dream-envelop': '&#xe903;',
		'dream-compass': '&#xe904;',
		'dream-bell': '&#xe905;',
		'dream-users': '&#xe906;',
		'dream-user-tie': '&#xe907;',
		'dream-gift': '&#xe908;',
		'dream-earth': '&#xe909;',
		'dream-wink': '&#xe90a;',
		'dream-ctrl': '&#xe90d;',
		'dream-paperplane': '&#xe90b;',
		'0': 0
		},
		els = document.getElementsByTagName('*'),
		i, c, el;
	for (i = 0; ; i += 1) {
		el = els[i];
		if(!el) {
			break;
		}
		c = el.className;
		c = c.match(/dream-[^\s'"]+/);
		if (c && icons[c[0]]) {
			addIcon(el, icons[c[0]]);
		}
	}
}());
