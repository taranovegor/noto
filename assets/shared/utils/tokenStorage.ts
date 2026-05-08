const STORAGE_KEYS = {
  ACCESS_TOKEN: 'auth_access_token',
  REFRESH_TOKEN: 'auth_refresh_token',
  REMEMBER_ME: 'auth_remember_me',
  CENTRIFUGO: 'auth_centrifugo',
} as const;

export interface CentrifugoStorage {
  userId: string;
  token: string;
  url: string;
}

class TokenStorage {
  private resolveStorage(rememberMe: boolean = this.getRememberMe()): Storage {
    return rememberMe ? localStorage : sessionStorage;
  }

  saveTokens(accessToken: string, refreshToken: string, rememberMe: boolean): void {
    const storage = this.resolveStorage(rememberMe);
    storage.setItem(STORAGE_KEYS.ACCESS_TOKEN, accessToken);
    storage.setItem(STORAGE_KEYS.REFRESH_TOKEN, refreshToken);
    storage.setItem(STORAGE_KEYS.REMEMBER_ME, String(rememberMe));
    window.localStorage.setItem(STORAGE_KEYS.REMEMBER_ME, String(rememberMe));
  }

  getAccessToken(): string | null {
    const rememberMe = this.getRememberMe();
    const storage = this.resolveStorage(rememberMe);
    return storage.getItem(STORAGE_KEYS.ACCESS_TOKEN);
  }

  getRefreshToken(): string | null {
    const rememberMe = this.getRememberMe();
    const storage = this.resolveStorage(rememberMe);
    return storage.getItem(STORAGE_KEYS.REFRESH_TOKEN);
  }

  getRememberMe(): boolean {
    const value = window.localStorage.getItem(STORAGE_KEYS.REMEMBER_ME);
    return value ? JSON.parse(value) : false;
  }

  saveCentrifugoConfig(config: CentrifugoStorage): void {
    localStorage.setItem(STORAGE_KEYS.CENTRIFUGO, JSON.stringify(config));
  }

  getCentrifugoConfig(): CentrifugoStorage | null {
    const raw = localStorage.getItem(STORAGE_KEYS.CENTRIFUGO);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  clearAll(): void {
    localStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN);
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
    localStorage.removeItem(STORAGE_KEYS.REMEMBER_ME);
    localStorage.removeItem(STORAGE_KEYS.CENTRIFUGO);
    sessionStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN);
    sessionStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
    sessionStorage.removeItem(STORAGE_KEYS.REMEMBER_ME);
  }
}

export const tokenStorage = new TokenStorage();
