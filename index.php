<?php

require 'lib/require.php';
$require = new RequirePHP();

require 'src/Nymph.php';
$require('NymphConfig', array(), function(){
	return include 'conf/config.php';
});

$nymph = $require('Nymph');

$newEntity = new Entity();
$newEntity->test = 'This is the test data!';
$newEntity->uniqueID = $nymph->newUID('entity_test');
$newEntity->private = 'This variable is confidential.';
$newEntity->save();

$entity = $nymph->getEntity($newEntity->guid);
$entity->privateData = array('private');

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
	</body>
</html>
