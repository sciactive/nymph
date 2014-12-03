# <img alt="logo" src="https://raw.githubusercontent.com/sciactive/2be-extras/master/logo/product-icon-40-bw.png" align="top" /> Nymph - a PHP and JS ORM

The goal of Nymph is to be easy to set up, easy to learn, and suitable for not only prototyping, but production projects. Nymph automatically creates tables the first time a new class is instantiated. Nymph entities are accessed just like any other object, in both PHP and JavaScript. There is a built in REST server, which makes building JavaScript applications with Nymph trivial. Nymph is based on the ORM in the Pines framework, which has been rigorously tested in real world, high demand web applications.

## Demos

You can find working versions of the demos in the "examples" directory hosted on Heroku. Check out the [todo](http://nymph-demo.herokuapp.com/examples/todo/) and [sudoku](http://nymph-demo.herokuapp.com/examples/sudoku/) apps. After playing around with them, you can check out the source for the [todo](https://github.com/sciactive/nymph/tree/master/examples/todo) and [sudoku](https://github.com/sciactive/nymph/tree/master/examples/sudoku) apps.

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

## Contacting the Developers

There are several ways to contact the Nymph developers with your questions, concerns, comments, bug reports, or feature requests.

- Nymph is part of [SciActive on Twitter](http://twitter.com/SciActive).
- Bug reports, questions, and feature requests can be filed at the [issues page](https://github.com/sciactive/nymph/issues).
- You can directly [email Hunter Perrin](mailto:hunter@sciactive.com), the creator of Nymph.