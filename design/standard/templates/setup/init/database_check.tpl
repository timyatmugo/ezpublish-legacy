{*?template charset=latin1?*}
{include uri='design:setup/setup_header.tpl' setup=$setup}

<form method="post" action="{$script}">

<p>
 {"The database is ready for initialization, click the"|i18n("design/standard/setup/init")} <i>{"Create Database"|i18n("design/standard/setup/init")}</i> {"button when ready."|i18n("design/standard/setup/init")}
</p>
{section show=$database_info.info.has_demo_data}
  {section show=$demo_data.can_unpack}
<p>
 {"If you want you can let the setup add some demo data to your database, this demo data will give a good demonstration of the capabilites of eZ publish"|i18n("design/standard/setup/init")} {$#version.text}.
 {"First time users are adviced to install the demo data."|i18n("design/standard/setup/init")}
</p>
<div class="highlight">
<p>
 {"Install demo data?"|i18n("design/standard/setup/init")}
 <input type="checkbox" name="eZSetupDemoData" value="1" {section show=$demo_data.use}checked="checked"{/section} />
<p>
</div>
  {section-else}
<blockquote class="note">
<p>
 {"Cannot install demo data, the zlib extension is missing from your PHP installation."|i18n("design/standard/setup/init")}
</p>
</blockquote>
 <input type="hidden" name="eZSetupDemoData" value="0" />
  {/section}
{section-else}
<blockquote class="note">
<p>
 {$database_info.info.name} {"does not support installing demo data at this point."|i18n("design/standard/setup/init")}
</p>
</blockquote>
 <input type="hidden" name="eZSetupDemoData" value="0" />
{/section}

{section show=$database_status.error}
<div class="error">
<p>
{section show=$demo_status|not}
  <h2>{"Demo data failure"|i18n("design/standard/setup/init")}</h2>
  <ul>
    <li>{"Could not unpack the demo data."|i18n("design/standard/setup/init")}</li>
    <li>{"You should try to install without demo data."|i18n("design/standard/setup/init")}</li>
  </ul>
{section-else}
  <h2>{"Initialization failed"|i18n("design/standard/setup/init")}</h2>
  <ul>
    <li>{"The database could not be properly initialized."|i18n("design/standard/setup/init")}</li>
    <li>{$database_status.error.text}</li>
    <li>{$database_info.info.name} Error #{$database_status.error.number}</li>
  </ul>
{/section}
</p>
</div>
{/section}

{section show=$database_info.table.is_empty|not}
<h1>{"Warning"|i18n("design/standard/setup/init")}</h1>
<p>
 {"Your database already contains data."|i18n("design/standard/setup/init")}
 {"The setup can continue with the initialization but may damage the present data."|i18n("design/standard/setup/init")}
</p>
<p>
 {"What do you want the setup to do?"|i18n("design/standard/setup/init")}
</p>

<blockquote class="note">
<p>
 <b>{"Note:"|i18n("design/standard/setup/init")}</b>
 {"The setup will not do an upgrade from older eZ publish versions (such as 2.2.7) if you leave the data as it is. This is only meant for people who have existing data that they don't want to loose. If you have existing eZ publish 3.0 data (such as from an RC release) you should skip DB initialization, however you will then need to do a manual upgrade."|i18n("design/standard/setup/init")}
</p>
</blockquote>

<div class="highlight">
<table cellspacing="0" cellpadding="0" border="0">
<tr>
 <td class="normal">
  <p>{"Continue but leave the data as it is."|i18n("design/standard/setup/init")}</p>
 </td>
 <td rowspan="4" class="normal">
  &nbsp;&nbsp;
 </td>
 <td class="normal">
  <input type="radio" name="eZSetupDatabaseDataChoice" value="1" />
 </td>
</tr>
<tr>
 <td class="normal">
  <p>{"Continue and remove the data."|i18n("design/standard/setup/init")}</p>
 </td>
 <td class="normal">
  <input type="radio" name="eZSetupDatabaseDataChoice" value="2"  checked="checked" />
 </td>
</tr>
<tr>
 <td class="normal">
  <p>{"Continue and skip database initialization."|i18n("design/standard/setup/init")}</p>
 </td>
 <td class="normal">
  <input type="radio" name="eZSetupDatabaseDataChoice" value="3" />
 </td>
</tr>
<tr>
 <td class="normal">
  <p>{"Let me choose a new database."|i18n("design/standard/setup/init")}</p>
 </td>
 <td class="normal">
  <input type="radio" name="eZSetupDatabaseDataChoice" value="4" />
 </td>
</tr>
</table>
</div>
{section-else}
<input type="hidden" name="eZSetupDatabaseDataChoice" value="1" />
{/section}


<blockquote class="note">
<p>
 <b>{"Note:"|i18n("design/standard/setup/init")}</b>
 {"It can take some time creating the database so please be patient and wait until the new page is finished."|i18n("design/standard/setup/init")}
</p>
</blockquote>


  <div class="buttonblock">
    <input type="hidden" name="eZSetupDatabaseReady" value="" />
    <input type="hidden" name="ChangeStepAction" value="" />
    <input class="defaultbutton" type="submit" name="StepButton_8" value="{'Create Database'|i18n('design/standard/setup/init')} >>" />
  </div>
  {include uri='design:setup/persistence.tpl'}
</form>
