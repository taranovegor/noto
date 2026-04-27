import { createApi, fetchBaseQuery, retry } from '@reduxjs/toolkit/query/react';

const baseQuery = fetchBaseQuery({
  baseUrl: '/api',
  prepareHeaders: (headers) => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (token) headers.set('X-CSRF-Token', token);
    return headers;
  },
  timeout: 15000,
});

const baseQueryWithRetry = retry(baseQuery, {
  maxRetries: 2,
});

export const api = createApi({
  reducerPath: 'api',
  baseQuery: baseQueryWithRetry,
  tagTypes: ['Tasks', 'Notes', 'Projects'],
  endpoints: () => ({}),
});
