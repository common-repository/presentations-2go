(function() {
	tinymce.PluginManager.add('P2GoWA', function( editor, url ) {
		editor.addButton('P2GoWA_button', {
			text: 'P2Go',
			icon: false,
			onclick: function() {
				tb_show('Presentations 2Go','#TB_inline?height=707&width=900&inlineId=P2GoWizard&class=thickbox');
				window.setTimeout(function() {
				if(tinymce.dom.DomQuery != null) {
					tinymce.dom.DomQuery('#TB_window').css( {'overflow-y': 'scroll', 'overflow-x': 'hidden' } );
				} else {
					document.getElementById('TB_window').style = "overflow-y: scroll;overflow-x: auto;";
				}
				}, 10);
			}
    	});
	});
})();