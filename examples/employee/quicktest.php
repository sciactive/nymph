<?php

// This file is to test whether GUIDs get properly encoded into JSON.
// When GUIDs had a larger range of numbers, some would be truncated.

require '../../lib/require.php';

require '../../src/Nymph.php';
RPHP::_('NymphConfig', array(), function(){
	return include '../config.php';
});

RPHP::_(array('Nymph'), function(){
	require 'Employee.php';
});

$newEntity = new Employee();
$newEntity->name = 'John Doe';
$newEntity->title = 'Senior Person';
$newEntity->salary = 5000000;
$newEntity->save();

$newEntity2 = new Employee();
$newEntity2->name = 'Jane Doe';
$newEntity2->title = 'Seniorer Person';
$newEntity2->salary = 8000000;
$newEntity2->subordinates[] = $newEntity;
$newEntity2->save();

$entity = RPHP::_('Nymph')->getEntity(array('class' => Employee), array('&', 'guid' => $newEntity2->guid));

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Nymph Quick Test</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body>
		<div>Check out the code to see what's going on. Here's the result:</div>
		<pre><?php echo json_encode($entity); ?></pre>
		<pre><?php var_dump($entity); ?></pre>
		<pre><?php echo json_encode((int) $entity->guid); ?></pre>
		<pre><script>
			document.write(JSON.parse(<?php echo json_encode((int) $entity->guid); ?>));
		</script></pre>
		<pre><?php var_dump($newEntity2); ?></pre>
	</body>
</html>
