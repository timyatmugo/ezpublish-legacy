{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{let contentobject_attribute_id=cond( $attribute|get_class|eq( 'ezinformationcollectionattribute' ),$attribute.contentobject_attribute_id, $attribute|get_class|eq( 'ezcontentobjectattribute' ),$attribute.id )
     contentobject_attribute=cond( $attribute|get_class|eq( 'ezinformationcollectionattribute' ), $attribute.contentobject_attribute, $attribute|get_class|eq( 'ezcontentobjectattribute' ),$attribute )
     total_count=fetch( content, collected_info_count, hash( object_attribute_id, $contentobject_attribute_id ) )
     item_counts=fetch( content,collected_info_count_list, hash( object_attribute_id, $contentobject_attribute_id  ) )
     poll_width=300}

{$contentobject_attribute.content.name}

<table cellspacing="4">
<tr>

{section var=Options loop=$contentobject_attribute.content.option_list}
    {let item_count=0}
    {section show=is_set($item_counts[$Options.item.id])}
        {set item_count=$item_counts[$Options.item.id]}
    {/section}
    <td>
        {$Options.item.value}
    </td>
    <td>
        <table width="{$poll_width}">
        <tr>
            <td bgcolor="ff0000" width="{div( mul( $:item_count, $poll_width ), $total_count )}">&nbsp;</td>
            <td bgcolor="cccccc" {* width="{sub( $poll_width, div( mul( $:item_count, $poll_width ), $total_count ) )}" *}>&nbsp;</td>
        </tr>
        </table>
    </td>
    <td>
        {$:item_count} / <i>{concat( div( mul( $:item_count, 100 ), $total_count ), '' )|extract_left( 5 )}%</i>
    </td>
    {/let}

{delimiter}
</tr>
<tr>
{/delimiter}

{/section}
</tr>
</table>

{/let}
