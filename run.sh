#! /bin/bash

if [ ! -d "vendor" ]; then
  composer install
fi

if [ ! -d "db_data" ]; then
  mkdir db_data
fi

docker-compose up
