<?php
/*
Template Name: Login Page
*/
?>
<?php if ($_GET['action']=="lostpassword") { ?>
<form name="lostpasswordform" class="log-page" action="<?php echo home_url(); ?>/wp-login.php?action=lostpassword" method="post">
    <h3>Reset password</h3>
    <p class="message">Please enter your username or email address. You will receive a link to create a new password via email.</p>
    <div class="input-prepend">
        <span class="add-on"><i class="icon-user"></i></span>
        <input type="text" name="user_login" id="user_login" class="input-xlarge" value="" size="20" placeholder="Username or E-mail" />
    </div>
    <div class="controls form-inline">
        <input type="submit" name="wp-submit" id="wp-submit" class="btn-u pull-right" value="Get New Password" />
        <input type="hidden" name="redirect_to" value="<?php echo ( $_GET['redirect_to'] ? $_GET['redirect_to'] : home_url() ); ?>" />
    </div>
    <hr style="margin-top:40px" />
    <a href="<?php echo home_url(); ?>/login/?redirect_to=<?php echo urlencode($_GET['redirect_to']); ?>" class="btn"><strong>Return to Login</strong></a>
</form>
<?php } else { ?>
<form name="loginform" class="log-page" action="<?php echo home_url(); ?>/wp-login.php" method="post">
    <h3>Login to your account</h3>

    <div class="input-prepend">
        <span class="add-on"><i class="icon-user"></i></span>
        <input type="text" name="log" id="user_login" class="input-xlarge" value="" size="20" placeholder="Username" />
    </div>
    <div class="input-prepend">
        <span class="add-on"><i class="icon-lock"></i></span>
        <input type="password" name="pwd" id="user_pass" class="input-xlarge" value="" size="20" placeholder="Password" />
    </div>
    <div class="controls form-inline">
        <label class="checkbox"><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> Stay Signed In</label>
        <input type="submit" name="wp-submit" id="wp-submit" class="btn-u pull-right" value="Login" />
        <input type="hidden" name="redirect_to" value="<?php echo ( $_GET['redirect_to'] ? $_GET['redirect_to'] : home_url() ); ?>" />
    </div>
    <hr />
    <h4>Forgot your password?</h4>
    <p>No worries, <a class="color-green" href="<?php echo home_url(); ?>/login/?action=lostpassword&redirect_to=<?php echo urlencode($_GET['redirect_to']); ?>">click here</a> to reset your password.</p>
</form>
<?php } ?>