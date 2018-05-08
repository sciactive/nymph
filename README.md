<h1>
  <img alt="Nymph" src="assets/nymph-header-125.png" /><br />
</h1>

[![Build Status](https://img.shields.io/travis/sciactive/nymph-server/master.svg)](http://travis-ci.org/sciactive/nymph-server) [![Demo App Uptime](https://img.shields.io/uptimerobot/ratio/m776732368-bd4ca09edc681d477a3ddf94.svg)](http://nymph-demo.herokuapp.com/examples/sudoku/) [![Last Commit](https://img.shields.io/github/last-commit/sciactive/nymph.svg)](https://github.com/sciactive/nymph/commits/master) [![license](https://img.shields.io/github/license/sciactive/nymph.svg)]()

Nymph stores data from objects and lets you query it easily.

It's an ORM with a powerful query language, modern client library, REST and Pub/Sub servers, and user/group management.

## Live Demos

Try opening the same one in two windows, and see one window update with changes from the other.

- [Todo](https://nymph-demo.herokuapp.com/examples/todo/svelte/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/todo/))
- [Sudoku](https://nymph-demo.herokuapp.com/examples/sudoku/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/sudoku))
- [Simple Clicker](https://nymph-demo.herokuapp.com/examples/clicker/) ([source](https://github.com/sciactive/nymph-examples/tree/master/examples/clicker))

## App Template

To start building an app with Nymph, you can use the [Nymph App Template](https://github.com/hperrin/nymph-template).

## Nymph Entities

Nymph sends data from objects (called entities) to the server and saves to the database.

```js
// Creating entities is super easy.
async function createBlogPost(title, body, archived) {
  // BlogPost extends Entity.
  const post = new BlogPost();
  post.set({title, body, archived});
  if (!await post.save()) {
    return null;
  }
  // The post is now saved in the database.
  return post;
}

// Creating relationships is also easy.
async function createBlogPostComment(post, body) {
  if (!(post instanceof BlogPost)) {
    alert('post should be a BlogPost object!');
    return null;
  }

  const comment = new BlogPostComment();
  comment.set({post, body});
  if (!await comment.save()) {
    return null;
  }
  return comment;
}
```

## Nymph Query Language

Nymph uses an object based query language. It's similar to Polish notation, as `'operator':['operand','operand']`.

```js
// Object based queries are easy from the frontend.
async function searchBlogPosts(userQuery, page = 0) {
  // The server will only return entities the user has access to.
  return await Nymph.getEntities({
    'class': BlogPost.class,
    'limit': 10,
    'offset': page * 10
  }, {
    'type': '&',
    'like': ['title', '%' + userQuery + '%'],
    'strict': ['archived', false]
  });
}

// Querying relationships is also easy.
async function getBlogPostComments(post) {
  return await Nymph.getEntities({
    'class': BlogPostComment.class
  }, {
    'type': '&',
    'ref': ['post', post]
  });
}

// Even complicated queries are easy.
async function getMyLatestCommentsForPosts(posts) {
  return await Nymph.getEntities({
    // Get all comments...
    'class': BlogPostComment.class
  }, {
    'type': '&',
    // ...made in the last day...
    'gte': ['cdate', null, '-1 day'],
    // ...where the current user is the author...
    'ref': ['user', await User.current()]
  }, {
    // ...and the comment is for any...
    'type': '|',
    // ...of the given posts.
    'ref': posts.map(post => ['post', post])
  });
}
```

## Nymph PubSub

Live page updating is easy with the PubSub server.

```js
function watchBlogPostComments(post, component) {
  const comments = component.state.comments || [];
  const subscription = Nymph.getEntities({
    'class': BlogPostComment.class
  }, {
    'type': '&',
    'ref': ['post', post]
  }).subscribe(update => {
    // The PubSub server keeps us up to date on this query.
    PubSub.updateArray(comments, update);
    component.setState({comments});
  });

  return {
    destroy() {
      subscription.unsubscribe();
    }
  };
}
```

## Installation

If you want to build an app with Nymph, you can use the [app template](https://github.com/hperrin/nymph-template).

You can also install Nymph in an existing app by following the instructions in the server and client repos, or in the wiki [for Nymph](https://github.com/sciactive/nymph/wiki/Setup-Guide) and [PubSub](https://github.com/sciactive/nymph/wiki/PubSub-Server-Setup).

[![Nymph Server](https://img.shields.io/badge/repo-nymph%20server-blue.svg)](https://github.com/sciactive/nymph-server) [![PubSub Server](https://img.shields.io/badge/repo-pubsub%20server-blue.svg)](https://github.com/sciactive/nymph-pubsub) [![Tilmeld Server](https://img.shields.io/badge/repo-tilmeld%20server-blue.svg)](https://github.com/sciactive/tilmeld-server) [![Browser Client](https://img.shields.io/badge/repo-browser%20client-brightgreen.svg)](https://github.com/sciactive/nymph-client) [![Node.js Client](https://img.shields.io/badge/repo-node%20client-brightgreen.svg)](https://github.com/sciactive/nymph-client-node) [![Tilmeld Client](https://img.shields.io/badge/repo-tilmeld%20client-brightgreen.svg)](https://github.com/sciactive/tilmeld-client) [![App Examples](https://img.shields.io/badge/repo-examples-orange.svg)](https://github.com/sciactive/nymph-examples)

### Dev Environment Installation

If you are interested in working on Nymph itself:

1. [Get Docker](https://www.docker.com/community-edition). On Ubuntu: `sudo apt-get install docker.io docker-compose`.
  * If you're on Ubuntu, you need to also run `sudo usermod -a -G docker $USER`, then log out and log back in.
2. Clone the repo: `git clone --recursive https://github.com/sciactive/nymph.git && cd nymph`
3. Make sure you're on master: `git submodule foreach git checkout master`
4. Run the app: `./run.sh`

Now you can see the example apps on your local machine:

* [Todo App with Svelte](http://localhost:8080/examples/examples/todo/svelte/)
* [Todo App with React](http://localhost:8080/examples/examples/todo/react/)
* [Todo App with AngularJS](http://localhost:8080/examples/examples/todo/angular1/)
* [Sudoku App](http://localhost:8080/examples/examples/sudoku/)
* [Simple Clicker App](http://localhost:8080/examples/examples/clicker/)

## API Docs

Check out the [API Docs in the wiki](https://github.com/sciactive/nymph/wiki/API-Docs).

## What's Next

Tilmeld is a user management system for Nymph. Currently in beta, it provides registration and login with Nymph entities. It's available at [tilmeld.org](http://tilmeld.org/).
