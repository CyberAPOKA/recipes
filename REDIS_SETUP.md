# Configuração do Redis - Solução de Problemas

## Problema: "Class Redis not found"

Este erro ocorre quando a extensão PHP Redis não está instalada no container Docker.

## Solução

### 1. Reconstruir o container do backend SEM cache

Execute os seguintes comandos na raiz do projeto:

```bash
# Parar todos os containers
docker-compose down

# Remover a imagem antiga do backend (opcional, mas recomendado)
docker rmi recipes_backend 2>/dev/null || true

# Reconstruir o backend SEM cache para garantir instalação completa
docker-compose build --no-cache backend

# Iniciar os containers
docker-compose up -d
```

### 2. Verificar se o Redis está instalado

Execute dentro do container:

```bash
# Verificar se a extensão Redis está carregada
docker-compose exec backend php -m | grep redis

# Ou executar o script de verificação
docker-compose exec backend php check-redis.php
```

### 3. Verificar se o serviço Redis está rodando

```bash
# Verificar status do container Redis
docker-compose ps redis

# Testar conexão com Redis
docker-compose exec redis redis-cli ping
```

### 4. Verificar configuração do Laravel

Certifique-se de que o arquivo `.env` do backend tenha:

```env
CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### 5. Testar cache no Laravel

```bash
# Entrar no tinker
docker-compose exec backend php artisan tinker

# No tinker, testar:
Cache::put('test', 'value', 60);
Cache::get('test');
```

## Se ainda não funcionar

### Alternativa: Usar Predis (cliente PHP puro)

Se a extensão Redis continuar com problemas, você pode usar Predis que é um cliente PHP puro:

1. Instalar Predis via Composer:
```bash
docker-compose exec backend composer require predis/predis
```

2. Alterar configuração em `backend/config/database.php`:
```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'), // Mudar de 'phpredis' para 'predis'
    // ... resto da configuração
]
```

3. Adicionar no `.env`:
```env
REDIS_CLIENT=predis
```

## Verificação Final

Após seguir os passos acima, teste a API:

```bash
curl http://localhost:8000/api/recipes?page=1
```

Se ainda houver erro, verifique os logs:

```bash
docker-compose logs backend | tail -50
```

