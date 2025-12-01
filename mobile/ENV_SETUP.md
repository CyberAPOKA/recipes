# Configuração de Variáveis de Ambiente

## Arquivo .env

Crie um arquivo `.env` na raiz da pasta `mobile` com o seguinte conteúdo:

```env
EXPO_PUBLIC_API_URL=http://localhost:8000/api
```

## Explicação

- `EXPO_PUBLIC_API_URL`: URL base da API do backend
- **Importante**: A URL deve terminar com `/api` pois é o prefixo das rotas da API Laravel
- No Expo, variáveis de ambiente devem começar com `EXPO_PUBLIC_` para serem acessíveis no código

## Exemplos de Configuração

### Desenvolvimento Local
```env
EXPO_PUBLIC_API_URL=http://localhost:8000/api
```

### Docker (mesmo host)
```env
EXPO_PUBLIC_API_URL=http://localhost:8000/api
```

### Docker (de dentro do container)
```env
EXPO_PUBLIC_API_URL=http://backend:8000/api
```

### Produção
```env
EXPO_PUBLIC_API_URL=https://api.seudominio.com/api
```

## Verificação

Após criar o arquivo `.env`, reinicie o servidor Expo para que as variáveis sejam carregadas:

```bash
# Pare o servidor (Ctrl+C) e inicie novamente
npx expo start
```

