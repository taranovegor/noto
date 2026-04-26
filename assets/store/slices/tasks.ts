import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import { TaskResponseDto } from '../../types/tasks';
import { ProjectResponseDto } from '../../types/projects';
import { api } from '../../api';

interface TasksState {
  tasks: TaskResponseDto[];
  projects: ProjectResponseDto[];
  loading: boolean;
  error: string | null;
  activeSearch: string | null;
  initialized: boolean;
  lastSearchQuery: string | null;
  scrollPositions: { [status: string]: number };
}

const initialState: TasksState = {
  tasks: [],
  projects: [],
  loading: false,
  error: null,
  activeSearch: null,
  initialized: false,
  lastSearchQuery: null,
  scrollPositions: {},
};

export const loadTasks = createAsyncThunk(
  'tasks/loadTasks',
  async (search: string | null) => {
    const [tasksData, projectsData] = await Promise.all([
      search ? api.tasks.search(search) : api.tasks.list(),
      api.projects.list(),
    ]);
    return { tasksData: tasksData.data, projectsData: projectsData.data };
  }
);

const tasksSlice = createSlice({
  name: 'tasks',
  initialState,
  reducers: {
    setActiveSearch(state, action: PayloadAction<string | null>) {
      state.activeSearch = action.payload;
    },
    setScrollPosition(state, action: PayloadAction<{ status: string; position: number }>) {
      state.scrollPositions[action.payload.status] = action.payload.position;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loadTasks.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loadTasks.fulfilled, (state, action) => {
        state.tasks = action.payload.tasksData;
        state.projects = action.payload.projectsData;
        state.loading = false;
        state.initialized = true;
        state.lastSearchQuery = state.activeSearch;
      })
      .addCase(loadTasks.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || 'Failed to load tasks';
      });
  },
});

export const { setActiveSearch, setScrollPosition } = tasksSlice.actions;
export default tasksSlice.reducer;
