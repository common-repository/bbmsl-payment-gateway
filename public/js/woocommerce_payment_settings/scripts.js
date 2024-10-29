window.addEventListener('DOMContentLoaded', function () {
	const toggle_site_checkbox = document.getElementById('toggle_site_checkbox');
	const toggle_site_live = document.getElementById('toggle_site_live');
	const toggle_site_test = document.getElementById('toggle_site_testing');

	[...document.querySelectorAll('[data-copy-source]')].map(function(e){
		e.addEventListener('click', function (event) {
			const target = event.target;
			if (!target.hasAttribute('data-copy-source')) { return; }
			const target_id = target.getAttribute('data-copy-source');
			const source = document.getElementById(target_id);
			if(source){
				source.select();
				source.setSelectionRange(0, 99999); /* For mobile devices */
				const value = source.value.trim();
				if(value.length === 0){
				  return alert('Key not yet generated.');
				}
				if(document.execCommand('copy')){
					return alert('Copied.');
				}
				navigator.clipboard.writeText(value).then(function(){
					return alert('Copied.');
				}, function(){
					return alert('Copy failed.');
				});
			}
		});
	});

	if (toggle_site_checkbox && toggle_site_live && toggle_site_test) {
		toggle_site_checkbox.addEventListener('change', function () {
			if (toggle_site_checkbox.checked) {
				toggle_site_live.classList.add('disabled');
				toggle_site_test.classList.remove('disabled');
			} else {
				toggle_site_live.classList.remove('disabled');
				toggle_site_test.classList.add('disabled');
			}
		});
	}
});