VarienForm=Class.create();VarienForm.prototype={initialize:function(formId,firstFieldFocus){this.form=$(formId);if(!this.form){return;}
this.cache=$A();this.currLoader=false;this.currDataIndex=false;this.validator=new Validation(this.form);this.elementFocus=this.elementOnFocus.bindAsEventListener(this);this.elementBlur=this.elementOnBlur.bindAsEventListener(this);this.childLoader=this.onChangeChildLoad.bindAsEventListener(this);this.highlightClass='highlight';this.extraChildParams='';this.firstFieldFocus=firstFieldFocus||false;this.bindElements();if(this.firstFieldFocus){try{Form.Element.focus(Form.findFirstElement(this.form))}
catch(e){}}},submit:function(url){if(this.validator&&this.validator.validate()){this.form.submit();}
return false;},bindElements:function(){var elements=Form.getElements(this.form);for(var row in elements){if(elements[row].id){Event.observe(elements[row],'focus',this.elementFocus);Event.observe(elements[row],'blur',this.elementBlur);}}},elementOnFocus:function(event){var element=Event.findElement(event,'fieldset');if(element){Element.addClassName(element,this.highlightClass);}},elementOnBlur:function(event){var element=Event.findElement(event,'fieldset');if(element){Element.removeClassName(element,this.highlightClass);}},setElementsRelation:function(parent,child,dataUrl,first){if(parent=$(parent)){if(!this.cache[parent.id]){this.cache[parent.id]=$A();this.cache[parent.id]['child']=child;this.cache[parent.id]['dataUrl']=dataUrl;this.cache[parent.id]['data']=$A();this.cache[parent.id]['first']=first||false;}
Event.observe(parent,'change',this.childLoader);}},onChangeChildLoad:function(event){element=Event.element(event);this.elementChildLoad(element);},elementChildLoad:function(element,callback){this.callback=callback||false;if(element.value){this.currLoader=element.id;this.currDataIndex=element.value;if(this.cache[element.id]['data'][element.value]){this.setDataToChild(this.cache[element.id]['data'][element.value]);}
else{new Ajax.Request(this.cache[this.currLoader]['dataUrl'],{method:'post',parameters:{"parent":element.value},onComplete:this.reloadChildren.bind(this)});}}},reloadChildren:function(transport){var data=eval('('+transport.responseText+')');this.cache[this.currLoader]['data'][this.currDataIndex]=data;this.setDataToChild(data);},setDataToChild:function(data){if(data.length){var child=$(this.cache[this.currLoader]['child']);if(child){var html='<select name="'+child.name+'" id="'+child.id+'" class="'+child.className+'" title="'+child.title+'" '+this.extraChildParams+'>';if(this.cache[this.currLoader]['first']){html+='<option value="">'+this.cache[this.currLoader]['first']+'</option>';}
for(var i in data){if(data[i].value){html+='<option value="'+data[i].value+'"';if(child.value&&(child.value==data[i].value||child.value==data[i].label)){html+=' selected';}
html+='>'+data[i].label+'</option>';}}
html+='</select>';Element.insert(child,{before:html});Element.remove(child);}}
else{var child=$(this.cache[this.currLoader]['child']);if(child){var html='<input type="text" name="'+child.name+'" id="'+child.id+'" class="'+child.className+'" title="'+child.title+'" '+this.extraChildParams+'>';Element.insert(child,{before:html});Element.remove(child);}}
this.bindElements();if(this.callback){this.callback();}}}
RegionUpdater=Class.create();RegionUpdater.prototype={initialize:function(countryEl,regionTextEl,regionSelectEl,regions,disableAction,zipEl)
{this.countryEl=$(countryEl);this.regionTextEl=$(regionTextEl);this.regionSelectEl=$(regionSelectEl);this.zipEl=$(zipEl);this.config=regions['config'];delete regions.config;this.regions=regions;this.disableAction=(typeof disableAction=='undefined')?'hide':disableAction;this.zipOptions=(typeof zipOptions=='undefined')?false:zipOptions;if(this.regionSelectEl.options.length<=1){this.update();}
Event.observe(this.countryEl,'change',this.update.bind(this));},_checkRegionRequired:function()
{var label,wildCard;var elements=[this.regionTextEl,this.regionSelectEl];var that=this;if(typeof this.config=='undefined'){return;}
var regionRequired=this.config.regions_required.indexOf(this.countryEl.value)>=0;elements.each(function(currentElement){Validation.reset(currentElement);label=$$('label[for="'+currentElement.id+'"]')[0];if(label){wildCard=label.down('em')||label.down('span.required');if(!that.config.show_all_regions){if(regionRequired){label.up().show();}else{label.up().hide();}}}
if(label&&wildCard){if(!regionRequired){wildCard.hide();if(label.hasClassName('required')){label.removeClassName('required');}}else if(regionRequired){wildCard.show();if(!label.hasClassName('required')){label.addClassName('required')}}}
if(!regionRequired){if(currentElement.hasClassName('required-entry')){currentElement.removeClassName('required-entry');}
if('select'==currentElement.tagName.toLowerCase()&&currentElement.hasClassName('validate-select')){currentElement.removeClassName('validate-select');}}else{if(!currentElement.hasClassName('required-entry')){currentElement.addClassName('required-entry');}
if('select'==currentElement.tagName.toLowerCase()&&!currentElement.hasClassName('validate-select')){currentElement.addClassName('validate-select');}}});},sortSelect : function (){
		elem = this.regionSelectEl;
		var tmpAry = new Array();
		var currentVal = $(elem).value;
		for (var i=0;i<$(elem).options.length;i++) {
			if (i == 0) continue;
			tmpAry[i-1] = new Array();
			tmpAry[i-1][0] = $(elem).options[i].sortorder;
			tmpAry[i-1][1] = $(elem).options[i].text;
			tmpAry[i-1][2] = $(elem).options[i].value;
		}
		tmpAry.sort((function(index){
			return function(a, b){
				return (a[index] === b[index] ? 0 : (a[index] < b[index] ? -1 : 1));
			};
		})(0));
		while ($(elem).options.length > 0) {
		   $(elem).options[0] = null;
		}
		
		for (var i=1;i<=tmpAry.length;i++) {
			var op = new Option(tmpAry[i-1][1], tmpAry[i-1][2]);
			$(elem).options[i] = op;
		}
		$(elem).value = currentVal;
		return;
	},update:function()
{if(this.regions[this.countryEl.value]){var i,option,region,def;def=this.regionSelectEl.getAttribute('defaultValue');if(this.regionTextEl){if(!def){def=this.regionTextEl.value.toLowerCase();}
this.regionTextEl.value='';}
this.regionSelectEl.options.length=1;for(regionId in this.regions[this.countryEl.value]){region=this.regions[this.countryEl.value][regionId];option=document.createElement('OPTION');option.value=regionId;option.text=region.name.stripTags();option.title=region.name;option.sortorder = region.sort_order;if(this.regionSelectEl.options.add){this.regionSelectEl.options.add(option);}else{this.regionSelectEl.appendChild(option);}
if(regionId==def||(region.name&&region.name.toLowerCase()==def)||(region.name&&region.code.toLowerCase()==def)){this.regionSelectEl.value=regionId;}}
this.sortSelect();
if(this.disableAction=='hide'){if(this.regionTextEl){this.regionTextEl.style.display='none';}
this.regionSelectEl.style.display='';}else if(this.disableAction=='disable'){if(this.regionTextEl){this.regionTextEl.disabled=true;}
this.regionSelectEl.disabled=false;}
this.setMarkDisplay(this.regionSelectEl,true);}else{this.regionSelectEl.options.length=1;if(this.disableAction=='hide'){if(this.regionTextEl){this.regionTextEl.style.display='';}
this.regionSelectEl.style.display='none';Validation.reset(this.regionSelectEl);}else if(this.disableAction=='disable'){if(this.regionTextEl){this.regionTextEl.disabled=false;}
this.regionSelectEl.disabled=true;}else if(this.disableAction=='nullify'){this.regionSelectEl.options.length=1;this.regionSelectEl.value='';this.regionSelectEl.selectedIndex=0;this.lastCountryId='';}
this.setMarkDisplay(this.regionSelectEl,false);}
this._checkRegionRequired();var zipUpdater=new ZipUpdater(this.countryEl.value,this.zipEl);zipUpdater.update();},setMarkDisplay:function(elem,display){elem=$(elem);var labelElement=elem.up(0).down('label > span.required')||elem.up(1).down('label > span.required')||elem.up(0).down('label.required > em')||elem.up(1).down('label.required > em');if(labelElement){inputElement=labelElement.up().next('input');if(display){labelElement.show();if(inputElement){inputElement.addClassName('required-entry');}}else{labelElement.hide();if(inputElement){inputElement.removeClassName('required-entry');}}}}}
ZipUpdater=Class.create();ZipUpdater.prototype={initialize:function(country,zipElement)
{this.country=country;this.zipElement=$(zipElement);},update:function()
{if(typeof optionalZipCountries=='undefined'){return false;}
if(this.zipElement!=undefined){Validation.reset(this.zipElement)
this._setPostcodeOptional();}else{Event.observe(window,"load",this._setPostcodeOptional.bind(this));}},_setPostcodeOptional:function()
{this.zipElement=$(this.zipElement);if(this.zipElement==undefined){return false;}
var label=$$('label[for="'+this.zipElement.id+'"]')[0];if(label!=undefined){var wildCard=label.down('em')||label.down('span.required');}
if(optionalZipCountries.indexOf(this.country)!=-1){while(this.zipElement.hasClassName('required-entry')){this.zipElement.removeClassName('required-entry');}
if(wildCard!=undefined){wildCard.hide();}}else{this.zipElement.addClassName('required-entry');if(wildCard!=undefined){wildCard.show();}}}}