import { createSlice, PayloadAction } from '@reduxjs/toolkit';

interface UIState {
  tasksActiveSearch: string | null;
  notesActiveSearch: string | null;
  tasksSelectedProjectId: string | null;
}

const initialState: UIState = {
  tasksActiveSearch: null,
  notesActiveSearch: null,
  tasksSelectedProjectId: null,
};

const uiSlice = createSlice({
  name: 'ui',
  initialState,
  reducers: {
    setTasksActiveSearch(state, action: PayloadAction<string | null>) {
      state.tasksActiveSearch = action.payload;
    },
    setNotesActiveSearch(state, action: PayloadAction<string | null>) {
      state.notesActiveSearch = action.payload;
    },
    setTasksSelectedProjectId(state, action: PayloadAction<string | null>) {
      state.tasksSelectedProjectId = action.payload;
    },
  },
});

export const { setTasksActiveSearch, setNotesActiveSearch, setTasksSelectedProjectId } =
  uiSlice.actions;
export default uiSlice.reducer;
