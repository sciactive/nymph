Nymph Sudoku
============

A Sudoku game, using Angular for UI and Nymph to save games.

How It Works
------------

Nymph is used to save and load games, as well as interact with the backend Game
class. Game.js and Game.php are the classes that represent an individual Sudoku
game. In the Game PHP class, the `generateBoard` method will generate a complete
Sudoku board. The `makeItFun` method will remove squares based on the difficulty
set on the game.

Nymph supplies import and export functionality, which is used to provide the
saved games import/export. This helps if transitioning from MySQL to Postgres or
vice versa.

The frontend is designed in Angular, which holds the game and UI state. It uses
Nymph on the frontend to load the saved games. When you create a new game, it
uses Nymph to run the `generateBoard` and `makeItFun` methods on the backend. It
tracks the time spent in a game with an Angular interval. It also checks answers
each time the board is changed. It will build a `mistakes` array, depending on
which level of help is selected.

The CSS is all just custom designed CSS. It uses media queries to alter the
layout for desktop, tablet, and mobile size displays.

Nymph makes saving data very easy. It handles constructing all the tables
initially, and all database queries come from the frontend, so very little
backend logic code is required. It also makes communicating with the backend
much simpler, since remote methods can be run on a class.

Angular makes the frontend design much more organized. It reduces the complexity
of the interface's code tremendously versus a similar interface written with
jQuery or Zepto.

How I Built the Board Generator
-------------------------------

I started off by building a board generating function on the game class to build
a completed Sudoku board. I wrote the test.php file to test this function and
visualize its output.

It seems my first attempt made boards that had missing squares that were
impossible to fill. I tried to remedy it by redoing the row if it encounters
one, but that is taking way too long.

I solved that problem by calculating affinity values based on the values of
the previous row in the square to the left. This effectively rotates values
between squares, if it can. After that alteration, the function is averaging a
little under half a second to generate a completed board.

It seems that ever so rarely, an attempt to make a board will result in an
unsolvable row. I solved that by keeping a row attempt counter. If it gets too
high, I just give up and restart.

To make the board, based on difficulty, the game removes some random squares,
then calculates the number of possible options each remaining square has. If the
game is set to hard, it will remove the squares with the most options. If it's
set to easy, it will remove the squares with the least. It removes up to 5 at
once, then recalculates the possible options.

On hard difficulty, I seem to be getting a lot of clumping of the squares left
over. I'm going to try remedying this by removing more random squares.

I ran into a problem of not being able to tell if a square was a preset or not.
I'm trying to remedy it by making each square an array with 'preset' and 'value'
keys, but that's making the makeItFun step take upwards of 6 seconds. :P

I solved it by just saving the empty board after it's calculated.

I didn't realize that my affinity fix gave the board a predictable pattern. I
fixed that by continuously swapping around the affinities. Now the function
takes an average of just under a second to complete a board, but they are much
less prone to recognizable patterns.