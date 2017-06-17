# Nymph - collaborative app data

[![Build Status](https://img.shields.io/travis/sciactive/nymph-server/master.svg?style=flat)](http://travis-ci.org/sciactive/nymph-server) [![Latest Stable Version](https://img.shields.io/packagist/v/sciactive/nymph.svg?style=flat)](https://packagist.org/packages/sciactive/nymph) [![License](https://img.shields.io/packagist/l/sciactive/nymph.svg?style=flat)](https://packagist.org/packages/sciactive/nymph) [![Open Issues](https://img.shields.io/github/issues/sciactive/nymph.svg?style=flat)](https://github.com/sciactive/nymph/issues)

Nymph is an object data store that is easy to use in JavaScript and PHP.

## Installation

You can install Nymph with Composer for the server side files, and NPM for the client side files.

```sh
composer require sciactive/nymph

npm install --save nymph-client
```

This repository is a container for the [server](https://github.com/sciactive/nymph-server), [pubsub](https://github.com/sciactive/nymph-pubsub), and [client](https://github.com/sciactive/nymph-client) files.

## Demos

Try opening the same one in two windows, and see one window react to changes in the other.

- [Todo](http://nymph-demo.herokuapp.com/examples/todo/svelte/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/todo/))
- [Sudoku](http://nymph-demo.herokuapp.com/examples/sudoku/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/sudoku))
- [Simple Clicker](http://nymph-demo.herokuapp.com/examples/clicker/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/clicker))

## Nymph Query Language vs Just SQL

#### Nymph Query from Frontend

```js
Nymph.getEntities({"class":"BlogPost"}, {"type":"&", "like":["title","%easy%"], "data":["archived",false]}).then(function(entities){
  console.log(entities);
}, function(){
  alert("Error");
});
```
*No need for a specific endpoint here. Nymph uses the same endpoint for all client side queries.*

#### Equivalent SQL Query from Frontend

```js
$.ajax({
  "url": "titlesearch.php",
  "data": {"title":"%not as easy%","archived":"false"},
  "dataType": "JSON",
  "success": function(entities){
    console.log(entities);
  },
  "error": function(){
    alert("Error");
  }
});
```
```php
<?php
// This file is the endpoint for searching for a BlogPost by title.
$mysqli = new mysqli();

$title = $_GET['title'];
$archived = ($_GET['archived'] == "true" ? 'TRUE' : 'FALSE');
$entities = array();
if ($stmt = $mysqli->prepare("SELECT * FROM BlogPosts WHERE title LIKE '?' AND archived=?")) {
  $stmt->bind_param("ss", $title, $archived);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $entities[] = $row;
  }
  $stmt->close();
}

header("Content-Type: application/json");
echo json_encode($entities);
$mysqli->close();
```
*Without Nymph, every time you want a new type of query available on the frontend, you're going to need to either modify this endpoint or create a new one.*

## What is Nymph?

Nymph takes the objects that hold your data and translates them to relational data to be stored in a SQL database. Nymph has two parts, in both JavaScript and PHP:

<dl>
	<dt>Nymph Object</dt>
	<dd>The Nymph object is where you communicate with the database and make queries. It also has sorting methods to help you sort arrays of entities.</dd>
	<dt>Entity Class</dt>
	<dd>The Entity class is what you will extend to make data objects.</dd>
</dl>

Both of these exist in PHP and JavaScript, and interacting with them in either environment is very similar. In JavaScript, since data can't be retrieved immediately, Nymph will return promises instead of actual data.

Nymph in JavaScript handles any database interaction by using a NymphREST endpoint. You can build an endpoint by following the instructions in the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Setting up a Nymph Application

<div dir="rtl">Quick Setup with Composer</div>

```sh
composer require sciactive/nymph
```
```php
require 'vendor/autoload.php';
use Nymph\Nymph;
Nymph::configure([
	'MySQL' => [
		'host' => 'your_db_host',
		'database' => 'your_database',
		'user' => 'your_user',
		'password' => 'your_password'
	]
]);

// You are set up. Now make a class like `MyEntity` and use it.

require 'my_autoloader.php';

$myEntity = new MyEntity();
$myEntity->myVar = "myValue";
$myEntity->save();

$allMyEntities = Nymph::getEntities(['class' => 'MyEntity']);
```

For a thorough step by step guide to setting up Nymph on your own server, visit the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Documentation

Check out the documentation in the wiki, [Technical Documentation Index](https://github.com/sciactive/nymph/wiki/Technical-Documentation).

## What's Next

Up next is an ACL system for Nymph. It will allow you to restrict what entities a user, or group of users, has access to. It will include a user and group entity class. It will soon be available at [tilmeld.org](http://tilmeld.org/).
