#! /bin/bash

if [ ! -d "vendor" ]; then
  if which composer; then
    # We have composer, so install on the host.
    cd server
    composer install
    cd ..
    cd pubsub
    composer install
    cd ..
    cd tilmeld-server
    composer install
    cd ..
  else
    # No composer on the host, so install with Docker image.
    if docker run -it --rm -v $PWD/server:/app composer install; then
      # Make sure the files are owned by the user.
      docker run -it --rm -v $PWD/server:/app ubuntu chown -R $(id -u):$(id -g) /app/vendor
    else
      echo "Failed to install PHP libraries."
      exit 1
    fi
    if docker run -it --rm -v $PWD/pubsub:/app composer install; then
      # Make sure the files are owned by the user.
      docker run -it --rm -v $PWD/pubsub:/app ubuntu chown -R $(id -u):$(id -g) /app/vendor
    else
      echo "Failed to install PHP libraries."
      exit 1
    fi
    if docker run -it --rm -v $PWD/tilmeld-server:/app composer install; then
      # Make sure the files are owned by the user.
      docker run -it --rm -v $PWD/tilmeld-server:/app ubuntu chown -R $(id -u):$(id -g) /app/vendor
    else
      echo "Failed to install PHP libraries."
      exit 1
    fi
  fi
fi

if [ ! -f "tilmeld_secret.txt" ]; then
  dd if=/dev/urandom bs=32 count=1 | base64 > ./tilmeld_secret.txt
fi

if [ ! -d "db_data" ]; then
  mkdir db_data
fi

if [ ! -f "wait-for-it.sh" ]; then
  curl https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh -o wait-for-it.sh
  chmod 755 ./wait-for-it.sh
fi

docker-compose up
