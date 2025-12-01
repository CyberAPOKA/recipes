# Instalação de Dependências

## AsyncStorage

Para que a autenticação funcione, você precisa instalar o AsyncStorage:

```bash
cd mobile
npm install @react-native-async-storage/async-storage
```

Ou se estiver usando Docker:

```bash
docker-compose exec mobile npm install @react-native-async-storage/async-storage
```

## Após instalar

Reinicie o servidor Expo:

```bash
# Local
npx expo start

# Docker
docker-compose restart mobile
```

