{if !$org_id && $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.create_scheme')}
	<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
		<button type="button" onclick="genericAjaxPopup('peek','c=rotationmanager&a=showRotationSchemePeek&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {'net.pixelinstrument.rotation_manager.new_rotation_scheme'|devblocks_translate}</button>
	</form>
{/if}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}