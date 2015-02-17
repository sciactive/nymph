# <img alt="logo" src="https://raw.githubusercontent.com/sciactive/2be-extras/master/logo/product-icon-40-bw.png" align="top" /> Nymph - a PHP and JS ORM

[![Build Status](https://img.shields.io/travis/sciactive/nymph-server/master.svg?style=flat)](http://travis-ci.org/sciactive/nymph-server) [![Latest Stable Version](https://img.shields.io/packagist/v/sciactive/nymph.svg?style=flat)](https://packagist.org/packages/sciactive/nymph) [![License](https://img.shields.io/packagist/l/sciactive/nymph.svg?style=flat)](https://packagist.org/packages/sciactive/nymph) [![Open Issues](https://img.shields.io/github/issues/sciactive/nymph.svg?style=flat)](https://github.com/sciactive/nymph/issues)

Nymph is an ORM that is simple to use in both JavaScript and PHP.

## Installation

You can install Nymph with Composer for the server side files, and Bower for the client side files.

```sh
composer require sciactive/nymph

bower install nymph
```

This repository is set up to retrieve the correct files for [server](https://github.com/sciactive/nymph-server) or [client](https://github.com/sciactive/nymph-client).

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

## Demos

You can find working versions of the demos in the "examples" directory hosted on Heroku. Check out the [todo](http://nymph-demo.herokuapp.com/examples/todo/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/todo)) and [sudoku](http://nymph-demo.herokuapp.com/examples/sudoku/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/sudoku)) apps.

## Why Nymph

The pain of binding your models to your HTML was relieved with frontend libraries like Angular. But developers so often still have to go through the pain of creating many endpoints to communicate your actual models to the frontend. Then there's the additional code required to query your database, translate the results, and relay that to the frontend. Where Angular left off, Nymph comes in. Nymph provides the models to build complex MVC applications simply. Querying logic can be entirely (or partially) handled on the frontend, with never a single line of SQL. This means less context switching between frontend and backend, and far less time to develop complex, functional apps. Unlike most other ORMs, Nymph is designed to let you put your business logic wherever you see fit.

Nymph also frees you from lots of database maintenance. There's no need to run a migration script every time you add a property to one of your objects. Nymph is flexible and automatic with object data. Never switching out of an Object Oriented context makes coding a lot easier and a lot more fun. Less time spent crafting databases means more time spent building useful applications.

## History

Nymph started in 2009, as part of the Pines PHP framework. After years of only residing in that project, in August 2014, it was ripped out and made into its own project. To make it more useful, the JavaScript half was written. Now Nymph is a mature, well tested product.

## Understanding Nymph

Nymph takes the objects that hold your data and translates them to relational data to be stored in a SQL database. Nymph has two parts, in both JavaScript and PHP:

<dl>
	<dt>Nymph Object</dt>
	<dd>The Nymph object is where you communicate with the database. It is what will actually translate your Nymph queries into database queries. You will mostly be using the getEntity, getEntities, and newUID methods. It also has sorting methods to help you sort arrays of entities.</dd>
	<dt>Entity Class</dt>
	<dd>The Entity class is what you will extend to create a new type of data object. All of the objects Nymph retrieves will be instantiated from classes extending this class. You can use the Entity class itself, however this will cause your data to be stored in only one table.</dd>
</dl>

Both of these things exist in PHP and JavaScript, and interacting with them in either environment is very similar. The main difference being that in JavaScript, since data can't be retrieved immediately without halting execution until the server responds, Nymph will return JavaScript promise objects instead of actual data. Promise objects let you cleanly handle situations where Nymph fails to retrieve the requested data from the server.

Nymph in JavaScript handles any database interaction by using a NymphREST endpoint. You can build your own endpoint very easily, by following the instructions in the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Setting up a Nymph Application

<div dir="rtl">Quick Setup with Composer</div>
```sh
composer require sciactive/nymph
```
```php
require 'vendor/autoload.php';
use Nymph\Nymph as Nymph;
\SciActive\R::_('NymphConfig', [], function(){
	$config = include('vendor/sciactive/nymph-server/conf/defaults.php');
	$config->MySQL->host['value'] = '127.0.0.1';
	$config->MySQL->database['value'] = 'my_database';
	$config->MySQL->user['value'] = 'my_user';
	$config->MySQL->password['value'] = 'my_password';
	return $config;
});

// You are set up. Now make a class like `MyEntity` and use it.

require 'my_autoloader.php';

$myEntity = new MyEntity();
$myEntity->myVar = "myValue";
$myEntity->save();

$allMyEntities = Nymph::getEntities(['class' => 'MyEntity']);
```

For a thorough step by step guide to setting up Nymph on your own server, visit the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Technical Documentation

The technical documentation on the wiki is accessible through the [Technical Documentation Index](https://github.com/sciactive/nymph/wiki/Technical-Documentation).

## What's Next

Up next are two projects. The first is a user access control system for Nymph. It will allow you to restrict what entities a user, or group of users, has access to. It will include a user and group entity class, and have methods for managing these. It will soon be available at [tilmeld.org](http://tilmeld.org/). The second is a set of Angular directives you can use to easily create forms for Nymph entities. It has already been started, and can be found at [https://github.com/sciactive/nymph-directives](https://github.com/sciactive/nymph-directives).

## Contacting the Developer

There are several ways to contact Nymph's developer with your questions, concerns, comments, bug reports, or feature requests.

- Nymph is part of [SciActive on Twitter](http://twitter.com/SciActive).
- Bug reports, questions, and feature requests can be filed at the [issues page](https://github.com/sciactive/nymph/issues).
- You can directly [email Hunter Perrin](mailto:hunter@sciactive.com), the creator of Nymph.
