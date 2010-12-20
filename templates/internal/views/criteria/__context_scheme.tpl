<input type="hidden" name="oper" value="in">

<b>{$translate->_('net.pixelinstrument.rotation_manager.rotation_schemes')|capitalize}:</b><br>

<div style="margin:0px 0px 10px 10px;">
<button type="button" class="chooser_group" onclick="criteriaChooserClick(this);"><span class="cerb-sprite sprite-add"></span></button>
</div>

<script type="text/javascript">
	function criteriaChooserClick(button) {
		$chooser=genericAjaxPopup('filters{$id}','c=internal&a=chooserOpen&context=net.pixelinstrument.contexts.rotation_scheme',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			event.stopPropagation();
			$button = $(button);
			$label = $button.prev('div.chooser-container');
			if(0==$label.length)
				$label = $('<div class="chooser-container"></div>').insertBefore($button);
			for(idx in event.labels) {
				if(0==$label.find('input:hidden[value='+event.values[idx]+']').length)
					$label.append($('<div><button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> '+event.labels[idx]+'<input type="hidden" name="rotation_scheme_id[]" value="'+event.values[idx]+'"></div>'));
			}
		});
	}
</script>

