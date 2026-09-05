import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

const baseQuery = fetchBaseQuery({
  baseUrl: '/api',
  prepareHeaders: (headers, { getState }) => {
    const state = getState() as { push: { sourceChecksum: string | null } };
    if (state.push?.sourceChecksum) {
      headers.set('X-Push-Source', state.push.sourceChecksum);
    }
    return headers;
  },
  timeout: 15000,
});

export const api = createApi({
  reducerPath: 'api',
  baseQuery,
  tagTypes: [
    'Tasks',
    'Memos',
    'Projects',
    'Stashes',
    'Attachments',
    'Pushes',
    'Notebooks',
    'Notes',
  ],
  endpoints: () => ({}),
});
