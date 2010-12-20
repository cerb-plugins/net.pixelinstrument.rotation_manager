{if $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.update_scheme')}
    <form enctype="multipart/form-data" action="{devblocks_url}{/devblocks_url}" method="post" id="formRotationSchemePeek" name="formRotationSchemePeek" onsubmit="return false">
        <h2>
            {if !$rotation_scheme}
                {$translate->_('net.pixelinstrument.rotation_manager.new_rotation_scheme')|capitalize}
            {elseif $rotation_scheme->name}
                {$rotation_scheme->name}
            {else}
                {$translate->_('net.pixelinstrument.rotation_manager.new_rotation_scheme')|capitalize}
            {/if}
        </h2>
        <input type="hidden" name="c" value="rotationmanager" />
        <input type="hidden" name="a" value="saveRotationSchemePeek" />
        <input type="hidden" name="id" value="{if $rotation_scheme}{$rotation_scheme->id}{else}0{/if}" />
        <input type="hidden" name="do_delete" value="0" />
    
        <fieldset>
            <legend>{'common.properties'|devblocks_translate}</legend>
            
            <table cellpadding="0" cellspacing="2" border="0" width="98%">
                <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('net.pixelinstrument.rotation_manager.group')|capitalize}: </td>
                    <td width="100%">
                        {if $org_id}
                            {$organization->name}
                            <input type="hidden" name="org_id" value="{$org_id}" />
                        {else}
                            <select id="group_id" name="group_id">
                               <option value="">--- {$translate->_('net.pixelinstrument.rotation_manager.select_group')|capitalize} ---</option>
                               {foreach from=$groups item=gr}
                                   <option value="{$gr.g_id}" {if $rotation_scheme->group_id == $gr.g_id}selected{/if}>{$gr.g_name}</option>
                               {/foreach}
                            </select>
                        {/if}
                    </td>
                </tr>
                
                <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('net.pixelinstrument.rotation_manager.name')|capitalize}:</td>
                    <td width="100%">
                        <input name="name" value="{$rotation_scheme->name}" /><br/>
                    </td>
                </tr>
                
                <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('net.pixelinstrument.rotation_manager.active')|capitalize}:</td>
                    <td width="100%">
                        <input name="active" type="checkbox" {if $rotation_scheme->active}checked{/if} />
                    </td>
                </tr>
                
                <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('net.pixelinstrument.rotation_manager.close_days')|capitalize}:</td>
                    <td width="100%">
                        <input class="days" name="close_days" value="{$rotation_scheme->close_days}" />
                    </td>
                </tr>
                
               <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('net.pixelinstrument.rotation_manager.alert_days')|capitalize}:</td>
                    <td width="100%">
                        <input class="days" name="alert_days" value="{$rotation_scheme->alert_days}" />
                    </td>
                </tr>
                
                <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{'common.workers'|devblocks_translate|capitalize}: </td>
                    <td width="100%">
                        <ul class="chooser-container bubbles">
                            {foreach from=$workers item=worker}
                                {assign var="worker_id" value=$worker->id}
                                <li class="worker_group {foreach from=$worker_groups.$worker_id item=group_id}worker_group_{$group_id} {/foreach}">{$workers.$worker_id->getName()|escape}<input type="checkbox" name="worker_id[]" value="{$worker->id}" {if isset($context_workers.$worker_id)}checked{/if}></li>
                            {/foreach}
                        </ul>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        {if !empty($custom_fields)}
        <fieldset>
            <legend>{'common.custom_fields'|devblocks_translate}</legend>
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
        </fieldset>
        {/if}
    
        <br/>
        <button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
        
        {if $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.delete_scheme') && !empty($rotation_scheme)}
            <button type="button" onclick="if(confirm('{$translate->_('net.pixelinstrument.rotation_manager.rotation_scheme.confirm_delete')}')) { $('#formRotationSchemePeek input[name=do_delete]').val('1'); genericAjaxPopupPostCloseReloadView('peek', 'formRotationSchemePeek', '{$view_id}', false, 'rotation_scheme_save'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>
        {/if}
    </form>
    
    <script type="text/javascript">
        $popup = genericAjaxPopupFetch('peek');
        $popup.one('popup_open',function(event,ui) {
            $('#formRotationSchemePeek :input:text:first').focus().select();
            $('#formRotationSchemePeek :input[name=name]').css({
                'width' : '80%',
                'border' : '1px solid #AAA'
            });
            
            $('#formRotationSchemePeek :input.days').css({
                'width' : '10%',
                'border' : '1px solid #AAA'
            });
            
            $('#formRotationSchemePeek select[name=group_id]').change(function(event){
                var group_id = $(this).val();
                
                $('#formRotationSchemePeek li.worker_group :input').attr('name', 'worker_disabled[]');
                
                $('#formRotationSchemePeek li.worker_group').hide();
                
                $('#formRotationSchemePeek li.worker_group').each(function() {
                    if($(this).hasClass('worker_group_'+group_id)) {
                        $(this).show();
                        
                        $(this).children('input:first').attr('name', 'worker_id[]');
                    }
                });
            });
            
            $('#formRotationSchemePeek select[name=group_id]').change();
        });
        
        $('#formRotationSchemePeek button.chooser_worker').each(function() {
            ajax.chooser(this,'cerberusweb.contexts.worker','worker_id');
        });
        
        $.validator.addMethod("alert_days_less", function(value, element) {
            return (parseInt(value) < parseInt($("#formRotationSchemePeek :input[name=close_days]").val()));
        });
        
        $("#formRotationSchemePeek").validate( {
			rules: {
                group_id: "required",
				name: "required",
                close_days: {
                    required: true,
                    min: 1
                },
                alert_days: {
                    required: true,
                    min: 1,
                    alert_days_less: true
                }
			},
			messages: {
                group_id: "{$translate->_('net.pixelinstrument.rotation_manager.error.missing_group_id')}",
				name: "{$translate->_('net.pixelinstrument.rotation_manager.error.missing_name')}",
                close_days: {
                    required: "{$translate->_('net.pixelinstrument.rotation_manager.error.missing_close_days')}",
                    min: "{$translate->_('net.pixelinstrument.rotation_manager.error.days_not_number')}"
                },
                alert_days: {
                    required: "{$translate->_('net.pixelinstrument.rotation_manager.error.missing_alert_days')}",
                    min: "{$translate->_('net.pixelinstrument.rotation_manager.error.days_not_number')}",
                    alert_days_less: "{$translate->_('net.pixelinstrument.rotation_manager.error.alert_days_less')}"
                }
            },
            submitHandler: function(form) {
                genericAjaxPopupPostCloseReloadView('peek','formRotationSchemePeek','{$view_id}',false,'rotation_scheme_save');
            }
		} );
        
        $("<style type='text/css'> label.error{ display: block; margin-bottom: 5px;} </style>").appendTo("head");
    </script>
{else if $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.update_themselves')}
<form enctype="multipart/form-data" action="{devblocks_url}{/devblocks_url}" method="post" id="formRotationSchemePeek" name="formRotationSchemePeek" onsubmit="return false">
        <h2>
            {if !$rotation_scheme}
                {$translate->_('net.pixelinstrument.rotation_manager.rotation_scheme')|capitalize}
            {elseif $rotation_scheme->name}
                {$rotation_scheme->name}
            {else}
                {$translate->_('net.pixelinstrument.rotation_manager.new_rotation_scheme')|capitalize}
            {/if}
        </h2>
        <input type="hidden" name="c" value="rotationmanager" />
        <input type="hidden" name="a" value="saveRotationSchemeSingleWorker" />
        <input type="hidden" name="id" value="{if $rotation_scheme}{$rotation_scheme->id}{else}0{/if}" />
    
        <fieldset>
            <legend>{'common.properties'|devblocks_translate}</legend>
            
            <table cellpadding="0" cellspacing="2" border="0" width="98%">
                <tr>
                    <td width="0%" nowrap="nowrap" valign="top" align="right">{'common.workers'|devblocks_translate|capitalize}: </td>
                    <td width="100%">
                        <ul class="chooser-container bubbles">
                            {foreach from=$workers item=worker}
                                {assign var="worker_id" value=$worker->id}
                                {if isset($context_workers.$worker_id) || $worker->id == $active_worker->id}
                                    <li class="worker_group {foreach from=$worker_groups.$worker_id item=group_id}worker_group_{$group_id} {/foreach}">{$workers.$worker_id->getName()|escape}{if $worker->id == $active_worker->id}<input type="checkbox" name="worker_in_scheme" value="1" {if isset($context_workers.$worker_id)}checked{/if}>{/if}</li>
                                {/if}
                            {/foreach}
                        </ul>
                    </td>
                </tr>
            </table>
        </fieldset>
        
        <br/>
        <button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
    </form>
    
    <script type="text/javascript">
        $("#formRotationSchemePeek").validate( {
			submitHandler: function(form) {
                genericAjaxPopupPostCloseReloadView('peek','formRotationSchemePeek','{$view_id}',false,'rotation_scheme_save');
            }
		} );
    
        $("<style type='text/css'> label.error{ display: block; margin-bottom: 5px;} </style>").appendTo("head");
    </script>
{else}
    <h2>{if $rotation_scheme->title}{$rotation_scheme->title}{else}{$translate->_('net.pixelinstrument.rotation_manager.new_rotation_scheme')|capitalize}{/if}</h2>
    <p>{$translate->_('net.pixelinstrument.rotation_manager.error.cant_update_rotation_scheme')}</p>
{/if}
