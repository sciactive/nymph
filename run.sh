#! /bin/bash

if [ ! -d "vendor" ]; then
  if which composer; then
    # We have composer, so install on the host.
    composer install
  else
    # No composer on the host, so install with Docker image.
    if ! docker run -it --rm -v $PWD:/app composer install; then
      echo "Failed to install PHP libraries."
      exit 1
    fi
  fi
fi

if [ ! -d "db_data" ]; then
  mkdir db_data
fi

if [ ! -f "wait-for-it.sh" ]; then
  curl https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh -o wait-for-it.sh
  chmod 755 ./wait-for-it.sh
fi

docker-compose up
