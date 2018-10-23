'use strict';

window.addEventListener(
	'load',
	function () {
		document
			.querySelectorAll('select[name="in[package_name]"]')
			.forEach(
				function (select) {
					var packageGroup = document.createElement('select'),
						initialValue = select.value,
						initialGroup = select.querySelector(':scope option[value="' + initialValue + '"]').parentNode;

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

								optgroup
									.querySelectorAll(':scope option')
									.forEach(
										function (option) {
											option.setAttribute('bug-group', groupName);
										}
									);
							}
						);

					var instructions = select.querySelector(':scope option[value="none"]');
					if (instructions instanceof HTMLElement) {
						instructions.textContent = 'Select a category';
					}

					select
						.querySelectorAll(':scope optgroup')
						.forEach(
							function (optgroup) {
								optgroup.style.display = 'none';
							}
						);

					function updateGroup() {
						select.disabled = false;

						if (instructions instanceof HTMLElement) {
							instructions.style.display = 'none';
						}

						var previousOptions = select.querySelectorAll(':scope > option:not([value=none])'),
							nextLabel = packageGroup.value,
							nextGroup = select.querySelector(':scope optgroup[label="' + nextLabel + '"]');

						if (previousOptions.length !== 0) {
							var previousLabel = previousOptions[0].getAttribute('bug-group'),
								previousGroup = select.querySelector(':scope optgroup[label="' + previousLabel + '"]');

							moveOptions(select, previousGroup);
						}

						moveOptions(nextGroup, select);

						select.selectedIndex = -1;
					}

					function moveOptions(from, to) {
						from.querySelectorAll(':scope > option')
							.forEach(
								function (option) {
									to.appendChild(option);
								}
							);
					}

					packageGroup.addEventListener('click', updateGroup);
					packageGroup.addEventListener('change', updateGroup);

					if (initialGroup instanceof HTMLOptGroupElement) {
						packageGroup.value = initialGroup.label;
						moveOptions(initialGroup, select);
					} else {
						select.disabled = true;
						select.value = null;
						packageGroup.selectedIndex = -1;
					}

					packageGroup.style.marginRight = '.5em';
					[select, packageGroup].forEach(
						function (element) {
							element.size = 12;
							element.style.width = '22em';
						}
					);

					select
						.parentNode
						.insertBefore(packageGroup, select);
				}
			);
	}
);
