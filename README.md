# Nymph - collaborative app data

[![Build Status](https://img.shields.io/travis/sciactive/nymph-server/master.svg?style=flat)](http://travis-ci.org/sciactive/nymph-server) [![Demo App Uptime](https://img.shields.io/uptimerobot/ratio/m776732368-bd4ca09edc681d477a3ddf94.svg?style=flat)](http://nymph-demo.herokuapp.com/examples/sudoku/) [![Last Commit](https://img.shields.io/github/last-commit/sciactive/nymph.svg)](https://github.com/sciactive/nymph/commits/master) [![License](https://img.shields.io/packagist/l/sciactive/nymph-server.svg?style=flat)](https://packagist.org/packages/sciactive/nymph-server)

Nymph is an object data store that is easy to use in JavaScript and PHP.

## Live Demos

Try opening the same one in two windows, and see one window react to changes in the other.

- [Todo](https://nymph-demo.herokuapp.com/examples/todo/svelte/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/todo/))
- [Sudoku](https://nymph-demo.herokuapp.com/examples/sudoku/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/sudoku))
- [Simple Clicker](https://nymph-demo.herokuapp.com/examples/clicker/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/clicker))

## Installation

This repo is for developing Nymph itself, and will run the example apps with latest Nymph code. If you want to develop an app with Nymph, you can use the app template repo:

[Nymph App Template](https://github.com/hperrin/nymph-template)

You can also install Nymph manually on your server by following the installation instructions in the server and client repos.

[![REST Server](https://img.shields.io/badge/repo-rest%20server-blue.svg?style=flat)](https://github.com/sciactive/nymph-server) [![PubSub Server](https://img.shields.io/badge/repo-pubsub%20server-blue.svg?style=flat)](https://github.com/sciactive/nymph-pubsub) [![Browser Client](https://img.shields.io/badge/repo-browser%20client-brightgreen.svg?style=flat)](https://github.com/sciactive/nymph-client) [![Node.js Client](https://img.shields.io/badge/repo-node%20client-brightgreen.svg?style=flat)](https://github.com/sciactive/nymph-client-node) [![App Examples](https://img.shields.io/badge/repo-examples-orange.svg?style=flat)](https://github.com/sciactive/nymph-examples)

### Dev Environment Installation

1. [Get Docker](https://www.docker.com/community-edition). On Ubuntu: `sudo apt-get install docker.io docker-compose`.
  * If you're on Ubuntu, you need to also run `sudo usermod -a -G docker $USER`, then log out and log back in.
2. Clone the repo: `git clone --recursive https://github.com/sciactive/nymph.git && cd nymph`
3. Run the app: `./run.sh`

Now you can see the example apps on your local machine:

* [Todo App with Svelte](http://localhost:8080/examples/examples/todo/svelte/)
* [Todo App with Angular 1](http://localhost:8080/examples/examples/todo/angular1/)
* [Sudoku App](http://localhost:8080/examples/examples/sudoku/)
* [Simple Clicker App](http://localhost:8080/examples/examples/clicker/)

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

## API Docs

Check out the [API Docs in the wiki](https://github.com/sciactive/nymph/wiki/API-Docs).

## What's Next

Up next is a user management system for Nymph. Currently in beta, it will let you set up a registration and login process using Nymph entities. It's available at [tilmeld.org](http://tilmeld.org/).
