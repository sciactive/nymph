# Nymph - collaborative app data

[![Build Status](https://img.shields.io/travis/sciactive/nymph-server/master.svg?style=flat)](http://travis-ci.org/sciactive/nymph-server) [![Demo App Uptime](https://img.shields.io/uptimerobot/ratio/m776732368-bd4ca09edc681d477a3ddf94.svg?style=flat)](http://nymph-demo.herokuapp.com/examples/sudoku/) [![Last Commit](https://img.shields.io/github/last-commit/sciactive/nymph.svg)](https://github.com/sciactive/nymph/commits/master) [![License](https://img.shields.io/packagist/l/sciactive/nymph.svg?style=flat)](https://packagist.org/packages/sciactive/nymph)

Nymph is an object data store that is easy to use in JavaScript and PHP.

## Installation

For more detailed installation instructions, see the individual repositories. The best place to start is cloning the example repo.

[![REST Server](https://img.shields.io/badge/repo-rest%20server-blue.svg?style=flat)](https://github.com/sciactive/nymph-server) [![PubSub Server](https://img.shields.io/badge/repo-pubsub%20server-blue.svg?style=flat)](https://github.com/sciactive/nymph-pubsub) [![Browser Client](https://img.shields.io/badge/repo-browser%20client-brightgreen.svg?style=flat)](https://github.com/sciactive/nymph-client) [![Node.js Client](https://img.shields.io/badge/repo-node%20client-brightgreen.svg?style=flat)](https://github.com/sciactive/nymph-client-node) [![App Examples](https://img.shields.io/badge/repo-examples-orange.svg?style=flat)](https://github.com/sciactive/nymph-examples)

### Server Installation

```sh
composer install sciactive/nymph-server
composer install sciactive/nymph-pubsub
```

### Client Installation

```sh
npm install --save nymph-client
npm install --save nymph-client-node
```

### Example Apps Installation

```sh
# setup MySQL db nymph_example with user nymph_example and password "omgomg"
git clone https://github.com/sciactive/nymph-examples.git
cd nymph-examples
composer install
php examples/pubsub.php
```

## Demos

Try opening the same one in two windows, and see one window react to changes in the other.

- [Todo](http://nymph-demo.herokuapp.com/examples/todo/svelte/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/todo/))
- [Sudoku](http://nymph-demo.herokuapp.com/examples/sudoku/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/sudoku))
- [Simple Clicker](http://nymph-demo.herokuapp.com/examples/clicker/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/clicker))

## Nymph Query Language

Nymph uses an object based query language. It is similar to Polish notation, as `"operator":["operand","operand"]`.

```js
const easyBlogPosts = await Nymph.getEntities(
  {
    "class": BlogPost.class
  },
  {"type": "&",
    "like": ["title", "%easy%"],
    "data": ["archived", false]
  }
);
```

## What is Nymph?

Nymph is an Object Relational Mapper. It takes the objects that hold your data and translates them to relational data to be stored in a SQL database. Nymph allows rapid prototyping and a powerful query language.

Nymph provides a client library which handles database interactions by using a REST endpoint. You can set up an endpoint by following the instructions in the [Setup Guide](https://github.com/sciactive/nymph/wiki/Setup-Guide).

## Documentation

Check out the documentation in the wiki, [Technical Documentation Index](https://github.com/sciactive/nymph/wiki/Technical-Documentation).

## What's Next

Up next is an user management system for Nymph. It will let you set up a registration and login process using Nymph entities. It will soon be available at [tilmeld.org](http://tilmeld.org/).
