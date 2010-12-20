{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center;background-color:rgb(232,242,254);border-color:rgb(121,183,231);"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap" style="background-color:rgb(232,242,254);border-color:rgb(121,183,231);">
			<a href="javascript:;" style="color:rgb(74,110,158);" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody onmouseover="$(this).find('tr').addClass('hover');" onmouseout="$(this).find('tr').removeClass('hover');" onclick="if(getEventTarget(event)=='TD') { var $chk=$(this).find('input:checkbox:first');if(!$chk) return;$chk.attr('checked', !$chk.is(':checked')); } ">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" title="{$result.r_name}" value="{$result.r_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
				<a href="#" class="subject">{if !empty($result.r_name)}{$result.r_name|escape}{else}{$translate->_('net.pixelinstrument.rotation_manager.new_rotation_scheme')|capitalize}{/if}</a> {if $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.update_scheme')}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=rotationmanager&a=showRotationSchemePeek&id={$result.r_id}&view_id={$view->id}',null,false,'550');"><span class="ui-icon ui-icon-newwin" style="display:inline-block;vertical-align:middle;" title="{$translate->_('views.peek')}"></span></a>{/if}
				
				{$object_workers = DAO_ContextLink::getContextLinks(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
				{if isset($object_workers.{$result.r_id})}
				<div style="display:inline;padding-left:5px;">
				{foreach from=$object_workers.{$result.r_id} key=worker_id item=worker name=workers}
					{if isset($workers.{$worker_id})}
						<span style="color:rgb(150,150,150);">
						{$workers.{$worker_id}->getName()}{if !$smarty.foreach.workers.last}, {/if}
						</span>
					{/if}
				{/foreach}
				</div>
				{/if}
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="r_id"}
				<td>{$result.r_id}&nbsp;</td>
			{elseif $column=="r_worker_id"}
				<td>{if isset($workers.{$result.r_worker_id})}{$workers.{$result.r_worker_id}->getName()}{/if}</td>
			{elseif $column=="r_group_id"}
				<td>{if isset($groups.{$result.r_group_id})}{$groups.{$result.r_group_id}->name}{/if}</td>
			{elseif $column=="r_active"}
				<td>{if $result.r_active}yes{else}no{/if}</td>
			{else}
				<td>{$result.$column|escape}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td align="left" valign="top" id="{$view->id}_actions">
			<button type="button" class="devblocks-chooser-add-selected"><span class="cerb-sprite sprite-add"></span> Add Selected</button>
		</td>
		<td align="right" valign="top" nowrap="nowrap">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>
