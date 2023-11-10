#!/bin/bash
#


set -ex

if [ "$#" -eq 0 ]; then
    echo "Usage: ${0} [Image Name]"
    exit 0;
fi

cd $WORKSPACE/vue

yarn install
npm install --save mux.js
npm install --save moment
yarn run build:$ENV

cd $WORKSPACE

mkdir -p docker/html

cp -rf app docker/html
cp -rf .htaccess docker/html
cp -rf static docker/html
cp -rf index.php docker/html

cp -rf vue/dist/css docker/html
cp -rf vue/dist/img docker/html
cp -rf vue/dist/js docker/html

mkdir -p docker/html/home
cp -rf vue/dist/index.html docker/html/home

cd $WORKSPACE/docker

docker build -t $1 .

