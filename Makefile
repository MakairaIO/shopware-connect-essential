SHELL := /bin/bash

.PHONY: ssh
ssh:
	docker exec -it shopware65 bash

.PHONY: up
up:
	docker-compose up -d

.PHONY: down
down:
	docker-compose down

.PHONY: init
init:
	mkdir -p ./src
	make down
	docker-compose up -d --build
	docker cp shopware65:/var/www/html/. ./src
