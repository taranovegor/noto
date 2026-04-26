import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { ProjectResponseDto } from '../../types/projects';
import { api } from '../../api';

interface ProjectsState {
  projects: ProjectResponseDto[];
  loading: boolean;
  error: string | null;
  initialized: boolean;
}

const initialState: ProjectsState = {
  projects: [],
  loading: false,
  error: null,
  initialized: false,
};

export const loadProjects = createAsyncThunk(
  'projects/loadProjects',
  async () => {
    const response = await api.projects.list();
    return response.data;
  }
);

const projectsSlice = createSlice({
  name: 'projects',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(loadProjects.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loadProjects.fulfilled, (state, action) => {
        state.projects = action.payload;
        state.loading = false;
        state.initialized = true;
      })
      .addCase(loadProjects.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || 'Failed to load projects';
      });
  },
});

export default projectsSlice.reducer;
