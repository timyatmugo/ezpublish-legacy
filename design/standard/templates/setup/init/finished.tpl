{*?template charset=latin1?*}
{include uri='design:setup/setup_header.tpl' setup=$setup}

{section show=and($email_info.sent,$email_info.result|not)}
<div class="error">
<p>
  <h2>Email sending failed</h2>
  <ul>
    <li>Failed sending registration email using {section show=eq($email_info.type,1)}sendmail{section-else}SMTP{/section}.</li>
  </ul>
</p>
</div>
{/section}

<h2>Congratulations, eZ publish should now run on your system.</h2>
<p>
If you need help with eZ publish, you can go to the <a href="http://developer.ez.no">eZ publish website</a>.
If you find a bug (error), please go to <a href="http://developer.ez.no/developer/bugreports/">eZ publish bug reports</a> and report it.
Only with your help can we fix the errors eZ publish might have and implement new features.
</p>
<p>
If you ever want to restart this setup, edit the file <i>settings/site.ini.php</i> and look for a line that say.
</p>
<pre class="example">[SiteAccessSettings]
CheckValidity=false</pre>
<p>
 Change the second line from <i>false</i> to <i>true</i>.
</p>
<pre class="example">[SiteAccessSettings]
CheckValidity=true</pre>
</p>
<p>
Click on the URL to access your new <a href="{$site_info.url}">eZ publish website</a> or click the <i>Done</i> button. Enjoy one of the most successful web content management systems!
</p>

<form method="post" action="{$script}">
  <div class="buttonblock">
    <input class="defaultbutton" type="submit" name="Refresh" value="Done" />
  </div>
</form>
