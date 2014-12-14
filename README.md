# <img alt="logo" src="https://raw.githubusercontent.com/sciactive/2be-extras/master/logo/product-icon-40-bw.png" align="top" /> Nymph - a PHP and JS ORM

Nymph is an ORM that is simple to use in both JavaScript and PHP.

## Nymph Query Language is BTSQL

I like to think of [Nymph's query language](https://github.com/sciactive/nymph/wiki/Entity-Querying) as a BTSQL (Better Than SQL) language, but you can decide for yourself:

#### Nymph Query from Frontend

```js
Nymph.getEntities({"class":"BlogPost"}, {"type":"&", "like":["title","%easy%"], "data":["deleted",false]}).then(function(entities){
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
  "data": {"title":"%not as easy%","deleted":"false"},
  "dataType": "JSON",
  "success": function(data){
    console.log(data);
  },
  "error": function(){
    alert("Error");
  }
});
```
```php
<?php
// This file is the endpoint for searching for a BlogPost by title.
$mysqllink = require('databasesetup.php');

$title = mysql_real_escape_string($_GET['title'], $mysqllink);
$deleted = ($_GET['deleted'] == "true" ? 'TRUE' : 'FALSE');

$result = mysql_query("SELECT * FROM BlogPosts WHERE title LIKE '$title' AND deleted=$deleted;", $mysqllink);

$entities = array();
while (($row = mysql_fetch_assoc($result)) !== false) {
  $entities[] = $row;
}

header("Content-Type: application/json");
echo json_encode($entities);
```
*Without Nymph, every time you want a new type of query available on the frontend, you're going to need to either modify this endpoint or create a new one.*

## Demos

You can find working versions of the demos in the "examples" directory hosted on Heroku. Check out the [todo](http://nymph-demo.herokuapp.com/examples/todo/) ([source](https://github.com/sciactive/nymph/tree/master/examples/todo)) and [sudoku](http://nymph-demo.herokuapp.com/examples/sudoku/) ([source](https://github.com/sciactive/nymph/tree/master/examples/sudoku)) apps.

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

For a step by step guide to setting up Nymph on your own server, visit the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Technical Documentation

The technical documentation on the wiki is accessible through the [Technical Documentation Index](https://github.com/sciactive/nymph/wiki/Technical-Documentation).

## Contacting the Developer

There are several ways to contact Nymph's developer with your questions, concerns, comments, bug reports, or feature requests.

- Nymph is part of [SciActive on Twitter](http://twitter.com/SciActive).
- Bug reports, questions, and feature requests can be filed at the [issues page](https://github.com/sciactive/nymph/issues).
- You can directly [email Hunter Perrin](mailto:hunter@sciactive.com), the creator of Nymph.