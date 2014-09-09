<?php

require 'lib/require.php';
$require = new RequirePHP();

require 'src/Nymph.php';
$require('NymphConfig', array(), function(){
	return include 'conf/config.php';
});

$nymph = $require('Nymph');

require 'Employee.php';

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

$entity = $nymph->getEntity(array('class' => Employee), array('&', 'guid' => $newEntity2->guid));

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Nymph Demo</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body>
		<div>Right now, it's just a test. Here's the result:</div>
		<pre><?php echo json_encode($entity); ?></pre>
		<pre><?php var_dump($entity); ?></pre>
		<pre><?php echo json_encode((int) $entity->guid); ?></pre>
		<pre><script>
			document.write(JSON.parse(<?php echo json_encode((int) $entity->guid); ?>));
		</script></pre>
		<pre><?php var_dump($newEntity2); ?></pre>
	</body>
</html>
