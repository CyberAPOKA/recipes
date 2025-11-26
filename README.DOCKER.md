# Docker Setup - Recipes Project

Este projeto utiliza Docker e Docker Compose para facilitar o desenvolvimento e deploy da aplicação completa.

## Estrutura do Projeto

- **backend/**: API Laravel (PHP 8.2)
- **web/**: Frontend Vue.js
- **mobile/**: Aplicativo React Native com Expo

## Pré-requisitos

- Docker Desktop instalado e rodando
- Docker Compose (geralmente incluído no Docker Desktop)

## Configuração Inicial

1. Copie o arquivo de exemplo de variáveis de ambiente:

   ```bash
   cp docker-compose.env.example .env
   ```

2. Edite o arquivo `.env` e configure as variáveis conforme necessário.

## Comandos Docker

### Iniciar todos os serviços

```bash
docker-compose up -d
```

### Parar todos os serviços

```bash
docker-compose down
```

### Parar e remover volumes (limpar banco de dados)

```bash
docker-compose down -v
```

### Ver logs de todos os serviços

```bash
docker-compose logs -f
```

### Ver logs de um serviço específico

```bash
docker-compose logs -f backend
docker-compose logs -f web
docker-compose logs -f mobile
```

### Reconstruir imagens

```bash
docker-compose build
```

### Reconstruir e iniciar

```bash
docker-compose up -d --build
```

### Executar comandos dentro de um container

**Backend (Laravel):**

```bash
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan tinker
docker-compose exec backend composer install
```

**Web (Vue):**

```bash
docker-compose exec web npm install
docker-compose exec web npm run build
```

**Mobile (Expo):**

```bash
docker-compose exec mobile npm install
docker-compose exec mobile npx expo start
```

## Acessos

Após iniciar os containers, os serviços estarão disponíveis em:

- **Backend API**: http://localhost:8000
- **Nginx (Backend)**: http://localhost:80
- **Frontend Web**: http://localhost:5173
- **Mobile Expo**: http://localhost:8081
- **phpMyAdmin**: http://localhost:8080

## Banco de Dados

O MySQL está configurado para rodar no container `mysql`. As credenciais padrão são:

- **Host**: mysql (dentro da rede Docker) ou localhost (do host)
- **Porta Interna**: 3306 (dentro da rede Docker)
- **Porta Externa**: 8082 (para acesso do host, ex: MySQL Workbench, DBeaver)
- **Database**: recipes
- **Usuário**: root
- **Senha**: root

### Acessar o Banco de Dados

**Via phpMyAdmin (Interface Web):**

- **URL**: http://localhost:8080
- **Usuário**: root
- **Senha**: root
- Acesse pelo navegador para visualizar e gerenciar o banco de dados MySQL

**Via Cliente MySQL (MySQL Workbench, DBeaver, etc.):**

- **Host**: localhost
- **Porta**: 8082
- **Usuário**: root
- **Senha**: root

**Via Linha de Comando:**

```bash
mysql -h localhost -P 8082 -u root -p
```

**⚠️ IMPORTANTE**:

- A porta **8082** é para conexões MySQL via cliente (não acesse pelo navegador!)
- Use o **phpMyAdmin na porta 8080** para acessar via navegador
- Para conectar de dentro dos containers: `mysql:3306`
- Altere a senha padrão em produção!

## Desenvolvimento

### Backend

O backend Laravel está configurado para recarregar automaticamente. As alterações no código são refletidas imediatamente.

### Frontend Web

O Vite está configurado para hot-reload. Alterações no código Vue são refletidas automaticamente.

### Mobile

O Expo está configurado para desenvolvimento web. Para desenvolvimento mobile nativo, você precisará usar o Expo CLI localmente ou configurar o Expo Go no seu dispositivo.

## Troubleshooting

### Erro de permissões no backend

```bash
docker-compose exec backend chown -R www-data:www-data /var/www/html/storage
docker-compose exec backend chmod -R 775 /var/www/html/storage
```

### Limpar cache do Laravel

```bash
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear
docker-compose exec backend php artisan view:clear
```

### Reinstalar dependências

```bash
# Backend
docker-compose exec backend composer install

# Web
docker-compose exec web npm install

# Mobile
docker-compose exec mobile npm install
```

### Verificar status dos containers

```bash
docker-compose ps
```

### Reiniciar um serviço específico

```bash
docker-compose restart backend
docker-compose restart web
docker-compose restart mobile
```
