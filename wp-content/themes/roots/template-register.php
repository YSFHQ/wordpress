<?php
/*
Template Name: Registration Page
*/
?>
<form name="registerform" id="registerform" class="reg-page" action="<?php echo home_url(); ?>/wp-login.php?action=register" method="post">
    <h3>Register a new account</h3>
    <div class="controls">
        <label for="user_login">Username <span class="color-red">*</span></label>
        <input type="text" name="user_login" id="user_login" class="span12" value="" size="20" />
        <label for="user_email">Email Address <span class="color-red">*</span></label>
        <input type="text" name="user_email" id="user_email" class="span12" value="" size="25" />
        <label>A password will be e-mailed to you.</label>
    </div>
    <div class="controls form-inline">
        <label for="user_agree" class="checkbox"><input name="agreement" type="checkbox" />&nbsp; I agree to the <a href="http://dev.ysfhq.com/terms-of-service-and-privacy-policy/">Terms of Service</a>.</label>
        <input type="hidden" name="redirect_to" value="<?php echo ( $_GET['redirect_to'] ? $_GET['redirect_to'] : home_url() ); ?>" />
        <input type="submit" name="wp-submit" id="wp-submit" class="btn-u pull-right" value="Register" />
    </div>
    <hr />
    <p>Already Signed Up? Click <a href="<?php echo home_url().'/login/?redirect_to='.$_GET['redirect_to']; ?>" class="color-green">Sign In</a> to login your account.</p>
</form>