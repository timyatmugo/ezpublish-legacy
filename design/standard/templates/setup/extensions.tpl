<h1>{'Extension setup'|i18n('design/standard/setup')}</h1>
<p>
    {'Here you can activate/deactivate you extensions. Only system wide extensions can be activated, for site access spesific extensions, modify these configuration files.'|i18n('design/standard/setup')}
</p>

<h2>{'Available extensions'|i18n('design/standard/setup')}</h2>
<form method="post" action={"/setup/extensions"|ezurl}>
{section name=Extensions loop=$available_extension_array}
<input type="checkbox" name="ActiveExtensionList[]" value="{$:item}"
{section show=$selected_extension_array|contains($Extensions:item)}
 checked
{/section}
 />{$:item}<br />
{/section}
<br />
<input type="submit" name="ActivateExtensionsButton" value="{'Activate extensions'|i18n('kernel/setup')}" />
</form>
