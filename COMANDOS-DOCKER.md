# Comandos Docker - Guia Rápido

## Comandos Básicos

### Iniciar/Parar Containers

```bash
# Iniciar todos os serviços
docker-compose up -d

# Parar todos os serviços
docker-compose down

# Parar e remover volumes (limpar banco)
docker-compose down -v

# Ver status dos containers
docker-compose ps
```

### Ver Logs

```bash
# Ver logs de todos os serviços
docker-compose logs -f

# Ver logs de um serviço específico
docker-compose logs -f backend
docker-compose logs -f web
docker-compose logs -f mysql
```

## Comandos Laravel (Backend)

### Acessar o container do backend

```bash
# Entrar no bash do container
docker-compose exec backend bash

# OU executar comandos diretamente sem entrar no container
docker-compose exec backend php artisan [comando]
```

### Comandos Artisan mais usados

```bash
# Gerar chave da aplicação
docker-compose exec backend php artisan key:generate

# Executar migrations
docker-compose exec backend php artisan migrate

# Reverter última migration
docker-compose exec backend php artisan migrate:rollback

# Ver status das migrations
docker-compose exec backend php artisan migrate:status

# Criar migration
docker-compose exec backend php artisan make:migration nome_da_migration

# Criar controller
docker-compose exec backend php artisan make:controller NomeController

# Criar model
docker-compose exec backend php artisan make:model NomeModel

# Limpar cache
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear
docker-compose exec backend php artisan view:clear

# Ver rotas
docker-compose exec backend php artisan route:list

# Tinker (console interativo)
docker-compose exec backend php artisan tinker
```

### Composer

```bash
# Instalar dependências
docker-compose exec backend composer install

# Atualizar dependências
docker-compose exec backend composer update

# Adicionar pacote
docker-compose exec backend composer require nome/do-pacote
```

## Comandos Frontend Web (Vue)

```bash
# Entrar no container
docker-compose exec web sh

# Instalar dependências
docker-compose exec web npm install

# Build para produção
docker-compose exec web npm run build
```

## Comandos Mobile (Expo)

```bash
# Entrar no container
docker-compose exec mobile sh

# Instalar dependências
docker-compose exec mobile npm install

# Iniciar Expo
docker-compose exec mobile npx expo start
```

## Comandos MySQL

```bash
# Acessar MySQL via linha de comando
docker-compose exec mysql mysql -uroot -proot recipes

# Executar comando SQL
docker-compose exec mysql mysql -uroot -proot -e "SHOW DATABASES;"

# Fazer backup do banco
docker-compose exec mysql mysqldump -uroot -proot recipes > backup.sql

# Restaurar backup
docker-compose exec -T mysql mysql -uroot -proot recipes < backup.sql
```

## Reconstruir Containers

```bash
# Reconstruir uma imagem específica
docker-compose build backend

# Reconstruir e reiniciar
docker-compose up -d --build backend

# Reconstruir tudo
docker-compose build
docker-compose up -d
```

## Limpar Docker

```bash
# Remover containers parados
docker-compose rm

# Remover imagens não utilizadas
docker image prune

# Limpar tudo (cuidado!)
docker system prune -a
```
