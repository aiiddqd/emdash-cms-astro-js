.DEFAULT_GOAL := help

.PHONY: help install dev build preview start typecheck types verify

help: ## Показать доступные команды
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ {printf "%-12s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Установить зависимости из package-lock.json
	npm ci

dev: ## Запустить локальный сервер разработки
	npm run dev

build: ## Собрать production-версию
	npm run build

preview: ## Открыть локальный preview production-сборки
	npm run preview

start: ## Запустить standalone production-сервер
	npm run start

typecheck: ## Проверить типы Astro и TypeScript
	npm run typecheck

types: ## Сгенерировать типы EmDash (нужен работающий сервер)
	npx emdash types

verify: typecheck build ## Выполнить проверку типов и production-сборку
