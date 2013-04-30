<? __move head ?><link type="text/css" rel="stylesheet" href="<? baseUrl ?>css/login.css" />
<? __end ?>
<h1 class="login">User identification</h1>
<? __if loginResult ?><p class="login-result"><? loginResult ?></p><? __end ?>
<form id="login" name="login" action="" method="post">
<p><span>User: </span><input type="text" id="um-login" name="um-login"></p>
<p><span>Password: </span><input type="password" name="um-password"></p>
<p><input type="submit" name="submit" value="Log in"></p>
<input type="hidden" name="goto" value="<? loginOkUrl ?>">
</form>
