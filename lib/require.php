<?php
// Search for RequirePHP and load it. If it's unavailable, inform the user.

if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."../bower_components/requirephp/require.php")) {
	require dirname(__FILE__).DIRECTORY_SEPARATOR."../bower_components/requirephp/require.php";
} elseif (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."../vendor/sciactive/requirephp/require.php")) {
	require dirname(__FILE__).DIRECTORY_SEPARATOR."../vendor/sciactive/requirephp/require.php";
} elseif (file_exists("../require.php")) {
	require dirname(__FILE__).DIRECTORY_SEPARATOR."../require.php";
} else {
	echo <<< EOF
It looks like you haven't downloade RequirePHP yet. You can do this with Bower,
Composer, or manually.<br>
<br>
Bower:<br>
<code>bower install</code><br>
<br>
Composer:<br>
<code>php composer.phar install</code> or <code>composer install</code><br>
<br>
Manually:<br>
<ol>
	<li>Go to <a href="https://github.com/sciactive/requirephp/releases" target="_blank">https://github.com/sciactive/requirephp/releases</a></li>
	<li>Download the latest release.</li>
	<li>Extract the require.php file and place it in the root dir of this repository.</li>
</ol>
EOF;
	exit;
}