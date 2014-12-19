<?php
// Search for RequirePHP and load it. If it's unavailable, inform the user.

if (file_exists(__DIR__.DIRECTORY_SEPARATOR."../vendor/autoload.php")) {
	require __DIR__.DIRECTORY_SEPARATOR."../vendor/autoload.php";
} elseif (file_exists(__DIR__.DIRECTORY_SEPARATOR."../bower_components/requirephp/src/R.php")) {
	require __DIR__.DIRECTORY_SEPARATOR."../bower_components/requirephp/src/R.php";
} else {
	echo <<< EOF
It looks like you haven't downloade RequirePHP yet. You can do this with Bower
or Composer.<br>
<br>
Bower:<br>
<code>bower install</code><br>
<br>
Composer:<br>
<code>php composer.phar install</code> or <code>composer install</code>
EOF;
	exit;
}