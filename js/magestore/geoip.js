/*
	Update billing and shipping address when filled postcode
*/
function compare(a, b) {
	if (a.sort_order < b.sort_order)
		return -1;
	else if (a.sort_order > b.sort_order)
		return 1;
	else 
		return 0;
}
function easy_updateRegion(selectId, regions)
{
	var nzRegions = [];
	for (regionId in regions["NZ"]) {
		var nzRegion = {regionId:"", name:"", sort_order:0};
		nzRegion.regionId = regionId;
		nzRegion.name = regions["NZ"][regionId].name;
		nzRegion.sort_order = regions["NZ"][regionId].sort_order;
		nzRegions.push(nzRegion);
	}

	nzRegions.sort(compare);
	//alert(JSON.stringify(nzRegions));
	
	var select = document.getElementById(selectId);
	for (i = 0; i < nzRegions.length; i++) { 
		option = document.createElement('OPTION');
		option.value = nzRegions[i].regionId;
		option.text = nzRegions[i].name;
		option.title = nzRegions[i].name;

		select.add(option);
	}
	select.style.display = '';
}

function updateRegion(countryEl, regionTextEl, regionSelectEl, regions, disableAction, zipEl)
	{
		if (regions[countryEl.value]) {
			var i, option, region, def;

			def = regionSelectEl.getAttribute('defaultValue');
			if (regionTextEl) {
				if (!def) {
					def = regionTextEl.value.toLowerCase();
				}
				regionTextEl.value = '';
			}

			regionSelectEl.options.length = 1;
			for (regionId in regions[countryEl.value]) {
				region = regions[countryEl.value][regionId];

				option = document.createElement('OPTION');
				option.value = regionId;
				option.text = region.name.stripTags();
				option.title = region.name;

				if (regionSelectEl.options.add) {
					regionSelectEl.options.add(option);
				} else {
					regionSelectEl.appendChild(option);
				}

				if (regionId==def || (region.name && region.name.toLowerCase()==def) ||
					(region.name && region.code.toLowerCase()==def)
				) {
					regionSelectEl.value = regionId;
				}
			}

			if (disableAction=='hide') {
				if (regionTextEl) {
					regionTextEl.style.display = 'none';
				}

				regionSelectEl.style.display = '';
			} else if (disableAction=='disable') {
				if (regionTextEl) {
					regionTextEl.disabled = true;
				}
				regionSelectEl.disabled = false;
			}
			setMarkDisplay(regionSelectEl, true);
		} else {
			if (disableAction=='hide') {
				if (regionTextEl) {
					regionTextEl.style.display = '';
				}
				regionSelectEl.style.display = 'none';
				Validation.reset(regionSelectEl);
			} else if (disableAction=='disable') {
				if (regionTextEl) {
					regionTextEl.disabled = false;
				}
				regionSelectEl.disabled = true;
			} else if (disableAction=='nullify') {
				regionSelectEl.options.length = 1;
				regionSelectEl.value = '';
				regionSelectEl.selectedIndex = 0;
				lastCountryId = '';
			}
			setMarkDisplay(regionSelectEl, false);
		}

		_checkRegionRequired(countryEl, regionTextEl, regionSelectEl, regions['config']);
		// Make Zip and its label required/optional
		var zipUpdater = new ZipUpdater(countryEl.value, zipEl);
		zipUpdater.update();
	}

	function setMarkDisplay(elem, display)
	{
		elem = $(elem);
		var labelElement = elem.up(0).down('label > span.required') ||
						   elem.up(1).down('label > span.required') ||
						   elem.up(0).down('label.required > em') ||
						   elem.up(1).down('label.required > em');
		if(labelElement) {
			inputElement = labelElement.up().next('input');
			if (display) {
				labelElement.show();
				if (inputElement) {
					inputElement.addClassName('required-entry');
				}
			} else {
				labelElement.hide();
				if (inputElement) {
					inputElement.removeClassName('required-entry');
				}
			}
		}
	}
	
	function _checkRegionRequired(countryEl, regionTextEl, regionSelectEl, config)
	{
		var label, wildCard;
		var elements = [regionTextEl, regionSelectEl];
		var that = this;
		if (typeof config == 'undefined') {
			return;
		}
		var regionRequired = config.regions_required.indexOf(countryEl.value) >= 0;

		elements.each(function(currentElement) {
			Validation.reset(currentElement);
			label = $$('label[for="' + currentElement.id + '"]')[0];
			if (label) {
				wildCard = label.down('em') || label.down('span.required');
				if (!that.config.show_all_regions) {
					if (regionRequired) {
						label.up().show();
					} else {
						label.up().hide();
					}
				}
			}

			if (label && wildCard) {
				if (!regionRequired) {
					wildCard.hide();
					if (label.hasClassName('required')) {
						label.removeClassName('required');
					}
				} else if (regionRequired) {
					wildCard.show();
					if (!label.hasClassName('required')) {
						label.addClassName('required')
					}
				}
			}

			if (!regionRequired) {
				if (currentElement.hasClassName('required-entry')) {
					currentElement.removeClassName('required-entry');
				}
				if ('select' == currentElement.tagName.toLowerCase() &&
					currentElement.hasClassName('validate-select')) {
					currentElement.removeClassName('validate-select');
				}
			} else {
				if (!currentElement.hasClassName('required-entry')) {
					currentElement.addClassName('required-entry');
				}
				if ('select' == currentElement.tagName.toLowerCase() &&
					!currentElement.hasClassName('validate-select')) {
					currentElement.addClassName('validate-select');
				}
			}
		});
	}	