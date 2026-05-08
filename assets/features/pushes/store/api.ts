import { api } from '../../../shared/store/api';
import type { PushSubscriptionRequest } from '../types';

const pushesApi = api.injectEndpoints({
  endpoints: (builder) => ({
    subscribePush: builder.mutation<void, PushSubscriptionRequest>({
      query: (body) => ({
        url: '/pushes',
        method: 'POST',
        body,
      }),
    }),

    unsubscribePush: builder.mutation<void, PushSubscriptionRequest>({
      query: (body) => ({
        url: '/pushes',
        method: 'DELETE',
        body,
      }),
    }),
  }),
});

export const { useSubscribePushMutation, useUnsubscribePushMutation } = pushesApi;
