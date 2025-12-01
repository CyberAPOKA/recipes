# Receitas Mobile App 👋

Aplicativo mobile React Native (Expo) para gerenciamento de receitas.

## Configuração

1. Instale as dependências:

   ```bash
   npm install
   ```

2. Configure a URL da API:

   **Opção 1: Usando arquivo .env (recomendado)**

   Crie um arquivo `.env` na raiz do projeto mobile (`mobile/.env`) com:

   ```
   EXPO_PUBLIC_API_URL=http://localhost:8000/api
   ```

   **Opção 2: Usando Docker Compose**

   Se estiver usando Docker, configure no arquivo `docker-compose.env.example` (ou `.env` na raiz do projeto):

   ```
   EXPO_PUBLIC_API_URL=http://localhost:8000/api
   ```

   **Opção 3: Configuração direta**

   Edite `mobile/constants/api.ts` e altere a URL padrão se necessário.

3. Inicie o app:

   **Desenvolvimento local:**

   ```bash
   cd mobile
   npx expo start
   ```

   **Com Docker:**

   ```bash
   docker-compose up -d mobile
   ```

## Como Acessar o App

### Com Docker (`docker-compose up -d`)

Quando você rodar `docker-compose up -d`, o Expo será iniciado automaticamente com suporte web. Você terá acesso em:

- **Metro Bundler (Interface de Desenvolvimento)**: http://localhost:8081

  - Aqui você verá o QR code e opções para abrir no dispositivo/simulador
  - Interface de desenvolvimento do Expo

- **App Web (Versão Web do App)**: http://localhost:19006
  - Versão web completa do aplicativo
  - Funciona diretamente no navegador

**Nota:** Se a porta 8081 já estiver em uso, você pode alterar no arquivo `.env`:

```env
MOBILE_PORT=8083
MOBILE_WEB_PORT=19007
```

### Desenvolvimento Local (sem Docker)

1. **No terminal, após iniciar o Expo**, você verá um QR code
2. **Escaneie o QR code** com:
   - **iOS**: App Camera nativo ou Expo Go
   - **Android**: Expo Go app
3. Ou pressione:
   - `i` para abrir no simulador iOS
   - `a` para abrir no emulador Android
   - `w` para abrir no navegador web (porta 19006)

## Funcionalidades

- ✅ Listagem de receitas com busca
- ✅ Visualização detalhada de receitas
- ✅ Criação de receitas (simplificada, sem scrap ou ChatGPT)
- ✅ Edição de receitas
- ✅ Exclusão de receitas
- ✅ Suporte a categorias
- ✅ Interface responsiva com suporte a tema claro/escuro

## Estrutura de Telas

- `/recipes` - Listagem de receitas (aba principal)
- `/recipes/[id]` - Visualização detalhada
- `/recipes/create` - Criação de nova receita
- `/recipes/[id]/edit` - Edição de receita

## Desenvolvimento

Você pode começar a desenvolver editando os arquivos dentro do diretório **app**. Este projeto usa [roteamento baseado em arquivos](https://docs.expo.dev/router/introduction) do Expo Router.

## Troubleshooting

### Porta 8081 já está em uso

Se você receber um erro de que a porta 8081 já está em uso:

1. **Com Docker**: Altere no arquivo `.env`:

   ```env
   MOBILE_PORT=8083
   ```

2. **Localmente**: O Expo tentará usar outra porta automaticamente, ou você pode especificar:
   ```bash
   npx expo start --port 8083
   ```

### App não carrega no navegador

- Verifique se o container está rodando: `docker-compose ps`
- Verifique os logs: `docker-compose logs mobile`
- Acesse http://localhost:19006 (porta do web) ao invés de 8081
