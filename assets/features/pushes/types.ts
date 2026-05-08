export interface PushKeys {
  p256dh: string;
  auth: string;
}

export interface PushSubscriptionData {
  endpoint: string;
  expirationTime?: number | null;
  keys: PushKeys;
}

export interface PushSubscriptionRequest {
  subscription: PushSubscriptionData;
  options?: Record<string, unknown>;
}

export type PushPermissionState = 'prompt' | 'granted' | 'denied';
