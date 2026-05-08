export interface LoginRequest {
  username: string;
  password: string;
}

export interface CentrifugoConfig {
  userId: string;
  token: string;
  url: string;
}

export interface LoginResponse {
  token: string;
  refresh_token: string;
  centrifugo?: CentrifugoConfig;
}

export interface RefreshRequest {
  refresh_token: string;
}

export interface User {
  id: string;
  email: string;
  roles: string[];
  createdAt: string;
  updatedAt: string;
}
