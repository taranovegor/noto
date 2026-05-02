const STORAGE_KEYS = {
  ACCESS_TOKEN: 'auth_access_token',
  REFRESH_TOKEN: 'auth_refresh_token',
  REMEMBER_ME: 'auth_remember_me',
} as const;

class TokenStorage {
  private resolveStorage(rememberMe: boolean = this.getRememberMe()): Storage {
    return rememberMe ? localStorage : sessionStorage;
  }

  saveTokens(accessToken: string, refreshToken: string, rememberMe: boolean): void {
    const storage = this.resolveStorage(rememberMe);
    storage.setItem(STORAGE_KEYS.ACCESS_TOKEN, accessToken);
    storage.setItem(STORAGE_KEYS.REFRESH_TOKEN, refreshToken);
    storage.setItem(STORAGE_KEYS.REMEMBER_ME, String(rememberMe));
    // Always sync to localStorage so getRememberMe() returns the latest choice,
    // even when the user switches from "remember me" to session-only.
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

  clearAll(): void {
    localStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN);
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
    localStorage.removeItem(STORAGE_KEYS.REMEMBER_ME);
    sessionStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN);
    sessionStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
    sessionStorage.removeItem(STORAGE_KEYS.REMEMBER_ME);
  }
}

export const tokenStorage = new TokenStorage();
