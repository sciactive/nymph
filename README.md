# <img alt="logo" src="https://raw.githubusercontent.com/sciactive/2be-extras/master/logo/product-icon-40-bw.png" align="top" /> Nymph

Nymph is an open source object relational mapper for PHP and JavaScript. The goal of Nymph is to be easy to set up, easy to learn, and suitable for not only prototyping, but production projects. Nymph automatically creates tables the first time a new class is instantiated. Nymph entities are accessed just like any other object, in both PHP and JavaScript. There is a built in REST server, which makes building JavaScript applications with Nymph trivial. Nymph is based on the ORM in the Pines framework, which has been rigorously tested in real world, high demand web applications.

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

Nymph in JavaScript handles any database interaction by using a NymphREST endpoint. You can build your own endpoint very easily, by following the instructions in the [Setup Guide](https://github.com/sciactive/nymph/wiki/SetupGuide).

## Setting up a Nymph Application

For a step by step guide to setting up Nymph on your own server, visit the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Technical Documentation

The technical documentation on this wiki is accessible through the [Technical Documentation Index](https://github.com/sciactive/nymph/wiki/Technical-Documentation).

## Contacting the Developers

There are several ways to contact the Nymph developers with your questions, concerns, comments, bug reports, or feature requests.

- Nymph is part of [2be on Twitter](http://twitter.com/2be_io).
- Bug reports, questions, and feature requests can be filed at the [issues page](https://github.com/sciactive/nymph/issues).
- You can directly [email Hunter Perrin](mailto:hunter@sciactive.com), the creator of Nymph.