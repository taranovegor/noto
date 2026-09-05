export interface CentrifugoConfig {
  userId: string;
  token: string;
  url: string;
}

export interface User {
  id: string;
  email: string;
  roles: string[];
  createdAt: string;
  updatedAt: string;
}
