import { api } from '../../../shared/store/api';
import type { LoginRequest, LoginResponse, RefreshRequest, User } from '../types';

const authApi = api.injectEndpoints({
  endpoints: (builder) => ({
    login: builder.mutation<LoginResponse, LoginRequest>({
      query: ({ username, password }) => ({
        url: '/auth/login',
        method: 'POST',
        body: { username, password },
      }),
    }),

    refresh: builder.mutation<LoginResponse, RefreshRequest>({
      query: (body) => ({
        url: '/auth/refresh',
        method: 'POST',
        body,
      }),
    }),

    logout: builder.mutation<void, RefreshRequest>({
      query: (body) => ({
        url: '/auth/logout',
        method: 'POST',
        body,
      }),
    }),

    getCurrentUser: builder.query<User, void>({
      query: () => '/users/me',
    }),
  }),
});

export { authApi };
export const { useLoginMutation, useLogoutMutation, useGetCurrentUserQuery } = authApi;
