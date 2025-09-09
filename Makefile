make start:
	docker-compose -f compose.yml up -d --build

make stop:
	docker-compose -f compose.yml down