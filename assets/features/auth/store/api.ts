import { api } from '../../../shared/store/api';
import type { CentrifugoConfig, User } from '../types';

const authApi = api.injectEndpoints({
  endpoints: (builder) => ({
    getCurrentUser: builder.query<User, void>({
      query: () => '/users/me',
    }),

    getCentrifugoConnection: builder.query<CentrifugoConfig, void>({
      query: () => '/centrifugo/connect',
    }),
  }),
});

export { authApi };
export const { useGetCurrentUserQuery, useGetCentrifugoConnectionQuery } = authApi;
