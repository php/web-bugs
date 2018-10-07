'use strict';

window.addEventListener(
	'load',
	function () {
		document
			.querySelectorAll('select[name="in[package_name]"]')
			.forEach(
				function (select) {
					var packageGroup = document.createElement('select');
					packageGroup.name = 'in[package_group]';

					select
						.querySelectorAll(':scope optgroup')
						.forEach(
							function (optgroup) {
								var packageOption = document.createElement('option'),
									groupName = optgroup.label;

								packageOption.textContent = groupName;
								packageOption.value = groupName;
								packageGroup.appendChild(packageOption);
							}
						);

					select
						.parentNode
						.insertBefore(packageGroup, select);
				}
			);
	}
);
