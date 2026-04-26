import { configureStore } from '@reduxjs/toolkit';
import tasksReducer from './slices/tasks';
import notesReducer from './slices/notes';
import projectsReducer from './slices/projects';

export const store = configureStore({
  reducer: {
    tasks: tasksReducer,
    notes: notesReducer,
    projects: projectsReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
