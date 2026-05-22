import { createSlice, PayloadAction } from '@reduxjs/toolkit';

interface UIState {
  tasksActiveSearch: string | null;
  memosActiveSearch: string | null;
  tasksSelectedProjectId: string | null;
}

const initialState: UIState = {
  tasksActiveSearch: null,
  memosActiveSearch: null,
  tasksSelectedProjectId: null,
};

const uiSlice = createSlice({
  name: 'ui',
  initialState,
  reducers: {
    setTasksActiveSearch(state, action: PayloadAction<string | null>) {
      state.tasksActiveSearch = action.payload;
    },
    setMemosActiveSearch(state, action: PayloadAction<string | null>) {
      state.memosActiveSearch = action.payload;
    },
    setTasksSelectedProjectId(state, action: PayloadAction<string | null>) {
      state.tasksSelectedProjectId = action.payload;
    },
  },
});

export const { setTasksActiveSearch, setMemosActiveSearch, setTasksSelectedProjectId } =
  uiSlice.actions;
export default uiSlice.reducer;
