<?php

if ($_FILES) {
	if ($_FILES['nex']['error'] === 0) {
		require '../../lib/require.php';

		require '../../src/Nymph.php';
		RPHP::_('NymphConfig', array(), function(){
			return include '../config.php';
		});

		RPHP::_(array('Nymph'), function(){
			require '../classes/Employee.php';
			require '../classes/Todo.php';
		});

		$result = RPHP::_('Nymph')->import($_FILES['nex']['tmp_name']);
	} else {
		$result = false;
	}
}
?>
<html>
	<head><title>Import Entities</title></head>
	<body>
		<?php if ($_FILES) { ?>
		<p>It looks like the import <?php echo $result ? 'succeeded' : 'failed' ?>.</p>
		<?php } ?>
		<p>
			Upload a NEX file to import:
			<form method="POST" action="" enctype="multipart/form-data">
				<input type="file" name="nex">&nbsp;&nbsp;&nbsp;<input type="submit" value="Submit">
			</form>
		</p>
	</body>
</html>