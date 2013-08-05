<?php
/*
Template Name: Login Page
*/
?>
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
    <p>No worries, <a class="color-green" href="<?php echo home_url(); ?>/wp-login.php?action=lostpassword">click here</a> to reset your password.</p>
</form>