import { createContext, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import { Centrifuge } from 'centrifuge';
import { useAppSelector, useAppDispatch } from '../store/hooks';
import { setCentrifugoConfig } from '../store/authSlice';

const CentrifugeContext = createContext<Centrifuge | null>(null);

export function useCentrifuge(): Centrifuge | null {
  return useContext(CentrifugeContext);
}

interface CentrifugoProviderProps {
  children: ReactNode;
}

async function fetchNewToken(accessToken: string): Promise<string> {
  const response = await fetch('/api/centrifugo/connect', {
    headers: { Authorization: `Bearer ${accessToken}` },
  });

  if (!response.ok) {
    throw new Error(`Centrifugo token refresh failed: ${response.status}`);
  }

  const data: { token: string } = await response.json();

  return data.token;
}

export function CentrifugoProvider({ children }: CentrifugoProviderProps) {
  const dispatch = useAppDispatch();
  const centrifugoConfig = useAppSelector((state) => state.auth.centrifugoConfig);
  const accessToken = useAppSelector((state) => state.auth.accessToken);
  const clientRef = useRef<Centrifuge | null>(null);
  const accessTokenRef = useRef(accessToken);
  accessTokenRef.current = accessToken;
  const [client, setClient] = useState<Centrifuge | null>(null);

  // Clear centrifugo config when logged out
  useEffect(() => {
    if (!accessToken && centrifugoConfig) {
      dispatch(setCentrifugoConfig(null));
    }
  }, [accessToken, centrifugoConfig, dispatch]);

  // Create/swap centrifuge client when centrifugo config changes
  useEffect(() => {
    // Disconnect previous client
    if (clientRef.current) {
      clientRef.current.disconnect();
      clientRef.current = null;
    }

    if (!centrifugoConfig) {
      setClient(null);
      return;
    }

    // Append Centrifugo WebSocket endpoint path to the base URL
    const baseUrl = centrifugoConfig.url.replace(/\/+$/, '');
    const wsUrl = `${baseUrl}/connection/websocket`;

    const instance = new Centrifuge(wsUrl, {
      token: centrifugoConfig.token,
      getToken: () => {
        const token = accessTokenRef.current;
        if (!token) {
          throw new Error('No access token for Centrifugo reconnection');
        }

        return fetchNewToken(token);
      },
    });

    clientRef.current = instance;
    setClient(instance);
    instance.connect();

    return () => {
      instance.disconnect();
      if (clientRef.current === instance) {
        clientRef.current = null;
      }
    };
  }, [centrifugoConfig]);

  return <CentrifugeContext.Provider value={client}>{children}</CentrifugeContext.Provider>;
}
