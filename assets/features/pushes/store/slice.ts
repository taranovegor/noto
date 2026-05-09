import { createSlice, PayloadAction } from '@reduxjs/toolkit';

interface PushState {
  sourceChecksum: string | null;
}

const initialState: PushState = {
  sourceChecksum: null,
};

const pushSlice = createSlice({
  name: 'push',
  initialState,
  reducers: {
    setSourceChecksum(state, action: PayloadAction<string | null>) {
      state.sourceChecksum = action.payload;
    },
  },
});

export const { setSourceChecksum } = pushSlice.actions;
export default pushSlice.reducer;
