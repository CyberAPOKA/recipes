// API Configuration
export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'http://localhost:8000/api';

export const getAuthToken = (): string | null => {
  // TODO: Implementar armazenamento seguro do token (ex: AsyncStorage ou SecureStore)
  // Por enquanto, retorna null
  return null;
};

export const setAuthToken = (token: string): void => {
  // TODO: Implementar armazenamento seguro do token
};

export const removeAuthToken = (): void => {
  // TODO: Implementar remoção do token
};

