# Guia para Gerar APK do Aplicativo Receitas

Este guia explica como gerar um APK funcional do aplicativo mobile.

## ⚠️ IMPORTANTE: Configurar URL da API

Antes de gerar o APK, você **DEVE** atualizar a URL da API para um IP/domínio real, pois `localhost` não funcionará no dispositivo Android.

### Opção 1: Usar IP da sua máquina na rede local

1. Descubra o IP da sua máquina:
   - **Windows**: Execute `ipconfig` no PowerShell e procure por "IPv4 Address"
   - **Linux/Mac**: Execute `ifconfig` ou `ip addr`

2. Atualize o arquivo `.env` ou `app.json`:
   ```env
   EXPO_PUBLIC_API_URL=http://SEU_IP_AQUI:8000/api
   ```
   Exemplo: `EXPO_PUBLIC_API_URL=http://192.168.1.100:8000/api`

### Opção 2: Usar um servidor de produção

Se você tem um servidor em produção:
```env
EXPO_PUBLIC_API_URL=https://seu-dominio.com/api
```

## 📦 Método 1: EAS Build (Recomendado - Build na Nuvem)

O EAS Build é o método oficial e mais fácil do Expo. Ele faz o build na nuvem do Expo.

### Pré-requisitos

1. **Instalar EAS CLI**:
   ```bash
   npm install -g eas-cli
   ```

2. **Fazer login no Expo**:
   ```bash
   eas login
   ```
   (Crie uma conta gratuita em https://expo.dev se não tiver)

3. **Configurar o projeto**:
   ```bash
   cd mobile
   eas build:configure
   ```

### Gerar APK

1. **Build de Preview (APK para testes)**:
   ```bash
   cd mobile
   eas build --platform android --profile preview
   ```

2. **Build de Produção**:
   ```bash
   cd mobile
   eas build --platform android --profile production
   ```

3. **Acompanhar o build**:
   - O processo leva alguns minutos
   - Você pode acompanhar em: https://expo.dev/accounts/[seu-usuario]/builds
   - Quando terminar, você receberá um link para download do APK

### Download do APK

Após o build terminar:
1. Acesse o link fornecido ou vá em https://expo.dev/accounts/[seu-usuario]/builds
2. Clique no build concluído
3. Baixe o arquivo `.apk`

## 🔨 Método 2: Build Local (Mais Complexo)

Se preferir fazer o build localmente, você precisa:

### Pré-requisitos

1. **Android Studio** instalado
2. **Java JDK** instalado
3. **Variáveis de ambiente** configuradas (ANDROID_HOME, JAVA_HOME)

### Passos

1. **Instalar dependências**:
   ```bash
   cd mobile
   npm install
   ```

2. **Gerar projeto Android nativo**:
   ```bash
   npx expo prebuild --platform android
   ```

3. **Build do APK**:
   ```bash
   cd android
   ./gradlew assembleRelease
   ```

4. **Localizar o APK**:
   O APK estará em: `android/app/build/outputs/apk/release/app-release.apk`

## 📱 Instalar o APK no Dispositivo

1. **Habilitar "Fontes Desconhecidas"**:
   - Vá em Configurações > Segurança
   - Ative "Fontes Desconhecidas" ou "Instalar apps desconhecidos"

2. **Transferir o APK**:
   - Conecte o dispositivo via USB ou envie por email/WhatsApp
   - Ou use: `adb install caminho/para/app.apk`

3. **Instalar**:
   - Abra o arquivo APK no dispositivo
   - Toque em "Instalar"

## 🔧 Configurações Adicionais

### Atualizar versão do app

No arquivo `app.json`, atualize:
```json
{
  "expo": {
    "version": "1.0.0",  // Versão visível ao usuário
    "android": {
      "versionCode": 1  // Versão interna (incrementar a cada build)
    }
  }
}
```

### Personalizar nome do app

No arquivo `app.json`:
```json
{
  "expo": {
    "name": "Receitas"  // Nome que aparece no dispositivo
  }
}
```

### Personalizar package name

No arquivo `app.json`:
```json
{
  "expo": {
    "android": {
      "package": "com.recipes.app"  // Identificador único do app
    }
  }
}
```

## 🐛 Troubleshooting

### Erro: "Unable to resolve module"

- Certifique-se de que todas as dependências estão instaladas: `npm install`
- Limpe o cache: `npx expo start --clear`

### Erro: "Network request failed"

- Verifique se a URL da API está correta e acessível do dispositivo
- Certifique-se de que o servidor backend está rodando
- Se usando IP local, certifique-se de que o dispositivo está na mesma rede

### APK muito grande

- Use `eas build` com otimizações automáticas
- Considere usar `expo-optimize` para reduzir o tamanho

## 📚 Recursos Adicionais

- [Documentação EAS Build](https://docs.expo.dev/build/introduction/)
- [Documentação Expo](https://docs.expo.dev/)
- [Guia de Publicação Android](https://docs.expo.dev/distribution/building-standalone-apps/)

