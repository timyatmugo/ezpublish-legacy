{*?template charset=latin1?*}
{include uri='design:setup/setup_header.tpl' setup=$setup}

<form method="post" action="{$script}">

<p>

</p>


<div class="highlight">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
  <th class="normal" colspan="3">Database:</th>
</tr>
<tr>
  <td class="normal">Type:</td>
  <td rowspan="7" class="normal">&nbsp;&nbsp;</td>
  <td class="normal">
  {section show=$database_list|gt(1)}
    <select name="eZSetupDatabaseType">
    {section name=DB loop=$database_list}
      <option value="{$:item.type}">{$:item.name}</option>
    {/section}
    </select>
  {section-else}
    <b>{$database_list[0].name}</b>
    <input type="hidden" name="eZSetupDatabaseType" value="{$database_list[0].type}" />
  {/section}
  </td>
</tr>
</table>
</div>


    <div class="buttonblock">
      <input type="hidden" name="ChangeStepAction" value="" />
      <input class="defaultbutton" type="submit" name="StepButton_5" value="Language Options>>" />
    </div>
    {include uri='design:setup/persistence.tpl'}
  </form>
