Nymph
=====

An object relational mapper with PHP and JavaScript interfaces.

Understanding Nymph
-------------------

Nymph takes the objects that hold your data and translates them to relational data to be stored in a SQL database. Nymph has two parts, in both JavaScript and PHP:

<dl>
	<dt>Nymph Object</dt>
	<dd>The Nymph object is where you communicate with the database. It is what will actually translate your Nymph queries into database queries. You will mostly be using the getEntity, getEntities, and newUID methods. It also has sorting methods to help you sort arrays of entities.</dd>
	<dt>Entity Class</dt>
	<dd>The Entity class is what you will extend to create a new type of data object. All of the objects Nymph retrieves will be instantiated from classes extending this class. You can use the Entity class itself, however this will cause your data to be stored in only one table.</dd>
</dl>

Both of these things exist in PHP and JavaScript, and interacting with them in either environment is very similar. The main difference being that in JavaScript, since data can't be retrieved immediately without halting execution until the server responds, Nymph will return JavaScript promise objects instead of actual data. Promise objects let you cleanly handle situations where Nymph fails to retrieve the requested data from the server.

Nymph in JavaScript handles any database interaction by using a NymphREST endpoint. You can build your own endpoint very easily, by following the instructions in the Getting Started section.

Getting Started
---------------

First, you need [RequirePHP](https://github.com/sciactive/requirephp). Include the require.php file, and instantiate your container.

```php
require("require.php");
$require = new RequirePHP();
```

Next, include Nymph, and set up your configuration module.

```php
require 'src/Nymph.php';
$require('NymphConfig', array(), function(){
	// The conf/config.php file is where you will put all of your own configuration.
	return include 'conf/config.php';
});
```

If your RequirePHP container is named `$require`, Nymph will automatically load itself as the module "Nymph". If your container has another name, you must setup Nymph yourself.

```php
setupNymph($your_container);
```

Next, include any custom Entity classes you've created. In this example, we'll include the ones from the code examples. This must be done after loading Nymph for the first time.

```php
$nymph = $require('Nymph');
require 'examples/Employee.php';
require 'examples/Todo.php';

// or

$require(array('Nymph'), function(){
	require 'examples/Employee.php';
	require 'examples/Todo.php';
});
```

Now, you can begin using Nymph.

```php
$newEntity = new Employee();
$newEntity->name = 'John Doe';
$newEntity->title = 'Senior Person';
$newEntity->salary = 5000000;
$newEntity->save();

$entity = $require('Nymph')->getEntity(array('class' => Employee), array('&', 'tag' => array('employee')));
```

Or, you can setup a REST endpoint, using the NymphREST module.

```php
$NymphREST = $require('NymphREST');

if (in_array($_SERVER['REQUEST_METHOD'], array('PUT', 'DELETE'))) {
	parse_str(file_get_contents("php://input"), $args);
	$NymphREST->run($_SERVER['REQUEST_METHOD'], $args['action'], $args['data']);
} else {
	$NymphREST->run($_SERVER['REQUEST_METHOD'], $_REQUEST['action'], $_REQUEST['data']);
}
```
