import {
  createApi,
  fetchBaseQuery,
  BaseQueryFn,
  FetchArgs,
  FetchBaseQueryError,
} from '@reduxjs/toolkit/query/react';
import { tokenStorage } from '../utils/tokenStorage';
import { setTokens, logout } from './authSlice';

const baseQuery = fetchBaseQuery({
  baseUrl: '/api',
  prepareHeaders: (headers) => {
    const accessToken = tokenStorage.getAccessToken();
    if (accessToken) {
      headers.set('Authorization', `Bearer ${accessToken}`);
    }
    return headers;
  },
  timeout: 15000,
});

const unauthenticatedQuery = fetchBaseQuery({
  baseUrl: '/api',
  timeout: 15000,
});

const isAuthEndpoint = (url: string | undefined): boolean =>
  !!(url && (url.includes('/auth/login') || url.includes('/auth/refresh')));

const authenticatedQuery: BaseQueryFn<FetchArgs | string, unknown, FetchBaseQueryError> = async (
  args,
  api,
  extraOptions,
) => {
  const url = typeof args === 'string' ? args : args.url;

  // Auth endpoints: no retry, just return the result as-is.
  if (isAuthEndpoint(url)) {
    return baseQuery(args, api, extraOptions);
  }

  // Non-auth endpoints: up to 3 attempts with token refresh on 401.
  for (let attempt = 0; attempt < 3; attempt++) {
    const result = await baseQuery(args, api, extraOptions);

    if (!result.error || result.error.status !== 401) {
      return result;
    }

    const state = api.getState() as {
      auth: { refreshToken: string | null; rememberMe: boolean };
    };

    if (!state.auth.refreshToken) {
      api.dispatch(logout());
      // Hard redirect — baseQuery has no access to the React Router context.
      window.location.href = '/login';
      return result;
    }

    const refreshResponse = await unauthenticatedQuery(
      {
        url: '/auth/refresh',
        method: 'POST',
        body: { refresh_token: state.auth.refreshToken },
      },
      api,
      extraOptions,
    );

    if (!refreshResponse.data) {
      api.dispatch(logout());
      window.location.href = '/login';
      return result;
    }

    const { token, refresh_token } = refreshResponse.data as {
      token: string;
      refresh_token: string;
    };
    api.dispatch(
      setTokens({
        accessToken: token,
        refreshToken: refresh_token,
        rememberMe: state.auth.rememberMe,
      }),
    );
    // Loop continues — retry with the new access token.
  }

  return baseQuery(args, api, extraOptions);
};

export const api = createApi({
  reducerPath: 'api',
  baseQuery: authenticatedQuery,
  tagTypes: ['Tasks', 'Notes', 'Projects', 'Stashes', 'Attachments', 'Pushes'],
  endpoints: () => ({}),
});
