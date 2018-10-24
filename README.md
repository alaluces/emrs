# Electronic Medical Records System
A web app for keeping medical records\
Uses SLIM microframework and TWIG templating engine

## Run using Docker

#### Build the image
```sh
git clone https://github.com/alaluces/docker-emrs.git
cd docker-emrs/
git clone https://github.com/alaluces/emrs.git
docker build -t emrs .
```
#### Run the containers
```sh
docker network create my_lan
docker run --name emrs-db -d -p3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=emrs -e MYSQL_USER=emrs -e MYSQL_PASSWORD=1234 --network=my_lan arieslaluces/emrs-db
docker run --name emrs -d -p 80:80 --network=my_lan emrs
```

#### Or run using docker-compose.yaml
```sh
sudo apt-get install -y docker-compose
git clone https://github.com/alaluces/docker-emrs.git
cd docker-emrs/
git clone https://github.com/alaluces/emrs.git
cd ..
docker-compose up -d
```
