{include file=header.tpl}
<div id="loginbox">
	{if $error}
	<div style='padding-top: 2em;'>
	{include file=components/errormessage.tpl message=$error}
	</div>
	{/if}
	<form name="login" action="{$request_uri}"  method="post">
	<div id="form_login" style="display: block;">
		<label for="username">Username:</label>
			<input type="text" name="edit[username]" id="username" style="width: 170px;" maxlength="50" value=""  /><br />
		
		<label for="password">Password:</label>
			<input type="password" name="edit[password]" id="password" style="width: 170px;" maxlength="50" value=""  /><br />

		<label>&nbsp;</label>
			<input type="checkbox" name="edit[remember_me]" value="1" />Keep me logged in on this computer?<br />

		<label>&nbsp;</label>
			<input type="submit" name="Submit" value="Login" /><br />

		<label>&nbsp;</label>
			<a href="{lr_url path=person/forgotpassword}">Forgot your password?</a><br />
			<a href="{lr_url path=person/create}">Create a new account?</a>

	</div>
	</form>
</div>
<script language="JavaScript">
document.login.elements[0].focus();
</script>
{include file=footer.tpl}