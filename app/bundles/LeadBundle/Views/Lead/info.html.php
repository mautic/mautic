<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-primary">
	<div class="panel-heading text-center pa15" style="min-height: 150px;">
	</div>
	<div class="panel-body text-center" style="margin-top: -60px">
	 	<img class="img-circle img-bordered-primary" src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($fields['core']['email']['value']))); ?>?&s=100" />
	 	<h1><?php echo $lead->getName(); ?></h1>
        <h4>
            <?php if(isset($fields['core']['position']['value'])): ?>
                <?php  echo $fields['core']['position']['value']; ?>
            <?php endif; ?>
            at
            <?php if(isset($fields['core']['company']['value'])): 
                echo $fields['core']['company']['value'];
            endif; ?></h4>
	 </div>

	<div class="panel-footer">
		<a class="btn btn-default"><span class="fa fa-twitter"></span></a>
		<a class="btn btn-default"><span class="fa fa-facebook"></span></a>
		<a class="btn btn-default"><span class="fa fa-linkedin"></span></a>
		<a class="btn btn-default"><span class="fa fa-google"></span></a>
	</div>
</div>
<br />

<strong>About</strong><br />
	<p><em>3 kids and counting, 1 wife and holding.</em></p>

<address>
    <strong>Twitter, Inc.</strong><br>
    795 Folsom Ave, Suite 600<br>
    San Francisco, CA 94107<br>
    <abbr title="Phone">P:</abbr><span> <span id="gc-number-0" class="gc-cs-link" title="Call with Google Voice">(123) 456-7890</span>
</span>
</address>

